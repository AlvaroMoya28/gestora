using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Purchasing;

// ------------------------------------------------------------------- Compras ----

public record PurchaseItemDto(int Id, int ProductId, string ProductCode, string ProductName,
    string UnitAbbreviation, decimal Quantity, decimal UnitCost, decimal TaxRate, decimal Subtotal);

public record PurchaseDto(int Id, string Number, int SupplierId, string SupplierName,
    DateTime Date, PurchaseStatus Status, string StatusName, decimal Subtotal, decimal TaxAmount,
    decimal Total, string? SupplierInvoiceNumber, string? Notes, IReadOnlyList<PurchaseItemDto> Items);

/// <summary>Fila de listado, sin las líneas: más liviana para la tabla.</summary>
public record PurchaseSummaryDto(int Id, string Number, string SupplierName, DateTime Date,
    PurchaseStatus Status, string StatusName, decimal Total);

public class PurchaseItemRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un producto.")]
    public int ProductId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal Quantity { get; set; }

    [Range(0, 999999999, ErrorMessage = "El costo no puede ser negativo.")]
    public decimal UnitCost { get; set; }

    [Range(0, 100, ErrorMessage = "El impuesto debe estar entre 0 y 100.")]
    public decimal TaxRate { get; set; }
}

public class PurchaseRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un proveedor.")]
    public int SupplierId { get; set; }

    public DateTime? Date { get; set; }

    [MaxLength(60)] public string? SupplierInvoiceNumber { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }

    [MinLength(1, ErrorMessage = "Agregue al menos un producto a la compra.")]
    public List<PurchaseItemRequest> Items { get; set; } = [];
}

public class PurchaseQuery : QueryParams
{
    public int? SupplierId { get; set; }
    public PurchaseStatus? Status { get; set; }
}

// ----------------------------------------------------------- Cuentas por pagar ----

public record PaymentDto(int Id, DateTime Date, decimal Amount, string Method, string? Reference,
    string? Notes, string? UserName);

public record AccountPayableDto(int Id, string DocumentNumber, int SupplierId, string SupplierName,
    int? PurchaseId, string? PurchaseNumber, DateTime IssueDate, DateTime DueDate, decimal Total,
    decimal PaidAmount, decimal Balance, PayableStatus Status, string StatusName, bool IsOverdue,
    string? Notes, IReadOnlyList<PaymentDto> Payments);

public class PayableQuery : QueryParams
{
    public int? SupplierId { get; set; }
    public PayableStatus? Status { get; set; }
    /// <summary>Solo cuentas vencidas (DueDate en el pasado y con saldo).</summary>
    public bool? Overdue { get; set; }
}

public class RegisterPaymentRequest
{
    [Range(0.01, 999999999, ErrorMessage = "El monto debe ser mayor que cero.")]
    public decimal Amount { get; set; }

    public DateTime? Date { get; set; }

    [Required(ErrorMessage = "Debe indicar el método de pago.")]
    [MaxLength(40)] public string Method { get; set; } = string.Empty;

    [MaxLength(80)] public string? Reference { get; set; }
    [MaxLength(250)] public string? Notes { get; set; }
}
