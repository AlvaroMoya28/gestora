using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Inventory;

[ApiController]
[Route("api/inventario")]
[Authorize]
public class InventoryController(InventoryService service) : ControllerBase
{
    /// <summary>Historial de movimientos. Es el "por qué" de cada variación de existencias.</summary>
    [HttpGet("movimientos")]
    [RequireModule("inventory")]
    public async Task<ActionResult<PagedResult<MovementDto>>> Movements([FromQuery] MovementQuery query)
        => Ok(await service.ListAsync(query));

    /// <summary>Registra una entrada o salida manual.</summary>
    [HttpPost("movimientos")]
    [RequireModule("inventory", write: true)]
    public async Task<ActionResult<MovementDto>> Create([FromBody] MovementRequest request)
    {
        // El sentido explícito solo se admite en ajustes, que tienen su propio endpoint.
        request.DirectionOverride = null;
        request.AllowNegative = false;
        return Ok(await service.ApplyMovementAsync(request));
    }

    /// <summary>Ajuste por conteo físico: se envía la existencia real y el sistema calcula el delta.</summary>
    [HttpPost("ajustes")]
    [RequireModule("inventory", write: true)]
    public async Task<ActionResult<MovementDto>> Adjust([FromBody] StockAdjustmentRequest request)
        => Ok(await service.AdjustAsync(request));
}
