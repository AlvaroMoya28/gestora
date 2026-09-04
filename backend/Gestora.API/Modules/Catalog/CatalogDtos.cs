using System.ComponentModel.DataAnnotations;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Catalog;

// ---------------------------------------------------------------- Clientes ----

public record CustomerDto(int Id, string Code, string Name, string? TradeName, string? TaxId,
    string? Phone, string? Email, string? Address, string? ContactName,
    PaymentTerm PaymentTerm, int CreditDays, decimal CreditLimit, string? Notes, bool IsActive);

public class CustomerRequest
{
    [MaxLength(20)] public string? Code { get; set; }

    [Required(ErrorMessage = "El nombre es obligatorio.")]
    [MaxLength(150)] public string Name { get; set; } = string.Empty;

    [MaxLength(150)] public string? TradeName { get; set; }
    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }

    [EmailAddress(ErrorMessage = "El correo no tiene un formato válido.")]
    [MaxLength(150)] public string? Email { get; set; }

    [MaxLength(250)] public string? Address { get; set; }
    [MaxLength(120)] public string? ContactName { get; set; }
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;

    [Range(0, 365, ErrorMessage = "Los días de crédito deben estar entre 0 y 365.")]
    public int CreditDays { get; set; }

    [Range(0, 999999999, ErrorMessage = "El límite de crédito no puede ser negativo.")]
    public decimal CreditLimit { get; set; }

    [MaxLength(500)] public string? Notes { get; set; }
}

// ------------------------------------------------------------ Proveedores ----

public record SupplierDto(int Id, string Code, string Name, string? TradeName, string? TaxId,
    string? Phone, string? Email, string? Address, string? ContactName,
    PaymentTerm PaymentTerm, int CreditDays, string? Notes, bool IsActive);

public class SupplierRequest
{
    [MaxLength(20)] public string? Code { get; set; }

    [Required(ErrorMessage = "El nombre es obligatorio.")]
    [MaxLength(150)] public string Name { get; set; } = string.Empty;

    [MaxLength(150)] public string? TradeName { get; set; }
    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }

    [EmailAddress(ErrorMessage = "El correo no tiene un formato válido.")]
    [MaxLength(150)] public string? Email { get; set; }

    [MaxLength(250)] public string? Address { get; set; }
    [MaxLength(120)] public string? ContactName { get; set; }
    public PaymentTerm PaymentTerm { get; set; } = PaymentTerm.Cash;

    [Range(0, 365)] public int CreditDays { get; set; }
    [MaxLength(500)] public string? Notes { get; set; }
}

// --------------------------------------------------------------- Productos ----

public record ProductDto(int Id, string Code, string Name, string? Description, string? Barcode,
    ProductType Type, int? CategoryId, string? CategoryName, int UnitId, string UnitName,
    string UnitAbbreviation, decimal Cost, decimal Price, decimal TaxRate,
    decimal Stock, decimal MinStock, bool IsActive)
{
    /// <summary>Estado de existencias calculado, para pintar el semáforo en la tabla.</summary>
    public string StockStatus => Stock <= 0 ? "agotado" : Stock <= MinStock ? "bajo" : "normal";
}

public class ProductRequest
{
    [MaxLength(30)] public string? Code { get; set; }

    [Required(ErrorMessage = "El nombre es obligatorio.")]
    [MaxLength(150)] public string Name { get; set; } = string.Empty;

    [MaxLength(500)] public string? Description { get; set; }
    [MaxLength(60)] public string? Barcode { get; set; }
    public ProductType Type { get; set; } = ProductType.FinishedGood;

    public int? CategoryId { get; set; }

    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar una unidad de medida.")]
    public int UnitId { get; set; }

    [Range(0, 999999999, ErrorMessage = "El costo no puede ser negativo.")]
    public decimal Cost { get; set; }

    [Range(0, 999999999, ErrorMessage = "El precio no puede ser negativo.")]
    public decimal Price { get; set; }

    [Range(0, 100, ErrorMessage = "El impuesto debe estar entre 0 y 100.")]
    public decimal TaxRate { get; set; }

    [Range(0, 999999999, ErrorMessage = "El mínimo no puede ser negativo.")]
    public decimal MinStock { get; set; }

    /// <summary>Existencia inicial. Solo se toma en cuenta al crear; genera un movimiento.</summary>
    [Range(0, 999999999)] public decimal InitialStock { get; set; }
}

// ------------------------------------------------- Categorías y unidades ----

public record CategoryDto(int Id, string Name, string? Description, bool IsActive, int ProductCount);

public class CategoryRequest
{
    [Required(ErrorMessage = "El nombre es obligatorio.")]
    [MaxLength(80)] public string Name { get; set; } = string.Empty;
    [MaxLength(250)] public string? Description { get; set; }
}

public record UnitDto(int Id, string Name, string Abbreviation, int DecimalPlaces, bool IsActive, int ProductCount);

public class UnitRequest
{
    [Required(ErrorMessage = "El nombre es obligatorio.")]
    [MaxLength(40)] public string Name { get; set; } = string.Empty;

    [Required(ErrorMessage = "La abreviatura es obligatoria.")]
    [MaxLength(10)] public string Abbreviation { get; set; } = string.Empty;

    [Range(0, 4, ErrorMessage = "Los decimales deben estar entre 0 y 4.")]
    public int DecimalPlaces { get; set; }
}
