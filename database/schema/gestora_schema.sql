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

