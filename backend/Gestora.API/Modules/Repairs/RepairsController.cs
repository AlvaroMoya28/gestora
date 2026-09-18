using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Repairs;

[ApiController]
[Route("api/reparaciones")]
[Authorize]
public class RepairsController(RepairService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("repairs")]
    public async Task<ActionResult<PagedResult<RepairOrderSummaryDto>>> List([FromQuery] RepairQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("repairs")]
    public async Task<ActionResult<RepairOrderDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("repairs", write: true)]
    public async Task<ActionResult<RepairOrderDto>> Create([FromBody] RepairOrderRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("repairs", write: true)]
    public async Task<ActionResult<RepairOrderDto>> Update(int id, [FromBody] RepairOrderRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("{id:int}/iniciar")]
    [RequireModule("repairs", write: true)]
    public async Task<ActionResult<RepairOrderDto>> Start(int id) => Ok(await service.StartAsync(id));

    /// <summary>Termina el trabajo: descarga del inventario el material utilizado.</summary>
    [HttpPost("{id:int}/terminar")]
    [RequireModule("repairs", write: true)]
    public async Task<ActionResult<RepairOrderDto>> Complete(int id,
        [FromBody] CompleteRepairRequest request)
        => Ok(await service.CompleteAsync(id, request));

    /// <summary>Entrega al cliente: genera la cuenta por cobrar.</summary>
    [HttpPost("{id:int}/entregar")]
    [RequireModule("repairs", write: true)]
    public async Task<ActionResult<RepairOrderDto>> Deliver(int id) => Ok(await service.DeliverAsync(id));

    [HttpPost("{id:int}/cancelar")]
    [RequireModule("repairs", write: true)]
    public async Task<ActionResult<RepairOrderDto>> Cancel(int id) => Ok(await service.CancelAsync(id));
}
