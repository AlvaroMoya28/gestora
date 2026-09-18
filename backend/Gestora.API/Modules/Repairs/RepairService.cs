using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Inventory;
using Gestora.API.Modules.Sales;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Repairs;

/// <summary>
/// Reparaciones: trabajo sobre un artículo del cliente, no venta de inventario.
///
/// El recorrido tiene dos puntos donde algo real ocurre. Al <b>terminar</b>, el material
/// usado sale del inventario: es cuando de verdad se consumió, no cuando se planificó ni
/// cuando el cliente pasa a recoger. Al <b>entregar</b> nace la cuenta por cobrar, porque
/// hasta que el artículo no sale del taller no hay nada que cobrar.
/// </summary>
public class RepairService(GestoraDbContext db, IAuditService audit, InventoryService inventory,
    ReceivableService receivables)
{
    public async Task<PagedResult<RepairOrderSummaryDto>> ListAsync(RepairQuery q)
    {
        var query = db.RepairOrders.AsNoTracking().Include(r => r.Customer).AsQueryable();

        if (q.CustomerId is { } customerId) query = query.Where(r => r.CustomerId == customerId);
        if (q.Status is { } status) query = query.Where(r => r.Status == status);
        if (q.Late == true)
        {
            var today = DateTime.UtcNow.Date;
            query = query.Where(r => r.PromisedAt != null && r.PromisedAt < today
                && r.Status != RepairStatus.Delivered && r.Status != RepairStatus.Cancelled);
        }

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(r => r.Number.Contains(term) || r.Customer.Name.Contains(term)
                || r.ItemDescription.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            // Primero lo que sigue vivo en el taller; lo entregado queda al final.
            .OrderBy(r => r.Status == RepairStatus.Delivered || r.Status == RepairStatus.Cancelled)
            .ThenBy(r => r.PromisedAt ?? r.ReceivedAt)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        var items = rows.Select(r => new RepairOrderSummaryDto(r.Id, r.Number, r.Customer.Name,
            r.ItemDescription, r.Status, StatusName(r.Status), r.ReceivedAt, r.PromisedAt, r.Total,
            IsLate(r))).ToList();

        return new PagedResult<RepairOrderSummaryDto>(items, total, q.Page, q.PageSize);
    }

    public async Task<RepairOrderDto> GetAsync(int id) => Map(await LoadAsync(id));

    public async Task<RepairOrderDto> CreateAsync(RepairOrderRequest request)
    {
        var customer = await ValidateCustomerAsync(request.CustomerId);

        var repair = new RepairOrder
        {
            Number = await ResolveNumberAsync(),
            CustomerId = request.CustomerId,
            ItemDescription = request.ItemDescription.Trim(),
            ReportedIssue = request.ReportedIssue?.Trim(),
            Diagnosis = request.Diagnosis?.Trim(),
            ReceivedAt = request.ReceivedAt ?? DateTime.UtcNow,
            PromisedAt = request.PromisedAt,
            LaborCost = request.LaborCost,
            TaxRate = request.TaxRate,
            PaymentTerm = request.PaymentTerm,
            CreditDays = request.PaymentTerm == PaymentTerm.Credit ? request.CreditDays : 0,
            Notes = request.Notes?.Trim(),
            Status = RepairStatus.Received
        };

        await ApplyMaterialsAsync(repair, request.Materials);
        db.RepairOrders.Add(repair);

        audit.Track("Creación", "repairs", nameof(RepairOrder), null,
            $"Reparación {repair.Number}: {repair.ItemDescription} de {customer.Name}");
        await db.SaveChangesAsync();

        return await GetAsync(repair.Id);
    }

    public async Task<RepairOrderDto> UpdateAsync(int id, RepairOrderRequest request)
    {
        var repair = await LoadAsync(id);
        EnsureEditable(repair);

        await ValidateCustomerAsync(request.CustomerId);

        repair.CustomerId = request.CustomerId;
        repair.ItemDescription = request.ItemDescription.Trim();
        repair.ReportedIssue = request.ReportedIssue?.Trim();
        repair.Diagnosis = request.Diagnosis?.Trim();
        repair.ReceivedAt = request.ReceivedAt ?? repair.ReceivedAt;
        repair.PromisedAt = request.PromisedAt;
        repair.LaborCost = request.LaborCost;
        repair.TaxRate = request.TaxRate;
        repair.PaymentTerm = request.PaymentTerm;
        repair.CreditDays = request.PaymentTerm == PaymentTerm.Credit ? request.CreditDays : 0;
        repair.Notes = request.Notes?.Trim();

        db.RepairMaterials.RemoveRange(repair.Materials);
        repair.Materials.Clear();
        await ApplyMaterialsAsync(repair, request.Materials);

        audit.Track("Actualización", "repairs", nameof(RepairOrder), id, $"Reparación {repair.Number}");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    public async Task<RepairOrderDto> StartAsync(int id)
    {
        var repair = await LoadAsync(id);
        if (repair.Status != RepairStatus.Received)
            throw new ApiException("Solo se puede poner en proceso una reparación recién recibida.");

        repair.Status = RepairStatus.InProgress;
        audit.Track("En proceso", "repairs", nameof(RepairOrder), id,
            $"Reparación {repair.Number} en proceso");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>
    /// Termina el trabajo: acá sale del inventario el material que se usó. Si falta
    /// existencia de alguno, no se descarga nada: el estado del taller y el del
    /// inventario tienen que coincidir siempre.
    /// </summary>
    public async Task<RepairOrderDto> CompleteAsync(int id, CompleteRepairRequest request)
    {
        var strategy = db.Database.CreateExecutionStrategy();
        await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var repair = await LoadAsync(id);
            if (repair.Status is not (RepairStatus.Received or RepairStatus.InProgress))
                throw new ApiException("Esta reparación ya fue terminada, entregada o cancelada.");

            if (request.Diagnosis is not null) repair.Diagnosis = request.Diagnosis.Trim();
            if (request.LaborCost is { } labor)
            {
                repair.LaborCost = labor;
                RecalculateTotals(repair);
            }

            foreach (var material in repair.Materials)
            {
                // Los servicios cargados a la orden se cobran, pero no tienen existencia.
                if (material.Product.Type == ProductType.Service) continue;

                await inventory.ApplyMovementAsync(new MovementRequest
                {
                    ProductId = material.ProductId,
                    Type = MovementType.RepairConsumption,
                    Quantity = material.Quantity,
                    UnitCost = material.Product.Cost,
                    Reason = $"Reparación {repair.Number}",
                    ReferenceType = nameof(RepairOrder),
                    ReferenceId = repair.Id
                });
            }

            repair.Status = RepairStatus.Ready;
            repair.CompletedAt = DateTime.UtcNow;

            audit.Track("Terminada", "repairs", nameof(RepairOrder), id,
                $"Reparación {repair.Number} terminada · material descargado por {repair.MaterialsCost:N2}");

            await db.SaveChangesAsync();
            await transaction.CommitAsync();
        });

        return await GetAsync(id);
    }

    /// <summary>Entrega al cliente: nace la cuenta por cobrar por el total de la orden.</summary>
    public async Task<RepairOrderDto> DeliverAsync(int id)
    {
        var strategy = db.Database.CreateExecutionStrategy();
        await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var repair = await LoadAsync(id);
            if (repair.Status != RepairStatus.Ready)
                throw new ApiException("Solo se puede entregar una reparación terminada.");

            repair.Status = RepairStatus.Delivered;
            repair.DeliveredAt = DateTime.UtcNow;

            receivables.AddForRepair(repair);

            audit.Track("Entrega", "repairs", nameof(RepairOrder), id,
                $"Reparación {repair.Number} entregada a {repair.Customer.Name} · cuenta por cobrar por {repair.Total:N2}");

            await db.SaveChangesAsync();
            await transaction.CommitAsync();
        });

        return await GetAsync(id);
    }

    public async Task<RepairOrderDto> CancelAsync(int id)
    {
        var repair = await LoadAsync(id);

        if (repair.Status == RepairStatus.Delivered)
            throw new ApiException("Una reparación entregada no se puede cancelar.");
        if (repair.Status == RepairStatus.Ready)
            throw new ApiException("Esta reparación ya consumió material del inventario; no se puede cancelar.");
        if (repair.Status == RepairStatus.Cancelled)
            throw new ApiException("La reparación ya está cancelada.");

        repair.Status = RepairStatus.Cancelled;
        audit.Track("Cancelación", "repairs", nameof(RepairOrder), id,
            $"Reparación {repair.Number} cancelada");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    private async Task ApplyMaterialsAsync(RepairOrder repair, List<RepairMaterialRequest> materials)
    {
        if (materials.Count > 0)
        {
            var ids = materials.Select(m => m.ProductId).Distinct().ToList();
            var products = await db.Products.Where(p => ids.Contains(p.Id)).ToDictionaryAsync(p => p.Id);

            foreach (var line in materials)
            {
                if (!products.TryGetValue(line.ProductId, out var product) || !product.IsActive)
                    throw new ApiException("Uno de los materiales seleccionados no es válido.");

                repair.Materials.Add(new RepairMaterial
                {
                    ProductId = line.ProductId,
                    Quantity = line.Quantity,
                    UnitPrice = line.UnitPrice,
                    Subtotal = line.Quantity * line.UnitPrice
                });
            }
        }

        RecalculateTotals(repair);
    }

    private static void RecalculateTotals(RepairOrder repair)
    {
        repair.MaterialsCost = repair.Materials.Sum(m => m.Subtotal);
        var taxable = repair.LaborCost + repair.MaterialsCost;
        repair.TaxAmount = taxable * repair.TaxRate / 100m;
        repair.Total = taxable + repair.TaxAmount;
    }

    private static void EnsureEditable(RepairOrder repair)
    {
        if (repair.Status is RepairStatus.Delivered or RepairStatus.Cancelled)
            throw new ApiException("Una reparación entregada o cancelada ya no se puede editar.");

        if (repair.Status == RepairStatus.Ready)
            throw new ApiException(
                "Esta reparación ya descargó su material del inventario; sus líneas no se pueden cambiar.");
    }

    private async Task<Customer> ValidateCustomerAsync(int customerId) =>
        await db.Customers.AsNoTracking().FirstOrDefaultAsync(c => c.Id == customerId && c.IsActive)
        ?? throw new ApiException("El cliente seleccionado no es válido.");

    private async Task<string> ResolveNumberAsync()
    {
        var numbers = await db.RepairOrders.Select(r => r.Number).ToListAsync();
        return CodeGenerator.Next("REP", numbers);
    }

    private async Task<RepairOrder> LoadAsync(int id) =>
        await db.RepairOrders
            .Include(r => r.Customer)
            .Include(r => r.Materials).ThenInclude(m => m.Product).ThenInclude(p => p.Unit)
            .FirstOrDefaultAsync(r => r.Id == id)
        ?? throw ApiException.NotFound("La reparación");

    private static bool IsLate(RepairOrder r) =>
        r.PromisedAt is { } promised && promised.Date < DateTime.UtcNow.Date
        && r.Status is not (RepairStatus.Delivered or RepairStatus.Cancelled);

    internal static string StatusName(RepairStatus status) => status switch
    {
        RepairStatus.Received => "Recibida",
        RepairStatus.InProgress => "En proceso",
        RepairStatus.Ready => "Lista para entregar",
        RepairStatus.Delivered => "Entregada",
        RepairStatus.Cancelled => "Cancelada",
        _ => status.ToString()
    };

    private static RepairOrderDto Map(RepairOrder r) => new(
        r.Id, r.Number, r.CustomerId, r.Customer.Name, r.ItemDescription, r.ReportedIssue,
        r.Diagnosis, r.Status, StatusName(r.Status), r.ReceivedAt, r.PromisedAt, r.CompletedAt,
        r.DeliveredAt, r.LaborCost, r.MaterialsCost, r.TaxRate, r.TaxAmount, r.Total,
        r.PaymentTerm, r.CreditDays, r.Notes, IsLate(r),
        r.Materials.Select(m => new RepairMaterialDto(m.Id, m.ProductId, m.Product.Code,
            m.Product.Name, m.Product.Unit.Abbreviation, m.Quantity, m.UnitPrice, m.Subtotal,
            m.Product.Stock)).ToList());
}
