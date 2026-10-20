using System.Text;
using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Einvoice;

[ApiController]
[Route("api/facturas-recibidas")]
[Authorize]
public class ReceivedDocumentsController(ReceivedDocumentService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("einvoices")]
    public async Task<ActionResult<PagedResult<ReceivedDocumentDto>>> List([FromQuery] ReceivedDocumentQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("einvoices")]
    public async Task<ActionResult<ReceivedDocumentDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpGet("{id:int}/xml")]
    [RequireModule("einvoices")]
    public async Task<IActionResult> Xml(int id) => Content(await service.GetXmlAsync(id), "application/xml", Encoding.UTF8);

    [HttpGet("direccion")]
    [RequireModule("einvoices")]
    public async Task<ActionResult<InboundAddressDto>> Address() => Ok(await service.GetAddressAsync());

    [HttpPost("subir")]
    [RequireModule("einvoices", write: true)]
    [RequestSizeLimit(20_000_000)]
    public async Task<ActionResult<List<UploadResultDto>>> Upload([FromForm] List<IFormFile> files)
    {
        if (files.Count == 0) throw new ApiException("Seleccione al menos un archivo XML.");
        var results = new List<UploadResultDto>();
        foreach (var file in files.Take(50))
        {
            using var reader = new StreamReader(file.OpenReadStream(), Encoding.UTF8);
            results.Add(await service.UploadAsync(file.FileName, await reader.ReadToEndAsync()));
        }
        return Ok(results);
    }

    [HttpPost("{id:int}/importar")]
    [RequireModule("einvoices", write: true)]
    public async Task<ActionResult<ReceivedDocumentDto>> Import(int id, [FromBody] ImportRequest request)
        => Ok(await service.ImportAsync(id, request));

    [HttpPost("{id:int}/descartar")]
    [RequireModule("einvoices", write: true)]
    public async Task<ActionResult<ReceivedDocumentDto>> Discard(int id) => Ok(await service.DiscardAsync(id));
}

/// <summary>
/// Receptor del correo entrante. Lo llama el proveedor de correo (SendGrid Inbound Parse,
/// Mailgun Routes...), no un usuario; por eso se autentica con un secreto compartido en la
/// configuración y no con sesión.
/// </summary>
[ApiController]
[Route("api/correo-entrante")]
[AllowAnonymous]
public class InboundMailController(ReceivedDocumentService service, IConfiguration config) : ControllerBase
{
    [HttpPost]
    [RequestSizeLimit(30_000_000)]
    public async Task<IActionResult> Receive([FromQuery] string? secret)
    {
        var expected = config["Einvoice:InboundSecret"];
        if (string.IsNullOrEmpty(expected) || !System.Security.Cryptography.CryptographicOperations.FixedTimeEquals(
                Encoding.UTF8.GetBytes(secret ?? string.Empty), Encoding.UTF8.GetBytes(expected)))
            return Unauthorized();

        var form = await Request.ReadFormAsync();
        var files = new List<(string, string)>();
        foreach (var file in form.Files.Where(f => f.FileName.EndsWith(".xml", StringComparison.OrdinalIgnoreCase)))
        {
            using var reader = new StreamReader(file.OpenReadStream(), Encoding.UTF8);
            files.Add((file.FileName, await reader.ReadToEndAsync()));
        }

        var received = await service.ReceiveEmailAsync(form["to"].ToString(), form["from"].ToString(), files);
        return Ok(new { received });
    }
}
