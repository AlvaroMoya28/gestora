using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Production;

[ApiController]
[Route("api/produccion")]
[Authorize]
public class ProductionController(ProductionService service) : ControllerBase
{
    [HttpGet("ordenes")]
    [RequireModule("production")]
    public async Task<ActionResult<PagedResult<ProductionOrderSummaryDto>>> List(
        [FromQuery] ProductionQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("ordenes/{id:int}")]
    [RequireModule("production")]
    public async Task<ActionResult<ProductionOrderDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost("ordenes")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<ProductionOrderDto>> Create([FromBody] ProductionOrderRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("ordenes/{id:int}")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<ProductionOrderDto>> Update(int id,
        [FromBody] ProductionOrderRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("ordenes/{id:int}/iniciar")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<ProductionOrderDto>> Start(int id) => Ok(await service.StartAsync(id));

    /// <summary>Punto sin retorno: consume los materiales y da entrada al producto fabricado.</summary>
    [HttpPost("ordenes/{id:int}/terminar")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<ProductionOrderDto>> Complete(int id,
        [FromBody] CompleteProductionRequest request)
        => Ok(await service.CompleteAsync(id, request));

    [HttpPost("ordenes/{id:int}/cancelar")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<ProductionOrderDto>> Cancel(int id) => Ok(await service.CancelAsync(id));

    // ------------------------------------------------------------------ Recetas ----

    [HttpGet("recetas")]
    [RequireModule("production")]
    public async Task<ActionResult<IReadOnlyList<RecipeDto>>> Recipes([FromQuery] bool? active)
        => Ok(await service.ListRecipesAsync(active));

    [HttpGet("recetas/{id:int}")]
    [RequireModule("production")]
    public async Task<ActionResult<RecipeDto>> Recipe(int id) => Ok(await service.GetRecipeAsync(id));

    [HttpPost("recetas")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<RecipeDto>> CreateRecipe([FromBody] RecipeRequest request)
        => Ok(await service.CreateRecipeAsync(request));

    [HttpPut("recetas/{id:int}")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<RecipeDto>> UpdateRecipe(int id, [FromBody] RecipeRequest request)
        => Ok(await service.UpdateRecipeAsync(id, request));

    [HttpPost("recetas/{id:int}/activar")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<RecipeDto>> ActivateRecipe(int id)
        => Ok(await service.SetRecipeActiveAsync(id, true));

    [HttpPost("recetas/{id:int}/inactivar")]
    [RequireModule("production", write: true)]
    public async Task<ActionResult<RecipeDto>> DeactivateRecipe(int id)
        => Ok(await service.SetRecipeActiveAsync(id, false));
}
