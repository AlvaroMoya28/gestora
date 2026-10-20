using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Einvoice;

public record ReceivedLineDto(int Number, string Code, string Description, decimal Quantity,
    decimal UnitCost, decimal Subtotal, decimal TaxRate, int? SuggestedProductId, string? SuggestedProductName);

public record ReceivedDocumentDto(int Id, string Key, string DocumentType, string TypeName, string Consecutive,
    string IssuerTaxId, string IssuerName, DateTime IssueDate, string Currency, bool IsCredit, int CreditDays,
    decimal Subtotal, decimal TaxAmount, decimal Total, string SourceName, string? SenderEmail,
    DateTime ReceivedAt, ReceivedDocumentStatus Status, string StatusName, int? PurchaseId, string? PurchaseNumber,
    string? SupplierName, IReadOnlyList<ReceivedLineDto> Lines);

public class ReceivedDocumentQuery : QueryParams
{
    public ReceivedDocumentStatus? Status { get; set; }
}

/// <summary>Producto elegido para una línea. Sin producto, se crea uno nuevo con la descripción.</summary>
public class ImportLineRequest
{
    public int Number { get; set; }
    public int? ProductId { get; set; }
}

public class ImportRequest
{
    public List<ImportLineRequest> Lines { get; set; } = new();
    /// <summary>Confirmar la compra enseguida (entra inventario y nace la cuenta por pagar).</summary>
    public bool Confirm { get; set; }
}

public record UploadResultDto(string FileName, bool Ok, bool Duplicate, string Message, int? Id);

public record InboundAddressDto(string? Address, bool Configured);
