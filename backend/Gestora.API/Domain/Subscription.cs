using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

public enum SubscriptionStatus
{
    /// <summary>Período de prueba, sin cobro todavía.</summary>
    Trial = 0,
    /// <summary>Vigente y al día.</summary>
    Active = 1,
    /// <summary>Pasó la fecha de vencimiento sin renovar.</summary>
    Expired = 2,
    /// <summary>Dada de baja por la empresa o por Gestora.</summary>
    Cancelled = 3
}

/// <summary>
/// Plan comercial de Gestora. Define el precio y cada cuánto se cobra; los límites
/// (usuarios incluidos) sirven para validar altas dentro de la empresa.
/// </summary>
public class Plan : BaseEntity
{
    [MaxLength(60)] public string Name { get; set; } = string.Empty;
    [MaxLength(300)] public string? Description { get; set; }

    /// <summary>Precio por período de facturación.</summary>
    public decimal Price { get; set; }
    [MaxLength(3)] public string Currency { get; set; } = "CRC";

    /// <summary>Meses que cubre cada cobro: 1 mensual, 12 anual.</summary>
    public int BillingPeriodMonths { get; set; } = 1;

    /// <summary>Usuarios que la empresa puede tener activos. 0 = sin límite.</summary>
    public int MaxUsers { get; set; }

    public bool IsActive { get; set; } = true;
    public int SortOrder { get; set; }
}

/// <summary>
/// Suscripción de una empresa a un plan. Es lo que determina si la empresa puede
/// entrar al sistema y cuándo hay que cobrarle.
/// </summary>
public class Subscription : BaseEntity
{
    public int CompanyId { get; set; }
    public Company Company { get; set; } = null!;

    public int PlanId { get; set; }
    public Plan Plan { get; set; } = null!;

    public DateTime StartDate { get; set; }
    /// <summary>Fecha hasta la que está pagada. Pasada esta fecha, la suscripción vence.</summary>
    public DateTime EndDate { get; set; }

    public SubscriptionStatus Status { get; set; } = SubscriptionStatus.Trial;

    /// <summary>Precio pactado, que puede diferir del precio de lista del plan.</summary>
    public decimal Price { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<SubscriptionPayment> Payments { get; set; } = new List<SubscriptionPayment>();

    /// <summary>Días que faltan para el vencimiento. Negativo si ya venció.</summary>
    public int DaysToExpiry => (int)(EndDate.Date - DateTime.UtcNow.Date).TotalDays;
}

/// <summary>
/// Cobro recibido de una empresa por su suscripción. Igual que en cuentas por pagar,
/// el pago es una entidad propia: deja constancia de qué período cubrió cada cobro.
/// </summary>
public class SubscriptionPayment : BaseEntity
{
    public int SubscriptionId { get; set; }
    public Subscription Subscription { get; set; } = null!;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public decimal Amount { get; set; }

    [MaxLength(40)] public string Method { get; set; } = string.Empty;
    [MaxLength(80)] public string? Reference { get; set; }

    /// <summary>Período que cubre este cobro; explica hasta dónde se corrió el vencimiento.</summary>
    public DateTime PeriodFrom { get; set; }
    public DateTime PeriodTo { get; set; }

    [MaxLength(250)] public string? Notes { get; set; }
    public int? UserId { get; set; }
    public User? User { get; set; }
}
