using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>
/// Dónde vive un rol. Es la separación central del SaaS: quien administra Gestora
/// no es un usuario de ninguna empresa, y quien usa una empresa no ve la plataforma.
/// </summary>
public enum RoleScope
{
    /// <summary>Gestora como producto: empresas, suscripciones y cobros.</summary>
    Platform = 0,
    /// <summary>Dentro de una empresa suscrita: su operación diaria.</summary>
    Company = 1
}

public enum CompanyStatus
{
    /// <summary>Registrada y operando con normalidad.</summary>
    Active = 0,
    /// <summary>Suspendida por falta de pago o a petición. No puede iniciar sesión.</summary>
    Suspended = 1,
    /// <summary>Baja definitiva. Se conserva el historial.</summary>
    Cancelled = 2
}

/// <summary>
/// Empresa suscrita. Todo el sistema operativo gira alrededor de ella: sus usuarios,
/// su catálogo, su inventario y sus finanzas quedan aislados de las demás empresas.
/// </summary>
public class Company : BaseEntity
{
    [MaxLength(150)] public string Name { get; set; } = string.Empty;
    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }

    /// <summary>
    /// Correo de la cuenta: el que se le entrega a la empresa al suscribirse y con el
    /// que entra su usuario administrador. Es el identificador comercial de la cuenta.
    /// </summary>
    [MaxLength(150)] public string AccountEmail { get; set; } = string.Empty;

    [MaxLength(250)] public string? Address { get; set; }
    /// <summary>Código ISO de la moneda con la que opera la empresa (CRC, USD, ...).</summary>
    [MaxLength(3)] public string Currency { get; set; } = "CRC";
    /// <summary>Porcentaje de impuesto por defecto para documentos nuevos.</summary>
    public decimal DefaultTaxRate { get; set; } = 13m;

    public CompanyStatus Status { get; set; } = CompanyStatus.Active;
    [MaxLength(500)] public string? Notes { get; set; }

    public ICollection<Subscription> Subscriptions { get; set; } = new List<Subscription>();

    public bool IsActive => Status == CompanyStatus.Active;
}

/// <summary>
/// Catálogo fijo de roles, común a toda la plataforma. No se crean roles por empresa:
/// cada empresa recibe exactamente dos accesos (administrador y consulta), y por
/// encima de todas están los roles de plataforma.
/// </summary>
public class Role : BaseEntity
{
    /// <summary>Clave estable usada en código. Ver <see cref="Common.RoleKeys"/>.</summary>
    [MaxLength(40)] public string Key { get; set; } = string.Empty;
    [MaxLength(60)] public string Name { get; set; } = string.Empty;
    [MaxLength(200)] public string? Description { get; set; }
    public RoleScope Scope { get; set; }
    /// <summary>Orden de presentación en los selectores.</summary>
    public int SortOrder { get; set; }
    public bool IsActive { get; set; } = true;

    public ICollection<RolePermission> Permissions { get; set; } = new List<RolePermission>();
}

/// <summary>
/// Permiso de un rol sobre un módulo. La lista de módulos válidos vive en
/// <see cref="Common.ModuleCatalog"/>, que es la única fuente de verdad y alimenta
/// tanto el menú del frontend como la autorización de los endpoints.
/// </summary>
public class RolePermission : BaseEntity
{
    public int RoleId { get; set; }
    public Role Role { get; set; } = null!;
    [MaxLength(50)] public string ModuleKey { get; set; } = string.Empty;
    public bool CanRead { get; set; }
    public bool CanWrite { get; set; }
}

public class User : BaseEntity
{
    /// <summary>
    /// Empresa a la que pertenece. <c>null</c> en los usuarios de plataforma
    /// (desarrollador y administración de Gestora), que no son de ninguna empresa.
    /// </summary>
    public int? CompanyId { get; set; }
    public Company? Company { get; set; }

    [MaxLength(100)] public string FirstName { get; set; } = string.Empty;
    [MaxLength(150)] public string LastName { get; set; } = string.Empty;
    [MaxLength(150)] public string Email { get; set; } = string.Empty;
    [MaxLength(200)] public string PasswordHash { get; set; } = string.Empty;
    /// <summary>Parámetros del PBKDF2 en formato <c>PBKDF2$iteraciones$salt</c>.</summary>
    [MaxLength(150)] public string PasswordSalt { get; set; } = string.Empty;
    [MaxLength(30)] public string? Phone { get; set; }
    public bool IsActive { get; set; } = true;

    public int RoleId { get; set; }
    public Role Role { get; set; } = null!;

    public DateTime? LastLoginAt { get; set; }
    public int FailedLoginAttempts { get; set; }
    public DateTime? LockedUntil { get; set; }

    public ICollection<RefreshToken> RefreshTokens { get; set; } = new List<RefreshToken>();

    public string FullName => $"{FirstName} {LastName}".Trim();
}

/// <summary>
/// Refresh token con rotación: cada uso revoca el anterior. Se guarda en tabla propia
/// (y no como columna del usuario, como hacía ADIC) para permitir varias sesiones
/// simultáneas y poder cerrarlas de forma individual.
/// </summary>
public class RefreshToken : BaseEntity
{
    public int UserId { get; set; }
    public User User { get; set; } = null!;
    [MaxLength(200)] public string Token { get; set; } = string.Empty;
    public DateTime ExpiresAt { get; set; }
    public DateTime? RevokedAt { get; set; }
    [MaxLength(60)] public string? CreatedByIp { get; set; }

    /// <summary>
    /// Empresa que el desarrollador está viendo con esta sesión. Se guarda acá para que
    /// al renovar el token no se pierda la vista en la que estaba trabajando.
    /// </summary>
    public int? ImpersonatedCompanyId { get; set; }

    public bool IsActive => RevokedAt is null && DateTime.UtcNow < ExpiresAt;
}

/// <summary>
/// Registro inmutable de operaciones sensibles (dinero, inventario, accesos).
/// <c>CompanyId = 0</c> identifica las acciones de plataforma, que no pertenecen
/// a ninguna empresa (altas de empresas, cobros, simulaciones del desarrollador).
/// </summary>
public class AuditLog : TenantEntity
{
    public int? UserId { get; set; }
    [MaxLength(200)] public string UserName { get; set; } = string.Empty;
    [MaxLength(50)] public string Action { get; set; } = string.Empty;
    [MaxLength(50)] public string Module { get; set; } = string.Empty;
    [MaxLength(80)] public string? EntityName { get; set; }
    public int? EntityId { get; set; }
    [MaxLength(500)] public string? Description { get; set; }
    [MaxLength(1000)] public string? OldValue { get; set; }
    [MaxLength(1000)] public string? NewValue { get; set; }
    [MaxLength(60)] public string? IpAddress { get; set; }
    public DateTime OccurredAt { get; set; } = DateTime.UtcNow;
}
