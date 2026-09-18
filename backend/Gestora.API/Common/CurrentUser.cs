using System.Security.Claims;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Filters;

namespace Gestora.API.Common;

/// <summary>Datos del usuario autenticado leídos del JWT. Se inyecta donde haga falta.</summary>
public interface ICurrentUser
{
    bool IsAuthenticated { get; }
    int UserId { get; }
    /// <summary>Empresa activa. 0 cuando el usuario es de plataforma y no está viendo ninguna.</summary>
    int CompanyId { get; }
    string Email { get; }
    string FullName { get; }
    string RoleKey { get; }
    string? IpAddress { get; }

    /// <summary>Usuario de Gestora (desarrollador o administración), no de una empresa.</summary>
    bool IsPlatformUser { get; }
    bool IsDeveloper { get; }
    /// <summary>El desarrollador está viendo el sistema como una empresa.</summary>
    bool IsImpersonating { get; }

    bool CanRead(string moduleKey);
    bool CanWrite(string moduleKey);
}

public class CurrentUser(IHttpContextAccessor accessor) : ICurrentUser
{
    public const string ClaimCompanyId = "company_id";
    public const string ClaimPermission = "perm";
    public const string ClaimFullName = "name";
    public const string ClaimRoleKey = "role_key";
    /// <summary>Presente solo mientras el desarrollador ve el sistema como una empresa.</summary>
    public const string ClaimImpersonating = "impersonating";

    private ClaimsPrincipal? Principal => accessor.HttpContext?.User;

    public bool IsAuthenticated => Principal?.Identity?.IsAuthenticated == true;

    public int UserId => int.TryParse(Principal?.FindFirstValue(ClaimTypes.NameIdentifier), out var id) ? id : 0;

    public int CompanyId => int.TryParse(Principal?.FindFirstValue(ClaimCompanyId), out var id) ? id : 0;

    public string Email => Principal?.FindFirstValue(ClaimTypes.Email) ?? string.Empty;

    public string FullName => Principal?.FindFirstValue(ClaimFullName) ?? Email;

    public string RoleKey => Principal?.FindFirstValue(ClaimRoleKey) ?? string.Empty;

    public string? IpAddress => accessor.HttpContext?.Connection.RemoteIpAddress?.ToString();

    public bool IsPlatformUser => RoleKeys.IsPlatform(RoleKey);

    public bool IsDeveloper => RoleKey == RoleKeys.Developer;

    public bool IsImpersonating => Principal?.FindFirstValue(ClaimImpersonating) == "1";

    /// <summary>Los permisos viajan en el token como "modulo:r" y "modulo:w".</summary>
    public bool CanRead(string moduleKey) => HasClaim($"{moduleKey}:r") || CanWrite(moduleKey);

    public bool CanWrite(string moduleKey) => HasClaim($"{moduleKey}:w");

    private bool HasClaim(string value) =>
        Principal?.HasClaim(c => c.Type == ClaimPermission && c.Value == value) == true;
}

/// <summary>
/// Exige permiso sobre un módulo del <see cref="ModuleCatalog"/>.
/// Sustituye a las políticas por número de rol: agregar un rol nuevo ya no
/// obliga a tocar los controladores.
/// </summary>
[AttributeUsage(AttributeTargets.Class | AttributeTargets.Method)]
public class RequireModuleAttribute(string moduleKey, bool write = false) : Attribute, IAuthorizationFilter
{
    public void OnAuthorization(AuthorizationFilterContext context)
    {
        var current = context.HttpContext.RequestServices.GetRequiredService<ICurrentUser>();

        if (!current.IsAuthenticated)
        {
            context.Result = new UnauthorizedObjectResult(new ApiErrorResponse("Sesión no válida o expirada."));
            return;
        }

        var allowed = write ? current.CanWrite(moduleKey) : current.CanRead(moduleKey);
        if (!allowed)
        {
            context.Result = new ObjectResult(new ApiErrorResponse("No tiene permisos para esta operación."))
            {
                StatusCode = StatusCodes.Status403Forbidden
            };
        }
    }
}

/// <summary>
/// Restringe una acción al desarrollador de Gestora. Se usa en lo que no debe poder
/// hacer ni siquiera administración de la plataforma, como entrar a ver una empresa.
/// </summary>
[AttributeUsage(AttributeTargets.Class | AttributeTargets.Method)]
public class RequireDeveloperAttribute : Attribute, IAuthorizationFilter
{
    public void OnAuthorization(AuthorizationFilterContext context)
    {
        var current = context.HttpContext.RequestServices.GetRequiredService<ICurrentUser>();

        if (!current.IsAuthenticated)
        {
            context.Result = new UnauthorizedObjectResult(new ApiErrorResponse("Sesión no válida o expirada."));
            return;
        }

        if (!current.IsDeveloper)
        {
            context.Result = new ObjectResult(new ApiErrorResponse("Esta operación es exclusiva del desarrollador."))
            {
                StatusCode = StatusCodes.Status403Forbidden
            };
        }
    }
}
