using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Inventory;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Purchasing;

/// <summary>
/// Compras. Un borrador se edita libremente; al confirmar, en una sola transacción,
/// nace el movimiento de inventario de cada línea (vía <see cref="InventoryService"/>)
/// y la cuenta por pagar correspondiente. Si algo falla, ni el inventario ni la deuda
/// quedan a medias.
/// </summary>
public class PurchaseService(GestoraDbContext db, IAuditService audit, InventoryService inventory)
{
    public async Task<PagedResult<PurchaseSummaryDto>> ListAsync(PurchaseQuery q)
    {
        var query = db.Purchases.AsNoTracking().Include(p => p.Supplier).AsQueryable();

        if (q.SupplierId is { } supplierId) query = query.Where(p => p.SupplierId == supplierId);
        if (q.Status is { } status) query = query.Where(p => p.Status == status);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(p => p.Number.Contains(term) || p.Supplier.Name.Contains(term)
                || (p.SupplierInvoiceNumber != null && p.SupplierInvoiceNumber.Contains(term)));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderByDescending(p => p.Date).ThenByDescending(p => p.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<PurchaseSummaryDto>(rows.Select(MapSummary).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<PurchaseDto> GetAsync(int id)
    {
        var purchase = await LoadAsync(id);
        return Map(purchase);
    }

    public async Task<PurchaseDto> CreateAsync(PurchaseRequest request)
    {
        await ValidateSupplierAsync(request.SupplierId);

        var purchase = new Purchase
        {
            Number = await ResolveNumberAsync(),
            SupplierId = request.SupplierId,
            Date = request.Date ?? DateTime.UtcNow,
            SupplierInvoiceNumber = request.SupplierInvoiceNumber?.Trim(),
            Notes = request.Notes?.Trim(),
            Status = PurchaseStatus.Draft
        };

        await ApplyItemsAsync(purchase, request.Items);
        db.Purchases.Add(purchase);

        audit.Track("Creación", "purchases", nameof(Purchase), null,
            $"Compra {purchase.Number} a {(await db.Suppliers.FindAsync(request.SupplierId))?.Name}");
        await db.SaveChangesAsync();

        return await GetAsync(purchase.Id);
    }

    public async Task<PurchaseDto> UpdateAsync(int id, PurchaseRequest request)
    {
        var purchase = await LoadAsync(id);
        if (purchase.Status != PurchaseStatus.Draft)
            throw new ApiException("Solo se puede editar una compra mientras está en borrador.");

        await ValidateSupplierAsync(request.SupplierId);

        purchase.SupplierId = request.SupplierId;
        purchase.Date = request.Date ?? purchase.Date;
        purchase.SupplierInvoiceNumber = request.SupplierInvoiceNumber?.Trim();
        purchase.Notes = request.Notes?.Trim();

        db.PurchaseItems.RemoveRange(purchase.Items);
        purchase.Items.Clear();
        await ApplyItemsAsync(purchase, request.Items);

        audit.Track("Actualización", "purchases", nameof(Purchase), id, $"Compra {purchase.Number}");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>
    /// Punto sin retorno: entra el inventario, sale la deuda con el proveedor.
    /// Reutiliza una sola transacción para ambas cosas.
    /// </summary>
    public async Task<PurchaseDto> ConfirmAsync(int id)
    {
        var strategy = db.Database.CreateExecutionStrategy();
        await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var purchase = await LoadAsync(id);
            if (purchase.Status != PurchaseStatus.Draft)
                throw new ApiException("La compra ya fue confirmada o está cancelada.");

            var supplier = await db.Suppliers.FirstAsync(s => s.Id == purchase.SupplierId);

            foreach (var item in purchase.Items)
            {
                // Los servicios no llevan existencia: no generan movimiento de inventario.
                if (item.Product.Type == ProductType.Service) continue;

                await inventory.ApplyMovementAsync(new MovementRequest
                {
                    ProductId = item.ProductId,
                    Type = MovementType.Purchase,
                    Quantity = item.Quantity,
                    UnitCost = item.UnitCost,
                    Reason = $"Compra {purchase.Number}",
                    ReferenceType = nameof(Purchase),
                    ReferenceId = purchase.Id
                });

                item.Product.Cost = item.UnitCost;
            }

            purchase.Status = PurchaseStatus.Confirmed;

            var payable = new AccountPayable
            {
                CompanyId = purchase.CompanyId,
                SupplierId = purchase.SupplierId,
                PurchaseId = purchase.Id,
                DocumentNumber = purchase.SupplierInvoiceNumber ?? purchase.Number,
                IssueDate = purchase.Date,
                DueDate = purchase.Date.AddDays(Math.Max(supplier.CreditDays, 0)),
                Total = purchase.Total,
                PaidAmount = 0,
                Balance = purchase.Total,
                Status = PayableStatus.Pending
            };
            db.AccountsPayable.Add(payable);

            audit.Track("Confirmación", "purchases", nameof(Purchase), id,
                $"Compra {purchase.Number} confirmada · inventario actualizado · cuenta por pagar {payable.DocumentNumber} por {payable.Total:N2}");

            await db.SaveChangesAsync();
            await transaction.CommitAsync();
        });

        return await GetAsync(id);
    }

    public async Task<PurchaseDto> CancelAsync(int id)
    {
        var purchase = await LoadAsync(id);
        if (purchase.Status != PurchaseStatus.Draft)
            throw new ApiException("Solo se puede cancelar una compra que sigue en borrador.");

        purchase.Status = PurchaseStatus.Cancelled;
        audit.Track("Cancelación", "purchases", nameof(Purchase), id, $"Compra {purchase.Number} cancelada");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    private async Task ApplyItemsAsync(Purchase purchase, List<PurchaseItemRequest> items)
    {
        var productIds = items.Select(i => i.ProductId).Distinct().ToList();
        var products = await db.Products.Where(p => productIds.Contains(p.Id)).ToDictionaryAsync(p => p.Id);

        foreach (var line in items)
        {
            if (!products.TryGetValue(line.ProductId, out var product) || !product.IsActive)
                throw new ApiException("Uno de los productos seleccionados no es válido.");

            purchase.Items.Add(new PurchaseItem
            {
                ProductId = line.ProductId,
                Quantity = line.Quantity,
                UnitCost = line.UnitCost,
                TaxRate = line.TaxRate,
                Subtotal = line.Quantity * line.UnitCost
            });
        }

        purchase.Subtotal = purchase.Items.Sum(i => i.Subtotal);
        purchase.TaxAmount = purchase.Items.Sum(i => i.Subtotal * i.TaxRate / 100m);
        purchase.Total = purchase.Subtotal + purchase.TaxAmount;
    }

    private async Task ValidateSupplierAsync(int supplierId)
    {
        var exists = await db.Suppliers.AnyAsync(s => s.Id == supplierId && s.IsActive);
        if (!exists) throw new ApiException("El proveedor seleccionado no es válido.");
    }

    private async Task<string> ResolveNumberAsync()
    {
        var numbers = await db.Purchases.Select(p => p.Number).ToListAsync();
        return CodeGenerator.Next("COM", numbers);
    }

    private async Task<Purchase> LoadAsync(int id) =>
        await db.Purchases
            .Include(p => p.Supplier)
            .Include(p => p.Items).ThenInclude(i => i.Product).ThenInclude(pr => pr.Unit)
            .FirstOrDefaultAsync(p => p.Id == id)
        ?? throw ApiException.NotFound("La compra");

    private static PurchaseSummaryDto MapSummary(Purchase p) => new(p.Id, p.Number, p.Supplier.Name,
        p.Date, p.Status, StatusName(p.Status), p.Total);

    private static PurchaseDto Map(Purchase p) => new(p.Id, p.Number, p.SupplierId, p.Supplier.Name,
        p.Date, p.Status, StatusName(p.Status), p.Subtotal, p.TaxAmount, p.Total,
        p.SupplierInvoiceNumber, p.Notes,
        p.Items.Select(i => new PurchaseItemDto(i.Id, i.ProductId, i.Product.Code, i.Product.Name,
            i.Product.Unit.Abbreviation, i.Quantity, i.UnitCost, i.TaxRate, i.Subtotal)).ToList());

    private static string StatusName(PurchaseStatus status) => status switch
    {
        PurchaseStatus.Draft => "Borrador",
        PurchaseStatus.Confirmed => "Confirmada",
        PurchaseStatus.Cancelled => "Cancelada",
        _ => status.ToString()
    };
}
