<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:deploy — деплой конфигов Nginx/Dex через Artisan-аналог.
 *
 * Перезагружает nginx внутри контейнера bitrix-nginx и dex.
 */
class DeployCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $this->info("Деплой конфигурации...");

        $services = [
            'bitrix-nginx' => 'docker exec bitrix-nginx nginx -s reload',
            'laravel-nginx' => 'docker exec laravel-nginx nginx -s reload',
            'dex'          => 'docker restart dex',
        ];

        foreach ($services as $name => $cmd) {
            $this->info("  Перезапуск {$name}...");
            exec($cmd . ' 2>&1', $output, $exitCode);
            if ($exitCode === 0) {
                $this->success("  {$name}: перезапущен.");
            } else {
                $this->warn("  {$name}: " . implode(' ', $output));
            }
            $output = [];
        }

        $this->success("Деплой завершён.");
        return 0;
    }
}
