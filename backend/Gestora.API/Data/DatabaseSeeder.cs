using Gestora.API.Common;
using Gestora.API.Domain;
using Gestora.API.Modules.Auth;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Data;

/// <summary>
/// Deja la base en un estado utilizable en el primer arranque: aplica migraciones,
/// crea la empresa, los roles con sus permisos, el usuario administrador y los
/// catálogos mínimos. Es idempotente: si algo ya existe, no lo duplica.
/// </summary>
public static class DatabaseSeeder
{
    public static async Task SeedAsync(IServiceProvider services, IConfiguration config, ILogger logger)
    {
        using var scope = services.CreateScope();
        var db = scope.ServiceProvider.GetRequiredService<GestoraDbContext>();

        await db.Database.MigrateAsync();

        // OrderBy explícito: sin él EF advierte que el resultado podría no ser estable.
        var company = await db.Companies.OrderBy(c => c.Id).FirstOrDefaultAsync();
        if (company is null)
        {
            company = new Company
            {
                Name = config["Seed:CompanyName"] ?? "Mi Empresa",
                Currency = config["Seed:Currency"] ?? "CRC",
                DefaultTaxRate = 13m
            };
            db.Companies.Add(company);
            await db.SaveChangesAsync();
            logger.LogInformation("Empresa inicial creada: {Company}", company.Name);
        }

        // A partir de acá todo pertenece a la empresa: se fija el inquilino de la unidad
        // de trabajo porque no hay petición HTTP de la que deducirlo.
        db.TenantId = company.Id;

        var adminRole = await EnsureRoleAsync(db, "Administrador",
            "Acceso total al sistema.", ModuleCatalog.AllKeys, write: true, isSystem: true);

        await EnsureRoleAsync(db, "Administración",
            "Operación diaria: clientes, compras, ventas, inventario y finanzas.",
            ModuleCatalog.OperatorModules, write: true, isSystem: false);

        await EnsureRoleAsync(db, "Consulta",
            "Solo lectura de información operativa y financiera.",
            ModuleCatalog.ViewerModules, write: false, isSystem: false);

        await db.SaveChangesAsync();

        if (!await db.Users.AnyAsync())
        {
            var email = (config["Seed:AdminEmail"] ?? "admin@gestora.local").ToLowerInvariant();
            var password = config["Seed:AdminPassword"] ?? "Gestora2026!";
            var (hash, salt) = PasswordHasher.Hash(password);

            db.Users.Add(new User
            {
                CompanyId = company.Id,
                FirstName = "Administrador",
                LastName = "del sistema",
                Email = email,
                PasswordHash = hash,
                PasswordSalt = salt,
                RoleId = adminRole.Id
            });
            await db.SaveChangesAsync();

            logger.LogWarning("Usuario administrador creado: {Email}. Cambie la contraseña al primer ingreso.", email);
        }

        await SeedCatalogAsync(db, company.Id, logger);
    }

    private static async Task<Role> EnsureRoleAsync(GestoraDbContext db, string name, string description,
        IEnumerable<string> modules, bool write, bool isSystem)
    {
        var role = await db.Roles.Include(r => r.Permissions).FirstOrDefaultAsync(r => r.Name == name);
        if (role is not null) return role;

        role = new Role { Name = name, Description = description, IsSystem = isSystem };
        foreach (var key in modules)
            role.Permissions.Add(new RolePermission { ModuleKey = key, CanRead = true, CanWrite = write });

        db.Roles.Add(role);
        return role;
    }

    /// <summary>Unidades y categorías genéricas para que el catálogo sea usable desde el minuto cero.</summary>
    private static async Task SeedCatalogAsync(GestoraDbContext db, int companyId, ILogger logger)
    {
        if (!await db.Units.AnyAsync())
        {
            db.Units.AddRange(
                new Unit { CompanyId = companyId, Name = "Unidad", Abbreviation = "ud", DecimalPlaces = 0 },
                new Unit { CompanyId = companyId, Name = "Pieza", Abbreviation = "pz", DecimalPlaces = 0 },
                new Unit { CompanyId = companyId, Name = "Metro", Abbreviation = "m", DecimalPlaces = 2 },
                new Unit { CompanyId = companyId, Name = "Metro cuadrado", Abbreviation = "m2", DecimalPlaces = 2 },
                new Unit { CompanyId = companyId, Name = "Litro", Abbreviation = "L", DecimalPlaces = 2 },
                new Unit { CompanyId = companyId, Name = "Kilogramo", Abbreviation = "kg", DecimalPlaces = 3 },
                new Unit { CompanyId = companyId, Name = "Caja", Abbreviation = "cja", DecimalPlaces = 0 },
                new Unit { CompanyId = companyId, Name = "Hora", Abbreviation = "h", DecimalPlaces = 2 });

            logger.LogInformation("Unidades de medida iniciales creadas.");
        }

        if (!await db.Categories.AnyAsync())
        {
            db.Categories.AddRange(
                new Category { CompanyId = companyId, Name = "Producto terminado", Description = "Artículos listos para la venta." },
                new Category { CompanyId = companyId, Name = "Materia prima", Description = "Insumos que se transforman en producción." },
                new Category { CompanyId = companyId, Name = "Insumos y consumibles", Description = "Material de apoyo y consumo." },
                new Category { CompanyId = companyId, Name = "Servicios", Description = "Servicios facturables." });

            logger.LogInformation("Categorías iniciales creadas.");
        }

        await db.SaveChangesAsync();
    }
}
