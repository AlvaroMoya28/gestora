using System.ComponentModel.DataAnnotations;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Auth;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Users;

public record UserDto(int Id, string FirstName, string LastName, string FullName, string Email,
    string? Phone, int RoleId, string RoleName, bool IsActive, DateTime? LastLoginAt, DateTime CreatedAt);

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

public record RoleDto(int Id, string Name, string? Description, bool IsSystem, bool IsActive,
    int UserCount, IReadOnlyList<RolePermissionDto> Permissions);

public class RoleRequest
{
    [Required(ErrorMessage = "El nombre del rol es obligatorio.")]
    [MaxLength(60)] public string Name { get; set; } = string.Empty;

    [MaxLength(200)] public string? Description { get; set; }

    public List<RolePermissionDto> Permissions { get; set; } = [];
}

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
        if (await db.Users.AnyAsync(u => u.Email == email))
            throw ApiException.Conflict("Ya existe un usuario con ese correo.");

        await EnsureRoleExistsAsync(request.RoleId);

        var (hash, salt) = PasswordHasher.Hash(request.Password);
        var user = new User
        {
            FirstName = request.FirstName.Trim(),
            LastName = request.LastName.Trim(),
            Email = email,
            Phone = request.Phone?.Trim(),
            RoleId = request.RoleId,
            PasswordHash = hash,
            PasswordSalt = salt
        };
        db.Users.Add(user);

        audit.Track("Creación", "users", nameof(User), null, $"Usuario {email}");
        await db.SaveChangesAsync();

        return Map(await LoadAsync(user.Id));
    }

    public async Task<UserDto> UpdateAsync(int id, UserRequest request)
    {
        var user = await LoadAsync(id);
        var email = request.Email.Trim().ToLowerInvariant();

        if (await db.Users.AnyAsync(u => u.Email == email && u.Id != id))
            throw ApiException.Conflict("Ya existe un usuario con ese correo.");

        await EnsureRoleExistsAsync(request.RoleId);

        if (user.Id == current.UserId && user.RoleId != request.RoleId)
            throw new ApiException("No puede cambiar su propio rol.");

        var before = $"{user.FullName} | {user.Email} | rol {user.RoleId}";

        user.FirstName = request.FirstName.Trim();
        user.LastName = request.LastName.Trim();
        user.Email = email;
        user.Phone = request.Phone?.Trim();
        user.RoleId = request.RoleId;

        if (!string.IsNullOrWhiteSpace(request.Password))
        {
            (user.PasswordHash, user.PasswordSalt) = PasswordHasher.Hash(request.Password);
            // Cambiar la contraseña desde administración cierra las sesiones del usuario.
            var tokens = await db.RefreshTokens.Where(t => t.UserId == id && t.RevokedAt == null).ToListAsync();
            tokens.ForEach(t => t.RevokedAt = DateTime.UtcNow);
            audit.Track("Restablecimiento de contraseña", "users", nameof(User), id,
                $"Contraseña restablecida para {email}");
        }

        audit.Track("Actualización", "users", nameof(User), id, "Usuario", before,
            $"{user.FullName} | {user.Email} | rol {user.RoleId}");
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
            user.IsActive = active;
            if (!active)
            {
                var tokens = await db.RefreshTokens.Where(t => t.UserId == id && t.RevokedAt == null).ToListAsync();
                tokens.ForEach(t => t.RevokedAt = DateTime.UtcNow);
            }
            audit.Track(active ? "Reactivación" : "Inactivación", "users", nameof(User), id,
                $"Usuario {user.Email}");
            await db.SaveChangesAsync();
        }

        return Map(user);
    }

    // ------------------------------------------------------------------ Roles ----

    public async Task<IReadOnlyList<RoleDto>> ListRolesAsync()
    {
        var roles = await db.Roles.AsNoTracking().Include(r => r.Permissions)
            .OrderBy(r => r.Name).ToListAsync();

        var counts = await db.Users.GroupBy(u => u.RoleId)
            .Select(g => new { RoleId = g.Key, Count = g.Count() }).ToDictionaryAsync(x => x.RoleId, x => x.Count);

        return roles.Select(r => new RoleDto(r.Id, r.Name, r.Description, r.IsSystem, r.IsActive,
            counts.GetValueOrDefault(r.Id),
            r.Permissions.Select(p => new RolePermissionDto(p.ModuleKey, p.CanRead, p.CanWrite)).ToList()))
            .ToList();
    }

    public async Task<RoleDto> CreateRoleAsync(RoleRequest request)
    {
        var name = request.Name.Trim();
        if (await db.Roles.AnyAsync(r => r.Name == name))
            throw ApiException.Conflict($"Ya existe el rol «{name}».");

        var role = new Role { Name = name, Description = request.Description?.Trim() };
        ApplyPermissions(role, request.Permissions);
        db.Roles.Add(role);

        audit.Track("Creación", "users", nameof(Role), null, $"Rol {name}");
        await db.SaveChangesAsync();

        return (await ListRolesAsync()).First(r => r.Id == role.Id);
    }

    public async Task<RoleDto> UpdateRoleAsync(int id, RoleRequest request)
    {
        var role = await db.Roles.Include(r => r.Permissions).FirstOrDefaultAsync(r => r.Id == id)
            ?? throw ApiException.NotFound("El rol");

        var name = request.Name.Trim();
        if (await db.Roles.AnyAsync(r => r.Name == name && r.Id != id))
            throw ApiException.Conflict($"Ya existe el rol «{name}».");

        if (role.IsSystem && !request.Permissions.Any(p => p.ModuleKey == "users" && p.CanWrite))
            throw new ApiException("El rol de administrador debe conservar la gestión de usuarios.");

        role.Name = name;
        role.Description = request.Description?.Trim();

        db.RolePermissions.RemoveRange(role.Permissions);
        role.Permissions.Clear();
        ApplyPermissions(role, request.Permissions);

        audit.Track("Actualización", "users", nameof(Role), id, $"Permisos del rol {name}");
        await db.SaveChangesAsync();

        return (await ListRolesAsync()).First(r => r.Id == id);
    }

    private static void ApplyPermissions(Role role, IEnumerable<RolePermissionDto> permissions)
    {
        foreach (var permission in permissions.Where(p => p.CanRead || p.CanWrite))
        {
            if (!ModuleCatalog.Exists(permission.ModuleKey))
                throw new ApiException($"El módulo «{permission.ModuleKey}» no existe.");

            role.Permissions.Add(new RolePermission
            {
                ModuleKey = permission.ModuleKey,
                // Escribir implica leer: evita estados de permiso incoherentes.
                CanRead = permission.CanRead || permission.CanWrite,
                CanWrite = permission.CanWrite
            });
        }
    }

    private async Task<User> LoadAsync(int id) =>
        await db.Users.Include(u => u.Role).FirstOrDefaultAsync(u => u.Id == id)
        ?? throw ApiException.NotFound("El usuario");

    private async Task EnsureRoleExistsAsync(int roleId)
    {
        if (!await db.Roles.AnyAsync(r => r.Id == roleId && r.IsActive))
            throw new ApiException("El rol seleccionado no es válido.");
    }

    private static UserDto Map(User u) => new(u.Id, u.FirstName, u.LastName, u.FullName, u.Email,
        u.Phone, u.RoleId, u.Role?.Name ?? string.Empty, u.IsActive, u.LastLoginAt, u.CreatedAt);
}
