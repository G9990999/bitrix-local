<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:health — проверяет доступность Dex, Gitea и ядра Битрикс.
 */
class HealthCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $this->info("Проверка работоспособности системы...\n");

        $dexIssuer  = getenv('OIDC_ISSUER') ?: 'http://localhost:5556/dex';
        $giteaUrl   = getenv('GITEA_URL')   ?: 'http://localhost:3000';

        $checks = [
            'Bitrix Core'  => [$this, 'checkBitrixCore'],
            'Database'     => [$this, 'checkDatabase'],
            'Dex OIDC'     => fn() => $this->checkHttp("{$dexIssuer}/.well-known/openid-configuration", 'Dex'),
            'Gitea'        => fn() => $this->checkHttp("{$giteaUrl}/api/healthz", 'Gitea'),
        ];

        $allOk = true;
        foreach ($checks as $name => $check) {
            $ok = $check();
            if (!$ok) {
                $allOk = false;
            }
        }

        echo "\n";
        if ($allOk) {
            $this->success("Все системы работают нормально.");
        } else {
            $this->warn("Некоторые компоненты недоступны. Проверьте логи.");
        }

        return $allOk ? 0 : 1;
    }

    private function checkBitrixCore(): bool
    {
        $root = $_SERVER["DOCUMENT_ROOT"] ?? '';
        $ok   = is_dir($root) && file_exists($root . "/bitrix/modules/main/include.php");
        $status = $ok ? "[OK]   " : "[FAIL] ";
        echo "  {$status} Bitrix Core\n";
        return $ok;
    }

    private function checkDatabase(): bool
    {
        try {
            $connection = \Bitrix\Main\Application::getConnection();
            $connection->query("SELECT 1");
            echo "  [OK]    Database\n";
            return true;
        } catch (\Exception $e) {
            echo "  [FAIL]  Database — " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function checkHttp(string $url, string $label): bool
    {
        $code = $this->httpGet($url);
        $ok   = ($code >= 200 && $code < 400);
        $status = $ok ? "[OK]   " : "[FAIL] ";
        echo "  {$status} {$label} (HTTP {$code})\n";
        return $ok;
    }
}
