using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Inventory;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Production;

/// <summary>
/// Producción: recetas y órdenes de fabricación.
///
/// Una receta declara cuánto material lleva producir cierta cantidad. Una orden toma
/// esa receta, la escala a lo que se quiere fabricar y espera. Terminarla es el punto
/// sin retorno: en una sola transacción salen los materiales y entra el producto
/// fabricado, con un costo unitario calculado a partir de lo que realmente se consumió
/// más la mano de obra. Ese costo queda en el producto, de modo que lo fabricado se
/// valore por lo que costó hacerlo y no por un precio escrito a mano.
/// </summary>
public class ProductionService(GestoraDbContext db, IAuditService audit, InventoryService inventory)
{
    // ------------------------------------------------------------------ Recetas ----

    public async Task<IReadOnlyList<RecipeDto>> ListRecipesAsync(bool? active)
    {
        var query = RecipeQuery();
        if (active is { } value) query = query.Where(r => r.IsActive == value);

        var recipes = await query.OrderBy(r => r.Name).ToListAsync();
        return recipes.Select(MapRecipe).ToList();
    }

    public async Task<RecipeDto> GetRecipeAsync(int id) => MapRecipe(await LoadRecipeAsync(id));

    public async Task<RecipeDto> CreateRecipeAsync(RecipeRequest request)
    {
        var product = await ResolveOutputProductAsync(request.ProductId);

        var recipe = new Recipe
        {
            Name = request.Name.Trim(),
            ProductId = request.ProductId,
            OutputQuantity = request.OutputQuantity,
            LaborCost = request.LaborCost,
            Notes = request.Notes?.Trim()
        };
        await ApplyRecipeItemsAsync(recipe, request.Items);
        db.Recipes.Add(recipe);

        audit.Track("Creación", "production", nameof(Recipe), null,
            $"Receta {recipe.Name} para {product.Name}");
        await db.SaveChangesAsync();

        return await GetRecipeAsync(recipe.Id);
    }

    public async Task<RecipeDto> UpdateRecipeAsync(int id, RecipeRequest request)
    {
        var recipe = await LoadRecipeAsync(id);
        await ResolveOutputProductAsync(request.ProductId);

        recipe.Name = request.Name.Trim();
        recipe.ProductId = request.ProductId;
        recipe.OutputQuantity = request.OutputQuantity;
        recipe.LaborCost = request.LaborCost;
        recipe.Notes = request.Notes?.Trim();

        db.RecipeItems.RemoveRange(recipe.Items);
        recipe.Items.Clear();
        await ApplyRecipeItemsAsync(recipe, request.Items);

        audit.Track("Actualización", "production", nameof(Recipe), id, $"Receta {recipe.Name}");
        await db.SaveChangesAsync();

        return await GetRecipeAsync(id);
    }

    public async Task<RecipeDto> SetRecipeActiveAsync(int id, bool active)
    {
        var recipe = await LoadRecipeAsync(id);
        recipe.IsActive = active;

        audit.Track(active ? "Reactivación" : "Inactivación", "production", nameof(Recipe), id,
            $"Receta {recipe.Name}");
        await db.SaveChangesAsync();

        return await GetRecipeAsync(id);
    }

    private async Task ApplyRecipeItemsAsync(Recipe recipe, List<RecipeItemRequest> items)
    {
        if (items.Any(i => i.ProductId == recipe.ProductId))
            throw new ApiException("Una receta no puede llevarse a sí misma como material.");

        var ids = items.Select(i => i.ProductId).Distinct().ToList();
        if (ids.Count != items.Count)
            throw new ApiException("Hay un material repetido en la receta. Sume las cantidades en una sola línea.");

        var products = await db.Products.Where(p => ids.Contains(p.Id)).ToDictionaryAsync(p => p.Id);

        foreach (var line in items)
        {
            if (!products.TryGetValue(line.ProductId, out var product) || !product.IsActive)
                throw new ApiException("Uno de los materiales seleccionados no es válido.");

            if (product.Type == ProductType.Service)
                throw new ApiException($"«{product.Name}» es un servicio y no se puede consumir como material.");

            recipe.Items.Add(new RecipeItem { ProductId = line.ProductId, Quantity = line.Quantity });
        }
    }

