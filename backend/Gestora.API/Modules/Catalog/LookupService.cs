using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Catalog;

/// <summary>
/// Categorías y unidades. Son listas cortas y se devuelven completas (sin paginar):
/// el frontend las usa para alimentar selectores.
/// </summary>
public class LookupService(GestoraDbContext db, IAuditService audit)
{
    // ------------------------------------------------------------- Categorías ----

    public async Task<IReadOnlyList<CategoryDto>> ListCategoriesAsync(bool? active)
    {
        var query = db.Categories.AsNoTracking().AsQueryable();
        if (active is { } value) query = query.Where(c => c.IsActive == value);

        return await query.OrderBy(c => c.Name)
            .Select(c => new CategoryDto(c.Id, c.Name, c.Description, c.IsActive,
                db.Products.Count(p => p.CategoryId == c.Id)))
            .ToListAsync();
    }

    public async Task<CategoryDto> CreateCategoryAsync(CategoryRequest request)
    {
        var name = request.Name.Trim();
        if (await db.Categories.AnyAsync(c => c.Name == name))
            throw ApiException.Conflict($"Ya existe la categoría «{name}».");

        var category = new Category { Name = name, Description = request.Description?.Trim() };
        db.Categories.Add(category);
        audit.Track("Creación", "products", nameof(Category), null, $"Categoría {name}");
        await db.SaveChangesAsync();

        return new CategoryDto(category.Id, category.Name, category.Description, category.IsActive, 0);
    }

    public async Task<CategoryDto> UpdateCategoryAsync(int id, CategoryRequest request)
    {
        var category = await db.Categories.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La categoría");

        var name = request.Name.Trim();
        if (await db.Categories.AnyAsync(c => c.Name == name && c.Id != id))
            throw ApiException.Conflict($"Ya existe la categoría «{name}».");

        audit.Track("Actualización", "products", nameof(Category), id, "Categoría",
            category.Name, name);

        category.Name = name;
        category.Description = request.Description?.Trim();
        await db.SaveChangesAsync();

        return new CategoryDto(category.Id, category.Name, category.Description, category.IsActive,
            await db.Products.CountAsync(p => p.CategoryId == id));
    }

    public async Task<CategoryDto> SetCategoryActiveAsync(int id, bool active)
    {
        var category = await db.Categories.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La categoría");

        category.IsActive = active;
        audit.Track(active ? "Reactivación" : "Inactivación", "products", nameof(Category), id,
            $"Categoría {category.Name}");
        await db.SaveChangesAsync();

        return new CategoryDto(category.Id, category.Name, category.Description, category.IsActive,
            await db.Products.CountAsync(p => p.CategoryId == id));
    }

    // ---------------------------------------------------------------- Unidades ----

    public async Task<IReadOnlyList<UnitDto>> ListUnitsAsync(bool? active)
    {
        var query = db.Units.AsNoTracking().AsQueryable();
        if (active is { } value) query = query.Where(u => u.IsActive == value);

        return await query.OrderBy(u => u.Name)
            .Select(u => new UnitDto(u.Id, u.Name, u.Abbreviation, u.DecimalPlaces, u.IsActive,
                db.Products.Count(p => p.UnitId == u.Id)))
            .ToListAsync();
    }

    public async Task<UnitDto> CreateUnitAsync(UnitRequest request)
    {
        var abbreviation = request.Abbreviation.Trim();
        if (await db.Units.AnyAsync(u => u.Abbreviation == abbreviation))
            throw ApiException.Conflict($"Ya existe una unidad con la abreviatura «{abbreviation}».");

        var unit = new Unit
        {
            Name = request.Name.Trim(),
            Abbreviation = abbreviation,
            DecimalPlaces = request.DecimalPlaces
        };
        db.Units.Add(unit);
        audit.Track("Creación", "products", nameof(Unit), null, $"Unidad {unit.Name}");
        await db.SaveChangesAsync();

        return new UnitDto(unit.Id, unit.Name, unit.Abbreviation, unit.DecimalPlaces, unit.IsActive, 0);
    }

    public async Task<UnitDto> UpdateUnitAsync(int id, UnitRequest request)
    {
        var unit = await db.Units.FirstOrDefaultAsync(u => u.Id == id)
            ?? throw ApiException.NotFound("La unidad");

        var abbreviation = request.Abbreviation.Trim();
        if (await db.Units.AnyAsync(u => u.Abbreviation == abbreviation && u.Id != id))
            throw ApiException.Conflict($"Ya existe una unidad con la abreviatura «{abbreviation}».");

        audit.Track("Actualización", "products", nameof(Unit), id, "Unidad de medida",
            $"{unit.Name} ({unit.Abbreviation})", $"{request.Name} ({abbreviation})");

        unit.Name = request.Name.Trim();
        unit.Abbreviation = abbreviation;
        unit.DecimalPlaces = request.DecimalPlaces;
        await db.SaveChangesAsync();

        return new UnitDto(unit.Id, unit.Name, unit.Abbreviation, unit.DecimalPlaces, unit.IsActive,
            await db.Products.CountAsync(p => p.UnitId == id));
    }

    public async Task<UnitDto> SetUnitActiveAsync(int id, bool active)
    {
        var unit = await db.Units.FirstOrDefaultAsync(u => u.Id == id)
            ?? throw ApiException.NotFound("La unidad");

        if (!active && await db.Products.AnyAsync(p => p.UnitId == id && p.IsActive))
            throw ApiException.Conflict("No se puede inactivar: hay productos activos que usan esta unidad.");

        unit.IsActive = active;
        audit.Track(active ? "Reactivación" : "Inactivación", "products", nameof(Unit), id,
            $"Unidad {unit.Name}");
        await db.SaveChangesAsync();

        return new UnitDto(unit.Id, unit.Name, unit.Abbreviation, unit.DecimalPlaces, unit.IsActive,
            await db.Products.CountAsync(p => p.UnitId == id));
    }
}
