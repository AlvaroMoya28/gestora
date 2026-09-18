using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Users;

[ApiController]
[Route("api/usuarios")]
[Authorize]
public class UsersController(UserService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("users")]
    public async Task<ActionResult<PagedResult<UserDto>>> List([FromQuery] QueryParams query)
        => Ok(await service.ListAsync(query));

    [HttpPost]
    [RequireModule("users", write: true)]
    public async Task<ActionResult<UserDto>> Create([FromBody] UserRequest request)
        => Ok(await service.CreateAsync(request));

    [HttpPut("{id:int}")]
    [RequireModule("users", write: true)]
    public async Task<ActionResult<UserDto>> Update(int id, [FromBody] UserRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("{id:int}/inactivar")]
    [RequireModule("users", write: true)]
    public async Task<ActionResult<UserDto>> Deactivate(int id)
        => Ok(await service.SetActiveAsync(id, false));

    [HttpPost("{id:int}/activar")]
    [RequireModule("users", write: true)]
    public async Task<ActionResult<UserDto>> Activate(int id)
        => Ok(await service.SetActiveAsync(id, true));
}

/// <summary>
/// Roles asignables dentro de una empresa. Solo lectura: el catálogo es fijo
/// (administrador y consulta) y se define en la plataforma, no por empresa.
/// </summary>
[ApiController]
[Route("api/roles")]
[Authorize]
public class RolesController(UserService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("users")]
    public async Task<ActionResult<IReadOnlyList<RoleDto>>> List()
        => Ok(await service.ListAssignableRolesAsync());
}
