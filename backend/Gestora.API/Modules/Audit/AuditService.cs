using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;

namespace Gestora.API.Modules.Audit;

public interface IAuditService
{
    /// <summary>
    /// Encola un registro de auditoría en la unidad de trabajo actual. No llama a
    /// SaveChanges: se persiste junto con la operación auditada, de modo que si la
    /// operación se revierte, su rastro también.
    /// </summary>
    void Track(string action, string module, string? entityName = null, int? entityId = null,
        string? description = null, string? oldValue = null, string? newValue = null,
        int? companyId = null, int? userId = null, string? userName = null);
}

public class AuditService(GestoraDbContext db, ICurrentUser current) : IAuditService
{
    public void Track(string action, string module, string? entityName = null, int? entityId = null,
        string? description = null, string? oldValue = null, string? newValue = null,
        int? companyId = null, int? userId = null, string? userName = null)
    {
        db.AuditLogs.Add(new AuditLog
        {
            // El login ocurre antes de que exista un principal autenticado, por eso
            // los identificadores pueden llegar explícitos desde el llamador.
            CompanyId = companyId ?? current.CompanyId,
            UserId = userId ?? (current.IsAuthenticated ? current.UserId : null),
            UserName = userName ?? current.FullName,
            Action = action,
            Module = module,
            EntityName = entityName,
            EntityId = entityId,
            Description = Truncate(description, 500),
            OldValue = Truncate(oldValue, 1000),
            NewValue = Truncate(newValue, 1000),
            IpAddress = current.IpAddress,
            OccurredAt = DateTime.UtcNow
        });
    }

    private static string? Truncate(string? value, int max) =>
        value is null || value.Length <= max ? value : value[..max];
}
