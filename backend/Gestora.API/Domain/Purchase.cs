using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>
/// Un borrador se puede editar libremente. Confirmar es el punto sin retorno: ahí
/// nacen el movimiento de inventario y la cuenta por pagar, dentro de una sola
/// transacción. Cancelar solo es posible mientras sigue en borrador.
/// </summary>
public enum PurchaseStatus
{
    Draft = 0,
    Confirmed = 1,
    Cancelled = 2
}

/// <summary>
/// Compra a un proveedor. Fusiona en un solo documento lo que conceptualmente sería
/// orden de compra + recepción, para no obligar a la secretaría a llenar dos
/// pantallas por cada compra en esta primera versión.
/// </summary>
public class Purchase : TenantEntity
{
    [MaxLength(20)] public string Number { get; set; } = string.Empty;

    public int SupplierId { get; set; }
    public Supplier Supplier { get; set; } = null!;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public PurchaseStatus Status { get; set; } = PurchaseStatus.Draft;

    /// <summary>
    /// Condición pactada en esta compra. Se propone la del proveedor, pero se guarda
    /// acá porque una compra puntual puede negociarse distinto de lo habitual.
    /// </summary>
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;

    /// <summary>Días de plazo desde <see cref="Date"/>. 0 en las compras de contado.</summary>
    public int CreditDays { get; set; }

    public decimal Subtotal { get; set; }
    public decimal TaxAmount { get; set; }
    public decimal Total { get; set; }

    /// <summary>Número de factura del proveedor, si ya se conoce. Texto libre.</summary>
    [MaxLength(60)] public string? SupplierInvoiceNumber { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<PurchaseItem> Items { get; set; } = new List<PurchaseItem>();
}

public class PurchaseItem : BaseEntity
{
    public int PurchaseId { get; set; }
    public Purchase Purchase { get; set; } = null!;

    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    public decimal Quantity { get; set; }
    public decimal UnitCost { get; set; }
    public decimal TaxRate { get; set; }

    /// <summary>Cantidad × costo unitario, sin impuesto.</summary>
    public decimal Subtotal { get; set; }
}
