using System.Net;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace Gestora.API.Common;

/// <summary>
/// Excepción de negocio: el mensaje SÍ es apto para mostrarse al usuario.
/// Cualquier otra excepción se convierte en un 500 genérico sin filtrar detalles.
/// </summary>
public class ApiException : Exception
{
    public int StatusCode { get; }
    public IReadOnlyDictionary<string, string[]>? Errors { get; }

    public ApiException(string message, int statusCode = 400,
        IReadOnlyDictionary<string, string[]>? errors = null) : base(message)
    {
        StatusCode = statusCode;
        Errors = errors;
    }

    public static ApiException NotFound(string what) => new($"{what} no existe o fue eliminado.", 404);
    public static ApiException Conflict(string message) => new(message, 409);
    public static ApiException Forbidden(string message = "No tiene permisos para esta operación.") => new(message, 403);
}

/// <summary>Cuerpo uniforme de error. El frontend siempre lee <c>message</c>.</summary>
public record ApiErrorResponse(string Message, IReadOnlyDictionary<string, string[]>? Errors = null, string? TraceId = null);

/// <summary>Resultado paginado estándar de los listados.</summary>
public record PagedResult<T>(IReadOnlyList<T> Items, int Total, int Page, int PageSize)
{
    public int TotalPages => PageSize <= 0 ? 0 : (int)Math.Ceiling(Total / (double)PageSize);
}

/// <summary>Parámetros comunes de cualquier listado.</summary>
public class QueryParams
{
    private int _pageSize = 20;
    public int Page { get; set; } = 1;
    public int PageSize
    {
        get => _pageSize;
        set => _pageSize = value is < 1 or > 200 ? 20 : value;
    }
    public string? Search { get; set; }
    /// <summary>null = todos, true = solo activos, false = solo inactivos.</summary>
    public bool? Active { get; set; }
}

/// <summary>
/// Traduce cualquier excepción a una respuesta JSON consistente y registra el detalle
/// técnico en el log. El usuario nunca ve un stack trace.
/// </summary>
public class ErrorHandlingMiddleware(RequestDelegate next, ILogger<ErrorHandlingMiddleware> logger)
{
    public async Task InvokeAsync(HttpContext context)
    {
        try
        {
            await next(context);
        }
        catch (ApiException ex)
        {
            logger.LogWarning("Regla de negocio en {Path}: {Message}", context.Request.Path, ex.Message);
            await WriteAsync(context, ex.StatusCode, new ApiErrorResponse(ex.Message, ex.Errors));
        }
        catch (Exception ex)
        {
            var traceId = context.TraceIdentifier;
            logger.LogError(ex, "Excepción no controlada en {Path} (trace {TraceId})", context.Request.Path, traceId);
            await WriteAsync(context, (int)HttpStatusCode.InternalServerError,
                new ApiErrorResponse("Ocurrió un error interno. Intente nuevamente más tarde.", null, traceId));
        }
    }

    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull,
    };

    private static async Task WriteAsync(HttpContext context, int statusCode, ApiErrorResponse body)
    {
        if (context.Response.HasStarted) return;
        context.Response.Clear();
        context.Response.StatusCode = statusCode;
        context.Response.ContentType = "application/json";
        await context.Response.WriteAsync(JsonSerializer.Serialize(body, JsonOptions));
    }
}
