namespace Gestora.API.Domain;

/// <summary>
/// Marca una entidad que pertenece a una empresa. El <see cref="Data.GestoraDbContext"/>
/// aplica un filtro global sobre <c>CompanyId</c>, de modo que ninguna consulta puede
/// devolver datos de otra empresa aunque el desarrollador olvide el <c>Where</c>.
/// Es la base del aislamiento multiempresa de cara al futuro SaaS.
/// </summary>
public interface ITenantEntity
{
    int CompanyId { get; set; }
}

/// <summary>
/// Campos de auditoría comunes. Se rellenan automáticamente en SaveChanges.
/// </summary>
public abstract class BaseEntity
{
    public int Id { get; set; }
    public DateTime CreatedAt { get; set; }
    public int? CreatedByUserId { get; set; }
    public DateTime? UpdatedAt { get; set; }
    public int? UpdatedByUserId { get; set; }
}

public abstract class TenantEntity : BaseEntity, ITenantEntity
{
    public int CompanyId { get; set; }
}
