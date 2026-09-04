using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Inventory;

public class MovementRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un producto.")]
    public int ProductId { get; set; }

    [Required] public MovementType Type { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal Quantity { get; set; }

    public decimal? UnitCost { get; set; }

    [MaxLength(250)] public string? Reason { get; set; }
    [MaxLength(40)] public string? ReferenceType { get; set; }
    public int? ReferenceId { get; set; }

    /// <summary>
    /// Permite dejar la existencia en negativo. Reservado para ajustes autorizados;
    /// el flujo normal lo rechaza para no arrastrar inventario imposible.
    /// </summary>
    public bool AllowNegative { get; set; }

    /// <summary>
    /// Sentido explícito (+1 entrada, -1 salida). Solo lo usan los ajustes, que pueden
    /// ir en cualquier dirección; para el resto de tipos el sentido es fijo.
    /// </summary>
    public int? DirectionOverride { get; set; }
}

/// <summary>Ajuste por conteo físico: el usuario indica la existencia real, no el delta.</summary>
public class StockAdjustmentRequest
{
    [Range(1, int.MaxValue)] public int ProductId { get; set; }

    [Range(0, 999999999, ErrorMessage = "La existencia contada no puede ser negativa.")]
    public decimal CountedStock { get; set; }

    [Required(ErrorMessage = "Debe indicar el motivo del ajuste.")]
    [MaxLength(250)] public string Reason { get; set; } = string.Empty;
}

public record MovementDto(int Id, int ProductId, string ProductCode, string ProductName,
    MovementType Type, string TypeName, int Direction, decimal Quantity, decimal StockAfter,
    decimal? UnitCost, string? Reason, string? ReferenceType, int? ReferenceId,
    DateTime OccurredAt, string? UserName);

public class MovementQuery : QueryParams
{
    public int? ProductId { get; set; }
    public MovementType? Type { get; set; }
    public DateTime? From { get; set; }
    public DateTime? To { get; set; }
}

/// <summary>
/// Único punto del sistema autorizado a modificar existencias.
///
/// Toda variación pasa por <see cref="ApplyMovementAsync"/>, que dentro de una misma
/// transacción registra el asiento, recalcula el saldo del producto y deja auditoría.
/// Así el inventario siempre puede explicarse: no hay "stock = stock - 5" suelto.
/// </summary>
public class InventoryService(GestoraDbContext db, IAuditService audit, ICurrentUser current)
{
    /// <summary>Tipos que suman existencia; el resto resta.</summary>
    private static readonly HashSet<MovementType> Inbound =
    [
        MovementType.Purchase, MovementType.ProductionOutput,
        MovementType.ReturnIn, MovementType.InitialStock
    ];

    public static int DirectionOf(MovementType type) => Inbound.Contains(type) ? 1 : -1;

    public static string NameOf(MovementType type) => type switch
    {
        MovementType.Purchase => "Compra",
        MovementType.Sale => "Venta",
        MovementType.ProductionInput => "Consumo de producción",
        MovementType.ProductionOutput => "Producción terminada",
        MovementType.Adjustment => "Ajuste",
        MovementType.ReturnIn => "Devolución de cliente",
        MovementType.ReturnOut => "Devolución a proveedor",
        MovementType.RepairConsumption => "Consumo en reparación",
        MovementType.InitialStock => "Existencia inicial",
        _ => type.ToString()
    };

