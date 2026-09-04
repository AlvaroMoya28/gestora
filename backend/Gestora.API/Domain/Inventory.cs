using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>Naturaleza del movimiento. El signo lo decide <see cref="InventoryMovement.Direction"/>.</summary>
public enum MovementType
{
    Purchase = 1,           // entrada por compra recibida
    Sale = 2,               // salida por venta
    ProductionInput = 3,    // salida de materia prima consumida
    ProductionOutput = 4,   // entrada de producto terminado
    Adjustment = 5,         // ajuste manual de inventario
    ReturnIn = 6,           // devolución de cliente
    ReturnOut = 7,          // devolución a proveedor
    RepairConsumption = 8,  // material usado en una reparación
    InitialStock = 9        // carga inicial
}

/// <summary>
/// Asiento de inventario. Es la única forma de alterar la existencia de un producto:
/// el saldo <c>Product.Stock</c> se recalcula a partir de estos registros y nunca se
/// edita a mano. Cada movimiento deja quién, cuándo y por qué documento se originó.
/// </summary>
public class InventoryMovement : TenantEntity
{
    public int ProductId { get; set; }
    public Product Product { get; set; } = null!;

    public MovementType Type { get; set; }
    /// <summary>+1 entrada, -1 salida. Redundante con el tipo, pero evita reglas dispersas.</summary>
    public int Direction { get; set; }
    /// <summary>Cantidad siempre positiva; el sentido lo aporta <see cref="Direction"/>.</summary>
    public decimal Quantity { get; set; }
    /// <summary>Existencia resultante después de aplicar el movimiento (para auditar).</summary>
    public decimal StockAfter { get; set; }
    /// <summary>Costo unitario del movimiento, cuando aplica (compras, producción).</summary>
    public decimal? UnitCost { get; set; }

    [MaxLength(250)] public string? Reason { get; set; }
    /// <summary>Documento que originó el movimiento: Purchase, Sale, ProductionOrder, Repair...</summary>
    [MaxLength(40)] public string? ReferenceType { get; set; }
    public int? ReferenceId { get; set; }

    public DateTime OccurredAt { get; set; } = DateTime.UtcNow;
    public int? UserId { get; set; }
    public User? User { get; set; }
}
