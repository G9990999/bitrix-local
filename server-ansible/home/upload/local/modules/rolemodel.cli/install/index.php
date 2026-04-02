<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class rolemodel_cli extends CModule // В Битриксе имя класса обычно совпадает с ID модуля (нижний регистр)
{
    public $MODULE_ID           = "rolemodel.cli";
    public $MODULE_VERSION      = "1.0.0";
    public $MODULE_VERSION_DATE = "2026-03-26";
    public $MODULE_NAME         = "RoleModel CLI";
    public $MODULE_DESCRIPTION  = "Bitrix24 CLI tool — аналог Artisan для управления ядром из консоли.";
    public $PARTNER_NAME        = "RoleModel Team";

    function DoInstall()
    {
        // Регистрируем модуль в БД
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
    }

    function DoUninstall()
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    function InstallFiles()
    {
        // Здесь можно скопировать cli.php в /bitrix/php_interface/cli/rolemodel
        // Но для Docker-контейнера удобнее оставить его в local
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }
}
