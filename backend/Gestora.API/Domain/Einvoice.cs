using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

public enum ReceivedDocumentStatus
{
    /// <summary>Llegó y espera que alguien la revise.</summary>
    Pending = 0,
    /// <summary>Ya se convirtió en una compra.</summary>
    Imported = 1,
    /// <summary>Se descartó (no es de la empresa, duplicada, no aplica).</summary>
    Discarded = 2
}

public enum ReceivedDocumentSource
{
    Upload = 0,
    Email = 1
}

/// <summary>
/// Comprobante electrónico recibido de un proveedor. Se guarda el XML original tal como
/// llegó (es el documento con validez fiscal) junto con los datos ya leídos, para listar
/// y revisar sin volver a interpretarlo. La clave de 50 dígitos es única por empresa:
/// reenviar el mismo comprobante no lo duplica.
/// </summary>
public class ReceivedDocument : TenantEntity
{
    [MaxLength(50)] public string Key { get; set; } = string.Empty;
    [MaxLength(40)] public string DocumentType { get; set; } = string.Empty;
    [MaxLength(20)] public string Consecutive { get; set; } = string.Empty;

    [MaxLength(30)] public string IssuerTaxId { get; set; } = string.Empty;
    [MaxLength(150)] public string IssuerName { get; set; } = string.Empty;
    [MaxLength(150)] public string? IssuerEmail { get; set; }

    public DateTime IssueDate { get; set; }
    [MaxLength(3)] public string Currency { get; set; } = "CRC";
    public bool IsCredit { get; set; }
    public int CreditDays { get; set; }

    public decimal Subtotal { get; set; }
    public decimal TaxAmount { get; set; }
    public decimal Total { get; set; }

    public ReceivedDocumentSource Source { get; set; }
    [MaxLength(150)] public string? SenderEmail { get; set; }
    public DateTime ReceivedAt { get; set; } = DateTime.UtcNow;

    public ReceivedDocumentStatus Status { get; set; } = ReceivedDocumentStatus.Pending;

    public int? PurchaseId { get; set; }
    public Purchase? Purchase { get; set; }

    /// <summary>Líneas ya leídas, en JSON.</summary>
    public string LinesJson { get; set; } = "[]";

    /// <summary>XML original, sin modificar.</summary>
    public string Xml { get; set; } = string.Empty;
}
