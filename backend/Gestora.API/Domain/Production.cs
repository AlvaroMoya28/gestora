using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

public enum ProductionStatus
{
    /// <summary>Planificada: se sabe qué se va a fabricar y con qué, pero nada se ha movido.</summary>
    Planned = 0,
    /// <summary>En proceso. El material sigue en inventario hasta que la orden se termina.</summary>
    InProgress = 1,
    /// <summary>Terminada: se consumió el material y entró el producto fabricado.</summary>
    Completed = 2,
    Cancelled = 3
}

/// <summary>
/// Receta (lista de materiales) de un producto fabricado. Define cuánto material lleva
/// producir una cantidad determinada, de modo que una orden de producción se arme sola
/// a partir de la cantidad que se quiere fabricar.
/// </summary>
public class Recipe : TenantEntity
{
    [MaxLength(120)] public string Name { get; set; } = string.Empty;

    /// <summary>Producto que resulta de la receta.</summary>
    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    /// <summary>Cantidad de producto que rinden las cantidades declaradas en las líneas.</summary>
    public decimal OutputQuantity { get; set; } = 1;

    /// <summary>Costo de mano de obra por cada <see cref="OutputQuantity"/> fabricada.</summary>
    public decimal LaborCost { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }
    public bool IsActive { get; set; } = true;

    public ICollection<RecipeItem> Items { get; set; } = new List<RecipeItem>();
}

public class RecipeItem : BaseEntity
{
    public int RecipeId { get; set; }
    public Recipe Recipe { get; set; } = null!;

    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    public decimal Quantity { get; set; }
}

/// <summary>
/// Orden de producción. Terminarla es el punto sin retorno: en una sola transacción sale
/// el material consumido y entra el producto fabricado, y el costo unitario resultante
/// (materiales + mano de obra ÷ cantidad) queda registrado en el producto.
/// </summary>
public class ProductionOrder : TenantEntity
{
    [MaxLength(20)] public string Number { get; set; } = string.Empty;

    /// <summary>Producto que se fabrica.</summary>
    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    /// <summary>Receta de la que se copiaron los materiales, si se usó una.</summary>
    public int? RecipeId { get; set; }
    public Recipe? Recipe { get; set; }

    /// <summary>Cantidad que se planea fabricar.</summary>
    public decimal Quantity { get; set; }
    /// <summary>Cantidad realmente obtenida. Se confirma al terminar la orden.</summary>
    public decimal ProducedQuantity { get; set; }

    public ProductionStatus Status { get; set; } = ProductionStatus.Planned;

    public DateTime Date { get; set; } = DateTime.UtcNow;
    public DateTime? StartedAt { get; set; }
    public DateTime? CompletedAt { get; set; }

    /// <summary>Mano de obra y costos indirectos de la orden completa.</summary>
    public decimal LaborCost { get; set; }
    /// <summary>Costo del material consumido. Se calcula al terminar.</summary>
    public decimal MaterialsCost { get; set; }
    /// <summary>Materiales + mano de obra.</summary>
    public decimal TotalCost { get; set; }
    /// <summary>TotalCost ÷ cantidad producida. Es el costo con el que entra el producto.</summary>
    public decimal UnitCost { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<ProductionMaterial> Materials { get; set; } = new List<ProductionMaterial>();
}

public class ProductionMaterial : BaseEntity
{
    public int ProductionOrderId { get; set; }
    public ProductionOrder ProductionOrder { get; set; } = null!;

    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    /// <summary>Lo que la receta o el planificador previeron consumir.</summary>
    public decimal PlannedQuantity { get; set; }
    /// <summary>Lo que realmente se consumió. Se confirma al terminar la orden.</summary>
    public decimal ConsumedQuantity { get; set; }
    /// <summary>Costo unitario del material al consumirlo.</summary>
    public decimal UnitCost { get; set; }
}
