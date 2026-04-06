<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:user-sync — синхронизирует пользователей из Dex (JWT claims) с таблицей b_user Битрикс.
 *
 * Использование / Usage:
 *   php cli.php bx:user-sync
 *
 * Требуется / Requires:
 *   OIDC_ISSUER env var pointing to Dex (e.g. http://localhost:5556/dex)
 *   BITRIX_WEBHOOK_URL env var for fetching CRM users
 */
class UserSyncCommand extends BaseCommand
{
    public function handle(array $args): int
    {
        $this->info("Синхронизация пользователей Dex → Битрикс...");

        $webhookUrl = getenv('BITRIX_WEBHOOK_URL');
        if (!$webhookUrl) {
            $this->error("BITRIX_WEBHOOK_URL не задан. Установите переменную окружения.");
            return 1;
        }

        // Fetch users from Bitrix via webhook
        $url      = rtrim($webhookUrl, '/') . '/user.get.json?FILTER[ACTIVE]=Y&SELECT[]=ID&SELECT[]=EMAIL&SELECT[]=NAME&SELECT[]=LAST_NAME';
        $response = @file_get_contents($url);
        if ($response === false) {
            $this->error("Не удалось получить пользователей через вебхук: {$url}");
            return 1;
        }

        $data = json_decode($response, true);
        if (!isset($data['result'])) {
            $this->error("Некорректный ответ вебхука: " . substr($response, 0, 200));
            return 1;
        }

        $users = $data['result'];
        $this->info(sprintf("Получено пользователей из CRM: %d", count($users)));

        $synced = 0;
        foreach ($users as $crmUser) {
            $email = $crmUser['EMAIL'] ?? null;
            if (!$email) continue;

            // Find or create user in Bitrix b_user by email
            $dbUser = \CUser::GetByLogin($email);
            if (!$dbUser->Fetch()) {
                // Create user
                $newUser = new \CUser();
                $fields = [
                    'NAME'        => $crmUser['NAME'] ?? '',
                    'LAST_NAME'   => $crmUser['LAST_NAME'] ?? '',
                    'LOGIN'       => $email,
                    'EMAIL'       => $email,
                    'PASSWORD'    => bin2hex(random_bytes(16)), // random, SSO only
                    'ACTIVE'      => 'Y',
                    'GROUP_ID'    => [2], // registered users group
                ];
                $userId = $newUser->Add($fields);
                if ($userId) {
                    $this->info("  Создан: {$email} (ID: {$userId})");
                    $synced++;
                } else {
                    $this->warn("  Ошибка создания: {$email} — " . $newUser->LAST_ERROR);
                }
            } else {
                $this->info("  Уже существует: {$email}");
            }
        }

        $this->success("Синхронизация завершена. Создано пользователей: {$synced}");
        return 0;
    }
}
