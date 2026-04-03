<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('local_snipeit')) {
    return;
}

/**
 * Bitrix Module installer for local.snipeit (Snipe-IT Asset Management).
 *
 * Naming convention: underscores replace dots in class name.
 */
class local_snipeit extends CModule
{
    public string $MODULE_ID          = 'local.snipeit';
    public string $MODULE_VERSION;
    public string $MODULE_VERSION_DATE;
    public string $MODULE_NAME;
    public string $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = Loc::getMessage('LOCAL_SNIPEIT_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = Loc::getMessage('LOCAL_SNIPEIT_MODULE_DESCRIPTION');
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        if (!$this->InstallDB()) {
            $APPLICATION->ThrowException(Loc::getMessage('LOCAL_SNIPEIT_INSTALL_DB_ERROR'));
            return false;
        }

        $this->InstallFiles();
        ModuleManager::registerModule($this->MODULE_ID);
        return true;
    }

    public function DoUninstall(): bool
    {
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

    public function InstallDB(): bool
    {
        global $DB;

        $sqlFile = __DIR__ . '/db/install.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }

        $DB->RunSQLBatch($sqlFile);
        return true;
    }

    public function UnInstallDB(): bool
    {
        global $DB;

        $sqlFile = __DIR__ . '/db/uninstall.sql';
        if (file_exists($sqlFile)) {
            $DB->RunSQLBatch($sqlFile);
        }
        return true;
    }

    public function InstallFiles(): bool
    {
        // Copy admin menu items
        CopyDirFiles(
            __DIR__ . '/../admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );
        return true;
    }

    public function UnInstallFiles(): bool
    {
        @unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/snipeit_assets.php');
        @unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/snipeit_licenses.php');
        return true;
    }
}
