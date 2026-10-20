using System.Text.Json;
using System.Text.RegularExpressions;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Purchasing;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Einvoice;

/// <summary>
/// Bandeja de comprobantes recibidos. Llegan por subida manual o por el correo de
/// recepción de la empresa; de ahí se revisan y se convierten en una compra (que a su
/// vez genera el inventario y la cuenta por pagar). El XML original se conserva intacto.
/// </summary>
public partial class ReceivedDocumentService(GestoraDbContext db, IAuditService audit, PurchaseService purchases,
    ICurrentUser current, IConfiguration config)
{
    private const int MaxXmlChars = 1_000_000;

    public async Task<PagedResult<ReceivedDocumentDto>> ListAsync(ReceivedDocumentQuery q)
    {
        var query = db.ReceivedDocuments.AsNoTracking().AsQueryable();
        if (q.Status is { } status) query = query.Where(d => d.Status == status);
        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(d => d.IssuerName.Contains(term) || d.Consecutive.Contains(term) || d.IssuerTaxId.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query.Include(d => d.Purchase)
            .OrderByDescending(d => d.ReceivedAt).ThenByDescending(d => d.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize).ToListAsync();

        return new PagedResult<ReceivedDocumentDto>(rows.Select(d => Map(d, null)).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<ReceivedDocumentDto> GetAsync(int id)
    {
        var doc = await LoadAsync(id);
        var lines = ReadLines(doc);

        // Sugerencia de producto: el código del proveedor contra el código de barras, o el
        // mismo nombre. Es solo una propuesta; la persona la corrige antes de importar.
        var products = await db.Products.AsNoTracking().Where(p => p.IsActive)
            .Select(p => new { p.Id, p.Name, p.Barcode }).ToListAsync();
        var mapped = lines.Select(l =>
        {
            var match = products.FirstOrDefault(p =>
                (l.Code.Length > 0 && p.Barcode == l.Code) ||
                string.Equals(p.Name, l.Description, StringComparison.OrdinalIgnoreCase));
            return new ReceivedLineDto(l.Number, l.Code, l.Description, l.Quantity, l.UnitCost,
                l.Subtotal, l.TaxRate, match?.Id, match?.Name);
        }).ToList();

        var supplier = await FindSupplierAsync(doc.IssuerTaxId);
        return Map(doc, mapped) with { SupplierName = supplier?.Name };
    }

    // ---------------------------------------------------------------- Ingreso ----

    public async Task<UploadResultDto> UploadAsync(string fileName, string xml,
        ReceivedDocumentSource source = ReceivedDocumentSource.Upload, string? sender = null, int? companyId = null)
    {
        try
        {
            if (xml.Length > MaxXmlChars) throw new ApiException("El archivo es demasiado grande.");
            var parsed = InvoiceXmlParser.Parse(xml.TrimStart('﻿'));

            var existing = await db.ReceivedDocuments.AsNoTracking()
                .Where(d => d.Key == parsed.Key).Select(d => (int?)d.Id).FirstOrDefaultAsync();
            if (existing is not null)
                return new UploadResultDto(fileName, true, true, "Ya estaba en la bandeja.", existing);

            var doc = new ReceivedDocument
            {
                Key = parsed.Key, DocumentType = parsed.DocumentType, Consecutive = parsed.Consecutive,
                IssuerTaxId = parsed.IssuerTaxId, IssuerName = Truncate(parsed.IssuerName, 150), IssuerEmail = parsed.IssuerEmail,
                IssueDate = parsed.IssueDate, Currency = parsed.Currency, IsCredit = parsed.IsCredit,
                CreditDays = parsed.CreditDays, Subtotal = parsed.Subtotal, TaxAmount = parsed.TaxAmount,
                Total = parsed.Total, Source = source, SenderEmail = sender,
                LinesJson = JsonSerializer.Serialize(parsed.Lines), Xml = xml
            };
            db.ReceivedDocuments.Add(doc);
            audit.Track("Recepción", "einvoices", nameof(ReceivedDocument), null,
                $"Comprobante {doc.Consecutive} de {doc.IssuerName} ({(source == ReceivedDocumentSource.Email ? "correo" : "subida")})",
                companyId: companyId);
            await db.SaveChangesAsync();
            return new UploadResultDto(fileName, true, false, "Recibido.", doc.Id);
        }
        catch (ApiException ex)
        {
            return new UploadResultDto(fileName, false, false, ex.Message, null);
        }
        catch (DbUpdateException)
        {
            // Dos envíos simultáneos del mismo comprobante: el índice único gana.
            db.ChangeTracker.Clear();
            return new UploadResultDto(fileName, true, true, "Ya estaba en la bandeja.", null);
        }
    }

    // ------------------------------------------------------------- Importación ----

    public async Task<ReceivedDocumentDto> ImportAsync(int id, ImportRequest request)
    {
        var doc = await LoadAsync(id);
        if (doc.Status != ReceivedDocumentStatus.Pending)
            throw new ApiException("Este comprobante ya fue importado o descartado.");
        if (doc.DocumentType is "NotaCreditoElectronica" or "NotaDebitoElectronica")
            throw new ApiException("Las notas de crédito y débito todavía no se importan. Descártela y ajuste la compra a mano.");

        var company = await db.Companies.AsNoTracking().FirstAsync(c => c.Id == current.CompanyId);
        if (!string.Equals(doc.Currency, company.Currency, StringComparison.OrdinalIgnoreCase))
            throw new ApiException($"El comprobante está en {doc.Currency} y la empresa opera en {company.Currency}. La conversión de moneda aún no está soportada.");

        var lines = ReadLines(doc);
        if (lines.Count == 0) throw new ApiException("El comprobante no trae líneas de detalle.");
        if (lines.Any(l => l.Quantity <= 0)) throw new ApiException("Hay líneas con cantidad cero o negativa.");

        var chosen = request.Lines.Where(l => l.ProductId is not null).ToDictionary(l => l.Number, l => l.ProductId!.Value);
        var validIds = await db.Products.Where(p => p.IsActive).Select(p => p.Id).ToListAsync();
        if (chosen.Values.Any(pid => !validIds.Contains(pid)))
            throw new ApiException("Uno de los productos seleccionados no es válido.");

        var supplier = await FindSupplierAsync(doc.IssuerTaxId) ?? await CreateSupplierAsync(doc);

        // Líneas sin producto elegido: se crea uno nuevo (materia prima) para no frenar el flujo.
        Unit? unit = null;
        List<string>? codes = null;
        var items = new List<PurchaseItemRequest>();
        foreach (var line in lines)
        {
            if (!chosen.TryGetValue(line.Number, out var productId))
            {
                unit ??= await db.Units.Where(u => u.IsActive).OrderBy(u => u.Id).FirstOrDefaultAsync()
                    ?? throw new ApiException("Cree al menos una unidad de medida antes de importar.");
                codes ??= await db.Products.Select(p => p.Code).ToListAsync();
                var code = CodeGenerator.Next("PRD", codes);
                codes.Add(code);

                var product = new Product
                {
                    Code = code, Name = Truncate(line.Description, 150),
                    Barcode = line.Code.Length is > 0 and <= 60 ? line.Code : null,
                    Type = ProductType.RawMaterial, UnitId = unit.Id, Cost = line.UnitCost, TaxRate = line.TaxRate
                };
                db.Products.Add(product);
                await db.SaveChangesAsync();
                productId = product.Id;
            }

            items.Add(new PurchaseItemRequest
            {
                ProductId = productId, Quantity = line.Quantity,
                UnitCost = line.UnitCost, TaxRate = Math.Min(line.TaxRate, 100)
            });
        }

        var purchase = await purchases.CreateAsync(new PurchaseRequest
        {
            SupplierId = supplier.Id, Date = doc.IssueDate,
            PaymentTerm = doc.IsCredit ? PaymentTerm.Credit : PaymentTerm.Cash,
            CreditDays = doc.IsCredit ? doc.CreditDays : 0,
            SupplierInvoiceNumber = doc.Consecutive,
            Notes = $"Importada del comprobante electrónico {doc.Key}",
            Items = items
        });

        doc.Status = ReceivedDocumentStatus.Imported;
        doc.PurchaseId = purchase.Id;
        audit.Track("Importación", "einvoices", nameof(ReceivedDocument), doc.Id,
            $"Comprobante {doc.Consecutive} importado como compra {purchase.Number}");
        await db.SaveChangesAsync();

        if (request.Confirm) await purchases.ConfirmAsync(purchase.Id);
        return await GetAsync(id);
    }

    public async Task<ReceivedDocumentDto> DiscardAsync(int id)
    {
        var doc = await LoadAsync(id);
        if (doc.Status != ReceivedDocumentStatus.Pending)
            throw new ApiException("Solo se puede descartar un comprobante pendiente.");

        doc.Status = ReceivedDocumentStatus.Discarded;
        audit.Track("Descarte", "einvoices", nameof(ReceivedDocument), id, $"Comprobante {doc.Consecutive} de {doc.IssuerName}");
        await db.SaveChangesAsync();
        return Map(doc, null);
    }

    public async Task<string> GetXmlAsync(int id) => (await LoadAsync(id)).Xml;

    // ------------------------------------------------------- Correo de recepción ----

    public async Task<InboundAddressDto> GetAddressAsync()
    {
        var domain = config["Einvoice:InboundDomain"];
        if (string.IsNullOrWhiteSpace(domain)) return new InboundAddressDto(null, false);

        var company = await db.Companies.FirstAsync(c => c.Id == current.CompanyId);
        if (company.InboundToken is null)
        {
            company.InboundToken = Convert.ToHexString(System.Security.Cryptography.RandomNumberGenerator.GetBytes(6)).ToLowerInvariant();
            await db.SaveChangesAsync();
        }
        return new InboundAddressDto($"facturas-{company.InboundToken}@{domain}", true);
    }

    /// <summary>
    /// Procesa un correo entrante reenviado por el proveedor de correo (SendGrid, Mailgun...).
    /// La empresa se identifica por la dirección de destino. Los XML adjuntos entran a la
    /// bandeja; cualquier otra cosa se ignora.
    /// </summary>
    public async Task<int> ReceiveEmailAsync(string recipients, string? sender, IEnumerable<(string Name, string Xml)> files)
    {
        var match = InboundAddress().Match(recipients ?? string.Empty);
        if (!match.Success) return 0;

        var token = match.Groups[1].Value.ToLowerInvariant();
        var company = await db.Companies.AsNoTracking().FirstOrDefaultAsync(c => c.InboundToken == token);
        if (company is null || !company.IsActive) return 0;

        db.TenantId = company.Id;
        var received = 0;
        foreach (var (name, xml) in files)
        {
            var result = await UploadAsync(name, xml, ReceivedDocumentSource.Email, Truncate(sender ?? string.Empty, 150), company.Id);
            if (result.Ok && !result.Duplicate) received++;
        }
        return received;
    }

    [GeneratedRegex("facturas-([a-f0-9]{12})@", RegexOptions.IgnoreCase)]
    private static partial Regex InboundAddress();

    // -------------------------------------------------------------------- Apoyo ----

    private async Task<Supplier?> FindSupplierAsync(string taxId)
    {
        var digits = Digits(taxId);
        if (digits.Length == 0) return null;
        var suppliers = await db.Suppliers.AsNoTracking().Where(s => s.TaxId != null).ToListAsync();
        return suppliers.FirstOrDefault(s => Digits(s.TaxId!) == digits);
    }

    private async Task<Supplier> CreateSupplierAsync(ReceivedDocument doc)
    {
        var codes = await db.Suppliers.Select(s => s.Code).ToListAsync();
        var supplier = new Supplier
        {
            Code = CodeGenerator.Next("PRV", codes), Name = Truncate(doc.IssuerName, 150), TaxId = doc.IssuerTaxId,
            Email = doc.IssuerEmail, PaymentTerm = doc.IsCredit ? PaymentTerm.Credit : PaymentTerm.Cash,
            CreditDays = doc.IsCredit ? doc.CreditDays : 0, Notes = "Creado al importar un comprobante electrónico."
        };
        db.Suppliers.Add(supplier);
        await db.SaveChangesAsync();
        return supplier;
    }

    private async Task<ReceivedDocument> LoadAsync(int id) =>
        await db.ReceivedDocuments.Include(d => d.Purchase).FirstOrDefaultAsync(d => d.Id == id)
        ?? throw ApiException.NotFound("El comprobante");

    private static List<ParsedLine> ReadLines(ReceivedDocument doc) =>
        JsonSerializer.Deserialize<List<ParsedLine>>(doc.LinesJson) ?? new();

    private static string Digits(string s) => new(s.Where(char.IsDigit).ToArray());
    private static string Truncate(string s, int max) => s.Length <= max ? s : s[..max];

    private static ReceivedDocumentDto Map(ReceivedDocument d, IReadOnlyList<ReceivedLineDto>? lines) => new(
        d.Id, d.Key, d.DocumentType, TypeName(d.DocumentType), d.Consecutive, d.IssuerTaxId, d.IssuerName, d.IssueDate,
        d.Currency, d.IsCredit, d.CreditDays, d.Subtotal, d.TaxAmount, d.Total,
        d.Source == ReceivedDocumentSource.Email ? "Correo" : "Subida manual", d.SenderEmail, d.ReceivedAt,
        d.Status, d.Status switch { ReceivedDocumentStatus.Pending => "Pendiente", ReceivedDocumentStatus.Imported => "Importada", _ => "Descartada" },
        d.PurchaseId, d.Purchase?.Number, null, lines ?? Array.Empty<ReceivedLineDto>());

    private static string TypeName(string type) => type switch
    {
        "FacturaElectronica" => "Factura electrónica",
        "TiqueteElectronico" => "Tiquete electrónico",
        "NotaCreditoElectronica" => "Nota de crédito",
        "NotaDebitoElectronica" => "Nota de débito",
        "FacturaElectronicaCompra" => "Factura de compra",
        "FacturaElectronicaExportacion" => "Factura de exportación",
        _ => type
    };
}
