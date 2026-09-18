using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Repairs;

public record RepairMaterialDto(int Id, int ProductId, string ProductCode, string ProductName,
    string UnitAbbreviation, decimal Quantity, decimal UnitPrice, decimal Subtotal, decimal Stock);

public record RepairOrderDto(int Id, string Number, int CustomerId, string CustomerName,
    string ItemDescription, string? ReportedIssue, string? Diagnosis, RepairStatus Status,
    string StatusName, DateTime ReceivedAt, DateTime? PromisedAt, DateTime? CompletedAt,
    DateTime? DeliveredAt, decimal LaborCost, decimal MaterialsCost, decimal TaxRate,
    decimal TaxAmount, decimal Total, PaymentTerm PaymentTerm, int CreditDays, string? Notes,
    bool IsLate, IReadOnlyList<RepairMaterialDto> Materials);

public record RepairOrderSummaryDto(int Id, string Number, string CustomerName, string ItemDescription,
    RepairStatus Status, string StatusName, DateTime ReceivedAt, DateTime? PromisedAt, decimal Total,
    bool IsLate);

public class RepairMaterialRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un material.")]
    public int ProductId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal Quantity { get; set; }

    [Range(0, 999999999, ErrorMessage = "El precio no puede ser negativo.")]
    public decimal UnitPrice { get; set; }
}

public class RepairOrderRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un cliente.")]
    public int CustomerId { get; set; }

    [Required(ErrorMessage = "Describa el artículo que se recibe.")]
    [MaxLength(200)] public string ItemDescription { get; set; } = string.Empty;

    [MaxLength(1000)] public string? ReportedIssue { get; set; }
    [MaxLength(1000)] public string? Diagnosis { get; set; }

    public DateTime? ReceivedAt { get; set; }
    public DateTime? PromisedAt { get; set; }

    [Range(0, 999999999, ErrorMessage = "La mano de obra no puede ser negativa.")]
    public decimal LaborCost { get; set; }

    [Range(0, 100, ErrorMessage = "El impuesto debe estar entre 0 y 100.")]
    public decimal TaxRate { get; set; }

    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;

    [Range(0, 365, ErrorMessage = "Los días de crédito deben estar entre 0 y 365.")]
    public int CreditDays { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    public List<RepairMaterialRequest> Materials { get; set; } = [];
}

/// <summary>Cierre del trabajo: acá se puede dejar el diagnóstico final y ajustar la mano de obra.</summary>
public class CompleteRepairRequest
{
    [MaxLength(1000)] public string? Diagnosis { get; set; }

    [Range(0, 999999999, ErrorMessage = "La mano de obra no puede ser negativa.")]
    public decimal? LaborCost { get; set; }
}

public class RepairQuery : QueryParams
{
    public int? CustomerId { get; set; }
    public RepairStatus? Status { get; set; }
    /// <summary>Solo las que pasaron su fecha prometida sin entregarse.</summary>
    public bool? Late { get; set; }
}
