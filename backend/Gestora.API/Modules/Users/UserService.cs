using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Auth;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Users;

public record UserDto(int Id, string FirstName, string LastName, string FullName, string Email,
    string? Phone, int RoleId, string RoleKey, string RoleName, bool IsActive,
    DateTime? LastLoginAt, DateTime CreatedAt);

public class UserRequest
{
    [Required(ErrorMessage = "El nombre es obligatorio.")]
    [MaxLength(100)] public string FirstName { get; set; } = string.Empty;

    [Required(ErrorMessage = "Los apellidos son obligatorios.")]
    [MaxLength(150)] public string LastName { get; set; } = string.Empty;

    [Required(ErrorMessage = "El correo es obligatorio.")]
    [EmailAddress(ErrorMessage = "El correo no tiene un formato válido.")]
    [MaxLength(150)] public string Email { get; set; } = string.Empty;

    [MaxLength(30)] public string? Phone { get; set; }

    [Range(1, int.MaxValue, ErrorMessage = "Debe seleccionar un rol.")]
    public int RoleId { get; set; }

    /// <summary>Obligatoria al crear; al editar, si viene vacía la contraseña no cambia.</summary>
    [MinLength(8, ErrorMessage = "La contraseña debe tener al menos 8 caracteres.")]
    public string? Password { get; set; }
}

public record RolePermissionDto(string ModuleKey, bool CanRead, bool CanWrite);

public record RoleDto(int Id, string Key, string Name, string? Description, string Scope,
    int UserCount, IReadOnlyList<RolePermissionDto> Permissions);

