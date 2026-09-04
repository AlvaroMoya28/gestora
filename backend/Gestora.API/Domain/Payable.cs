using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

public enum PayableStatus
{
    Pending = 0,
    PartiallyPaid = 1,
    Paid = 2,
    Cancelled = 3
}

/// <summary>
/// Obligación de pago con un proveedor. Nace al confirmar una compra a crédito o al
/// contado: incluso al contado se registra la deuda y su pago por separado, en vez
/// de un campo <c>paid = true</c>, para poder ver siempre facturas, saldos y abonos.
/// </summary>
public class AccountPayable : TenantEntity
{
    public int SupplierId { get; set; }
    public Supplier Supplier { get; set; } = null!;

    public int? PurchaseId { get; set; }
    public Purchase? Purchase { get; set; }

    [MaxLength(60)] public string DocumentNumber { get; set; } = string.Empty;

    public DateTime IssueDate { get; set; }
    public DateTime DueDate { get; set; }

    public decimal Total { get; set; }
    /// <summary>Suma de los pagos aplicados. Solo la escribe <c>PayableService</c>.</summary>
    public decimal PaidAmount { get; set; }
    /// <summary>Total − PaidAmount, recalculado en cada pago.</summary>
    public decimal Balance { get; set; }

    public PayableStatus Status { get; set; } = PayableStatus.Pending;
    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<Payment> Payments { get; set; } = new List<Payment>();
}

/// <summary>
/// Un abono, completo o parcial, sobre una cuenta por pagar. Entidad propia —no un
/// booleano— para poder registrar varios pagos sobre la misma factura con su fecha,
/// monto, método y referencia.
/// </summary>
public class Payment : BaseEntity
{
    public int AccountPayableId { get; set; }
    public AccountPayable AccountPayable { get; set; } = null!;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public decimal Amount { get; set; }

    [MaxLength(40)] public string Method { get; set; } = string.Empty;
    [MaxLength(80)] public string? Reference { get; set; }
    [MaxLength(250)] public string? Notes { get; set; }

    public int? UserId { get; set; }
    public User? User { get; set; }
}
