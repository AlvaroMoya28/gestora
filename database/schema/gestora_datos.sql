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
-- Dumping data for table `__efmigrationshistory`
--

LOCK TABLES `__efmigrationshistory` WRITE;
/*!40000 ALTER TABLE `__efmigrationshistory` DISABLE KEYS */;
INSERT INTO `__efmigrationshistory` VALUES ('20260904194349_InitialSchema','8.0.13'),('20260904203142_AddPurchasingAndPayables','8.0.13'),('20260907140508_SaasPlatformAndRoleScopes','8.0.13'),('20260918193603_VentasProduccionReparacionesFinanzas','8.0.13'),('20260918204148_CondicionDePagoEnCompras','8.0.13');
/*!40000 ALTER TABLE `__efmigrationshistory` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accountspayable`
--

LOCK TABLES `accountspayable` WRITE;
/*!40000 ALTER TABLE `accountspayable` DISABLE KEYS */;
INSERT INTO `accountspayable` VALUES (1,1,1,'COM-0001','2026-09-18 19:50:48.942545','2026-10-18 19:50:48.942545',29380.00,15000.00,14380.00,1,NULL,'2026-09-18 19:50:49.273313',2,'2026-09-18 19:50:49.577584',2,1),(2,2,2,'FE-00012455','2026-08-11 20:14:13.000000','2026-09-10 20:14:13.000000',793260.00,793260.00,0.00,2,NULL,'2026-09-18 20:14:13.725687',5,'2026-09-18 20:14:14.048890',5,3),(3,3,3,'FE-0004471','2026-08-18 20:14:13.000000','2026-09-02 20:14:13.000000',392110.00,392110.00,0.00,2,NULL,'2026-09-18 20:14:13.767700',5,'2026-09-18 20:14:13.937701',5,3),(4,4,4,'FE-0098120','2026-08-27 20:14:13.000000','2026-09-26 20:14:13.000000',694950.00,0.00,694950.00,0,NULL,'2026-09-18 20:14:13.796477',5,NULL,NULL,3),(5,5,5,'FE-0033187','2026-09-04 20:14:13.000000','2026-09-04 20:14:13.000000',280240.00,140120.00,140120.00,1,NULL,'2026-09-18 20:14:13.822988',5,'2026-09-18 20:14:14.037064',5,3),(6,2,6,'FE-00012781','2026-09-12 20:14:13.000000','2026-10-12 20:14:13.000000',718680.00,0.00,718680.00,0,NULL,'2026-09-18 20:14:13.852330',5,NULL,NULL,3),(7,6,7,'COM-0006','2026-09-18 20:14:13.860774','2026-09-18 20:14:13.860774',107124.00,0.00,107124.00,0,NULL,'2026-09-18 20:37:36.791022',5,NULL,NULL,3),(9,6,9,'COM-0007','2026-09-18 00:00:00.000000','2026-09-18 00:00:00.000000',3503.00,0.00,3503.00,0,NULL,'2026-09-18 20:49:07.061820',5,NULL,NULL,3);
/*!40000 ALTER TABLE `accountspayable` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accountsreceivable`
--

LOCK TABLES `accountsreceivable` WRITE;
/*!40000 ALTER TABLE `accountsreceivable` DISABLE KEYS */;
INSERT INTO `accountsreceivable` VALUES (1,1,1,NULL,'VEN-0001','2026-09-18 19:49:01.919723','2026-10-18 19:49:01.919723',19323.00,10000.00,9323.00,1,NULL,'2026-09-18 19:49:02.723876',2,'2026-09-18 19:49:03.652364',2,1),(2,1,NULL,1,'REP-0001','2026-09-18 19:50:22.947705','2026-10-03 19:50:22.947705',24408.00,0.00,24408.00,0,NULL,'2026-09-18 19:50:22.948293',2,NULL,NULL,1),(3,2,2,NULL,'VEN-0001','2026-08-23 20:14:14.000000','2026-09-22 20:14:14.000000',836200.00,836200.00,0.00,2,NULL,'2026-09-18 20:14:14.789205',5,'2026-09-18 21:42:32.370437',5,3),(4,3,3,NULL,'VEN-0002','2026-08-30 20:14:14.000000','2026-09-29 20:14:14.000000',627997.50,627997.50,0.00,2,NULL,'2026-09-18 20:14:14.828997',5,'2026-09-18 21:42:32.536779',5,3),(5,4,4,NULL,'VEN-0003','2026-09-03 20:14:14.000000','2026-09-18 20:14:14.000000',402280.00,402280.00,0.00,2,NULL,'2026-09-18 20:14:14.854014',5,'2026-09-18 20:14:15.090062',5,3),(6,5,5,NULL,'VEN-0004','2026-09-07 20:14:14.000000','2026-09-07 20:14:14.000000',458780.00,458780.00,0.00,2,NULL,'2026-09-18 20:14:14.877664',5,'2026-09-18 20:14:15.046403',5,3),(7,2,6,NULL,'VEN-0005','2026-09-10 20:14:14.000000','2026-10-10 20:14:14.000000',548050.00,0.00,548050.00,0,NULL,'2026-09-18 20:14:14.904578',5,NULL,NULL,3),(8,6,7,NULL,'VEN-0006','2026-09-14 20:14:14.000000','2026-10-14 20:14:14.000000',237300.00,0.00,237300.00,0,NULL,'2026-09-18 20:14:14.932375',5,NULL,NULL,3),(9,5,8,NULL,'VEN-0007','2026-09-17 20:14:14.000000','2026-09-17 20:14:14.000000',117520.00,117520.00,0.00,2,NULL,'2026-09-18 20:14:14.958167',5,'2026-09-18 20:14:15.073939',5,3),(10,2,NULL,2,'REP-0001','2026-09-18 20:14:15.293597','2026-10-18 20:14:15.293597',75145.00,0.00,75145.00,0,NULL,'2026-09-18 20:14:15.294430',5,NULL,NULL,3),(11,3,NULL,3,'REP-0002','2026-09-18 20:14:15.335326','2026-09-18 20:14:15.335326',41245.00,41245.00,0.00,2,NULL,'2026-09-18 20:14:15.335449',5,'2026-09-18 21:42:32.588404',5,3),(12,6,10,NULL,'VEN-0009','2026-09-18 20:37:03.683671','2026-10-18 20:37:03.683671',0.00,0.00,0.00,0,NULL,'2026-09-18 20:37:47.321015',5,NULL,NULL,3);
/*!40000 ALTER TABLE `accountsreceivable` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=354 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditlogs`
--

LOCK TABLES `auditlogs` WRITE;
/*!40000 ALTER TABLE `auditlogs` DISABLE KEYS */;
INSERT INTO `auditlogs` VALUES (1,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:06:15.152338','2026-09-07 14:06:15.166932',NULL,NULL,NULL,0),(2,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-07 14:06:15.996086','2026-09-07 14:06:15.996157',NULL,NULL,NULL,1),(3,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:07:23.887553','2026-09-07 14:07:23.887659',NULL,NULL,NULL,0),(4,1,'Desarrollador Gestora','Acceso de soporte','auth','Company',1,'Desarrollador Gestora (Gestora) entró a ver el sistema como Mi Empresa',NULL,NULL,'::1','2026-09-07 14:07:24.043475','2026-09-07 14:07:24.043564',1,NULL,NULL,1),(5,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:09:57.557741','2026-09-07 14:09:57.557931',NULL,NULL,NULL,0),(6,1,'Desarrollador Gestora','Alta de empresa','platform_companies','Company',NULL,'Carpinteria Rodriguez registrada con el plan Profesional · cuenta admin@carpinteriarodriguez.com',NULL,NULL,'::1','2026-09-07 14:09:58.904298','2026-09-07 14:09:58.904338',1,NULL,NULL,0),(7,3,'Marta Rodriguez','Inicio de sesión','auth','User',3,'Marta Rodriguez inició sesión',NULL,NULL,'::1','2026-09-07 14:09:59.202164','2026-09-07 14:09:59.202211',NULL,NULL,NULL,2),(8,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:10:14.660582','2026-09-07 14:10:14.660644',NULL,NULL,NULL,0),(9,1,'Desarrollador Gestora','Cobro registrado','platform_billing','Subscription',1,'Mi Empresa pagó 45,000.00 · vence 07/12/2026',NULL,NULL,'::1','2026-09-07 14:10:14.900010','2026-09-07 14:10:14.900068',1,NULL,NULL,0),(10,1,'Desarrollador Gestora','Cambio de estado','platform_companies','Company',2,'Carpinteria Rodriguez: Activa → Suspendida · Falta de pago',NULL,NULL,'::1','2026-09-07 14:10:15.164817','2026-09-07 14:10:15.164869',1,NULL,NULL,0),(11,1,'Desarrollador Gestora','Cambio de estado','platform_companies','Company',2,'Carpinteria Rodriguez: Suspendida → Activa',NULL,NULL,'::1','2026-09-07 14:10:15.441961','2026-09-07 14:10:15.442021',1,NULL,NULL,0),(12,3,'Marta Rodriguez','Inicio de sesión','auth','User',3,'Marta Rodriguez inició sesión',NULL,NULL,'::1','2026-09-07 14:10:15.522198','2026-09-07 14:10:15.522257',NULL,NULL,NULL,2),(13,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-07 14:10:40.911962','2026-09-07 14:10:40.912031',NULL,NULL,NULL,1),(14,2,'Administración Mi Empresa','Creación','users','User',NULL,'Usuario consulta@gestora.local con rol Consulta',NULL,NULL,'::1','2026-09-07 14:10:42.137209','2026-09-07 14:10:42.137229',2,NULL,NULL,1),(15,4,'Ana Consulta','Inicio de sesión','auth','User',4,'Ana Consulta inició sesión',NULL,NULL,'::1','2026-09-07 14:10:42.563104','2026-09-07 14:10:42.563149',NULL,NULL,NULL,1),(16,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:25:13.755907','2026-09-07 14:25:13.755994',NULL,NULL,NULL,0),(17,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-07 14:25:14.057476','2026-09-07 14:25:14.057530',NULL,NULL,NULL,1),(18,4,'Ana Consulta','Inicio de sesión','auth','User',4,'Ana Consulta inició sesión',NULL,NULL,'::1','2026-09-07 14:25:14.354537','2026-09-07 14:25:14.354589',NULL,NULL,NULL,1),(19,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:44:05.102182','2026-09-07 14:44:05.116336',NULL,NULL,NULL,0),(20,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-07 14:44:08.684380','2026-09-07 14:44:08.684961',1,NULL,NULL,0),(21,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-07 14:44:16.667379','2026-09-07 14:44:16.667448',NULL,NULL,NULL,1),(22,2,'Administración Mi Empresa','Cierre de sesión','auth','User',2,'Sesión cerrada',NULL,NULL,'::1','2026-09-07 14:44:18.919139','2026-09-07 14:44:18.919271',2,NULL,NULL,1),(23,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:48:21.950193','2026-09-07 14:48:21.950296',NULL,NULL,NULL,0),(24,1,'Desarrollador Gestora','Acceso de soporte','auth','Company',2,'Desarrollador Gestora (Gestora) entró a ver el sistema como Carpinteria Rodriguez',NULL,NULL,'::1','2026-09-07 14:48:30.992256','2026-09-07 14:48:30.992336',1,NULL,NULL,2),(25,1,'Desarrollador Gestora','Cambio de plan','platform_billing','Subscription',2,'Carpinteria Rodriguez','Profesional · 45,000.00','Profesional · 15,000.00','::1','2026-09-07 14:48:54.752359','2026-09-07 14:48:54.752442',1,NULL,NULL,0),(26,1,'Desarrollador Gestora','Cambio de plan','platform_billing','Subscription',1,'Mi Empresa','Básico · 25,000.00','Básico · 15,000.00','::1','2026-09-07 14:49:02.644549','2026-09-07 14:49:02.644696',1,NULL,NULL,0),(27,1,'Desarrollador Gestora','Actualización','platform_plans','Plan',1,'Plan','Básico · 25,000.00','Básico · 15,000.00','::1','2026-09-07 14:49:19.679321','2026-09-07 14:49:19.679439',1,NULL,NULL,0),(28,1,'Desarrollador Gestora','Actualización','platform_plans','Plan',2,'Plan','Profesional · 45,000.00','Profesional · 20,000.00','::1','2026-09-07 14:49:25.005211','2026-09-07 14:49:25.005742',1,NULL,NULL,0),(29,1,'Desarrollador Gestora','Actualización','platform_plans','Plan',3,'Plan','Profesional anual · 450,000.00','Profesional anual · 50,000.00','::1','2026-09-07 14:49:30.747319','2026-09-07 14:49:30.747462',1,NULL,NULL,0),(30,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-07 14:49:50.481887','2026-09-07 14:49:50.482002',1,NULL,NULL,0),(31,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-07 14:49:55.164521','2026-09-07 14:49:55.164581',NULL,NULL,NULL,1),(32,2,'Administración Mi Empresa','Cierre de sesión','auth','User',2,'Sesión cerrada',NULL,NULL,'::1','2026-09-07 14:50:03.256271','2026-09-07 14:50:03.256379',2,NULL,NULL,1),(33,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:50:06.205508','2026-09-07 14:50:06.205575',NULL,NULL,NULL,0),(34,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-07 14:50:10.527921','2026-09-07 14:50:10.528061',1,NULL,NULL,0),(35,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-07 14:54:57.306265','2026-09-07 14:54:57.319192',NULL,NULL,NULL,0),(36,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-07 14:55:20.878702','2026-09-07 14:55:20.879258',1,NULL,NULL,0),(37,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-07 14:55:23.331676','2026-09-07 14:55:23.331757',NULL,NULL,NULL,1),(38,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-18 19:17:12.930097','2026-09-18 19:17:12.944253',NULL,NULL,NULL,1),(39,2,'Administración Mi Empresa','Cierre de sesión','auth','User',2,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 19:18:23.993737','2026-09-18 19:18:23.994226',2,NULL,NULL,1),(40,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 19:18:29.625177','2026-09-18 19:18:29.625261',NULL,NULL,NULL,0),(41,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 19:18:38.214521','2026-09-18 19:18:38.214604',1,NULL,NULL,0),(42,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-18 19:18:44.123866','2026-09-18 19:18:44.123944',NULL,NULL,NULL,1),(43,2,'Administración Mi Empresa','Cierre de sesión','auth','User',2,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 19:18:47.509826','2026-09-18 19:18:47.509986',2,NULL,NULL,1),(44,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 19:18:53.055150','2026-09-18 19:18:53.055231',NULL,NULL,NULL,0),(45,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 19:19:13.727545','2026-09-18 19:19:13.727708',1,NULL,NULL,0),(46,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 19:19:19.726857','2026-09-18 19:19:19.726950',NULL,NULL,NULL,0),(47,1,'Desarrollador Gestora','Cierre de sesión','auth','User',1,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 19:19:25.125794','2026-09-18 19:19:25.125878',1,NULL,NULL,0),(48,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-18 19:19:29.152805','2026-09-18 19:19:29.152874',NULL,NULL,NULL,1),(49,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-18 19:47:08.090415','2026-09-18 19:47:08.107195',NULL,NULL,NULL,1),(50,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-18 19:49:01.083150','2026-09-18 19:49:01.097644',NULL,NULL,NULL,1),(51,2,'Administración Mi Empresa','Creación','customers','Customer',NULL,'Cliente CLI-0001 - Cliente Prueba Ventas',NULL,NULL,'::1','2026-09-18 19:49:01.441137','2026-09-18 19:49:01.441259',2,NULL,NULL,1),(52,2,'Administración Mi Empresa','Creación','products','Product',NULL,'Producto TST-V1 - Producto de prueba',NULL,NULL,'::1','2026-09-18 19:49:01.593217','2026-09-18 19:49:01.593314',2,NULL,NULL,1),(53,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',1,'Existencia inicial de 50.00 cja · Producto de prueba','Existencia: 0.00','Existencia: 50.00','::1','2026-09-18 19:49:01.650969','2026-09-18 19:49:01.651056',2,NULL,NULL,1),(54,2,'Administración Mi Empresa','Creación','sales','Sale',NULL,'Venta VEN-0001 a Cliente Prueba Ventas por 19,323.00',NULL,NULL,'::1','2026-09-18 19:49:01.966789','2026-09-18 19:49:01.966883',2,NULL,NULL,1),(55,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',1,'Venta de 10.00 cja · Producto de prueba','Existencia: 50.00','Existencia: 40.00','::1','2026-09-18 19:49:02.699760','2026-09-18 19:49:02.699806',2,NULL,NULL,1),(56,2,'Administración Mi Empresa','Confirmación','sales','Sale',1,'Venta VEN-0001 confirmada · inventario descargado · cuenta por cobrar por 19,323.00',NULL,NULL,'::1','2026-09-18 19:49:02.723796','2026-09-18 19:49:02.723876',2,NULL,NULL,1),(57,2,'Administración Mi Empresa','Cobro registrado','receivables','AccountReceivable',1,'Cobro de 10,000.00 sobre VEN-0001 (Cliente Prueba Ventas) · saldo 9,323.00',NULL,NULL,'::1','2026-09-18 19:49:03.652306','2026-09-18 19:49:03.652364',2,NULL,NULL,1),(58,2,'Administración Mi Empresa','Creación','products','Product',NULL,'Producto MAT-1 - Madera',NULL,NULL,'::1','2026-09-18 19:49:20.382084','2026-09-18 19:49:20.382127',2,NULL,NULL,1),(59,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',2,'Existencia inicial de 200.00 cja · Madera','Existencia: 0.00','Existencia: 200.00','::1','2026-09-18 19:49:20.384452','2026-09-18 19:49:20.384504',2,NULL,NULL,1),(60,2,'Administración Mi Empresa','Creación','products','Product',NULL,'Producto FAB-1 - Mesa fabricada',NULL,NULL,'::1','2026-09-18 19:49:20.542562','2026-09-18 19:49:20.542592',2,NULL,NULL,1),(61,2,'Administración Mi Empresa','Creación','production','Recipe',NULL,'Receta Mesa estandar para Mesa fabricada',NULL,NULL,'::1','2026-09-18 19:49:46.124845','2026-09-18 19:49:46.124927',2,NULL,NULL,1),(62,2,'Administración Mi Empresa','Creación','production','ProductionOrder',NULL,'Orden PRO-0001: 5.00 de Mesa fabricada',NULL,NULL,'::1','2026-09-18 19:49:59.157285','2026-09-18 19:49:59.157362',2,NULL,NULL,1),(63,2,'Administración Mi Empresa','Inicio','production','ProductionOrder',1,'Orden PRO-0001 iniciada',NULL,NULL,'::1','2026-09-18 19:49:59.915346','2026-09-18 19:49:59.915451',2,NULL,NULL,1),(64,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',2,'Consumo de producción de 42.00 cja · Madera','Existencia: 200.00','Existencia: 158.00','::1','2026-09-18 19:50:00.069668','2026-09-18 19:50:00.069704',2,NULL,NULL,1),(65,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',3,'Producción terminada de 5.00 cja · Mesa fabricada','Existencia: 0.00','Existencia: 5.00','::1','2026-09-18 19:50:00.074227','2026-09-18 19:50:00.074258',2,NULL,NULL,1),(66,2,'Administración Mi Empresa','Terminada','production','ProductionOrder',1,'Orden PRO-0001: 5.00 de Mesa fabricada · costo unitario 6,200.00 (materiales 21,000.00 + mano de obra 10,000.00)',NULL,NULL,'::1','2026-09-18 19:50:00.076902','2026-09-18 19:50:00.076955',2,NULL,NULL,1),(67,2,'Administración Mi Empresa','Creación','repairs','RepairOrder',NULL,'Reparación REP-0001: Mesa de comedor de Cliente Prueba Ventas',NULL,NULL,'::1','2026-09-18 19:50:21.652116','2026-09-18 19:50:21.652162',2,NULL,NULL,1),(68,2,'Administración Mi Empresa','En proceso','repairs','RepairOrder',1,'Reparación REP-0001 en proceso',NULL,NULL,'::1','2026-09-18 19:50:22.520441','2026-09-18 19:50:22.520501',2,NULL,NULL,1),(69,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',2,'Consumo en reparación de 4.00 cja · Madera','Existencia: 158.00','Existencia: 154.00','::1','2026-09-18 19:50:22.666473','2026-09-18 19:50:22.666491',2,NULL,NULL,1),(70,2,'Administración Mi Empresa','Terminada','repairs','RepairOrder',1,'Reparación REP-0001 terminada · material descargado por 3,600.00',NULL,NULL,'::1','2026-09-18 19:50:22.669350','2026-09-18 19:50:22.669403',2,NULL,NULL,1),(71,2,'Administración Mi Empresa','Entrega','repairs','RepairOrder',1,'Reparación REP-0001 entregada a Cliente Prueba Ventas · cuenta por cobrar por 24,408.00',NULL,NULL,'::1','2026-09-18 19:50:22.948262','2026-09-18 19:50:22.948293',2,NULL,NULL,1),(72,2,'Administración Mi Empresa','Creación','suppliers','Supplier',NULL,'Proveedor PRV-0001 - Proveedor Prueba',NULL,NULL,'::1','2026-09-18 19:50:48.807206','2026-09-18 19:50:48.807275',2,NULL,NULL,1),(73,2,'Administración Mi Empresa','Creación','purchases','Purchase',NULL,'Compra COM-0001 a Proveedor Prueba',NULL,NULL,'::1','2026-09-18 19:50:48.978116','2026-09-18 19:50:48.978166',2,NULL,NULL,1),(74,2,'Administración Mi Empresa','Movimiento de inventario','inventory','Product',2,'Compra de 50.00 cja · Madera','Existencia: 154.00','Existencia: 204.00','::1','2026-09-18 19:50:49.256745','2026-09-18 19:50:49.256773',2,NULL,NULL,1),(75,2,'Administración Mi Empresa','Confirmación','purchases','Purchase',1,'Compra COM-0001 confirmada · inventario actualizado · cuenta por pagar COM-0001 por 29,380.00',NULL,NULL,'::1','2026-09-18 19:50:49.273266','2026-09-18 19:50:49.273313',2,NULL,NULL,1),(76,2,'Administración Mi Empresa','Pago registrado','payables','AccountPayable',1,'Pago de 15,000.00 a COM-0001 (Proveedor Prueba) · saldo 14,380.00',NULL,NULL,'::1','2026-09-18 19:50:49.577531','2026-09-18 19:50:49.577584',2,NULL,NULL,1),(77,2,'Administración Mi Empresa','Creación','expenses','FinanceEntry',NULL,'Gasto de 250,000.00 · Alquiler · Alquiler del local',NULL,NULL,'::1','2026-09-18 19:50:49.991418','2026-09-18 19:50:49.991441',2,NULL,NULL,1),(78,2,'Administración Mi Empresa','Actualización','settings','Company',1,'Datos de la empresa','Mi Empresa · CRC · IVA 13.00%','Empresa Demo · CRC · IVA 13.00%','::1','2026-09-18 19:51:12.866073','2026-09-18 19:51:12.866224',2,NULL,NULL,1),(79,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 19:51:13.148991','2026-09-18 19:51:13.149032',NULL,NULL,NULL,0),(80,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 19:51:30.614676','2026-09-18 19:51:30.614717',NULL,NULL,NULL,0),(81,4,'Ana Consulta','Inicio de sesión','auth','User',4,'Ana Consulta inició sesión',NULL,NULL,'::1','2026-09-18 19:51:57.818251','2026-09-18 19:51:57.818284',NULL,NULL,NULL,1),(82,4,'Ana Consulta','Inicio de sesión','auth','User',4,'Ana Consulta inició sesión',NULL,NULL,'::1','2026-09-18 19:52:10.009853','2026-09-18 19:52:10.009900',NULL,NULL,NULL,1),(83,2,'Administración Mi Empresa','Inicio de sesión','auth','User',2,'Administración Mi Empresa inició sesión',NULL,NULL,'::1','2026-09-18 19:53:37.885172','2026-09-18 19:53:37.898528',NULL,NULL,NULL,1),(84,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 20:14:12.132453','2026-09-18 20:14:12.158870',NULL,NULL,NULL,0),(85,1,'Desarrollador Gestora','Alta de empresa','platform_companies','Company',NULL,'Funeraria Nazareno registrada con el plan Profesional · cuenta admin@nazareno.cr',NULL,NULL,'::1','2026-09-18 20:14:12.542037','2026-09-18 20:14:12.542300',1,NULL,NULL,0),(86,1,'Desarrollador Gestora','Cobro registrado','platform_billing','Subscription',3,'Funeraria Nazareno pagó 20,000.00 · vence 18/10/2026',NULL,NULL,'::1','2026-09-18 20:14:12.684727','2026-09-18 20:14:12.684849',1,NULL,NULL,0),(87,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:14:12.761110','2026-09-18 20:14:12.761234',NULL,NULL,NULL,3),(88,5,'Marielos Rodriguez','Creación','products','Category',NULL,'Categoría Ataudes',NULL,NULL,'::1','2026-09-18 20:14:12.793046','2026-09-18 20:14:12.793097',5,NULL,NULL,3),(89,5,'Marielos Rodriguez','Creación','products','Category',NULL,'Categoría Urnas y baules',NULL,NULL,'::1','2026-09-18 20:14:12.800508','2026-09-18 20:14:12.800570',5,NULL,NULL,3),(90,5,'Marielos Rodriguez','Creación','products','Category',NULL,'Categoría Madera y tableros',NULL,NULL,'::1','2026-09-18 20:14:12.807249','2026-09-18 20:14:12.807320',5,NULL,NULL,3),(91,5,'Marielos Rodriguez','Creación','products','Category',NULL,'Categoría Telas y acolchados',NULL,NULL,'::1','2026-09-18 20:14:12.814510','2026-09-18 20:14:12.814574',5,NULL,NULL,3),(92,5,'Marielos Rodriguez','Creación','products','Category',NULL,'Categoría Herrajes y accesorios',NULL,NULL,'::1','2026-09-18 20:14:12.821188','2026-09-18 20:14:12.821233',5,NULL,NULL,3),(93,5,'Marielos Rodriguez','Creación','products','Category',NULL,'Categoría Pinturas y acabados',NULL,NULL,'::1','2026-09-18 20:14:12.828081','2026-09-18 20:14:12.828129',5,NULL,NULL,3),(94,5,'Marielos Rodriguez','Creación','suppliers','Supplier',NULL,'Proveedor PRV-0001 - Maderas del Valle S.A.',NULL,NULL,'::1','2026-09-18 20:14:12.883180','2026-09-18 20:14:12.883284',5,NULL,NULL,3),(95,5,'Marielos Rodriguez','Creación','suppliers','Supplier',NULL,'Proveedor PRV-0002 - Textiles Monserrat',NULL,NULL,'::1','2026-09-18 20:14:12.904442','2026-09-18 20:14:12.904493',5,NULL,NULL,3),(96,5,'Marielos Rodriguez','Creación','suppliers','Supplier',NULL,'Proveedor PRV-0003 - Herrajes y Accesorios CR',NULL,NULL,'::1','2026-09-18 20:14:12.911754','2026-09-18 20:14:12.911804',5,NULL,NULL,3),(97,5,'Marielos Rodriguez','Creación','suppliers','Supplier',NULL,'Proveedor PRV-0004 - Pinturas y Solventes del Norte',NULL,NULL,'::1','2026-09-18 20:14:12.922116','2026-09-18 20:14:12.922192',5,NULL,NULL,3),(98,5,'Marielos Rodriguez','Creación','suppliers','Supplier',NULL,'Proveedor PRV-0005 - Ferreteria El Carpintero',NULL,NULL,'::1','2026-09-18 20:14:12.930337','2026-09-18 20:14:12.930385',5,NULL,NULL,3),(99,5,'Marielos Rodriguez','Creación','customers','Customer',NULL,'Cliente CLI-0001 - Funeraria La Paz',NULL,NULL,'::1','2026-09-18 20:14:12.976437','2026-09-18 20:14:12.976570',5,NULL,NULL,3),(100,5,'Marielos Rodriguez','Creación','customers','Customer',NULL,'Cliente CLI-0002 - Funeraria San Rafael',NULL,NULL,'::1','2026-09-18 20:14:12.995987','2026-09-18 20:14:12.996041',5,NULL,NULL,3),(101,5,'Marielos Rodriguez','Creación','customers','Customer',NULL,'Cliente CLI-0003 - Servicios Funerarios El Descanso',NULL,NULL,'::1','2026-09-18 20:14:13.003730','2026-09-18 20:14:13.003783',5,NULL,NULL,3),(102,5,'Marielos Rodriguez','Creación','customers','Customer',NULL,'Cliente CLI-0004 - Funeraria Monte de los Olivos',NULL,NULL,'::1','2026-09-18 20:14:13.011069','2026-09-18 20:14:13.011136',5,NULL,NULL,3),(103,5,'Marielos Rodriguez','Creación','customers','Customer',NULL,'Cliente CLI-0005 - Funeraria del Recuerdo',NULL,NULL,'::1','2026-09-18 20:14:13.018459','2026-09-18 20:14:13.018515',5,NULL,NULL,3),(104,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-MAD-01 - Tablero de laurel 2.40 x 0.30 m',NULL,NULL,'::1','2026-09-18 20:14:13.050491','2026-09-18 20:14:13.050565',5,NULL,NULL,3),(105,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Existencia inicial de 180.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 0.00','Existencia: 180.00','::1','2026-09-18 20:14:13.097054','2026-09-18 20:14:13.097135',5,NULL,NULL,3),(106,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-MAD-02 - Tablero de cedro 2.40 x 0.30 m',NULL,NULL,'::1','2026-09-18 20:14:13.131553','2026-09-18 20:14:13.131610',5,NULL,NULL,3),(107,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',5,'Existencia inicial de 60.00 ud · Tablero de cedro 2.40 x 0.30 m','Existencia: 0.00','Existencia: 60.00','::1','2026-09-18 20:14:13.134189','2026-09-18 20:14:13.134220',5,NULL,NULL,3),(108,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-MAD-03 - Plywood 4 x 8 pies',NULL,NULL,'::1','2026-09-18 20:14:13.143883','2026-09-18 20:14:13.143935',5,NULL,NULL,3),(109,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',6,'Existencia inicial de 45.00 ud · Plywood 4 x 8 pies','Existencia: 0.00','Existencia: 45.00','::1','2026-09-18 20:14:13.146342','2026-09-18 20:14:13.146374',5,NULL,NULL,3),(110,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-TEL-01 - Raso blanco',NULL,NULL,'::1','2026-09-18 20:14:13.156330','2026-09-18 20:14:13.156386',5,NULL,NULL,3),(111,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',7,'Existencia inicial de 220.00 m · Raso blanco','Existencia: 0.00','Existencia: 220.00','::1','2026-09-18 20:14:13.158878','2026-09-18 20:14:13.158915',5,NULL,NULL,3),(112,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-TEL-02 - Raso champagne',NULL,NULL,'::1','2026-09-18 20:14:13.166906','2026-09-18 20:14:13.166945',5,NULL,NULL,3),(113,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',8,'Existencia inicial de 95.00 m · Raso champagne','Existencia: 0.00','Existencia: 95.00','::1','2026-09-18 20:14:13.169147','2026-09-18 20:14:13.169179',5,NULL,NULL,3),(114,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-TEL-03 - Acolchado esponja 1 pulgada',NULL,NULL,'::1','2026-09-18 20:14:13.178530','2026-09-18 20:14:13.178624',5,NULL,NULL,3),(115,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Existencia inicial de 140.00 m2 · Acolchado esponja 1 pulgada','Existencia: 0.00','Existencia: 140.00','::1','2026-09-18 20:14:13.181411','2026-09-18 20:14:13.181446',5,NULL,NULL,3),(116,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-HER-01 - Manija metalica cromada',NULL,NULL,'::1','2026-09-18 20:14:13.190229','2026-09-18 20:14:13.190351',5,NULL,NULL,3),(117,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',10,'Existencia inicial de 320.00 ud · Manija metalica cromada','Existencia: 0.00','Existencia: 320.00','::1','2026-09-18 20:14:13.193446','2026-09-18 20:14:13.193481',5,NULL,NULL,3),(118,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-HER-02 - Bisagra piano 30 cm',NULL,NULL,'::1','2026-09-18 20:14:13.203437','2026-09-18 20:14:13.203524',5,NULL,NULL,3),(119,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Existencia inicial de 180.00 ud · Bisagra piano 30 cm','Existencia: 0.00','Existencia: 180.00','::1','2026-09-18 20:14:13.206308','2026-09-18 20:14:13.206344',5,NULL,NULL,3),(120,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-HER-03 - Crucifijo decorativo',NULL,NULL,'::1','2026-09-18 20:14:13.214528','2026-09-18 20:14:13.214561',5,NULL,NULL,3),(121,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',12,'Existencia inicial de 85.00 ud · Crucifijo decorativo','Existencia: 0.00','Existencia: 85.00','::1','2026-09-18 20:14:13.216711','2026-09-18 20:14:13.216729',5,NULL,NULL,3),(122,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-HER-04 - Tornillo para madera 2 pulgadas (caja 100)',NULL,NULL,'::1','2026-09-18 20:14:13.226800','2026-09-18 20:14:13.226824',5,NULL,NULL,3),(123,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',13,'Existencia inicial de 24.00 cja · Tornillo para madera 2 pulgadas (caja 100)','Existencia: 0.00','Existencia: 24.00','::1','2026-09-18 20:14:13.228699','2026-09-18 20:14:13.228714',5,NULL,NULL,3),(124,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-PIN-01 - Barniz poliuretano',NULL,NULL,'::1','2026-09-18 20:14:13.236106','2026-09-18 20:14:13.236129',5,NULL,NULL,3),(125,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',14,'Existencia inicial de 68.00 L · Barniz poliuretano','Existencia: 0.00','Existencia: 68.00','::1','2026-09-18 20:14:13.238185','2026-09-18 20:14:13.238203',5,NULL,NULL,3),(126,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-PIN-02 - Sellador para madera',NULL,NULL,'::1','2026-09-18 20:14:13.248783','2026-09-18 20:14:13.248883',5,NULL,NULL,3),(127,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',15,'Existencia inicial de 42.00 L · Sellador para madera','Existencia: 0.00','Existencia: 42.00','::1','2026-09-18 20:14:13.251852','2026-09-18 20:14:13.251889',5,NULL,NULL,3),(128,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-PIN-03 - Tinte caoba',NULL,NULL,'::1','2026-09-18 20:14:13.260554','2026-09-18 20:14:13.260601',5,NULL,NULL,3),(129,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',16,'Existencia inicial de 26.00 L · Tinte caoba','Existencia: 0.00','Existencia: 26.00','::1','2026-09-18 20:14:13.262863','2026-09-18 20:14:13.262896',5,NULL,NULL,3),(130,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto MP-CON-01 - Pegamento de contacto',NULL,NULL,'::1','2026-09-18 20:14:13.273115','2026-09-18 20:14:13.273162',5,NULL,NULL,3),(131,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',17,'Existencia inicial de 31.00 L · Pegamento de contacto','Existencia: 0.00','Existencia: 31.00','::1','2026-09-18 20:14:13.275531','2026-09-18 20:14:13.275565',5,NULL,NULL,3),(132,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-ATA-01 - Ataud economico blanco',NULL,NULL,'::1','2026-09-18 20:14:13.284100','2026-09-18 20:14:13.284146',5,NULL,NULL,3),(133,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',18,'Existencia inicial de 12.00 ud · Ataud economico blanco','Existencia: 0.00','Existencia: 12.00','::1','2026-09-18 20:14:13.287214','2026-09-18 20:14:13.287268',5,NULL,NULL,3),(134,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-ATA-02 - Ataud clasico caoba',NULL,NULL,'::1','2026-09-18 20:14:13.296330','2026-09-18 20:14:13.296380',5,NULL,NULL,3),(135,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',19,'Existencia inicial de 7.00 ud · Ataud clasico caoba','Existencia: 0.00','Existencia: 7.00','::1','2026-09-18 20:14:13.298673','2026-09-18 20:14:13.298711',5,NULL,NULL,3),(136,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-ATA-03 - Ataud premium en cedro',NULL,NULL,'::1','2026-09-18 20:14:13.307468','2026-09-18 20:14:13.307515',5,NULL,NULL,3),(137,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',20,'Existencia inicial de 3.00 ud · Ataud premium en cedro','Existencia: 0.00','Existencia: 3.00','::1','2026-09-18 20:14:13.309607','2026-09-18 20:14:13.309637',5,NULL,NULL,3),(138,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-ATA-04 - Ataud infantil',NULL,NULL,'::1','2026-09-18 20:14:13.319467','2026-09-18 20:14:13.319522',5,NULL,NULL,3),(139,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',21,'Existencia inicial de 4.00 ud · Ataud infantil','Existencia: 0.00','Existencia: 4.00','::1','2026-09-18 20:14:13.322209','2026-09-18 20:14:13.322243',5,NULL,NULL,3),(140,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-URN-01 - Urna de madera tallada',NULL,NULL,'::1','2026-09-18 20:14:13.331384','2026-09-18 20:14:13.331407',5,NULL,NULL,3),(141,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',22,'Existencia inicial de 18.00 ud · Urna de madera tallada','Existencia: 0.00','Existencia: 18.00','::1','2026-09-18 20:14:13.333458','2026-09-18 20:14:13.333474',5,NULL,NULL,3),(142,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-URN-02 - Urna sencilla en laurel',NULL,NULL,'::1','2026-09-18 20:14:13.341276','2026-09-18 20:14:13.341303',5,NULL,NULL,3),(143,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',23,'Existencia inicial de 22.00 ud · Urna sencilla en laurel','Existencia: 0.00','Existencia: 22.00','::1','2026-09-18 20:14:13.343177','2026-09-18 20:14:13.343197',5,NULL,NULL,3),(144,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-BAU-01 - Baul para cenizas mediano',NULL,NULL,'::1','2026-09-18 20:14:13.351476','2026-09-18 20:14:13.351504',5,NULL,NULL,3),(145,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',24,'Existencia inicial de 9.00 ud · Baul para cenizas mediano','Existencia: 0.00','Existencia: 9.00','::1','2026-09-18 20:14:13.353485','2026-09-18 20:14:13.353500',5,NULL,NULL,3),(146,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto PT-BAU-02 - Baul para cenizas grande',NULL,NULL,'::1','2026-09-18 20:14:13.361302','2026-09-18 20:14:13.361360',5,NULL,NULL,3),(147,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',25,'Existencia inicial de 6.00 ud · Baul para cenizas grande','Existencia: 0.00','Existencia: 6.00','::1','2026-09-18 20:14:13.363737','2026-09-18 20:14:13.363752',5,NULL,NULL,3),(148,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto SV-REP-01 - Mano de obra de reparacion',NULL,NULL,'::1','2026-09-18 20:14:13.371696','2026-09-18 20:14:13.371744',5,NULL,NULL,3),(149,5,'Marielos Rodriguez','Creación','products','Product',NULL,'Producto SV-TRA-01 - Transporte y entrega',NULL,NULL,'::1','2026-09-18 20:14:13.379909','2026-09-18 20:14:13.379933',5,NULL,NULL,3),(150,5,'Marielos Rodriguez','Creación','production','Recipe',NULL,'Receta Ataud economico blanco para Ataud economico blanco',NULL,NULL,'::1','2026-09-18 20:14:13.412576','2026-09-18 20:14:13.412610',5,NULL,NULL,3),(151,5,'Marielos Rodriguez','Creación','production','Recipe',NULL,'Receta Ataud clasico caoba para Ataud clasico caoba',NULL,NULL,'::1','2026-09-18 20:14:13.472245','2026-09-18 20:14:13.472277',5,NULL,NULL,3),(152,5,'Marielos Rodriguez','Creación','production','Recipe',NULL,'Receta Ataud premium en cedro para Ataud premium en cedro',NULL,NULL,'::1','2026-09-18 20:14:13.489356','2026-09-18 20:14:13.489407',5,NULL,NULL,3),(153,5,'Marielos Rodriguez','Creación','production','Recipe',NULL,'Receta Urna de madera tallada para Urna de madera tallada',NULL,NULL,'::1','2026-09-18 20:14:13.505264','2026-09-18 20:14:13.505317',5,NULL,NULL,3),(154,5,'Marielos Rodriguez','Creación','production','Recipe',NULL,'Receta Baul para cenizas mediano para Baul para cenizas mediano',NULL,NULL,'::1','2026-09-18 20:14:13.519790','2026-09-18 20:14:13.519813',5,NULL,NULL,3),(155,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0001 a Maderas del Valle S.A.',NULL,NULL,'::1','2026-09-18 20:14:13.612073','2026-09-18 20:14:13.612145',5,NULL,NULL,3),(156,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Compra de 60.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 180.00','Existencia: 240.00','::1','2026-09-18 20:14:13.691115','2026-09-18 20:14:13.691181',5,NULL,NULL,3),(157,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',6,'Compra de 15.00 ud · Plywood 4 x 8 pies','Existencia: 45.00','Existencia: 60.00','::1','2026-09-18 20:14:13.696100','2026-09-18 20:14:13.696142',5,NULL,NULL,3),(158,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',2,'Compra COM-0001 confirmada · inventario actualizado · cuenta por pagar FE-00012455 por 793,260.00',NULL,NULL,'::1','2026-09-18 20:14:13.725615','2026-09-18 20:14:13.725687',5,NULL,NULL,3),(159,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0002 a Textiles Monserrat',NULL,NULL,'::1','2026-09-18 20:14:13.749499','2026-09-18 20:14:13.749555',5,NULL,NULL,3),(160,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',7,'Compra de 80.00 m · Raso blanco','Existencia: 220.00','Existencia: 300.00','::1','2026-09-18 20:14:13.762622','2026-09-18 20:14:13.762691',5,NULL,NULL,3),(161,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Compra de 50.00 m2 · Acolchado esponja 1 pulgada','Existencia: 140.00','Existencia: 190.00','::1','2026-09-18 20:14:13.765730','2026-09-18 20:14:13.765762',5,NULL,NULL,3),(162,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',3,'Compra COM-0002 confirmada · inventario actualizado · cuenta por pagar FE-0004471 por 392,110.00',NULL,NULL,'::1','2026-09-18 20:14:13.767669','2026-09-18 20:14:13.767700',5,NULL,NULL,3),(163,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0003 a Herrajes y Accesorios CR',NULL,NULL,'::1','2026-09-18 20:14:13.778011','2026-09-18 20:14:13.778074',5,NULL,NULL,3),(164,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',10,'Compra de 120.00 ud · Manija metalica cromada','Existencia: 320.00','Existencia: 440.00','::1','2026-09-18 20:14:13.789056','2026-09-18 20:14:13.789130',5,NULL,NULL,3),(165,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Compra de 60.00 ud · Bisagra piano 30 cm','Existencia: 180.00','Existencia: 240.00','::1','2026-09-18 20:14:13.792397','2026-09-18 20:14:13.792430',5,NULL,NULL,3),(166,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',12,'Compra de 30.00 ud · Crucifijo decorativo','Existencia: 85.00','Existencia: 115.00','::1','2026-09-18 20:14:13.794830','2026-09-18 20:14:13.794845',5,NULL,NULL,3),(167,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',4,'Compra COM-0003 confirmada · inventario actualizado · cuenta por pagar FE-0098120 por 694,950.00',NULL,NULL,'::1','2026-09-18 20:14:13.796445','2026-09-18 20:14:13.796477',5,NULL,NULL,3),(168,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0004 a Pinturas y Solventes del Norte',NULL,NULL,'::1','2026-09-18 20:14:13.807068','2026-09-18 20:14:13.807106',5,NULL,NULL,3),(169,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',14,'Compra de 25.00 L · Barniz poliuretano','Existencia: 68.00','Existencia: 93.00','::1','2026-09-18 20:14:13.818453','2026-09-18 20:14:13.818506',5,NULL,NULL,3),(170,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',15,'Compra de 15.00 L · Sellador para madera','Existencia: 42.00','Existencia: 57.00','::1','2026-09-18 20:14:13.821178','2026-09-18 20:14:13.821211',5,NULL,NULL,3),(171,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',5,'Compra COM-0004 confirmada · inventario actualizado · cuenta por pagar FE-0033187 por 280,240.00',NULL,NULL,'::1','2026-09-18 20:14:13.822959','2026-09-18 20:14:13.822988',5,NULL,NULL,3),(172,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0005 a Maderas del Valle S.A.',NULL,NULL,'::1','2026-09-18 20:14:13.833141','2026-09-18 20:14:13.833200',5,NULL,NULL,3),(173,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Compra de 40.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 240.00','Existencia: 280.00','::1','2026-09-18 20:14:13.847124','2026-09-18 20:14:13.847150',5,NULL,NULL,3),(174,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',5,'Compra de 20.00 ud · Tablero de cedro 2.40 x 0.30 m','Existencia: 60.00','Existencia: 80.00','::1','2026-09-18 20:14:13.849905','2026-09-18 20:14:13.849943',5,NULL,NULL,3),(175,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',6,'Compra COM-0005 confirmada · inventario actualizado · cuenta por pagar FE-00012781 por 718,680.00',NULL,NULL,'::1','2026-09-18 20:14:13.852297','2026-09-18 20:14:13.852330',5,NULL,NULL,3),(176,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0006 a Ferreteria El Carpintero',NULL,NULL,'::1','2026-09-18 20:14:13.862731','2026-09-18 20:14:13.862805',5,NULL,NULL,3),(177,5,'Marielos Rodriguez','Pago registrado','payables','AccountPayable',3,'Pago de 392,110.00 a FE-0004471 (Textiles Monserrat) · saldo 0.00',NULL,NULL,'::1','2026-09-18 20:14:13.937606','2026-09-18 20:14:13.937701',5,NULL,NULL,3),(178,5,'Marielos Rodriguez','Pago registrado','payables','AccountPayable',5,'Pago de 140,120.00 a FE-0033187 (Pinturas y Solventes del Norte) · saldo 140,120.00',NULL,NULL,'::1','2026-09-18 20:14:14.037042','2026-09-18 20:14:14.037064',5,NULL,NULL,3),(179,5,'Marielos Rodriguez','Pago registrado','payables','AccountPayable',2,'Pago de 793,260.00 a FE-00012455 (Maderas del Valle S.A.) · saldo 0.00',NULL,NULL,'::1','2026-09-18 20:14:14.048864','2026-09-18 20:14:14.048890',5,NULL,NULL,3),(180,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0001: 8.00 de Ataud economico blanco',NULL,NULL,'::1','2026-09-18 20:14:14.128073','2026-09-18 20:14:14.128140',5,NULL,NULL,3),(181,5,'Marielos Rodriguez','Inicio','production','ProductionOrder',2,'Orden PRO-0001 iniciada',NULL,NULL,'::1','2026-09-18 20:14:14.207741','2026-09-18 20:14:14.207830',5,NULL,NULL,3),(182,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Consumo de producción de 48.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 280.00','Existencia: 232.00','::1','2026-09-18 20:14:14.242638','2026-09-18 20:14:14.242662',5,NULL,NULL,3),(183,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',6,'Consumo de producción de 8.00 ud · Plywood 4 x 8 pies','Existencia: 60.00','Existencia: 52.00','::1','2026-09-18 20:14:14.248421','2026-09-18 20:14:14.248471',5,NULL,NULL,3),(184,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',7,'Consumo de producción de 40.00 m · Raso blanco','Existencia: 300.00','Existencia: 260.00','::1','2026-09-18 20:14:14.253722','2026-09-18 20:14:14.253750',5,NULL,NULL,3),(185,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Consumo de producción de 24.00 m2 · Acolchado esponja 1 pulgada','Existencia: 190.00','Existencia: 166.00','::1','2026-09-18 20:14:14.257248','2026-09-18 20:14:14.257308',5,NULL,NULL,3),(186,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',10,'Consumo de producción de 48.00 ud · Manija metalica cromada','Existencia: 440.00','Existencia: 392.00','::1','2026-09-18 20:14:14.261050','2026-09-18 20:14:14.261085',5,NULL,NULL,3),(187,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Consumo de producción de 16.00 ud · Bisagra piano 30 cm','Existencia: 240.00','Existencia: 224.00','::1','2026-09-18 20:14:14.264180','2026-09-18 20:14:14.264211',5,NULL,NULL,3),(188,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',13,'Consumo de producción de 8.00 cja · Tornillo para madera 2 pulgadas (caja 100)','Existencia: 24.00','Existencia: 16.00','::1','2026-09-18 20:14:14.267355','2026-09-18 20:14:14.267389',5,NULL,NULL,3),(189,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',15,'Consumo de producción de 8.00 L · Sellador para madera','Existencia: 57.00','Existencia: 49.00','::1','2026-09-18 20:14:14.270754','2026-09-18 20:14:14.270788',5,NULL,NULL,3),(190,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',17,'Consumo de producción de 4.00 L · Pegamento de contacto','Existencia: 31.00','Existencia: 27.00','::1','2026-09-18 20:14:14.274135','2026-09-18 20:14:14.274167',5,NULL,NULL,3),(191,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',18,'Producción terminada de 8.00 ud · Ataud economico blanco','Existencia: 12.00','Existencia: 20.00','::1','2026-09-18 20:14:14.277389','2026-09-18 20:14:14.277420',5,NULL,NULL,3),(192,5,'Marielos Rodriguez','Terminada','production','ProductionOrder',2,'Orden PRO-0001: 8.00 de Ataud economico blanco · costo unitario 142,250.00 (materiales 962,000.00 + mano de obra 176,000.00)',NULL,NULL,'::1','2026-09-18 20:14:14.279972','2026-09-18 20:14:14.280015',5,NULL,NULL,3),(193,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0002: 5.00 de Ataud clasico caoba',NULL,NULL,'::1','2026-09-18 20:14:14.298751','2026-09-18 20:14:14.298820',5,NULL,NULL,3),(194,5,'Marielos Rodriguez','Inicio','production','ProductionOrder',3,'Orden PRO-0002 iniciada',NULL,NULL,'::1','2026-09-18 20:14:14.313098','2026-09-18 20:14:14.313176',5,NULL,NULL,3),(195,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Consumo de producción de 35.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 232.00','Existencia: 197.00','::1','2026-09-18 20:14:14.331306','2026-09-18 20:14:14.331380',5,NULL,NULL,3),(196,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',6,'Consumo de producción de 5.00 ud · Plywood 4 x 8 pies','Existencia: 52.00','Existencia: 47.00','::1','2026-09-18 20:14:14.337637','2026-09-18 20:14:14.337672',5,NULL,NULL,3),(197,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',7,'Consumo de producción de 25.00 m · Raso blanco','Existencia: 260.00','Existencia: 235.00','::1','2026-09-18 20:14:14.341709','2026-09-18 20:14:14.341775',5,NULL,NULL,3),(198,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Consumo de producción de 15.00 m2 · Acolchado esponja 1 pulgada','Existencia: 166.00','Existencia: 151.00','::1','2026-09-18 20:14:14.347111','2026-09-18 20:14:14.347186',5,NULL,NULL,3),(199,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',10,'Consumo de producción de 30.00 ud · Manija metalica cromada','Existencia: 392.00','Existencia: 362.00','::1','2026-09-18 20:14:14.351182','2026-09-18 20:14:14.351201',5,NULL,NULL,3),(200,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Consumo de producción de 10.00 ud · Bisagra piano 30 cm','Existencia: 224.00','Existencia: 214.00','::1','2026-09-18 20:14:14.353395','2026-09-18 20:14:14.353413',5,NULL,NULL,3),(201,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',12,'Consumo de producción de 5.00 ud · Crucifijo decorativo','Existencia: 115.00','Existencia: 110.00','::1','2026-09-18 20:14:14.355416','2026-09-18 20:14:14.355447',5,NULL,NULL,3),(202,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',13,'Consumo de producción de 5.00 cja · Tornillo para madera 2 pulgadas (caja 100)','Existencia: 16.00','Existencia: 11.00','::1','2026-09-18 20:14:14.359450','2026-09-18 20:14:14.359462',5,NULL,NULL,3),(203,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',14,'Consumo de producción de 7.50 L · Barniz poliuretano','Existencia: 93.00','Existencia: 85.50','::1','2026-09-18 20:14:14.361928','2026-09-18 20:14:14.361943',5,NULL,NULL,3),(204,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',16,'Consumo de producción de 4.00 L · Tinte caoba','Existencia: 26.00','Existencia: 22.00','::1','2026-09-18 20:14:14.364029','2026-09-18 20:14:14.364040',5,NULL,NULL,3),(205,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',17,'Consumo de producción de 2.50 L · Pegamento de contacto','Existencia: 27.00','Existencia: 24.50','::1','2026-09-18 20:14:14.366603','2026-09-18 20:14:14.366620',5,NULL,NULL,3),(206,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',19,'Producción terminada de 5.00 ud · Ataud clasico caoba','Existencia: 7.00','Existencia: 12.00','::1','2026-09-18 20:14:14.369121','2026-09-18 20:14:14.369135',5,NULL,NULL,3),(207,5,'Marielos Rodriguez','Terminada','production','ProductionOrder',3,'Orden PRO-0002: 5.00 de Ataud clasico caoba · costo unitario 172,770.00 (materiales 723,850.00 + mano de obra 140,000.00)',NULL,NULL,'::1','2026-09-18 20:14:14.371145','2026-09-18 20:14:14.371183',5,NULL,NULL,3),(208,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0003: 12.00 de Urna de madera tallada',NULL,NULL,'::1','2026-09-18 20:14:14.384356','2026-09-18 20:14:14.384378',5,NULL,NULL,3),(209,5,'Marielos Rodriguez','Inicio','production','ProductionOrder',4,'Orden PRO-0003 iniciada',NULL,NULL,'::1','2026-09-18 20:14:14.394005','2026-09-18 20:14:14.394056',5,NULL,NULL,3),(210,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Consumo de producción de 6.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 197.00','Existencia: 191.00','::1','2026-09-18 20:14:14.416583','2026-09-18 20:14:14.416632',5,NULL,NULL,3),(211,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',14,'Consumo de producción de 2.40 L · Barniz poliuretano','Existencia: 85.50','Existencia: 83.10','::1','2026-09-18 20:14:14.421246','2026-09-18 20:14:14.421269',5,NULL,NULL,3),(212,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',16,'Consumo de producción de 1.20 L · Tinte caoba','Existencia: 22.00','Existencia: 20.80','::1','2026-09-18 20:14:14.424595','2026-09-18 20:14:14.424645',5,NULL,NULL,3),(213,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',17,'Consumo de producción de 0.90 L · Pegamento de contacto','Existencia: 24.50','Existencia: 23.60','::1','2026-09-18 20:14:14.429271','2026-09-18 20:14:14.429317',5,NULL,NULL,3),(214,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',22,'Producción terminada de 12.00 ud · Urna de madera tallada','Existencia: 18.00','Existencia: 30.00','::1','2026-09-18 20:14:14.432547','2026-09-18 20:14:14.432591',5,NULL,NULL,3),(215,5,'Marielos Rodriguez','Terminada','production','ProductionOrder',4,'Orden PRO-0003: 12.00 de Urna de madera tallada · costo unitario 11,367.50 (materiales 82,410.00 + mano de obra 54,000.00)',NULL,NULL,'::1','2026-09-18 20:14:14.435551','2026-09-18 20:14:14.435604',5,NULL,NULL,3),(216,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0004: 6.00 de Baul para cenizas mediano',NULL,NULL,'::1','2026-09-18 20:14:14.453141','2026-09-18 20:14:14.453192',5,NULL,NULL,3),(217,5,'Marielos Rodriguez','Inicio','production','ProductionOrder',5,'Orden PRO-0004 iniciada',NULL,NULL,'::1','2026-09-18 20:14:14.465655','2026-09-18 20:14:14.465746',5,NULL,NULL,3),(218,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Consumo de producción de 6.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 191.00','Existencia: 185.00','::1','2026-09-18 20:14:14.481999','2026-09-18 20:14:14.482047',5,NULL,NULL,3),(219,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',7,'Consumo de producción de 4.50 m · Raso blanco','Existencia: 235.00','Existencia: 230.50','::1','2026-09-18 20:14:14.485705','2026-09-18 20:14:14.485751',5,NULL,NULL,3),(220,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Consumo de producción de 6.00 ud · Bisagra piano 30 cm','Existencia: 214.00','Existencia: 208.00','::1','2026-09-18 20:14:14.489422','2026-09-18 20:14:14.489469',5,NULL,NULL,3),(221,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',14,'Consumo de producción de 1.80 L · Barniz poliuretano','Existencia: 83.10','Existencia: 81.30','::1','2026-09-18 20:14:14.492835','2026-09-18 20:14:14.492875',5,NULL,NULL,3),(222,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',24,'Producción terminada de 6.00 ud · Baul para cenizas mediano','Existencia: 9.00','Existencia: 15.00','::1','2026-09-18 20:14:14.496042','2026-09-18 20:14:14.496079',5,NULL,NULL,3),(223,5,'Marielos Rodriguez','Terminada','production','ProductionOrder',5,'Orden PRO-0004: 6.00 de Baul para cenizas mediano · costo unitario 21,090.00 (materiales 84,540.00 + mano de obra 42,000.00)',NULL,NULL,'::1','2026-09-18 20:14:14.498663','2026-09-18 20:14:14.498732',5,NULL,NULL,3),(224,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0005: 6.00 de Ataud economico blanco',NULL,NULL,'::1','2026-09-18 20:14:14.518360','2026-09-18 20:14:14.518404',5,NULL,NULL,3),(225,5,'Marielos Rodriguez','Inicio','production','ProductionOrder',6,'Orden PRO-0005 iniciada',NULL,NULL,'::1','2026-09-18 20:14:14.532313','2026-09-18 20:14:14.532397',5,NULL,NULL,3),(226,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Consumo de producción de 36.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 185.00','Existencia: 149.00','::1','2026-09-18 20:14:14.550745','2026-09-18 20:14:14.550805',5,NULL,NULL,3),(227,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',6,'Consumo de producción de 6.00 ud · Plywood 4 x 8 pies','Existencia: 47.00','Existencia: 41.00','::1','2026-09-18 20:14:14.554715','2026-09-18 20:14:14.554751',5,NULL,NULL,3),(228,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',7,'Consumo de producción de 30.00 m · Raso blanco','Existencia: 230.50','Existencia: 200.50','::1','2026-09-18 20:14:14.557790','2026-09-18 20:14:14.557823',5,NULL,NULL,3),(229,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Consumo de producción de 18.00 m2 · Acolchado esponja 1 pulgada','Existencia: 151.00','Existencia: 133.00','::1','2026-09-18 20:14:14.561071','2026-09-18 20:14:14.561089',5,NULL,NULL,3),(230,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',10,'Consumo de producción de 36.00 ud · Manija metalica cromada','Existencia: 362.00','Existencia: 326.00','::1','2026-09-18 20:14:14.563797','2026-09-18 20:14:14.563838',5,NULL,NULL,3),(231,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Consumo de producción de 12.00 ud · Bisagra piano 30 cm','Existencia: 208.00','Existencia: 196.00','::1','2026-09-18 20:14:14.567188','2026-09-18 20:14:14.567225',5,NULL,NULL,3),(232,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',13,'Consumo de producción de 6.00 cja · Tornillo para madera 2 pulgadas (caja 100)','Existencia: 11.00','Existencia: 5.00','::1','2026-09-18 20:14:14.571020','2026-09-18 20:14:14.571054',5,NULL,NULL,3),(233,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',15,'Consumo de producción de 6.00 L · Sellador para madera','Existencia: 49.00','Existencia: 43.00','::1','2026-09-18 20:14:14.574313','2026-09-18 20:14:14.574352',5,NULL,NULL,3),(234,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',17,'Consumo de producción de 3.00 L · Pegamento de contacto','Existencia: 23.60','Existencia: 20.60','::1','2026-09-18 20:14:14.577617','2026-09-18 20:14:14.577658',5,NULL,NULL,3),(235,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',18,'Producción terminada de 6.00 ud · Ataud economico blanco','Existencia: 20.00','Existencia: 26.00','::1','2026-09-18 20:14:14.580924','2026-09-18 20:14:14.580960',5,NULL,NULL,3),(236,5,'Marielos Rodriguez','Terminada','production','ProductionOrder',6,'Orden PRO-0005: 6.00 de Ataud economico blanco · costo unitario 142,250.00 (materiales 721,500.00 + mano de obra 132,000.00)',NULL,NULL,'::1','2026-09-18 20:14:14.583937','2026-09-18 20:14:14.584001',5,NULL,NULL,3),(237,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0006: 2.00 de Ataud premium en cedro',NULL,NULL,'::1','2026-09-18 20:14:14.601020','2026-09-18 20:14:14.601065',5,NULL,NULL,3),(238,5,'Marielos Rodriguez','Inicio','production','ProductionOrder',7,'Orden PRO-0006 iniciada',NULL,NULL,'::1','2026-09-18 20:14:14.614795','2026-09-18 20:14:14.614877',5,NULL,NULL,3),(239,5,'Marielos Rodriguez','Creación','production','ProductionOrder',NULL,'Orden PRO-0007: 4.00 de Ataud clasico caoba',NULL,NULL,'::1','2026-09-18 20:14:14.632148','2026-09-18 20:14:14.632201',5,NULL,NULL,3),(240,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0001 a Funeraria La Paz por 836,200.00',NULL,NULL,'::1','2026-09-18 20:14:14.692889','2026-09-18 20:14:14.692912',5,NULL,NULL,3),(241,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',18,'Venta de 5.00 ud · Ataud economico blanco','Existencia: 26.00','Existencia: 21.00','::1','2026-09-18 20:14:14.750885','2026-09-18 20:14:14.750920',5,NULL,NULL,3),(242,5,'Marielos Rodriguez','Confirmación','sales','Sale',2,'Venta VEN-0001 confirmada · inventario descargado · cuenta por cobrar por 836,200.00',NULL,NULL,'::1','2026-09-18 20:14:14.789126','2026-09-18 20:14:14.789205',5,NULL,NULL,3),(243,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0002 a Funeraria San Rafael por 627,997.50',NULL,NULL,'::1','2026-09-18 20:14:14.815064','2026-09-18 20:14:14.815107',5,NULL,NULL,3),(244,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',19,'Venta de 3.00 ud · Ataud clasico caoba','Existencia: 12.00','Existencia: 9.00','::1','2026-09-18 20:14:14.826589','2026-09-18 20:14:14.826633',5,NULL,NULL,3),(245,5,'Marielos Rodriguez','Confirmación','sales','Sale',3,'Venta VEN-0002 confirmada · inventario descargado · cuenta por cobrar por 627,997.50',NULL,NULL,'::1','2026-09-18 20:14:14.828963','2026-09-18 20:14:14.828997',5,NULL,NULL,3),(246,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0003 a Servicios Funerarios El Descanso por 402,280.00',NULL,NULL,'::1','2026-09-18 20:14:14.839002','2026-09-18 20:14:14.839044',5,NULL,NULL,3),(247,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',22,'Venta de 6.00 ud · Urna de madera tallada','Existencia: 30.00','Existencia: 24.00','::1','2026-09-18 20:14:14.849097','2026-09-18 20:14:14.849135',5,NULL,NULL,3),(248,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',24,'Venta de 2.00 ud · Baul para cenizas mediano','Existencia: 15.00','Existencia: 13.00','::1','2026-09-18 20:14:14.851969','2026-09-18 20:14:14.852002',5,NULL,NULL,3),(249,5,'Marielos Rodriguez','Confirmación','sales','Sale',4,'Venta VEN-0003 confirmada · inventario descargado · cuenta por cobrar por 402,280.00',NULL,NULL,'::1','2026-09-18 20:14:14.853988','2026-09-18 20:14:14.854014',5,NULL,NULL,3),(250,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0004 a Funeraria Monte de los Olivos por 458,780.00',NULL,NULL,'::1','2026-09-18 20:14:14.862740','2026-09-18 20:14:14.862775',5,NULL,NULL,3),(251,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',18,'Venta de 2.00 ud · Ataud economico blanco','Existencia: 21.00','Existencia: 19.00','::1','2026-09-18 20:14:14.872748','2026-09-18 20:14:14.872793',5,NULL,NULL,3),(252,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',23,'Venta de 4.00 ud · Urna sencilla en laurel','Existencia: 22.00','Existencia: 18.00','::1','2026-09-18 20:14:14.875780','2026-09-18 20:14:14.875807',5,NULL,NULL,3),(253,5,'Marielos Rodriguez','Confirmación','sales','Sale',5,'Venta VEN-0004 confirmada · inventario descargado · cuenta por cobrar por 458,780.00',NULL,NULL,'::1','2026-09-18 20:14:14.877639','2026-09-18 20:14:14.877664',5,NULL,NULL,3),(254,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0005 a Funeraria La Paz por 548,050.00',NULL,NULL,'::1','2026-09-18 20:14:14.887181','2026-09-18 20:14:14.887222',5,NULL,NULL,3),(255,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',19,'Venta de 2.00 ud · Ataud clasico caoba','Existencia: 9.00','Existencia: 7.00','::1','2026-09-18 20:14:14.897311','2026-09-18 20:14:14.897375',5,NULL,NULL,3),(256,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',21,'Venta de 1.00 ud · Ataud infantil','Existencia: 4.00','Existencia: 3.00','::1','2026-09-18 20:14:14.901397','2026-09-18 20:14:14.901452',5,NULL,NULL,3),(257,5,'Marielos Rodriguez','Confirmación','sales','Sale',6,'Venta VEN-0005 confirmada · inventario descargado · cuenta por cobrar por 548,050.00',NULL,NULL,'::1','2026-09-18 20:14:14.904519','2026-09-18 20:14:14.904578',5,NULL,NULL,3),(258,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0006 a Funeraria del Recuerdo por 237,300.00',NULL,NULL,'::1','2026-09-18 20:14:14.914599','2026-09-18 20:14:14.914641',5,NULL,NULL,3),(259,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',23,'Venta de 5.00 ud · Urna sencilla en laurel','Existencia: 18.00','Existencia: 13.00','::1','2026-09-18 20:14:14.926152','2026-09-18 20:14:14.926202',5,NULL,NULL,3),(260,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',25,'Venta de 1.00 ud · Baul para cenizas grande','Existencia: 6.00','Existencia: 5.00','::1','2026-09-18 20:14:14.929915','2026-09-18 20:14:14.929963',5,NULL,NULL,3),(261,5,'Marielos Rodriguez','Confirmación','sales','Sale',7,'Venta VEN-0006 confirmada · inventario descargado · cuenta por cobrar por 237,300.00',NULL,NULL,'::1','2026-09-18 20:14:14.932340','2026-09-18 20:14:14.932375',5,NULL,NULL,3),(262,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0007 a Funeraria Monte de los Olivos por 117,520.00',NULL,NULL,'::1','2026-09-18 20:14:14.943665','2026-09-18 20:14:14.943718',5,NULL,NULL,3),(263,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',24,'Venta de 2.00 ud · Baul para cenizas mediano','Existencia: 13.00','Existencia: 11.00','::1','2026-09-18 20:14:14.954743','2026-09-18 20:14:14.954794',5,NULL,NULL,3),(264,5,'Marielos Rodriguez','Confirmación','sales','Sale',8,'Venta VEN-0007 confirmada · inventario descargado · cuenta por cobrar por 117,520.00',NULL,NULL,'::1','2026-09-18 20:14:14.958125','2026-09-18 20:14:14.958167',5,NULL,NULL,3),(265,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0008 a Funeraria San Rafael por 644,100.00',NULL,NULL,'::1','2026-09-18 20:14:14.969394','2026-09-18 20:14:14.969444',5,NULL,NULL,3),(266,5,'Marielos Rodriguez','Cobro registrado','receivables','AccountReceivable',6,'Cobro de 458,780.00 sobre VEN-0004 (Funeraria Monte de los Olivos) · saldo 0.00',NULL,NULL,'::1','2026-09-18 20:14:15.046307','2026-09-18 20:14:15.046403',5,NULL,NULL,3),(267,5,'Marielos Rodriguez','Cobro registrado','receivables','AccountReceivable',9,'Cobro de 117,520.00 sobre VEN-0007 (Funeraria Monte de los Olivos) · saldo 0.00',NULL,NULL,'::1','2026-09-18 20:14:15.073898','2026-09-18 20:14:15.073939',5,NULL,NULL,3),(268,5,'Marielos Rodriguez','Cobro registrado','receivables','AccountReceivable',5,'Cobro de 402,280.00 sobre VEN-0003 (Servicios Funerarios El Descanso) · saldo 0.00',NULL,NULL,'::1','2026-09-18 20:14:15.090011','2026-09-18 20:14:15.090062',5,NULL,NULL,3),(269,5,'Marielos Rodriguez','Cobro registrado','receivables','AccountReceivable',3,'Cobro de 500,000.00 sobre VEN-0001 (Funeraria La Paz) · saldo 336,200.00',NULL,NULL,'::1','2026-09-18 20:14:15.103913','2026-09-18 20:14:15.103952',5,NULL,NULL,3),(270,5,'Marielos Rodriguez','Creación','repairs','RepairOrder',NULL,'Reparación REP-0001: Ataud clasico caoba - tapa danada de Funeraria La Paz',NULL,NULL,'::1','2026-09-18 20:14:15.185991','2026-09-18 20:14:15.186052',5,NULL,NULL,3),(271,5,'Marielos Rodriguez','En proceso','repairs','RepairOrder',2,'Reparación REP-0001 en proceso',NULL,NULL,'::1','2026-09-18 20:14:15.253374','2026-09-18 20:14:15.253444',5,NULL,NULL,3),(272,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',4,'Consumo en reparación de 2.00 ud · Tablero de laurel 2.40 x 0.30 m','Existencia: 149.00','Existencia: 147.00','::1','2026-09-18 20:14:15.273874','2026-09-18 20:14:15.273915',5,NULL,NULL,3),(273,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',14,'Consumo en reparación de 1.00 L · Barniz poliuretano','Existencia: 81.30','Existencia: 80.30','::1','2026-09-18 20:14:15.277122','2026-09-18 20:14:15.277149',5,NULL,NULL,3),(274,5,'Marielos Rodriguez','Terminada','repairs','RepairOrder',2,'Reparación REP-0001 terminada · material descargado por 31,500.00',NULL,NULL,'::1','2026-09-18 20:14:15.279016','2026-09-18 20:14:15.279055',5,NULL,NULL,3),(275,5,'Marielos Rodriguez','Entrega','repairs','RepairOrder',2,'Reparación REP-0001 entregada a Funeraria La Paz · cuenta por cobrar por 75,145.00',NULL,NULL,'::1','2026-09-18 20:14:15.294380','2026-09-18 20:14:15.294430',5,NULL,NULL,3),(276,5,'Marielos Rodriguez','Creación','repairs','RepairOrder',NULL,'Reparación REP-0002: Ataud economico blanco - herrajes sueltos de Funeraria San Rafael',NULL,NULL,'::1','2026-09-18 20:14:15.306782','2026-09-18 20:14:15.306821',5,NULL,NULL,3),(277,5,'Marielos Rodriguez','En proceso','repairs','RepairOrder',3,'Reparación REP-0002 en proceso',NULL,NULL,'::1','2026-09-18 20:14:15.315024','2026-09-18 20:14:15.315072',5,NULL,NULL,3),(278,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',10,'Consumo en reparación de 3.00 ud · Manija metalica cromada','Existencia: 326.00','Existencia: 323.00','::1','2026-09-18 20:14:15.323673','2026-09-18 20:14:15.323695',5,NULL,NULL,3),(279,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',13,'Consumo en reparación de 1.00 cja · Tornillo para madera 2 pulgadas (caja 100)','Existencia: 5.00','Existencia: 4.00','::1','2026-09-18 20:14:15.326148','2026-09-18 20:14:15.326161',5,NULL,NULL,3),(280,5,'Marielos Rodriguez','Terminada','repairs','RepairOrder',3,'Reparación REP-0002 terminada · material descargado por 18,500.00',NULL,NULL,'::1','2026-09-18 20:14:15.327442','2026-09-18 20:14:15.327468',5,NULL,NULL,3),(281,5,'Marielos Rodriguez','Entrega','repairs','RepairOrder',3,'Reparación REP-0002 entregada a Funeraria San Rafael · cuenta por cobrar por 41,245.00',NULL,NULL,'::1','2026-09-18 20:14:15.335429','2026-09-18 20:14:15.335449',5,NULL,NULL,3),(282,5,'Marielos Rodriguez','Creación','repairs','RepairOrder',NULL,'Reparación REP-0003: Baul para cenizas - bisagra quebrada de Servicios Funerarios El Descanso',NULL,NULL,'::1','2026-09-18 20:14:15.343472','2026-09-18 20:14:15.343493',5,NULL,NULL,3),(283,5,'Marielos Rodriguez','En proceso','repairs','RepairOrder',4,'Reparación REP-0003 en proceso',NULL,NULL,'::1','2026-09-18 20:14:15.350340','2026-09-18 20:14:15.350383',5,NULL,NULL,3),(284,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',11,'Consumo en reparación de 2.00 ud · Bisagra piano 30 cm','Existencia: 196.00','Existencia: 194.00','::1','2026-09-18 20:14:15.358752','2026-09-18 20:14:15.358772',5,NULL,NULL,3),(285,5,'Marielos Rodriguez','Terminada','repairs','RepairOrder',4,'Reparación REP-0003 terminada · material descargado por 5,000.00',NULL,NULL,'::1','2026-09-18 20:14:15.360715','2026-09-18 20:14:15.360800',5,NULL,NULL,3),(286,5,'Marielos Rodriguez','Creación','repairs','RepairOrder',NULL,'Reparación REP-0004: Ataud infantil - forro manchado de Funeraria del Recuerdo',NULL,NULL,'::1','2026-09-18 20:14:15.368668','2026-09-18 20:14:15.368687',5,NULL,NULL,3),(287,5,'Marielos Rodriguez','En proceso','repairs','RepairOrder',5,'Reparación REP-0004 en proceso',NULL,NULL,'::1','2026-09-18 20:14:15.376542','2026-09-18 20:14:15.376582',5,NULL,NULL,3),(288,5,'Marielos Rodriguez','Creación','repairs','RepairOrder',NULL,'Reparación REP-0005: Urna tallada - acabado opaco de Funeraria Monte de los Olivos',NULL,NULL,'::1','2026-09-18 20:14:15.384320','2026-09-18 20:14:15.384339',5,NULL,NULL,3),(289,5,'Marielos Rodriguez','Creación','repairs','RepairOrder',NULL,'Reparación REP-0006: Ataud premium cedro - ajuste de tapa de Funeraria La Paz',NULL,NULL,'::1','2026-09-18 20:14:15.392553','2026-09-18 20:14:15.392651',5,NULL,NULL,3),(290,5,'Marielos Rodriguez','En proceso','repairs','RepairOrder',7,'Reparación REP-0006 en proceso',NULL,NULL,'::1','2026-09-18 20:14:15.402802','2026-09-18 20:14:15.402888',5,NULL,NULL,3),(291,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Combustible y fletes',NULL,NULL,'::1','2026-09-18 20:14:15.451863','2026-09-18 20:14:15.451919',5,NULL,NULL,3),(292,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Publicidad',NULL,NULL,'::1','2026-09-18 20:14:15.465259','2026-09-18 20:14:15.465303',5,NULL,NULL,3),(293,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:16:08.395700','2026-09-18 20:16:08.412664',NULL,NULL,NULL,3),(294,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:16:40.385261','2026-09-18 20:16:40.385369',NULL,NULL,NULL,3),(295,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Salarios y cargas sociales',NULL,NULL,'::1','2026-09-18 20:16:40.423516','2026-09-18 20:16:40.423618',5,NULL,NULL,3),(296,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Alquiler',NULL,NULL,'::1','2026-09-18 20:16:40.440098','2026-09-18 20:16:40.440133',5,NULL,NULL,3),(297,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Servicios publicos',NULL,NULL,'::1','2026-09-18 20:16:40.448008','2026-09-18 20:16:40.448042',5,NULL,NULL,3),(298,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Mantenimiento y reparaciones',NULL,NULL,'::1','2026-09-18 20:16:40.454838','2026-09-18 20:16:40.454870',5,NULL,NULL,3),(299,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Impuestos y patentes',NULL,NULL,'::1','2026-09-18 20:16:40.462126','2026-09-18 20:16:40.462173',5,NULL,NULL,3),(300,5,'Marielos Rodriguez','Creación','expenses','FinanceCategory',NULL,'Categoría de gasto: Otros gastos',NULL,NULL,'::1','2026-09-18 20:16:40.469214','2026-09-18 20:16:40.469249',5,NULL,NULL,3),(301,5,'Marielos Rodriguez','Creación','income','FinanceCategory',NULL,'Categoría de ingreso: Otros ingresos',NULL,NULL,'::1','2026-09-18 20:16:40.476873','2026-09-18 20:16:40.476929',5,NULL,NULL,3),(302,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 1,250,000.00 · Salarios y cargas sociales · Planilla primera quincena',NULL,NULL,'::1','2026-09-18 20:16:40.534433','2026-09-18 20:16:40.534529',5,NULL,NULL,3),(303,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 1,250,000.00 · Salarios y cargas sociales · Planilla segunda quincena',NULL,NULL,'::1','2026-09-18 20:16:40.585443','2026-09-18 20:16:40.585475',5,NULL,NULL,3),(304,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 350,000.00 · Alquiler · Alquiler del taller',NULL,NULL,'::1','2026-09-18 20:16:40.594504','2026-09-18 20:16:40.594532',5,NULL,NULL,3),(305,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 128,000.00 · Servicios publicos · Electricidad del taller',NULL,NULL,'::1','2026-09-18 20:16:40.601897','2026-09-18 20:16:40.601935',5,NULL,NULL,3),(306,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 34,000.00 · Servicios publicos · Agua',NULL,NULL,'::1','2026-09-18 20:16:40.609064','2026-09-18 20:16:40.609088',5,NULL,NULL,3),(307,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 28,000.00 · Servicios publicos · Internet y telefono',NULL,NULL,'::1','2026-09-18 20:16:40.615502','2026-09-18 20:16:40.615533',5,NULL,NULL,3),(308,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 85,000.00 · Combustible y fletes · Combustible de entregas',NULL,NULL,'::1','2026-09-18 20:16:40.622543','2026-09-18 20:16:40.622591',5,NULL,NULL,3),(309,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 60,000.00 · Combustible y fletes · Flete a Ciudad Quesada',NULL,NULL,'::1','2026-09-18 20:16:40.629400','2026-09-18 20:16:40.629539',5,NULL,NULL,3),(310,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 145,000.00 · Mantenimiento y reparaciones · Mantenimiento de la sierra de banco',NULL,NULL,'::1','2026-09-18 20:16:40.642620','2026-09-18 20:16:40.642646',5,NULL,NULL,3),(311,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 92,000.00 · Impuestos y patentes · Patente municipal trimestral',NULL,NULL,'::1','2026-09-18 20:16:40.650028','2026-09-18 20:16:40.650054',5,NULL,NULL,3),(312,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 45,000.00 · Publicidad · Rotulacion del vehiculo de reparto',NULL,NULL,'::1','2026-09-18 20:16:40.656668','2026-09-18 20:16:40.656692',5,NULL,NULL,3),(313,5,'Marielos Rodriguez','Creación','expenses','FinanceEntry',NULL,'Gasto de 38,000.00 · Otros gastos · Consumibles de taller (lijas, brochas)',NULL,NULL,'::1','2026-09-18 20:16:40.663350','2026-09-18 20:16:40.663373',5,NULL,NULL,3),(314,5,'Marielos Rodriguez','Creación','income','FinanceEntry',NULL,'Ingreso de 65,000.00 · Otros ingresos · Venta de retazos de madera',NULL,NULL,'::1','2026-09-18 20:16:40.672556','2026-09-18 20:16:40.672590',5,NULL,NULL,3),(315,5,'Marielos Rodriguez','Creación','income','FinanceEntry',NULL,'Ingreso de 40,000.00 · Otros ingresos · Alquiler de espacio de bodega',NULL,NULL,'::1','2026-09-18 20:16:40.681434','2026-09-18 20:16:40.681468',5,NULL,NULL,3),(316,5,'Marielos Rodriguez','Creación','users','User',NULL,'Usuario consulta@nazareno.cr con rol Consulta',NULL,NULL,'::1','2026-09-18 20:16:40.799079','2026-09-18 20:16:40.799135',5,NULL,NULL,3),(317,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:17:06.708605','2026-09-18 20:17:06.708718',NULL,NULL,NULL,3),(318,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:17:14.989308','2026-09-18 20:17:14.989361',NULL,NULL,NULL,3),(319,1,'Desarrollador Gestora','Inicio de sesión','auth','User',1,'Desarrollador Gestora inició sesión',NULL,NULL,'::1','2026-09-18 20:17:25.032022','2026-09-18 20:17:25.032087',NULL,NULL,NULL,0),(320,2,'Administración Mi Empresa','Cierre de sesión','auth','User',2,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 20:19:44.028791','2026-09-18 20:19:44.047712',2,NULL,NULL,1),(321,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:19:57.243084','2026-09-18 20:19:57.243475',NULL,NULL,NULL,3),(322,5,'Marielos Rodriguez','Cierre de sesión','auth','User',5,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 20:20:00.672207','2026-09-18 20:20:00.672281',5,NULL,NULL,3),(323,6,'Esteban Villalobos','Inicio de sesión','auth','User',6,'Esteban Villalobos inició sesión',NULL,NULL,'::1','2026-09-18 20:20:06.872455','2026-09-18 20:20:06.872536',NULL,NULL,NULL,3),(324,6,'Esteban Villalobos','Cierre de sesión','auth','User',6,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 20:20:14.231909','2026-09-18 20:20:14.231974',6,NULL,NULL,3),(325,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:21:01.680531','2026-09-18 20:21:01.680602',NULL,NULL,NULL,3),(326,5,'Marielos Rodriguez','Inicio de sesión','auth','User',5,'Marielos Rodriguez inició sesión',NULL,NULL,'::1','2026-09-18 20:25:41.181678','2026-09-18 20:25:41.198718',NULL,NULL,NULL,3),(327,5,'Marielos Rodriguez','Actualización','users','User',5,'Usuario','Marielos Rodriguez | admin@nazareno.cr | Administrador','Zusana Artavia | admin@nazareno.cr | Administrador','::1','2026-09-18 20:25:41.594722','2026-09-18 20:25:41.595024',5,NULL,NULL,3),(328,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 20:25:56.384680','2026-09-18 20:25:56.384770',NULL,NULL,NULL,3),(329,5,'Marielos Rodriguez','Inactivación','suppliers','Supplier',6,'Proveedor PRV-0005 - Ferreteria El Carpintero',NULL,NULL,'::1','2026-09-18 20:27:43.704657','2026-09-18 20:27:43.738901',5,NULL,NULL,3),(330,5,'Marielos Rodriguez','Reactivación','suppliers','Supplier',6,'Proveedor PRV-0005 - Ferreteria El Carpintero',NULL,NULL,'::1','2026-09-18 20:27:48.249432','2026-09-18 20:27:48.250243',5,NULL,NULL,3),(331,5,'Marielos Rodriguez','Creación','sales','Sale',NULL,'Venta VEN-0009 a Funeraria del Recuerdo por 0.00',NULL,NULL,'::1','2026-09-18 20:37:03.792534','2026-09-18 20:37:03.817733',5,NULL,NULL,3),(332,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',13,'Compra de 10.00 cja · Tornillo para madera 2 pulgadas (caja 100)','Existencia: 4.00','Existencia: 14.00','::1','2026-09-18 20:37:36.710863','2026-09-18 20:37:36.711180',5,NULL,NULL,3),(333,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',17,'Compra de 12.00 L · Pegamento de contacto','Existencia: 20.60','Existencia: 32.60','::1','2026-09-18 20:37:36.765553','2026-09-18 20:37:36.765578',5,NULL,NULL,3),(334,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',7,'Compra COM-0006 confirmada · inventario actualizado · cuenta por pagar COM-0006 por 107,124.00',NULL,NULL,'::1','2026-09-18 20:37:36.790953','2026-09-18 20:37:36.791022',5,NULL,NULL,3),(335,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Venta de 1.00 m2 · Acolchado esponja 1 pulgada','Existencia: 133.00','Existencia: 132.00','::1','2026-09-18 20:37:47.281870','2026-09-18 20:37:47.281937',5,NULL,NULL,3),(336,5,'Marielos Rodriguez','Confirmación','sales','Sale',10,'Venta VEN-0009 confirmada · inventario descargado · cuenta por cobrar por 0.00',NULL,NULL,'::1','2026-09-18 20:37:47.320929','2026-09-18 20:37:47.321015',5,NULL,NULL,3),(337,5,'Marielos Rodriguez','Actualización','users','User',6,'Usuario','Esteban Villalobos | consulta@nazareno.cr | Consulta','Minor Moya | consulta@nazareno.cr | Consulta','::1','2026-09-18 20:41:34.503311','2026-09-18 20:41:34.503422',5,NULL,NULL,3),(338,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 20:45:22.499889','2026-09-18 20:45:22.500002',NULL,NULL,NULL,3),(339,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 20:45:30.636128','2026-09-18 20:45:30.636173',NULL,NULL,NULL,3),(340,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 20:46:22.193636','2026-09-18 20:46:22.208551',NULL,NULL,NULL,3),(342,5,'Zusana Artavia','Movimiento de inventario','inventory','Product',9,'Compra de 1.00 m2 · Acolchado esponja 1 pulgada','Existencia: 132.00','Existencia: 133.00','::1','2026-09-18 20:46:22.860721','2026-09-18 20:46:22.860799',5,NULL,NULL,3),(344,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 20:46:45.099540','2026-09-18 20:46:45.099631',NULL,NULL,NULL,3),(345,5,'Marielos Rodriguez','Creación','purchases','Purchase',NULL,'Compra COM-0007 a Ferreteria El Carpintero',NULL,NULL,'::1','2026-09-18 20:49:04.175098','2026-09-18 20:49:04.195182',5,NULL,NULL,3),(346,5,'Marielos Rodriguez','Movimiento de inventario','inventory','Product',9,'Compra de 1.00 m2 · Acolchado esponja 1 pulgada','Existencia: 132.00','Existencia: 133.00','::1','2026-09-18 20:49:06.998484','2026-09-18 20:49:06.998971',5,NULL,NULL,3),(347,5,'Marielos Rodriguez','Confirmación','purchases','Purchase',9,'Compra COM-0007 confirmada · inventario actualizado · cuenta por pagar COM-0007 por 3,503.00',NULL,NULL,'::1','2026-09-18 20:49:07.061723','2026-09-18 20:49:07.061820',5,NULL,NULL,3),(348,5,'Zusana Artavia','Cierre de sesión','auth','User',5,'Sesión cerrada',NULL,NULL,'::1','2026-09-18 21:30:37.645919','2026-09-18 21:30:37.687967',5,NULL,NULL,3),(349,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 21:30:42.020918','2026-09-18 21:30:42.021361',NULL,NULL,NULL,3),(350,5,'Zusana Artavia','Inicio de sesión','auth','User',5,'Zusana Artavia inició sesión',NULL,NULL,'::1','2026-09-18 21:42:31.926757','2026-09-18 21:42:31.949906',NULL,NULL,NULL,3),(351,5,'Zusana Artavia','Cobro registrado','receivables','AccountReceivable',3,'Cobro de 336,200.00 sobre VEN-0001 (Funeraria La Paz) · saldo 0.00',NULL,NULL,'::1','2026-09-18 21:42:32.369965','2026-09-18 21:42:32.370437',5,NULL,NULL,3),(352,5,'Zusana Artavia','Cobro registrado','receivables','AccountReceivable',4,'Cobro de 627,997.50 sobre VEN-0002 (Funeraria San Rafael) · saldo 0.00',NULL,NULL,'::1','2026-09-18 21:42:32.536752','2026-09-18 21:42:32.536779',5,NULL,NULL,3),(353,5,'Zusana Artavia','Cobro registrado','receivables','AccountReceivable',11,'Cobro de 41,245.00 sobre REP-0002 (Funeraria San Rafael) · saldo 0.00',NULL,NULL,'::1','2026-09-18 21:42:32.588361','2026-09-18 21:42:32.588404',5,NULL,NULL,3);
/*!40000 ALTER TABLE `auditlogs` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Producto terminado','Artículos listos para la venta.',1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(2,'Materia prima','Insumos que se transforman en producción.',1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(3,'Insumos y consumibles','Material de apoyo y consumo.',1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(4,'Servicios','Servicios facturables.',1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(5,'Producto terminado','Artículos listos para la venta.',1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(6,'Materia prima','Insumos que se transforman en producción.',1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(7,'Insumos y consumibles','Material de apoyo y consumo.',1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(8,'Servicios','Servicios facturables.',1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(9,'Producto terminado','Artículos listos para la venta.',1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(10,'Materia prima','Insumos que se transforman en producción.',1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(11,'Insumos y consumibles','Material de apoyo y consumo.',1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(12,'Servicios','Servicios facturables.',1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(13,'Ataudes','Cajas funerarias terminadas, listas para entregar.',1,'2026-09-18 20:14:12.793097',5,NULL,NULL,3),(14,'Urnas y baules','Urnas y baules para cenizas.',1,'2026-09-18 20:14:12.800570',5,NULL,NULL,3),(15,'Madera y tableros','Materia prima principal.',1,'2026-09-18 20:14:12.807320',5,NULL,NULL,3),(16,'Telas y acolchados','Forros interiores.',1,'2026-09-18 20:14:12.814574',5,NULL,NULL,3),(17,'Herrajes y accesorios','Manijas, bisagras, crucifijos, tornilleria.',1,'2026-09-18 20:14:12.821233',5,NULL,NULL,3),(18,'Pinturas y acabados','Barniz, sellador, tintes.',1,'2026-09-18 20:14:12.828129',5,NULL,NULL,3);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Empresa Demo','3-101-999999','2222-3333','San Jose, Costa Rica','CRC',13.00,'2026-09-07 14:05:29.535047',NULL,'2026-09-18 19:51:12.866224',2,'empresa@gestora.local',NULL,0),(2,'Carpinteria Rodriguez','3-101-123456',NULL,NULL,'CRC',13.00,'2026-09-07 14:09:58.899075',1,'2026-09-07 14:10:15.442021',1,'admin@carpinteriarodriguez.com',NULL,0),(3,'Funeraria Nazareno','3-101-482910','2447-8890','Naranjo, Alajuela, 300 m norte de la iglesia','CRC',13.00,'2026-09-18 20:14:12.501119',1,NULL,NULL,'admin@nazareno.cr','Fabricacion y venta de ataudes, urnas y baules para cenizas. Vende a funerarias de la zona.',0);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CLI-0001','Cliente Prueba Ventas',NULL,NULL,NULL,NULL,NULL,NULL,1,30,0.00,NULL,1,'2026-09-18 19:49:01.441259',2,NULL,NULL,1),(2,'CLI-0001','Funeraria La Paz','La Paz','3-101-201455','2445-3300','compras@funerarialapaz.cr','Grecia, Alajuela','Sra. Dinia Mora',1,30,2500000.00,'Cliente principal. Pedido fijo mensual de ataudes economicos.',1,'2026-09-18 20:14:12.976570',5,NULL,NULL,3),(3,'CLI-0002','Funeraria San Rafael',NULL,'3-101-337109','2494-1187','administracion@fsanrafael.cr','Sarchi, Alajuela','Carlos Jimenez',1,30,1500000.00,'Pide modelos con acabado en cedro.',1,'2026-09-18 20:14:12.996041',5,NULL,NULL,3),(4,'CLI-0003','Servicios Funerarios El Descanso','El Descanso','3-102-556214','2460-8890','eldescanso@outlook.com','Ciudad Quesada, San Carlos','Kattia Ugalde',1,15,800000.00,'Compra urnas y baules. Retira en planta.',1,'2026-09-18 20:14:13.003783',5,NULL,NULL,3),(5,'CLI-0004','Funeraria Monte de los Olivos',NULL,'3-101-774120','2237-4412','pedidos@montedelosolivos.cr','Heredia centro','Randall Picado',0,0,0.00,'Siempre paga de contado al retirar.',1,'2026-09-18 20:14:13.011136',5,NULL,NULL,3),(6,'CLI-0005','Funeraria del Recuerdo',NULL,'3-101-889033','2471-2204','funerariadelrecuerdo@gmail.com','Zarcero, Alajuela','Sra. Flor Arias',1,30,600000.00,'Cliente nuevo. Revisar puntualidad de pago.',1,'2026-09-18 20:14:13.018515',5,NULL,NULL,3);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financecategories`
--

LOCK TABLES `financecategories` WRITE;
/*!40000 ALTER TABLE `financecategories` DISABLE KEYS */;
INSERT INTO `financecategories` VALUES (1,'Cobros a clientes',0,'Generada por los cobros registrados en cuentas por cobrar.',1,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(2,'Otros ingresos',0,'Ingresos que no provienen de una venta.',0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(3,'Pagos a proveedores',1,'Generada por los pagos registrados en cuentas por pagar.',1,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(4,'Salarios y cargas sociales',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(5,'Alquiler',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(6,'Servicios públicos',1,'Luz, agua, internet, teléfono.',0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(7,'Transporte y combustible',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(8,'Mantenimiento y reparaciones',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(9,'Impuestos y patentes',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(10,'Otros gastos',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,2),(11,'Cobros a clientes',0,'Generada por los cobros registrados en cuentas por cobrar.',1,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(12,'Otros ingresos',0,'Ingresos que no provienen de una venta.',0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(13,'Pagos a proveedores',1,'Generada por los pagos registrados en cuentas por pagar.',1,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(14,'Salarios y cargas sociales',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(15,'Alquiler',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(16,'Servicios públicos',1,'Luz, agua, internet, teléfono.',0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(17,'Transporte y combustible',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(18,'Mantenimiento y reparaciones',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(19,'Impuestos y patentes',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(20,'Otros gastos',1,NULL,0,1,'2026-09-18 19:48:30.746619',NULL,NULL,NULL,1),(21,'Pagos a proveedores',1,'Generada por los pagos registrados en cuentas por pagar.',1,1,'2026-09-18 20:14:13.977870',5,NULL,NULL,3),(22,'Cobros a clientes',0,'Generada por los cobros registrados en cuentas por cobrar.',1,1,'2026-09-18 20:14:15.060117',5,NULL,NULL,3),(23,'Combustible y fletes',1,NULL,0,1,'2026-09-18 20:14:15.451919',5,NULL,NULL,3),(24,'Publicidad',1,NULL,0,1,'2026-09-18 20:14:15.465303',5,NULL,NULL,3),(25,'Salarios y cargas sociales',1,NULL,0,1,'2026-09-18 20:16:40.423618',5,NULL,NULL,3),(26,'Alquiler',1,NULL,0,1,'2026-09-18 20:16:40.440133',5,NULL,NULL,3),(27,'Servicios publicos',1,'Luz, agua, internet, telefono.',0,1,'2026-09-18 20:16:40.448042',5,NULL,NULL,3),(28,'Mantenimiento y reparaciones',1,NULL,0,1,'2026-09-18 20:16:40.454870',5,NULL,NULL,3),(29,'Impuestos y patentes',1,NULL,0,1,'2026-09-18 20:16:40.462173',5,NULL,NULL,3),(30,'Otros gastos',1,NULL,0,1,'2026-09-18 20:16:40.469249',5,NULL,NULL,3),(31,'Otros ingresos',0,'Ingresos que no provienen de una venta.',0,1,'2026-09-18 20:16:40.476929',5,NULL,NULL,3);
/*!40000 ALTER TABLE `financecategories` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financeentries`
--

LOCK TABLES `financeentries` WRITE;
/*!40000 ALTER TABLE `financeentries` DISABLE KEYS */;
INSERT INTO `financeentries` VALUES (1,0,11,'2026-09-18 19:49:03.642040',10000.00,'Cobro VEN-0001 · Cliente Prueba Ventas','Transferencia','TEST-001',NULL,1,1,2,'2026-09-18 19:49:03.682605',2,NULL,NULL,1),(2,1,13,'2026-09-18 19:50:49.567389',15000.00,'Pago COM-0001 · Proveedor Prueba','Transferencia','PAGO-1',NULL,2,1,2,'2026-09-18 19:50:49.585208',2,NULL,NULL,1),(3,1,15,'2026-09-18 19:50:49.991246',250000.00,'Alquiler del local','Transferencia',NULL,NULL,0,NULL,2,'2026-09-18 19:50:49.991441',2,NULL,NULL,1),(4,1,21,'2026-08-29 20:14:13.000000',392110.00,'Pago FE-0004471 · Textiles Monserrat','Transferencia','SINPE-771204',NULL,2,2,5,'2026-09-18 20:14:14.012991',5,NULL,NULL,3),(5,1,21,'2026-09-08 20:14:14.000000',140120.00,'Pago FE-0033187 · Pinturas y Solventes del Norte','Transferencia','SINPE-779881',NULL,2,3,5,'2026-09-18 20:14:14.039587',5,NULL,NULL,3),(6,1,21,'2026-09-15 20:14:14.000000',793260.00,'Pago FE-00012455 · Maderas del Valle S.A.','Efectivo',NULL,NULL,2,4,5,'2026-09-18 20:14:14.052401',5,NULL,NULL,3),(7,0,22,'2026-09-07 00:00:00.000000',458780.00,'Cobro VEN-0004 · Funeraria Monte de los Olivos','Sinpe Movil','SINPE-4412',NULL,1,2,5,'2026-09-18 20:14:15.062526',5,NULL,NULL,3),(8,0,22,'2026-09-17 00:00:00.000000',117520.00,'Cobro VEN-0007 · Funeraria Monte de los Olivos','Sinpe Movil','SINPE-4412',NULL,1,3,5,'2026-09-18 20:14:15.076518',5,NULL,NULL,3),(9,0,22,'2026-09-16 20:14:15.000000',402280.00,'Cobro VEN-0003 · Servicios Funerarios El Descanso','Transferencia','TRF-88455',NULL,1,4,5,'2026-09-18 20:14:15.092900',5,NULL,NULL,3),(10,0,22,'2026-09-09 20:14:15.000000',500000.00,'Cobro VEN-0001 · Funeraria La Paz','Transferencia','TRF-88120',NULL,1,5,5,'2026-09-18 20:14:15.106718',5,NULL,NULL,3),(11,1,25,'2026-08-19 20:16:40.000000',1250000.00,'Planilla primera quincena','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.534529',5,NULL,NULL,3),(12,1,25,'2026-09-03 20:16:40.000000',1250000.00,'Planilla segunda quincena','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.585475',5,NULL,NULL,3),(13,1,26,'2026-08-21 20:16:40.000000',350000.00,'Alquiler del taller','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.594532',5,NULL,NULL,3),(14,1,27,'2026-08-23 20:16:40.000000',128000.00,'Electricidad del taller','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.601935',5,NULL,NULL,3),(15,1,27,'2026-08-23 20:16:40.000000',34000.00,'Agua','Efectivo',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.609088',5,NULL,NULL,3),(16,1,27,'2026-08-24 20:16:40.000000',28000.00,'Internet y telefono','Tarjeta',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.615533',5,NULL,NULL,3),(17,1,23,'2026-08-31 20:16:40.000000',85000.00,'Combustible de entregas','Efectivo',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.622591',5,NULL,NULL,3),(18,1,23,'2026-09-06 20:16:40.000000',60000.00,'Flete a Ciudad Quesada','Sinpe Movil',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.629539',5,NULL,NULL,3),(19,1,28,'2026-08-29 20:16:40.000000',145000.00,'Mantenimiento de la sierra de banco','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.642646',5,NULL,NULL,3),(20,1,29,'2026-08-16 20:16:40.000000',92000.00,'Patente municipal trimestral','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.650054',5,NULL,NULL,3),(21,1,24,'2026-09-09 20:16:40.000000',45000.00,'Rotulacion del vehiculo de reparto','Efectivo',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.656692',5,NULL,NULL,3),(22,1,30,'2026-09-11 20:16:40.000000',38000.00,'Consumibles de taller (lijas, brochas)','Efectivo',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.663373',5,NULL,NULL,3),(23,0,31,'2026-09-01 20:16:40.000000',65000.00,'Venta de retazos de madera','Efectivo',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.672590',5,NULL,NULL,3),(24,0,31,'2026-09-08 20:16:40.000000',40000.00,'Alquiler de espacio de bodega','Transferencia',NULL,NULL,0,NULL,5,'2026-09-18 20:16:40.681468',5,NULL,NULL,3),(25,0,22,'2026-09-16 00:00:00.000000',336200.00,'Cobro VEN-0001 · Funeraria La Paz','Transferencia','BAC 7741-0093',NULL,1,6,5,'2026-09-18 21:42:32.492015',5,NULL,NULL,3),(26,0,22,'2026-09-17 00:00:00.000000',627997.50,'Cobro VEN-0002 · Funeraria San Rafael','Transferencia','BN 55120-8',NULL,1,7,5,'2026-09-18 21:42:32.541406',5,NULL,NULL,3),(27,0,22,'2026-09-18 00:00:00.000000',41245.00,'Cobro REP-0002 · Funeraria San Rafael','Efectivo',NULL,NULL,1,8,5,'2026-09-18 21:42:32.611228',5,NULL,NULL,3);
/*!40000 ALTER TABLE `financeentries` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventorymovements`
--

LOCK TABLES `inventorymovements` WRITE;
/*!40000 ALTER TABLE `inventorymovements` DISABLE KEYS */;
INSERT INTO `inventorymovements` VALUES (1,1,9,1,50.0000,50.0000,1000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 19:49:01.632042',2,'2026-09-18 19:49:01.651056',2,NULL,NULL,1),(2,1,2,-1,10.0000,40.0000,1000.00,'Venta VEN-0001','Sale',1,'2026-09-18 19:49:02.699501',2,'2026-09-18 19:49:02.699806',2,NULL,NULL,1),(3,2,9,1,200.0000,200.0000,500.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 19:49:20.384271',2,'2026-09-18 19:49:20.384504',2,NULL,NULL,1),(4,2,3,-1,42.0000,158.0000,500.00,'Orden de producción PRO-0001','ProductionOrder',1,'2026-09-18 19:50:00.069513',2,'2026-09-18 19:50:00.069704',2,NULL,NULL,1),(5,3,4,1,5.0000,5.0000,6200.00,'Orden de producción PRO-0001','ProductionOrder',1,'2026-09-18 19:50:00.074078',2,'2026-09-18 19:50:00.074258',2,NULL,NULL,1),(6,2,8,-1,4.0000,154.0000,500.00,'Reparación REP-0001','RepairOrder',1,'2026-09-18 19:50:22.666345',2,'2026-09-18 19:50:22.666491',2,NULL,NULL,1),(7,2,1,1,50.0000,204.0000,520.00,'Compra COM-0001','Purchase',1,'2026-09-18 19:50:49.256577',2,'2026-09-18 19:50:49.256773',2,NULL,NULL,1),(8,4,9,1,180.0000,180.0000,8500.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.076434',5,'2026-09-18 20:14:13.097135',5,NULL,NULL,3),(9,5,9,1,60.0000,60.0000,14200.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.134074',5,'2026-09-18 20:14:13.134220',5,NULL,NULL,3),(10,6,9,1,45.0000,45.0000,12800.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.146234',5,'2026-09-18 20:14:13.146374',5,NULL,NULL,3),(11,7,9,1,220.0000,220.0000,2400.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.158726',5,'2026-09-18 20:14:13.158915',5,NULL,NULL,3),(12,8,9,1,95.0000,95.0000,2900.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.169031',5,'2026-09-18 20:14:13.169179',5,NULL,NULL,3),(13,9,9,1,140.0000,140.0000,3100.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.181289',5,'2026-09-18 20:14:13.181446',5,NULL,NULL,3),(14,10,9,1,320.0000,320.0000,3200.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.193325',5,'2026-09-18 20:14:13.193481',5,NULL,NULL,3),(15,11,9,1,180.0000,180.0000,1450.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.206118',5,'2026-09-18 20:14:13.206344',5,NULL,NULL,3),(16,12,9,1,85.0000,85.0000,4800.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.216627',5,'2026-09-18 20:14:13.216729',5,NULL,NULL,3),(17,13,9,1,24.0000,24.0000,3600.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.228611',5,'2026-09-18 20:14:13.228714',5,NULL,NULL,3),(18,14,9,1,68.0000,68.0000,6800.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.238091',5,'2026-09-18 20:14:13.238203',5,NULL,NULL,3),(19,15,9,1,42.0000,42.0000,5200.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.251730',5,'2026-09-18 20:14:13.251889',5,NULL,NULL,3),(20,16,9,1,26.0000,26.0000,7400.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.262744',5,'2026-09-18 20:14:13.262896',5,NULL,NULL,3),(21,17,9,1,31.0000,31.0000,4900.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.275411',5,'2026-09-18 20:14:13.275565',5,NULL,NULL,3),(22,18,9,1,12.0000,12.0000,86000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.287025',5,'2026-09-18 20:14:13.287268',5,NULL,NULL,3),(23,19,9,1,7.0000,7.0000,118000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.298549',5,'2026-09-18 20:14:13.298711',5,NULL,NULL,3),(24,20,9,1,3.0000,3.0000,168000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.309511',5,'2026-09-18 20:14:13.309637',5,NULL,NULL,3),(25,21,9,1,4.0000,4.0000,52000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.322095',5,'2026-09-18 20:14:13.322243',5,NULL,NULL,3),(26,22,9,1,18.0000,18.0000,21000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.333378',5,'2026-09-18 20:14:13.333474',5,NULL,NULL,3),(27,23,9,1,22.0000,22.0000,14500.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.343107',5,'2026-09-18 20:14:13.343197',5,NULL,NULL,3),(28,24,9,1,9.0000,9.0000,27000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.353417',5,'2026-09-18 20:14:13.353500',5,NULL,NULL,3),(29,25,9,1,6.0000,6.0000,34000.00,'Carga inicial al crear el producto',NULL,NULL,'2026-09-18 20:14:13.363661',5,'2026-09-18 20:14:13.363752',5,NULL,NULL,3),(30,4,1,1,60.0000,240.0000,8500.00,'Compra COM-0001','Purchase',2,'2026-09-18 20:14:13.690881',5,'2026-09-18 20:14:13.691181',5,NULL,NULL,3),(31,6,1,1,15.0000,60.0000,12800.00,'Compra COM-0001','Purchase',2,'2026-09-18 20:14:13.695972',5,'2026-09-18 20:14:13.696142',5,NULL,NULL,3),(32,7,1,1,80.0000,300.0000,2400.00,'Compra COM-0002','Purchase',3,'2026-09-18 20:14:13.762412',5,'2026-09-18 20:14:13.762691',5,NULL,NULL,3),(33,9,1,1,50.0000,190.0000,3100.00,'Compra COM-0002','Purchase',3,'2026-09-18 20:14:13.765634',5,'2026-09-18 20:14:13.765762',5,NULL,NULL,3),(34,10,1,1,120.0000,440.0000,3200.00,'Compra COM-0003','Purchase',4,'2026-09-18 20:14:13.788861',5,'2026-09-18 20:14:13.789130',5,NULL,NULL,3),(35,11,1,1,60.0000,240.0000,1450.00,'Compra COM-0003','Purchase',4,'2026-09-18 20:14:13.792299',5,'2026-09-18 20:14:13.792430',5,NULL,NULL,3),(36,12,1,1,30.0000,115.0000,4800.00,'Compra COM-0003','Purchase',4,'2026-09-18 20:14:13.794763',5,'2026-09-18 20:14:13.794845',5,NULL,NULL,3),(37,14,1,1,25.0000,93.0000,6800.00,'Compra COM-0004','Purchase',5,'2026-09-18 20:14:13.818301',5,'2026-09-18 20:14:13.818506',5,NULL,NULL,3),(38,15,1,1,15.0000,57.0000,5200.00,'Compra COM-0004','Purchase',5,'2026-09-18 20:14:13.821075',5,'2026-09-18 20:14:13.821211',5,NULL,NULL,3),(39,4,1,1,40.0000,280.0000,8800.00,'Compra COM-0005','Purchase',6,'2026-09-18 20:14:13.846958',5,'2026-09-18 20:14:13.847150',5,NULL,NULL,3),(40,5,1,1,20.0000,80.0000,14200.00,'Compra COM-0005','Purchase',6,'2026-09-18 20:14:13.849787',5,'2026-09-18 20:14:13.849943',5,NULL,NULL,3),(41,4,3,-1,48.0000,232.0000,8800.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.242513',5,'2026-09-18 20:14:14.242662',5,NULL,NULL,3),(42,6,3,-1,8.0000,52.0000,12800.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.248252',5,'2026-09-18 20:14:14.248471',5,NULL,NULL,3),(43,7,3,-1,40.0000,260.0000,2400.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.253512',5,'2026-09-18 20:14:14.253750',5,NULL,NULL,3),(44,9,3,-1,24.0000,166.0000,3100.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.257094',5,'2026-09-18 20:14:14.257308',5,NULL,NULL,3),(45,10,3,-1,48.0000,392.0000,3200.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.260940',5,'2026-09-18 20:14:14.261085',5,NULL,NULL,3),(46,11,3,-1,16.0000,224.0000,1450.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.264080',5,'2026-09-18 20:14:14.264211',5,NULL,NULL,3),(47,13,3,-1,8.0000,16.0000,3600.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.267253',5,'2026-09-18 20:14:14.267389',5,NULL,NULL,3),(48,15,3,-1,8.0000,49.0000,5200.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.270643',5,'2026-09-18 20:14:14.270788',5,NULL,NULL,3),(49,17,3,-1,4.0000,27.0000,4900.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.274032',5,'2026-09-18 20:14:14.274167',5,NULL,NULL,3),(50,18,4,1,8.0000,20.0000,142250.00,'Orden de producción PRO-0001','ProductionOrder',2,'2026-09-18 20:14:14.277309',5,'2026-09-18 20:14:14.277420',5,NULL,NULL,3),(51,4,3,-1,35.0000,197.0000,8800.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.331078',5,'2026-09-18 20:14:14.331380',5,NULL,NULL,3),(52,6,3,-1,5.0000,47.0000,12800.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.337530',5,'2026-09-18 20:14:14.337672',5,NULL,NULL,3),(53,7,3,-1,25.0000,235.0000,2400.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.341510',5,'2026-09-18 20:14:14.341775',5,NULL,NULL,3),(54,9,3,-1,15.0000,151.0000,3100.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.346904',5,'2026-09-18 20:14:14.347186',5,NULL,NULL,3),(55,10,3,-1,30.0000,362.0000,3200.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.351106',5,'2026-09-18 20:14:14.351201',5,NULL,NULL,3),(56,11,3,-1,10.0000,214.0000,1450.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.353340',5,'2026-09-18 20:14:14.353413',5,NULL,NULL,3),(57,12,3,-1,5.0000,110.0000,4800.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.355347',5,'2026-09-18 20:14:14.355447',5,NULL,NULL,3),(58,13,3,-1,5.0000,11.0000,3600.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.359397',5,'2026-09-18 20:14:14.359462',5,NULL,NULL,3),(59,14,3,-1,7.5000,85.5000,6800.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.361878',5,'2026-09-18 20:14:14.361943',5,NULL,NULL,3),(60,16,3,-1,4.0000,22.0000,7400.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.363980',5,'2026-09-18 20:14:14.364040',5,NULL,NULL,3),(61,17,3,-1,2.5000,24.5000,4900.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.366542',5,'2026-09-18 20:14:14.366620',5,NULL,NULL,3),(62,19,4,1,5.0000,12.0000,172770.00,'Orden de producción PRO-0002','ProductionOrder',3,'2026-09-18 20:14:14.369045',5,'2026-09-18 20:14:14.369135',5,NULL,NULL,3),(63,4,3,-1,6.0000,191.0000,8800.00,'Orden de producción PRO-0003','ProductionOrder',4,'2026-09-18 20:14:14.416398',5,'2026-09-18 20:14:14.416632',5,NULL,NULL,3),(64,14,3,-1,2.4000,83.1000,6800.00,'Orden de producción PRO-0003','ProductionOrder',4,'2026-09-18 20:14:14.421138',5,'2026-09-18 20:14:14.421269',5,NULL,NULL,3),(65,16,3,-1,1.2000,20.8000,7400.00,'Orden de producción PRO-0003','ProductionOrder',4,'2026-09-18 20:14:14.424438',5,'2026-09-18 20:14:14.424645',5,NULL,NULL,3),(66,17,3,-1,0.9000,23.6000,4900.00,'Orden de producción PRO-0003','ProductionOrder',4,'2026-09-18 20:14:14.429129',5,'2026-09-18 20:14:14.429317',5,NULL,NULL,3),(67,22,4,1,12.0000,30.0000,11367.50,'Orden de producción PRO-0003','ProductionOrder',4,'2026-09-18 20:14:14.432378',5,'2026-09-18 20:14:14.432591',5,NULL,NULL,3),(68,4,3,-1,6.0000,185.0000,8800.00,'Orden de producción PRO-0004','ProductionOrder',5,'2026-09-18 20:14:14.481827',5,'2026-09-18 20:14:14.482047',5,NULL,NULL,3),(69,7,3,-1,4.5000,230.5000,2400.00,'Orden de producción PRO-0004','ProductionOrder',5,'2026-09-18 20:14:14.485548',5,'2026-09-18 20:14:14.485751',5,NULL,NULL,3),(70,11,3,-1,6.0000,208.0000,1450.00,'Orden de producción PRO-0004','ProductionOrder',5,'2026-09-18 20:14:14.489273',5,'2026-09-18 20:14:14.489469',5,NULL,NULL,3),(71,14,3,-1,1.8000,81.3000,6800.00,'Orden de producción PRO-0004','ProductionOrder',5,'2026-09-18 20:14:14.492710',5,'2026-09-18 20:14:14.492875',5,NULL,NULL,3),(72,24,4,1,6.0000,15.0000,21090.00,'Orden de producción PRO-0004','ProductionOrder',5,'2026-09-18 20:14:14.495914',5,'2026-09-18 20:14:14.496079',5,NULL,NULL,3),(73,4,3,-1,36.0000,149.0000,8800.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.550561',5,'2026-09-18 20:14:14.550805',5,NULL,NULL,3),(74,6,3,-1,6.0000,41.0000,12800.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.554595',5,'2026-09-18 20:14:14.554751',5,NULL,NULL,3),(75,7,3,-1,30.0000,200.5000,2400.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.557683',5,'2026-09-18 20:14:14.557823',5,NULL,NULL,3),(76,9,3,-1,18.0000,133.0000,3100.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.560984',5,'2026-09-18 20:14:14.561089',5,NULL,NULL,3),(77,10,3,-1,36.0000,326.0000,3200.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.563691',5,'2026-09-18 20:14:14.563838',5,NULL,NULL,3),(78,11,3,-1,12.0000,196.0000,1450.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.567074',5,'2026-09-18 20:14:14.567225',5,NULL,NULL,3),(79,13,3,-1,6.0000,5.0000,3600.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.570907',5,'2026-09-18 20:14:14.571054',5,NULL,NULL,3),(80,15,3,-1,6.0000,43.0000,5200.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.574210',5,'2026-09-18 20:14:14.574352',5,NULL,NULL,3),(81,17,3,-1,3.0000,20.6000,4900.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.577503',5,'2026-09-18 20:14:14.577658',5,NULL,NULL,3),(82,18,4,1,6.0000,26.0000,142250.00,'Orden de producción PRO-0005','ProductionOrder',6,'2026-09-18 20:14:14.580813',5,'2026-09-18 20:14:14.580960',5,NULL,NULL,3),(83,18,2,-1,5.0000,21.0000,142250.00,'Venta VEN-0001','Sale',2,'2026-09-18 20:14:14.750699',5,'2026-09-18 20:14:14.750920',5,NULL,NULL,3),(84,19,2,-1,3.0000,9.0000,172770.00,'Venta VEN-0002','Sale',3,'2026-09-18 20:14:14.826444',5,'2026-09-18 20:14:14.826633',5,NULL,NULL,3),(85,22,2,-1,6.0000,24.0000,11367.50,'Venta VEN-0003','Sale',4,'2026-09-18 20:14:14.848978',5,'2026-09-18 20:14:14.849135',5,NULL,NULL,3),(86,24,2,-1,2.0000,13.0000,21090.00,'Venta VEN-0003','Sale',4,'2026-09-18 20:14:14.851860',5,'2026-09-18 20:14:14.852002',5,NULL,NULL,3),(87,18,2,-1,2.0000,19.0000,142250.00,'Venta VEN-0004','Sale',5,'2026-09-18 20:14:14.872599',5,'2026-09-18 20:14:14.872793',5,NULL,NULL,3),(88,23,2,-1,4.0000,18.0000,14500.00,'Venta VEN-0004','Sale',5,'2026-09-18 20:14:14.875693',5,'2026-09-18 20:14:14.875807',5,NULL,NULL,3),(89,19,2,-1,2.0000,7.0000,172770.00,'Venta VEN-0005','Sale',6,'2026-09-18 20:14:14.897127',5,'2026-09-18 20:14:14.897375',5,NULL,NULL,3),(90,21,2,-1,1.0000,3.0000,52000.00,'Venta VEN-0005','Sale',6,'2026-09-18 20:14:14.901233',5,'2026-09-18 20:14:14.901452',5,NULL,NULL,3),(91,23,2,-1,5.0000,13.0000,14500.00,'Venta VEN-0006','Sale',7,'2026-09-18 20:14:14.925996',5,'2026-09-18 20:14:14.926202',5,NULL,NULL,3),(92,25,2,-1,1.0000,5.0000,34000.00,'Venta VEN-0006','Sale',7,'2026-09-18 20:14:14.929789',5,'2026-09-18 20:14:14.929963',5,NULL,NULL,3),(93,24,2,-1,2.0000,11.0000,21090.00,'Venta VEN-0007','Sale',8,'2026-09-18 20:14:14.954564',5,'2026-09-18 20:14:14.954794',5,NULL,NULL,3),(94,4,8,-1,2.0000,147.0000,8800.00,'Reparación REP-0001','RepairOrder',2,'2026-09-18 20:14:15.273732',5,'2026-09-18 20:14:15.273915',5,NULL,NULL,3),(95,14,8,-1,1.0000,80.3000,6800.00,'Reparación REP-0001','RepairOrder',2,'2026-09-18 20:14:15.277038',5,'2026-09-18 20:14:15.277149',5,NULL,NULL,3),(96,10,8,-1,3.0000,323.0000,3200.00,'Reparación REP-0002','RepairOrder',3,'2026-09-18 20:14:15.323571',5,'2026-09-18 20:14:15.323695',5,NULL,NULL,3),(97,13,8,-1,1.0000,4.0000,3600.00,'Reparación REP-0002','RepairOrder',3,'2026-09-18 20:14:15.326092',5,'2026-09-18 20:14:15.326161',5,NULL,NULL,3),(98,11,8,-1,2.0000,194.0000,1450.00,'Reparación REP-0003','RepairOrder',4,'2026-09-18 20:14:15.358620',5,'2026-09-18 20:14:15.358772',5,NULL,NULL,3),(99,13,1,1,10.0000,14.0000,3600.00,'Compra COM-0006','Purchase',7,'2026-09-18 20:37:36.684610',5,'2026-09-18 20:37:36.711180',5,NULL,NULL,3),(100,17,1,1,12.0000,32.6000,4900.00,'Compra COM-0006','Purchase',7,'2026-09-18 20:37:36.765436',5,'2026-09-18 20:37:36.765578',5,NULL,NULL,3),(101,9,2,-1,1.0000,132.0000,3100.00,'Venta VEN-0009','Sale',10,'2026-09-18 20:37:47.281670',5,'2026-09-18 20:37:47.281937',5,NULL,NULL,3),(103,9,1,1,1.0000,133.0000,3100.00,'Compra COM-0007','Purchase',9,'2026-09-18 20:49:06.971762',5,'2026-09-18 20:49:06.998971',5,NULL,NULL,3);
/*!40000 ALTER TABLE `inventorymovements` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'2026-09-18 19:50:49.567389',15000.00,'Transferencia','PAGO-1',NULL,2,'2026-09-18 19:50:49.577584',2,NULL,NULL),(2,3,'2026-08-29 20:14:13.000000',392110.00,'Transferencia','SINPE-771204',NULL,5,'2026-09-18 20:14:13.937701',5,NULL,NULL),(3,5,'2026-09-08 20:14:14.000000',140120.00,'Transferencia','SINPE-779881','Abono parcial acordado con el proveedor.',5,'2026-09-18 20:14:14.037064',5,NULL,NULL),(4,2,'2026-09-15 20:14:14.000000',793260.00,'Efectivo',NULL,NULL,5,'2026-09-18 20:14:14.048890',5,NULL,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (1,'Básico','Catálogo, inventario y compras para una empresa pequeña.',15000.00,'CRC',1,2,1,1,'2026-09-07 14:05:29.422694',NULL,'2026-09-07 14:49:19.679439',1),(2,'Profesional','Todo el sistema, incluidas ventas, producción y reportes.',20000.00,'CRC',1,5,1,2,'2026-09-07 14:05:29.422694',NULL,'2026-09-07 14:49:25.005742',1),(3,'Profesional anual','Plan profesional con dos meses de descuento al pagar por año.',50000.00,'CRC',12,5,1,3,'2026-09-07 14:05:29.422694',NULL,'2026-09-07 14:49:30.747462',1);
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productionmaterials`
--

LOCK TABLES `productionmaterials` WRITE;
/*!40000 ALTER TABLE `productionmaterials` DISABLE KEYS */;
INSERT INTO `productionmaterials` VALUES (1,1,2,40.0000,42.0000,500.00,'2026-09-18 19:49:59.157362',2,'2026-09-18 19:50:00.069704',2),(2,2,4,48.0000,48.0000,8800.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.242662',5),(3,2,6,8.0000,8.0000,12800.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.248471',5),(4,2,7,40.0000,40.0000,2400.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.253750',5),(5,2,9,24.0000,24.0000,3100.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.257308',5),(6,2,10,48.0000,48.0000,3200.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.261085',5),(7,2,11,16.0000,16.0000,1450.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.264211',5),(8,2,13,8.0000,8.0000,3600.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.267389',5),(9,2,15,8.0000,8.0000,5200.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.270788',5),(10,2,17,4.0000,4.0000,4900.00,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.274167',5),(11,3,4,35.0000,35.0000,8800.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.331380',5),(12,3,6,5.0000,5.0000,12800.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.337672',5),(13,3,7,25.0000,25.0000,2400.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.341775',5),(14,3,9,15.0000,15.0000,3100.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.347186',5),(15,3,10,30.0000,30.0000,3200.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.351201',5),(16,3,11,10.0000,10.0000,1450.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.353413',5),(17,3,12,5.0000,5.0000,4800.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.355447',5),(18,3,13,5.0000,5.0000,3600.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.359462',5),(19,3,14,7.5000,7.5000,6800.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.361943',5),(20,3,16,4.0000,4.0000,7400.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.364040',5),(21,3,17,2.5000,2.5000,4900.00,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.366620',5),(22,4,4,6.0000,6.0000,8800.00,'2026-09-18 20:14:14.384378',5,'2026-09-18 20:14:14.416632',5),(23,4,14,2.4000,2.4000,6800.00,'2026-09-18 20:14:14.384378',5,'2026-09-18 20:14:14.421269',5),(24,4,16,1.2000,1.2000,7400.00,'2026-09-18 20:14:14.384378',5,'2026-09-18 20:14:14.424645',5),(25,4,17,0.9000,0.9000,4900.00,'2026-09-18 20:14:14.384378',5,'2026-09-18 20:14:14.429317',5),(26,5,4,6.0000,6.0000,8800.00,'2026-09-18 20:14:14.453192',5,'2026-09-18 20:14:14.482047',5),(27,5,7,4.5000,4.5000,2400.00,'2026-09-18 20:14:14.453192',5,'2026-09-18 20:14:14.485751',5),(28,5,11,6.0000,6.0000,1450.00,'2026-09-18 20:14:14.453192',5,'2026-09-18 20:14:14.489469',5),(29,5,14,1.8000,1.8000,6800.00,'2026-09-18 20:14:14.453192',5,'2026-09-18 20:14:14.492875',5),(30,6,4,36.0000,36.0000,8800.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.550805',5),(31,6,6,6.0000,6.0000,12800.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.554751',5),(32,6,7,30.0000,30.0000,2400.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.557823',5),(33,6,9,18.0000,18.0000,3100.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.561089',5),(34,6,10,36.0000,36.0000,3200.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.563838',5),(35,6,11,12.0000,12.0000,1450.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.567225',5),(36,6,13,6.0000,6.0000,3600.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.571054',5),(37,6,15,6.0000,6.0000,5200.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.574352',5),(38,6,17,3.0000,3.0000,4900.00,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.577658',5),(39,7,5,14.0000,0.0000,14200.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(40,7,6,2.0000,0.0000,12800.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(41,7,8,12.0000,0.0000,2900.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(42,7,9,8.0000,0.0000,3100.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(43,7,10,12.0000,0.0000,3200.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(44,7,11,4.0000,0.0000,1450.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(45,7,12,2.0000,0.0000,4800.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(46,7,13,4.0000,0.0000,3600.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(47,7,14,4.0000,0.0000,6800.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(48,7,17,1.2000,0.0000,4900.00,'2026-09-18 20:14:14.601065',5,NULL,NULL),(49,8,4,28.0000,0.0000,8800.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(50,8,6,4.0000,0.0000,12800.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(51,8,7,20.0000,0.0000,2400.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(52,8,9,12.0000,0.0000,3100.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(53,8,10,24.0000,0.0000,3200.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(54,8,11,8.0000,0.0000,1450.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(55,8,12,4.0000,0.0000,4800.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(56,8,13,4.0000,0.0000,3600.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(57,8,14,6.0000,0.0000,6800.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(58,8,16,3.2000,0.0000,7400.00,'2026-09-18 20:14:14.632201',5,NULL,NULL),(59,8,17,2.0000,0.0000,4900.00,'2026-09-18 20:14:14.632201',5,NULL,NULL);
/*!40000 ALTER TABLE `productionmaterials` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productionorders`
--

LOCK TABLES `productionorders` WRITE;
/*!40000 ALTER TABLE `productionorders` DISABLE KEYS */;
INSERT INTO `productionorders` VALUES (1,'PRO-0001',3,1,5.0000,5.0000,2,'2026-09-18 19:49:59.128202','2026-09-18 19:49:59.915286','2026-09-18 19:50:00.076805',10000.00,21000.00,31000.00,6200.00,NULL,'2026-09-18 19:49:59.157362',2,'2026-09-18 19:50:00.076955',2,1),(2,'PRO-0001',18,2,8.0000,8.0000,2,'2026-08-21 20:14:14.000000','2026-09-18 20:14:14.207649','2026-09-18 20:14:14.279866',176000.00,962000.00,1138000.00,142250.00,NULL,'2026-09-18 20:14:14.128140',5,'2026-09-18 20:14:14.280015',5,3),(3,'PRO-0002',19,3,5.0000,5.0000,2,'2026-08-28 20:14:14.000000','2026-09-18 20:14:14.313068','2026-09-18 20:14:14.371130',140000.00,723850.00,863850.00,172770.00,NULL,'2026-09-18 20:14:14.298820',5,'2026-09-18 20:14:14.371183',5,3),(4,'PRO-0003',22,5,12.0000,12.0000,2,'2026-09-02 20:14:14.000000','2026-09-18 20:14:14.393977','2026-09-18 20:14:14.435526',54000.00,82410.00,136410.00,11367.50,NULL,'2026-09-18 20:14:14.384378',5,'2026-09-18 20:14:14.435604',5,3),(5,'PRO-0004',24,6,6.0000,6.0000,2,'2026-09-06 20:14:14.000000','2026-09-18 20:14:14.465619','2026-09-18 20:14:14.498637',42000.00,84540.00,126540.00,21090.00,NULL,'2026-09-18 20:14:14.453192',5,'2026-09-18 20:14:14.498732',5,3),(6,'PRO-0005',18,2,6.0000,6.0000,2,'2026-09-13 20:14:14.000000','2026-09-18 20:14:14.532279','2026-09-18 20:14:14.583909',132000.00,721500.00,853500.00,142250.00,NULL,'2026-09-18 20:14:14.518404',5,'2026-09-18 20:14:14.584001',5,3),(7,'PRO-0006',20,4,2.0000,0.0000,1,'2026-09-16 20:14:14.000000','2026-09-18 20:14:14.614762',NULL,84000.00,0.00,0.00,0.00,NULL,'2026-09-18 20:14:14.601065',5,'2026-09-18 20:14:14.614877',5,3),(8,'PRO-0007',19,3,4.0000,0.0000,0,'2026-09-17 20:14:14.000000',NULL,NULL,112000.00,0.00,0.00,0.00,NULL,'2026-09-18 20:14:14.632201',5,NULL,NULL,3);
/*!40000 ALTER TABLE `productionorders` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'TST-V1','Producto de prueba',NULL,NULL,1,NULL,7,1000.00,1800.00,13.00,40.0000,0.0000,1,'2026-09-18 19:49:01.593314',2,'2026-09-18 19:49:02.699806',2,1),(2,'MAT-1','Madera',NULL,NULL,0,NULL,7,520.00,0.00,13.00,204.0000,0.0000,1,'2026-09-18 19:49:20.382127',2,'2026-09-18 19:50:49.273313',2,1),(3,'FAB-1','Mesa fabricada',NULL,NULL,1,NULL,7,6200.00,9000.00,13.00,5.0000,0.0000,1,'2026-09-18 19:49:20.542592',2,'2026-09-18 19:50:00.076955',2,1),(4,'MP-MAD-01','Tablero de laurel 2.40 x 0.30 m','Tablero cepillado para cuerpo de ataud.',NULL,0,15,17,8800.00,0.00,13.00,147.0000,40.0000,1,'2026-09-18 20:14:13.050565',5,'2026-09-18 20:14:15.273915',5,3),(5,'MP-MAD-02','Tablero de cedro 2.40 x 0.30 m','Para modelos de linea alta.',NULL,0,15,17,14200.00,0.00,13.00,80.0000,20.0000,1,'2026-09-18 20:14:13.131610',5,'2026-09-18 20:14:13.849943',5,3),(6,'MP-MAD-03','Plywood 4 x 8 pies','Fondo y tapas interiores.',NULL,0,15,17,12800.00,0.00,13.00,41.0000,15.0000,1,'2026-09-18 20:14:13.143935',5,'2026-09-18 20:14:14.554751',5,3),(7,'MP-TEL-01','Raso blanco','Forro interior estandar.',NULL,0,16,19,2400.00,0.00,13.00,200.5000,60.0000,1,'2026-09-18 20:14:13.156386',5,'2026-09-18 20:14:14.557823',5,3),(8,'MP-TEL-02','Raso champagne','Forro interior linea alta.',NULL,0,16,19,2900.00,0.00,13.00,95.0000,40.0000,1,'2026-09-18 20:14:13.166945',5,'2026-09-18 20:14:13.169179',5,3),(9,'MP-TEL-03','Acolchado esponja 1 pulgada',NULL,NULL,0,16,20,3100.00,0.00,13.00,133.0000,40.0000,1,'2026-09-18 20:14:13.178624',5,'2026-09-18 20:49:06.998971',5,3),(10,'MP-HER-01','Manija metalica cromada','Seis por ataud.',NULL,0,17,17,3200.00,0.00,13.00,323.0000,80.0000,1,'2026-09-18 20:14:13.190351',5,'2026-09-18 20:14:15.323695',5,3),(11,'MP-HER-02','Bisagra piano 30 cm',NULL,NULL,0,17,17,1450.00,0.00,13.00,194.0000,50.0000,1,'2026-09-18 20:14:13.203524',5,'2026-09-18 20:14:15.358772',5,3),(12,'MP-HER-03','Crucifijo decorativo',NULL,NULL,0,17,17,4800.00,0.00,13.00,110.0000,25.0000,1,'2026-09-18 20:14:13.214561',5,'2026-09-18 20:14:14.355447',5,3),(13,'MP-HER-04','Tornillo para madera 2 pulgadas (caja 100)',NULL,NULL,0,17,23,3600.00,0.00,13.00,14.0000,8.0000,1,'2026-09-18 20:14:13.226824',5,'2026-09-18 20:37:36.711180',5,3),(14,'MP-PIN-01','Barniz poliuretano',NULL,NULL,0,18,21,6800.00,0.00,13.00,80.3000,20.0000,1,'2026-09-18 20:14:13.236129',5,'2026-09-18 20:14:15.277149',5,3),(15,'MP-PIN-02','Sellador para madera',NULL,NULL,0,18,21,5200.00,0.00,13.00,43.0000,15.0000,1,'2026-09-18 20:14:13.248883',5,'2026-09-18 20:14:14.574352',5,3),(16,'MP-PIN-03','Tinte caoba',NULL,NULL,0,18,21,7400.00,0.00,13.00,20.8000,10.0000,1,'2026-09-18 20:14:13.260601',5,'2026-09-18 20:14:14.424645',5,3),(17,'MP-CON-01','Pegamento de contacto',NULL,NULL,0,18,21,4900.00,0.00,13.00,32.6000,10.0000,1,'2026-09-18 20:14:13.273162',5,'2026-09-18 20:37:36.765578',5,3),(18,'PT-ATA-01','Ataud economico blanco','Laurel, forro de raso blanco, seis manijas.',NULL,1,13,17,142250.00,145000.00,13.00,19.0000,5.0000,1,'2026-09-18 20:14:13.284146',5,'2026-09-18 20:14:14.872793',5,3),(19,'PT-ATA-02','Ataud clasico caoba','Laurel con tinte caoba y acabado en barniz.',NULL,1,13,17,172770.00,195000.00,13.00,7.0000,3.0000,1,'2026-09-18 20:14:13.296380',5,'2026-09-18 20:14:14.897375',5,3),(20,'PT-ATA-03','Ataud premium en cedro','Cedro macizo, forro champagne, herrajes cromados.',NULL,1,13,17,168000.00,285000.00,13.00,3.0000,2.0000,1,'2026-09-18 20:14:13.307515',5,'2026-09-18 20:14:13.309637',5,3),(21,'PT-ATA-04','Ataud infantil','Medida reducida, forro blanco.',NULL,1,13,17,52000.00,95000.00,13.00,3.0000,2.0000,1,'2026-09-18 20:14:13.319522',5,'2026-09-18 20:14:14.901452',5,3),(22,'PT-URN-01','Urna de madera tallada',NULL,NULL,1,14,17,11367.50,42000.00,13.00,24.0000,6.0000,1,'2026-09-18 20:14:13.331407',5,'2026-09-18 20:14:14.849135',5,3),(23,'PT-URN-02','Urna sencilla en laurel',NULL,NULL,1,14,17,14500.00,29000.00,13.00,13.0000,8.0000,1,'2026-09-18 20:14:13.341303',5,'2026-09-18 20:14:14.926202',5,3),(24,'PT-BAU-01','Baul para cenizas mediano',NULL,NULL,1,14,17,21090.00,52000.00,13.00,11.0000,4.0000,1,'2026-09-18 20:14:13.351504',5,'2026-09-18 20:14:14.954794',5,3),(25,'PT-BAU-02','Baul para cenizas grande',NULL,NULL,1,14,17,34000.00,65000.00,13.00,5.0000,3.0000,1,'2026-09-18 20:14:13.361360',5,'2026-09-18 20:14:14.929963',5,3),(26,'SV-REP-01','Mano de obra de reparacion','Hora de taller para reparacion y mantenimiento.',NULL,2,12,24,0.00,9000.00,13.00,0.0000,0.0000,1,'2026-09-18 20:14:13.371744',5,NULL,NULL,3),(27,'SV-TRA-01','Transporte y entrega','Entrega en la zona de Alajuela y alrededores.',NULL,2,12,17,0.00,15000.00,13.00,0.0000,0.0000,1,'2026-09-18 20:14:13.379933',5,NULL,NULL,3);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchaseitems`
--

LOCK TABLES `purchaseitems` WRITE;
/*!40000 ALTER TABLE `purchaseitems` DISABLE KEYS */;
INSERT INTO `purchaseitems` VALUES (1,1,2,50.0000,520.00,13.00,26000.00,'2026-09-18 19:50:48.978166',2,NULL,NULL),(2,2,4,60.0000,8500.00,13.00,510000.00,'2026-09-18 20:14:13.612145',5,NULL,NULL),(3,2,6,15.0000,12800.00,13.00,192000.00,'2026-09-18 20:14:13.612145',5,NULL,NULL),(4,3,7,80.0000,2400.00,13.00,192000.00,'2026-09-18 20:14:13.749555',5,NULL,NULL),(5,3,9,50.0000,3100.00,13.00,155000.00,'2026-09-18 20:14:13.749555',5,NULL,NULL),(6,4,10,120.0000,3200.00,13.00,384000.00,'2026-09-18 20:14:13.778074',5,NULL,NULL),(7,4,11,60.0000,1450.00,13.00,87000.00,'2026-09-18 20:14:13.778074',5,NULL,NULL),(8,4,12,30.0000,4800.00,13.00,144000.00,'2026-09-18 20:14:13.778074',5,NULL,NULL),(9,5,14,25.0000,6800.00,13.00,170000.00,'2026-09-18 20:14:13.807106',5,NULL,NULL),(10,5,15,15.0000,5200.00,13.00,78000.00,'2026-09-18 20:14:13.807106',5,NULL,NULL),(11,6,4,40.0000,8800.00,13.00,352000.00,'2026-09-18 20:14:13.833200',5,NULL,NULL),(12,6,5,20.0000,14200.00,13.00,284000.00,'2026-09-18 20:14:13.833200',5,NULL,NULL),(13,7,13,10.0000,3600.00,13.00,36000.00,'2026-09-18 20:14:13.862805',5,NULL,NULL),(14,7,17,12.0000,4900.00,13.00,58800.00,'2026-09-18 20:14:13.862805',5,NULL,NULL),(16,9,9,1.0000,3100.00,13.00,3100.00,'2026-09-18 20:49:04.195182',5,NULL,NULL);
/*!40000 ALTER TABLE `purchaseitems` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,'COM-0001',1,'2026-09-18 19:50:48.942545',1,26000.00,3380.00,29380.00,NULL,NULL,'2026-09-18 19:50:48.978166',2,'2026-09-18 19:50:49.273313',2,1,30,1),(2,'COM-0001',2,'2026-08-11 20:14:13.000000',1,702000.00,91260.00,793260.00,'FE-00012455','Entrega en planta.','2026-09-18 20:14:13.612145',5,'2026-09-18 20:14:13.725687',5,3,30,1),(3,'COM-0002',3,'2026-08-18 20:14:13.000000',1,347000.00,45110.00,392110.00,'FE-0004471',NULL,'2026-09-18 20:14:13.749555',5,'2026-09-18 20:14:13.767700',5,3,15,1),(4,'COM-0003',4,'2026-08-27 20:14:13.000000',1,615000.00,79950.00,694950.00,'FE-0098120',NULL,'2026-09-18 20:14:13.778074',5,'2026-09-18 20:14:13.796477',5,3,30,1),(5,'COM-0004',5,'2026-09-04 20:14:13.000000',1,248000.00,32240.00,280240.00,'FE-0033187',NULL,'2026-09-18 20:14:13.807106',5,'2026-09-18 20:14:13.822988',5,3,0,0),(6,'COM-0005',2,'2026-09-12 20:14:13.000000',1,636000.00,82680.00,718680.00,'FE-00012781',NULL,'2026-09-18 20:14:13.833200',5,'2026-09-18 20:14:13.852330',5,3,30,1),(7,'COM-0006',6,'2026-09-18 20:14:13.860774',1,94800.00,12324.00,107124.00,NULL,'Pendiente de confirmar: falta revisar el precio del pegamento.','2026-09-18 20:14:13.862805',5,'2026-09-18 20:37:36.791022',5,3,0,0),(9,'COM-0007',6,'2026-09-18 00:00:00.000000',1,3100.00,403.00,3503.00,NULL,NULL,'2026-09-18 20:49:04.195182',5,'2026-09-18 20:49:07.061820',5,3,0,0);
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
INSERT INTO `receipts` VALUES (1,1,'2026-09-18 19:49:03.642040',10000.00,'Transferencia','TEST-001',NULL,2,'2026-09-18 19:49:03.652364',2,NULL,NULL),(2,6,'2026-09-07 00:00:00.000000',458780.00,'Sinpe Movil','SINPE-4412',NULL,5,'2026-09-18 20:14:15.046403',5,NULL,NULL),(3,9,'2026-09-17 00:00:00.000000',117520.00,'Sinpe Movil','SINPE-4412',NULL,5,'2026-09-18 20:14:15.073939',5,NULL,NULL),(4,5,'2026-09-16 20:14:15.000000',402280.00,'Transferencia','TRF-88455',NULL,5,'2026-09-18 20:14:15.090062',5,NULL,NULL),(5,3,'2026-09-09 20:14:15.000000',500000.00,'Transferencia','TRF-88120','Abono parcial.',5,'2026-09-18 20:14:15.103952',5,NULL,NULL),(6,3,'2026-09-16 00:00:00.000000',336200.00,'Transferencia','BAC 7741-0093','Cancela el saldo de la factura.',5,'2026-09-18 21:42:32.370437',5,NULL,NULL),(7,4,'2026-09-17 00:00:00.000000',627997.50,'Transferencia','BN 55120-8',NULL,5,'2026-09-18 21:42:32.536779',5,NULL,NULL),(8,11,'2026-09-18 00:00:00.000000',41245.00,'Efectivo',NULL,'Pagado al retirar la caja reparada.',5,'2026-09-18 21:42:32.588404',5,NULL,NULL);
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipeitems`
--

LOCK TABLES `recipeitems` WRITE;
/*!40000 ALTER TABLE `recipeitems` DISABLE KEYS */;
INSERT INTO `recipeitems` VALUES (1,1,2,8.0000,'2026-09-18 19:49:46.124927',2,NULL,NULL),(2,2,4,6.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(3,2,6,1.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(4,2,7,5.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(5,2,9,3.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(6,2,10,6.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(7,2,11,2.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(8,2,13,1.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(9,2,15,1.0000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(10,2,17,0.5000,'2026-09-18 20:14:13.412610',5,NULL,NULL),(11,3,4,7.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(12,3,6,1.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(13,3,7,5.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(14,3,9,3.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(15,3,10,6.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(16,3,11,2.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(17,3,12,1.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(18,3,13,1.0000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(19,3,14,1.5000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(20,3,16,0.8000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(21,3,17,0.5000,'2026-09-18 20:14:13.472277',5,NULL,NULL),(22,4,5,7.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(23,4,6,1.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(24,4,8,6.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(25,4,9,4.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(26,4,10,6.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(27,4,11,2.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(28,4,12,1.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(29,4,13,2.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(30,4,14,2.0000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(31,4,17,0.6000,'2026-09-18 20:14:13.489407',5,NULL,NULL),(32,5,4,2.0000,'2026-09-18 20:14:13.505317',5,NULL,NULL),(33,5,14,0.8000,'2026-09-18 20:14:13.505317',5,NULL,NULL),(34,5,16,0.4000,'2026-09-18 20:14:13.505317',5,NULL,NULL),(35,5,17,0.3000,'2026-09-18 20:14:13.505317',5,NULL,NULL),(36,6,4,2.0000,'2026-09-18 20:14:13.519813',5,NULL,NULL),(37,6,7,1.5000,'2026-09-18 20:14:13.519813',5,NULL,NULL),(38,6,11,2.0000,'2026-09-18 20:14:13.519813',5,NULL,NULL),(39,6,14,0.6000,'2026-09-18 20:14:13.519813',5,NULL,NULL);
/*!40000 ALTER TABLE `recipeitems` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,'Mesa estandar',3,1.0000,2000.00,NULL,1,'2026-09-18 19:49:46.124927',2,NULL,NULL,1),(2,'Ataud economico blanco',18,1.0000,22000.00,'Dos operarios, aproximadamente un dia de trabajo.',1,'2026-09-18 20:14:13.412610',5,NULL,NULL,3),(3,'Ataud clasico caoba',19,1.0000,28000.00,'Lleva tinte y doble mano de barniz.',1,'2026-09-18 20:14:13.472277',5,NULL,NULL,3),(4,'Ataud premium en cedro',20,1.0000,42000.00,'Linea alta. Solo bajo pedido.',1,'2026-09-18 20:14:13.489407',5,NULL,NULL,3),(5,'Urna de madera tallada',22,4.0000,18000.00,'Se hacen en lotes de cuatro.',1,'2026-09-18 20:14:13.505317',5,NULL,NULL,3),(6,'Baul para cenizas mediano',24,2.0000,14000.00,NULL,1,'2026-09-18 20:14:13.519813',5,NULL,NULL,3);
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refreshtokens`
--

LOCK TABLES `refreshtokens` WRITE;
/*!40000 ALTER TABLE `refreshtokens` DISABLE KEYS */;
INSERT INTO `refreshtokens` VALUES (9,3,'G6ybuo4n2c2boc4r1npAZZCuUjQLtMoKAA03Bd4GvBKZy3WSa9qUi4gjCsCZnA5J','2026-09-14 14:10:15.520195',NULL,'::1','2026-09-07 14:10:15.522257',NULL,NULL,NULL,NULL),(29,2,'TorvaMJ8rauLTcr7nrFEbbOeSQC_zl4LC_q3C-MEJvajzz4stOCh_ZPdEkG0y6ea','2026-09-25 19:19:29.151539','2026-09-18 20:19:44.027668','::1','2026-09-18 19:19:29.152874',NULL,'2026-09-18 20:19:44.047712',2,NULL),(30,2,'voRm4qAq7mABGJfxRkyPjBLIdfUQ1Ol-id88mrOhhbCbVKmW0PreXqI2hIsyndCu','2026-09-25 19:47:08.043803',NULL,'::1','2026-09-18 19:47:08.107195',NULL,NULL,NULL,NULL),(31,2,'joQr7dVmZucjLcNf7Sx1Oi5-plOkgmPUsp_SpbW2qn4QXLwSDrxtwmkCjgwwhNq_','2026-09-25 19:49:01.048761',NULL,'::1','2026-09-18 19:49:01.097644',NULL,NULL,NULL,NULL),(32,1,'xS4_9sta5XKwml8C61yfLXeq4muhe9mIhmOn4uhFsVuwbzHWNzl01qAiVoL7DhEe','2026-09-25 19:51:13.146554',NULL,'::1','2026-09-18 19:51:13.149032',NULL,NULL,NULL,NULL),(33,1,'yiIqMzxkvfHww3f2Agg0FXrHffkAht6aGsR2_rFYY1PXEwOwA4UvGsxdK6M0rn2n','2026-09-25 19:51:30.613418',NULL,'::1','2026-09-18 19:51:30.614717',NULL,NULL,NULL,NULL),(34,4,'kXYn4CxvobnJJn9n0PBGXzyoR6XPHNpL5_eZd_Jseb3IP5k3cZbha7tuSK1eW1J6','2026-09-25 19:51:57.817445',NULL,'::1','2026-09-18 19:51:57.818284',NULL,NULL,NULL,NULL),(35,4,'n9FoVE5mEIxvlcKKqQAdzre25rFaZ_7KCb7nGeV6wYXKFeTu0fNzBXwH1Tk7Tx9S','2026-09-25 19:52:10.008776',NULL,'::1','2026-09-18 19:52:10.009900',NULL,NULL,NULL,NULL),(36,2,'IKvej5YEUF5HZCeJucclbgL_YMJ0nS27IZpTyoyP5rHY0iV3ybm7hchLWnlmdZFC','2026-09-25 19:53:37.842686',NULL,'::1','2026-09-18 19:53:37.898528',NULL,NULL,NULL,NULL),(37,1,'RObiQIMiHHjbVDJjqwQBNH7LbysPL6MsyikTcQIvw53q2iRtJL4LKhw0HG3kQ8zC','2026-09-25 20:14:12.075892',NULL,'::1','2026-09-18 20:14:12.158870',NULL,NULL,NULL,NULL),(38,5,'9dBVtpQf4_TOeqylX49qaLszFqlzqq655UvQIH9JV1B2KleoxDw555Yfk_p68xau','2026-09-25 20:14:12.759528',NULL,'::1','2026-09-18 20:14:12.761234',NULL,NULL,NULL,NULL),(39,5,'lx7YdC6iyuIt_72czid1cUh-sScZKx39gkaQJsmlWh04GmjxkQ5hxxUw5hmvQEb5','2026-09-25 20:16:08.345758',NULL,'::1','2026-09-18 20:16:08.412664',NULL,NULL,NULL,NULL),(40,5,'AVLsw8iFr5Ue1G7vXRZAKY0nQpgp6o3eyAmxB-BrHXmlWNr4bXnqBGntYhcyfz6r','2026-09-25 20:16:40.383535',NULL,'::1','2026-09-18 20:16:40.385369',NULL,NULL,NULL,NULL),(41,5,'7PEjGOC-ug3FUy6YgmpvtlSoo3COwoTzipFoKwn3qGJGbJX-aEUjxQA2X_-z52TA','2026-09-25 20:17:06.706702',NULL,'::1','2026-09-18 20:17:06.708718',NULL,NULL,NULL,NULL),(42,5,'IkXMQXyFZeiHEa7bse-zH0Br1_Uc7AEORmvRbnPVf6LFy-6rGJEy55z72RoXxFMl','2026-09-25 20:17:14.988492',NULL,'::1','2026-09-18 20:17:14.989361',NULL,NULL,NULL,NULL),(43,1,'YP2JZ2cKAFAG7Su3lJW69mTSga35V1NfrhXdWTErmo6GDyHwEJn46qTqfGS0uZoE','2026-09-25 20:17:25.030720',NULL,'::1','2026-09-18 20:17:25.032087',NULL,NULL,NULL,NULL),(45,6,'tjMqmkF8zrQN1OzOlkL5rw7ohZ8LAxZinkyTjLP_Wc_J6Q0r_tGQWQ3cGGxYYxz0','2026-09-25 20:20:06.870795','2026-09-18 20:20:14.231887','::1','2026-09-18 20:20:06.872536',NULL,'2026-09-18 20:20:14.231974',6,NULL),(47,5,'B4o2iE-vXwJA1Rzaj4OQ-cZCVaulT57PgcTZdqCd-tBqUSsJTAa5CsENTxoOiFM2','2026-09-25 20:25:41.138007',NULL,'::1','2026-09-18 20:25:41.198718',NULL,NULL,NULL,NULL),(48,5,'rh7lfBUunSws-TM50GGWk_eFYD4MIz1E9gk6Z5Si4qILUTa2f0-d_sRBEOmsrZrP','2026-09-25 20:25:56.383294',NULL,'::1','2026-09-18 20:25:56.384770',NULL,NULL,NULL,NULL),(49,5,'AQbusvfpNW66FcZgecUrCJpggPQJIQENksAjNQIG6dFoR4s6rNBc7uo6ki2qjUqO','2026-09-25 20:45:22.472552',NULL,'::1','2026-09-18 20:45:22.500002',NULL,NULL,NULL,NULL),(50,5,'BJRaJbwwdavsIYlWfGPmFUu0pQ7TR59eX8qAzbHdusacF_63-x0831KV1xp2T_N6','2026-09-25 20:45:30.635171',NULL,'::1','2026-09-18 20:45:30.636173',NULL,NULL,NULL,NULL),(51,5,'jpV3XjcVIASKQvmRDrHqTvoxZioUrEQJAGoyHxvVzsLiG1r-Eg08h-sQrNe1_WDO','2026-09-25 20:46:22.149250',NULL,'::1','2026-09-18 20:46:22.208551',NULL,NULL,NULL,NULL),(52,5,'XC4NdkJCy38cCz65zCYHntTBpTdlGcuTwdFHxDVmfPr0O6z76TaDJxaPLs-VMxgN','2026-09-25 20:46:45.098207',NULL,'::1','2026-09-18 20:46:45.099631',NULL,NULL,NULL,NULL),(54,5,'0_5p8xvtQB6JLw9SpDZJkMqaQbgoh8alIt2XWLCLAWPg_F2FxylqOJPzJ_cmIaLX','2026-09-25 21:30:42.006663',NULL,'::1','2026-09-18 21:30:42.021361',NULL,NULL,NULL,NULL),(55,5,'Ny9SvDJofAIIwfHn54ObMO47ScR6J6IOGwX4K35ajZdWcorrKxpQMYXNR8v7meZa','2026-09-25 21:42:31.875351',NULL,'::1','2026-09-18 21:42:31.949906',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `refreshtokens` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repairmaterials`
--

LOCK TABLES `repairmaterials` WRITE;
/*!40000 ALTER TABLE `repairmaterials` DISABLE KEYS */;
INSERT INTO `repairmaterials` VALUES (1,1,2,4.0000,900.00,3600.00,'2026-09-18 19:50:21.652162',2,NULL,NULL),(2,2,4,2.0000,11000.00,22000.00,'2026-09-18 20:14:15.186052',5,NULL,NULL),(3,2,14,1.0000,9500.00,9500.00,'2026-09-18 20:14:15.186052',5,NULL,NULL),(4,3,10,3.0000,4500.00,13500.00,'2026-09-18 20:14:15.306821',5,NULL,NULL),(5,3,13,1.0000,5000.00,5000.00,'2026-09-18 20:14:15.306821',5,NULL,NULL),(6,4,11,2.0000,2500.00,5000.00,'2026-09-18 20:14:15.343493',5,NULL,NULL),(7,5,7,4.0000,3800.00,15200.00,'2026-09-18 20:14:15.368687',5,NULL,NULL),(8,5,9,2.0000,4500.00,9000.00,'2026-09-18 20:14:15.368687',5,NULL,NULL),(9,6,14,0.5000,9500.00,4750.00,'2026-09-18 20:14:15.384339',5,NULL,NULL);
/*!40000 ALTER TABLE `repairmaterials` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repairorders`
--

LOCK TABLES `repairorders` WRITE;
/*!40000 ALTER TABLE `repairorders` DISABLE KEYS */;
INSERT INTO `repairorders` VALUES (1,'REP-0001',1,'Mesa de comedor','Pata quebrada','Se repuso la pata',3,'2026-09-18 19:50:21.623355',NULL,'2026-09-18 19:50:22.669233','2026-09-18 19:50:22.947705',18000.00,3600.00,13.00,2808.00,24408.00,1,15,NULL,'2026-09-18 19:50:21.652162',2,'2026-09-18 19:50:22.948293',2,1),(2,'REP-0001',2,'Ataud clasico caoba - tapa danada','La tapa se rajo durante el traslado.','Se sustituyo la tapa completa y se retoco el barniz.',3,'2026-08-25 20:14:15.000000','2026-08-31 20:14:15.000000','2026-09-18 20:14:15.278903','2026-09-18 20:14:15.293597',35000.00,31500.00,13.00,8645.00,75145.00,1,30,NULL,'2026-09-18 20:14:15.186052',5,'2026-09-18 20:14:15.294430',5,3),(3,'REP-0002',3,'Ataud economico blanco - herrajes sueltos','Tres manijas se soltaron.','Se cambiaron las manijas y se reforzo la fijacion.',3,'2026-09-02 20:14:15.000000','2026-09-06 20:14:15.000000','2026-09-18 20:14:15.327433','2026-09-18 20:14:15.335326',18000.00,18500.00,13.00,4745.00,41245.00,0,0,NULL,'2026-09-18 20:14:15.306821',5,'2026-09-18 20:14:15.335449',5,3),(4,'REP-0003',4,'Baul para cenizas - bisagra quebrada','No cierra bien.','Bisagra sustituida. Listo para retirar.',2,'2026-09-09 20:14:15.000000','2026-09-20 20:14:15.000000','2026-09-18 20:14:15.360686',NULL,12000.00,5000.00,13.00,2210.00,19210.00,0,0,NULL,'2026-09-18 20:14:15.343493',5,'2026-09-18 20:14:15.360800',5,3),(5,'REP-0004',6,'Ataud infantil - forro manchado','El forro interior se mancho con humedad.','Se cambia el forro completo.',1,'2026-09-13 20:14:15.000000','2026-09-22 20:14:15.000000',NULL,NULL,15000.00,24200.00,13.00,5096.00,44296.00,0,0,NULL,'2026-09-18 20:14:15.368687',5,'2026-09-18 20:14:15.376582',5,3),(6,'REP-0005',5,'Urna tallada - acabado opaco','Perdio brillo, el cliente pide retoque.',NULL,0,'2026-09-16 20:14:15.000000','2026-09-23 20:14:15.000000',NULL,NULL,9000.00,4750.00,13.00,1787.50,15537.50,0,0,NULL,'2026-09-18 20:14:15.384339',5,NULL,NULL,3),(7,'REP-0006',2,'Ataud premium cedro - ajuste de tapa','La tapa no calza del todo.',NULL,1,'2026-09-17 20:14:15.000000','2026-09-17 20:14:15.000000',NULL,NULL,22000.00,0.00,13.00,2860.00,24860.00,0,0,NULL,'2026-09-18 20:14:15.392651',5,'2026-09-18 20:14:15.402888',5,3);
/*!40000 ALTER TABLE `repairorders` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rolepermissions`
--

LOCK TABLES `rolepermissions` WRITE;
/*!40000 ALTER TABLE `rolepermissions` DISABLE KEYS */;
INSERT INTO `rolepermissions` VALUES (1,1,'platform_overview',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(2,1,'platform_companies',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(3,1,'platform_billing',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(4,1,'platform_plans',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(5,1,'platform_audit',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(6,1,'dashboard',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(7,1,'customers',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(8,1,'suppliers',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(9,1,'products',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(10,1,'inventory',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(11,1,'purchases',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(12,1,'sales',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(13,1,'production',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(14,1,'repairs',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(15,1,'receivables',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(16,1,'payables',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(17,1,'income',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(18,1,'expenses',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(19,1,'reports',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(20,1,'users',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(21,1,'audit',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(22,1,'settings',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(23,2,'platform_overview',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(24,2,'platform_companies',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(25,2,'platform_billing',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(26,2,'platform_plans',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(27,3,'dashboard',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(28,3,'customers',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(29,3,'suppliers',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(30,3,'products',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(31,3,'inventory',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(32,3,'purchases',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(33,3,'sales',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(34,3,'production',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(35,3,'repairs',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(36,3,'receivables',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(37,3,'payables',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(38,3,'income',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(39,3,'expenses',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(40,3,'reports',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(41,3,'users',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(42,3,'audit',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(43,3,'settings',1,1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(44,4,'dashboard',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(45,4,'customers',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(46,4,'suppliers',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(47,4,'products',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(48,4,'inventory',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(49,4,'purchases',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(50,4,'sales',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(51,4,'production',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(52,4,'repairs',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(53,4,'receivables',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(54,4,'payables',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(55,4,'income',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(56,4,'expenses',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(57,4,'reports',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(58,4,'audit',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL),(59,4,'settings',1,0,'2026-09-07 14:05:29.263226',NULL,NULL,NULL);
/*!40000 ALTER TABLE `rolepermissions` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Desarrollador','Acceso completo a la plataforma y a cualquier empresa. Puede ver el sistema como la ve un cliente.',1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL,1,'developer',0),(2,'Administración Gestora','Gestiona empresas, suscripciones y cobros. No entra a los datos internos de una empresa.',1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL,2,'platform_admin',0),(3,'Administrador','Administra la empresa: registra, edita y da de baja información.',1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL,3,'company_admin',1),(4,'Consulta','Solo lectura de la información de la empresa.',1,'2026-09-07 14:05:29.263226',NULL,NULL,NULL,4,'company_viewer',1);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saleitems`
--

LOCK TABLES `saleitems` WRITE;
/*!40000 ALTER TABLE `saleitems` DISABLE KEYS */;
INSERT INTO `saleitems` VALUES (1,1,1,10.0000,1800.00,5.00,13.00,17100.00,1000.00,'2026-09-18 19:49:01.966883',2,NULL,NULL),(2,2,18,5.0000,145000.00,0.00,13.00,725000.00,142250.00,'2026-09-18 20:14:14.692912',5,NULL,NULL),(3,2,27,1.0000,15000.00,0.00,13.00,15000.00,0.00,'2026-09-18 20:14:14.692912',5,NULL,NULL),(4,3,19,3.0000,195000.00,5.00,13.00,555750.00,172770.00,'2026-09-18 20:14:14.815107',5,NULL,NULL),(5,4,22,6.0000,42000.00,0.00,13.00,252000.00,11367.50,'2026-09-18 20:14:14.839044',5,NULL,NULL),(6,4,24,2.0000,52000.00,0.00,13.00,104000.00,21090.00,'2026-09-18 20:14:14.839044',5,NULL,NULL),(7,5,18,2.0000,145000.00,0.00,13.00,290000.00,142250.00,'2026-09-18 20:14:14.862775',5,NULL,NULL),(8,5,23,4.0000,29000.00,0.00,13.00,116000.00,14500.00,'2026-09-18 20:14:14.862775',5,NULL,NULL),(9,6,19,2.0000,195000.00,0.00,13.00,390000.00,172770.00,'2026-09-18 20:14:14.887222',5,NULL,NULL),(10,6,21,1.0000,95000.00,0.00,13.00,95000.00,52000.00,'2026-09-18 20:14:14.887222',5,NULL,NULL),(11,7,23,5.0000,29000.00,0.00,13.00,145000.00,14500.00,'2026-09-18 20:14:14.914641',5,NULL,NULL),(12,7,25,1.0000,65000.00,0.00,13.00,65000.00,34000.00,'2026-09-18 20:14:14.914641',5,NULL,NULL),(13,8,24,2.0000,52000.00,0.00,13.00,104000.00,21090.00,'2026-09-18 20:14:14.943718',5,NULL,NULL),(14,9,20,2.0000,285000.00,0.00,13.00,570000.00,168000.00,'2026-09-18 20:14:14.969444',5,NULL,NULL),(15,10,9,1.0000,0.00,0.00,13.00,0.00,3100.00,'2026-09-18 20:37:03.817733',5,NULL,NULL);
/*!40000 ALTER TABLE `saleitems` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,'VEN-0001',1,'2026-09-18 19:49:01.919723',1,1,30,17100.00,900.00,2223.00,19323.00,NULL,'2026-09-18 19:49:01.966883',2,'2026-09-18 19:49:02.723876',2,1),(2,'VEN-0001',2,'2026-08-23 20:14:14.000000',1,1,30,740000.00,0.00,96200.00,836200.00,'Pedido mensual.','2026-09-18 20:14:14.692912',5,'2026-09-18 20:14:14.789205',5,3),(3,'VEN-0002',3,'2026-08-30 20:14:14.000000',1,1,30,555750.00,29250.00,72247.50,627997.50,NULL,'2026-09-18 20:14:14.815107',5,'2026-09-18 20:14:14.828997',5,3),(4,'VEN-0003',4,'2026-09-03 20:14:14.000000',1,1,15,356000.00,0.00,46280.00,402280.00,NULL,'2026-09-18 20:14:14.839044',5,'2026-09-18 20:14:14.854014',5,3),(5,'VEN-0004',5,'2026-09-07 20:14:14.000000',1,0,0,406000.00,0.00,52780.00,458780.00,NULL,'2026-09-18 20:14:14.862775',5,'2026-09-18 20:14:14.877664',5,3),(6,'VEN-0005',2,'2026-09-10 20:14:14.000000',1,1,30,485000.00,0.00,63050.00,548050.00,NULL,'2026-09-18 20:14:14.887222',5,'2026-09-18 20:14:14.904578',5,3),(7,'VEN-0006',6,'2026-09-14 20:14:14.000000',1,1,30,210000.00,0.00,27300.00,237300.00,NULL,'2026-09-18 20:14:14.914641',5,'2026-09-18 20:14:14.932375',5,3),(8,'VEN-0007',5,'2026-09-17 20:14:14.000000',1,0,0,104000.00,0.00,13520.00,117520.00,NULL,'2026-09-18 20:14:14.943718',5,'2026-09-18 20:14:14.958167',5,3),(9,'VEN-0008',3,'2026-09-18 20:14:14.000000',0,1,30,570000.00,0.00,74100.00,644100.00,'Esperando confirmacion del cliente para fabricar.','2026-09-18 20:14:14.969444',5,NULL,NULL,3),(10,'VEN-0009',6,'2026-09-18 20:37:03.683671',1,1,30,0.00,0.00,0.00,0.00,NULL,'2026-09-18 20:37:03.817733',5,'2026-09-18 20:37:47.321015',5,3);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptionpayments`
--

LOCK TABLES `subscriptionpayments` WRITE;
/*!40000 ALTER TABLE `subscriptionpayments` DISABLE KEYS */;
INSERT INTO `subscriptionpayments` VALUES (1,1,'2026-09-07 14:10:14.886789',45000.00,'Transferencia','SINPE-8891','2026-10-07 00:00:00.000000','2026-12-07 00:00:00.000000',NULL,1,'2026-09-07 14:10:14.900068',1,NULL,NULL),(2,3,'2026-08-04 20:14:12.000000',20000.00,'Transferencia','SINPE-884512','2026-09-18 00:00:00.000000','2026-10-18 00:00:00.000000','Primer mes',1,'2026-09-18 20:14:12.684849',1,NULL,NULL);
/*!40000 ALTER TABLE `subscriptionpayments` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (1,1,1,'2026-09-07 00:00:00.000000','2026-12-07 00:00:00.000000',1,15000.00,NULL,'2026-09-07 14:05:29.590088',NULL,'2026-09-07 14:49:02.644696',1),(2,2,2,'2026-09-07 00:00:00.000000','2026-10-07 00:00:00.000000',1,15000.00,NULL,'2026-09-07 14:09:58.904338',1,'2026-09-07 14:48:54.752442',1),(3,3,2,'2026-08-04 00:00:00.000000','2026-10-18 00:00:00.000000',1,20000.00,NULL,'2026-09-18 20:14:12.542300',1,'2026-09-18 20:14:12.684849',1);
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'PRV-0001','Proveedor Prueba',NULL,NULL,NULL,NULL,NULL,NULL,1,30,NULL,1,'2026-09-18 19:50:48.807275',2,NULL,NULL,1),(2,'PRV-0001','Maderas del Valle S.A.','Maderas del Valle','3-101-220118','2445-1120','ventas@maderasdelvalle.cr','San Ramon, Alajuela','Jorge Alfaro',1,30,'Tablero de laurel y cedro. Entrega los martes.',1,'2026-09-18 20:14:12.883284',5,NULL,NULL,3),(3,'PRV-0002','Textiles Monserrat',NULL,'3-102-448201','2222-9087','pedidos@textilesmonserrat.cr','San Jose centro','Ana Cordero',1,15,'Raso y acolchado interior.',1,'2026-09-18 20:14:12.904493',5,NULL,NULL,3),(4,'PRV-0003','Herrajes y Accesorios CR',NULL,'3-101-559034','2438-7712','info@herrajescr.com','Heredia, La Aurora','Luis Vega',1,30,'Manijas, bisagras y crucifijos.',1,'2026-09-18 20:14:12.911804',5,NULL,NULL,3),(5,'PRV-0004','Pinturas y Solventes del Norte',NULL,'3-101-330876','2460-3345','ventas@pinturasnorte.cr','Ciudad Quesada, San Carlos','Marvin Salas',0,0,'Barniz, sellador y tinte. Siempre de contado.',1,'2026-09-18 20:14:12.922192',5,NULL,NULL,3),(6,'PRV-0005','Ferreteria El Carpintero',NULL,'3-101-118722','2451-0098','elcarpintero@gmail.com','Naranjo centro','Don Rafael',0,0,'Tornilleria, pegamento y consumibles del taller.',1,'2026-09-18 20:14:12.930385',5,'2026-09-18 20:27:48.250243',5,3);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'Unidad','ud',0,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(2,'Pieza','pz',0,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(3,'Metro','m',2,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(4,'Metro cuadrado','m2',2,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(5,'Litro','L',2,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(6,'Kilogramo','kg',3,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(7,'Caja','cja',0,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(8,'Hora','h',2,1,'2026-09-07 14:05:29.623413',NULL,NULL,NULL,1),(9,'Unidad','ud',0,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(10,'Pieza','pz',0,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(11,'Metro','m',2,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(12,'Metro cuadrado','m2',2,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(13,'Litro','L',2,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(14,'Kilogramo','kg',3,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(15,'Caja','cja',0,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(16,'Hora','h',2,1,'2026-09-07 14:09:58.906946',1,NULL,NULL,2),(17,'Unidad','ud',0,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(18,'Pieza','pz',0,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(19,'Metro','m',2,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(20,'Metro cuadrado','m2',2,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(21,'Litro','L',2,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(22,'Kilogramo','kg',3,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(23,'Caja','cja',0,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3),(24,'Hora','h',2,1,'2026-09-18 20:14:12.570615',1,NULL,NULL,3);
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_Users_Email` (`Email`),
  KEY `IX_Users_RoleId` (`RoleId`),
  KEY `IX_Users_CompanyId` (`CompanyId`),
  CONSTRAINT `FK_Users_Companies_CompanyId` FOREIGN KEY (`CompanyId`) REFERENCES `companies` (`Id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_Users_Roles_RoleId` FOREIGN KEY (`RoleId`) REFERENCES `roles` (`Id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Desarrollador','Gestora','dev@gestora.local','G+vnDjzQFCaBPeNqa2EBD4BOpA2B34TwLRXkht42KBQ=','PBKDF2$150000$vT1o0PjmyZZySckldPyloA==',NULL,1,1,'2026-09-18 20:17:25.030534',0,NULL,'2026-09-07 14:05:29.490423',NULL,'2026-09-18 20:17:25.032087',NULL,NULL),(2,'Administración','Mi Empresa','empresa@gestora.local','nUd+FFC6zXgJv5FJcQP4183xvZINI0Z8lpjkJ4NK6pI=','PBKDF2$150000$7Sr1ecgGVPhyhZxI2kbHOg==',NULL,1,3,'2026-09-18 19:53:37.839463',0,NULL,'2026-09-07 14:05:29.590088',NULL,'2026-09-18 19:53:37.898528',NULL,1),(3,'Marta','Rodriguez','admin@carpinteriarodriguez.com','rHNBlN2vE5Pgp5smQZhE9uh4+iz9uyX2gVE9pIYao+Y=','PBKDF2$150000$gBy8Nkf6uNwzoOu700Z8gQ==',NULL,1,3,'2026-09-07 14:10:15.520047',0,NULL,'2026-09-07 14:09:58.904338',1,'2026-09-07 14:10:15.522257',NULL,2),(4,'Ana','Consulta','consulta@gestora.local','ugY9SHdQl4Nl15k8oOKlwaS4iqhs82FKE99BGl4ydGY=','PBKDF2$150000$wJY0jOcKAAwQTU8zMfR8TQ==',NULL,1,4,'2026-09-18 19:52:10.008612',1,NULL,'2026-09-07 14:10:42.137229',2,'2026-09-18 19:52:10.075183',NULL,1),(5,'Zusana','Artavia','admin@nazareno.cr','BGSAFywPFhpWbP8HPvUV37qzXhgE4lLAWyqD5xxIiD4=','PBKDF2$150000$EwmLaE5bSFOiIOqYbc/J2A==',NULL,1,3,'2026-09-18 21:42:31.868700',0,NULL,'2026-09-18 20:14:12.542300',1,'2026-09-18 21:42:31.949906',NULL,3),(6,'Minor','Moya','consulta@nazareno.cr','2W+H2vl8b0YkOoSePW8bUAF2BCZ6wFApSwMWR62cOeE=','PBKDF2$150000$5cff6LPUzMKXi4KMdcNzwA==','8812-4477',1,4,'2026-09-18 20:20:06.870086',0,NULL,'2026-09-18 20:16:40.799135',5,'2026-09-18 20:41:34.503422',5,3);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

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
