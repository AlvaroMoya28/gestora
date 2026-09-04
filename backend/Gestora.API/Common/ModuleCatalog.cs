namespace Gestora.API.Common;

/// <summary>
/// Catálogo de módulos del sistema. <b>Única fuente de verdad sobre permisos.</b>
///
/// En ADIC la autorización estaba dispersa (políticas por número de rol repartidas
/// por los controladores) y el menú del frontend era otra lista aparte que podía
/// discrepar. Aquí el catálogo se expone por API: el frontend dibuja el menú con lo
/// que el usuario tiene permitido y el backend corta la entrada con la misma lista.
/// Agregar un módulo es añadir una línea acá.
/// </summary>
public static class ModuleCatalog
{
    public record ModuleDefinition(string Key, string Name, string Group, string Icon, bool Available);

    public static readonly IReadOnlyList<ModuleDefinition> Modules = new List<ModuleDefinition>
    {
        new("dashboard",   "Panel",              "General",     "gauge",     true),

        new("customers",   "Clientes",           "Comercial",   "users",     true),
        new("suppliers",   "Proveedores",        "Comercial",   "truck",     true),

        new("products",    "Productos",          "Catálogo",    "box",       true),
        new("inventory",   "Inventario",         "Catálogo",    "layers",    true),

        new("purchases",   "Compras",            "Operación",   "cart",      false),
        new("sales",       "Ventas",             "Operación",   "receipt",   false),
        new("production",  "Producción",         "Operación",   "factory",   false),
        new("repairs",     "Reparaciones",       "Operación",   "wrench",    false),

        new("receivables", "Cuentas por cobrar", "Finanzas",    "arrow-in",  false),
        new("payables",    "Cuentas por pagar",  "Finanzas",    "arrow-out", false),
        new("income",      "Ingresos",           "Finanzas",    "plus",      false),
        new("expenses",    "Gastos",             "Finanzas",    "minus",     false),

        new("reports",     "Reportes",           "Análisis",    "chart",     false),

        new("users",       "Usuarios y roles",   "Administración", "shield",  true),
        new("audit",       "Auditoría",          "Administración", "history", true),
        new("settings",    "Configuración",      "Administración", "cog",     false),
    };

    public static bool Exists(string key) => Modules.Any(m => m.Key == key);

    public static IEnumerable<string> AllKeys => Modules.Select(m => m.Key);

    /// <summary>Módulos que un rol operativo (secretaría/administración) usa a diario.</summary>
    public static readonly string[] OperatorModules =
    {
        "dashboard", "customers", "suppliers", "products", "inventory",
        "purchases", "sales", "production", "repairs",
        "receivables", "payables", "income", "expenses", "reports"
    };

    /// <summary>Módulos de solo lectura para un perfil de consulta (contador).</summary>
    public static readonly string[] ViewerModules =
    {
        "dashboard", "customers", "suppliers", "products", "inventory",
        "purchases", "sales", "receivables", "payables", "income", "expenses", "reports"
    };
}
