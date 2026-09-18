using System.Globalization;
using System.Text;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Reports;

/// <summary>
/// Reportes de la empresa.
///
/// Todos devuelven la misma estructura (<see cref="ReportDto"/>): unas cifras, una serie
/// y una tabla. Esa uniformidad es deliberada: permite que una sola pantalla dibuje
/// cualquier reporte y que agregar uno nuevo sea escribir su consulta acá.
///
/// Las agregaciones se hacen en memoria después de traer los datos del período. Para el
/// volumen de una pyme —meses, no años, de documentos— es más simple y predecible que
/// pelear con la traducción a SQL de cada agrupación, y el filtro multiempresa ya acotó
/// la consulta a los datos de una sola empresa.
/// </summary>
public class ReportService(GestoraDbContext db)
{
    public static readonly IReadOnlyList<ReportDefinitionDto> Available =
    [
        new("ventas", "Ventas por producto", "Qué se vendió, cuánto ingresó y qué utilidad dejó.", "Comercial", true),
        new("clientes", "Ventas por cliente", "Quién compra, cuánto y con qué frecuencia.", "Comercial", true),
        new("compras", "Compras por proveedor", "En qué y con quién se gastó en el período.", "Comercial", true),
        new("inventario", "Valoración de inventario", "Existencia actual valorada al costo.", "Operación", false),
        new("produccion", "Producción terminada", "Órdenes completadas y su costo real.", "Operación", true),
        new("cuentas-por-cobrar", "Antigüedad de cuentas por cobrar", "Cuánto se debe y desde hace cuánto.", "Finanzas", false),
        new("cuentas-por-pagar", "Antigüedad de cuentas por pagar", "Qué se debe y cuándo vence.", "Finanzas", false),
        new("finanzas", "Ingresos y gastos", "Flujo de dinero del período por categoría.", "Finanzas", true)
    ];

    public async Task<ReportDto> BuildAsync(string key, DateTime? from, DateTime? to)
    {
        var definition = Available.FirstOrDefault(r => r.Key == key)
            ?? throw ApiException.NotFound("El reporte");

        // Período por defecto: el mes en curso. El "hasta" incluye el día completo.
        var start = (from ?? new DateTime(DateTime.UtcNow.Year, DateTime.UtcNow.Month, 1,
            0, 0, 0, DateTimeKind.Utc)).Date;
        var end = (to ?? DateTime.UtcNow).Date.AddDays(1).AddTicks(-1);

        if (end < start) throw new ApiException("La fecha final no puede ser anterior a la inicial.");

        return key switch
        {
            "ventas" => await SalesByProductAsync(definition, start, end),
            "clientes" => await SalesByCustomerAsync(definition, start, end),
            "compras" => await PurchasesBySupplierAsync(definition, start, end),
            "inventario" => await InventoryValuationAsync(definition, start, end),
            "produccion" => await ProductionAsync(definition, start, end),
            "cuentas-por-cobrar" => await ReceivableAgingAsync(definition, start, end),
            "cuentas-por-pagar" => await PayableAgingAsync(definition, start, end),
            "finanzas" => await CashFlowAsync(definition, start, end),
            _ => throw ApiException.NotFound("El reporte")
        };
    }

    // ------------------------------------------------------------------- Ventas ----

    private async Task<ReportDto> SalesByProductAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var sales = await db.Sales.AsNoTracking()
            .Include(s => s.Items).ThenInclude(i => i.Product).ThenInclude(p => p.Unit)
            .Where(s => s.Status == SaleStatus.Confirmed && s.Date >= from && s.Date <= to)
            .ToListAsync();

        var lines = sales.SelectMany(s => s.Items).ToList();

