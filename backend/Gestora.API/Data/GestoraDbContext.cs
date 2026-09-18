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

    public DbSet<Purchase> Purchases => Set<Purchase>();
    public DbSet<PurchaseItem> PurchaseItems => Set<PurchaseItem>();
    public DbSet<AccountPayable> AccountsPayable => Set<AccountPayable>();
    public DbSet<Payment> Payments => Set<Payment>();

    public DbSet<Sale> Sales => Set<Sale>();
    public DbSet<SaleItem> SaleItems => Set<SaleItem>();
    public DbSet<AccountReceivable> AccountsReceivable => Set<AccountReceivable>();
    public DbSet<Receipt> Receipts => Set<Receipt>();

    public DbSet<Recipe> Recipes => Set<Recipe>();
    public DbSet<RecipeItem> RecipeItems => Set<RecipeItem>();
    public DbSet<ProductionOrder> ProductionOrders => Set<ProductionOrder>();
    public DbSet<ProductionMaterial> ProductionMaterials => Set<ProductionMaterial>();

    public DbSet<RepairOrder> RepairOrders => Set<RepairOrder>();
    public DbSet<RepairMaterial> RepairMaterials => Set<RepairMaterial>();

    public DbSet<FinanceCategory> FinanceCategories => Set<FinanceCategory>();
    public DbSet<FinanceEntry> FinanceEntries => Set<FinanceEntry>();

    public DbSet<Plan> Plans => Set<Plan>();
    public DbSet<Subscription> Subscriptions => Set<Subscription>();
    public DbSet<SubscriptionPayment> SubscriptionPayments => Set<SubscriptionPayment>();

    protected override void OnModelCreating(ModelBuilder b)
    {
        base.OnModelCreating(b);

        // --- Aislamiento multiempresa -------------------------------------------------
        // Filtro global: ninguna consulta puede ver datos de otra empresa aunque se
        // olvide el Where. Es la garantía estructural del SaaS.
        //
        // Los usuarios de plataforma (desarrollador y administración de Gestora) no
        // pertenecen a ninguna empresa: su TenantId es 0 y solo se ven entre ellos.
        // Cuando el desarrollador entra a ver una empresa, su token lleva el CompanyId
        // de esa empresa y desde ese momento ve exactamente lo mismo que ella.
        b.Entity<User>().HasQueryFilter(e =>
            e.CompanyId == TenantId || (TenantId == 0 && e.CompanyId == null));
        b.Entity<AuditLog>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Category>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Unit>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Customer>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Supplier>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Product>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<InventoryMovement>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Purchase>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<AccountPayable>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Sale>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<AccountReceivable>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<Recipe>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<ProductionOrder>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<RepairOrder>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<FinanceCategory>().HasQueryFilter(e => e.CompanyId == TenantId);
        b.Entity<FinanceEntry>().HasQueryFilter(e => e.CompanyId == TenantId);
        // Las entidades dependientes heredan el filtro por su principal.
        b.Entity<RefreshToken>().HasQueryFilter(e =>
            e.User.CompanyId == TenantId || (TenantId == 0 && e.User.CompanyId == null));
        b.Entity<PurchaseItem>().HasQueryFilter(e => e.Purchase.CompanyId == TenantId);
        b.Entity<Payment>().HasQueryFilter(e => e.AccountPayable.CompanyId == TenantId);
        b.Entity<SaleItem>().HasQueryFilter(e => e.Sale.CompanyId == TenantId);
        b.Entity<Receipt>().HasQueryFilter(e => e.AccountReceivable.CompanyId == TenantId);
        b.Entity<RecipeItem>().HasQueryFilter(e => e.Recipe.CompanyId == TenantId);
        b.Entity<ProductionMaterial>().HasQueryFilter(e => e.ProductionOrder.CompanyId == TenantId);
        b.Entity<RepairMaterial>().HasQueryFilter(e => e.RepairOrder.CompanyId == TenantId);

        // Roles, planes y suscripciones son de la plataforma, no de una empresa:
        // no llevan filtro. El acceso se controla por permisos de módulo.

        // --- Dinero y cantidades ------------------------------------------------------
        // decimal explícito: nunca float/double para importes.
        foreach (var property in b.Model.GetEntityTypes()
                     .SelectMany(t => t.GetProperties())
                     .Where(p => p.ClrType == typeof(decimal) || p.ClrType == typeof(decimal?)))
        {
            var isQuantity = property.Name is "Quantity" or "Stock" or "MinStock" or "StockAfter"
                or "PlannedQuantity" or "ConsumedQuantity" or "ProducedQuantity" or "OutputQuantity";
            property.SetColumnType(isQuantity ? "decimal(18,4)" : "decimal(18,2)");
        }

        // --- Identidad ----------------------------------------------------------------
        // El correo es único en toda la plataforma, no por empresa: es con lo que se
        // inicia sesión, y el login no sabe todavía a qué empresa pertenece quien entra.
        b.Entity<User>().HasIndex(u => u.Email).IsUnique();
        b.Entity<User>().HasOne(u => u.Role).WithMany().HasForeignKey(u => u.RoleId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<User>().HasOne(u => u.Company).WithMany().HasForeignKey(u => u.CompanyId)
            .OnDelete(DeleteBehavior.Restrict);

        b.Entity<Role>().HasIndex(r => r.Key).IsUnique();
        b.Entity<Role>().HasMany(r => r.Permissions).WithOne(p => p.Role)
            .HasForeignKey(p => p.RoleId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<RolePermission>().HasIndex(p => new { p.RoleId, p.ModuleKey }).IsUnique();

        b.Entity<Company>().HasIndex(c => c.AccountEmail).IsUnique();

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

        // --- Compras y cuentas por pagar ----------------------------------------------
        b.Entity<Purchase>().HasIndex(p => new { p.CompanyId, p.Number }).IsUnique();
        b.Entity<Purchase>().HasIndex(p => new { p.CompanyId, p.Status, p.Date });
        b.Entity<Purchase>().HasOne(p => p.Supplier).WithMany().HasForeignKey(p => p.SupplierId)
            .OnDelete(DeleteBehavior.Restrict);
        // Las líneas no existen sin su documento: se borran con él mientras es borrador.
        b.Entity<Purchase>().HasMany(p => p.Items).WithOne(i => i.Purchase)
            .HasForeignKey(i => i.PurchaseId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<PurchaseItem>().HasOne(i => i.Product).WithMany().HasForeignKey(i => i.ProductId)
            .OnDelete(DeleteBehavior.Restrict);

        b.Entity<AccountPayable>().HasIndex(a => new { a.CompanyId, a.Status, a.DueDate });
        b.Entity<AccountPayable>().HasOne(a => a.Supplier).WithMany().HasForeignKey(a => a.SupplierId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<AccountPayable>().HasOne(a => a.Purchase).WithMany().HasForeignKey(a => a.PurchaseId)
            .OnDelete(DeleteBehavior.SetNull);
        b.Entity<AccountPayable>().HasMany(a => a.Payments).WithOne(p => p.AccountPayable)
            .HasForeignKey(p => p.AccountPayableId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<Payment>().HasOne(p => p.User).WithMany().HasForeignKey(p => p.UserId)
            .OnDelete(DeleteBehavior.SetNull);

        // --- Ventas y cuentas por cobrar ----------------------------------------------
        b.Entity<Sale>().HasIndex(s => new { s.CompanyId, s.Number }).IsUnique();
        b.Entity<Sale>().HasIndex(s => new { s.CompanyId, s.Status, s.Date });
        b.Entity<Sale>().HasOne(s => s.Customer).WithMany().HasForeignKey(s => s.CustomerId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<Sale>().HasMany(s => s.Items).WithOne(i => i.Sale)
            .HasForeignKey(i => i.SaleId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<SaleItem>().HasOne(i => i.Product).WithMany().HasForeignKey(i => i.ProductId)
            .OnDelete(DeleteBehavior.Restrict);

        b.Entity<AccountReceivable>().HasIndex(a => new { a.CompanyId, a.Status, a.DueDate });
        b.Entity<AccountReceivable>().HasOne(a => a.Customer).WithMany().HasForeignKey(a => a.CustomerId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<AccountReceivable>().HasOne(a => a.Sale).WithMany().HasForeignKey(a => a.SaleId)
            .OnDelete(DeleteBehavior.SetNull);
        b.Entity<AccountReceivable>().HasOne(a => a.RepairOrder).WithMany().HasForeignKey(a => a.RepairOrderId)
            .OnDelete(DeleteBehavior.SetNull);
        b.Entity<AccountReceivable>().HasMany(a => a.Receipts).WithOne(r => r.AccountReceivable)
            .HasForeignKey(r => r.AccountReceivableId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<Receipt>().HasOne(r => r.User).WithMany().HasForeignKey(r => r.UserId)
            .OnDelete(DeleteBehavior.SetNull);

        // --- Producción ---------------------------------------------------------------
        b.Entity<Recipe>().HasIndex(r => new { r.CompanyId, r.ProductId });
        b.Entity<Recipe>().HasOne(r => r.Product).WithMany().HasForeignKey(r => r.ProductId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<Recipe>().HasMany(r => r.Items).WithOne(i => i.Recipe)
            .HasForeignKey(i => i.RecipeId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<RecipeItem>().HasOne(i => i.Product).WithMany().HasForeignKey(i => i.ProductId)
            .OnDelete(DeleteBehavior.Restrict);

        b.Entity<ProductionOrder>().HasIndex(o => new { o.CompanyId, o.Number }).IsUnique();
        b.Entity<ProductionOrder>().HasIndex(o => new { o.CompanyId, o.Status, o.Date });
        b.Entity<ProductionOrder>().HasOne(o => o.Product).WithMany().HasForeignKey(o => o.ProductId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<ProductionOrder>().HasOne(o => o.Recipe).WithMany().HasForeignKey(o => o.RecipeId)
            .OnDelete(DeleteBehavior.SetNull);
        b.Entity<ProductionOrder>().HasMany(o => o.Materials).WithOne(m => m.ProductionOrder)
            .HasForeignKey(m => m.ProductionOrderId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<ProductionMaterial>().HasOne(m => m.Product).WithMany().HasForeignKey(m => m.ProductId)
            .OnDelete(DeleteBehavior.Restrict);

        // --- Reparaciones -------------------------------------------------------------
        b.Entity<RepairOrder>().HasIndex(r => new { r.CompanyId, r.Number }).IsUnique();
        b.Entity<RepairOrder>().HasIndex(r => new { r.CompanyId, r.Status, r.ReceivedAt });
        b.Entity<RepairOrder>().HasOne(r => r.Customer).WithMany().HasForeignKey(r => r.CustomerId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<RepairOrder>().HasMany(r => r.Materials).WithOne(m => m.RepairOrder)
            .HasForeignKey(m => m.RepairOrderId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<RepairMaterial>().HasOne(m => m.Product).WithMany().HasForeignKey(m => m.ProductId)
            .OnDelete(DeleteBehavior.Restrict);

        // --- Ingresos y gastos --------------------------------------------------------
        b.Entity<FinanceCategory>().HasIndex(c => new { c.CompanyId, c.Kind, c.Name }).IsUnique();
        b.Entity<FinanceEntry>().HasIndex(e => new { e.CompanyId, e.Kind, e.Date });
        b.Entity<FinanceEntry>().HasIndex(e => new { e.CompanyId, e.Source, e.SourceId });
        b.Entity<FinanceEntry>().HasOne(e => e.Category).WithMany().HasForeignKey(e => e.CategoryId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<FinanceEntry>().HasOne(e => e.User).WithMany().HasForeignKey(e => e.UserId)
            .OnDelete(DeleteBehavior.SetNull);

        // --- Planes, suscripciones y cobros de Gestora --------------------------------
        b.Entity<Subscription>().HasOne(s => s.Company).WithMany(c => c.Subscriptions)
            .HasForeignKey(s => s.CompanyId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<Subscription>().HasOne(s => s.Plan).WithMany().HasForeignKey(s => s.PlanId)
            .OnDelete(DeleteBehavior.Restrict);
        b.Entity<Subscription>().HasMany(s => s.Payments).WithOne(p => p.Subscription)
            .HasForeignKey(p => p.SubscriptionId).OnDelete(DeleteBehavior.Cascade);
        b.Entity<Subscription>().HasIndex(s => new { s.Status, s.EndDate });
        b.Entity<SubscriptionPayment>().HasOne(p => p.User).WithMany().HasForeignKey(p => p.UserId)
            .OnDelete(DeleteBehavior.SetNull);
        b.Entity<Plan>().HasIndex(p => p.Name).IsUnique();
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
