-- ============================================================
-- Snipe-IT Bitrix-24 Module — Install SQL
-- ============================================================

CREATE TABLE IF NOT EXISTS `snipeit_assets` (
    `ID`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `NAME`            VARCHAR(255) DEFAULT NULL,
    `ASSET_TAG`       VARCHAR(255) DEFAULT NULL,
    `SERIAL`          VARCHAR(255) DEFAULT NULL,
    `MODEL_ID`        INT UNSIGNED DEFAULT NULL,
    `PURCHASE_DATE`   DATE         DEFAULT NULL,
    `PURCHASE_COST`   DECIMAL(10,2) DEFAULT NULL,
    `ORDER_NUMBER`    VARCHAR(255) DEFAULT NULL,
    `STATUS`          ENUM('deployable','pending','archived','undeployable') DEFAULT 'pending',
    `ASSIGNED_TO`     INT UNSIGNED DEFAULT NULL,
    `COMPANY_ID`      INT UNSIGNED DEFAULT NULL,
    `LOCATION_ID`     INT UNSIGNED DEFAULT NULL,
    `NOTES`           TEXT         DEFAULT NULL,
    `DATE_CREATE`     DATETIME     DEFAULT NULL,
    `DATE_MODIFY`     DATETIME     DEFAULT NULL,
    `ACTIVE`          CHAR(1)      NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`ID`),
    UNIQUE KEY `UNQ_ASSET_TAG` (`ASSET_TAG`),
    KEY `IDX_ASSIGNED_TO`  (`ASSIGNED_TO`),
    KEY `IDX_COMPANY_ID`   (`COMPANY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Snipe-IT assets';

CREATE TABLE IF NOT EXISTS `snipeit_licenses` (
    `ID`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `NAME`             VARCHAR(255) NOT NULL,
    `SERIAL`           TEXT         DEFAULT NULL,
    `SEATS`            INT UNSIGNED DEFAULT 1,
    `PURCHASE_DATE`    DATE         DEFAULT NULL,
    `PURCHASE_COST`    DECIMAL(10,2) DEFAULT NULL,
    `EXPIRATION_DATE`  DATE         DEFAULT NULL,
    `COMPANY_ID`       INT UNSIGNED DEFAULT NULL,
    `NOTES`            TEXT         DEFAULT NULL,
    `DATE_CREATE`      DATETIME     DEFAULT NULL,
    `DATE_MODIFY`      DATETIME     DEFAULT NULL,
    `ACTIVE`           CHAR(1)      NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`ID`),
    KEY `IDX_LICENSE_COMPANY` (`COMPANY_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Snipe-IT software licenses';

CREATE TABLE IF NOT EXISTS `snipeit_assignments` (
    `ID`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ASSET_ID`    INT UNSIGNED NOT NULL,
    `USER_ID`     INT UNSIGNED NOT NULL,
    `DATE_FROM`   DATETIME     DEFAULT NULL,
    `DATE_TO`     DATETIME     DEFAULT NULL,
    `NOTE`        TEXT         DEFAULT NULL,
    `ACTIVE`      CHAR(1)      NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`ID`),
    KEY `IDX_ASSIGNMENT_ASSET`  (`ASSET_ID`),
    KEY `IDX_ASSIGNMENT_USER`   (`USER_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Asset checkout history';

CREATE TABLE IF NOT EXISTS `snipeit_action_log` (
    `ID`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `USER_ID`      INT UNSIGNED DEFAULT NULL,
    `ACTION`       VARCHAR(100) NOT NULL,
    `TARGET_TYPE`  VARCHAR(50)  DEFAULT NULL,
    `TARGET_ID`    INT UNSIGNED DEFAULT NULL,
    `NOTE`         TEXT         DEFAULT NULL,
    `REMOTE_IP`    VARCHAR(40)  DEFAULT NULL,
    `DATE_CREATE`  DATETIME     DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `IDX_LOG_TARGET` (`TARGET_TYPE`, `TARGET_ID`),
    KEY `IDX_LOG_USER`   (`USER_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit log';
