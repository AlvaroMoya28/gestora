using Gestora.API.Domain;

namespace Gestora.API.Data;

/// <summary>
/// Con qué arranca una empresa nueva: unidades de medida, categorías de producto y
/// categorías de caja.
///
/// Vive en un solo lugar porque hay dos caminos por los que nace una empresa —el alta
/// desde la plataforma y la empresa de ejemplo del primer arranque— y ambos deben
/// dejarla igual de lista para operar. Cuando estaba duplicado, agregar algo acá
/// significaba que las empresas creadas por el otro camino no lo recibían.
///
/// Solo agrega al contexto; guardar es responsabilidad de quien llama, para que el
/// alta completa de un cliente siga siendo una sola transacción.
/// </summary>
public static class CompanyCatalogSeed
{
    public static void AddAll(GestoraDbContext db, int companyId)
    {
        AddUnits(db, companyId);
        AddProductCategories(db, companyId);
        AddFinanceCategories(db, companyId);
    }

    public static void AddUnits(GestoraDbContext db, int companyId) =>
        db.Units.AddRange(
            new Unit { CompanyId = companyId, Name = "Unidad", Abbreviation = "ud", DecimalPlaces = 0 },
            new Unit { CompanyId = companyId, Name = "Pieza", Abbreviation = "pz", DecimalPlaces = 0 },
            new Unit { CompanyId = companyId, Name = "Metro", Abbreviation = "m", DecimalPlaces = 2 },
            new Unit { CompanyId = companyId, Name = "Metro cuadrado", Abbreviation = "m2", DecimalPlaces = 2 },
            new Unit { CompanyId = companyId, Name = "Litro", Abbreviation = "L", DecimalPlaces = 2 },
            new Unit { CompanyId = companyId, Name = "Kilogramo", Abbreviation = "kg", DecimalPlaces = 3 },
            new Unit { CompanyId = companyId, Name = "Caja", Abbreviation = "cja", DecimalPlaces = 0 },
            new Unit { CompanyId = companyId, Name = "Hora", Abbreviation = "h", DecimalPlaces = 2 });

    public static void AddProductCategories(GestoraDbContext db, int companyId) =>
        db.Categories.AddRange(
            new Category { CompanyId = companyId, Name = "Producto terminado", Description = "Artículos listos para la venta." },
            new Category { CompanyId = companyId, Name = "Materia prima", Description = "Insumos que se transforman en producción." },
            new Category { CompanyId = companyId, Name = "Insumos y consumibles", Description = "Material de apoyo y consumo." },
            new Category { CompanyId = companyId, Name = "Servicios", Description = "Servicios facturables." });

    /// <summary>
    /// Categorías de caja. Las dos del sistema respaldan los asientos automáticos que
    /// generan los cobros y los pagos; el resto son un punto de partida que cada
    /// empresa edita a su gusto desde configuración.
    /// </summary>
    public static void AddFinanceCategories(GestoraDbContext db, int companyId) =>
        db.FinanceCategories.AddRange(
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Income, Name = "Cobros a clientes", IsSystem = true, Description = "Generada por los cobros registrados en cuentas por cobrar." },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Income, Name = "Otros ingresos", Description = "Ingresos que no provienen de una venta." },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Pagos a proveedores", IsSystem = true, Description = "Generada por los pagos registrados en cuentas por pagar." },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Salarios y cargas sociales" },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Alquiler" },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Servicios públicos", Description = "Luz, agua, internet, teléfono." },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Transporte y combustible" },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Mantenimiento y reparaciones" },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Impuestos y patentes" },
            new FinanceCategory { CompanyId = companyId, Kind = FinanceKind.Expense, Name = "Otros gastos" });
}
