using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>
/// Misma lógica que la compra: el borrador se edita libremente y confirmar es el punto
/// sin retorno, donde sale el inventario y nace la cuenta por cobrar.
/// </summary>
public enum SaleStatus
{
    Draft = 0,
    Confirmed = 1,
    Cancelled = 2
}

/// <summary>
/// Venta a un cliente. Al confirmarse descarga el inventario de cada línea y genera la
/// cuenta por cobrar, aunque sea de contado: la deuda y su cobro se registran siempre
/// por separado para que el historial de cada factura sea reconstruible.
/// </summary>
public class Sale : TenantEntity
{
    [MaxLength(20)] public string Number { get; set; } = string.Empty;

    public int CustomerId { get; set; }
    public Customer Customer { get; set; } = null!;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public SaleStatus Status { get; set; } = SaleStatus.Draft;

    /// <summary>Condición con la que se cerró esta venta, que puede diferir de la del cliente.</summary>
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;
    /// <summary>Días de crédito pactados en esta venta. 0 en las de contado.</summary>
    public int CreditDays { get; set; }

    public decimal Subtotal { get; set; }
    public decimal DiscountAmount { get; set; }
    public decimal TaxAmount { get; set; }
    public decimal Total { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<SaleItem> Items { get; set; } = new List<SaleItem>();
}

public class SaleItem : BaseEntity
{
    public int SaleId { get; set; }
    public Sale Sale { get; set; } = null!;

    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    public decimal Quantity { get; set; }
    public decimal UnitPrice { get; set; }
    /// <summary>Descuento de la línea en porcentaje, sobre cantidad × precio.</summary>
    public decimal DiscountRate { get; set; }
    public decimal TaxRate { get; set; }

    /// <summary>Cantidad × precio, ya con el descuento aplicado y sin impuesto.</summary>
    public decimal Subtotal { get; set; }

    /// <summary>
    /// Costo unitario del producto al momento de vender. Se congela acá para poder
    /// calcular la utilidad de la venta aunque el costo del producto cambie después.
    /// </summary>
    public decimal UnitCost { get; set; }
}
