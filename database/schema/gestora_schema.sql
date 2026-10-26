CREATE TABLE IF NOT EXISTS `__EFMigrationsHistory` (
    `MigrationId` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
    `ProductVersion` varchar(32) CHARACTER SET utf8mb4 NOT NULL,
    CONSTRAINT `PK___EFMigrationsHistory` PRIMARY KEY (`MigrationId`)
) CHARACTER SET=utf8mb4;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    ALTER DATABASE CHARACTER SET utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `AuditLogs` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `UserId` int NULL,
        `UserName` varchar(200) CHARACTER SET utf8mb4 NOT NULL,
        `Action` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
        `Module` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
        `EntityName` varchar(80) CHARACTER SET utf8mb4 NULL,
        `EntityId` int NULL,
        `Description` varchar(500) CHARACTER SET utf8mb4 NULL,
        `OldValue` varchar(1000) CHARACTER SET utf8mb4 NULL,
        `NewValue` varchar(1000) CHARACTER SET utf8mb4 NULL,
        `IpAddress` varchar(60) CHARACTER SET utf8mb4 NULL,
        `OccurredAt` datetime(6) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_AuditLogs` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Categories` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(80) CHARACTER SET utf8mb4 NOT NULL,
        `Description` varchar(250) CHARACTER SET utf8mb4 NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Categories` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Companies` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `TaxId` varchar(50) CHARACTER SET utf8mb4 NULL,
        `Phone` varchar(30) CHARACTER SET utf8mb4 NULL,
        `Email` varchar(150) CHARACTER SET utf8mb4 NULL,
        `Address` varchar(250) CHARACTER SET utf8mb4 NULL,
        `Currency` varchar(3) CHARACTER SET utf8mb4 NOT NULL,
        `DefaultTaxRate` decimal(18,2) NOT NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_Companies` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Customers` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Code` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `Name` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `TradeName` varchar(150) CHARACTER SET utf8mb4 NULL,
        `TaxId` varchar(50) CHARACTER SET utf8mb4 NULL,
        `Phone` varchar(30) CHARACTER SET utf8mb4 NULL,
        `Email` varchar(150) CHARACTER SET utf8mb4 NULL,
        `Address` varchar(250) CHARACTER SET utf8mb4 NULL,
        `ContactName` varchar(120) CHARACTER SET utf8mb4 NULL,
        `PaymentTerm` int NOT NULL,
        `CreditDays` int NOT NULL,
        `CreditLimit` decimal(18,2) NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Customers` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Roles` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(60) CHARACTER SET utf8mb4 NOT NULL,
        `Description` varchar(200) CHARACTER SET utf8mb4 NULL,
        `IsSystem` tinyint(1) NOT NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Roles` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Suppliers` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Code` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `Name` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `TradeName` varchar(150) CHARACTER SET utf8mb4 NULL,
        `TaxId` varchar(50) CHARACTER SET utf8mb4 NULL,
        `Phone` varchar(30) CHARACTER SET utf8mb4 NULL,
        `Email` varchar(150) CHARACTER SET utf8mb4 NULL,
        `Address` varchar(250) CHARACTER SET utf8mb4 NULL,
        `ContactName` varchar(120) CHARACTER SET utf8mb4 NULL,
        `PaymentTerm` int NOT NULL,
        `CreditDays` int NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Suppliers` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Units` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(40) CHARACTER SET utf8mb4 NOT NULL,
        `Abbreviation` varchar(10) CHARACTER SET utf8mb4 NOT NULL,
        `DecimalPlaces` int NOT NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Units` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `RolePermissions` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `RoleId` int NOT NULL,
        `ModuleKey` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
        `CanRead` tinyint(1) NOT NULL,
        `CanWrite` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_RolePermissions` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RolePermissions_Roles_RoleId` FOREIGN KEY (`RoleId`) REFERENCES `Roles` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Users` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `FirstName` varchar(100) CHARACTER SET utf8mb4 NOT NULL,
        `LastName` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `Email` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `PasswordHash` varchar(200) CHARACTER SET utf8mb4 NOT NULL,
        `PasswordSalt` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `Phone` varchar(30) CHARACTER SET utf8mb4 NULL,
        `IsActive` tinyint(1) NOT NULL,
        `RoleId` int NOT NULL,
        `LastLoginAt` datetime(6) NULL,
        `FailedLoginAttempts` int NOT NULL,
        `LockedUntil` datetime(6) NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Users` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Users_Roles_RoleId` FOREIGN KEY (`RoleId`) REFERENCES `Roles` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `Products` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Code` varchar(30) CHARACTER SET utf8mb4 NOT NULL,
        `Name` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `Description` varchar(500) CHARACTER SET utf8mb4 NULL,
        `Barcode` varchar(60) CHARACTER SET utf8mb4 NULL,
        `Type` int NOT NULL,
        `CategoryId` int NULL,
        `UnitId` int NOT NULL,
        `Cost` decimal(18,2) NOT NULL,
        `Price` decimal(18,2) NOT NULL,
        `TaxRate` decimal(18,2) NOT NULL,
        `Stock` decimal(18,4) NOT NULL,
        `MinStock` decimal(18,4) NOT NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Products` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Products_Categories_CategoryId` FOREIGN KEY (`CategoryId`) REFERENCES `Categories` (`Id`) ON DELETE SET NULL,
        CONSTRAINT `FK_Products_Units_UnitId` FOREIGN KEY (`UnitId`) REFERENCES `Units` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `RefreshTokens` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `UserId` int NOT NULL,
        `Token` varchar(200) CHARACTER SET utf8mb4 NOT NULL,
        `ExpiresAt` datetime(6) NOT NULL,
        `RevokedAt` datetime(6) NULL,
        `CreatedByIp` varchar(60) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_RefreshTokens` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RefreshTokens_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE TABLE `InventoryMovements` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `ProductId` int NOT NULL,
        `Type` int NOT NULL,
        `Direction` int NOT NULL,
        `Quantity` decimal(18,4) NOT NULL,
        `StockAfter` decimal(18,4) NOT NULL,
        `UnitCost` decimal(18,2) NULL,
        `Reason` varchar(250) CHARACTER SET utf8mb4 NULL,
        `ReferenceType` varchar(40) CHARACTER SET utf8mb4 NULL,
        `ReferenceId` int NULL,
        `OccurredAt` datetime(6) NOT NULL,
        `UserId` int NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_InventoryMovements` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_InventoryMovements_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_InventoryMovements_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_AuditLogs_CompanyId_OccurredAt` ON `AuditLogs` (`CompanyId`, `OccurredAt`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Categories_CompanyId_Name` ON `Categories` (`CompanyId`, `Name`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Customers_CompanyId_Code` ON `Customers` (`CompanyId`, `Code`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_InventoryMovements_CompanyId_ProductId_OccurredAt` ON `InventoryMovements` (`CompanyId`, `ProductId`, `OccurredAt`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_InventoryMovements_ProductId` ON `InventoryMovements` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_InventoryMovements_UserId` ON `InventoryMovements` (`UserId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_Products_CategoryId` ON `Products` (`CategoryId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_Products_CompanyId_Barcode` ON `Products` (`CompanyId`, `Barcode`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Products_CompanyId_Code` ON `Products` (`CompanyId`, `Code`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_Products_UnitId` ON `Products` (`UnitId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_RefreshTokens_Token` ON `RefreshTokens` (`Token`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_RefreshTokens_UserId` ON `RefreshTokens` (`UserId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_RolePermissions_RoleId_ModuleKey` ON `RolePermissions` (`RoleId`, `ModuleKey`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Roles_CompanyId_Name` ON `Roles` (`CompanyId`, `Name`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Suppliers_CompanyId_Code` ON `Suppliers` (`CompanyId`, `Code`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Units_CompanyId_Abbreviation` ON `Units` (`CompanyId`, `Abbreviation`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE UNIQUE INDEX `IX_Users_CompanyId_Email` ON `Users` (`CompanyId`, `Email`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    CREATE INDEX `IX_Users_RoleId` ON `Users` (`RoleId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904194349_InitialSchema') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260904194349_InitialSchema', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE TABLE `Purchases` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Number` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `SupplierId` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `Status` int NOT NULL,
        `Subtotal` decimal(18,2) NOT NULL,
        `TaxAmount` decimal(18,2) NOT NULL,
        `Total` decimal(18,2) NOT NULL,
        `SupplierInvoiceNumber` varchar(60) CHARACTER SET utf8mb4 NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Purchases` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Purchases_Suppliers_SupplierId` FOREIGN KEY (`SupplierId`) REFERENCES `Suppliers` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE TABLE `AccountsPayable` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `SupplierId` int NOT NULL,
        `PurchaseId` int NULL,
        `DocumentNumber` varchar(60) CHARACTER SET utf8mb4 NOT NULL,
        `IssueDate` datetime(6) NOT NULL,
        `DueDate` datetime(6) NOT NULL,
        `Total` decimal(18,2) NOT NULL,
        `PaidAmount` decimal(18,2) NOT NULL,
        `Balance` decimal(18,2) NOT NULL,
        `Status` int NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_AccountsPayable` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_AccountsPayable_Purchases_PurchaseId` FOREIGN KEY (`PurchaseId`) REFERENCES `Purchases` (`Id`) ON DELETE SET NULL,
        CONSTRAINT `FK_AccountsPayable_Suppliers_SupplierId` FOREIGN KEY (`SupplierId`) REFERENCES `Suppliers` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE TABLE `PurchaseItems` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `PurchaseId` int NOT NULL,
        `ProductId` int NOT NULL,
        `Quantity` decimal(18,4) NOT NULL,
        `UnitCost` decimal(18,2) NOT NULL,
        `TaxRate` decimal(18,2) NOT NULL,
        `Subtotal` decimal(18,2) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_PurchaseItems` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_PurchaseItems_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_PurchaseItems_Purchases_PurchaseId` FOREIGN KEY (`PurchaseId`) REFERENCES `Purchases` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE TABLE `Payments` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `AccountPayableId` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `Amount` decimal(18,2) NOT NULL,
        `Method` varchar(40) CHARACTER SET utf8mb4 NOT NULL,
        `Reference` varchar(80) CHARACTER SET utf8mb4 NULL,
        `Notes` varchar(250) CHARACTER SET utf8mb4 NULL,
        `UserId` int NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_Payments` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Payments_AccountsPayable_AccountPayableId` FOREIGN KEY (`AccountPayableId`) REFERENCES `AccountsPayable` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_Payments_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_AccountsPayable_CompanyId_Status_DueDate` ON `AccountsPayable` (`CompanyId`, `Status`, `DueDate`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_AccountsPayable_PurchaseId` ON `AccountsPayable` (`PurchaseId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_AccountsPayable_SupplierId` ON `AccountsPayable` (`SupplierId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_Payments_AccountPayableId` ON `Payments` (`AccountPayableId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_Payments_UserId` ON `Payments` (`UserId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_PurchaseItems_ProductId` ON `PurchaseItems` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_PurchaseItems_PurchaseId` ON `PurchaseItems` (`PurchaseId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE UNIQUE INDEX `IX_Purchases_CompanyId_Number` ON `Purchases` (`CompanyId`, `Number`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_Purchases_CompanyId_Status_Date` ON `Purchases` (`CompanyId`, `Status`, `Date`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    CREATE INDEX `IX_Purchases_SupplierId` ON `Purchases` (`SupplierId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260904203142_AddPurchasingAndPayables') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260904203142_AddPurchasingAndPayables', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Users` DROP INDEX `IX_Users_CompanyId_Email`;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Roles` DROP INDEX `IX_Roles_CompanyId_Name`;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Roles` DROP COLUMN `IsSystem`;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Companies` DROP COLUMN `Email`;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Companies` DROP COLUMN `IsActive`;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Roles` RENAME COLUMN `CompanyId` TO `SortOrder`;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Users` MODIFY COLUMN `CompanyId` int NULL;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Roles` ADD `Key` varchar(40) CHARACTER SET utf8mb4 NOT NULL DEFAULT '';

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Roles` ADD `Scope` int NOT NULL DEFAULT 0;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `RefreshTokens` ADD `ImpersonatedCompanyId` int NULL;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Companies` ADD `AccountEmail` varchar(150) CHARACTER SET utf8mb4 NOT NULL DEFAULT '';

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Companies` ADD `Notes` varchar(500) CHARACTER SET utf8mb4 NULL;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Companies` ADD `Status` int NOT NULL DEFAULT 0;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE TABLE `Plans` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(60) CHARACTER SET utf8mb4 NOT NULL,
        `Description` varchar(300) CHARACTER SET utf8mb4 NULL,
        `Price` decimal(18,2) NOT NULL,
        `Currency` varchar(3) CHARACTER SET utf8mb4 NOT NULL,
        `BillingPeriodMonths` int NOT NULL,
        `MaxUsers` int NOT NULL,
        `IsActive` tinyint(1) NOT NULL,
        `SortOrder` int NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_Plans` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE TABLE `Subscriptions` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `CompanyId` int NOT NULL,
        `PlanId` int NOT NULL,
        `StartDate` datetime(6) NOT NULL,
        `EndDate` datetime(6) NOT NULL,
        `Status` int NOT NULL,
        `Price` decimal(18,2) NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_Subscriptions` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Subscriptions_Companies_CompanyId` FOREIGN KEY (`CompanyId`) REFERENCES `Companies` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_Subscriptions_Plans_PlanId` FOREIGN KEY (`PlanId`) REFERENCES `Plans` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE TABLE `SubscriptionPayments` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `SubscriptionId` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `Amount` decimal(18,2) NOT NULL,
        `Method` varchar(40) CHARACTER SET utf8mb4 NOT NULL,
        `Reference` varchar(80) CHARACTER SET utf8mb4 NULL,
        `PeriodFrom` datetime(6) NOT NULL,
        `PeriodTo` datetime(6) NOT NULL,
        `Notes` varchar(250) CHARACTER SET utf8mb4 NULL,
        `UserId` int NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_SubscriptionPayments` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_SubscriptionPayments_Subscriptions_SubscriptionId` FOREIGN KEY (`SubscriptionId`) REFERENCES `Subscriptions` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_SubscriptionPayments_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE INDEX `IX_Users_CompanyId` ON `Users` (`CompanyId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE UNIQUE INDEX `IX_Users_Email` ON `Users` (`Email`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE UNIQUE INDEX `IX_Roles_Key` ON `Roles` (`Key`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE UNIQUE INDEX `IX_Companies_AccountEmail` ON `Companies` (`AccountEmail`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE UNIQUE INDEX `IX_Plans_Name` ON `Plans` (`Name`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE INDEX `IX_SubscriptionPayments_SubscriptionId` ON `SubscriptionPayments` (`SubscriptionId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE INDEX `IX_SubscriptionPayments_UserId` ON `SubscriptionPayments` (`UserId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE INDEX `IX_Subscriptions_CompanyId` ON `Subscriptions` (`CompanyId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE INDEX `IX_Subscriptions_PlanId` ON `Subscriptions` (`PlanId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    CREATE INDEX `IX_Subscriptions_Status_EndDate` ON `Subscriptions` (`Status`, `EndDate`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    ALTER TABLE `Users` ADD CONSTRAINT `FK_Users_Companies_CompanyId` FOREIGN KEY (`CompanyId`) REFERENCES `Companies` (`Id`) ON DELETE RESTRICT;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260907140508_SaasPlatformAndRoleScopes') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260907140508_SaasPlatformAndRoleScopes', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `FinanceCategories` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(80) CHARACTER SET utf8mb4 NOT NULL,
        `Kind` int NOT NULL,
        `Description` varchar(250) CHARACTER SET utf8mb4 NULL,
        `IsSystem` tinyint(1) NOT NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_FinanceCategories` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `Recipes` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Name` varchar(120) CHARACTER SET utf8mb4 NOT NULL,
        `ProductId` int NOT NULL,
        `OutputQuantity` decimal(18,4) NOT NULL,
        `LaborCost` decimal(18,2) NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `IsActive` tinyint(1) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Recipes` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Recipes_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `RepairOrders` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Number` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `CustomerId` int NOT NULL,
        `ItemDescription` varchar(200) CHARACTER SET utf8mb4 NOT NULL,
        `ReportedIssue` varchar(1000) CHARACTER SET utf8mb4 NULL,
        `Diagnosis` varchar(1000) CHARACTER SET utf8mb4 NULL,
        `Status` int NOT NULL,
        `ReceivedAt` datetime(6) NOT NULL,
        `PromisedAt` datetime(6) NULL,
        `CompletedAt` datetime(6) NULL,
        `DeliveredAt` datetime(6) NULL,
        `LaborCost` decimal(18,2) NOT NULL,
        `MaterialsCost` decimal(18,2) NOT NULL,
        `TaxRate` decimal(18,2) NOT NULL,
        `TaxAmount` decimal(18,2) NOT NULL,
        `Total` decimal(18,2) NOT NULL,
        `PaymentTerm` int NOT NULL,
        `CreditDays` int NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_RepairOrders` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RepairOrders_Customers_CustomerId` FOREIGN KEY (`CustomerId`) REFERENCES `Customers` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `Sales` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Number` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `CustomerId` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `Status` int NOT NULL,
        `PaymentTerm` int NOT NULL,
        `CreditDays` int NOT NULL,
        `Subtotal` decimal(18,2) NOT NULL,
        `DiscountAmount` decimal(18,2) NOT NULL,
        `TaxAmount` decimal(18,2) NOT NULL,
        `Total` decimal(18,2) NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_Sales` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Sales_Customers_CustomerId` FOREIGN KEY (`CustomerId`) REFERENCES `Customers` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `FinanceEntries` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Kind` int NOT NULL,
        `CategoryId` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `Amount` decimal(18,2) NOT NULL,
        `Description` varchar(200) CHARACTER SET utf8mb4 NOT NULL,
        `Method` varchar(40) CHARACTER SET utf8mb4 NOT NULL,
        `Reference` varchar(80) CHARACTER SET utf8mb4 NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `Source` int NOT NULL,
        `SourceId` int NULL,
        `UserId` int NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_FinanceEntries` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_FinanceEntries_FinanceCategories_CategoryId` FOREIGN KEY (`CategoryId`) REFERENCES `FinanceCategories` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_FinanceEntries_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `ProductionOrders` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Number` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `ProductId` int NOT NULL,
        `RecipeId` int NULL,
        `Quantity` decimal(18,4) NOT NULL,
        `ProducedQuantity` decimal(18,4) NOT NULL,
        `Status` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `StartedAt` datetime(6) NULL,
        `CompletedAt` datetime(6) NULL,
        `LaborCost` decimal(18,2) NOT NULL,
        `MaterialsCost` decimal(18,2) NOT NULL,
        `TotalCost` decimal(18,2) NOT NULL,
        `UnitCost` decimal(18,2) NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_ProductionOrders` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_ProductionOrders_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_ProductionOrders_Recipes_RecipeId` FOREIGN KEY (`RecipeId`) REFERENCES `Recipes` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `RecipeItems` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `RecipeId` int NOT NULL,
        `ProductId` int NOT NULL,
        `Quantity` decimal(18,4) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_RecipeItems` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RecipeItems_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_RecipeItems_Recipes_RecipeId` FOREIGN KEY (`RecipeId`) REFERENCES `Recipes` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `RepairMaterials` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `RepairOrderId` int NOT NULL,
        `ProductId` int NOT NULL,
        `Quantity` decimal(18,4) NOT NULL,
        `UnitPrice` decimal(18,2) NOT NULL,
        `Subtotal` decimal(18,2) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_RepairMaterials` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RepairMaterials_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_RepairMaterials_RepairOrders_RepairOrderId` FOREIGN KEY (`RepairOrderId`) REFERENCES `RepairOrders` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `AccountsReceivable` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `CustomerId` int NOT NULL,
        `SaleId` int NULL,
        `RepairOrderId` int NULL,
        `DocumentNumber` varchar(60) CHARACTER SET utf8mb4 NOT NULL,
        `IssueDate` datetime(6) NOT NULL,
        `DueDate` datetime(6) NOT NULL,
        `Total` decimal(18,2) NOT NULL,
        `CollectedAmount` decimal(18,2) NOT NULL,
        `Balance` decimal(18,2) NOT NULL,
        `Status` int NOT NULL,
        `Notes` varchar(500) CHARACTER SET utf8mb4 NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_AccountsReceivable` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_AccountsReceivable_Customers_CustomerId` FOREIGN KEY (`CustomerId`) REFERENCES `Customers` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_AccountsReceivable_RepairOrders_RepairOrderId` FOREIGN KEY (`RepairOrderId`) REFERENCES `RepairOrders` (`Id`) ON DELETE SET NULL,
        CONSTRAINT `FK_AccountsReceivable_Sales_SaleId` FOREIGN KEY (`SaleId`) REFERENCES `Sales` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `SaleItems` (
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
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_SaleItems` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_SaleItems_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_SaleItems_Sales_SaleId` FOREIGN KEY (`SaleId`) REFERENCES `Sales` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `ProductionMaterials` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `ProductionOrderId` int NOT NULL,
        `ProductId` int NOT NULL,
        `PlannedQuantity` decimal(18,4) NOT NULL,
        `ConsumedQuantity` decimal(18,4) NOT NULL,
        `UnitCost` decimal(18,2) NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_ProductionMaterials` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_ProductionMaterials_ProductionOrders_ProductionOrderId` FOREIGN KEY (`ProductionOrderId`) REFERENCES `ProductionOrders` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_ProductionMaterials_Products_ProductId` FOREIGN KEY (`ProductId`) REFERENCES `Products` (`Id`) ON DELETE RESTRICT
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE TABLE `Receipts` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `AccountReceivableId` int NOT NULL,
        `Date` datetime(6) NOT NULL,
        `Amount` decimal(18,2) NOT NULL,
        `Method` varchar(40) CHARACTER SET utf8mb4 NOT NULL,
        `Reference` varchar(80) CHARACTER SET utf8mb4 NULL,
        `Notes` varchar(250) CHARACTER SET utf8mb4 NULL,
        `UserId` int NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        CONSTRAINT `PK_Receipts` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Receipts_AccountsReceivable_AccountReceivableId` FOREIGN KEY (`AccountReceivableId`) REFERENCES `AccountsReceivable` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_Receipts_Users_UserId` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_AccountsReceivable_CompanyId_Status_DueDate` ON `AccountsReceivable` (`CompanyId`, `Status`, `DueDate`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_AccountsReceivable_CustomerId` ON `AccountsReceivable` (`CustomerId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_AccountsReceivable_RepairOrderId` ON `AccountsReceivable` (`RepairOrderId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_AccountsReceivable_SaleId` ON `AccountsReceivable` (`SaleId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE UNIQUE INDEX `IX_FinanceCategories_CompanyId_Kind_Name` ON `FinanceCategories` (`CompanyId`, `Kind`, `Name`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_FinanceEntries_CategoryId` ON `FinanceEntries` (`CategoryId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_FinanceEntries_CompanyId_Kind_Date` ON `FinanceEntries` (`CompanyId`, `Kind`, `Date`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_FinanceEntries_CompanyId_Source_SourceId` ON `FinanceEntries` (`CompanyId`, `Source`, `SourceId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_FinanceEntries_UserId` ON `FinanceEntries` (`UserId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_ProductionMaterials_ProductId` ON `ProductionMaterials` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_ProductionMaterials_ProductionOrderId` ON `ProductionMaterials` (`ProductionOrderId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE UNIQUE INDEX `IX_ProductionOrders_CompanyId_Number` ON `ProductionOrders` (`CompanyId`, `Number`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_ProductionOrders_CompanyId_Status_Date` ON `ProductionOrders` (`CompanyId`, `Status`, `Date`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_ProductionOrders_ProductId` ON `ProductionOrders` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_ProductionOrders_RecipeId` ON `ProductionOrders` (`RecipeId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_Receipts_AccountReceivableId` ON `Receipts` (`AccountReceivableId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_Receipts_UserId` ON `Receipts` (`UserId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_RecipeItems_ProductId` ON `RecipeItems` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_RecipeItems_RecipeId` ON `RecipeItems` (`RecipeId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_Recipes_CompanyId_ProductId` ON `Recipes` (`CompanyId`, `ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_Recipes_ProductId` ON `Recipes` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_RepairMaterials_ProductId` ON `RepairMaterials` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_RepairMaterials_RepairOrderId` ON `RepairMaterials` (`RepairOrderId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE UNIQUE INDEX `IX_RepairOrders_CompanyId_Number` ON `RepairOrders` (`CompanyId`, `Number`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_RepairOrders_CompanyId_Status_ReceivedAt` ON `RepairOrders` (`CompanyId`, `Status`, `ReceivedAt`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_RepairOrders_CustomerId` ON `RepairOrders` (`CustomerId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_SaleItems_ProductId` ON `SaleItems` (`ProductId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_SaleItems_SaleId` ON `SaleItems` (`SaleId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE UNIQUE INDEX `IX_Sales_CompanyId_Number` ON `Sales` (`CompanyId`, `Number`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_Sales_CompanyId_Status_Date` ON `Sales` (`CompanyId`, `Status`, `Date`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    CREATE INDEX `IX_Sales_CustomerId` ON `Sales` (`CustomerId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918193603_VentasProduccionReparacionesFinanzas') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260918193603_VentasProduccionReparacionesFinanzas', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918204148_CondicionDePagoEnCompras') THEN

    ALTER TABLE `Purchases` ADD `CreditDays` int NOT NULL DEFAULT 0;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918204148_CondicionDePagoEnCompras') THEN

    ALTER TABLE `Purchases` ADD `PaymentTerm` int NOT NULL DEFAULT 0;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918204148_CondicionDePagoEnCompras') THEN


                    UPDATE Purchases p
                    JOIN Suppliers s ON s.Id = p.SupplierId
                    SET p.PaymentTerm = s.PaymentTerm,
                        p.CreditDays  = CASE WHEN s.PaymentTerm = 1 THEN s.CreditDays ELSE 0 END;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260918204148_CondicionDePagoEnCompras') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260918204148_CondicionDePagoEnCompras', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260921132447_RecorridoGuiado') THEN

    ALTER TABLE `Users` ADD `TourCompletedAt` datetime(6) NULL;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260921132447_RecorridoGuiado') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260921132447_RecorridoGuiado', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

START TRANSACTION;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    ALTER TABLE `Companies` ADD `InboundToken` varchar(20) CHARACTER SET utf8mb4 NULL;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    CREATE TABLE `ReceivedDocuments` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Key` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
        `DocumentType` varchar(40) CHARACTER SET utf8mb4 NOT NULL,
        `Consecutive` varchar(20) CHARACTER SET utf8mb4 NOT NULL,
        `IssuerTaxId` varchar(30) CHARACTER SET utf8mb4 NOT NULL,
        `IssuerName` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
        `IssuerEmail` varchar(150) CHARACTER SET utf8mb4 NULL,
        `IssueDate` datetime(6) NOT NULL,
        `Currency` varchar(3) CHARACTER SET utf8mb4 NOT NULL,
        `IsCredit` tinyint(1) NOT NULL,
        `CreditDays` int NOT NULL,
        `Subtotal` decimal(18,2) NOT NULL,
        `TaxAmount` decimal(18,2) NOT NULL,
        `Total` decimal(18,2) NOT NULL,
        `Source` int NOT NULL,
        `SenderEmail` varchar(150) CHARACTER SET utf8mb4 NULL,
        `ReceivedAt` datetime(6) NOT NULL,
        `Status` int NOT NULL,
        `PurchaseId` int NULL,
        `LinesJson` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Xml` longtext CHARACTER SET utf8mb4 NOT NULL,
        `CreatedAt` datetime(6) NOT NULL,
        `CreatedByUserId` int NULL,
        `UpdatedAt` datetime(6) NULL,
        `UpdatedByUserId` int NULL,
        `CompanyId` int NOT NULL,
        CONSTRAINT `PK_ReceivedDocuments` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_ReceivedDocuments_Purchases_PurchaseId` FOREIGN KEY (`PurchaseId`) REFERENCES `Purchases` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    CREATE UNIQUE INDEX `IX_Companies_InboundToken` ON `Companies` (`InboundToken`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    CREATE UNIQUE INDEX `IX_ReceivedDocuments_CompanyId_Key` ON `ReceivedDocuments` (`CompanyId`, `Key`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    CREATE INDEX `IX_ReceivedDocuments_CompanyId_Status_IssueDate` ON `ReceivedDocuments` (`CompanyId`, `Status`, `IssueDate`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    CREATE INDEX `IX_ReceivedDocuments_PurchaseId` ON `ReceivedDocuments` (`PurchaseId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20261020023420_FacturasRecibidas') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20261020023420_FacturasRecibidas', '8.0.13');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

