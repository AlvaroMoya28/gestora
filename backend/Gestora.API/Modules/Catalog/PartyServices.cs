using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Catalog;

public class CustomerService(GestoraDbContext db, IAuditService audit)
{
    public async Task<PagedResult<CustomerDto>> ListAsync(QueryParams q)
    {
        var query = db.Customers.AsNoTracking().AsQueryable();

        if (q.Active is { } active) query = query.Where(c => c.IsActive == active);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(c => c.Name.Contains(term) || c.Code.Contains(term)
                || (c.TradeName != null && c.TradeName.Contains(term))
                || (c.TaxId != null && c.TaxId.Contains(term)));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(c => c.Name)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<CustomerDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<CustomerDto> GetAsync(int id)
    {
        var customer = await db.Customers.AsNoTracking().FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("El cliente");
        return Map(customer);
    }

    public async Task<CustomerDto> CreateAsync(CustomerRequest request)
    {
        var code = await ResolveCodeAsync(request.Code, null);

        var customer = new Customer { Code = code };
        Apply(customer, request);
        db.Customers.Add(customer);

        audit.Track("Creación", "customers", nameof(Customer), null,
            $"Cliente {code} - {customer.Name}");
        await db.SaveChangesAsync();

        return Map(customer);
    }

    public async Task<CustomerDto> UpdateAsync(int id, CustomerRequest request)
    {
        var customer = await db.Customers.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("El cliente");

        var before = $"{customer.Name} | crédito {customer.CreditDays}d | límite {customer.CreditLimit:N2}";
        customer.Code = await ResolveCodeAsync(request.Code, id);
        Apply(customer, request);

        audit.Track("Actualización", "customers", nameof(Customer), id,
            $"Cliente {customer.Code}", before,
            $"{customer.Name} | crédito {customer.CreditDays}d | límite {customer.CreditLimit:N2}");
        await db.SaveChangesAsync();

        return Map(customer);
    }

    /// <summary>
    /// Los clientes no se eliminan: se inactivan. Un borrado físico rompería el
    /// historial de ventas y cuentas por cobrar que apunta a ellos.
    /// </summary>
    public async Task<CustomerDto> SetActiveAsync(int id, bool active)
    {
        var customer = await db.Customers.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("El cliente");

        if (customer.IsActive == active) return Map(customer);

        customer.IsActive = active;
        audit.Track(active ? "Reactivación" : "Inactivación", "customers", nameof(Customer), id,
            $"Cliente {customer.Code} - {customer.Name}");
        await db.SaveChangesAsync();

        return Map(customer);
    }

    private async Task<string> ResolveCodeAsync(string? requested, int? currentId)
    {
        var code = requested?.Trim().ToUpperInvariant();

        if (string.IsNullOrEmpty(code))
        {
            var codes = await db.Customers.Select(c => c.Code).ToListAsync();
            return CodeGenerator.Next("CLI", codes);
        }

        var taken = await db.Customers.AnyAsync(c => c.Code == code && c.Id != currentId);
        if (taken) throw ApiException.Conflict($"Ya existe un cliente con el código {code}.");

        return code;
    }

    private static void Apply(Customer c, CustomerRequest r)
    {
        c.Name = r.Name.Trim();
        c.TradeName = r.TradeName?.Trim();
        c.TaxId = r.TaxId?.Trim();
        c.Phone = r.Phone?.Trim();
        c.Email = r.Email?.Trim().ToLowerInvariant();
        c.Address = r.Address?.Trim();
        c.ContactName = r.ContactName?.Trim();
        c.PaymentTerm = r.PaymentTerm;
        c.CreditDays = r.PaymentTerm == PaymentTerm.Credit ? r.CreditDays : 0;
        c.CreditLimit = r.CreditLimit;
        c.Notes = r.Notes?.Trim();
    }

    private static CustomerDto Map(Customer c) => new(c.Id, c.Code, c.Name, c.TradeName, c.TaxId,
        c.Phone, c.Email, c.Address, c.ContactName, c.PaymentTerm, c.CreditDays, c.CreditLimit,
        c.Notes, c.IsActive);
}

public class SupplierService(GestoraDbContext db, IAuditService audit)
{
    public async Task<PagedResult<SupplierDto>> ListAsync(QueryParams q)
    {
        var query = db.Suppliers.AsNoTracking().AsQueryable();

        if (q.Active is { } active) query = query.Where(s => s.IsActive == active);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(s => s.Name.Contains(term) || s.Code.Contains(term)
                || (s.TradeName != null && s.TradeName.Contains(term))
                || (s.TaxId != null && s.TaxId.Contains(term)));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(s => s.Name)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<SupplierDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<SupplierDto> GetAsync(int id)
    {
        var supplier = await db.Suppliers.AsNoTracking().FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("El proveedor");
        return Map(supplier);
    }

    public async Task<SupplierDto> CreateAsync(SupplierRequest request)
    {
        var supplier = new Supplier { Code = await ResolveCodeAsync(request.Code, null) };
        Apply(supplier, request);
        db.Suppliers.Add(supplier);

        audit.Track("Creación", "suppliers", nameof(Supplier), null,
            $"Proveedor {supplier.Code} - {supplier.Name}");
        await db.SaveChangesAsync();

        return Map(supplier);
    }

    public async Task<SupplierDto> UpdateAsync(int id, SupplierRequest request)
    {
        var supplier = await db.Suppliers.FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("El proveedor");

        var before = $"{supplier.Name} | crédito {supplier.CreditDays}d";
        supplier.Code = await ResolveCodeAsync(request.Code, id);
        Apply(supplier, request);

        audit.Track("Actualización", "suppliers", nameof(Supplier), id,
            $"Proveedor {supplier.Code}", before, $"{supplier.Name} | crédito {supplier.CreditDays}d");
        await db.SaveChangesAsync();

        return Map(supplier);
    }

    public async Task<SupplierDto> SetActiveAsync(int id, bool active)
    {
        var supplier = await db.Suppliers.FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("El proveedor");

        if (supplier.IsActive == active) return Map(supplier);

        supplier.IsActive = active;
        audit.Track(active ? "Reactivación" : "Inactivación", "suppliers", nameof(Supplier), id,
            $"Proveedor {supplier.Code} - {supplier.Name}");
        await db.SaveChangesAsync();

        return Map(supplier);
    }

    private async Task<string> ResolveCodeAsync(string? requested, int? currentId)
    {
        var code = requested?.Trim().ToUpperInvariant();

        if (string.IsNullOrEmpty(code))
        {
            var codes = await db.Suppliers.Select(s => s.Code).ToListAsync();
            return CodeGenerator.Next("PRV", codes);
        }

        var taken = await db.Suppliers.AnyAsync(s => s.Code == code && s.Id != currentId);
        if (taken) throw ApiException.Conflict($"Ya existe un proveedor con el código {code}.");

        return code;
    }

    private static void Apply(Supplier s, SupplierRequest r)
    {
        s.Name = r.Name.Trim();
        s.TradeName = r.TradeName?.Trim();
        s.TaxId = r.TaxId?.Trim();
        s.Phone = r.Phone?.Trim();
        s.Email = r.Email?.Trim().ToLowerInvariant();
        s.Address = r.Address?.Trim();
        s.ContactName = r.ContactName?.Trim();
        s.PaymentTerm = r.PaymentTerm;
        s.CreditDays = r.PaymentTerm == PaymentTerm.Credit ? r.CreditDays : 0;
        s.Notes = r.Notes?.Trim();
    }

    private static SupplierDto Map(Supplier s) => new(s.Id, s.Code, s.Name, s.TradeName, s.TaxId,
        s.Phone, s.Email, s.Address, s.ContactName, s.PaymentTerm, s.CreditDays, s.Notes, s.IsActive);
}
