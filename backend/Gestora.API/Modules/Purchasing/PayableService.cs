using Gestora.API.Common;
using Gestora.API.Data;
using Gestora.API.Domain;
using Gestora.API.Modules.Audit;
using Microsoft.EntityFrameworkCore;

namespace Gestora.API.Modules.Purchasing;

/// <summary>
/// Cuentas por pagar y sus pagos. Las cuentas nacen solas al confirmar una compra
/// (<see cref="PurchaseService.ConfirmAsync"/>); este servicio solo las consulta y
/// registra abonos, completos o parciales, contra ellas.
/// </summary>
public class PayableService(GestoraDbContext db, IAuditService audit, ICurrentUser current)
{
    public async Task<PagedResult<AccountPayableDto>> ListAsync(PayableQuery q)
    {
        var query = db.AccountsPayable.AsNoTracking()
            .Include(a => a.Supplier).Include(a => a.Purchase).Include(a => a.Payments).ThenInclude(p => p.User)
            .AsQueryable();

        if (q.SupplierId is { } supplierId) query = query.Where(a => a.SupplierId == supplierId);
        if (q.Status is { } status) query = query.Where(a => a.Status == status);
        if (q.Overdue == true)
        {
            var today = DateTime.UtcNow.Date;
            query = query.Where(a => a.Balance > 0 && a.DueDate < today);
        }

        if (!string.IsNullOrWhiteSpace(q.Search))
        {
            var term = q.Search.Trim();
            query = query.Where(a => a.DocumentNumber.Contains(term) || a.Supplier.Name.Contains(term));
        }

        var total = await query.CountAsync();
        var rows = await query
            .OrderBy(a => a.Status == PayableStatus.Paid).ThenBy(a => a.DueDate)
            .Skip((Math.Max(q.Page, 1) - 1) * q.PageSize).Take(q.PageSize)
            .ToListAsync();

        return new PagedResult<AccountPayableDto>(rows.Select(Map).ToList(), total, q.Page, q.PageSize);
    }

    public async Task<AccountPayableDto> GetAsync(int id)
    {
        var payable = await LoadAsync(id);
        return Map(payable);
    }

    /// <summary>
    /// Registra un abono. No se permite pagar más de lo que se debe: un sobrepago
    /// indica un error de digitación que hay que corregir, no absorber en silencio.
    /// </summary>
    public async Task<AccountPayableDto> RegisterPaymentAsync(int payableId, RegisterPaymentRequest request)
    {
        var payable = await LoadAsync(payableId);

        if (payable.Status is PayableStatus.Paid or PayableStatus.Cancelled)
            throw new ApiException("Esta cuenta ya está saldada y no admite más pagos.");

        if (request.Amount > payable.Balance)
            throw ApiException.Conflict(
                $"El monto ({request.Amount:N2}) supera el saldo pendiente ({payable.Balance:N2}).");

        var payment = new Payment
        {
            AccountPayableId = payable.Id,
            Date = request.Date ?? DateTime.UtcNow,
            Amount = request.Amount,
            Method = request.Method.Trim(),
            Reference = request.Reference?.Trim(),
            Notes = request.Notes?.Trim(),
            UserId = current.IsAuthenticated ? current.UserId : null
        };
        db.Payments.Add(payment);

        payable.PaidAmount += request.Amount;
        payable.Balance -= request.Amount;
        payable.Status = payable.Balance <= 0 ? PayableStatus.Paid : PayableStatus.PartiallyPaid;

        audit.Track("Pago registrado", "payables", nameof(AccountPayable), payable.Id,
            $"Pago de {request.Amount:N2} a {payable.DocumentNumber} ({payable.Supplier.Name}) · saldo {payable.Balance:N2}");

        await db.SaveChangesAsync();
        return await GetAsync(payableId);
    }

    private async Task<AccountPayable> LoadAsync(int id) =>
        await db.AccountsPayable
            .Include(a => a.Supplier).Include(a => a.Purchase)
            .Include(a => a.Payments).ThenInclude(p => p.User)
            .FirstOrDefaultAsync(a => a.Id == id)
        ?? throw ApiException.NotFound("La cuenta por pagar");

    private static AccountPayableDto Map(AccountPayable a) => new(
        a.Id, a.DocumentNumber, a.SupplierId, a.Supplier.Name, a.PurchaseId, a.Purchase?.Number,
        a.IssueDate, a.DueDate, a.Total, a.PaidAmount, a.Balance, a.Status, StatusName(a.Status),
        a.Balance > 0 && a.DueDate.Date < DateTime.UtcNow.Date, a.Notes,
        a.Payments.OrderByDescending(p => p.Date)
            .Select(p => new PaymentDto(p.Id, p.Date, p.Amount, p.Method, p.Reference, p.Notes, p.User?.FullName))
            .ToList());

    private static string StatusName(PayableStatus status) => status switch
    {
        PayableStatus.Pending => "Pendiente",
        PayableStatus.PartiallyPaid => "Pago parcial",
        PayableStatus.Paid => "Pagada",
        PayableStatus.Cancelled => "Cancelada",
        _ => status.ToString()
    };
}
