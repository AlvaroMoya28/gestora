using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;

namespace Gestora.API.Modules.Auth;

public class JwtOptions
{
    public string Key { get; set; } = string.Empty;
    public string Issuer { get; set; } = "Gestora";
    public string Audience { get; set; } = "Gestora";
    public int AccessTokenMinutes { get; set; } = 60;
    public int RefreshTokenDays { get; set; } = 7;
}

/// <summary>
/// Autenticación: emisión de tokens, rotación de refresh tokens, bloqueo temporal tras
/// intentos fallidos y el cambio de vista del desarrollador ("ver como empresa").
///
/// La sesión resuelve dos cosas distintas: <b>quién</b> es el usuario (siempre el mismo)
/// y <b>qué empresa</b> está viendo. Para un usuario de empresa las dos coinciden; para
/// el desarrollador, la segunda puede cambiar sin que cambie la primera.
/// </summary>
public class AuthService(
    GestoraDbContext db,
    IAuditService audit,
    ICurrentUser current,
    IConfiguration config,
    ILogger<AuthService> logger)
{
    private const int MaxFailedAttempts = 5;
    private static readonly TimeSpan LockDuration = TimeSpan.FromMinutes(15);

    private JwtOptions Jwt => config.GetSection("Jwt").Get<JwtOptions>()
        ?? throw new InvalidOperationException("Falta la sección Jwt en la configuración.");

    public async Task<AuthResponse> LoginAsync(LoginRequest request)
    {
        var email = request.Email.Trim().ToLowerInvariant();

        // IgnoreQueryFilters: en el login todavía no se conoce la empresa del usuario.
        var user = await db.Users
            .IgnoreQueryFilters()
            .Include(u => u.Role).ThenInclude(r => r.Permissions)
            .FirstOrDefaultAsync(u => u.Email == email);

        // Mensaje idéntico para usuario inexistente y contraseña incorrecta: no se
        // revela qué correos están registrados.
        const string invalid = "Correo o contraseña incorrectos.";

        if (user is null)
        {
            logger.LogWarning("Login fallido para correo no registrado desde {Ip}", current.IpAddress);
            throw new ApiException(invalid, 401);
        }

        if (user.LockedUntil is { } locked && locked > DateTime.UtcNow)
        {
            var minutes = Math.Max(1, (int)(locked - DateTime.UtcNow).TotalMinutes);
            throw new ApiException($"Cuenta bloqueada temporalmente. Intente de nuevo en {minutes} minuto(s).", 429);
        }

        if (!PasswordHasher.Verify(request.Password, user.PasswordHash, user.PasswordSalt))
        {
            user.FailedLoginAttempts++;
            if (user.FailedLoginAttempts >= MaxFailedAttempts)
            {
                user.LockedUntil = DateTime.UtcNow.Add(LockDuration);
                user.FailedLoginAttempts = 0;
                audit.Track("Bloqueo", "auth", nameof(User), user.Id,
                    "Cuenta bloqueada por intentos fallidos", companyId: user.CompanyId ?? 0,
                    userId: user.Id, userName: user.FullName);
            }
            await db.SaveChangesAsync();
            throw new ApiException(invalid, 401);
        }

        if (!user.IsActive)
            throw new ApiException("La cuenta está inactiva. Contacte al administrador.", 403);

        var company = await LoadCompanyForLoginAsync(user);

        user.FailedLoginAttempts = 0;
        user.LockedUntil = null;
        user.LastLoginAt = DateTime.UtcNow;

        var response = await IssueTokensAsync(user, company);

        audit.Track("Inicio de sesión", "auth", nameof(User), user.Id,
            $"{user.FullName} inició sesión", companyId: user.CompanyId ?? 0,
            userId: user.Id, userName: user.FullName);

        await db.SaveChangesAsync();
        return response;
    }

    /// <summary>
    /// Comprueba que la empresa del usuario pueda operar. Los usuarios de plataforma no
    /// tienen empresa, así que entran sin esta validación.
    /// </summary>
    private async Task<Company?> LoadCompanyForLoginAsync(User user)
    {
        if (user.CompanyId is not { } companyId) return null;

        var company = await db.Companies
            .Include(c => c.Subscriptions)
            .FirstOrDefaultAsync(c => c.Id == companyId)
            ?? throw new ApiException("La empresa asociada no está disponible.", 403);

        if (company.Status == CompanyStatus.Suspended)
            throw new ApiException(
                "La cuenta de su empresa está suspendida. Contacte a Gestora para reactivarla.", 403);

        if (company.Status == CompanyStatus.Cancelled)
            throw new ApiException("La cuenta de su empresa fue dada de baja.", 403);

        // La suscripción vencida no bloquea el acceso de golpe: se avisa en la interfaz.
        // Cortar el servicio sin previo aviso es una decisión comercial, no técnica.
        return company;
    }

    /// <summary>Rota el refresh token: el anterior se revoca en el mismo momento en que se usa.</summary>
    public async Task<AuthResponse> RefreshAsync(string refreshToken)
    {
        var stored = await db.RefreshTokens
            .IgnoreQueryFilters()
            .Include(t => t.User).ThenInclude(u => u.Role).ThenInclude(r => r.Permissions)
            .FirstOrDefaultAsync(t => t.Token == refreshToken);

        if (stored is null || !stored.IsActive || !stored.User.IsActive)
            throw new ApiException("La sesión expiró. Inicie sesión nuevamente.", 401);

        stored.RevokedAt = DateTime.UtcNow;

        var user = stored.User;

        // Si estaba viendo una empresa, la renovación lo deja donde estaba.
        var viewedCompanyId = stored.ImpersonatedCompanyId ?? user.CompanyId;
        var company = viewedCompanyId is { } id
            ? await db.Companies.IgnoreQueryFilters().FirstOrDefaultAsync(c => c.Id == id)
            : null;

        var response = await IssueTokensAsync(user, company, stored.ImpersonatedCompanyId);
        await db.SaveChangesAsync();
        return response;
    }

    public async Task LogoutAsync(string? refreshToken)
    {
        if (string.IsNullOrWhiteSpace(refreshToken)) return;

        var stored = await db.RefreshTokens.IgnoreQueryFilters()
            .FirstOrDefaultAsync(t => t.Token == refreshToken);

        if (stored is { RevokedAt: null })
        {
            stored.RevokedAt = DateTime.UtcNow;
            audit.Track("Cierre de sesión", "auth", nameof(User), stored.UserId, "Sesión cerrada");
            await db.SaveChangesAsync();
        }
    }

    public async Task<AuthenticatedUserDto> GetProfileAsync()
    {
        var user = await db.Users.IgnoreQueryFilters()
            .Include(u => u.Role).ThenInclude(r => r.Permissions)
            .FirstOrDefaultAsync(u => u.Id == current.UserId)
            ?? throw new ApiException("Sesión no válida.", 401);

        // La empresa que se está viendo sale del token, no del usuario: puede ser la
        // suya o, si es el desarrollador, aquella a la que entró.
        var company = current.CompanyId > 0
            ? await db.Companies.IgnoreQueryFilters().FirstOrDefaultAsync(c => c.Id == current.CompanyId)
            : null;

        return BuildUserDto(user, company, current.IsImpersonating);
    }

    public async Task ChangePasswordAsync(ChangePasswordRequest request)
    {
        var user = await db.Users.IgnoreQueryFilters()
            .FirstOrDefaultAsync(u => u.Id == current.UserId)
            ?? throw new ApiException("Sesión no válida.", 401);

        if (!PasswordHasher.Verify(request.CurrentPassword, user.PasswordHash, user.PasswordSalt))
            throw new ApiException("La contraseña actual no es correcta.");

        (user.PasswordHash, user.PasswordSalt) = PasswordHasher.Hash(request.NewPassword);

        // Cambiar la contraseña invalida las demás sesiones abiertas.
        var tokens = await db.RefreshTokens.IgnoreQueryFilters()
            .Where(t => t.UserId == user.Id && t.RevokedAt == null).ToListAsync();
        tokens.ForEach(t => t.RevokedAt = DateTime.UtcNow);

        audit.Track("Cambio de contraseña", "auth", nameof(User), user.Id, "El usuario cambió su contraseña");
        await db.SaveChangesAsync();
    }

    // ------------------------------------------------------- Ver como empresa ----

    /// <summary>
    /// Emite una sesión con la que el desarrollador ve el sistema exactamente como lo
    /// ve la empresa indicada. No cambia quién es el usuario: cambia qué empresa mira.
    /// Queda registrado en la bitácora de las dos partes.
    /// </summary>
    public async Task<AuthResponse> ImpersonateAsync(int companyId)
    {
        var user = await db.Users.IgnoreQueryFilters()
            .Include(u => u.Role).ThenInclude(r => r.Permissions)
            .FirstOrDefaultAsync(u => u.Id == current.UserId)
            ?? throw new ApiException("Sesión no válida.", 401);

        if (user.Role.Key != RoleKeys.Developer)
            throw ApiException.Forbidden("Solo el desarrollador puede ver el sistema como una empresa.");

        var company = await db.Companies.IgnoreQueryFilters().FirstOrDefaultAsync(c => c.Id == companyId)
            ?? throw ApiException.NotFound("La empresa");

        var response = await IssueTokensAsync(user, company, companyId);

        // Se registra en la bitácora de la empresa: si alguien de Gestora entró a
        // mirar sus datos, la empresa tiene que poder verlo.
        audit.Track("Acceso de soporte", "auth", nameof(Company), company.Id,
            $"{user.FullName} (Gestora) entró a ver el sistema como {company.Name}",
            companyId: company.Id, userId: user.Id, userName: user.FullName);

        await db.SaveChangesAsync();
        return response;
    }

    /// <summary>Devuelve al desarrollador a su propia vista de plataforma.</summary>
    public async Task<AuthResponse> StopImpersonatingAsync()
    {
        var user = await db.Users.IgnoreQueryFilters()
            .Include(u => u.Role).ThenInclude(r => r.Permissions)
            .FirstOrDefaultAsync(u => u.Id == current.UserId)
            ?? throw new ApiException("Sesión no válida.", 401);

        var response = await IssueTokensAsync(user, null);
        await db.SaveChangesAsync();
        return response;
    }

    // ------------------------------------------------------------- Emisión ----

    /// <param name="viewedCompanyId">
    /// Empresa que se está viendo cuando no es la del usuario. Solo lo usa el
    /// desarrollador; <c>null</c> en cualquier sesión normal.
    /// </param>
    private async Task<AuthResponse> IssueTokensAsync(User user, Company? company, int? viewedCompanyId = null)
    {
        var impersonating = viewedCompanyId is not null;
        var effectiveCompanyId = viewedCompanyId ?? user.CompanyId ?? 0;

        var claims = new List<Claim>
        {
            new(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString()),
            new(ClaimTypes.NameIdentifier, user.Id.ToString()),
            new(ClaimTypes.Email, user.Email),
            new(CurrentUser.ClaimFullName, user.FullName),
            new(CurrentUser.ClaimCompanyId, effectiveCompanyId.ToString()),
            new(CurrentUser.ClaimRoleKey, user.Role.Key),
            new(ClaimTypes.Role, user.Role.Name)
        };

        if (impersonating) claims.Add(new Claim(CurrentUser.ClaimImpersonating, "1"));

        // Los permisos viajan en el token: la autorización no golpea la base en cada request.
        // Mientras ve una empresa, el desarrollador conserva solo los módulos de empresa:
        // así la vista es fiel a la que tiene el cliente, sin el menú de plataforma.
        var permissions = user.Role.Permissions.Where(p => p.CanRead || p.CanWrite);
        if (impersonating)
        {
            var companyModules = ModuleCatalog.CompanyModules.ToHashSet();
            permissions = permissions.Where(p => companyModules.Contains(p.ModuleKey));
        }

        foreach (var permission in permissions)
        {
            if (permission.CanRead) claims.Add(new Claim(CurrentUser.ClaimPermission, $"{permission.ModuleKey}:r"));
            if (permission.CanWrite) claims.Add(new Claim(CurrentUser.ClaimPermission, $"{permission.ModuleKey}:w"));
        }

        var options = Jwt;
        var key = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(options.Key));
        var token = new JwtSecurityToken(
            issuer: options.Issuer,
            audience: options.Audience,
            claims: claims,
            expires: DateTime.UtcNow.AddMinutes(options.AccessTokenMinutes),
            signingCredentials: new SigningCredentials(key, SecurityAlgorithms.HmacSha256));

        var refresh = new RefreshToken
        {
            UserId = user.Id,
            Token = PasswordHasher.RandomToken(),
            ExpiresAt = DateTime.UtcNow.AddDays(options.RefreshTokenDays),
            CreatedByIp = current.IpAddress,
            ImpersonatedCompanyId = viewedCompanyId
        };
        db.RefreshTokens.Add(refresh);

        await PurgeExpiredTokensAsync(user.Id);

        return new AuthResponse(
            new JwtSecurityTokenHandler().WriteToken(token),
            refresh.Token,
            options.AccessTokenMinutes * 60,
            BuildUserDto(user, company, impersonating));
    }

    private async Task PurgeExpiredTokensAsync(int userId)
    {
        var stale = await db.RefreshTokens.IgnoreQueryFilters()
            .Where(t => t.UserId == userId &&
                        (t.ExpiresAt < DateTime.UtcNow || t.RevokedAt != null))
            .ToListAsync();

        if (stale.Count > 0) db.RefreshTokens.RemoveRange(stale);
    }

    /// <summary>
    /// Cruza los permisos del rol con el catálogo de módulos. El frontend arma el menú
    /// exclusivamente con esta lista, así menú y autorización nunca se desincronizan.
    /// </summary>
    private static AuthenticatedUserDto BuildUserDto(User user, Company? company, bool impersonating)
    {
        var granted = user.Role.Permissions.ToDictionary(p => p.ModuleKey);

        // Viendo una empresa, el desarrollador ve el menú de esa empresa y nada más.
        var scope = impersonating ? RoleScope.Company : user.Role.Scope;

        var modules = ModuleCatalog.Modules
            .Where(m => m.Scope == scope)
            .Where(m => granted.TryGetValue(m.Key, out var p) && (p.CanRead || p.CanWrite))
            .Select(m => new ModulePermissionDto(m.Key, m.Name, m.Group, m.Icon, m.Available,
                granted[m.Key].CanWrite))
            .ToList();

        return new AuthenticatedUserDto(
            user.Id, user.FirstName, user.LastName, user.FullName, user.Email, user.Phone,
            user.RoleId, user.Role.Key, user.Role.Name, scope.ToString(),
            company?.Id ?? 0, company?.Name, company?.Currency ?? "CRC",
            impersonating, modules);
    }
}
