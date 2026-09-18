using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Inventory;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Sales;

/// <summary>
/// Ventas. Espejo de <see cref="Purchasing.PurchaseService"/>: el borrador se edita
/// libremente y confirmar es el punto sin retorno, donde en una sola transacción sale
/// el inventario de cada línea y nace la cuenta por cobrar.
///
/// La cuenta por cobrar se crea también en las ventas de contado, con vencimiento el
/// mismo día. Registrar la deuda y su cobro por separado —en vez de marcar la venta
/// como pagada— es lo que permite que una venta de contado que nadie llegó a pagar
/// aparezca como pendiente en lugar de perderse.
/// </summary>
public class SaleService(GestoraDbContext db, IAuditService audit, InventoryService inventory)
{
    public async Task<PagedResult<SaleSummaryDto>> ListAsync(SaleQuery q)
    {
        var query = db.Sales.AsNoTracking().Include(s => s.Customer).AsQueryable();

        if (q.CustomerId is { } customerId) query = query.Where(s => s.CustomerId == customerId);
        if (q.Status is { } status) query = query.Where(s => s.Status == status);
        if (q.From is { } from) query = query.Where(s => s.Date >= from);
        if (q.To is { } to) query = query.Where(s => s.Date <= to);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(s => s.Number.Contains(term) || s.Customer.Name.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderByDescending(s => s.Date).ThenByDescending(s => s.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<SaleSummaryDto>(rows.Select(MapSummary).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<SaleDto> GetAsync(int id) => Map(await LoadAsync(id));

    public async Task<SaleDto> CreateAsync(SaleRequest request)
    {
        var customer = await ValidateCustomerAsync(request.CustomerId);

        var sale = new Sale
        {
            Number = await ResolveNumberAsync(),
            CustomerId = request.CustomerId,
            Date = request.Date ?? DateTime.UtcNow,
            PaymentTerm = request.PaymentTerm,
            CreditDays = request.PaymentTerm == PaymentTerm.Credit ? request.CreditDays : 0,
            Notes = request.Notes?.Trim(),
            Status = SaleStatus.Draft
        };

        await ApplyItemsAsync(sale, request.Items);
        db.Sales.Add(sale);

        audit.Track("Creación", "sales", nameof(Sale), null,
            $"Venta {sale.Number} a {customer.Name} por {sale.Total:N2}");
        await db.SaveChangesAsync();

        return await GetAsync(sale.Id);
    }

    public async Task<SaleDto> UpdateAsync(int id, SaleRequest request)
    {
        var sale = await LoadAsync(id);
        if (sale.Status != SaleStatus.Draft)
            throw new ApiException("Solo se puede editar una venta mientras está en borrador.");

        await ValidateCustomerAsync(request.CustomerId);

        sale.CustomerId = request.CustomerId;
        sale.Date = request.Date ?? sale.Date;
        sale.PaymentTerm = request.PaymentTerm;
        sale.CreditDays = request.PaymentTerm == PaymentTerm.Credit ? request.CreditDays : 0;
        sale.Notes = request.Notes?.Trim();

        db.SaleItems.RemoveRange(sale.Items);
        sale.Items.Clear();
        await ApplyItemsAsync(sale, request.Items);

        audit.Track("Actualización", "sales", nameof(Sale), id, $"Venta {sale.Number}");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>
    /// Punto sin retorno: sale el inventario, entra el derecho de cobro.
    /// Si alguna línea no tiene existencia suficiente, la venta entera se revierte:
    /// no se despacha media factura.
    /// </summary>
    public async Task<SaleDto> ConfirmAsync(int id)
    {
        var strategy = db.Database.CreateExecutionStrategy();
        await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var sale = await LoadAsync(id);
            if (sale.Status != SaleStatus.Draft)
                throw new ApiException("La venta ya fue confirmada o está cancelada.");

            if (sale.Items.Count == 0)
                throw new ApiException("La venta no tiene líneas que despachar.");

            foreach (var item in sale.Items)
            {
                // El costo se congela al confirmar: es el costo real de lo que sale hoy,
                // y permite calcular la utilidad aunque el producto se encarezca después.
                item.UnitCost = item.Product.Cost;

                // Los servicios no llevan existencia: se facturan sin mover inventario.
                if (item.Product.Type == ProductType.Service) continue;

                await inventory.ApplyMovementAsync(new MovementRequest
                {
                    ProductId = item.ProductId,
                    Type = MovementType.Sale,
                    Quantity = item.Quantity,
                    UnitCost = item.UnitCost,
                    Reason = $"Venta {sale.Number}",
                    ReferenceType = nameof(Sale),
                    ReferenceId = sale.Id
                });
            }

            sale.Status = SaleStatus.Confirmed;

            var receivable = new AccountReceivable
            {
                CompanyId = sale.CompanyId,
                CustomerId = sale.CustomerId,
                SaleId = sale.Id,
                DocumentNumber = sale.Number,
                IssueDate = sale.Date,
                DueDate = DueDateOf(sale),
                Total = sale.Total,
                CollectedAmount = 0,
                Balance = sale.Total,
                Status = ReceivableStatus.Pending
            };
            db.AccountsReceivable.Add(receivable);

            audit.Track("Confirmación", "sales", nameof(Sale), id,
                $"Venta {sale.Number} confirmada · inventario descargado · cuenta por cobrar por {sale.Total:N2}");

            await db.SaveChangesAsync();
            await transaction.CommitAsync();
        });

        return await GetAsync(id);
    }

    public async Task<SaleDto> CancelAsync(int id)
    {
        var sale = await LoadAsync(id);
        if (sale.Status != SaleStatus.Draft)
            throw new ApiException("Solo se puede cancelar una venta que sigue en borrador.");

        sale.Status = SaleStatus.Cancelled;
        audit.Track("Cancelación", "sales", nameof(Sale), id, $"Venta {sale.Number} cancelada");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    private async Task ApplyItemsAsync(Sale sale, List<SaleItemRequest> items)
    {
        var productIds = items.Select(i => i.ProductId).Distinct().ToList();
        var products = await db.Products.Where(p => productIds.Contains(p.Id)).ToDictionaryAsync(p => p.Id);

        foreach (var line in items)
        {
            if (!products.TryGetValue(line.ProductId, out var product) || !product.IsActive)
                throw new ApiException("Uno de los productos seleccionados no es válido.");

            var gross = line.Quantity * line.UnitPrice;
            var subtotal = gross - gross * line.DiscountRate / 100m;

            sale.Items.Add(new SaleItem
            {
                ProductId = line.ProductId,
                Quantity = line.Quantity,
                UnitPrice = line.UnitPrice,
                DiscountRate = line.DiscountRate,
                TaxRate = line.TaxRate,
                Subtotal = subtotal,
                UnitCost = product.Cost
            });
        }

        sale.Subtotal = sale.Items.Sum(i => i.Subtotal);
        sale.DiscountAmount = sale.Items.Sum(i => i.Quantity * i.UnitPrice * i.DiscountRate / 100m);
        sale.TaxAmount = sale.Items.Sum(i => i.Subtotal * i.TaxRate / 100m);
        sale.Total = sale.Subtotal + sale.TaxAmount;
    }

    private async Task<Customer> ValidateCustomerAsync(int customerId)
    {
        return await db.Customers.AsNoTracking().FirstOrDefaultAsync(c => c.Id == customerId && c.IsActive)
            ?? throw new ApiException("El cliente seleccionado no es válido.");
    }

    private async Task<string> ResolveNumberAsync()
    {
        var numbers = await db.Sales.Select(s => s.Number).ToListAsync();
        return CodeGenerator.Next("VEN", numbers);
    }

    private async Task<Sale> LoadAsync(int id) =>
        await db.Sales
            .Include(s => s.Customer)
            .Include(s => s.Items).ThenInclude(i => i.Product).ThenInclude(p => p.Unit)
            .FirstOrDefaultAsync(s => s.Id == id)
        ?? throw ApiException.NotFound("La venta");

    internal static string StatusName(SaleStatus status) => status switch
    {
        SaleStatus.Draft => "Borrador",
        SaleStatus.Confirmed => "Confirmada",
        SaleStatus.Cancelled => "Cancelada",
        _ => status.ToString()
    };

    /// <summary>Cuándo vence: la fecha del documento más su plazo. Una sola definición.</summary>
    private static DateTime DueDateOf(Sale s) => s.Date.AddDays(Math.Max(s.CreditDays, 0));

    private static SaleSummaryDto MapSummary(Sale s) => new(s.Id, s.Number, s.Customer.Name, s.Date,
        DueDateOf(s), s.Status, StatusName(s.Status), s.PaymentTerm, s.Total);

    private static SaleDto Map(Sale s) => new(s.Id, s.Number, s.CustomerId, s.Customer.Name, s.Date,
        s.Status, StatusName(s.Status), s.PaymentTerm, s.CreditDays, DueDateOf(s),
        s.Subtotal, s.DiscountAmount, s.TaxAmount, s.Total, s.Notes,
        s.Items.Select(i => new SaleItemDto(i.Id, i.ProductId, i.Product.Code, i.Product.Name,
            i.Product.Unit.Abbreviation, i.Quantity, i.UnitPrice, i.DiscountRate, i.TaxRate, i.Subtotal))
            .ToList());
}
