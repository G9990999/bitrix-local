<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:cache-clear — очищает кеш Битрикс через CLI.
 */
class CacheClearCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $this->info("Очистка кеша Битрикс...");

        $root = $_SERVER["DOCUMENT_ROOT"];

        // 1. Clear managed cache
        $cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
        $cache->cleanAll();
        $this->success("Managed cache очищен.");

        // 2. Clear /bitrix/cache directory
        $cacheDir = $root . "/bitrix/cache";
        if (is_dir($cacheDir)) {
            $this->deleteDir($cacheDir);
            $this->success("Директория /bitrix/cache очищена.");
        }

        // 3. Clear /bitrix/stack_cache directory
        $stackCacheDir = $root . "/bitrix/stack_cache";
        if (is_dir($stackCacheDir)) {
            $this->deleteDir($stackCacheDir);
            $this->success("Директория /bitrix/stack_cache очищена.");
        }

        $this->success("Кеш Битрикс успешно очищен.");
        return 0;
    }

    private function deleteDir(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item) : unlink($item);
        }
    }
}
