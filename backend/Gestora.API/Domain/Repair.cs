using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>
/// Estados por los que pasa una reparación. El material sale del inventario al terminar
/// el trabajo, y el cobro al cliente nace al entregar.
/// </summary>
public enum RepairStatus
{
    /// <summary>Recibida del cliente, pendiente de revisar.</summary>
    Received = 0,
    /// <summary>En taller.</summary>
    InProgress = 1,
    /// <summary>Terminada y lista para entregar: acá se consumió el material.</summary>
    Ready = 2,
    /// <summary>Entregada al cliente: acá nació la cuenta por cobrar.</summary>
    Delivered = 3,
    Cancelled = 4
}

/// <summary>
/// Orden de reparación de un artículo del cliente. No es una venta de inventario: lo que
/// se cobra es mano de obra más los materiales que se hayan usado, y esos materiales sí
/// salen del inventario de la empresa.
/// </summary>
public class RepairOrder : TenantEntity
{
    [MaxLength(20)] public string Number { get; set; } = string.Empty;

    public int CustomerId { get; set; }
    public Customer Customer { get; set; } = null!;

    /// <summary>Qué se recibió a reparar. Texto libre: no es un producto del catálogo.</summary>
    [MaxLength(200)] public string ItemDescription { get; set; } = string.Empty;
    /// <summary>Falla reportada por el cliente.</summary>
    [MaxLength(1000)] public string? ReportedIssue { get; set; }
    /// <summary>Diagnóstico del taller.</summary>
    [MaxLength(1000)] public string? Diagnosis { get; set; }

    public RepairStatus Status { get; set; } = RepairStatus.Received;

    public DateTime ReceivedAt { get; set; } = DateTime.UtcNow;
    public DateTime? PromisedAt { get; set; }
    public DateTime? CompletedAt { get; set; }
    public DateTime? DeliveredAt { get; set; }

    public decimal LaborCost { get; set; }
    /// <summary>Suma de los materiales cargados a la orden.</summary>
    public decimal MaterialsCost { get; set; }
    public decimal TaxRate { get; set; }
    public decimal TaxAmount { get; set; }
    /// <summary>Mano de obra + materiales + impuesto. Es lo que se le cobra al cliente.</summary>
    public decimal Total { get; set; }

    /// <summary>Condición de pago al entregar.</summary>
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;
    public int CreditDays { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<RepairMaterial> Materials { get; set; } = new List<RepairMaterial>();
}

public class RepairMaterial : BaseEntity
{
    public int RepairOrderId { get; set; }
    public RepairOrder RepairOrder { get; set; } = null!;

    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    public decimal Quantity { get; set; }
    /// <summary>Precio al que se le cobra el material al cliente.</summary>
    public decimal UnitPrice { get; set; }
    public decimal Subtotal { get; set; }
}