        var rows = lines
            .GroupBy(i => new { i.ProductId, i.Product.Code, i.Product.Name, Unit = i.Product.Unit.Abbreviation })
            .Select(g => new
            {
                g.Key.Code,
                g.Key.Name,
                g.Key.Unit,
                Quantity = g.Sum(i => i.Quantity),
                Revenue = g.Sum(i => i.Subtotal),
                Cost = g.Sum(i => i.Quantity * i.UnitCost)
            })
            .OrderByDescending(r => r.Revenue)
            .Select(r => Row(
                ("code", r.Code), ("product", r.Name), ("unit", r.Unit),
                ("quantity", r.Quantity), ("revenue", r.Revenue), ("cost", r.Cost),
                ("profit", r.Revenue - r.Cost),
                ("margin", r.Revenue == 0 ? 0m : (r.Revenue - r.Cost) / r.Revenue * 100m)))
            .ToList();

        var revenue = lines.Sum(i => i.Subtotal);
        var cost = lines.Sum(i => i.Quantity * i.UnitCost);

        var metrics = new List<ReportMetricDto>
        {
            new("revenue", "Ventas del período", revenue, "money", "Sin impuesto"),
            new("count", "Facturas emitidas", sales.Count, "integer", null),
            new("average", "Venta promedio", sales.Count == 0 ? 0 : revenue / sales.Count, "money", null),
            new("profit", "Utilidad bruta", revenue - cost, "money",
                revenue == 0 ? null : $"Margen {(revenue - cost) / revenue * 100:N1} %")
        };