    // --------------------------------------------------------- Órdenes de producción ----

    public async Task<PagedResult<ProductionOrderSummaryDto>> ListAsync(ProductionQuery q)
    {
        var query = db.ProductionOrders.AsNoTracking().Include(o => o.Product).AsQueryable();

        if (q.Status is { } status) query = query.Where(o => o.Status == status);
        if (q.ProductId is { } productId) query = query.Where(o => o.ProductId == productId);

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(o => o.Number.Contains(term) || o.Product.Name.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderByDescending(o => o.Date).ThenByDescending(o => o.Id)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        var items = rows.Select(o => new ProductionOrderSummaryDto(o.Id, o.Number, o.Product.Name,
            o.Quantity, o.ProducedQuantity, o.Status, StatusName(o.Status), o.Date, o.TotalCost)).ToList();

        return new PagedResult<ProductionOrderSummaryDto>(items, total, q.Page, q.PageSize);
    }

    public async Task<ProductionOrderDto> GetAsync(int id) => MapOrder(await LoadOrderAsync(id));

    public async Task<ProductionOrderDto> CreateAsync(ProductionOrderRequest request)
    {
        var product = await ResolveOutputProductAsync(request.ProductId);

        var order = new ProductionOrder
        {
            Number = await ResolveNumberAsync(),
            ProductId = request.ProductId,
            RecipeId = request.RecipeId,
            Quantity = request.Quantity,
            Date = request.Date ?? DateTime.UtcNow,
            LaborCost = request.LaborCost,
            Notes = request.Notes?.Trim(),
            Status = ProductionStatus.Planned
        };

        await ApplyMaterialsAsync(order, request);
        db.ProductionOrders.Add(order);

        audit.Track("Creación", "production", nameof(ProductionOrder), null,
            $"Orden {order.Number}: {request.Quantity:N2} de {product.Name}");
        await db.SaveChangesAsync();

        return await GetAsync(order.Id);
    }

    public async Task<ProductionOrderDto> UpdateAsync(int id, ProductionOrderRequest request)
    {
        var order = await LoadOrderAsync(id);
        if (order.Status is ProductionStatus.Completed or ProductionStatus.Cancelled)
            throw new ApiException("Una orden terminada o cancelada ya no se puede editar.");

        await ResolveOutputProductAsync(request.ProductId);

        order.ProductId = request.ProductId;
        order.RecipeId = request.RecipeId;
        order.Quantity = request.Quantity;
        order.Date = request.Date ?? order.Date;
        order.LaborCost = request.LaborCost;
        order.Notes = request.Notes?.Trim();

        db.ProductionMaterials.RemoveRange(order.Materials);
        order.Materials.Clear();
        await ApplyMaterialsAsync(order, request);

        audit.Track("Actualización", "production", nameof(ProductionOrder), id, $"Orden {order.Number}");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>Marca el inicio del trabajo. No mueve inventario: el material sale al terminar.</summary>
    public async Task<ProductionOrderDto> StartAsync(int id)
    {
        var order = await LoadOrderAsync(id);
        if (order.Status != ProductionStatus.Planned)
            throw new ApiException("Solo se puede iniciar una orden planificada.");

        order.Status = ProductionStatus.InProgress;
        order.StartedAt = DateTime.UtcNow;

        audit.Track("Inicio", "production", nameof(ProductionOrder), id, $"Orden {order.Number} iniciada");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>
    /// Punto sin retorno: salen los materiales consumidos y entra el producto fabricado,
    /// todo en una transacción. El costo unitario resultante queda en el producto.
    /// </summary>
    public async Task<ProductionOrderDto> CompleteAsync(int id, CompleteProductionRequest request)
    {
        var strategy = db.Database.CreateExecutionStrategy();
        await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var order = await LoadOrderAsync(id);
            if (order.Status is not (ProductionStatus.Planned or ProductionStatus.InProgress))
                throw new ApiException("Esta orden ya fue terminada o cancelada.");

            if (order.Materials.Count == 0)
                throw new ApiException("La orden no tiene materiales que consumir.");

            var consumption = request.Materials.ToDictionary(m => m.ProductId, m => m.ConsumedQuantity);
            decimal materialsCost = 0;

            foreach (var material in order.Materials)
            {
                // Lo que no se corrige se consume según lo planificado.
                var quantity = consumption.TryGetValue(material.ProductId, out var actual)
                    ? actual
                    : material.PlannedQuantity;

                material.ConsumedQuantity = quantity;
                material.UnitCost = material.Product.Cost;

                if (quantity <= 0) continue;

                await inventory.ApplyMovementAsync(new MovementRequest
                {
                    ProductId = material.ProductId,
                    Type = MovementType.ProductionInput,
                    Quantity = quantity,
                    UnitCost = material.UnitCost,
                    Reason = $"Orden de producción {order.Number}",
                    ReferenceType = nameof(ProductionOrder),
                    ReferenceId = order.Id
                });

                materialsCost += quantity * material.UnitCost;
            }

            if (request.LaborCost is { } labor) order.LaborCost = labor;

            order.ProducedQuantity = request.ProducedQuantity;
            order.MaterialsCost = materialsCost;
            order.TotalCost = materialsCost + order.LaborCost;
            order.UnitCost = request.ProducedQuantity > 0 ? order.TotalCost / request.ProducedQuantity : 0;

            await inventory.ApplyMovementAsync(new MovementRequest
            {
                ProductId = order.ProductId,
                Type = MovementType.ProductionOutput,
                Quantity = request.ProducedQuantity,
                UnitCost = order.UnitCost,
                Reason = $"Orden de producción {order.Number}",
                ReferenceType = nameof(ProductionOrder),
                ReferenceId = order.Id
            });

            // Lo fabricado se valora por lo que costó hacerlo.
            order.Product.Cost = order.UnitCost;

            order.Status = ProductionStatus.Completed;
            order.CompletedAt = DateTime.UtcNow;

            audit.Track("Terminada", "production", nameof(ProductionOrder), id,
                $"Orden {order.Number}: {request.ProducedQuantity:N2} de {order.Product.Name} · " +
                $"costo unitario {order.UnitCost:N2} (materiales {materialsCost:N2} + mano de obra {order.LaborCost:N2})");

            await db.SaveChangesAsync();
            await transaction.CommitAsync();
        });

        return await GetAsync(id);
    }

    public async Task<ProductionOrderDto> CancelAsync(int id)
    {
        var order = await LoadOrderAsync(id);
        if (order.Status == ProductionStatus.Completed)
            throw new ApiException("Una orden terminada no se puede cancelar: ya movió el inventario.");
        if (order.Status == ProductionStatus.Cancelled)
            throw new ApiException("La orden ya está cancelada.");

        order.Status = ProductionStatus.Cancelled;
        audit.Track("Cancelación", "production", nameof(ProductionOrder), id, $"Orden {order.Number} cancelada");
        await db.SaveChangesAsync();

        return await GetAsync(id);
    }

    /// <summary>
    /// Arma los materiales de la orden: de la receta escalada a la cantidad pedida, o
    /// de lo que el planificador haya indicado a mano si no se usó receta.
    /// </summary>
    private async Task ApplyMaterialsAsync(ProductionOrder order, ProductionOrderRequest request)
    {
        if (request.RecipeId is { } recipeId)
        {
            var recipe = await LoadRecipeAsync(recipeId);
            if (recipe.OutputQuantity <= 0)
                throw new ApiException("La receta tiene un rendimiento inválido.");

            var factor = request.Quantity / recipe.OutputQuantity;

            foreach (var item in recipe.Items)
            {
                order.Materials.Add(new ProductionMaterial
                {
                    ProductId = item.ProductId,
                    PlannedQuantity = item.Quantity * factor,
                    UnitCost = item.Product.Cost
                });
            }

            // La mano de obra de la receta se escala igual, salvo que la orden traiga la suya.
            if (request.LaborCost <= 0) order.LaborCost = recipe.LaborCost * factor;
            return;
        }

        if (request.Materials.Count == 0)
            throw new ApiException("Seleccione una receta o agregue los materiales de la orden.");

        var ids = request.Materials.Select(m => m.ProductId).Distinct().ToList();
        var products = await db.Products.Where(p => ids.Contains(p.Id)).ToDictionaryAsync(p => p.Id);

        foreach (var line in request.Materials)
        {
            if (!products.TryGetValue(line.ProductId, out var product) || !product.IsActive)
                throw new ApiException("Uno de los materiales seleccionados no es válido.");

            if (product.Id == order.ProductId)
                throw new ApiException("El producto fabricado no puede ser también su propio material.");

            order.Materials.Add(new ProductionMaterial
            {
                ProductId = line.ProductId,
                PlannedQuantity = line.PlannedQuantity,
                UnitCost = product.Cost
            });
        }
    }

    private async Task<Product> ResolveOutputProductAsync(int productId)
    {
        var product = await db.Products.AsNoTracking().FirstOrDefaultAsync(p => p.Id == productId && p.IsActive)
            ?? throw new ApiException("El producto a fabricar no es válido.");

        if (product.Type == ProductType.Service)
            throw new ApiException("Un servicio no se fabrica: elija un producto con existencia.");

        return product;
    }

    private async Task<string> ResolveNumberAsync()
    {
        var numbers = await db.ProductionOrders.Select(o => o.Number).ToListAsync();
        return CodeGenerator.Next("PRO", numbers);
    }

    private IQueryable<Recipe> RecipeQuery() => db.Recipes
        .Include(r => r.Product).ThenInclude(p => p.Unit)
        .Include(r => r.Items).ThenInclude(i => i.Product).ThenInclude(p => p.Unit);

    private async Task<Recipe> LoadRecipeAsync(int id) =>
        await RecipeQuery().FirstOrDefaultAsync(r => r.Id == id)
        ?? throw ApiException.NotFound("La receta");

    private async Task<ProductionOrder> LoadOrderAsync(int id) =>
        await db.ProductionOrders
            .Include(o => o.Product).ThenInclude(p => p.Unit)
            .Include(o => o.Recipe)
            .Include(o => o.Materials).ThenInclude(m => m.Product).ThenInclude(p => p.Unit)
            .FirstOrDefaultAsync(o => o.Id == id)
        ?? throw ApiException.NotFound("La orden de producción");

    internal static string StatusName(ProductionStatus status) => status switch
    {
        ProductionStatus.Planned => "Planificada",
        ProductionStatus.InProgress => "En proceso",
        ProductionStatus.Completed => "Terminada",
        ProductionStatus.Cancelled => "Cancelada",
        _ => status.ToString()
    };

    private static RecipeDto MapRecipe(Recipe r)
    {
        var materialsCost = r.Items.Sum(i => i.Quantity * i.Product.Cost);
        var total = materialsCost + r.LaborCost;

        return new RecipeDto(r.Id, r.Name, r.ProductId, r.Product.Code, r.Product.Name,
            r.Product.Unit.Abbreviation, r.OutputQuantity, r.LaborCost, materialsCost,
            r.OutputQuantity > 0 ? total / r.OutputQuantity : 0, r.Notes, r.IsActive,
            r.Items.Select(i => new RecipeItemDto(i.Id, i.ProductId, i.Product.Code, i.Product.Name,
                i.Product.Unit.Abbreviation, i.Quantity, i.Product.Cost, i.Quantity * i.Product.Cost,
                i.Product.Stock)).ToList());
    }

    private static ProductionOrderDto MapOrder(ProductionOrder o) => new(
        o.Id, o.Number, o.ProductId, o.Product.Code, o.Product.Name, o.Product.Unit.Abbreviation,
        o.RecipeId, o.Recipe?.Name, o.Quantity, o.ProducedQuantity, o.Status, StatusName(o.Status),
        o.Date, o.StartedAt, o.CompletedAt, o.LaborCost, o.MaterialsCost, o.TotalCost, o.UnitCost,
        o.Notes,
        o.Status == ProductionStatus.Planned,
        o.Status is ProductionStatus.Planned or ProductionStatus.InProgress,
        o.Materials.Select(m => new ProductionMaterialDto(m.Id, m.ProductId, m.Product.Code,
            m.Product.Name, m.Product.Unit.Abbreviation, m.PlannedQuantity, m.ConsumedQuantity,
            m.Product.Cost, m.Product.Stock, m.Product.Stock >= m.PlannedQuantity)).ToList());
}
