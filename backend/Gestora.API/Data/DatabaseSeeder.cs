using Gestora.API.Common;
using Gestora.API.Domain;
using Gestora.API.Modules.Auth;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Data;

/// <summary>
/// Deja la base en un estado utilizable en el primer arranque: aplica migraciones,
/// crea el catálogo de roles, los planes comerciales, el usuario desarrollador y —en
/// desarrollo— una empresa de ejemplo con su cuenta, para poder probar las dos vistas.
/// Es idempotente: si algo ya existe, no lo duplica.
/// </summary>
public static class DatabaseSeeder
{
    public static async Task SeedAsync(IServiceProvider services, IConfiguration config, ILogger logger)
    {
        using var scope = services.CreateScope();
        var db = scope.ServiceProvider.GetRequiredService<GestoraDbContext>();

        await db.Database.MigrateAsync();

        await SeedRolesAsync(db, logger);
        await SeedPlansAsync(db, logger);
        await SeedDeveloperAsync(db, config, logger);
        await SeedDemoCompanyAsync(db, config, logger);
        await BackfillFinanceCategoriesAsync(db, logger);
    }

    /// <summary>
    /// Da a cada empresa sus categorías de caja si todavía no las tiene. Hace falta
    /// porque las empresas dadas de alta antes del módulo de finanzas no las recibieron
    /// al crearse, y sin al menos una categoría no se puede registrar un gasto.
    /// </summary>
    private static async Task BackfillFinanceCategoriesAsync(GestoraDbContext db, ILogger logger)
    {
        var withCategories = await db.FinanceCategories.IgnoreQueryFilters()
            .Select(c => c.CompanyId).Distinct().ToListAsync();

        var pending = await db.Companies
            .Where(c => !withCategories.Contains(c.Id))
            .Select(c => c.Id)
            .ToListAsync();

        if (pending.Count == 0) return;

        foreach (var companyId in pending) CompanyCatalogSeed.AddFinanceCategories(db, companyId);
        await db.SaveChangesAsync();

        logger.LogInformation("Categorías de ingresos y gastos creadas para {Count} empresa(s).", pending.Count);
    }

    /// <summary>
    /// Catálogo fijo de roles. Los permisos se recalculan en cada arranque para que
    /// agregar un módulo al catálogo baste para que los roles lo reciban.
    /// </summary>
    private static async Task SeedRolesAsync(GestoraDbContext db, ILogger logger)
    {
        var definitions = new (string Key, string Name, string Description, RoleScope Scope,
            int Order, IEnumerable<string> Modules, bool Write)[]
        {
            (RoleKeys.Developer, "Desarrollador",
                "Acceso completo a la plataforma y a cualquier empresa. Puede ver el sistema como la ve un cliente.",
                RoleScope.Platform, 1,
                ModuleCatalog.PlatformModules.Concat(ModuleCatalog.CompanyModules), true),

            (RoleKeys.PlatformAdmin, "Administración Gestora",
                "Gestiona empresas, suscripciones y cobros. No entra a los datos internos de una empresa.",
                RoleScope.Platform, 2,
                ModuleCatalog.PlatformAdminModules, true),

            (RoleKeys.CompanyAdmin, "Administrador",
                "Administra la empresa: registra, edita y da de baja información.",
                RoleScope.Company, 3,
                ModuleCatalog.CompanyModules, true),

            (RoleKeys.CompanyViewer, "Consulta",
                "Solo lectura de la información de la empresa.",
                RoleScope.Company, 4,
                ModuleCatalog.CompanyModules.Where(m => m != "users"), false),
        };

        foreach (var definition in definitions)
        {
            var role = await db.Roles.Include(r => r.Permissions)
                .FirstOrDefaultAsync(r => r.Key == definition.Key);

            if (role is null)
            {
                role = new Role { Key = definition.Key };
                db.Roles.Add(role);
                logger.LogInformation("Rol creado: {Role}", definition.Name);
            }

            role.Name = definition.Name;
            role.Description = definition.Description;
            role.Scope = definition.Scope;
            role.SortOrder = definition.Order;

            SyncPermissions(db, role, definition.Modules, definition.Write);
        }

        await db.SaveChangesAsync();
    }

    /// <summary>Deja el rol exactamente con los módulos indicados, sin duplicar filas.</summary>
    private static void SyncPermissions(GestoraDbContext db, Role role,
        IEnumerable<string> modules, bool canWrite)
    {
        var wanted = modules.Distinct().ToHashSet();

        var obsolete = role.Permissions.Where(p => !wanted.Contains(p.ModuleKey)).ToList();
        if (obsolete.Count > 0) db.RolePermissions.RemoveRange(obsolete);

        foreach (var moduleKey in wanted)
        {
            var permission = role.Permissions.FirstOrDefault(p => p.ModuleKey == moduleKey);
            if (permission is null)
            {
                role.Permissions.Add(new RolePermission
                {
                    ModuleKey = moduleKey,
                    CanRead = true,
                    CanWrite = canWrite
                });
            }
            else
            {
                permission.CanRead = true;
                permission.CanWrite = canWrite;
            }
        }
    }

