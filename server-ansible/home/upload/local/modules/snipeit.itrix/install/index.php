<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class snipeit_itrix extends CModule
{
    public string $MODULE_ID          = 'snipeit.itrix';
    public string $MODULE_VERSION;
    public string $MODULE_VERSION_DATE;
    public string $MODULE_NAME;
    public string $MODULE_DESCRIPTION;
    public string $MODULE_GROUP_RIGHTS = 'Y';
    public string $PARTNER_NAME       = 'Bitrix-Local';
    public string $PARTNER_URI        = 'https://github.com/G9990999/bitrix-local';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = Loc::getMessage('SNIPEIT_ITRIX_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = Loc::getMessage('SNIPEIT_ITRIX_MODULE_DESCRIPTION');
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        // Создаём таблицы через ORM
        $this->InstallDB();

        ModuleManager::registerModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('SNIPEIT_ITRIX_INSTALL_TITLE'),
            __DIR__ . '/step1.php'
        );

        return true;
    }

    public function DoUninstall(): bool
    {
        global $APPLICATION;

        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('SNIPEIT_ITRIX_UNINSTALL_TITLE'),
            __DIR__ . '/unstep1.php'
        );

        return true;
    }

    public function InstallDB(): void
    {
        \Bitrix\Main\Application::getConnection()->query("
            CREATE TABLE IF NOT EXISTS snipeit_assets (
                id              SERIAL PRIMARY KEY,
                name            VARCHAR(255) NOT NULL,
                asset_tag       VARCHAR(100) UNIQUE,
                serial          VARCHAR(100),
                model_id        INTEGER,
                purchase_date   DATE,
                purchase_cost   NUMERIC(12,2),
                order_number    VARCHAR(100),
                status          VARCHAR(30) NOT NULL DEFAULT 'deployable'
                                    CHECK (status IN ('deployable','pending','archived','deployed')),
                assigned_to     INTEGER,
                company_id      INTEGER,
                location_id     INTEGER,
                notes           TEXT,
                date_create     TIMESTAMP NOT NULL DEFAULT NOW(),
                date_modify     TIMESTAMP NOT NULL DEFAULT NOW(),
                active          CHAR(1) NOT NULL DEFAULT 'Y'
            )
        ");

        \Bitrix\Main\Application::getConnection()->query("
            CREATE TABLE IF NOT EXISTS snipeit_licenses (
                id              SERIAL PRIMARY KEY,
                name            VARCHAR(255) NOT NULL,
                product_key     VARCHAR(255),
                serial          VARCHAR(100),
                manufacturer_id INTEGER,
                purchase_date   DATE,
                purchase_cost   NUMERIC(12,2),
                seats           INTEGER NOT NULL DEFAULT 1,
                seats_used      INTEGER NOT NULL DEFAULT 0,
                license_email   VARCHAR(255),
                license_name    VARCHAR(255),
                notes           TEXT,
                date_create     TIMESTAMP NOT NULL DEFAULT NOW(),
                date_modify     TIMESTAMP NOT NULL DEFAULT NOW(),
                active          CHAR(1) NOT NULL DEFAULT 'Y'
            )
        ");

        \Bitrix\Main\Application::getConnection()->query("
            CREATE TABLE IF NOT EXISTS snipeit_user_assignments (
                id              SERIAL PRIMARY KEY,
                user_id         INTEGER NOT NULL,
                asset_id        INTEGER REFERENCES snipeit_assets(id) ON DELETE CASCADE,
                license_id      INTEGER REFERENCES snipeit_licenses(id) ON DELETE CASCADE,
                assigned_at     TIMESTAMP NOT NULL DEFAULT NOW(),
                returned_at     TIMESTAMP,
                notes           TEXT,
                CHECK (asset_id IS NOT NULL OR license_id IS NOT NULL)
            )
        ");
    }

    public function UnInstallDB(): void
    {
        $conn = \Bitrix\Main\Application::getConnection();
        $conn->query("DROP TABLE IF EXISTS snipeit_user_assignments");
        $conn->query("DROP TABLE IF EXISTS snipeit_assets");
        $conn->query("DROP TABLE IF EXISTS snipeit_licenses");
    }
}
