using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace Gestora.API.Migrations
{
    /// <inheritdoc />
    public partial class VentaDesdeProduccion : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<int>(
                name: "ProductionOrderId",
                table: "SaleItems",
                type: "int",
                nullable: true);

            migrationBuilder.CreateIndex(
                name: "IX_SaleItems_ProductionOrderId",
                table: "SaleItems",
                column: "ProductionOrderId");

            migrationBuilder.AddForeignKey(
                name: "FK_SaleItems_ProductionOrders_ProductionOrderId",
                table: "SaleItems",
                column: "ProductionOrderId",
                principalTable: "ProductionOrders",
                principalColumn: "Id",
                onDelete: ReferentialAction.Restrict);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropForeignKey(
                name: "FK_SaleItems_ProductionOrders_ProductionOrderId",
                table: "SaleItems");

            migrationBuilder.DropIndex(
                name: "IX_SaleItems_ProductionOrderId",
                table: "SaleItems");

            migrationBuilder.DropColumn(
                name: "ProductionOrderId",
                table: "SaleItems");
        }
    }
}
