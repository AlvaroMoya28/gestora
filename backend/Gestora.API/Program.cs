using System.Text;
using System.Text.Json.Serialization;
using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Auth;
using Gestora.API.Modules.Catalog;
using Gestora.API.Modules.Inventory;
using Gestora.API.Modules.Users;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using Microsoft.OpenApi.Models;

var builder = WebApplication.CreateBuilder(args);

// ---------------------------------------------------------------- Configuración ----

var connectionString = builder.Configuration.GetConnectionString("DefaultConnection");
if (string.IsNullOrWhiteSpace(connectionString))
{
    throw new InvalidOperationException(
        "Falta ConnectionStrings:DefaultConnection. Copie appsettings.example.json a " +
        "appsettings.Development.json y complete los valores locales.");
}

var jwt = builder.Configuration.GetSection("Jwt").Get<JwtOptions>()
    ?? throw new InvalidOperationException("Falta la sección Jwt en la configuración.");

if (string.IsNullOrWhiteSpace(jwt.Key) || jwt.Key.Length < 32)
{
    throw new InvalidOperationException(
        "Jwt:Key debe existir y tener al menos 32 caracteres. Genere una clave aleatoria larga.");
}

// ------------------------------------------------------------------- Servicios ----

builder.Services.AddDbContext<GestoraDbContext>(options =>
    options.UseMySql(connectionString, new MySqlServerVersion(new Version(8, 0, 36)),
        mySql => mySql.EnableRetryOnFailure(3, TimeSpan.FromSeconds(5), null)));

builder.Services.AddHttpContextAccessor();
builder.Services.AddScoped<ICurrentUser, CurrentUser>();

// Un servicio por responsabilidad; sin interfaces "por si acaso" donde no hay
// una segunda implementación ni necesidad de sustituirla en pruebas.
builder.Services.AddScoped<IAuditService, AuditService>();
builder.Services.AddScoped<AuthService>();
builder.Services.AddScoped<UserService>();
builder.Services.AddScoped<CustomerService>();
builder.Services.AddScoped<SupplierService>();
builder.Services.AddScoped<ProductService>();
builder.Services.AddScoped<LookupService>();
builder.Services.AddScoped<InventoryService>();

builder.Services.AddControllers()
    .AddJsonOptions(options =>
    {
        // Los enums viajan como número: el frontend los tipa en TypeScript.
        options.JsonSerializerOptions.DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull;
    });

// Las validaciones de DataAnnotations se devuelven con el mismo contrato de error
// que el resto de la API, para que el frontend tenga un único camino de manejo.
builder.Services.Configure<ApiBehaviorOptions>(options =>
{
    options.InvalidModelStateResponseFactory = context =>
    {
        var errors = context.ModelState
            .Where(e => e.Value?.Errors.Count > 0)
            .ToDictionary(
                e => e.Key,
                e => e.Value!.Errors.Select(x => x.ErrorMessage).ToArray());

        var first = errors.Values.FirstOrDefault()?.FirstOrDefault() ?? "Los datos enviados no son válidos.";
        return new BadRequestObjectResult(new ApiErrorResponse(first, errors));
    };
});

builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen(options =>
{
    options.SwaggerDoc("v1", new OpenApiInfo { Title = "Gestora API", Version = "v1" });
    options.AddSecurityDefinition("Bearer", new OpenApiSecurityScheme
    {
        Name = "Authorization",
        Type = SecuritySchemeType.Http,
        Scheme = "bearer",
        BearerFormat = "JWT",
        In = ParameterLocation.Header,
        Description = "Pegue el access token devuelto por /api/auth/login."
    });
    options.AddSecurityRequirement(new OpenApiSecurityRequirement
    {
        {
            new OpenApiSecurityScheme
            {
                Reference = new OpenApiReference { Type = ReferenceType.SecurityScheme, Id = "Bearer" }
            },
            Array.Empty<string>()
        }
    });
});

builder.Services.AddAuthentication(JwtBearerDefaults.AuthenticationScheme)
    .AddJwtBearer(options =>
    {
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer = true,
            ValidIssuer = jwt.Issuer,
            ValidateAudience = true,
            ValidAudience = jwt.Audience,
            ValidateLifetime = true,
            ValidateIssuerSigningKey = true,
            IssuerSigningKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwt.Key)),
            ClockSkew = TimeSpan.FromSeconds(30)
        };
    });

builder.Services.AddAuthorization();

const string CorsPolicy = "GestoraFrontend";
builder.Services.AddCors(options => options.AddPolicy(CorsPolicy, policy =>
{
    var origins = builder.Configuration.GetSection("AllowedOrigins").Get<string[]>()
        ?? ["http://localhost:8080"];

    policy.WithOrigins(origins).AllowAnyMethod().AllowAnyHeader();
}));

var app = builder.Build();

// ---------------------------------------------------------------- Pipeline HTTP ----

// El manejo de errores va primero para envolver todo lo que venga después.
app.UseMiddleware<ErrorHandlingMiddleware>();

if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI(options => options.DocumentTitle = "Gestora API");
}
else
{
    app.UseHsts();
    app.UseHttpsRedirection();
}

app.Use(async (context, next) =>
{
    context.Response.Headers["X-Content-Type-Options"] = "nosniff";
    context.Response.Headers["X-Frame-Options"] = "DENY";
    context.Response.Headers["Referrer-Policy"] = "no-referrer";
    await next();
});

app.UseCors(CorsPolicy);
app.UseAuthentication();
app.UseAuthorization();
app.MapControllers();

app.MapGet("/api/health", () => Results.Ok(new
{
    status = "ok",
    service = "Gestora API",
    time = DateTime.UtcNow
})).AllowAnonymous();

await DatabaseSeeder.SeedAsync(app.Services, app.Configuration,
    app.Services.GetRequiredService<ILoggerFactory>().CreateLogger("Seed"));

app.Run();
