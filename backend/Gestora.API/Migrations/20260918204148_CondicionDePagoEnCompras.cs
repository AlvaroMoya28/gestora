using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace Gestora.API.Migrations
{
    /// <inheritdoc />
    public partial class CondicionDePagoEnCompras : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<int>(
                name: "CreditDays",
                table: "Purchases",
                type: "int",
                nullable: false,
                defaultValue: 0);

            migrationBuilder.AddColumn<int>(
                name: "PaymentTerm",
                table: "Purchases",
                type: "int",
                nullable: false,
                defaultValue: 0);

            // Hasta ahora el plazo de una compra se leía del proveedor al confirmarla.
            // Se copia a cada compra existente para que su vencimiento siga coincidiendo
            // con el de la cuenta por pagar que ya generó.
            migrationBuilder.Sql(@"
                UPDATE Purchases p
                JOIN Suppliers s ON s.Id = p.SupplierId
                SET p.PaymentTerm = s.PaymentTerm,
                    p.CreditDays  = CASE WHEN s.PaymentTerm = 1 THEN s.CreditDays ELSE 0 END;");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropColumn(
                name: "CreditDays",
                table: "Purchases");

            migrationBuilder.DropColumn(
                name: "PaymentTerm",
                table: "Purchases");
        }
    }
}
