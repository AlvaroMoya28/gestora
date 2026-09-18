using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Sales;

// -------------------------------------------------------------------- Ventas ----

public record SaleItemDto(int Id, int ProductId, string ProductCode, string ProductName,
    string UnitAbbreviation, decimal Quantity, decimal UnitPrice, decimal DiscountRate,
    decimal TaxRate, decimal Subtotal);

public record SaleDto(int Id, string Number, int CustomerId, string CustomerName, DateTime Date,
    SaleStatus Status, string StatusName, PaymentTerm PaymentTerm, int CreditDays, DateTime DueDate,
    decimal Subtotal, decimal DiscountAmount, decimal TaxAmount, decimal Total, string? Notes,
    IReadOnlyList<SaleItemDto> Items);

public record SaleSummaryDto(int Id, string Number, string CustomerName, DateTime Date,
    DateTime DueDate, SaleStatus Status, string StatusName, PaymentTerm PaymentTerm, decimal Total);

public class SaleItemRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un producto.")]
    public int ProductId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal Quantity { get; set; }

    [Range(0, 999999999, ErrorMessage = "El precio no puede ser negativo.")]
    public decimal UnitPrice { get; set; }

    [Range(0, 100, ErrorMessage = "El descuento debe estar entre 0 y 100.")]
    public decimal DiscountRate { get; set; }

    [Range(0, 100, ErrorMessage = "El impuesto debe estar entre 0 y 100.")]
    public decimal TaxRate { get; set; }
}

public class SaleRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un cliente.")]
    public int CustomerId { get; set; }

    public DateTime? Date { get; set; }

    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;

    /// <summary>
    /// Días de plazo desde la fecha de la venta. La pantalla permite elegirlos como
    /// plazo (30 días, 2 meses…) o señalando directamente la fecha de vencimiento.
    /// </summary>
    [Range(0, 3650, ErrorMessage = "El plazo debe estar entre 0 y 3650 días.")]
    public int CreditDays { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    [MinLength(1, ErrorMessage = "Agregue al menos un producto a la venta.")]
    public List<SaleItemRequest> Items { get; set; } = [];
}

public class SaleQuery : QueryParams
{
    public int? CustomerId { get; set; }
    public SaleStatus? Status { get; set; }
    public DateTime? From { get; set; }
    public DateTime? To { get; set; }
}

// ----------------------------------------------------- Cuentas por cobrar ----

public record ReceiptDto(int Id, DateTime Date, decimal Amount, string Method, string? Reference,
    string? Notes, string? UserName);

public record AccountReceivableDto(int Id, string DocumentNumber, int CustomerId, string CustomerName,
    int? SaleId, string? SaleNumber, int? RepairOrderId, string? RepairNumber, DateTime IssueDate,
    DateTime DueDate, decimal Total, decimal CollectedAmount, decimal Balance, ReceivableStatus Status,
    string StatusName, bool IsOverdue, string? Notes, IReadOnlyList<ReceiptDto> Receipts);

public class ReceivableQuery : QueryParams
{
    public int? CustomerId { get; set; }
    public ReceivableStatus? Status { get; set; }
    /// <summary>Solo cuentas vencidas (DueDate en el pasado y con saldo).</summary>
    public bool? Overdue { get; set; }
}

public class RegisterReceiptRequest
{
    [Range(0.01, 999999999, ErrorMessage = "El monto debe ser mayor que cero.")]
    public decimal Amount { get; set; }

    public DateTime? Date { get; set; }

    [Required(ErrorMessage = "Debe indicar el medio de cobro.")]
    [MaxLength(40)] public string Method { get; set; } = string.Empty;

    [MaxLength(80)] public string? Reference { get; set; }
    [MaxLength(250)] public string? Notes { get; set; }
}
