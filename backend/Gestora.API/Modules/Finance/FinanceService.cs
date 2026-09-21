using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Finance;

/// <summary>
/// Libro de caja de la empresa: ingresos y gastos.
///
/// Dos orígenes conviven acá. Los <b>manuales</b> son los que alguien registra a mano
/// (un alquiler, una venta de chatarra). Los <b>automáticos</b> son el reflejo de un
/// cobro o un pago ya registrado en cuentas por cobrar o por pagar: los crea el sistema
/// y no se editan desde finanzas, porque su verdad vive en la cuenta que los originó.
/// Gracias a eso, ingresos y gastos muestran el flujo completo de dinero sin que nadie
/// tenga que digitar dos veces lo mismo ni arriesgarse a dos saldos distintos.
/// </summary>
public class FinanceService(GestoraDbContext db, IAuditService audit, ICurrentUser current)
{
    /// <summary>Categorías que respaldan los asientos automáticos. Se crean solas al necesitarse.</summary>
    private const string ReceiptCategory = "Cobros a clientes";
    private const string PaymentCategory = "Pagos a proveedores";

    public static string NameOf(FinanceKind kind) => kind == FinanceKind.Income ? "Ingreso" : "Gasto";

    public static string NameOf(FinanceSource source) => source switch
    {
        FinanceSource.Manual => "Registro manual",
        FinanceSource.Receipt => "Cobro a cliente",
        FinanceSource.Payment => "Pago a proveedor",
        _ => source.ToString()
    };

    // ---------------------------------------------------------------- Categorías ----

    public async Task<IReadOnlyList<FinanceCategoryDto>> ListCategoriesAsync(FinanceKind? kind, bool? active)
    {
        var query = db.FinanceCategories.AsNoTracking().AsQueryable();
        if (kind is { } value) query = query.Where(c => c.Kind == value);
        if (active is { } isActive) query = query.Where(c => c.IsActive == isActive);

        // Se materializa antes de armar el DTO: NameOf es código C# y no se traduce a SQL.
        var rows = await query.OrderBy(c => c.Kind).ThenBy(c => c.Name)
            .Select(c => new { Category = c, Entries = db.FinanceEntries.Count(e => e.CategoryId == c.Id) })
            .ToListAsync();

        return rows.Select(r => new FinanceCategoryDto(r.Category.Id, r.Category.Name, r.Category.Kind,
            NameOf(r.Category.Kind), r.Category.Description, r.Category.IsSystem, r.Category.IsActive,
            r.Entries)).ToList();
    }

    public async Task<FinanceCategoryDto> CreateCategoryAsync(FinanceCategoryRequest request)
    {
        var name = request.Name.Trim();
        if (await db.FinanceCategories.AnyAsync(c => c.Kind == request.Kind && c.Name == name))
            throw ApiException.Conflict($"Ya existe la categoría «{name}».");

        var category = new FinanceCategory
        {
            Name = name,
            Kind = request.Kind,
            Description = request.Description?.Trim()
        };
        db.FinanceCategories.Add(category);

        audit.Track("Creación", ModuleOf(request.Kind), nameof(FinanceCategory), null,
            $"Categoría de {NameOf(request.Kind).ToLowerInvariant()}: {name}");
        await db.SaveChangesAsync();

        return new FinanceCategoryDto(category.Id, category.Name, category.Kind, NameOf(category.Kind),
            category.Description, false, true, 0);
    }

    public async Task<FinanceCategoryDto> UpdateCategoryAsync(int id, FinanceCategoryRequest request)
    {
        var category = await db.FinanceCategories.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La categoría");

        if (category.IsSystem)
            throw new ApiException("Las categorías del sistema no se pueden modificar.");

        var name = request.Name.Trim();
        if (await db.FinanceCategories.AnyAsync(c => c.Kind == category.Kind && c.Name == name && c.Id != id))
            throw ApiException.Conflict($"Ya existe la categoría «{name}».");

        audit.Track("Actualización", ModuleOf(category.Kind), nameof(FinanceCategory), id,
            "Categoría", category.Name, name);

        category.Name = name;
        category.Description = request.Description?.Trim();
        await db.SaveChangesAsync();

        return new FinanceCategoryDto(category.Id, category.Name, category.Kind, NameOf(category.Kind),
            category.Description, category.IsSystem, category.IsActive,
            await db.FinanceEntries.CountAsync(e => e.CategoryId == id));
    }

