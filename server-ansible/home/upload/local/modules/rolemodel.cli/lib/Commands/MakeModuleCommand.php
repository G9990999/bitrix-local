<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:make-module <vendor.name> — генерирует структуру нового модуля в local/modules/.
 */
class MakeModuleCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $moduleName = $args[0] ?? null;
        if (!$moduleName || !preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $moduleName)) {
            $this->error("Укажите имя модуля в формате vendor.name (например: rolemodel.crm)");
            return 1;
        }

        $root      = $_SERVER["DOCUMENT_ROOT"];
        $moduleDir = "{$root}/local/modules/{$moduleName}";

        if (is_dir($moduleDir)) {
            $this->error("Модуль уже существует: {$moduleDir}");
            return 1;
        }

        $dirs = [
            $moduleDir,
            "{$moduleDir}/install",
            "{$moduleDir}/lib",
            "{$moduleDir}/lang/ru",
        ];

        foreach ($dirs as $dir) {
            mkdir($dir, 0755, true);
        }

        // Generate install/index.php
        file_put_contents("{$moduleDir}/install/index.php", $this->installTemplate($moduleName));

        // Generate .settings.php stub
        file_put_contents("{$moduleDir}/.settings.php", "<?php\nreturn [];\n");

        $this->success("Модуль создан: {$moduleDir}");
        $this->info("Для регистрации выполните: php cli.php bx:init");
        return 0;
    }

    private function installTemplate(string $name): string
    {
        [$vendor, $mod] = explode('.', $name, 2);
        $class = ucfirst($vendor) . '_' . ucfirst($mod);
        return <<<PHP
<?php
use Bitrix\\Main\\Localization\\Loc;
use Bitrix\\Main\\ModuleManager;

Loc::loadMessages(__FILE__);

class {$class} extends CModule
{
    public \$MODULE_ID = "{$name}";

    function DoInstall()
    {
        ModuleManager::registerModule(\$this->MODULE_ID);
        \$this->InstallFiles();
    }

    function DoUninstall()
    {
        \$this->UnInstallFiles();
        ModuleManager::unRegisterModule(\$this->MODULE_ID);
    }
}
PHP;
    }
}
