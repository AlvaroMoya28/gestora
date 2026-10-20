using System.Globalization;
using System.Xml;
using System.Xml.Linq;
using Gestora.API.Common;

namespace Gestora.API.Modules.Einvoice;

public record ParsedLine(int Number, string Code, string Description, decimal Quantity,
    decimal UnitCost, decimal Subtotal, decimal TaxRate, decimal TaxAmount);

public record ParsedDocument(string Key, string DocumentType, string Consecutive, string IssuerTaxId,
    string IssuerName, string? IssuerEmail, DateTime IssueDate, string Currency, bool IsCredit,
    int CreditDays, decimal Subtotal, decimal TaxAmount, decimal Total, List<ParsedLine> Lines);

/// <summary>
/// Lee los comprobantes electrónicos de Hacienda (factura, tiquete, notas). Ignora el
/// namespace a propósito: las versiones 4.3 y 4.4 lo cambian y la estructura que importa
/// es la misma, así que un solo lector sirve para ambas.
/// </summary>
public static class InvoiceXmlParser
{
    private static readonly HashSet<string> Supported = new()
    {
        "FacturaElectronica", "TiqueteElectronico", "NotaCreditoElectronica",
        "NotaDebitoElectronica", "FacturaElectronicaCompra", "FacturaElectronicaExportacion"
    };

    public static ParsedDocument Parse(string xml)
    {
        XDocument doc;
        try
        {
            // Sin DTD ni resolutor externo: un XML subido por terceros no debe poder
            // leer archivos ni disparar expansiones de entidades.
            var settings = new XmlReaderSettings { DtdProcessing = DtdProcessing.Prohibit, XmlResolver = null };
            using var reader = XmlReader.Create(new StringReader(xml), settings);
            doc = XDocument.Load(reader);
        }
        catch (XmlException)
        {
            throw new ApiException("El archivo no es un XML válido.");
        }

        var root = doc.Root!;
        var type = root.Name.LocalName;
        if (!Supported.Contains(type))
            throw new ApiException(type == "MensajeReceptor"
                ? "Ese XML es un mensaje de aceptación, no un comprobante."
                : $"Tipo de documento no soportado: {type}.");

        var key = Text(root, "Clave");
        if (key.Length != 50) throw new ApiException("El comprobante no trae una clave válida de 50 dígitos.");

        var issuer = root.Elements().FirstOrDefault(e => e.Name.LocalName == "Emisor")
            ?? throw new ApiException("El comprobante no trae datos del emisor.");
        var summary = root.Elements().FirstOrDefault(e => e.Name.LocalName == "ResumenFactura");

        var lines = new List<ParsedLine>();
        var detail = root.Elements().FirstOrDefault(e => e.Name.LocalName == "DetalleServicio");
        foreach (var line in detail?.Elements().Where(e => e.Name.LocalName == "LineaDetalle") ?? [])
        {
            var quantity = Num(line, "Cantidad");
            var subtotal = line.Elements().Any(e => e.Name.LocalName == "SubTotal")
                ? Num(line, "SubTotal") : Num(line, "MontoTotal");
            var taxes = line.Elements().Where(e => e.Name.LocalName == "Impuesto").ToList();
            var cabys = Text(line, "CodigoCABYS");

            lines.Add(new ParsedLine(
                (int)Num(line, "NumeroLinea"),
                cabys.Length > 0 ? cabys : Text(line.Elements().FirstOrDefault(e => e.Name.LocalName == "CodigoComercial"), "Codigo"),
                Text(line, "Detalle"),
                quantity,
                quantity > 0 ? Math.Round(subtotal / quantity, 4) : subtotal,
                subtotal,
                taxes.Sum(t => Num(t, "Tarifa")),
                taxes.Sum(t => Num(t, "Monto"))));
        }

        var saleCondition = Text(root, "CondicionVenta");

        return new ParsedDocument(
            key, type, Text(root, "NumeroConsecutivo"),
            Text(issuer.Elements().FirstOrDefault(e => e.Name.LocalName == "Identificacion"), "Numero"),
            Text(issuer, "Nombre"), NullIfEmpty(Text(issuer, "CorreoElectronico")),
            DateTime.TryParse(Text(root, "FechaEmision"), CultureInfo.InvariantCulture, DateTimeStyles.AdjustToUniversal | DateTimeStyles.AssumeUniversal, out var date)
                ? date : DateTime.UtcNow,
            NullIfEmpty(Text(summary?.Elements().FirstOrDefault(e => e.Name.LocalName == "CodigoTipoMoneda"), "CodigoMoneda")) ?? "CRC",
            saleCondition == "02", (int)Num(root, "PlazoCredito"),
            summary is null ? lines.Sum(l => l.Subtotal) : Num(summary, summary.Elements().Any(e => e.Name.LocalName == "TotalVentaNeta") ? "TotalVentaNeta" : "TotalVenta"),
            summary is null ? lines.Sum(l => l.TaxAmount) : Num(summary, "TotalImpuesto"),
            summary is null ? lines.Sum(l => l.Subtotal + l.TaxAmount) : Num(summary, "TotalComprobante"),
            lines);
    }

    private static string Text(XElement? parent, string name) =>
        parent?.Elements().FirstOrDefault(e => e.Name.LocalName == name)?.Value.Trim() ?? string.Empty;

    private static decimal Num(XElement? parent, string name) =>
        decimal.TryParse(Text(parent, name), NumberStyles.Any, CultureInfo.InvariantCulture, out var v) ? v : 0m;

    private static string? NullIfEmpty(string s) => s.Length == 0 ? null : s;
}