/// <summary>
/// Usuarios de la empresa activa. Cada empresa administra sus propias cuentas dentro
/// de los dos roles disponibles (administrador y consulta); los roles no se crean ni
/// se editan desde acá, son un catálogo fijo de la plataforma.
/// </summary>
public class UserService(GestoraDbContext db, IAuditService audit, ICurrentUser current)
{
    public async Task<PagedResult<UserDto>> ListAsync(QueryParams q)
    {
        var query = db.Users.AsNoTracking().Include(u => u.Role).AsQueryable();

        if (q.Active is { } active) query = query.Where(u => u.IsActive == active);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(u => u.FirstName.Contains(term) || u.LastName.Contains(term)
                || u.Email.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(u => u.FirstName).ThenBy(u => u.LastName)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<UserDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<UserDto> CreateAsync(UserRequest request)
    {
        if (string.IsNullOrWhiteSpace(request.Password))
            throw new ApiException("La contraseña es obligatoria al crear un usuario.");

        var email = request.Email.Trim().ToLowerInvariant();

        // El correo identifica la sesión en toda la plataforma, así que la unicidad
        // se comprueba globalmente, no solo dentro de la empresa.
        if (await db.Users.IgnoreQueryFilters().AnyAsync(u => u.Email == email))
            throw ApiException.Conflict("Ya existe un usuario con ese correo.");

        var role = await EnsureAssignableRoleAsync(request.RoleId);
        await EnsureUserQuotaAsync();

        var (hash, salt) = PasswordHasher.Hash(request.Password);
        var user = new User
        {
            CompanyId = current.CompanyId > 0 ? current.CompanyId : null,
            FirstName = request.FirstName.Trim(),
            LastName = request.LastName.Trim(),
            Email = email,
            Phone = request.Phone?.Trim(),
            RoleId = role.Id,
            PasswordHash = hash,
            PasswordSalt = salt
        };
        db.Users.Add(user);

        audit.Track("Creación", "users", nameof(User), null, $"Usuario {email} con rol {role.Name}");
        await db.SaveChangesAsync();

        return Map(await LoadAsync(user.Id));
    }

    public async Task<UserDto> UpdateAsync(int id, UserRequest request)
    {
        var user = await LoadAsync(id);
        var email = request.Email.Trim().ToLowerInvariant();

        if (await db.Users.IgnoreQueryFilters().AnyAsync(u => u.Email == email && u.Id != id))
            throw ApiException.Conflict("Ya existe un usuario con ese correo.");

        var role = await EnsureAssignableRoleAsync(request.RoleId);

        if (user.Id == current.UserId && user.RoleId != role.Id)
            throw new ApiException("No puede cambiar su propio rol.");

        var before = $"{user.FullName} | {user.Email} | {user.Role.Name}";

        user.FirstName = request.FirstName.Trim();
        user.LastName = request.LastName.Trim();
        user.Email = email;
        user.Phone = request.Phone?.Trim();
        user.RoleId = role.Id;

        if (!string.IsNullOrWhiteSpace(request.Password))
        {
            (user.PasswordHash, user.PasswordSalt) = PasswordHasher.Hash(request.Password);
            // Cambiar la contraseña desde administración cierra las sesiones del usuario.
            var tokens = await db.RefreshTokens.IgnoreQueryFilters()
                .Where(t => t.UserId == id && t.RevokedAt == null).ToListAsync();
            tokens.ForEach(t => t.RevokedAt = DateTime.UtcNow);
            audit.Track("Restablecimiento de contraseña", "users", nameof(User), id,
                $"Contraseña restablecida para {email}");
        }

        audit.Track("Actualización", "users", nameof(User), id, "Usuario", before,
            $"{user.FullName} | {user.Email} | {role.Name}");
        await db.SaveChangesAsync();

        return Map(await LoadAsync(id));
    }

    public async Task<UserDto> SetActiveAsync(int id, bool active)
    {
        var user = await LoadAsync(id);

        if (user.Id == current.UserId && !active)
            throw new ApiException("No puede desactivar su propia cuenta.");

        if (user.IsActive != active)
        {
            if (active) await EnsureUserQuotaAsync();

            user.IsActive = active;
            if (!active)
            {
                var tokens = await db.RefreshTokens.IgnoreQueryFilters()
                    .Where(t => t.UserId == id && t.RevokedAt == null).ToListAsync();
                tokens.ForEach(t => t.RevokedAt = DateTime.UtcNow);
            }
            audit.Track(active ? "Reactivación" : "Inactivación", "users", nameof(User), id,
                $"Usuario {user.Email}");
            await db.SaveChangesAsync();
        }

        return Map(user);
    }

    /// <summary>
    /// Roles que se pueden asignar dentro de la empresa activa. Nunca devuelve los de
    /// plataforma: una empresa no puede crear administradores de Gestora.
    /// </summary>
    public async Task<IReadOnlyList<RoleDto>> ListAssignableRolesAsync()
    {
        var roles = await db.Roles.AsNoTracking().Include(r => r.Permissions)
            .Where(r => r.IsActive && r.Scope == RoleScope.Company)
            .OrderBy(r => r.SortOrder)
            .ToListAsync();

        var counts = await db.Users.GroupBy(u => u.RoleId)
            .Select(g => new { RoleId = g.Key, Count = g.Count() })
            .ToDictionaryAsync(x => x.RoleId, x => x.Count);

        return roles.Select(r => new RoleDto(r.Id, r.Key, r.Name, r.Description, r.Scope.ToString(),
            counts.GetValueOrDefault(r.Id),
            r.Permissions.Select(p => new RolePermissionDto(p.ModuleKey, p.CanRead, p.CanWrite)).ToList()))
            .ToList();
    }

    // ------------------------------------------------------------ Internos ----

    private async Task<Role> EnsureAssignableRoleAsync(int roleId)
    {
        var role = await db.Roles.FirstOrDefaultAsync(r => r.Id == roleId && r.IsActive)
            ?? throw new ApiException("El rol seleccionado no es válido.");

        if (role.Scope != RoleScope.Company)
            throw ApiException.Forbidden("No se pueden asignar roles de plataforma desde una empresa.");

        return role;
    }

    /// <summary>
    /// El plan contratado limita cuántas cuentas activas puede tener la empresa.
    /// Es el punto donde el modelo comercial toca la operación diaria.
    /// </summary>
    private async Task EnsureUserQuotaAsync()
    {
        if (current.CompanyId <= 0) return;

        var plan = await db.Subscriptions
            .Where(s => s.CompanyId == current.CompanyId && s.Status != SubscriptionStatus.Cancelled)
            .OrderByDescending(s => s.EndDate)
            .Select(s => s.Plan)
            .FirstOrDefaultAsync();

        if (plan is null || plan.MaxUsers <= 0) return;

        var activeUsers = await db.Users.CountAsync(u => u.IsActive);
        if (activeUsers >= plan.MaxUsers)
            throw ApiException.Conflict(
                $"El plan {plan.Name} permite {plan.MaxUsers} usuario(s) activo(s). " +
                "Desactive una cuenta o consulte a Gestora para ampliar el plan.");
    }

    private async Task<User> LoadAsync(int id) =>
        await db.Users.Include(u => u.Role).FirstOrDefaultAsync(u => u.Id == id)
        ?? throw ApiException.NotFound("El usuario");

    private static UserDto Map(User u) => new(u.Id, u.FirstName, u.LastName, u.FullName, u.Email,
        u.Phone, u.RoleId, u.Role?.Key ?? string.Empty, u.Role?.Name ?? string.Empty,
        u.IsActive, u.LastLoginAt, u.CreatedAt);
}
