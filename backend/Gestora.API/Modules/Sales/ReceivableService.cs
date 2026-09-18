using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Gestora.API.Modules.Finance;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Sales;

/// <summary>
/// Cuentas por cobrar y sus cobros. Las cuentas nacen solas al confirmar una venta o al
/// entregar una reparación; acá solo se consultan y se abonan.
///
/// Cada cobro registra además su entrada en el libro de ingresos, dentro de la misma
/// transacción: el dinero entra una vez y se ve en los dos lugares, sin digitarlo dos veces.
/// </summary>
public class ReceivableService(GestoraDbContext db, IAuditService audit, ICurrentUser current,
    FinanceService finance)
{
    public async Task<PagedResult<AccountReceivableDto>> ListAsync(ReceivableQuery q)
    {
        var query = db.AccountsReceivable.AsNoTracking()
            .Include(a => a.Customer).Include(a => a.Sale).Include(a => a.RepairOrder)
            .Include(a => a.Receipts).ThenInclude(r => r.User)
            .AsQueryable();

        if (q.CustomerId is { } customerId) query = query.Where(a => a.CustomerId == customerId);
        if (q.Status is { } status) query = query.Where(a => a.Status == status);
        if (q.Overdue == true)
        {
            var today = DateTime.UtcNow.Date;
            query = query.Where(a => a.Balance > 0 && a.DueDate < today);
        }

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(a => a.DocumentNumber.Contains(term) || a.Customer.Name.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(a => a.Status == ReceivableStatus.Collected).ThenBy(a => a.DueDate)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<AccountReceivableDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<AccountReceivableDto> GetAsync(int id) => Map(await LoadAsync(id));

    /// <summary>
    /// Registra un cobro. No se admite cobrar de más: un sobrepago señala un error de
    /// digitación que hay que corregir, no absorber en silencio.
    /// </summary>
    public async Task<AccountReceivableDto> RegisterReceiptAsync(int receivableId, RegisterReceiptRequest request)
    {
        var strategy = db.Database.CreateExecutionStrategy();
        await strategy.ExecuteAsync(async () =>
        {
            await using var transaction = await db.Database.BeginTransactionAsync();

            var receivable = await LoadAsync(receivableId);

            if (receivable.Status is ReceivableStatus.Collected or ReceivableStatus.Cancelled)
                throw new ApiException("Esta cuenta ya está saldada y no admite más cobros.");

            if (request.Amount > receivable.Balance)
                throw ApiException.Conflict(
                    $"El monto ({request.Amount:N2}) supera el saldo pendiente ({receivable.Balance:N2}).");

            var receipt = new Receipt
            {
                AccountReceivableId = receivable.Id,
                Date = request.Date ?? DateTime.UtcNow,
                Amount = request.Amount,
                Method = request.Method.Trim(),
                Reference = request.Reference?.Trim(),
                Notes = request.Notes?.Trim(),
                UserId = current.IsAuthenticated ? current.UserId : null
            };
            db.Receipts.Add(receipt);

            receivable.CollectedAmount += request.Amount;
            receivable.Balance -= request.Amount;
            receivable.Status = receivable.Balance <= 0
                ? ReceivableStatus.Collected
                : ReceivableStatus.PartiallyCollected;

            audit.Track("Cobro registrado", "receivables", nameof(AccountReceivable), receivable.Id,
                $"Cobro de {request.Amount:N2} sobre {receivable.DocumentNumber} ({receivable.Customer.Name}) · saldo {receivable.Balance:N2}");

            // El Id del cobro hace falta para enlazar el asiento de caja con su origen.
            await db.SaveChangesAsync();

            await finance.RecordAutomaticAsync(FinanceKind.Income, FinanceSource.Receipt, receipt.Id,
                receipt.Amount, $"Cobro {receivable.DocumentNumber} · {receivable.Customer.Name}",
                receipt.Method, receipt.Reference, receipt.Date);

            await db.SaveChangesAsync();
            await transaction.CommitAsync();
        });

        return await GetAsync(receivableId);
    }

    /// <summary>
    /// Crea la cuenta por cobrar de una reparación entregada. Vive acá, y no en el
    /// servicio de reparaciones, para que exista un solo lugar donde nace una deuda
    /// de cliente. No guarda: lo hace quien la llama, dentro de su transacción.
    /// </summary>
    internal AccountReceivable AddForRepair(RepairOrder repair)
    {
        var receivable = new AccountReceivable
        {
            CompanyId = repair.CompanyId,
            CustomerId = repair.CustomerId,
            RepairOrderId = repair.Id,
            DocumentNumber = repair.Number,
            IssueDate = repair.DeliveredAt ?? DateTime.UtcNow,
            DueDate = (repair.DeliveredAt ?? DateTime.UtcNow).AddDays(Math.Max(repair.CreditDays, 0)),
            Total = repair.Total,
            CollectedAmount = 0,
            Balance = repair.Total,
            Status = ReceivableStatus.Pending
        };
        db.AccountsReceivable.Add(receivable);
        return receivable;
    }

    private async Task<AccountReceivable> LoadAsync(int id) =>
        await db.AccountsReceivable
            .Include(a => a.Customer).Include(a => a.Sale).Include(a => a.RepairOrder)
            .Include(a => a.Receipts).ThenInclude(r => r.User)
            .FirstOrDefaultAsync(a => a.Id == id)
        ?? throw ApiException.NotFound("La cuenta por cobrar");

    private static AccountReceivableDto Map(AccountReceivable a) => new(
        a.Id, a.DocumentNumber, a.CustomerId, a.Customer.Name, a.SaleId, a.Sale?.Number,
        a.RepairOrderId, a.RepairOrder?.Number, a.IssueDate, a.DueDate, a.Total, a.CollectedAmount,
        a.Balance, a.Status, StatusName(a.Status),
        a.Balance > 0 && a.DueDate.Date < DateTime.UtcNow.Date, a.Notes,
        a.Receipts.OrderByDescending(r => r.Date)
            .Select(r => new ReceiptDto(r.Id, r.Date, r.Amount, r.Method, r.Reference, r.Notes, r.User?.FullName))
            .ToList());

    private static string StatusName(ReceivableStatus status) => status switch
    {
        ReceivableStatus.Pending => "Pendiente",
        ReceivableStatus.PartiallyCollected => "Cobro parcial",
        ReceivableStatus.Collected => "Cobrada",
        ReceivableStatus.Cancelled => "Anulada",
        _ => status.ToString()
    };
}
