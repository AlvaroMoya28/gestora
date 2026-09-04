using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Inventory;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Catalog;

public class ProductService(GestoraDbContext db, IAuditService audit, InventoryService inventory)
{
    public async Task<PagedResult<ProductDto>> ListAsync(ProductQuery q)
    {
        var query = db.Products.AsNoTracking()
            .Include(p => p.Category).Include(p => p.Unit)
            .AsQueryable();

        if (q.Active is { } active) query = query.Where(p => p.IsActive == active);
        if (q.Type is { } type) query = query.Where(p => p.Type == type);
        if (q.CategoryId is { } categoryId) query = query.Where(p => p.CategoryId == categoryId);
        if (q.LowStock == true) query = query.Where(p => p.Stock <= p.MinStock);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(p => p.Name.Contains(term) || p.Code.Contains(term)
                || (p.Barcode != null && p.Barcode.Contains(term)));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(p => p.Name)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<ProductDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<ProductDto> GetAsync(int id)
    {
        var product = await db.Products.AsNoTracking()
            .Include(p => p.Category).Include(p => p.Unit)
            .FirstOrDefaultAsync(p => p.Id == id) ?? throw ApiException.NotFound("El producto");
        return Map(product);
    }

    public async Task<ProductDto> CreateAsync(ProductRequest request)
    {
        await ValidateReferencesAsync(request);

        var code = await ResolveCodeAsync(request.Code, null);

        // Alta y existencia inicial son una sola operación: si el movimiento falla,
        // el producto tampoco queda creado.
        var strategy = db.Database.CreateExecutionStrategy();
        var productId = await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var product = new Product { Code = code };
            Apply(product, request);
            db.Products.Add(product);

            audit.Track("Creación", "products", nameof(Product), null,
                $"Producto {product.Code} - {product.Name}");
            await db.SaveChangesAsync();

            // La existencia inicial entra como movimiento, nunca como asignación directa.
            if (request.InitialStock > 0)
            {
                await inventory.ApplyMovementAsync(new MovementRequest
                {
                    ProductId = product.Id,
                    Type = MovementType.InitialStock,
                    Quantity = request.InitialStock,
                    UnitCost = request.Cost,
                    Reason = "Carga inicial al crear el producto"
                });
            }

            await transaction.CommitAsync();
            return product.Id;
        });

        return await GetAsync(productId);
    }

    public async Task<ProductDto> UpdateAsync(int id, ProductRequest request)
    {
        var product = await db.Products.FirstOrDefaultAsync(p => p.Id == id)
            ?? throw ApiException.NotFound("El producto");

        await ValidateReferencesAsync(request);

        var before = $"{product.Name} | costo {product.Cost:N2} | precio {product.Price:N2}";
        product.Code = await ResolveCodeAsync(request.Code, id);
        Apply(product, request);

        audit.Track("Actualización", "products", nameof(Product), id, $"Producto {product.Code}",
            before, $"{product.Name} | costo {product.Cost:N2} | precio {product.Price:N2}");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    public async Task<ProductDto> SetActiveAsync(int id, bool active)
    {
        var product = await db.Products.FirstOrDefaultAsync(p => p.Id == id)
            ?? throw ApiException.NotFound("El producto");

        if (product.IsActive != active)
        {
            product.IsActive = active;
            audit.Track(active ? "Reactivación" : "Inactivación", "products", nameof(Product), id,
                $"Producto {product.Code} - {product.Name}");
            await db.SaveChangesAsync();
        }

        return await GetAsync(id);
    }

    private async Task ValidateReferencesAsync(ProductRequest request)
    {
        var unitExists = await db.Units.AnyAsync(u => u.Id == request.UnitId && u.IsActive);
        if (!unitExists) throw new ApiException("La unidad de medida seleccionada no es válida.");

        if (request.CategoryId is { } categoryId)
        {
            var categoryExists = await db.Categories.AnyAsync(c => c.Id == categoryId && c.IsActive);
            if (!categoryExists) throw new ApiException("La categoría seleccionada no es válida.");
        }

        if (request.Price > 0 && request.Cost > request.Price)
        {
            // Es un aviso legítimo de negocio, no un error de datos: se permite pero
            // queda registrado para que el margen negativo no pase inadvertido.
            audit.Track("Advertencia", "products", nameof(Product), null,
                $"Producto '{request.Name}' guardado con costo mayor al precio de venta.");
        }
    }

    private async Task<string> ResolveCodeAsync(string? requested, int? currentId)
    {
        var code = requested?.Trim().ToUpperInvariant();

        if (string.IsNullOrEmpty(code))
        {
            var codes = await db.Products.Select(p => p.Code).ToListAsync();
            return CodeGenerator.Next("PRD", codes);
        }

        var taken = await db.Products.AnyAsync(p => p.Code == code && p.Id != currentId);
        if (taken) throw ApiException.Conflict($"Ya existe un producto con el código {code}.");

        return code;
    }

    private static void Apply(Product p, ProductRequest r)
    {
        p.Name = r.Name.Trim();
        p.Description = r.Description?.Trim();
        p.Barcode = string.IsNullOrWhiteSpace(r.Barcode) ? null : r.Barcode.Trim();
        p.Type = r.Type;
        p.CategoryId = r.CategoryId;
        p.UnitId = r.UnitId;
        p.Cost = r.Cost;
        p.Price = r.Price;
        p.TaxRate = r.TaxRate;
        p.MinStock = r.MinStock;
        // Stock no se toca acá: lo administra InventoryService mediante movimientos.
    }

    internal static ProductDto Map(Product p) => new(p.Id, p.Code, p.Name, p.Description, p.Barcode,
        p.Type, p.CategoryId, p.Category?.Name, p.UnitId, p.Unit?.Name ?? string.Empty,
        p.Unit?.Abbreviation ?? string.Empty, p.Cost, p.Price, p.TaxRate, p.Stock, p.MinStock, p.IsActive);
}

public class ProductQuery : QueryParams
{
    public ProductType? Type { get; set; }
    public int? CategoryId { get; set; }
    public bool? LowStock { get; set; }
}
