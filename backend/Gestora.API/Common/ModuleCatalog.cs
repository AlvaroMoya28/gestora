using Gestora.API.Domain;

namespace Gestora.API.Common;

/// <summary>Claves estables de los roles. Se usan en código; los nombres son solo etiquetas.</summary>
public static class RoleKeys
{
    /// <summary>Desarrollador de Gestora. Ve todo y puede entrar a cualquier empresa.</summary>
    public const string Developer = "developer";
    /// <summary>Administración de Gestora: empresas, suscripciones y cobros. No entra a los datos de una empresa.</summary>
    public const string PlatformAdmin = "platform_admin";
    /// <summary>Administrador dentro de una empresa: crea, edita y elimina.</summary>
    public const string CompanyAdmin = "company_admin";
    /// <summary>Consulta dentro de una empresa: solo lectura.</summary>
    public const string CompanyViewer = "company_viewer";

    public static bool IsPlatform(string key) => key is Developer or PlatformAdmin;
}

/// <summary>
/// Catálogo de módulos del sistema. <b>Única fuente de verdad sobre permisos.</b>
///
/// Los módulos están separados por <see cref="RoleScope"/>: los de plataforma son la
/// administración de Gestora como producto (empresas, suscripciones, cobros) y los de
/// empresa son la operación diaria de un cliente suscrito. Un usuario nunca ve los dos
/// conjuntos a la vez, salvo el desarrollador cuando entra a ver una empresa.
///
/// El frontend dibuja el menú con lo que el usuario tiene permitido y el backend corta
/// la entrada con la misma lista. Agregar un módulo es añadir una línea acá.
/// </summary>
public static class ModuleCatalog
{
    public record ModuleDefinition(string Key, string Name, string Group, string Icon,
        bool Available, RoleScope Scope);

    public static readonly IReadOnlyList<ModuleDefinition> Modules = new List<ModuleDefinition>
    {
        // --- Plataforma: la administración de Gestora ---------------------------------
        new("platform_overview",  "Resumen",       "Gestora",     "gauge",     true,  RoleScope.Platform),
        new("platform_companies", "Empresas",      "Gestora",     "building",  true,  RoleScope.Platform),
        new("platform_billing",   "Suscripciones", "Gestora",     "receipt",   true,  RoleScope.Platform),
        new("platform_plans",     "Planes",        "Gestora",     "tag",       true,  RoleScope.Platform),
        new("platform_audit",     "Bitácora",      "Gestora",     "history",   true,  RoleScope.Platform),

        // --- Empresa: la operación diaria del cliente ---------------------------------
        new("dashboard",   "Panel",              "General",        "gauge",     true,  RoleScope.Company),

        new("customers",   "Clientes",           "Comercial",      "users",     true,  RoleScope.Company),
        new("suppliers",   "Proveedores",        "Comercial",      "truck",     true,  RoleScope.Company),

        new("products",    "Productos",          "Catálogo",       "box",       true,  RoleScope.Company),
        new("inventory",   "Inventario",         "Catálogo",       "layers",    true,  RoleScope.Company),

        new("purchases",   "Compras",            "Operación",      "cart",      true,  RoleScope.Company),
        new("sales",       "Ventas",             "Operación",      "receipt",   true,  RoleScope.Company),
        new("production",  "Producción",         "Operación",      "factory",   true,  RoleScope.Company),
        new("repairs",     "Reparaciones",       "Operación",      "wrench",    true,  RoleScope.Company),

        new("receivables", "Cuentas por cobrar", "Finanzas",       "arrow-in",  true,  RoleScope.Company),
        new("payables",    "Cuentas por pagar",  "Finanzas",       "arrow-out", true,  RoleScope.Company),
        new("income",      "Ingresos",           "Finanzas",       "plus",      true,  RoleScope.Company),
        new("expenses",    "Gastos",             "Finanzas",       "minus",     true,  RoleScope.Company),

        new("reports",     "Reportes",           "Análisis",       "chart",     true,  RoleScope.Company),

        new("users",       "Usuarios",           "Administración", "shield",    true,  RoleScope.Company),
        new("audit",       "Auditoría",          "Administración", "history",   true,  RoleScope.Company),
        new("settings",    "Configuración",      "Administración", "cog",       true,  RoleScope.Company),
    };

    public static bool Exists(string key) => Modules.Any(m => m.Key == key);

    public static IEnumerable<string> KeysFor(RoleScope scope) =>
        Modules.Where(m => m.Scope == scope).Select(m => m.Key);

    /// <summary>Módulos de plataforma. El desarrollador los tiene todos.</summary>
    public static IEnumerable<string> PlatformModules => KeysFor(RoleScope.Platform);

    /// <summary>
    /// Lo que administración de Gestora puede ver: la gestión comercial del producto.
    /// Deliberadamente no incluye nada que exponga datos internos de una empresa.
    /// </summary>
    public static readonly string[] PlatformAdminModules =
    {
        "platform_overview", "platform_companies", "platform_billing", "platform_plans"
    };

    /// <summary>Todos los módulos de una empresa: lo que ve su administrador.</summary>
    public static IEnumerable<string> CompanyModules => KeysFor(RoleScope.Company);
}