    public async Task<FinanceCategoryDto> SetCategoryActiveAsync(int id, bool active)
    {
        var category = await db.FinanceCategories.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La categoría");

        if (category.IsSystem)
            throw new ApiException("Las categorías del sistema no se pueden inactivar: respaldan los cobros y pagos automáticos.");

        category.IsActive = active;
        audit.Track(active ? "Reactivación" : "Inactivación", ModuleOf(category.Kind),
            nameof(FinanceCategory), id, $"Categoría {category.Name}");
        await db.SaveChangesAsync();

        return new FinanceCategoryDto(category.Id, category.Name, category.Kind, NameOf(category.Kind),
            category.Description, category.IsSystem, category.IsActive,
            await db.FinanceEntries.CountAsync(e => e.CategoryId == id));
    }

    // ------------------------------------------------------------------ Asientos ----

    public async Task<PagedResult<FinanceEntryDto>> ListAsync(FinanceKind kind, FinanceQuery q)
    {
        var query = BaseQuery(kind, q);

        var total = await query.CountAsync();
        var rows = await query
            .OrderByDescending(e => e.Date).ThenByDescending(e => e.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<FinanceEntryDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    /// <summary>Totales del mismo filtro que el listado, para la cabecera de la pantalla.</summary>
    public async Task<FinanceSummaryDto> SummaryAsync(FinanceKind kind, FinanceQuery q)
    {
        var query = BaseQuery(kind, q);
        var monthStart = new DateTime(DateTime.UtcNow.Year, DateTime.UtcNow.Month, 1, 0, 0, 0, DateTimeKind.Utc);

        // Se agrupa en la base y se ordena en memoria: ordenar por una propiedad del DTO
        // obliga a EF a traducir el constructor del record, y eso MySQL no lo sabe hacer.
        // Son unas pocas categorías, así que ordenarlas acá no cuesta nada.
        var totals = await query
            .GroupBy(e => new { e.CategoryId, e.Category.Name })
            .Select(g => new { g.Key.CategoryId, g.Key.Name, Total = g.Sum(e => e.Amount), Count = g.Count() })
            .ToListAsync();

        var byCategory = totals
            .OrderByDescending(c => c.Total)
            .Select(c => new FinanceCategoryTotalDto(c.CategoryId, c.Name, c.Total, c.Count))
            .ToList();

        return new FinanceSummaryDto(
            await query.SumAsync(e => (decimal?)e.Amount) ?? 0,
            await query.Where(e => e.Date >= monthStart).SumAsync(e => (decimal?)e.Amount) ?? 0,
            await query.CountAsync(),
            byCategory);
    }

    public async Task<FinanceEntryDto> GetAsync(int id) => Map(await LoadAsync(id));

    public async Task<FinanceEntryDto> CreateAsync(FinanceKind kind, FinanceEntryRequest request)
    {
        var category = await ResolveCategoryAsync(kind, request.CategoryId);

        var entry = new FinanceEntry
        {
            Kind = kind,
            CategoryId = category.Id,
            Date = request.Date ?? DateTime.UtcNow,
            Amount = request.Amount,
            Description = request.Description.Trim(),
            Method = request.Method.Trim(),
            Reference = request.Reference?.Trim(),
            Notes = request.Notes?.Trim(),
            Source = FinanceSource.Manual,
            UserId = current.IsAuthenticated ? current.UserId : null
        };
        db.FinanceEntries.Add(entry);

        audit.Track("Creación", ModuleOf(kind), nameof(FinanceEntry), null,
            $"{NameOf(kind)} de {request.Amount:N2} · {category.Name} · {entry.Description}");
        await db.SaveChangesAsync();

        return await GetAsync(entry.Id);
    }

    public async Task<FinanceEntryDto> UpdateAsync(int id, FinanceEntryRequest request)
    {
        var entry = await LoadAsync(id);
        EnsureManual(entry);

        var category = await ResolveCategoryAsync(entry.Kind, request.CategoryId);

        audit.Track("Actualización", ModuleOf(entry.Kind), nameof(FinanceEntry), id,
            $"{NameOf(entry.Kind)} {entry.Description}",
            $"{entry.Amount:N2} · {entry.Category.Name}",
            $"{request.Amount:N2} · {category.Name}");

        entry.CategoryId = category.Id;
        entry.Date = request.Date ?? entry.Date;
        entry.Amount = request.Amount;
        entry.Description = request.Description.Trim();
        entry.Method = request.Method.Trim();
        entry.Reference = request.Reference?.Trim();
        entry.Notes = request.Notes?.Trim();

        await db.SaveChangesAsync();
        return await GetAsync(id);
    }

    /// <summary>
    /// Elimina un asiento manual. Es la única entidad de dinero que sí se borra: un
    /// ingreso o gasto mal digitado no tiene documento ni saldo asociado que preservar,
    /// y la eliminación queda registrada en la bitácora con su importe.
    /// </summary>
    public async Task DeleteAsync(int id)
    {
        var entry = await LoadAsync(id);
        EnsureManual(entry);

        audit.Track("Eliminación", ModuleOf(entry.Kind), nameof(FinanceEntry), id,
            $"{NameOf(entry.Kind)} de {entry.Amount:N2} · {entry.Category.Name} · {entry.Description}");

        db.FinanceEntries.Remove(entry);
        await db.SaveChangesAsync();
    }

    // -------------------------------------------------------- Asientos del sistema ----

    /// <summary>
    /// Registra en caja un cobro o un pago recién aplicado. Lo llaman los servicios de
    /// cuentas por cobrar y por pagar dentro de su propia unidad de trabajo: acá no se
    /// guarda nada, para que el asiento se confirme con la misma transacción que el
    /// cobro que lo origina y nunca quede uno sin el otro.
    /// </summary>
    public async Task RecordAutomaticAsync(FinanceKind kind, FinanceSource source, int sourceId,
        decimal amount, string description, string method, string? reference, DateTime date)
    {
        var category = await ResolveSystemCategoryAsync(kind);

        db.FinanceEntries.Add(new FinanceEntry
        {
            Kind = kind,
            CategoryId = category.Id,
            Date = date,
            Amount = amount,
            Description = description,
            Method = method,
            Reference = reference,
            Source = source,
            SourceId = sourceId,
            UserId = current.IsAuthenticated ? current.UserId : null
        });
    }

    /// <summary>
    /// Categoría del sistema para los asientos automáticos. Se crea la primera vez que
    /// hace falta, de modo que las empresas dadas de alta antes de este módulo también
    /// la tengan sin necesidad de migrar datos.
    /// </summary>
    private async Task<FinanceCategory> ResolveSystemCategoryAsync(FinanceKind kind)
    {
        var name = kind == FinanceKind.Income ? ReceiptCategory : PaymentCategory;

        var existing = await db.FinanceCategories
            .FirstOrDefaultAsync(c => c.Kind == kind && c.Name == name);
        if (existing is not null) return existing;

        var category = new FinanceCategory
        {
            Name = name,
            Kind = kind,
            IsSystem = true,
            Description = kind == FinanceKind.Income
                ? "Generada por los cobros registrados en cuentas por cobrar."
                : "Generada por los pagos registrados en cuentas por pagar."
        };
        db.FinanceCategories.Add(category);
        // Se guarda de una vez: el asiento que viene a continuación necesita su Id.
        await db.SaveChangesAsync();

        return category;
    }

    // ------------------------------------------------------------------- Apoyo ----

    private IQueryable<FinanceEntry> BaseQuery(FinanceKind kind, FinanceQuery q)
    {
        var query = db.FinanceEntries.AsNoTracking()
            .Include(e => e.Category).Include(e => e.User)
            .Where(e => e.Kind == kind);

        if (q.CategoryId is { } categoryId) query = query.Where(e => e.CategoryId == categoryId);
        if (q.From is { } from) query = query.Where(e => e.Date >= from);
        if (q.To is { } to) query = query.Where(e => e.Date <= to);
        if (q.Manual is { } manual)
            query = manual
                ? query.Where(e => e.Source == FinanceSource.Manual)
                : query.Where(e => e.Source != FinanceSource.Manual);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(e => e.Description.Contains(term) || e.Category.Name.Contains(term)
                || (e.Reference != null && e.Reference.Contains(term)));
        }

        return query;
    }

    private async Task<FinanceCategory> ResolveCategoryAsync(FinanceKind kind, int categoryId)
    {
        var category = await db.FinanceCategories.FirstOrDefaultAsync(c => c.Id == categoryId)
            ?? throw new ApiException("La categoría seleccionada no es válida.");

        if (category.Kind != kind)
            throw new ApiException($"La categoría «{category.Name}» no corresponde a {NameOf(kind).ToLowerInvariant()}s.");

        if (!category.IsActive)
            throw new ApiException($"La categoría «{category.Name}» está inactiva.");

        return category;
    }

    private static void EnsureManual(FinanceEntry entry)
    {
        if (entry.Source != FinanceSource.Manual)
            throw new ApiException(
                "Este movimiento lo generó un cobro o un pago. Modifíquelo desde la cuenta que lo originó.");
    }

    private static string ModuleOf(FinanceKind kind) => kind == FinanceKind.Income ? "income" : "expenses";

    private async Task<FinanceEntry> LoadAsync(int id) =>
        await db.FinanceEntries.Include(e => e.Category).Include(e => e.User)
            .FirstOrDefaultAsync(e => e.Id == id)
        ?? throw ApiException.NotFound("El movimiento");

    private static FinanceEntryDto Map(FinanceEntry e) => new(
        e.Id, e.Kind, NameOf(e.Kind), e.CategoryId, e.Category.Name, e.Date, e.Amount,
        e.Description, e.Method, e.Reference, e.Notes, e.Source, NameOf(e.Source), e.SourceId,
        e.Source == FinanceSource.Manual, e.User?.FullName, e.CreatedAt);
}
