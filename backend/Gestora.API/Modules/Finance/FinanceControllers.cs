using Gestora.API.Common;
using Gestora.API.Domain;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Finance;

/// <summary>
/// Ingresos y gastos comparten servicio y tabla, pero son dos módulos con permisos
/// propios: una empresa puede querer que alguien registre gastos sin ver los ingresos.
/// Cada controlador fija su <see cref="FinanceKind"/> y delega en el mismo servicio.
/// </summary>
[ApiController]
[Route("api/ingresos")]
[Authorize]
public class IncomeController(FinanceService service) : ControllerBase
{
    private const FinanceKind Kind = FinanceKind.Income;

    [HttpGet]
    [RequireModule("income")]
    public async Task<ActionResult<PagedResult<FinanceEntryDto>>> List([FromQuery] FinanceQuery query)
        => Ok(await service.ListAsync(Kind, query));

    [HttpGet("resumen")]
    [RequireModule("income")]
    public async Task<ActionResult<FinanceSummaryDto>> Summary([FromQuery] FinanceQuery query)
        => Ok(await service.SummaryAsync(Kind, query));

    [HttpGet("{id:int}")]
    [RequireModule("income")]
    public async Task<ActionResult<FinanceEntryDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("income", write: true)]
    public async Task<ActionResult<FinanceEntryDto>> Create([FromBody] FinanceEntryRequest request)
    {
        var created = await service.CreateAsync(Kind, request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("income", write: true)]
    public async Task<ActionResult<FinanceEntryDto>> Update(int id, [FromBody] FinanceEntryRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpDelete("{id:int}")]
    [RequireModule("income", write: true)]
    public async Task<IActionResult> Delete(int id)
    {
        await service.DeleteAsync(id);
        return NoContent();
    }

    [HttpGet("categorias")]
    [RequireModule("income")]
    public async Task<ActionResult<IReadOnlyList<FinanceCategoryDto>>> Categories([FromQuery] bool? active)
        => Ok(await service.ListCategoriesAsync(Kind, active));
}

[ApiController]
[Route("api/gastos")]
[Authorize]
public class ExpensesController(FinanceService service) : ControllerBase
{
    private const FinanceKind Kind = FinanceKind.Expense;

    [HttpGet]
    [RequireModule("expenses")]
    public async Task<ActionResult<PagedResult<FinanceEntryDto>>> List([FromQuery] FinanceQuery query)
        => Ok(await service.ListAsync(Kind, query));

    [HttpGet("resumen")]
    [RequireModule("expenses")]
    public async Task<ActionResult<FinanceSummaryDto>> Summary([FromQuery] FinanceQuery query)
        => Ok(await service.SummaryAsync(Kind, query));

    [HttpGet("{id:int}")]
    [RequireModule("expenses")]
    public async Task<ActionResult<FinanceEntryDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("expenses", write: true)]
    public async Task<ActionResult<FinanceEntryDto>> Create([FromBody] FinanceEntryRequest request)
    {
        var created = await service.CreateAsync(Kind, request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("expenses", write: true)]
    public async Task<ActionResult<FinanceEntryDto>> Update(int id, [FromBody] FinanceEntryRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpDelete("{id:int}")]
    [RequireModule("expenses", write: true)]
    public async Task<IActionResult> Delete(int id)
    {
        await service.DeleteAsync(id);
        return NoContent();
    }

    [HttpGet("categorias")]
    [RequireModule("expenses")]
    public async Task<ActionResult<IReadOnlyList<FinanceCategoryDto>>> Categories([FromQuery] bool? active)
        => Ok(await service.ListCategoriesAsync(Kind, active));
}

/// <summary>
/// Mantenimiento de las categorías, desde configuración. Se administran juntas las de
/// ingreso y las de gasto porque conceptualmente son la misma lista del plan de cuentas.
/// </summary>
[ApiController]
[Route("api/finanzas/categorias")]
[Authorize]
public class FinanceCategoriesController(FinanceService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("settings")]
    public async Task<ActionResult<IReadOnlyList<FinanceCategoryDto>>> List(
        [FromQuery] FinanceKind? kind, [FromQuery] bool? active)
        => Ok(await service.ListCategoriesAsync(kind, active));

    [HttpPost]
    [RequireModule("settings", write: true)]
    public async Task<ActionResult<FinanceCategoryDto>> Create([FromBody] FinanceCategoryRequest request)
        => Ok(await service.CreateCategoryAsync(request));

    [HttpPut("{id:int}")]
    [RequireModule("settings", write: true)]
    public async Task<ActionResult<FinanceCategoryDto>> Update(int id, [FromBody] FinanceCategoryRequest request)
        => Ok(await service.UpdateCategoryAsync(id, request));

    [HttpPost("{id:int}/activar")]
    [RequireModule("settings", write: true)]
    public async Task<ActionResult<FinanceCategoryDto>> Activate(int id)
        => Ok(await service.SetCategoryActiveAsync(id, true));

    [HttpPost("{id:int}/inactivar")]
    [RequireModule("settings", write: true)]
    public async Task<ActionResult<FinanceCategoryDto>> Deactivate(int id)
        => Ok(await service.SetCategoryActiveAsync(id, false));
}
