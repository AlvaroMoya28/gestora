namespace Gestora.API.Modules.Reports;

/// <summary>Cifra destacada del reporte. Mismo contrato que las métricas del panel.</summary>
public record ReportMetricDto(string Key, string Label, decimal Value, string Format, string? Hint);

/// <summary>
/// Columna de la tabla del reporte. El formato lo interpreta el frontend, de modo que
/// los importes se muestren en la moneda de la empresa sin que el backend la conozca.
/// </summary>
public record ReportColumnDto(string Key, string Label, string Format, string Align);

/// <summary>Punto de la serie que se dibuja como barras. Etiqueta ya legible.</summary>
public record ReportPointDto(string Label, decimal Value);

/// <summary>Un reporte disponible, para armar el menú de la pantalla.</summary>
public record ReportDefinitionDto(string Key, string Name, string Description, string Group, bool NeedsPeriod);

/// <summary>
/// Resultado de cualquier reporte.
///
/// Todos los reportes devuelven esta misma forma —cifras, serie y tabla— para que una
/// sola pantalla los dibuje todos. Agregar un reporte es escribir su consulta acá, sin
/// tocar el frontend.
/// </summary>
public record ReportDto(
    string Key,
    string Title,
    string Description,
    DateTime From,
    DateTime To,
    bool NeedsPeriod,
    IReadOnlyList<ReportMetricDto> Metrics,
    IReadOnlyList<ReportColumnDto> Columns,
    IReadOnlyList<Dictionary<string, object?>> Rows,
    IReadOnlyList<ReportPointDto> Series,
    string? SeriesLabel);