    private static async Task SeedPlansAsync(GestoraDbContext db, ILogger logger)
    {
        if (await db.Plans.AnyAsync()) return;

        db.Plans.AddRange(
            new Plan
            {
                Name = "Básico",
                Description = "Catálogo, inventario y compras para una empresa pequeña.",
                Price = 25000m, BillingPeriodMonths = 1, MaxUsers = 2, SortOrder = 1
            },
            new Plan
            {
                Name = "Profesional",
                Description = "Todo el sistema, incluidas ventas, producción y reportes.",
                Price = 45000m, BillingPeriodMonths = 1, MaxUsers = 5, SortOrder = 2
            },
            new Plan
            {
                Name = "Profesional anual",
                Description = "Plan profesional con dos meses de descuento al pagar por año.",
                Price = 450000m, BillingPeriodMonths = 12, MaxUsers = 5, SortOrder = 3
            });

        await db.SaveChangesAsync();
        logger.LogInformation("Planes comerciales iniciales creados.");
    }

    /// <summary>Cuenta del desarrollador: no pertenece a ninguna empresa.</summary>
    private static async Task SeedDeveloperAsync(GestoraDbContext db, IConfiguration config, ILogger logger)
    {
        if (await db.Users.IgnoreQueryFilters().AnyAsync(u => u.CompanyId == null)) return;

        var email = (config["Seed:DeveloperEmail"] ?? "dev@gestora.local").ToLowerInvariant();
        var password = config["Seed:DeveloperPassword"] ?? "Gestora2026!";
        var (hash, salt) = PasswordHasher.Hash(password);
        var role = await db.Roles.FirstAsync(r => r.Key == RoleKeys.Developer);

        db.Users.Add(new User
        {
            CompanyId = null,
            FirstName = config["Seed:DeveloperFirstName"] ?? "Desarrollador",
            LastName = config["Seed:DeveloperLastName"] ?? "Gestora",
            Email = email,
            PasswordHash = hash,
            PasswordSalt = salt,
            RoleId = role.Id
        });

        await db.SaveChangesAsync();
        logger.LogWarning("Cuenta de desarrollador creada: {Email}. Cambie la contraseña al primer ingreso.", email);
    }

    /// <summary>
    /// Empresa de ejemplo con su suscripción y su cuenta de administrador, para poder
    /// probar la vista de empresa sin tener que darla de alta a mano en cada entorno.
    /// </summary>
    private static async Task SeedDemoCompanyAsync(GestoraDbContext db, IConfiguration config, ILogger logger)
    {
        if (await db.Companies.AnyAsync()) return;

        var accountEmail = (config["Seed:CompanyEmail"] ?? "empresa@gestora.local").ToLowerInvariant();

        var company = new Company
        {
            Name = config["Seed:CompanyName"] ?? "Empresa Demo",
            AccountEmail = accountEmail,
            Currency = config["Seed:Currency"] ?? "CRC",
            DefaultTaxRate = 13m,
            Status = CompanyStatus.Active
        };
        db.Companies.Add(company);
        await db.SaveChangesAsync();

        var plan = await db.Plans.OrderBy(p => p.SortOrder).FirstAsync();
        db.Subscriptions.Add(new Subscription
        {
            CompanyId = company.Id,
            PlanId = plan.Id,
            StartDate = DateTime.UtcNow.Date,
            EndDate = DateTime.UtcNow.Date.AddMonths(plan.BillingPeriodMonths),
            Status = SubscriptionStatus.Active,
            Price = plan.Price
        });

        var password = config["Seed:CompanyPassword"] ?? "Empresa2026!";
        var (hash, salt) = PasswordHasher.Hash(password);
        var adminRole = await db.Roles.FirstAsync(r => r.Key == RoleKeys.CompanyAdmin);

        db.Users.Add(new User
        {
            CompanyId = company.Id,
            FirstName = "Administración",
            LastName = company.Name,
            Email = accountEmail,
            PasswordHash = hash,
            PasswordSalt = salt,
            RoleId = adminRole.Id
        });

        await db.SaveChangesAsync();

        // El inquilino se fija a mano: no hay petición HTTP de la que deducirlo.
        db.TenantId = company.Id;
        await SeedCompanyCatalogAsync(db, company.Id);

        logger.LogWarning("Empresa de ejemplo creada: {Company} · cuenta {Email}", company.Name, accountEmail);
    }

    /// <summary>Catálogo base para que la empresa sea usable desde el minuto cero.</summary>
    private static async Task SeedCompanyCatalogAsync(GestoraDbContext db, int companyId)
    {
        CompanyCatalogSeed.AddAll(db, companyId);
        await db.SaveChangesAsync();
    }
}
