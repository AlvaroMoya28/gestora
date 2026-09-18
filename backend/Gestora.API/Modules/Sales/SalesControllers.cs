using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Sales;

[ApiController]
[Route("api/ventas")]
[Authorize]
public class SalesController(SaleService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("sales")]
    public async Task<ActionResult<PagedResult<SaleSummaryDto>>> List([FromQuery] SaleQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("sales")]
    public async Task<ActionResult<SaleDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("sales", write: true)]
    public async Task<ActionResult<SaleDto>> Create([FromBody] SaleRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("sales", write: true)]
    public async Task<ActionResult<SaleDto>> Update(int id, [FromBody] SaleRequest request)
        => Ok(await service.UpdateAsync(id, request));

    /// <summary>Punto sin retorno: descarga el inventario y genera la cuenta por cobrar.</summary>
    [HttpPost("{id:int}/confirmar")]
    [RequireModule("sales", write: true)]
    public async Task<ActionResult<SaleDto>> Confirm(int id) => Ok(await service.ConfirmAsync(id));

    [HttpPost("{id:int}/cancelar")]
    [RequireModule("sales", write: true)]
    public async Task<ActionResult<SaleDto>> Cancel(int id) => Ok(await service.CancelAsync(id));
}

[ApiController]
[Route("api/cuentas-por-cobrar")]
[Authorize]
public class ReceivablesController(ReceivableService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("receivables")]
    public async Task<ActionResult<PagedResult<AccountReceivableDto>>> List([FromQuery] ReceivableQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("receivables")]
    public async Task<ActionResult<AccountReceivableDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost("{id:int}/cobros")]
    [RequireModule("receivables", write: true)]
    public async Task<ActionResult<AccountReceivableDto>> RegisterReceipt(int id,
        [FromBody] RegisterReceiptRequest request)
        => Ok(await service.RegisterReceiptAsync(id, request));
}
