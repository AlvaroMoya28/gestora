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

    /// <summary>Catálogo completo de módulos, para la pantalla de administración de roles.</summary>
    [HttpGet("modules")]
    [Authorize]
    public ActionResult<IEnumerable<object>> Modules()
        => Ok(ModuleCatalog.Modules.Select(m => new { m.Key, m.Name, m.Group, m.Icon, m.Available }));
}
