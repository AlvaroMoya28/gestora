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
/// Resumen de la empresa en una sola llamada: lo que se vendió este mes, lo que está
/// por cobrar y por pagar, el estado del inventario y el trabajo abierto en taller.
/// Las alertas están ordenadas por urgencia, de modo que lo primero que se lee al
/// entrar sea lo que exige una decisión hoy.
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
        var today = DateTime.UtcNow.Date;
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

        // --- Dinero ---------------------------------------------------------------
        var monthSales = await db.Sales
            .Where(s => s.Status == SaleStatus.Confirmed && s.Date >= monthStart)
            .SumAsync(s => (decimal?)s.Total) ?? 0;

        var receivable = await db.AccountsReceivable
            .Where(a => a.Balance > 0 && a.Status != ReceivableStatus.Cancelled)
            .SumAsync(a => (decimal?)a.Balance) ?? 0;

        var overdueReceivable = await db.AccountsReceivable
            .Where(a => a.Balance > 0 && a.Status != ReceivableStatus.Cancelled && a.DueDate < today)
            .SumAsync(a => (decimal?)a.Balance) ?? 0;

        var payable = await db.AccountsPayable
            .Where(a => a.Balance > 0 && a.Status != PayableStatus.Cancelled)
            .SumAsync(a => (decimal?)a.Balance) ?? 0;

        var overduePayable = await db.AccountsPayable
            .CountAsync(a => a.Balance > 0 && a.Status != PayableStatus.Cancelled && a.DueDate < today);

        var monthIncome = await db.FinanceEntries
            .Where(e => e.Kind == FinanceKind.Income && e.Date >= monthStart)
            .SumAsync(e => (decimal?)e.Amount) ?? 0;

        var monthExpense = await db.FinanceEntries
            .Where(e => e.Kind == FinanceKind.Expense && e.Date >= monthStart)
            .SumAsync(e => (decimal?)e.Amount) ?? 0;

        // --- Trabajo abierto ------------------------------------------------------
        var openRepairs = await db.RepairOrders
            .CountAsync(r => r.Status != RepairStatus.Delivered && r.Status != RepairStatus.Cancelled);

        var lateRepairs = await db.RepairOrders
            .CountAsync(r => r.PromisedAt != null && r.PromisedAt < today
                && r.Status != RepairStatus.Delivered && r.Status != RepairStatus.Cancelled);

        var openProduction = await db.ProductionOrders
            .CountAsync(o => o.Status == ProductionStatus.Planned || o.Status == ProductionStatus.InProgress);

        var metrics = new List<MetricDto>
        {
            new("monthSales", "Ventas del mes", monthSales, "money", "Facturas confirmadas"),
            new("receivable", "Por cobrar", receivable, "money",
                overdueReceivable > 0 ? $"{overdueReceivable:N2} vencido" : null),
            new("payable", "Por pagar", payable, "money",
                overduePayable > 0 ? $"{overduePayable} documento(s) vencido(s)" : null),
            // No es la utilidad del mes: es dinero que entró menos dinero que salió. Un mes
            // puede cerrar en rojo por haber comprado materia prima que todavía no se vende,
            // sin que la empresa esté perdiendo. Llamarlo «resultado» hacía leer una pérdida
            // donde solo hay un desfase entre lo que se paga y lo que se cobra.
            new("monthResult", "Flujo de caja del mes", monthIncome - monthExpense, "money",
                $"Entró {monthIncome:N2} · salió {monthExpense:N2}"),
            new("inventoryValue", "Valor del inventario",
                products.Sum(p => p.Stock * p.Cost), "money",
                "Existencia actual valorada al costo"),
            new("openRepairs", "Reparaciones abiertas", openRepairs, "integer",
                lateRepairs > 0 ? $"{lateRepairs} atrasada(s)" : null),
            new("openProduction", "Producción en curso", openProduction, "integer", null),
            new("lowStock", "Productos bajo mínimo", lowStock.Count, "integer",
                outOfStock > 0 ? $"{outOfStock} agotado(s)" : null)
        };

        // Las alertas se agregan de la más urgente a la menos: dinero vencido primero,
        // compromisos incumplidos después, y al final lo que solo es preparación.
        var alerts = new List<AlertDto>();

        if (overdueReceivable > 0)
            alerts.Add(new AlertDto("danger", "Cobros vencidos",
                $"{overdueReceivable:N2} en cuentas que ya vencieron.", "receivables"));

        if (overduePayable > 0)
            alerts.Add(new AlertDto("warning", "Pagos vencidos",
                $"{overduePayable} cuenta(s) por pagar pasaron su fecha de vencimiento.", "payables"));

        if (lateRepairs > 0)
            alerts.Add(new AlertDto("warning", "Reparaciones atrasadas",
                $"{lateRepairs} reparación(es) pasaron la fecha prometida al cliente.", "repairs"));

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
