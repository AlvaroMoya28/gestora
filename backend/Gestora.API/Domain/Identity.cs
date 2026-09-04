using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Domain;

/// <summary>Empresa inquilina. Hoy hay una sola; el modelo ya soporta varias.</summary>
public class Company : BaseEntity
{
    [MaxLength(150)] public string Name { get; set; } = string.Empty;
    [MaxLength(50)] public string? TaxId { get; set; }
    [MaxLength(30)] public string? Phone { get; set; }
    [MaxLength(150)] public string? Email { get; set; }
    [MaxLength(250)] public string? Address { get; set; }
    /// <summary>Código ISO de la moneda con la que opera la empresa (CRC, USD, ...).</summary>
    [MaxLength(3)] public string Currency { get; set; } = "CRC";
    /// <summary>Porcentaje de impuesto por defecto para documentos nuevos.</summary>
    public decimal DefaultTaxRate { get; set; } = 13m;
    public bool IsActive { get; set; } = true;
}

public class Role : TenantEntity
{
    [MaxLength(60)] public string Name { get; set; } = string.Empty;
    [MaxLength(200)] public string? Description { get; set; }
    /// <summary>Los roles de sistema (Administrador) no se pueden borrar ni dejar sin permisos.</summary>
    public bool IsSystem { get; set; }
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

public class User : TenantEntity
{
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

    public bool IsActive => RevokedAt is null && DateTime.UtcNow < ExpiresAt;
}

/// <summary>Registro inmutable de operaciones sensibles (dinero, inventario, accesos).</summary>
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
