using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

public enum FinanceKind
{
    Income = 0,
    Expense = 1
}

/// <summary>
/// De dónde salió el asiento. Los automáticos son el reflejo en caja de un cobro o un
/// pago ya registrado: no se editan desde finanzas, porque su verdad vive en la cuenta
/// que los originó. Así el dinero nunca se registra dos veces con dos saldos distintos.
/// </summary>
public enum FinanceSource
{
    /// <summary>Registrado a mano en ingresos o gastos.</summary>
    Manual = 0,
    /// <summary>Generado por un cobro de una cuenta por cobrar.</summary>
    Receipt = 1,
    /// <summary>Generado por un pago de una cuenta por pagar.</summary>
    Payment = 2
}

/// <summary>Categoría de ingreso o de gasto. Cada empresa define las suyas.</summary>
public class FinanceCategory : TenantEntity
{
    [MaxLength(80)] public string Name { get; set; } = string.Empty;
    public FinanceKind Kind { get; set; }
    [MaxLength(250)] public string? Description { get; set; }
    /// <summary>Las categorías del sistema respaldan los asientos automáticos y no se borran.</summary>
    public bool IsSystem { get; set; }
    public bool IsActive { get; set; } = true;
}

/// <summary>
/// Movimiento de dinero: un ingreso o un gasto. Es el libro de caja de la empresa.
/// Los cobros a clientes y los pagos a proveedores caen acá automáticamente, de modo
/// que ingresos y gastos muestren el flujo completo y no solo lo cargado a mano.
/// </summary>
public class FinanceEntry : TenantEntity
{
    public FinanceKind Kind { get; set; }

    public int CategoryId { get; set; }
    public FinanceCategory Category { get; set; } = null!;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public decimal Amount { get; set; }

    [MaxLength(200)] public string Description { get; set; } = string.Empty;
    [MaxLength(40)] public string Method { get; set; } = string.Empty;
    [MaxLength(80)] public string? Reference { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }

    public FinanceSource Source { get; set; } = FinanceSource.Manual;
    /// <summary>Id del cobro o del pago que lo originó, cuando es automático.</summary>
    public int? SourceId { get; set; }

    public int? UserId { get; set; }
    public User? User { get; set; }

    public bool IsManual => Source == FinanceSource.Manual;
}
