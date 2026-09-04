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
    string RoleName,
    int CompanyId,
    string CompanyName,
    string Currency,
    IReadOnlyList<ModulePermissionDto> Modules);

public record AuthResponse(string AccessToken, string RefreshToken, int ExpiresInSeconds, AuthenticatedUserDto User);
