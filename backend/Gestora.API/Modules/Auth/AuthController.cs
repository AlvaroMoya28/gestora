using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Auth;

/// <summary>
/// Endpoints de sesión. El controlador es delgado a propósito: valida el modelo,
/// delega en <see cref="AuthService"/> y traduce el resultado a HTTP.
/// </summary>
[ApiController]
[Route("api/auth")]
public class AuthController(AuthService auth) : ControllerBase
{
    [HttpPost("login")]
    [AllowAnonymous]
    public async Task<ActionResult<AuthResponse>> Login([FromBody] LoginRequest request)
        => Ok(await auth.LoginAsync(request));

    [HttpPost("refresh")]
    [AllowAnonymous]
    public async Task<ActionResult<AuthResponse>> Refresh([FromBody] RefreshRequest request)
        => Ok(await auth.RefreshAsync(request.RefreshToken));

    [HttpPost("logout")]
    [AllowAnonymous]
    public async Task<IActionResult> Logout([FromBody] RefreshRequest request)
    {
        await auth.LogoutAsync(request.RefreshToken);
        return NoContent();
    }

    /// <summary>Devuelve el usuario y sus módulos permitidos; el frontend lo usa al recargar.</summary>
    [HttpGet("me")]
    [Authorize]
    public async Task<ActionResult<AuthenticatedUserDto>> Me()
        => Ok(await auth.GetProfileAsync());

    [HttpPost("change-password")]
    [Authorize]
    public async Task<IActionResult> ChangePassword([FromBody] ChangePasswordRequest request)
    {
        await auth.ChangePasswordAsync(request);
        return NoContent();
    }

    /// <summary>Catálogo completo de módulos, para las pantallas de administración.</summary>
    [HttpGet("modules")]
    [Authorize]
    public ActionResult<IEnumerable<object>> Modules()
        => Ok(ModuleCatalog.Modules.Select(m => new { m.Key, m.Name, m.Group, m.Icon, m.Available, Scope = m.Scope.ToString() }));

    /// <summary>
    /// Cambia la vista del desarrollador a la de una empresa. Devuelve una sesión nueva:
    /// a partir de ahí ve exactamente lo que ve esa empresa.
    /// </summary>
    [HttpPost("ver-como/{companyId:int}")]
    [Authorize]
    [RequireDeveloper]
    public async Task<ActionResult<AuthResponse>> Impersonate(int companyId)
        => Ok(await auth.ImpersonateAsync(companyId));

    /// <summary>Vuelve el desarrollador a su vista de plataforma.</summary>
    [HttpPost("volver-a-plataforma")]
    [Authorize]
    [RequireDeveloper]
    public async Task<ActionResult<AuthResponse>> StopImpersonating()
        => Ok(await auth.StopImpersonatingAsync());
}
