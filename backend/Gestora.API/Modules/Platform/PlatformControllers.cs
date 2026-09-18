using Gestora.API.Common;
using Gestora.API.Domain;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Platform;

/// <summary>
/// Administración de Gestora como producto. Todo lo que cuelga de <c>/api/plataforma</c>
/// es ajeno a cualquier empresa: los permisos son de módulos de plataforma, que ningún
/// usuario de empresa tiene.
/// </summary>
[ApiController]
[Route("api/plataforma/empresas")]
[Authorize]
public class PlatformCompaniesController(CompanyService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("platform_companies")]
    public async Task<ActionResult<PagedResult<CompanyDto>>> List([FromQuery] CompanyQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("platform_companies")]
    public async Task<ActionResult<CompanyDto>> Get(int id) => Ok(await service.GetAsync(id));

    /// <summary>Alta completa de un cliente: empresa, suscripción y cuenta de acceso.</summary>
    [HttpPost]
    [RequireModule("platform_companies", write: true)]
    public async Task<ActionResult<RegisterCompanyResult>> Register([FromBody] RegisterCompanyRequest request)
    {
        var result = await service.RegisterAsync(request);
        return CreatedAtAction(nameof(Get), new { id = result.Company.Id }, result);
    }

    [HttpPut("{id:int}")]
    [RequireModule("platform_companies", write: true)]
    public async Task<ActionResult<CompanyDto>> Update(int id, [FromBody] CompanyRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("{id:int}/suspender")]
    [RequireModule("platform_companies", write: true)]
    public async Task<ActionResult<CompanyDto>> Suspend(int id, [FromQuery] string? motivo)
        => Ok(await service.SetStatusAsync(id, CompanyStatus.Suspended, motivo));

    [HttpPost("{id:int}/reactivar")]
    [RequireModule("platform_companies", write: true)]
    public async Task<ActionResult<CompanyDto>> Reactivate(int id)
        => Ok(await service.SetStatusAsync(id, CompanyStatus.Active, null));

    [HttpPost("{id:int}/dar-de-baja")]
    [RequireModule("platform_companies", write: true)]
    public async Task<ActionResult<CompanyDto>> Cancel(int id, [FromQuery] string? motivo)
        => Ok(await service.SetStatusAsync(id, CompanyStatus.Cancelled, motivo));
}

[ApiController]
[Route("api/plataforma")]
[Authorize]
public class PlatformBillingController(SubscriptionService service) : ControllerBase
{
    /// <summary>Estado del negocio: empresas, ingreso recurrente y próximos vencimientos.</summary>
    [HttpGet("resumen")]
    [RequireModule("platform_overview")]
    public async Task<ActionResult<PlatformOverviewDto>> Overview()
    {
        // Se ponen al día los vencimientos antes de calcular, para no depender de una
        // tarea programada que puede no haber corrido.
        await service.RefreshExpiredAsync();
        return Ok(await service.GetOverviewAsync());
    }

    [HttpGet("suscripciones")]
    [RequireModule("platform_billing")]
    public async Task<ActionResult<PagedResult<SubscriptionDto>>> ListSubscriptions([FromQuery] SubscriptionQuery query)
    {
        await service.RefreshExpiredAsync();
        return Ok(await service.ListAsync(query));
    }

    [HttpGet("suscripciones/{id:int}")]
    [RequireModule("platform_billing")]
    public async Task<ActionResult<SubscriptionDto>> GetSubscription(int id) => Ok(await service.GetAsync(id));

    /// <summary>Registra un cobro y corre el vencimiento los períodos que se hayan pagado.</summary>
    [HttpPost("suscripciones/{id:int}/cobros")]
    [RequireModule("platform_billing", write: true)]
    public async Task<ActionResult<SubscriptionDto>> RegisterPayment(int id,
        [FromBody] RegisterSubscriptionPaymentRequest request)
        => Ok(await service.RegisterPaymentAsync(id, request));

    [HttpPost("suscripciones/{id:int}/cambiar-plan")]
    [RequireModule("platform_billing", write: true)]
    public async Task<ActionResult<SubscriptionDto>> ChangePlan(int id, [FromBody] ChangePlanRequest request)
        => Ok(await service.ChangePlanAsync(id, request));

    [HttpPost("suscripciones/{id:int}/dar-de-baja")]
    [RequireModule("platform_billing", write: true)]
    public async Task<ActionResult<SubscriptionDto>> CancelSubscription(int id, [FromQuery] string? motivo)
        => Ok(await service.CancelAsync(id, motivo));

    // ---------------------------------------------------------------- Planes ----

    [HttpGet("planes")]
    [RequireModule("platform_plans")]
    public async Task<ActionResult<IReadOnlyList<PlanDto>>> ListPlans([FromQuery] bool? active)
        => Ok(await service.ListPlansAsync(active));

    [HttpPost("planes")]
    [RequireModule("platform_plans", write: true)]
    public async Task<ActionResult<PlanDto>> CreatePlan([FromBody] PlanRequest request)
        => Ok(await service.CreatePlanAsync(request));

    [HttpPut("planes/{id:int}")]
    [RequireModule("platform_plans", write: true)]
    public async Task<ActionResult<PlanDto>> UpdatePlan(int id, [FromBody] PlanRequest request)
        => Ok(await service.UpdatePlanAsync(id, request));

    [HttpPost("planes/{id:int}/estado")]
    [RequireModule("platform_plans", write: true)]
    public async Task<ActionResult<PlanDto>> TogglePlan(int id, [FromQuery] bool active)
        => Ok(await service.SetPlanActiveAsync(id, active));
}
