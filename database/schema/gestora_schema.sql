-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: gestoradb
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `gestoradb`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `gestoradb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `gestoradb`;

--
-- Table structure for table `__efmigrationshistory`
--

DROP TABLE IF EXISTS `__efmigrationshistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `__efmigrationshistory` (
  `MigrationId` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ProductVersion` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`MigrationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accountspayable`
--

DROP TABLE IF EXISTS `accountspayable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accountspayable` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SupplierId` int NOT NULL,
  `PurchaseId` int DEFAULT NULL,
  `DocumentNumber` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `IssueDate` datetime(6) NOT NULL,
  `DueDate` datetime(6) NOT NULL,
  `Total` decimal(18,2) NOT NULL,
  `PaidAmount` decimal(18,2) NOT NULL,
  `Balance` decimal(18,2) NOT NULL,
  `Status` int NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_AccountsPayable_CompanyId_Status_DueDate` (`CompanyId`,`Status`,`DueDate`),
  KEY `IX_AccountsPayable_PurchaseId` (`PurchaseId`),
  KEY `IX_AccountsPayable_SupplierId` (`SupplierId`),
  CONSTRAINT `FK_AccountsPayable_Purchases_PurchaseId` FOREIGN KEY (`PurchaseId`) REFERENCES `purchases` (`Id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AccountsPayable_Suppliers_SupplierId` FOREIGN KEY (`SupplierId`) REFERENCES `suppliers` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accountsreceivable`
--

DROP TABLE IF EXISTS `accountsreceivable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accountsreceivable` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `CustomerId` int NOT NULL,
  `SaleId` int DEFAULT NULL,
  `RepairOrderId` int DEFAULT NULL,
  `DocumentNumber` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `IssueDate` datetime(6) NOT NULL,
  `DueDate` datetime(6) NOT NULL,
  `Total` decimal(18,2) NOT NULL,
  `CollectedAmount` decimal(18,2) NOT NULL,
  `Balance` decimal(18,2) NOT NULL,
  `Status` int NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_AccountsReceivable_CompanyId_Status_DueDate` (`CompanyId`,`Status`,`DueDate`),
  KEY `IX_AccountsReceivable_CustomerId` (`CustomerId`),
  KEY `IX_AccountsReceivable_RepairOrderId` (`RepairOrderId`),
  KEY `IX_AccountsReceivable_SaleId` (`SaleId`),
  CONSTRAINT `FK_AccountsReceivable_Customers_CustomerId` FOREIGN KEY (`CustomerId`) REFERENCES `customers` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_AccountsReceivable_RepairOrders_RepairOrderId` FOREIGN KEY (`RepairOrderId`) REFERENCES `repairorders` (`Id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AccountsReceivable_Sales_SaleId` FOREIGN KEY (`SaleId`) REFERENCES `sales` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auditlogs`
--

DROP TABLE IF EXISTS `auditlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditlogs` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `UserId` int DEFAULT NULL,
  `UserName` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `EntityName` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `EntityId` int DEFAULT NULL,
  `Description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `OldValue` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `NewValue` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IpAddress` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `OccurredAt` datetime(6) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_AuditLogs_CompanyId_OccurredAt` (`CompanyId`,`OccurredAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Categories_CompanyId_Name` (`CompanyId`,`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `TaxId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `DefaultTaxRate` decimal(18,2) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `AccountEmail` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Companies_AccountEmail` (`AccountEmail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `TradeName` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `TaxId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ContactName` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `PaymentTerm` int NOT NULL,
  `CreditDays` int NOT NULL,
  `CreditLimit` decimal(18,2) NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Customers_CompanyId_Code` (`CompanyId`,`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `financecategories`
--

DROP TABLE IF EXISTS `financecategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financecategories` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Kind` int NOT NULL,
  `Description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsSystem` tinyint(1) NOT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_FinanceCategories_CompanyId_Kind_Name` (`CompanyId`,`Kind`,`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `financeentries`
--

DROP TABLE IF EXISTS `financeentries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financeentries` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Kind` int NOT NULL,
  `CategoryId` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `Amount` decimal(18,2) NOT NULL,
  `Description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Method` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Reference` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Source` int NOT NULL,
  `SourceId` int DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_FinanceEntries_CategoryId` (`CategoryId`),
  KEY `IX_FinanceEntries_CompanyId_Kind_Date` (`CompanyId`,`Kind`,`Date`),
  KEY `IX_FinanceEntries_CompanyId_Source_SourceId` (`CompanyId`,`Source`,`SourceId`),
  KEY `IX_FinanceEntries_UserId` (`UserId`),
  CONSTRAINT `FK_FinanceEntries_FinanceCategories_CategoryId` FOREIGN KEY (`CategoryId`) REFERENCES `financecategories` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_FinanceEntries_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventorymovements`
--

DROP TABLE IF EXISTS `inventorymovements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventorymovements` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `ProductId` int NOT NULL,
  `Type` int NOT NULL,
  `Direction` int NOT NULL,
  `Quantity` decimal(18,4) NOT NULL,
  `StockAfter` decimal(18,4) NOT NULL,
  `UnitCost` decimal(18,2) DEFAULT NULL,
  `Reason` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ReferenceType` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ReferenceId` int DEFAULT NULL,
  `OccurredAt` datetime(6) NOT NULL,
  `UserId` int DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_InventoryMovements_CompanyId_ProductId_OccurredAt` (`CompanyId`,`ProductId`,`OccurredAt`),
  KEY `IX_InventoryMovements_ProductId` (`ProductId`),
  KEY `IX_InventoryMovements_UserId` (`UserId`),
  CONSTRAINT `FK_InventoryMovements_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_InventoryMovements_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `AccountPayableId` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `Amount` decimal(18,2) NOT NULL,
  `Method` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Reference` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Notes` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_Payments_AccountPayableId` (`AccountPayableId`),
  KEY `IX_Payments_UserId` (`UserId`),
  CONSTRAINT `FK_Payments_AccountsPayable_AccountPayableId` FOREIGN KEY (`AccountPayableId`) REFERENCES `accountspayable` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_Payments_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Description` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Price` decimal(18,2) NOT NULL,
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `BillingPeriodMonths` int NOT NULL,
  `MaxUsers` int NOT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `SortOrder` int NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Plans_Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionmaterials`
--

DROP TABLE IF EXISTS `productionmaterials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionmaterials` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `ProductionOrderId` int NOT NULL,
  `ProductId` int NOT NULL,
  `PlannedQuantity` decimal(18,4) NOT NULL,
  `ConsumedQuantity` decimal(18,4) NOT NULL,
  `UnitCost` decimal(18,2) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_ProductionMaterials_ProductId` (`ProductId`),
  KEY `IX_ProductionMaterials_ProductionOrderId` (`ProductionOrderId`),
  CONSTRAINT `FK_ProductionMaterials_ProductionOrders_ProductionOrderId` FOREIGN KEY (`ProductionOrderId`) REFERENCES `productionorders` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_ProductionMaterials_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionorders`
--

DROP TABLE IF EXISTS `productionorders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionorders` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ProductId` int NOT NULL,
  `RecipeId` int DEFAULT NULL,
  `Quantity` decimal(18,4) NOT NULL,
  `ProducedQuantity` decimal(18,4) NOT NULL,
  `Status` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `StartedAt` datetime(6) DEFAULT NULL,
  `CompletedAt` datetime(6) DEFAULT NULL,
  `LaborCost` decimal(18,2) NOT NULL,
  `MaterialsCost` decimal(18,2) NOT NULL,
  `TotalCost` decimal(18,2) NOT NULL,
  `UnitCost` decimal(18,2) NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_ProductionOrders_CompanyId_Number` (`CompanyId`,`Number`),
  KEY `IX_ProductionOrders_CompanyId_Status_Date` (`CompanyId`,`Status`,`Date`),
  KEY `IX_ProductionOrders_ProductId` (`ProductId`),
  KEY `IX_ProductionOrders_RecipeId` (`RecipeId`),
  CONSTRAINT `FK_ProductionOrders_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_ProductionOrders_Recipes_RecipeId` FOREIGN KEY (`RecipeId`) REFERENCES `recipes` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Barcode` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Type` int NOT NULL,
  `CategoryId` int DEFAULT NULL,
  `UnitId` int NOT NULL,
  `Cost` decimal(18,2) NOT NULL,
  `Price` decimal(18,2) NOT NULL,
  `TaxRate` decimal(18,2) NOT NULL,
  `Stock` decimal(18,4) NOT NULL,
  `MinStock` decimal(18,4) NOT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Products_CompanyId_Code` (`CompanyId`,`Code`),
  KEY `IX_Products_CategoryId` (`CategoryId`),
  KEY `IX_Products_CompanyId_Barcode` (`CompanyId`,`Barcode`),
  KEY `IX_Products_UnitId` (`UnitId`),
  CONSTRAINT `FK_Products_Categories_CategoryId` FOREIGN KEY (`CategoryId`) REFERENCES `categories` (`Id`) ON DELETE SET NULL,
  CONSTRAINT `FK_Products_Units_UnitId` FOREIGN KEY (`UnitId`) REFERENCES `units` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchaseitems`
--

DROP TABLE IF EXISTS `purchaseitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchaseitems` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `PurchaseId` int NOT NULL,
  `ProductId` int NOT NULL,
  `Quantity` decimal(18,4) NOT NULL,
  `UnitCost` decimal(18,2) NOT NULL,
  `TaxRate` decimal(18,2) NOT NULL,
  `Subtotal` decimal(18,2) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_PurchaseItems_ProductId` (`ProductId`),
  KEY `IX_PurchaseItems_PurchaseId` (`PurchaseId`),
  CONSTRAINT `FK_PurchaseItems_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_PurchaseItems_Purchases_PurchaseId` FOREIGN KEY (`PurchaseId`) REFERENCES `purchases` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `SupplierId` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `Status` int NOT NULL,
  `Subtotal` decimal(18,2) NOT NULL,
  `TaxAmount` decimal(18,2) NOT NULL,
  `Total` decimal(18,2) NOT NULL,
  `SupplierInvoiceNumber` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  `CreditDays` int NOT NULL DEFAULT '0',
  `PaymentTerm` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Purchases_CompanyId_Number` (`CompanyId`,`Number`),
  KEY `IX_Purchases_CompanyId_Status_Date` (`CompanyId`,`Status`,`Date`),
  KEY `IX_Purchases_SupplierId` (`SupplierId`),
  CONSTRAINT `FK_Purchases_Suppliers_SupplierId` FOREIGN KEY (`SupplierId`) REFERENCES `suppliers` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `AccountReceivableId` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `Amount` decimal(18,2) NOT NULL,
  `Method` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Reference` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Notes` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_Receipts_AccountReceivableId` (`AccountReceivableId`),
  KEY `IX_Receipts_UserId` (`UserId`),
  CONSTRAINT `FK_Receipts_AccountsReceivable_AccountReceivableId` FOREIGN KEY (`AccountReceivableId`) REFERENCES `accountsreceivable` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_Receipts_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipeitems`
--

DROP TABLE IF EXISTS `recipeitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipeitems` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `RecipeId` int NOT NULL,
  `ProductId` int NOT NULL,
  `Quantity` decimal(18,4) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_RecipeItems_ProductId` (`ProductId`),
  KEY `IX_RecipeItems_RecipeId` (`RecipeId`),
  CONSTRAINT `FK_RecipeItems_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_RecipeItems_Recipes_RecipeId` FOREIGN KEY (`RecipeId`) REFERENCES `recipes` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ProductId` int NOT NULL,
  `OutputQuantity` decimal(18,4) NOT NULL,
  `LaborCost` decimal(18,2) NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_Recipes_CompanyId_ProductId` (`CompanyId`,`ProductId`),
  KEY `IX_Recipes_ProductId` (`ProductId`),
  CONSTRAINT `FK_Recipes_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `refreshtokens`
--

DROP TABLE IF EXISTS `refreshtokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refreshtokens` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `Token` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ExpiresAt` datetime(6) NOT NULL,
  `RevokedAt` datetime(6) DEFAULT NULL,
  `CreatedByIp` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `ImpersonatedCompanyId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_RefreshTokens_Token` (`Token`),
  KEY `IX_RefreshTokens_UserId` (`UserId`),
  CONSTRAINT `FK_RefreshTokens_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `repairmaterials`
--

DROP TABLE IF EXISTS `repairmaterials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repairmaterials` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `RepairOrderId` int NOT NULL,
  `ProductId` int NOT NULL,
  `Quantity` decimal(18,4) NOT NULL,
  `UnitPrice` decimal(18,2) NOT NULL,
  `Subtotal` decimal(18,2) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_RepairMaterials_ProductId` (`ProductId`),
  KEY `IX_RepairMaterials_RepairOrderId` (`RepairOrderId`),
  CONSTRAINT `FK_RepairMaterials_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_RepairMaterials_RepairOrders_RepairOrderId` FOREIGN KEY (`RepairOrderId`) REFERENCES `repairorders` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `repairorders`
--

DROP TABLE IF EXISTS `repairorders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repairorders` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `CustomerId` int NOT NULL,
  `ItemDescription` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ReportedIssue` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Diagnosis` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Status` int NOT NULL,
  `ReceivedAt` datetime(6) NOT NULL,
  `PromisedAt` datetime(6) DEFAULT NULL,
  `CompletedAt` datetime(6) DEFAULT NULL,
  `DeliveredAt` datetime(6) DEFAULT NULL,
  `LaborCost` decimal(18,2) NOT NULL,
  `MaterialsCost` decimal(18,2) NOT NULL,
  `TaxRate` decimal(18,2) NOT NULL,
  `TaxAmount` decimal(18,2) NOT NULL,
  `Total` decimal(18,2) NOT NULL,
  `PaymentTerm` int NOT NULL,
  `CreditDays` int NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_RepairOrders_CompanyId_Number` (`CompanyId`,`Number`),
  KEY `IX_RepairOrders_CompanyId_Status_ReceivedAt` (`CompanyId`,`Status`,`ReceivedAt`),
  KEY `IX_RepairOrders_CustomerId` (`CustomerId`),
  CONSTRAINT `FK_RepairOrders_Customers_CustomerId` FOREIGN KEY (`CustomerId`) REFERENCES `customers` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rolepermissions`
--

DROP TABLE IF EXISTS `rolepermissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rolepermissions` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `RoleId` int NOT NULL,
  `ModuleKey` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `CanRead` tinyint(1) NOT NULL,
  `CanWrite` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_RolePermissions_RoleId_ModuleKey` (`RoleId`,`ModuleKey`),
  CONSTRAINT `FK_RolePermissions_Roles_RoleId` FOREIGN KEY (`RoleId`) REFERENCES `roles` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `SortOrder` int NOT NULL,
  `Key` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `Scope` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Roles_Key` (`Key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saleitems`
--

DROP TABLE IF EXISTS `saleitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saleitems` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SaleId` int NOT NULL,
  `ProductId` int NOT NULL,
  `Quantity` decimal(18,4) NOT NULL,
  `UnitPrice` decimal(18,2) NOT NULL,
  `DiscountRate` decimal(18,2) NOT NULL,
  `TaxRate` decimal(18,2) NOT NULL,
  `Subtotal` decimal(18,2) NOT NULL,
  `UnitCost` decimal(18,2) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_SaleItems_ProductId` (`ProductId`),
  KEY `IX_SaleItems_SaleId` (`SaleId`),
  CONSTRAINT `FK_SaleItems_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `products` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_SaleItems_Sales_SaleId` FOREIGN KEY (`SaleId`) REFERENCES `sales` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `CustomerId` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `Status` int NOT NULL,
  `PaymentTerm` int NOT NULL,
  `CreditDays` int NOT NULL,
  `Subtotal` decimal(18,2) NOT NULL,
  `DiscountAmount` decimal(18,2) NOT NULL,
  `TaxAmount` decimal(18,2) NOT NULL,
  `Total` decimal(18,2) NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Sales_CompanyId_Number` (`CompanyId`,`Number`),
  KEY `IX_Sales_CompanyId_Status_Date` (`CompanyId`,`Status`,`Date`),
  KEY `IX_Sales_CustomerId` (`CustomerId`),
  CONSTRAINT `FK_Sales_Customers_CustomerId` FOREIGN KEY (`CustomerId`) REFERENCES `customers` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscriptionpayments`
--

DROP TABLE IF EXISTS `subscriptionpayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptionpayments` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SubscriptionId` int NOT NULL,
  `Date` datetime(6) NOT NULL,
  `Amount` decimal(18,2) NOT NULL,
  `Method` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Reference` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `PeriodFrom` datetime(6) NOT NULL,
  `PeriodTo` datetime(6) NOT NULL,
  `Notes` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_SubscriptionPayments_SubscriptionId` (`SubscriptionId`),
  KEY `IX_SubscriptionPayments_UserId` (`UserId`),
  CONSTRAINT `FK_SubscriptionPayments_Subscriptions_SubscriptionId` FOREIGN KEY (`SubscriptionId`) REFERENCES `subscriptions` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_SubscriptionPayments_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `CompanyId` int NOT NULL,
  `PlanId` int NOT NULL,
  `StartDate` datetime(6) NOT NULL,
  `EndDate` datetime(6) NOT NULL,
  `Status` int NOT NULL,
  `Price` decimal(18,2) NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_Subscriptions_CompanyId` (`CompanyId`),
  KEY `IX_Subscriptions_PlanId` (`PlanId`),
  KEY `IX_Subscriptions_Status_EndDate` (`Status`,`EndDate`),
  CONSTRAINT `FK_Subscriptions_Companies_CompanyId` FOREIGN KEY (`CompanyId`) REFERENCES `companies` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `FK_Subscriptions_Plans_PlanId` FOREIGN KEY (`PlanId`) REFERENCES `plans` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `TradeName` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `TaxId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ContactName` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `PaymentTerm` int NOT NULL,
  `CreditDays` int NOT NULL,
  `Notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Suppliers_CompanyId_Code` (`CompanyId`,`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Abbreviation` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `DecimalPlaces` int NOT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Units_CompanyId_Abbreviation` (`CompanyId`,`Abbreviation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `LastName` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `PasswordHash` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `PasswordSalt` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `RoleId` int NOT NULL,
  `LastLoginAt` datetime(6) DEFAULT NULL,
  `FailedLoginAttempts` int NOT NULL,
  `LockedUntil` datetime(6) DEFAULT NULL,
  `CreatedAt` datetime(6) NOT NULL,
  `CreatedByUserId` int DEFAULT NULL,
  `UpdatedAt` datetime(6) DEFAULT NULL,
  `UpdatedByUserId` int DEFAULT NULL,
  `CompanyId` int DEFAULT NULL,
  `TourCompletedAt` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Users_Email` (`Email`),
  KEY `IX_Users_RoleId` (`RoleId`),
  KEY `IX_Users_CompanyId` (`CompanyId`),
  CONSTRAINT `FK_Users_Companies_CompanyId` FOREIGN KEY (`CompanyId`) REFERENCES `companies` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_Users_Roles_RoleId` FOREIGN KEY (`RoleId`) REFERENCES `roles` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'gestoradb'
--

--
-- Dumping routines for database 'gestoradb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
