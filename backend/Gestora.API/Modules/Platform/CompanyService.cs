using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Auth;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Platform;

/// <summary>
/// Gestión de las empresas suscritas, desde la perspectiva de Gestora como producto.
///
/// Solo maneja la cuenta: nombre, contacto, estado y suscripción. No toca —ni puede
/// consultar— la información interna de la empresa; para eso el desarrollador entra
/// a verla con <see cref="AuthService.ImpersonateAsync"/>, lo cual queda auditado.
/// </summary>
public class CompanyService(GestoraDbContext db, IAuditService audit)
{
    public async Task<PagedResult<CompanyDto>> ListAsync(CompanyQuery q)
    {
        var query = BaseQuery();

        if (q.Status is { } status) query = query.Where(c => c.Status == status);

        if (q.Expired == true)
        {
            var today = DateTime.UtcNow.Date;
            query = query.Where(c => c.Subscriptions.Any(s =>
                s.Status != SubscriptionStatus.Cancelled && s.EndDate < today));
        }

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(c => c.Name.Contains(term) || c.AccountEmail.Contains(term)
                || (c.TaxId != null && c.TaxId.Contains(term)));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(c => c.Name)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<CompanyDto>(await MapManyAsync(rows), total, q.Page, q.PageSize);
    }

    public async Task<CompanyDto> GetAsync(int id)
    {
        var company = await BaseQuery().FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La empresa");

        return (await MapManyAsync([company])).Single();
    }

