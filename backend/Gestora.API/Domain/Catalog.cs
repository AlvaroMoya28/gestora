using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>Condición comercial pactada con un cliente o proveedor.</summary>
public enum PaymentTerm
{
    Cash = 0,
    Credit = 1
}

/// <summary>
/// Un producto es materia prima o producto terminado. La distinción es lo que
/// permite que producción consuma unos y genere otros sin tablas separadas.
/// </summary>
public enum ProductType
{
    RawMaterial = 0,
    FinishedGood = 1,
    Service = 2
}

public class Category : TenantEntity
{
    [MaxLength(80)] public string Name { get; set; } = string.Empty;
    [MaxLength(250)] public string? Description { get; set; }
    public bool IsActive { get; set; } = true;
}

/// <summary>Unidad de medida configurable (unidad, metro, litro, kg, tabla...).</summary>
public class Unit : TenantEntity
{
    [MaxLength(40)] public string Name { get; set; } = string.Empty;
    [MaxLength(10)] public string Abbreviation { get; set; } = string.Empty;
    /// <summary>Decimales admitidos en las cantidades. 0 para piezas enteras.</summary>
    public int DecimalPlaces { get; set; }
    public bool IsActive { get; set; } = true;
}

public class Customer : TenantEntity
{
    [MaxLength(20)] public string Code { get; set; } = string.Empty;
    [MaxLength(150)] public string Name { get; set; } = string.Empty;
    [MaxLength(150)] public string? TradeName { get; set; }
    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }
    [MaxLength(150)] public string? Email { get; set; }
    [MaxLength(250)] public string? Address { get; set; }
    [MaxLength(120)] public string? ContactName { get; set; }
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;
    public int CreditDays { get; set; }
    /// <summary>Límite de crédito. 0 = sin límite definido.</summary>
    public decimal CreditLimit { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }
    public bool IsActive { get; set; } = true;
}

public class Supplier : TenantEntity
{
    [MaxLength(20)] public string Code { get; set; } = string.Empty;
    [MaxLength(150)] public string Name { get; set; } = string.Empty;
    [MaxLength(150)] public string? TradeName { get; set; }
    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }
    [MaxLength(150)] public string? Email { get; set; }
    [MaxLength(250)] public string? Address { get; set; }
    [MaxLength(120)] public string? ContactName { get; set; }
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;
    public int CreditDays { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }
    public bool IsActive { get; set; } = true;
}

public class Product : TenantEntity
{
    [MaxLength(30)] public string Code { get; set; } = string.Empty;
    [MaxLength(150)] public string Name { get; set; } = string.Empty;
    [MaxLength(500)] public string? Description { get; set; }
    [MaxLength(60)] public string? Barcode { get; set; }
    public ProductType Type { get; set; } = ProductType.FinishedGood;

    public int? CategoryId { get; set; }
    public Category? Category { get; set; }
    public int UnitId { get; set; }
    public Unit Unit { get; set; } = null!;

    /// <summary>Costo unitario de referencia.</summary>
    public decimal Cost { get; set; }
    /// <summary>Precio de venta sin impuesto.</summary>
    public decimal Price { get; set; }
    public decimal TaxRate { get; set; }

    /// <summary>
    /// Existencia actual. Es un saldo derivado: solo lo escribe
    /// <c>InventoryService</c> al aplicar un movimiento dentro de una transacción.
    /// Ningún otro punto del sistema debe asignar esta columna.
    /// </summary>
    public decimal Stock { get; set; }
    public decimal MinStock { get; set; }

    public bool IsActive { get; set; } = true;
}
