<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:make-controller <ControllerName> — генерирует REST-контроллер для React API.
 */
class MakeControllerCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            $this->error("Укажите имя контроллера (например: User, Role, Department)");
            return 1;
        }

        $root      = $_SERVER["DOCUMENT_ROOT"];
        $destDir   = "{$root}/local/modules/rolemodel.cli/lib/controllers";
        $destFile  = "{$destDir}/{$name}Controller.php";

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (file_exists($destFile)) {
            $this->error("Контроллер уже существует: {$destFile}");
            return 1;
        }

        file_put_contents($destFile, $this->controllerTemplate($name));
        $this->success("Контроллер создан: {$destFile}");
        return 0;
    }

    private function controllerTemplate(string $name): string
    {
        return <<<PHP
<?php
namespace RoleModel\\Cli\\Controllers;

use Bitrix\\Main\\Engine\\Controller;
use Bitrix\\Main\\Engine\\ActionFilter;

/**
 * {$name}Controller — REST API controller for React frontend.
 *
 * Endpoint: /api/rolemodel/{$name}/
 * Requires Authorization: Bearer <Dex JWT>
 */
class {$name}Controller extends Controller
{
    public function configureActions(): array
    {
        return [
            'list'   => ['prefilters' => [new ActionFilter\\Authentication()]],
            'get'    => ['prefilters' => [new ActionFilter\\Authentication()]],
            'create' => ['prefilters' => [new ActionFilter\\Authentication()]],
            'update' => ['prefilters' => [new ActionFilter\\Authentication()]],
            'delete' => ['prefilters' => [new ActionFilter\\Authentication()]],
        ];
    }

    public function listAction(): array
    {
        // TODO: return list of {$name} items
        return ['items' => []];
    }

    public function getAction(int \$id): array
    {
        // TODO: return single {$name} item by ID
        return ['item' => null];
    }

    public function createAction(array \$fields): array
    {
        // TODO: create a new {$name} item
        return ['id' => null];
    }

    public function updateAction(int \$id, array \$fields): array
    {
        // TODO: update {$name} item
        return ['success' => false];
    }

    public function deleteAction(int \$id): array
    {
        // TODO: delete {$name} item
        return ['success' => false];
    }
}
PHP;
    }
}
