using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

public enum ReceivableStatus
{
    Pending = 0,
    PartiallyCollected = 1,
    Collected = 2,
    Cancelled = 3
}

/// <summary>
/// Derecho de cobro contra un cliente. Nace al confirmar una venta o al entregar una
/// reparación. Espejo exacto de <see cref="AccountPayable"/>: mismo modelo de saldo y
/// abonos, para que quien entiende una entienda la otra.
/// </summary>
public class AccountReceivable : TenantEntity
{
    public int CustomerId { get; set; }
    public Customer Customer { get; set; } = null!;

    public int? SaleId { get; set; }
    public Sale? Sale { get; set; }

    public int? RepairOrderId { get; set; }
    public RepairOrder? RepairOrder { get; set; }

    [MaxLength(60)] public string DocumentNumber { get; set; } = string.Empty;

    public DateTime IssueDate { get; set; }
    public DateTime DueDate { get; set; }

    public decimal Total { get; set; }
    /// <summary>Suma de los cobros aplicados. Solo la escribe <c>ReceivableService</c>.</summary>
    public decimal CollectedAmount { get; set; }
    /// <summary>Total − CollectedAmount, recalculado en cada cobro.</summary>
    public decimal Balance { get; set; }

    public ReceivableStatus Status { get; set; } = ReceivableStatus.Pending;
    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<Receipt> Receipts { get; set; } = new List<Receipt>();
}

/// <summary>Cobro, completo o parcial, sobre una cuenta por cobrar.</summary>
public class Receipt : BaseEntity
{
    public int AccountReceivableId { get; set; }
    public AccountReceivable AccountReceivable { get; set; } = null!;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public decimal Amount { get; set; }

    [MaxLength(40)] public string Method { get; set; } = string.Empty;
    [MaxLength(80)] public string? Reference { get; set; }
    [MaxLength(250)] public string? Notes { get; set; }

    public int? UserId { get; set; }
    public User? User { get; set; }
}
