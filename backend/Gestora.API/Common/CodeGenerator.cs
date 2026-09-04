namespace Gestora.API.Common;

/// <summary>
/// Genera códigos correlativos por empresa (CLI-0001, PRV-0001, PRD-0001, COM-0001).
/// El usuario puede escribir el suyo; si lo deja vacío, se propone uno.
/// </summary>
internal static class CodeGenerator
{
    public static string Next(string prefix, IEnumerable<string> existing)
    {
        var max = existing
            .Where(c => c.StartsWith(prefix + "-", StringComparison.OrdinalIgnoreCase))
            .Select(c => int.TryParse(c[(prefix.Length + 1)..], out var n) ? n : 0)
            .DefaultIfEmpty(0)
            .Max();

        return $"{prefix}-{max + 1:D4}";
    }
}