    /// <summary>
    /// Aplica un movimiento y devuelve el asiento resultante. Si ya hay una transacción
    /// abierta (por ejemplo, al confirmar una venta con varias líneas) se reutiliza,
    /// de modo que o se guarda todo o no se guarda nada.
    ///
    /// La transacción propia se abre dentro de la estrategia de reintentos de EF: con
    /// reintentos activos, una transacción iniciada a mano fuera de ella es rechazada.
    /// </summary>
    public async Task<MovementDto> ApplyMovementAsync(MovementRequest request)
    {
        if (request.Quantity <= 0)
            throw new ApiException("La cantidad del movimiento debe ser mayor que cero.");

        // Si el llamador ya abrió una transacción, este movimiento se suma a ella.
        if (db.Database.CurrentTransaction is not null)
            return await ExecuteAsync(request, null);

        var strategy = db.Database.CreateExecutionStrategy();
        return await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();
            return await ExecuteAsync(request, transaction);
        });
    }

    private async Task<MovementDto> ExecuteAsync(MovementRequest request,
        Microsoft.EntityFrameworkCore.Storage.IDbContextTransaction? transaction)
    {
        try
        {
            var product = await db.Products.Include(p => p.Unit)
                .FirstOrDefaultAsync(p => p.Id == request.ProductId)
                ?? throw ApiException.NotFound("El producto");

            if (!product.IsActive && request.Type != MovementType.Adjustment)
                throw new ApiException($"El producto {product.Code} está inactivo y no admite movimientos.");

            var direction = request.DirectionOverride ?? DirectionOf(request.Type);
            if (direction is not (1 or -1))
                throw new ApiException("El sentido del movimiento no es válido.");

            var newStock = product.Stock + direction * request.Quantity;

            if (newStock < 0 && !request.AllowNegative)
                throw ApiException.Conflict(
                    $"Existencia insuficiente de {product.Name}: hay {product.Stock:N2} " +
                    $"{product.Unit.Abbreviation} y se intentan sacar {request.Quantity:N2}.");

            product.Stock = newStock;

            var movement = new InventoryMovement
            {
                ProductId = product.Id,
                Type = request.Type,
                Direction = direction,
                Quantity = request.Quantity,
                StockAfter = newStock,
                UnitCost = request.UnitCost,
                Reason = request.Reason,
                ReferenceType = request.ReferenceType,
                ReferenceId = request.ReferenceId,
                OccurredAt = DateTime.UtcNow,
                UserId = current.IsAuthenticated ? current.UserId : null
            };
            db.InventoryMovements.Add(movement);

            audit.Track("Movimiento de inventario", "inventory", nameof(Product), product.Id,
                $"{NameOf(request.Type)} de {request.Quantity:N2} {product.Unit.Abbreviation} · {product.Name}",
                oldValue: $"Existencia: {newStock - direction * request.Quantity:N2}",
                newValue: $"Existencia: {newStock:N2}");

            await db.SaveChangesAsync();
            if (transaction is not null) await transaction.CommitAsync();

            movement.Product = product;
            return Map(movement, current.IsAuthenticated ? current.FullName : null);
        }
        catch
        {
            if (transaction is not null) await transaction.RollbackAsync();
            throw;
        }
    }

    /// <summary>Convierte un conteo físico en el movimiento de ajuste correspondiente.</summary>
    public async Task<MovementDto> AdjustAsync(StockAdjustmentRequest request)
    {
        var product = await db.Products.AsNoTracking().FirstOrDefaultAsync(p => p.Id == request.ProductId)
            ?? throw ApiException.NotFound("El producto");

        var difference = request.CountedStock - product.Stock;
        if (difference == 0)
            throw new ApiException("La existencia contada coincide con la registrada; no hay ajuste que aplicar.");

        return await ApplyMovementAsync(new MovementRequest
        {
            ProductId = request.ProductId,
            Type = MovementType.Adjustment,
            // El ajuste se guarda como cantidad positiva; el sentido lo da el signo del delta.
            Quantity = Math.Abs(difference),
            DirectionOverride = difference > 0 ? 1 : -1,
            Reason = $"{request.Reason} (contado {request.CountedStock:N2}, registrado {product.Stock:N2})",
            AllowNegative = true
        });
    }

    public async Task<PagedResult<MovementDto>> ListAsync(MovementQuery q)
    {
        var query = db.InventoryMovements.AsNoTracking()
            .Include(m => m.Product).Include(m => m.User)
            .AsQueryable();

        if (q.ProductId is { } productId) query = query.Where(m => m.ProductId == productId);
        if (q.Type is { } type) query = query.Where(m => m.Type == type);
        if (q.From is { } from) query = query.Where(m => m.OccurredAt >= from);
        if (q.To is { } to) query = query.Where(m => m.OccurredAt <= to);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(m => m.Product.Name.Contains(term) || m.Product.Code.Contains(term)
                || (m.Reason != null && m.Reason.Contains(term)));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderByDescending(m => m.OccurredAt).ThenByDescending(m => m.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        var items = rows.Select(m => Map(m, m.User?.FullName)).ToList();
        return new PagedResult<MovementDto>(items, total, q.Page, q.PageSize);
    }

    private static MovementDto Map(InventoryMovement m, string? userName) => new(
        m.Id, m.ProductId, m.Product?.Code ?? string.Empty, m.Product?.Name ?? string.Empty,
        m.Type, NameOf(m.Type), m.Direction, m.Quantity, m.StockAfter, m.UnitCost, m.Reason,
        m.ReferenceType, m.ReferenceId, m.OccurredAt, userName);
}
