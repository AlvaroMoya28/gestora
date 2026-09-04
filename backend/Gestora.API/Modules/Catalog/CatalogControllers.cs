using Gestora.API.Common;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Gestora.API.Modules.Catalog;

/// <summary>
/// Clientes. La baja es lógica (<c>POST /{id}/inactivar</c>): nunca se borra un
/// registro al que apuntan ventas o cuentas por cobrar.
/// </summary>
[ApiController]
[Route("api/clientes")]
[Authorize]
public class CustomersController(CustomerService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("customers")]
    public async Task<ActionResult<PagedResult<CustomerDto>>> List([FromQuery] QueryParams query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("customers")]
    public async Task<ActionResult<CustomerDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("customers", write: true)]
    public async Task<ActionResult<CustomerDto>> Create([FromBody] CustomerRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("customers", write: true)]
    public async Task<ActionResult<CustomerDto>> Update(int id, [FromBody] CustomerRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("{id:int}/inactivar")]
    [RequireModule("customers", write: true)]
    public async Task<ActionResult<CustomerDto>> Deactivate(int id)
        => Ok(await service.SetActiveAsync(id, false));

    [HttpPost("{id:int}/activar")]
    [RequireModule("customers", write: true)]
    public async Task<ActionResult<CustomerDto>> Activate(int id)
        => Ok(await service.SetActiveAsync(id, true));
}

[ApiController]
[Route("api/proveedores")]
[Authorize]
public class SuppliersController(SupplierService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("suppliers")]
    public async Task<ActionResult<PagedResult<SupplierDto>>> List([FromQuery] QueryParams query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("suppliers")]
    public async Task<ActionResult<SupplierDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("suppliers", write: true)]
    public async Task<ActionResult<SupplierDto>> Create([FromBody] SupplierRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("suppliers", write: true)]
    public async Task<ActionResult<SupplierDto>> Update(int id, [FromBody] SupplierRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("{id:int}/inactivar")]
    [RequireModule("suppliers", write: true)]
    public async Task<ActionResult<SupplierDto>> Deactivate(int id)
        => Ok(await service.SetActiveAsync(id, false));

    [HttpPost("{id:int}/activar")]
    [RequireModule("suppliers", write: true)]
    public async Task<ActionResult<SupplierDto>> Activate(int id)
        => Ok(await service.SetActiveAsync(id, true));
}

[ApiController]
[Route("api/productos")]
[Authorize]
public class ProductsController(ProductService service) : ControllerBase
{
    [HttpGet]
    [RequireModule("products")]
    public async Task<ActionResult<PagedResult<ProductDto>>> List([FromQuery] ProductQuery query)
        => Ok(await service.ListAsync(query));

    [HttpGet("{id:int}")]
    [RequireModule("products")]
    public async Task<ActionResult<ProductDto>> Get(int id) => Ok(await service.GetAsync(id));

    [HttpPost]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<ProductDto>> Create([FromBody] ProductRequest request)
    {
        var created = await service.CreateAsync(request);
        return CreatedAtAction(nameof(Get), new { id = created.Id }, created);
    }

    [HttpPut("{id:int}")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<ProductDto>> Update(int id, [FromBody] ProductRequest request)
        => Ok(await service.UpdateAsync(id, request));

    [HttpPost("{id:int}/inactivar")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<ProductDto>> Deactivate(int id)
        => Ok(await service.SetActiveAsync(id, false));

    [HttpPost("{id:int}/activar")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<ProductDto>> Activate(int id)
        => Ok(await service.SetActiveAsync(id, true));
}

/// <summary>Categorías y unidades de medida: catálogos auxiliares del módulo de productos.</summary>
[ApiController]
[Route("api")]
[Authorize]
public class LookupsController(LookupService service) : ControllerBase
{
    [HttpGet("categorias")]
    [RequireModule("products")]
    public async Task<ActionResult<IReadOnlyList<CategoryDto>>> Categories([FromQuery] bool? active)
        => Ok(await service.ListCategoriesAsync(active));

    [HttpPost("categorias")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<CategoryDto>> CreateCategory([FromBody] CategoryRequest request)
        => Ok(await service.CreateCategoryAsync(request));

    [HttpPut("categorias/{id:int}")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<CategoryDto>> UpdateCategory(int id, [FromBody] CategoryRequest request)
        => Ok(await service.UpdateCategoryAsync(id, request));

    [HttpPost("categorias/{id:int}/estado")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<CategoryDto>> ToggleCategory(int id, [FromQuery] bool active)
        => Ok(await service.SetCategoryActiveAsync(id, active));

    [HttpGet("unidades")]
    [RequireModule("products")]
    public async Task<ActionResult<IReadOnlyList<UnitDto>>> Units([FromQuery] bool? active)
        => Ok(await service.ListUnitsAsync(active));

    [HttpPost("unidades")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<UnitDto>> CreateUnit([FromBody] UnitRequest request)
        => Ok(await service.CreateUnitAsync(request));

    [HttpPut("unidades/{id:int}")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<UnitDto>> UpdateUnit(int id, [FromBody] UnitRequest request)
        => Ok(await service.UpdateUnitAsync(id, request));

    [HttpPost("unidades/{id:int}/estado")]
    [RequireModule("products", write: true)]
    public async Task<ActionResult<UnitDto>> ToggleUnit(int id, [FromQuery] bool active)
        => Ok(await service.SetUnitActiveAsync(id, active));
}
