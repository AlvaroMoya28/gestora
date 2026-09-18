using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Platform;

/// <summary>
/// Suscripciones, planes y cobros. Es el lado comercial de Gestora: quién está al día,
/// a quién hay que cobrarle y cuándo vence cada cuenta.
/// </summary>
public class SubscriptionService(GestoraDbContext db, IAuditService audit, ICurrentUser current)
{
    // ------------------------------------------------------- Suscripciones ----

    public async Task<PagedResult<SubscriptionDto>> ListAsync(SubscriptionQuery q)
    {
        var query = BaseQuery();

        if (q.Status is { } status) query = query.Where(s => s.Status == status);

        if (q.ExpiringInDays is { } days)
        {
            var limit = DateTime.UtcNow.Date.AddDays(days);
            query = query.Where(s => s.Status != SubscriptionStatus.Cancelled && s.EndDate <= limit);
        }

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(s => s.Company.Name.Contains(term)
                || s.Company.AccountEmail.Contains(term) || s.Plan.Name.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(s => s.EndDate)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<SubscriptionDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<SubscriptionDto> GetAsync(int id)
    {
        var subscription = await BaseQuery().FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("La suscripción");
        return Map(subscription);
    }

    /// <summary>
    /// Registra un cobro y corre el vencimiento tantos períodos como se hayan pagado.
    /// La renovación arranca desde el vencimiento actual, no desde hoy: si la empresa
    /// paga con atraso no pierde los días, y si paga adelantado se acumulan.
    /// </summary>
    public async Task<SubscriptionDto> RegisterPaymentAsync(int id, RegisterSubscriptionPaymentRequest request)
    {
        var subscription = await db.Subscriptions
            .Include(s => s.Company).Include(s => s.Plan).Include(s => s.Payments)
            .FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("La suscripción");

        if (subscription.Status == SubscriptionStatus.Cancelled)
            throw new ApiException("La suscripción está dada de baja y no admite cobros.");

        var months = subscription.Plan.BillingPeriodMonths * request.Periods;

        // Si venció hace mucho, se renueva desde hoy: no tiene sentido cobrar meses
        // durante los que la empresa no usó el sistema.
        var periodFrom = subscription.EndDate.Date < DateTime.UtcNow.Date
            ? DateTime.UtcNow.Date
            : subscription.EndDate.Date;
        var periodTo = periodFrom.AddMonths(months);

        db.SubscriptionPayments.Add(new SubscriptionPayment
        {
            SubscriptionId = subscription.Id,
            Date = request.Date ?? DateTime.UtcNow,
            Amount = request.Amount,
            Method = request.Method.Trim(),
            Reference = request.Reference?.Trim(),
            PeriodFrom = periodFrom,
            PeriodTo = periodTo,
            Notes = request.Notes?.Trim(),
            UserId = current.IsAuthenticated ? current.UserId : null
        });

        subscription.EndDate = periodTo;
        subscription.Status = SubscriptionStatus.Active;

        // Si estaba suspendida por falta de pago, el cobro la reactiva.
        if (subscription.Company.Status == CompanyStatus.Suspended)
            subscription.Company.Status = CompanyStatus.Active;

        audit.Track("Cobro registrado", "platform_billing", nameof(Subscription), subscription.Id,
            $"{subscription.Company.Name} pagó {request.Amount:N2} · vence {periodTo:dd/MM/yyyy}",
            companyId: 0);

        await db.SaveChangesAsync();
        return await GetAsync(id);
    }

    public async Task<SubscriptionDto> ChangePlanAsync(int id, ChangePlanRequest request)
    {
        var subscription = await db.Subscriptions.Include(s => s.Company).Include(s => s.Plan)
            .FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("La suscripción");

        var plan = await db.Plans.FirstOrDefaultAsync(p => p.Id == request.PlanId && p.IsActive)
            ?? throw new ApiException("El plan seleccionado no es válido.");

        var before = $"{subscription.Plan.Name} · {subscription.Price:N2}";
        subscription.PlanId = plan.Id;
        subscription.Price = request.Price ?? plan.Price;

        audit.Track("Cambio de plan", "platform_billing", nameof(Subscription), id,
            $"{subscription.Company.Name}" + (string.IsNullOrWhiteSpace(request.Reason) ? "" : $" · {request.Reason}"),
            before, $"{plan.Name} · {subscription.Price:N2}", companyId: 0);

        await db.SaveChangesAsync();
        return await GetAsync(id);
    }

    public async Task<SubscriptionDto> CancelAsync(int id, string? reason)
    {
        var subscription = await db.Subscriptions.Include(s => s.Company)
            .FirstOrDefaultAsync(s => s.Id == id)
            ?? throw ApiException.NotFound("La suscripción");

        subscription.Status = SubscriptionStatus.Cancelled;

        audit.Track("Baja de suscripción", "platform_billing", nameof(Subscription), id,
            $"{subscription.Company.Name} dada de baja" +
            (string.IsNullOrWhiteSpace(reason) ? "" : $" · {reason}"), companyId: 0);

        await db.SaveChangesAsync();
        return await GetAsync(id);
    }

    // --------------------------------------------------------------- Planes ----

    public async Task<IReadOnlyList<PlanDto>> ListPlansAsync(bool? active)
    {
        var query = db.Plans.AsNoTracking().AsQueryable();
        if (active is { } value) query = query.Where(p => p.IsActive == value);

        var plans = await query.OrderBy(p => p.SortOrder).ThenBy(p => p.Name).ToListAsync();

        var counts = await db.Subscriptions
            .Where(s => s.Status != SubscriptionStatus.Cancelled)
            .GroupBy(s => s.PlanId)
            .Select(g => new { PlanId = g.Key, Count = g.Count() })
            .ToDictionaryAsync(x => x.PlanId, x => x.Count);

        return plans.Select(p => MapPlan(p, counts.GetValueOrDefault(p.Id))).ToList();
    }

    public async Task<PlanDto> CreatePlanAsync(PlanRequest request)
    {
        var name = request.Name.Trim();
        if (await db.Plans.AnyAsync(p => p.Name == name))
            throw ApiException.Conflict($"Ya existe el plan «{name}».");

        var plan = new Plan
        {
            Name = name,
            Description = request.Description?.Trim(),
            Price = request.Price,
            Currency = request.Currency.Trim().ToUpperInvariant(),
            BillingPeriodMonths = request.BillingPeriodMonths,
            MaxUsers = request.MaxUsers,
            SortOrder = request.SortOrder
        };
        db.Plans.Add(plan);

        audit.Track("Creación", "platform_plans", nameof(Plan), null, $"Plan {name}", companyId: 0);
        await db.SaveChangesAsync();

        return MapPlan(plan, 0);
    }

    public async Task<PlanDto> UpdatePlanAsync(int id, PlanRequest request)
    {
        var plan = await db.Plans.FirstOrDefaultAsync(p => p.Id == id)
            ?? throw ApiException.NotFound("El plan");

        var name = request.Name.Trim();
        if (await db.Plans.AnyAsync(p => p.Name == name && p.Id != id))
            throw ApiException.Conflict($"Ya existe el plan «{name}».");

        var before = $"{plan.Name} · {plan.Price:N2}";

        plan.Name = name;
        plan.Description = request.Description?.Trim();
        plan.Price = request.Price;
        plan.Currency = request.Currency.Trim().ToUpperInvariant();
        plan.BillingPeriodMonths = request.BillingPeriodMonths;
        plan.MaxUsers = request.MaxUsers;
        plan.SortOrder = request.SortOrder;

        // Cambiar el precio de lista no toca las suscripciones vigentes: cada una
        // conserva el precio que se pactó cuando se firmó.
        audit.Track("Actualización", "platform_plans", nameof(Plan), id, "Plan",
            before, $"{plan.Name} · {plan.Price:N2}", companyId: 0);
        await db.SaveChangesAsync();

        return MapPlan(plan, await db.Subscriptions.CountAsync(s => s.PlanId == id));
    }

    public async Task<PlanDto> SetPlanActiveAsync(int id, bool active)
    {
        var plan = await db.Plans.FirstOrDefaultAsync(p => p.Id == id)
            ?? throw ApiException.NotFound("El plan");

        plan.IsActive = active;
        audit.Track(active ? "Reactivación" : "Retiro", "platform_plans", nameof(Plan), id,
            $"Plan {plan.Name}", companyId: 0);
        await db.SaveChangesAsync();

        return MapPlan(plan, await db.Subscriptions.CountAsync(s => s.PlanId == id));
    }

    // -------------------------------------------------------------- Resumen ----

    /// <summary>
    /// Estado del negocio de Gestora: cuántas empresas hay, cuánto se factura al mes
    /// y a quién hay que cobrarle pronto.
    /// </summary>
    public async Task<PlatformOverviewDto> GetOverviewAsync()
    {
        var today = DateTime.UtcNow.Date;

        var companies = await db.Companies.AsNoTracking()
            .Include(c => c.Subscriptions).ThenInclude(s => s.Plan)
            .ToListAsync();

        var active = companies.Where(c => c.Status == CompanyStatus.Active).ToList();

        var liveSubscriptions = companies
            .SelectMany(c => c.Subscriptions)
            .Where(s => s.Status is SubscriptionStatus.Active or SubscriptionStatus.Trial)
            .ToList();

        // Ingreso mensual recurrente: cada suscripción llevada a su equivalente por mes.
        var monthlyRevenue = liveSubscriptions
            .Where(s => s.Status == SubscriptionStatus.Active)
            .Sum(s => s.Price / Math.Max(s.Plan?.BillingPeriodMonths ?? 1, 1));

        var expired = liveSubscriptions.Count(s => s.EndDate.Date < today);

        var metrics = new List<PlatformMetricDto>
        {
            new("companies", "Empresas registradas", companies.Count, "integer", null),
            new("activeCompanies", "Empresas activas", active.Count, "integer",
                companies.Count > active.Count ? $"{companies.Count - active.Count} suspendida(s) o de baja" : null),
            new("monthlyRevenue", "Ingreso mensual recurrente", monthlyRevenue, "money",
                "Suscripciones activas llevadas a su equivalente mensual"),
            new("trials", "En período de prueba",
                liveSubscriptions.Count(s => s.Status == SubscriptionStatus.Trial), "integer", null),
            new("expired", "Suscripciones vencidas", expired, "integer",
                expired > 0 ? "Requieren cobro o suspensión" : null),
            new("users", "Usuarios de empresas",
                await db.Users.IgnoreQueryFilters().CountAsync(u => u.CompanyId != null), "integer", null)
        };

        var expiringSoon = await BaseQuery()
            .Where(s => s.Status != SubscriptionStatus.Cancelled && s.EndDate <= today.AddDays(30))
            .OrderBy(s => s.EndDate)
            .Take(8)
            .ToListAsync();

        var recent = companies
            .OrderByDescending(c => c.CreatedAt)
            .Take(5)
            .Select(c => CompanyService.Map(c, 0))
            .ToList();

        return new PlatformOverviewDto(metrics, expiringSoon.Select(Map).ToList(), recent);
    }

    /// <summary>
    /// Marca como vencidas las suscripciones cuya fecha ya pasó. Se ejecuta al consultar
    /// para que el estado no dependa de una tarea programada que puede no correr.
    /// </summary>
    public async Task<int> RefreshExpiredAsync()
    {
        var today = DateTime.UtcNow.Date;
        var stale = await db.Subscriptions
            .Where(s => s.Status == SubscriptionStatus.Active && s.EndDate < today)
            .ToListAsync();

        stale.ForEach(s => s.Status = SubscriptionStatus.Expired);
        if (stale.Count > 0) await db.SaveChangesAsync();

        return stale.Count;
    }

    // ------------------------------------------------------------ Internos ----

    private IQueryable<Subscription> BaseQuery() =>
        db.Subscriptions.AsNoTracking()
            .Include(s => s.Company)
            .Include(s => s.Plan)
            .Include(s => s.Payments).ThenInclude(p => p.User);

    private static SubscriptionDto Map(Subscription s) => new(
        s.Id, s.CompanyId, s.Company?.Name ?? string.Empty, s.Company?.AccountEmail ?? string.Empty,
        s.PlanId, s.Plan?.Name ?? string.Empty, s.StartDate, s.EndDate, s.Status,
        StatusName(s.Status), s.Price, s.DaysToExpiry, s.DaysToExpiry < 0, s.Notes,
        s.Payments.OrderByDescending(p => p.Date)
            .Select(p => new SubscriptionPaymentDto(p.Id, p.Date, p.Amount, p.Method, p.Reference,
                p.PeriodFrom, p.PeriodTo, p.Notes, p.User?.FullName))
            .ToList());

    private static PlanDto MapPlan(Plan p, int companyCount) => new(p.Id, p.Name, p.Description,
        p.Price, p.Currency, p.BillingPeriodMonths, p.MaxUsers, p.IsActive, p.SortOrder, companyCount);

    internal static string StatusName(SubscriptionStatus status) => status switch
    {
        SubscriptionStatus.Trial => "Prueba",
        SubscriptionStatus.Active => "Activa",
        SubscriptionStatus.Expired => "Vencida",
        SubscriptionStatus.Cancelled => "Dada de baja",
        _ => status.ToString()
    };
}