        var columns = new List<ReportColumnDto>
        {
            new("code", "Código", "text", "left"),
            new("product", "Producto", "text", "left"),
            new("unit", "Unidad", "text", "left"),
            new("quantity", "Cantidad", "decimal", "right"),
            new("revenue", "Ingreso", "money", "right"),
            new("cost", "Costo", "money", "right"),
            new("profit", "Utilidad", "money", "right"),
            new("margin", "Margen", "percent", "right")
        };

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            DailySeries(sales.Select(s => (s.Date, s.Total))), "Ventas por día");
    }

    private async Task<ReportDto> SalesByCustomerAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var sales = await db.Sales.AsNoTracking().Include(s => s.Customer)
            .Where(s => s.Status == SaleStatus.Confirmed && s.Date >= from && s.Date <= to)
            .ToListAsync();

        var grouped = sales
            .GroupBy(s => new { s.CustomerId, s.Customer.Code, s.Customer.Name })
            .Select(g => new
            {
                g.Key.Code,
                g.Key.Name,
                Count = g.Count(),
                Total = g.Sum(s => s.Total),
                Last = g.Max(s => s.Date)
            })
            .OrderByDescending(r => r.Total)
            .ToList();

        var rows = grouped.Select(r => Row(
            ("code", r.Code), ("customer", r.Name), ("count", r.Count),
            ("total", r.Total), ("average", r.Count == 0 ? 0m : r.Total / r.Count),
            ("last", r.Last))).ToList();

        var total = sales.Sum(s => s.Total);

        var metrics = new List<ReportMetricDto>
        {
            new("total", "Facturado", total, "money", "Con impuesto"),
            new("customers", "Clientes que compraron", grouped.Count, "integer", null),
            new("count", "Facturas", sales.Count, "integer", null),
            new("average", "Promedio por cliente", grouped.Count == 0 ? 0 : total / grouped.Count, "money", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("code", "Código", "text", "left"),
            new("customer", "Cliente", "text", "left"),
            new("count", "Facturas", "integer", "right"),
            new("total", "Total", "money", "right"),
            new("average", "Promedio", "money", "right"),
            new("last", "Última compra", "date", "left")
        };

        var series = grouped.Take(10).Select(r => new ReportPointDto(r.Name, r.Total)).ToList();

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            series, "Diez clientes con mayor facturación");
    }

    // ------------------------------------------------------------------ Compras ----

    private async Task<ReportDto> PurchasesBySupplierAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var purchases = await db.Purchases.AsNoTracking().Include(p => p.Supplier)
            .Where(p => p.Status == PurchaseStatus.Confirmed && p.Date >= from && p.Date <= to)
            .ToListAsync();

        var grouped = purchases
            .GroupBy(p => new { p.SupplierId, p.Supplier.Code, p.Supplier.Name })
            .Select(g => new
            {
                g.Key.Code,
                g.Key.Name,
                Count = g.Count(),
                Total = g.Sum(p => p.Total),
                Last = g.Max(p => p.Date)
            })
            .OrderByDescending(r => r.Total)
            .ToList();

        var rows = grouped.Select(r => Row(
            ("code", r.Code), ("supplier", r.Name), ("count", r.Count),
            ("total", r.Total), ("last", r.Last))).ToList();

        var total = purchases.Sum(p => p.Total);

        var metrics = new List<ReportMetricDto>
        {
            new("total", "Comprado", total, "money", "Con impuesto"),
            new("suppliers", "Proveedores", grouped.Count, "integer", null),
            new("count", "Compras", purchases.Count, "integer", null),
            new("average", "Compra promedio", purchases.Count == 0 ? 0 : total / purchases.Count, "money", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("code", "Código", "text", "left"),
            new("supplier", "Proveedor", "text", "left"),
            new("count", "Compras", "integer", "right"),
            new("total", "Total", "money", "right"),
            new("last", "Última compra", "date", "left")
        };

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            DailySeries(purchases.Select(p => (p.Date, p.Total))), "Compras por día");
    }

    // --------------------------------------------------------------- Inventario ----

    private async Task<ReportDto> InventoryValuationAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var products = await db.Products.AsNoTracking()
            .Include(p => p.Unit).Include(p => p.Category)
            .Where(p => p.IsActive && p.Type != ProductType.Service)
            .ToListAsync();

        var rows = products
            .OrderByDescending(p => p.Stock * p.Cost)
            .Select(p => Row(
                ("code", p.Code), ("product", p.Name),
                ("category", p.Category?.Name ?? "Sin categoría"),
                ("unit", p.Unit.Abbreviation), ("stock", p.Stock), ("minStock", p.MinStock),
                ("cost", p.Cost), ("value", p.Stock * p.Cost),
                ("status", p.Stock <= 0 ? "Agotado" : p.Stock <= p.MinStock ? "Bajo mínimo" : "Normal")))
            .ToList();

        var value = products.Sum(p => p.Stock * p.Cost);
        var below = products.Count(p => p.Stock <= p.MinStock && p.Stock > 0);
        var out_ = products.Count(p => p.Stock <= 0);

        var metrics = new List<ReportMetricDto>
        {
            new("value", "Valor del inventario", value, "money", "Existencia al costo"),
            new("products", "Productos", products.Count, "integer", null),
            new("below", "Bajo mínimo", below, "integer", null),
            new("out", "Agotados", out_, "integer", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("code", "Código", "text", "left"),
            new("product", "Producto", "text", "left"),
            new("category", "Categoría", "text", "left"),
            new("unit", "Unidad", "text", "left"),
            new("stock", "Existencia", "decimal", "right"),
            new("minStock", "Mínimo", "decimal", "right"),
            new("cost", "Costo", "money", "right"),
            new("value", "Valor", "money", "right"),
            new("status", "Estado", "text", "left")
        };

        var series = products
            .GroupBy(p => p.Category?.Name ?? "Sin categoría")
            .Select(g => new ReportPointDto(g.Key, g.Sum(p => p.Stock * p.Cost)))
            .Where(p => p.Value > 0)
            .OrderByDescending(p => p.Value)
            .ToList();

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            series, "Valor por categoría");
    }

    // --------------------------------------------------------------- Producción ----

    private async Task<ReportDto> ProductionAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var orders = await db.ProductionOrders.AsNoTracking()
            .Include(o => o.Product).ThenInclude(p => p.Unit)
            .Where(o => o.Status == ProductionStatus.Completed
                && o.CompletedAt != null && o.CompletedAt >= from && o.CompletedAt <= to)
            .ToListAsync();

        var rows = orders
            .OrderByDescending(o => o.CompletedAt)
            .Select(o => Row(
                ("number", o.Number), ("product", o.Product.Name),
                ("unit", o.Product.Unit.Abbreviation),
                ("planned", o.Quantity), ("produced", o.ProducedQuantity),
                ("materials", o.MaterialsCost), ("labor", o.LaborCost),
                ("total", o.TotalCost), ("unitCost", o.UnitCost),
                ("completed", o.CompletedAt)))
            .ToList();

        var produced = orders.Sum(o => o.ProducedQuantity);
        var cost = orders.Sum(o => o.TotalCost);

        var metrics = new List<ReportMetricDto>
        {
            new("orders", "Órdenes terminadas", orders.Count, "integer", null),
            new("produced", "Unidades producidas", produced, "decimal", null),
            new("cost", "Costo de producción", cost, "money", "Materiales y mano de obra"),
            new("materials", "Materiales consumidos", orders.Sum(o => o.MaterialsCost), "money", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("number", "Orden", "text", "left"),
            new("product", "Producto", "text", "left"),
            new("unit", "Unidad", "text", "left"),
            new("planned", "Planificado", "decimal", "right"),
            new("produced", "Producido", "decimal", "right"),
            new("materials", "Materiales", "money", "right"),
            new("labor", "Mano de obra", "money", "right"),
            new("total", "Costo total", "money", "right"),
            new("unitCost", "Costo unitario", "money", "right"),
            new("completed", "Terminada", "date", "left")
        };

        var series = orders
            .GroupBy(o => o.Product.Name)
            .Select(g => new ReportPointDto(g.Key, g.Sum(o => o.ProducedQuantity)))
            .OrderByDescending(p => p.Value)
            .Take(10)
            .ToList();

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            series, "Unidades producidas por producto");
    }

    // ----------------------------------------------------- Antigüedad de saldos ----

    /// <summary>
    /// Tramos de antigüedad. Separar "por vencer" de los tres tramos vencidos es lo que
    /// convierte una lista de saldos en una herramienta de cobro: no es lo mismo deber
    /// desde hace una semana que desde hace tres meses.
    /// </summary>
    private static string BucketOf(DateTime dueDate)
    {
        var days = (DateTime.UtcNow.Date - dueDate.Date).Days;
        return days switch
        {
            <= 0 => "Por vencer",
            <= 30 => "1 a 30 días",
            <= 60 => "31 a 60 días",
            <= 90 => "61 a 90 días",
            _ => "Más de 90 días"
        };
    }

    private static readonly string[] BucketOrder =
        ["Por vencer", "1 a 30 días", "31 a 60 días", "61 a 90 días", "Más de 90 días"];

    private async Task<ReportDto> ReceivableAgingAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var accounts = await db.AccountsReceivable.AsNoTracking().Include(a => a.Customer)
            .Where(a => a.Balance > 0 && a.Status != ReceivableStatus.Cancelled)
            .ToListAsync();

        var rows = accounts
            .OrderBy(a => a.DueDate)
            .Select(a => Row(
                ("document", a.DocumentNumber), ("customer", a.Customer.Name),
                ("issue", a.IssueDate), ("due", a.DueDate),
                ("days", Math.Max((DateTime.UtcNow.Date - a.DueDate.Date).Days, 0)),
                ("total", a.Total), ("collected", a.CollectedAmount), ("balance", a.Balance),
                ("bucket", BucketOf(a.DueDate))))
            .ToList();

        var balance = accounts.Sum(a => a.Balance);
        var overdue = accounts.Where(a => a.DueDate.Date < DateTime.UtcNow.Date).Sum(a => a.Balance);

        var metrics = new List<ReportMetricDto>
        {
            new("balance", "Saldo por cobrar", balance, "money", null),
            new("overdue", "Vencido", overdue, "money",
                balance == 0 ? null : $"{overdue / balance * 100:N1} % del total"),
            new("documents", "Documentos", accounts.Count, "integer", null),
            new("customers", "Clientes con saldo", accounts.Select(a => a.CustomerId).Distinct().Count(),
                "integer", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("document", "Documento", "text", "left"),
            new("customer", "Cliente", "text", "left"),
            new("issue", "Emisión", "date", "left"),
            new("due", "Vence", "date", "left"),
            new("days", "Días vencido", "integer", "right"),
            new("total", "Total", "money", "right"),
            new("collected", "Cobrado", "money", "right"),
            new("balance", "Saldo", "money", "right"),
            new("bucket", "Tramo", "text", "left")
        };

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            BucketSeries(accounts.Select(a => (a.DueDate, a.Balance))), "Saldo por tramo de antigüedad");
    }

    private async Task<ReportDto> PayableAgingAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var accounts = await db.AccountsPayable.AsNoTracking().Include(a => a.Supplier)
            .Where(a => a.Balance > 0 && a.Status != PayableStatus.Cancelled)
            .ToListAsync();

        var rows = accounts
            .OrderBy(a => a.DueDate)
            .Select(a => Row(
                ("document", a.DocumentNumber), ("supplier", a.Supplier.Name),
                ("issue", a.IssueDate), ("due", a.DueDate),
                ("days", Math.Max((DateTime.UtcNow.Date - a.DueDate.Date).Days, 0)),
                ("total", a.Total), ("paid", a.PaidAmount), ("balance", a.Balance),
                ("bucket", BucketOf(a.DueDate))))
            .ToList();

        var balance = accounts.Sum(a => a.Balance);
        var overdue = accounts.Where(a => a.DueDate.Date < DateTime.UtcNow.Date).Sum(a => a.Balance);

        var metrics = new List<ReportMetricDto>
        {
            new("balance", "Saldo por pagar", balance, "money", null),
            new("overdue", "Vencido", overdue, "money",
                balance == 0 ? null : $"{overdue / balance * 100:N1} % del total"),
            new("documents", "Documentos", accounts.Count, "integer", null),
            new("suppliers", "Proveedores con saldo", accounts.Select(a => a.SupplierId).Distinct().Count(),
                "integer", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("document", "Documento", "text", "left"),
            new("supplier", "Proveedor", "text", "left"),
            new("issue", "Emisión", "date", "left"),
            new("due", "Vence", "date", "left"),
            new("days", "Días vencido", "integer", "right"),
            new("total", "Total", "money", "right"),
            new("paid", "Pagado", "money", "right"),
            new("balance", "Saldo", "money", "right"),
            new("bucket", "Tramo", "text", "left")
        };

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            BucketSeries(accounts.Select(a => (a.DueDate, a.Balance))), "Saldo por tramo de antigüedad");
    }

    // ------------------------------------------------------ Ingresos y gastos ----

    private async Task<ReportDto> CashFlowAsync(ReportDefinitionDto d, DateTime from, DateTime to)
    {
        var entries = await db.FinanceEntries.AsNoTracking().Include(e => e.Category)
            .Where(e => e.Date >= from && e.Date <= to)
            .ToListAsync();

        var rows = entries
            .GroupBy(e => new { e.Kind, Category = e.Category.Name })
            .Select(g => new
            {
                g.Key.Kind,
                g.Key.Category,
                Count = g.Count(),
                Total = g.Sum(e => e.Amount)
            })
            .OrderBy(r => r.Kind).ThenByDescending(r => r.Total)
            .Select(r => Row(
                ("kind", r.Kind == FinanceKind.Income ? "Ingreso" : "Gasto"),
                ("category", r.Category), ("count", r.Count), ("total", r.Total)))
            .ToList();

        var income = entries.Where(e => e.Kind == FinanceKind.Income).Sum(e => e.Amount);
        var expense = entries.Where(e => e.Kind == FinanceKind.Expense).Sum(e => e.Amount);

        var metrics = new List<ReportMetricDto>
        {
            new("income", "Ingresos", income, "money", null),
            new("expense", "Gastos", expense, "money", null),
            new("result", "Resultado", income - expense, "money",
                income - expense >= 0 ? "Superávit del período" : "Déficit del período"),
            new("movements", "Movimientos", entries.Count, "integer", null)
        };

        var columns = new List<ReportColumnDto>
        {
            new("kind", "Tipo", "text", "left"),
            new("category", "Categoría", "text", "left"),
            new("count", "Movimientos", "integer", "right"),
            new("total", "Total", "money", "right")
        };

        // La serie compara mes a mes lo que entró contra lo que salió.
        var series = entries
            .GroupBy(e => new DateTime(e.Date.Year, e.Date.Month, 1))
            .OrderBy(g => g.Key)
            .Select(g => new ReportPointDto(
                g.Key.ToString("MMM yyyy", new CultureInfo("es-CR")),
                g.Where(e => e.Kind == FinanceKind.Income).Sum(e => e.Amount)
                    - g.Where(e => e.Kind == FinanceKind.Expense).Sum(e => e.Amount)))
            .ToList();

        return new ReportDto(d.Key, d.Name, d.Description, from, to, d.NeedsPeriod, metrics, columns, rows,
            series, "Resultado por mes");
    }

    // -------------------------------------------------------------------- Apoyo ----

    private static Dictionary<string, object?> Row(params (string Key, object? Value)[] cells)
        => cells.ToDictionary(c => c.Key, c => c.Value);

    private static IReadOnlyList<ReportPointDto> DailySeries(IEnumerable<(DateTime Date, decimal Value)> source)
        => source
            .GroupBy(x => x.Date.Date)
            .OrderBy(g => g.Key)
            .Select(g => new ReportPointDto(g.Key.ToString("dd MMM", new CultureInfo("es-CR")),
                g.Sum(x => x.Value)))
            .ToList();

    private static IReadOnlyList<ReportPointDto> BucketSeries(IEnumerable<(DateTime Due, decimal Balance)> source)
    {
        var totals = source
            .GroupBy(x => BucketOf(x.Due))
            .ToDictionary(g => g.Key, g => g.Sum(x => x.Balance));

        return BucketOrder
            .Where(totals.ContainsKey)
            .Select(b => new ReportPointDto(b, totals[b]))
            .ToList();
    }

    /// <summary>
    /// Exporta el reporte a CSV con separador de punto y coma y BOM: es lo que abre
    /// Excel en español sin pedir nada, que es donde estos archivos terminan.
    /// </summary>
    public static byte[] ToCsv(ReportDto report)
    {
        var sb = new StringBuilder();
        var culture = new CultureInfo("es-CR");

        sb.AppendLine(Escape(report.Title));
        if (report.NeedsPeriod)
            sb.AppendLine(Escape($"Período: {report.From:dd/MM/yyyy} al {report.To:dd/MM/yyyy}"));
        sb.AppendLine();

        foreach (var metric in report.Metrics)
            sb.AppendLine($"{Escape(metric.Label)};{Escape(metric.Value.ToString("N2", culture))}");
        sb.AppendLine();

        sb.AppendLine(string.Join(';', report.Columns.Select(c => Escape(c.Label))));

        foreach (var row in report.Rows)
        {
            var cells = report.Columns.Select(column =>
            {
                row.TryGetValue(column.Key, out var value);
                return Escape(value switch
                {
                    null => string.Empty,
                    DateTime date => date.ToString("dd/MM/yyyy", culture),
                    decimal number => number.ToString("N2", culture),
                    _ => value.ToString() ?? string.Empty
                });
            });
            sb.AppendLine(string.Join(';', cells));
        }

        return Encoding.UTF8.GetPreamble().Concat(Encoding.UTF8.GetBytes(sb.ToString())).ToArray();
    }

    private static string Escape(string value) =>
        value.Contains(';') || value.Contains('"') || value.Contains('\n')
            ? $"\"{value.Replace("\"", "\"\"")}\""
            : value;
}
