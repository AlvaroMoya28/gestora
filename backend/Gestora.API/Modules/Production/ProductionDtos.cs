using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Production;

// ------------------------------------------------------------------ Recetas ----

public record RecipeItemDto(int Id, int ProductId, string ProductCode, string ProductName,
    string UnitAbbreviation, decimal Quantity, decimal UnitCost, decimal Subtotal, decimal Stock);

public record RecipeDto(int Id, string Name, int ProductId, string ProductCode, string ProductName,
    string UnitAbbreviation, decimal OutputQuantity, decimal LaborCost, decimal MaterialsCost,
    decimal UnitCost, string? Notes, bool IsActive, IReadOnlyList<RecipeItemDto> Items);

public class RecipeItemRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un material.")]
    public int ProductId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal Quantity { get; set; }
}

public class RecipeRequest
{
    [Required(ErrorMessage = "Debe indicar el nombre de la receta.")]
    [MaxLength(120)] public string Name { get; set; } = string.Empty;

    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar el producto que se fabrica.")]
    public int ProductId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "El rendimiento debe ser mayor que cero.")]
    public decimal OutputQuantity { get; set; } = 1;

    [Range(0, 999999999, ErrorMessage = "La mano de obra no puede ser negativa.")]
    public decimal LaborCost { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    [MinLength(1, ErrorMessage = "Agregue al menos un material a la receta.")]
    public List<RecipeItemRequest> Items { get; set; } = [];
}

// ---------------------------------------------------- Órdenes de producción ----

public record ProductionMaterialDto(int Id, int ProductId, string ProductCode, string ProductName,
    string UnitAbbreviation, decimal PlannedQuantity, decimal ConsumedQuantity, decimal UnitCost,
    decimal Stock, bool HasEnoughStock);

public record ProductionOrderDto(int Id, string Number, int ProductId, string ProductCode,
    string ProductName, string UnitAbbreviation, int? RecipeId, string? RecipeName, decimal Quantity,
    decimal ProducedQuantity, ProductionStatus Status, string StatusName, DateTime Date,
    DateTime? StartedAt, DateTime? CompletedAt, decimal LaborCost, decimal MaterialsCost,
    decimal TotalCost, decimal UnitCost, string? Notes, bool CanStart, bool CanComplete,
    IReadOnlyList<ProductionMaterialDto> Materials);

public record ProductionOrderSummaryDto(int Id, string Number, string ProductName, decimal Quantity,
    decimal ProducedQuantity, ProductionStatus Status, string StatusName, DateTime Date, decimal TotalCost);

public class ProductionMaterialRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un material.")]
    public int ProductId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal PlannedQuantity { get; set; }
}

public class ProductionOrderRequest
{
    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar el producto a fabricar.")]
    public int ProductId { get; set; }

    /// <summary>Si viene, los materiales se calculan de la receta y se ignora <see cref="Materials"/>.</summary>
    public int? RecipeId { get; set; }

    [Range(0.0001, 999999999, ErrorMessage = "La cantidad debe ser mayor que cero.")]
    public decimal Quantity { get; set; }

    public DateTime? Date { get; set; }

    [Range(0, 999999999, ErrorMessage = "La mano de obra no puede ser negativa.")]
    public decimal LaborCost { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    public List<ProductionMaterialRequest> Materials { get; set; } = [];
}

public class MaterialConsumptionRequest
{
    public int ProductId { get; set; }

    [Range(0, 999999999, ErrorMessage = "La cantidad consumida no puede ser negativa.")]
    public decimal ConsumedQuantity { get; set; }
}

/// <summary>
/// Cierre de la orden. Admite corregir lo que realmente se produjo y se consumió: en
/// taller casi nunca coincide al milímetro con lo planificado, y forzar el plan haría
/// que el inventario dejara de reflejar la realidad.
/// </summary>
public class CompleteProductionRequest
{
    [Range(0.0001, 999999999, ErrorMessage = "La cantidad producida debe ser mayor que cero.")]
    public decimal ProducedQuantity { get; set; }

    [Range(0, 999999999, ErrorMessage = "La mano de obra no puede ser negativa.")]
    public decimal? LaborCost { get; set; }

    /// <summary>Consumo real por material. Lo que no venga se consume según lo planificado.</summary>
    public List<MaterialConsumptionRequest> Materials { get; set; } = [];
}

public class ProductionQuery : QueryParams
{
    public ProductionStatus? Status { get; set; }
    public int? ProductId { get; set; }
}
