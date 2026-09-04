using Gestora.API.Common;
using Gestora.API.Domain;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Data;

public class GestoraDbContext : DbContext
{
    private readonly ICurrentUser? _currentUser;

    public GestoraDbContext(DbContextOptions<GestoraDbContext> options, ICurrentUser? currentUser = null)
        : base(options)
    {
        _currentUser = currentUser;
        TenantId = currentUser?.CompanyId ?? 0;
    }

    /// <summary>
    /// Empresa activa para esta unidad de trabajo. Se toma del JWT; los procesos sin
    /// petición HTTP (seed, tareas) deben asignarla explícitamente.
    /// Todas las consultas sobre entidades multiempresa se filtran por este valor.
    /// </summary>
    public int TenantId { get; set; }

    public DbSet<Company> Companies => Set<Company>();
    public DbSet<Role> Roles => Set<Role>();
    public DbSet<RolePermission> RolePermissions => Set<RolePermission>();
    public DbSet<User> Users => Set<User>();
    public DbSet<RefreshToken> RefreshTokens => Set<RefreshToken>();
    public DbSet<AuditLog> AuditLogs => Set<AuditLog>();

    public DbSet<Category> Categories => Set<Category>();
    public DbSet<Unit> Units => Set<Unit>();
    public DbSet<Customer> Customers => Set<Customer>();
    public DbSet<Supplier> Suppliers => Set<Supplier>();
    public DbSet<Product> Products => Set<Product>();
    public DbSet<InventoryMovement> InventoryMovements => Set<InventoryMovement>();

    protected override void OnModelCreating(ModelBuilder b)
    {
        base.OnModelCreating(b);

        // --- Aislamiento multiempresa -------------------------------------------------
        // Filtro global: ninguna consulta puede ver datos de otra empresa aunque se
        // olvide el Where. Es la garantía estructural del futuro SaaS.
        b.Entity<Role>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<User>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<AuditLog>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Category>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Unit>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Customer>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Supplier>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Product>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<InventoryMovement>().HasQueryFilter(e => e.CompanyId == TenantId);
        // Las entidades dependientes de User heredan el filtro por su principal.
        b.Entity<RefreshToken>().HasQueryFilter(e => e.User.CompanyId == TenantId);
        b.Entity<RolePermission>().HasQueryFilter(e => e.Role.CompanyId == TenantId);

        // --- Dinero y cantidades ------------------------------------------------------
        // decimal explícito: nunca float/double para importes.
        foreach (var property in b.Model.GetEntityTypes()
                     .SelectMany(t => t.GetProperties())
                     .Where(p => p.ClrType == typeof(decimal) || p.ClrType == typeof(decimal?)))
        {
            var isQuantity = property.Name is "Quantity" or "Stock" or "MinStock" or "StockAfter";
            property.SetColumnType(isQuantity ? "decimal(18,4)" : "decimal(18,2)");
        }

        // --- Índices y unicidad por empresa -------------------------------------------
        b.Entity<User>().HasIndex(u => new { u.CompanyId, u.Email }).IsUnique();
        b.Entity<User>().HasOne(u => u.Role).WithMany().HasForeignKey(u => u.RoleId)
            .OnDelete(DeleteBehavior.Restrict);

        b.Entity<Role>().HasIndex(r => new { r.CompanyId, r.Name }).IsUnique();
        b.Entity<Role>().HasMany(r => r.Permissions).WithOne(p => p.Role)
            .HasForeignKey(p => p.RoleId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<RolePermission>().HasIndex(p => new { p.RoleId, p.ModuleKey }).IsUnique();

        b.Entity<RefreshToken>().HasIndex(t => t.Token).IsUnique();
        b.Entity<RefreshToken>().HasOne(t => t.User).WithMany(u => u.RefreshTokens)
            .HasForeignKey(t => t.UserId).OnDelete(DeleteBehavior.Cascade);

        b.Entity<Customer>().HasIndex(c => new { c.CompanyId, c.Code }).IsUnique();
        b.Entity<Supplier>().HasIndex(s => new { s.CompanyId, s.Code }).IsUnique();
        b.Entity<Product>().HasIndex(p => new { p.CompanyId, p.Code }).IsUnique();
        b.Entity<Product>().HasIndex(p => new { p.CompanyId, p.Barcode });
        b.Entity<Category>().HasIndex(c => new { c.CompanyId, c.Name }).IsUnique();
        b.Entity<Unit>().HasIndex(u => new { u.CompanyId, u.Abbreviation }).IsUnique();

        b.Entity<Product>().HasOne(p => p.Category).WithMany().HasForeignKey(p => p.CategoryId)
            .OnDelete(DeleteBehavior.SetNull);
        b.Entity<Product>().HasOne(p => p.Unit).WithMany().HasForeignKey(p => p.UnitId)
            .OnDelete(DeleteBehavior.Restrict);

        b.Entity<InventoryMovement>().HasIndex(m => new { m.CompanyId, m.ProductId, m.OccurredAt });
        b.Entity<InventoryMovement>().HasOne(m => m.Product).WithMany().HasForeignKey(m => m.ProductId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<InventoryMovement>().HasOne(m => m.User).WithMany().HasForeignKey(m => m.UserId)
            .OnDelete(DeleteBehavior.SetNull);

        b.Entity<AuditLog>().HasIndex(a => new { a.CompanyId, a.OccurredAt });
    }

    public override Task<int> SaveChangesAsync(CancellationToken cancellationToken = default)
    {
        StampAuditFields();
        return base.SaveChangesAsync(cancellationToken);
    }

    public override int SaveChanges()
    {
        StampAuditFields();
        return base.SaveChanges();
    }

    /// <summary>Rellena empresa y campos de auditoría sin que cada servicio los repita.</summary>
    private void StampAuditFields()
    {
        var userId = _currentUser?.IsAuthenticated == true ? _currentUser.UserId : (int?)null;
        var now = DateTime.UtcNow;

        foreach (var entry in ChangeTracker.Entries<BaseEntity>())
        {
            if (entry.State == EntityState.Added)
            {
                entry.Entity.CreatedAt = now;
                entry.Entity.CreatedByUserId ??= userId;

                if (entry.Entity is ITenantEntity tenant && tenant.CompanyId == 0)
                    tenant.CompanyId = TenantId;
            }
            else if (entry.State == EntityState.Modified)
            {
                entry.Entity.UpdatedAt = now;
                entry.Entity.UpdatedByUserId = userId;
            }
        }
    }
}
