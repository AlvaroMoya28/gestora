using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Finance;

// ---------------------------------------------------------------- Categorías ----

public record FinanceCategoryDto(int Id, string Name, FinanceKind Kind, string KindName,
    string? Description, bool IsSystem, bool IsActive, int EntryCount);

public class FinanceCategoryRequest
{
    [Required(ErrorMessage = "Debe indicar el nombre de la categoría.")]
    [MaxLength(80)] public string Name { get; set; } = string.Empty;

    public FinanceKind Kind { get; set; }

    [MaxLength(250)] public string? Description { get; set; }
}

// -------------------------------------------------------------------- Asientos ----

public record FinanceEntryDto(int Id, FinanceKind Kind, string KindName, int CategoryId,
    string CategoryName, DateTime Date, decimal Amount, string Description, string Method,
    string? Reference, string? Notes, FinanceSource Source, string SourceName, int? SourceId,
    bool IsManual, string? UserName, DateTime CreatedAt);

public class FinanceEntryRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar una categoría.")]
    public int CategoryId { get; set; }

    public DateTime? Date { get; set; }

    [Range(0.01, 999999999, ErrorMessage = "El monto debe ser mayor que cero.")]
    public decimal Amount { get; set; }

    [Required(ErrorMessage = "Debe indicar una descripción.")]
    [MaxLength(200)] public string Description { get; set; } = string.Empty;

    [Required(ErrorMessage = "Debe indicar el medio de pago.")]
    [MaxLength(40)] public string Method { get; set; } = string.Empty;

    [MaxLength(80)] public string? Reference { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }
}

public class FinanceQuery : QueryParams
{
    public int? CategoryId { get; set; }
    public DateTime? From { get; set; }
    public DateTime? To { get; set; }
    /// <summary>true = solo los cargados a mano, false = solo los automáticos.</summary>
    public bool? Manual { get; set; }
}

/// <summary>Totales del período consultado, para la cabecera de ingresos y gastos.</summary>
public record FinanceSummaryDto(decimal Total, decimal MonthTotal, int Count,
    IReadOnlyList<FinanceCategoryTotalDto> ByCategory);

public record FinanceCategoryTotalDto(int CategoryId, string CategoryName, decimal Total, int Count);
