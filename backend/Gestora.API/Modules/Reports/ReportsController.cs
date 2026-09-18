using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Reports;

[ApiController]
[Route("api/reportes")]
[Authorize]
[RequireModule("reports")]
public class ReportsController(ReportService service) : ControllerBase
{
    /// <summary>Catálogo de reportes disponibles, para armar el menú de la pantalla.</summary>
    [HttpGet]
    public ActionResult<IReadOnlyList<ReportDefinitionDto>> Available() => Ok(ReportService.Available);

    [HttpGet("{key}")]
    public async Task<ActionResult<ReportDto>> Get(string key,
        [FromQuery] DateTime? from, [FromQuery] DateTime? to)
        => Ok(await service.BuildAsync(key, from, to));

    /// <summary>El mismo reporte en CSV, para abrirlo en una hoja de cálculo.</summary>
    [HttpGet("{key}/csv")]
    public async Task<IActionResult> Csv(string key, [FromQuery] DateTime? from, [FromQuery] DateTime? to)
    {
        var report = await service.BuildAsync(key, from, to);
        var name = $"{key}-{DateTime.UtcNow:yyyyMMdd}.csv";

        return File(ReportService.ToCsv(report), "text/csv", name);
    }
}
