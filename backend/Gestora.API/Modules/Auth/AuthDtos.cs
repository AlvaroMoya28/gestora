using System.ComponentModel.DataAnnotations;

namespace Gestora.API.Modules.Auth;

public class LoginRequest
{
    [Required(ErrorMessage = "El correo es obligatorio.")]
    [EmailAddress(ErrorMessage = "El correo no tiene un formato válido.")]
    public string Email { get; set; } = string.Empty;

    [Required(ErrorMessage = "La contraseña es obligatoria.")]
    public string Password { get; set; } = string.Empty;
}

public class RefreshRequest
{
    [Required] public string RefreshToken { get; set; } = string.Empty;
}

public class ChangePasswordRequest
{
    [Required] public string CurrentPassword { get; set; } = string.Empty;

    [Required]
    [MinLength(8, ErrorMessage = "La nueva contraseña debe tener al menos 8 caracteres.")]
    public string NewPassword { get; set; } = string.Empty;
}

/// <summary>Permiso efectivo del usuario sobre un módulo, ya resuelto desde su rol.</summary>
public record ModulePermissionDto(string Key, string Name, string Group, string Icon, bool Available, bool CanWrite);

public record AuthenticatedUserDto(
    int Id,
    string FirstName,
    string LastName,
    string FullName,
    string Email,
    string? Phone,
    int RoleId,
    /// <summary>Clave estable del rol; el frontend decide con esto, no con el nombre.</summary>
    string RoleKey,
    string RoleName,
    /// <summary>"Platform" o "Company": determina qué shell y qué menú se dibuja.</summary>
    string Scope,
    /// <summary>Empresa que se está viendo. 0 cuando el usuario está en la plataforma.</summary>
    int CompanyId,
    string? CompanyName,
    string Currency,
    /// <summary>El desarrollador está viendo el sistema como esta empresa.</summary>
    bool IsImpersonating,
    IReadOnlyList<ModulePermissionDto> Modules);

public record AuthResponse(string AccessToken, string RefreshToken, int ExpiresInSeconds, AuthenticatedUserDto User);
