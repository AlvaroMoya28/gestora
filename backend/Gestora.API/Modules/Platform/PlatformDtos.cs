using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Platform;

// ------------------------------------------------------------------ Empresas ----

/// <summary>
/// Ficha comercial de una empresa. Deliberadamente no expone nada de su operación
/// (clientes, ventas, saldos): administración de Gestora gestiona la cuenta, no el
/// negocio del cliente.
/// </summary>
public record CompanyDto(int Id, string Name, string AccountEmail, string? TaxId, string? Phone,
    string? Address, string Currency, CompanyStatus Status, string StatusName, string? Notes,
    DateTime CreatedAt, int UserCount, SubscriptionSummaryDto? Subscription);

public record SubscriptionSummaryDto(int Id, int PlanId, string PlanName, DateTime StartDate,
    DateTime EndDate, SubscriptionStatus Status, string StatusName, decimal Price,
    int DaysToExpiry, bool IsExpired);

public class CompanyRequest
{
    [Required(ErrorMessage = "El nombre de la empresa es obligatorio.")]
    [MaxLength(150)] public string Name { get; set; } = string.Empty;

    [Required(ErrorMessage = "El correo de la cuenta es obligatorio.")]
    [EmailAddress(ErrorMessage = "El correo no tiene un formato válido.")]
    [MaxLength(150)] public string AccountEmail { get; set; } = string.Empty;

    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }
    [MaxLength(250)] public string? Address { get; set; }

    [MaxLength(3)] public string Currency { get; set; } = "CRC";

    [Range(0, 100, ErrorMessage = "El impuesto debe estar entre 0 y 100.")]
    public decimal DefaultTaxRate { get; set; } = 13m;

    [MaxLength(500)] public string? Notes { get; set; }
}

/// <summary>
/// Alta completa de un cliente nuevo: crea la empresa, su suscripción y la cuenta de
/// administrador con la que entrará. Es lo que se ejecuta cuando alguien se suscribe.
/// </summary>
public class RegisterCompanyRequest : CompanyRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un plan.")]
    public int PlanId { get; set; }

    /// <summary>Si se omite, arranca hoy.</summary>
    public DateTime? StartDate { get; set; }

    /// <summary>Precio pactado. Si se omite, se toma el precio de lista del plan.</summary>
    public decimal? Price { get; set; }

    public bool TrialPeriod { get; set; }

    [Required(ErrorMessage = "El nombre del contacto administrador es obligatorio.")]
    [MaxLength(100)] public string AdminFirstName { get; set; } = string.Empty;

    [MaxLength(150)] public string AdminLastName { get; set; } = string.Empty;

    /// <summary>Si se omite, se genera una contraseña temporal que se devuelve una sola vez.</summary>
    [MinLength(8, ErrorMessage = "La contraseña debe tener al menos 8 caracteres.")]
    public string? AdminPassword { get; set; }
}

/// <summary>
/// Resultado del alta. La contraseña temporal viaja una única vez, en esta respuesta:
/// no se guarda en claro ni se puede volver a consultar.
/// </summary>
public record RegisterCompanyResult(CompanyDto Company, string AdminEmail, string? TemporaryPassword);

public class CompanyQuery : QueryParams
{
    public CompanyStatus? Status { get; set; }
    /// <summary>Solo empresas con la suscripción vencida.</summary>
    public bool? Expired { get; set; }
}

// ------------------------------------------------------------------- Planes ----

public record PlanDto(int Id, string Name, string? Description, decimal Price, string Currency,
    int BillingPeriodMonths, int MaxUsers, bool IsActive, int SortOrder, int CompanyCount);

public class PlanRequest
{
    [Required(ErrorMessage = "El nombre del plan es obligatorio.")]
    [MaxLength(60)] public string Name { get; set; } = string.Empty;

    [MaxLength(300)] public string? Description { get; set; }

    [Range(0, 99999999, ErrorMessage = "El precio no puede ser negativo.")]
    public decimal Price { get; set; }

    [MaxLength(3)] public string Currency { get; set; } = "CRC";

    [Range(1, 60, ErrorMessage = "El período debe estar entre 1 y 60 meses.")]
    public int BillingPeriodMonths { get; set; } = 1;

    [Range(0, 500, ErrorMessage = "El máximo de usuarios no puede ser negativo.")]
    public int MaxUsers { get; set; }

    public int SortOrder { get; set; }
}

// ----------------------------------------------------------- Suscripciones ----

public record SubscriptionPaymentDto(int Id, DateTime Date, decimal Amount, string Method,
    string? Reference, DateTime PeriodFrom, DateTime PeriodTo, string? Notes, string? UserName);

public record SubscriptionDto(int Id, int CompanyId, string CompanyName, string AccountEmail,
    int PlanId, string PlanName, DateTime StartDate, DateTime EndDate, SubscriptionStatus Status,
    string StatusName, decimal Price, int DaysToExpiry, bool IsExpired, string? Notes,
    IReadOnlyList<SubscriptionPaymentDto> Payments);

public class SubscriptionQuery : QueryParams
{
    public SubscriptionStatus? Status { get; set; }
    /// <summary>Suscripciones que vencen dentro de N días (o ya vencidas).</summary>
    public int? ExpiringInDays { get; set; }
}

/// <summary>Cobro de una suscripción. Al registrarlo se corre la fecha de vencimiento.</summary>
public class RegisterSubscriptionPaymentRequest
{
    [Range(0.01, 99999999, ErrorMessage = "El monto debe ser mayor que cero.")]
    public decimal Amount { get; set; }

    public DateTime? Date { get; set; }

    [Required(ErrorMessage = "Debe indicar el método de pago.")]
    [MaxLength(40)] public string Method { get; set; } = string.Empty;

    [MaxLength(80)] public string? Reference { get; set; }

    /// <summary>Períodos que cubre el cobro. Por defecto uno, según el plan.</summary>
    [Range(1, 60, ErrorMessage = "Debe cubrir entre 1 y 60 períodos.")]
    public int Periods { get; set; } = 1;

    [MaxLength(250)] public string? Notes { get; set; }
}

public class ChangePlanRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un plan.")]
    public int PlanId { get; set; }

    /// <summary>Precio pactado. Si se omite, se toma el de lista.</summary>
    public decimal? Price { get; set; }

    [MaxLength(250)] public string? Reason { get; set; }
}

// -------------------------------------------------------------- Resumen ----

public record PlatformMetricDto(string Key, string Label, decimal Value, string Format, string? Hint);

public record PlatformOverviewDto(
    IReadOnlyList<PlatformMetricDto> Metrics,
    IReadOnlyList<SubscriptionDto> ExpiringSoon,
    IReadOnlyList<CompanyDto> RecentCompanies);
