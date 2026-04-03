<?php
/**
 * Module installer for role_model
 * Integrates the AI Role Access Prediction Model into 1C-Bitrix24.
 */

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class role_model extends CModule
{
    public $MODULE_ID = 'role_model';
    public $MODULE_NAME = 'AI Role Access Prediction Model';
    public $MODULE_DESCRIPTION = 'Predicts 1C access roles (checkboxes) for users by job title using scikit-learn + GigaChat';
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '2026-04-03';
    public $MODULE_VENDOR = 'Vendor';

    public function DoInstall(): bool
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallEvents();
        return true;
    }

    public function DoUninstall(): bool
    {
        $this->UnInstallEvents();
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

    public function InstallEvents(): void
    {
        // Register REST API handlers
        \Bitrix\Main\EventManager::getInstance()->registerEventHandlerCompatible(
            'rest', 'OnRestServiceBuildDescription',
            $this->MODULE_ID, '\Local\RoleModel\RestHandler', 'onBuildServiceDescription'
        );
    }

    public function UnInstallEvents(): void
    {
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'rest', 'OnRestServiceBuildDescription',
            $this->MODULE_ID, '\Local\RoleModel\RestHandler', 'onBuildServiceDescription'
        );
    }
}
