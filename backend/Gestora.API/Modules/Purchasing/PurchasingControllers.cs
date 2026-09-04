using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Purchasing;

[ApiController]
[Route("api/compras")]
[Authorize]
public class PurchasesController(PurchaseService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("purchases")]
    public async Task<ActionResult<PagedResult<PurchaseSummaryDto>>> List([FromQuery] PurchaseQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("purchases")]
    public async Task<ActionResult<PurchaseDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("purchases", write: true)]
    public async Task<ActionResult<PurchaseDto>> Create([FromBody] PurchaseRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("purchases", write: true)]
    public async Task<ActionResult<PurchaseDto>> Update(int id, [FromBody] PurchaseRequest request)
        => Ok(await service.UpdateAsync(id, request));

    /// <summary>Punto sin retorno: genera el movimiento de inventario y la cuenta por pagar.</summary>
    [HttpPost("{id:int}/confirmar")]
    [RequireModule("purchases", write: true)]
    public async Task<ActionResult<PurchaseDto>> Confirm(int id) => Ok(await service.ConfirmAsync(id));

    [HttpPost("{id:int}/cancelar")]
    [RequireModule("purchases", write: true)]
    public async Task<ActionResult<PurchaseDto>> Cancel(int id) => Ok(await service.CancelAsync(id));
}

[ApiController]
[Route("api/cuentas-por-pagar")]
[Authorize]
public class PayablesController(PayableService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("payables")]
    public async Task<ActionResult<PagedResult<AccountPayableDto>>> List([FromQuery] PayableQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("payables")]
    public async Task<ActionResult<AccountPayableDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost("{id:int}/pagos")]
    [RequireModule("payables", write: true)]
    public async Task<ActionResult<AccountPayableDto>> RegisterPayment(int id, [FromBody] RegisterPaymentRequest request)
        => Ok(await service.RegisterPaymentAsync(id, request));
}
