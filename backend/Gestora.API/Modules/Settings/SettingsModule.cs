using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Settings;

/// <summary>Lo que la empresa puede ver y cambiar de su propia ficha.</summary>
public record CompanySettingsDto(int Id, string Name, string? TaxId, string? Phone,
    string AccountEmail, string? Address, string Currency, decimal DefaultTaxRate,
    string StatusName, DateTime CreatedAt, CompanyStatsDto Stats);

/// <summary>Tamaño de la cuenta, para que el administrador sepa qué tiene cargado.</summary>
public record CompanyStatsDto(int Users, int Customers, int Suppliers, int Products,
    int Sales, int Purchases, int Repairs, int ProductionOrders);

public class CompanySettingsRequest
{
    [Required(ErrorMessage = "Debe indicar el nombre de la empresa.")]
    [MaxLength(150)] public string Name { get; set; } = string.Empty;

    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }
    [MaxLength(250)] public string? Address { get; set; }

    [Required(ErrorMessage = "Debe indicar la moneda.")]
    [MaxLength(3), MinLength(3, ErrorMessage = "La moneda usa el código de 3 letras: CRC, USD…")]
    public string Currency { get; set; } = "CRC";

    [Range(0, 100, ErrorMessage = "El impuesto debe estar entre 0 y 100.")]
    public decimal DefaultTaxRate { get; set; }
}

/// <summary>
/// Configuración de la empresa.
///
/// El correo de la cuenta no se edita desde acá: es el identificador con el que Gestora
/// reconoce a la empresa suscrita, y cambiarlo es una operación de plataforma. Tampoco
/// aparece nada de la suscripción: eso vive del otro lado del sistema.
/// </summary>
public class SettingsService(GestoraDbContext db, IAuditService audit, ICurrentUser current)
{
    public async Task<CompanySettingsDto> GetAsync()
    {
        var company = await LoadAsync();

        var stats = new CompanyStatsDto(
            await db.Users.CountAsync(u => u.IsActive),
            await db.Customers.CountAsync(),
            await db.Suppliers.CountAsync(),
            await db.Products.CountAsync(),
            await db.Sales.CountAsync(),
            await db.Purchases.CountAsync(),
            await db.RepairOrders.CountAsync(),
            await db.ProductionOrders.CountAsync());

        return Map(company, stats);
    }

    public async Task<CompanySettingsDto> UpdateAsync(CompanySettingsRequest request)
    {
        var company = await LoadAsync();
        var currency = request.Currency.Trim().ToUpperInvariant();

        audit.Track("Actualización", "settings", nameof(Company), company.Id, "Datos de la empresa",
            $"{company.Name} · {company.Currency} · IVA {company.DefaultTaxRate:N2}%",
            $"{request.Name.Trim()} · {currency} · IVA {request.DefaultTaxRate:N2}%");

        company.Name = request.Name.Trim();
        company.TaxId = request.TaxId?.Trim();
        company.Phone = request.Phone?.Trim();
        company.Address = request.Address?.Trim();
        company.Currency = currency;
        company.DefaultTaxRate = request.DefaultTaxRate;

        await db.SaveChangesAsync();
        return await GetAsync();
    }

    /// <summary>
    /// La empresa de la sesión. Se busca ignorando el filtro multiempresa porque
    /// <c>Company</c> es la entidad raíz: no lleva <c>CompanyId</c> propio.
    /// </summary>
    private async Task<Company> LoadAsync() =>
        await db.Companies.IgnoreQueryFilters().FirstOrDefaultAsync(c => c.Id == current.CompanyId)
        ?? throw ApiException.NotFound("La empresa");

    private static CompanySettingsDto Map(Company c, CompanyStatsDto stats) => new(
        c.Id, c.Name, c.TaxId, c.Phone, c.AccountEmail, c.Address, c.Currency, c.DefaultTaxRate,
        c.Status switch
        {
            CompanyStatus.Active => "Activa",
            CompanyStatus.Suspended => "Suspendida",
            CompanyStatus.Cancelled => "Dada de baja",
            _ => c.Status.ToString()
        },
        c.CreatedAt, stats);
}

[ApiController]
[Route("api/configuracion")]
[Authorize]
public class SettingsController(SettingsService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("settings")]
    public async Task<ActionResult<CompanySettingsDto>> Get() => Ok(await service.GetAsync());

    [HttpPut]
    [RequireModule("settings", write: true)]
    public async Task<ActionResult<CompanySettingsDto>> Update([FromBody] CompanySettingsRequest request)
        => Ok(await service.UpdateAsync(request));
}
