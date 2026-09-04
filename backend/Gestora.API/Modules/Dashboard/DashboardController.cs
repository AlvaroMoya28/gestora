using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Inventory;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Dashboard;

public record MetricDto(string Key, string Label, decimal Value, string Format, string? Hint);
public record AlertDto(string Level, string Title, string Detail, string? ModuleKey);
public record ActivityDto(string Action, string Module, string Description, string UserName, DateTime OccurredAt);

public record DashboardDto(
    IReadOnlyList<MetricDto> Metrics,
    IReadOnlyList<AlertDto> Alerts,
    IReadOnlyList<ActivityDto> RecentActivity,
    IReadOnlyList<LowStockDto> LowStock);

public record LowStockDto(int ProductId, string Code, string Name, decimal Stock, decimal MinStock, string Unit);

/// <summary>
/// Resumen de la empresa. Hoy consolida catálogo e inventario; conforme entren
/// compras, ventas y finanzas se agregan sus métricas a la misma respuesta, para que
/// el frontend siga haciendo una sola llamada al entrar.
/// </summary>
[ApiController]
[Route("api/dashboard")]
[Authorize]
[RequireModule("dashboard")]
public class DashboardController(GestoraDbContext db) : ControllerBase
{
    [HttpGet]
    public async Task<ActionResult<DashboardDto>> Get()
    {
        var monthStart = new DateTime(DateTime.UtcNow.Year, DateTime.UtcNow.Month, 1, 0, 0, 0, DateTimeKind.Utc);

        var products = await db.Products.AsNoTracking().Include(p => p.Unit)
            .Where(p => p.IsActive).ToListAsync();

        var lowStock = products
            .Where(p => p.Type != ProductType.Service && p.Stock <= p.MinStock)
            .OrderBy(p => p.Stock - p.MinStock)
            .Take(10)
            .Select(p => new LowStockDto(p.Id, p.Code, p.Name, p.Stock, p.MinStock, p.Unit.Abbreviation))
            .ToList();

        var outOfStock = products.Count(p => p.Type != ProductType.Service && p.Stock <= 0);

        var metrics = new List<MetricDto>
        {
            new("inventoryValue", "Valor del inventario",
                products.Sum(p => p.Stock * p.Cost), "money",
                "Existencia actual valorada al costo"),
            new("products", "Productos activos", products.Count, "integer", null),
            new("customers", "Clientes activos",
                await db.Customers.CountAsync(c => c.IsActive), "integer", null),
            new("suppliers", "Proveedores activos",
                await db.Suppliers.CountAsync(s => s.IsActive), "integer", null),
            new("lowStock", "Productos bajo mínimo", lowStock.Count, "integer",
                outOfStock > 0 ? $"{outOfStock} agotado(s)" : null),
            new("movements", "Movimientos del mes",
                await db.InventoryMovements.CountAsync(m => m.OccurredAt >= monthStart), "integer", null)
        };

        var alerts = new List<AlertDto>();
        if (outOfStock > 0)
            alerts.Add(new AlertDto("danger", "Productos agotados",
                $"{outOfStock} producto(s) sin existencia disponible.", "inventory"));

        var belowMin = lowStock.Count - outOfStock;
        if (belowMin > 0)
            alerts.Add(new AlertDto("warning", "Existencias bajo el mínimo",
                $"{belowMin} producto(s) por debajo de su nivel mínimo.", "inventory"));

        if (products.Count == 0)
            alerts.Add(new AlertDto("info", "Catálogo vacío",
                "Registre sus productos y materiales para empezar a controlar el inventario.", "products"));

        var activity = await db.AuditLogs.AsNoTracking()
            .OrderByDescending(a => a.OccurredAt)
            .Take(8)
            .Select(a => new ActivityDto(a.Action, a.Module, a.Description ?? string.Empty,
                a.UserName, a.OccurredAt))
            .ToListAsync();

        return Ok(new DashboardDto(metrics, alerts, activity, lowStock));
    }
}

/// <summary>Consulta de la bitácora. Solo lectura: los registros de auditoría no se editan ni borran.</summary>
[ApiController]
[Route("api/auditoria")]
[Authorize]
public class AuditController(GestoraDbContext db) : ControllerBase
{
    [HttpGet]
    [RequireModule("audit")]
    public async Task<ActionResult<PagedResult<AuditEntryDto>>> List([FromQuery] AuditQuery q)
    {
        var query = db.AuditLogs.AsNoTracking().AsQueryable();

        if (!string.IsNullOrWhiteSpace(q.Module)) query = query.Where(a => a.Module == q.Module);
        if (q.From is { } from) query = query.Where(a => a.OccurredAt >= from);
        if (q.To is { } to) query = query.Where(a => a.OccurredAt <= to);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(a => a.UserName.Contains(term) || a.Action.Contains(term)
                || (a.Description != null && a.Description.Contains(term)));
        }

        var total = await query.CountAsync();
        var items = await query
            .OrderByDescending(a => a.OccurredAt).ThenByDescending(a => a.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .Select(a => new AuditEntryDto(a.Id, a.UserName, a.Action, a.Module, a.EntityName,
                a.EntityId, a.Description, a.OldValue, a.NewValue, a.IpAddress, a.OccurredAt))
            .ToListAsync();

        return Ok(new PagedResult<AuditEntryDto>(items, total, q.Page, q.PageSize));
    }
}

public record AuditEntryDto(int Id, string UserName, string Action, string Module, string? EntityName,
    int? EntityId, string? Description, string? OldValue, string? NewValue, string? IpAddress, DateTime OccurredAt);

public class AuditQuery : QueryParams
{
    public string? Module { get; set; }
    public DateTime? From { get; set; }
    public DateTime? To { get; set; }
}