    /// <summary>
    /// Da de alta un cliente nuevo completo: empresa, suscripción y cuenta de
    /// administrador. Todo en una transacción: una empresa sin cuenta con la que entrar,
    /// o sin suscripción que la habilite, no sirve de nada.
    /// </summary>
    public async Task<RegisterCompanyResult> RegisterAsync(RegisterCompanyRequest request)
    {
        var accountEmail = request.AccountEmail.Trim().ToLowerInvariant();

        if (await db.Companies.AnyAsync(c => c.AccountEmail == accountEmail))
            throw ApiException.Conflict("Ya existe una empresa registrada con ese correo.");

        if (await db.Users.IgnoreQueryFilters().AnyAsync(u => u.Email == accountEmail))
            throw ApiException.Conflict("Ya existe un usuario con ese correo.");

        var plan = await db.Plans.FirstOrDefaultAsync(p => p.Id == request.PlanId && p.IsActive)
            ?? throw new ApiException("El plan seleccionado no es válido.");

        var adminRole = await db.Roles.FirstAsync(r => r.Key == RoleKeys.CompanyAdmin);

        // Si no se define una contraseña, se genera una temporal que se muestra una vez.
        var generated = string.IsNullOrWhiteSpace(request.AdminPassword);
        var password = generated ? GenerateTemporaryPassword() : request.AdminPassword!;
        var (hash, salt) = PasswordHasher.Hash(password);

        var startDate = (request.StartDate ?? DateTime.UtcNow).Date;

        var strategy = db.Database.CreateExecutionStrategy();
        var companyId = await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var company = new Company
            {
                Name = request.Name.Trim(),
                AccountEmail = accountEmail,
                TaxId = request.TaxId?.Trim(),
                Phone = request.Phone?.Trim(),
                Address = request.Address?.Trim(),
                Currency = string.IsNullOrWhiteSpace(request.Currency) ? "CRC" : request.Currency.Trim().ToUpperInvariant(),
                DefaultTaxRate = request.DefaultTaxRate,
                Notes = request.Notes?.Trim(),
                Status = CompanyStatus.Active
            };
            db.Companies.Add(company);
            await db.SaveChangesAsync();

            db.Subscriptions.Add(new Subscription
            {
                CompanyId = company.Id,
                PlanId = plan.Id,
                StartDate = startDate,
                EndDate = startDate.AddMonths(plan.BillingPeriodMonths),
                Status = request.TrialPeriod ? SubscriptionStatus.Trial : SubscriptionStatus.Active,
                Price = request.Price ?? plan.Price
            });

            db.Users.Add(new User
            {
                CompanyId = company.Id,
                FirstName = request.AdminFirstName.Trim(),
                LastName = string.IsNullOrWhiteSpace(request.AdminLastName)
                    ? company.Name
                    : request.AdminLastName.Trim(),
                Email = accountEmail,
                PasswordHash = hash,
                PasswordSalt = salt,
                RoleId = adminRole.Id
            });

            audit.Track("Alta de empresa", "platform_companies", nameof(Company), null,
                $"{company.Name} registrada con el plan {plan.Name} · cuenta {accountEmail}",
                companyId: 0);

            await db.SaveChangesAsync();

            // El catálogo base deja la empresa lista para operar desde el primer día.
            db.TenantId = company.Id;
            await SeedCompanyCatalogAsync(company.Id);

            await transaction.CommitAsync();
            return company.Id;
        });

        db.TenantId = 0;
        return new RegisterCompanyResult(await GetAsync(companyId), accountEmail,
            generated ? password : null);
    }

    public async Task<CompanyDto> UpdateAsync(int id, CompanyRequest request)
    {
        var company = await db.Companies.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La empresa");

        var accountEmail = request.AccountEmail.Trim().ToLowerInvariant();
        if (await db.Companies.AnyAsync(c => c.AccountEmail == accountEmail && c.Id != id))
            throw ApiException.Conflict("Ya existe otra empresa con ese correo.");

        var before = $"{company.Name} · {company.AccountEmail}";

        company.Name = request.Name.Trim();
        company.AccountEmail = accountEmail;
        company.TaxId = request.TaxId?.Trim();
        company.Phone = request.Phone?.Trim();
        company.Address = request.Address?.Trim();
        company.Currency = string.IsNullOrWhiteSpace(request.Currency) ? company.Currency : request.Currency.Trim().ToUpperInvariant();
        company.DefaultTaxRate = request.DefaultTaxRate;
        company.Notes = request.Notes?.Trim();

        audit.Track("Actualización", "platform_companies", nameof(Company), id,
            "Datos de la empresa", before, $"{company.Name} · {company.AccountEmail}", companyId: 0);
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>
    /// Suspende o reactiva la cuenta. Suspender cierra las sesiones abiertas de la
    /// empresa: si dejó de pagar, deja de entrar en el momento en que se decide.
    /// </summary>
    public async Task<CompanyDto> SetStatusAsync(int id, CompanyStatus status, string? reason)
    {
        var company = await db.Companies.FirstOrDefaultAsync(c => c.Id == id)
            ?? throw ApiException.NotFound("La empresa");

        if (company.Status == status) return await GetAsync(id);

        var before = company.Status;
        company.Status = status;

        if (status != CompanyStatus.Active)
        {
            var tokens = await db.RefreshTokens.IgnoreQueryFilters()
                .Where(t => t.User.CompanyId == id && t.RevokedAt == null)
                .ToListAsync();
            tokens.ForEach(t => t.RevokedAt = DateTime.UtcNow);
        }

        audit.Track("Cambio de estado", "platform_companies", nameof(Company), id,
            $"{company.Name}: {StatusName(before)} → {StatusName(status)}" +
            (string.IsNullOrWhiteSpace(reason) ? "" : $" · {reason}"),
            companyId: 0);

        await db.SaveChangesAsync();
        return await GetAsync(id);
    }

    // ------------------------------------------------------------ Internos ----

    private IQueryable<Company> BaseQuery() =>
        db.Companies.AsNoTracking()
            .Include(c => c.Subscriptions).ThenInclude(s => s.Plan);

    /// <summary>
    /// Cuenta los usuarios de cada empresa en una sola consulta, en vez de una por fila.
    /// Se usa IgnoreQueryFilters porque el usuario de plataforma no tiene inquilino.
    /// </summary>
    private async Task<List<CompanyDto>> MapManyAsync(List<Company> companies)
    {
        var ids = companies.Select(c => c.Id).ToList();

        var userCounts = await db.Users.IgnoreQueryFilters()
            .Where(u => u.CompanyId != null && ids.Contains(u.CompanyId!.Value))
            .GroupBy(u => u.CompanyId!.Value)
            .Select(g => new { CompanyId = g.Key, Count = g.Count() })
            .ToDictionaryAsync(x => x.CompanyId, x => x.Count);

        return companies.Select(c => Map(c, userCounts.GetValueOrDefault(c.Id))).ToList();
    }

    internal static CompanyDto Map(Company c, int userCount)
    {
        var subscription = c.Subscriptions
            .OrderByDescending(s => s.EndDate)
            .FirstOrDefault();

        return new CompanyDto(c.Id, c.Name, c.AccountEmail, c.TaxId, c.Phone, c.Address,
            c.Currency, c.Status, StatusName(c.Status), c.Notes, c.CreatedAt, userCount,
            subscription is null ? null : new SubscriptionSummaryDto(
                subscription.Id, subscription.PlanId, subscription.Plan?.Name ?? string.Empty,
                subscription.StartDate, subscription.EndDate, subscription.Status,
                SubscriptionService.StatusName(subscription.Status), subscription.Price,
                subscription.DaysToExpiry, subscription.DaysToExpiry < 0));
    }

    internal static string StatusName(CompanyStatus status) => status switch
    {
        CompanyStatus.Active => "Activa",
        CompanyStatus.Suspended => "Suspendida",
        CompanyStatus.Cancelled => "Dada de baja",
        _ => status.ToString()
    };

    /// <summary>Contraseña temporal legible pero no adivinable, para entregarla al cliente.</summary>
    private static string GenerateTemporaryPassword() =>
        $"Gestora{Random.Shared.Next(1000, 9999)}{PasswordHasher.RandomToken(4)[..4]}";

    /// <summary>
    /// Deja la empresa lista para operar desde el primer ingreso: unidades, categorías
    /// de producto y categorías de caja. La definición vive en <see cref="CompanyCatalogSeed"/>,
    /// compartida con el arranque, para que ningún camino de alta deje una empresa a medias.
    /// </summary>
    private async Task SeedCompanyCatalogAsync(int companyId)
    {
        CompanyCatalogSeed.AddAll(db, companyId);
        await db.SaveChangesAsync();
    }
}
