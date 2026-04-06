<?php
namespace RoleModel\Cli\Commands;

use RoleModel\Cli\BaseCommand;

/**
 * bx:role — управляет каталогом должностей и ролей доступа.
 *
 * Использование:
 *   bx bx:role                   — список всех должностей и прикреплённых ролей
 *   bx bx:role install           — обход CSV файла и обновление таблицы roles
 *   bx bx:role generator         — генерация прав доступа через GigaChat по должности и отделу
 *
 * Цель: каталог доступов по ролям (должностям).
 * Доступы выдаются не людям, а ролям — администратор назначает роль, система выдаёт права.
 * Поддерживает аудит: кто имеет доступ к чему.
 *
 * Переменные окружения:
 *   GIGACHAT_CREDENTIALS  — Base64-encoded токен GigaChat
 *   GIGACHAT_SCOPE        — область видимости API (по умолчанию: GIGACHAT_API_PERS)
 *   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD — данные PostgreSQL
 */
class RoleTableCommand extends BaseCommand
{
    /** Путь к CSV с должностями */
    private string $csvPath;

    /** Путь к CSV с шаблоном прав доступа */
    private string $checkRolesCsvPath;

    public function __construct()
    {
        $base = __DIR__ . '/../../data/';
        $this->csvPath           = $base . 'roles-list.csv';
        $this->checkRolesCsvPath = $base . 'check-roles.csv';
    }

    public function handle(array $args): int
    {
        $subcommand = $args[0] ?? '';

        return match ($subcommand) {
            'install'   => $this->installRoles(),
            'generator' => $this->generateRoles(),
            default     => $this->listRoles(),
        };
    }

    /**
     * Выводит список всех должностей и прикреплённых ролей (только названия)
     */
    private function listRoles(): int
    {
        $this->info("Каталог должностей и ролей доступа:");

        $conn = $this->getDbConnection();
        if (!$conn) {
            return 1;
        }

        $this->ensureRolesTable($conn);

        $result = pg_query($conn, "
            SELECT r.name AS role_name, r.department, r.description,
                   COUNT(ra.id) AS access_count
            FROM b_roles r
            LEFT JOIN b_role_access ra ON ra.role_id = r.id
            GROUP BY r.id, r.name, r.department, r.description
            ORDER BY r.department, r.name
        ");

        if (!$result) {
            $this->error("Ошибка запроса: " . pg_last_error($conn));
            pg_close($conn);
            return 1;
        }

        $rows = [];
        while ($row = pg_fetch_assoc($result)) {
            $rows[] = $row;
        }
        pg_close($conn);

        if (empty($rows)) {
            echo json_encode([
                'status'  => 'ok',
                'message' => 'Должности не найдены. Выполните: bx bx:role install',
                'roles'   => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            return 0;
        }

        echo json_encode([
            'status' => 'ok',
            'total'  => count($rows),
            'roles'  => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return 0;
    }

    /**
     * Читает CSV файл с должностями и обновляет таблицу b_roles
     * Добавляет только новые записи — существующие не удаляет.
     */
    private function installRoles(): int
    {
        $this->info("Загрузка должностей из CSV: {$this->csvPath}");

        if (!file_exists($this->csvPath)) {
            $this->error("CSV файл не найден: {$this->csvPath}");
            return 1;
        }

        $conn = $this->getDbConnection();
        if (!$conn) {
            return 1;
        }

        $this->ensureRolesTable($conn);

        // Читаем CSV
        $handle = fopen($this->csvPath, 'r');
        if (!$handle) {
            $this->error("Не удалось открыть файл: {$this->csvPath}");
            pg_close($conn);
            return 1;
        }

        $header  = fgetcsv($handle); // пропускаем заголовок
        $added   = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;

            $name        = trim($row[0]);
            $profile     = trim($row[1] ?? '');
            $responsible = trim($row[2] ?? '');

            // Проверяем, существует ли уже эта роль
            $existing = pg_query_params(
                $conn,
                "SELECT id FROM b_roles WHERE name = $1",
                [$name]
            );

            if (pg_num_rows($existing) > 0) {
                $skipped++;
                continue;
            }

            // Добавляем новую роль
            pg_query_params(
                $conn,
                "INSERT INTO b_roles (name, description, department, created_at) VALUES ($1, $2, $3, NOW())",
                [$name, $profile, $responsible]
            );

            if (pg_last_error($conn)) {
                $this->warn("  Ошибка добавления '{$name}': " . pg_last_error($conn));
            } else {
                $this->info("  + Добавлена: {$name}");
                $added++;
            }
        }

        fclose($handle);
        pg_close($conn);

        $this->success("Загрузка завершена. Добавлено: {$added}, пропущено (уже существуют): {$skipped}");
        return 0;
    }

    /**
     * Генерирует список прав доступа через GigaChat по должности и отделу.
     * Использует check-roles.csv как пример формата.
     * Тестирует 10 должностей.
     */
    private function generateRoles(): int
    {
        $this->info("Генерация прав доступа через GigaChat...");

        $credentials = getenv('GIGACHAT_CREDENTIALS') ?: '';
        $scope       = getenv('GIGACHAT_SCOPE') ?: 'GIGACHAT_API_PERS';

        // Читаем шаблон опций из check-roles.csv
        $checkOptions = $this->loadCheckRolesOptions();
        if (empty($checkOptions)) {
            $this->error("Не удалось загрузить шаблон опций из: {$this->checkRolesCsvPath}");
            return 1;
        }

        // Тестовые должности (10 штук с отделами)
        $testPositions = [
            ['position' => 'Менеджер по продажам',    'department' => 'Отдел продаж'],
            ['position' => 'Бухгалтер',               'department' => 'Бухгалтерия'],
            ['position' => 'Системный администратор', 'department' => 'ИТ-отдел'],
            ['position' => 'Менеджер по закупкам',    'department' => 'Отдел снабжения'],
            ['position' => 'Руководитель отдела продаж', 'department' => 'Отдел продаж'],
            ['position' => 'HR-менеджер',             'department' => 'Отдел кадров'],
            ['position' => 'Финансовый директор',     'department' => 'Финансовый отдел'],
            ['position' => 'Менеджер проекта',        'department' => 'Проектный отдел'],
            ['position' => 'Складской работник',      'department' => 'Склад'],
            ['position' => 'Генеральный директор',    'department' => 'Руководство'],
        ];

        $results = [];

        foreach ($testPositions as $entry) {
            $position   = $entry['position'];
            $department = $entry['department'];

            $this->info("  Генерирую права для: {$position} ({$department})");

            if ($credentials) {
                $accessMap = $this->generateWithGigaChat($credentials, $scope, $position, $department, $checkOptions);
            } else {
                // Фоллбэк: используем предустановленные правила из check-roles.csv
                $accessMap = $this->getPresetAccess($checkOptions, $position);
                $this->warn("  GIGACHAT_CREDENTIALS не задан — используем CSV-шаблон для '{$position}'");
            }

            $results[] = [
                'position'   => $position,
                'department' => $department,
                'access'     => $accessMap,
            ];
        }

        // Сохраняем результат в БД
        $conn = $this->getDbConnection();
        if ($conn) {
            $this->ensureRolesTable($conn);
            $this->ensureRoleAccessTable($conn);

            foreach ($results as $entry) {
                // Найти или создать роль
                $roleResult = pg_query_params(
                    $conn,
                    "INSERT INTO b_roles (name, department, created_at) VALUES ($1, $2, NOW())
                     ON CONFLICT (name) DO UPDATE SET department = EXCLUDED.department
                     RETURNING id",
                    [$entry['position'], $entry['department']]
                );

                if (!$roleResult || pg_num_rows($roleResult) === 0) continue;
                $roleRow = pg_fetch_assoc($roleResult);
                $roleId  = $roleRow['id'];

                // Удаляем старые права и вставляем новые
                pg_query_params($conn, "DELETE FROM b_role_access WHERE role_id = $1", [$roleId]);

                foreach ($entry['access'] as $accessName => $granted) {
                    pg_query_params(
                        $conn,
                        "INSERT INTO b_role_access (role_id, access_name, is_granted) VALUES ($1, $2, $3)",
                        [$roleId, $accessName, $granted ? 'true' : 'false']
                    );
                }
            }

            pg_close($conn);
            $this->success("Права доступа сохранены в PostgreSQL.");
        }

        echo json_encode([
            'status'  => 'ok',
            'total'   => count($results),
            'results' => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        return 0;
    }

    /**
     * Генерирует права через GigaChat API
     */
    private function generateWithGigaChat(
        string $credentials,
        string $scope,
        string $position,
        string $department,
        array $checkOptions
    ): array {
        $optionsList = implode(', ', $checkOptions);
        $prompt = "Ты администратор доступов корпоративной системы.
Для сотрудника с должностью '{$position}' в отделе '{$department}' определи права доступа.
Доступные системы: {$optionsList}.
Для каждой системы ответь true (доступ нужен) или false (доступ не нужен).
Учитывай специфику должности и отдела. Расставь права логично.
Ответ дай строго в формате JSON: {\"system_name\": true/false, ...}
Только JSON, без пояснений.";

        // Получаем токен GigaChat
        $token = $this->getGigaChatToken($credentials, $scope);
        if (!$token) {
            $this->warn("  Не удалось получить токен GigaChat — используем шаблон.");
            return $this->getPresetAccess($checkOptions, $position);
        }

        $requestBody = json_encode([
            'model'    => 'GigaChat',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
                'content'       => $requestBody,
                'timeout'       => 30,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false],
        ]);

        $response = @file_get_contents('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', false, $ctx);

        if ($response === false) {
            $this->warn("  GigaChat API недоступен — используем шаблон.");
            return $this->getPresetAccess($checkOptions, $position);
        }

        $data    = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Парсим JSON из ответа
        if (preg_match('/\{[^}]+\}/s', $content, $m)) {
            $accessMap = json_decode($m[0], true);
            if (is_array($accessMap)) {
                // Нормализуем ключи и значения
                $normalized = [];
                foreach ($checkOptions as $opt) {
                    $normalized[$opt] = (bool)($accessMap[$opt] ?? false);
                }
                return $normalized;
            }
        }

        $this->warn("  Не удалось распарсить ответ GigaChat — используем шаблон.");
        return $this->getPresetAccess($checkOptions, $position);
    }

    /**
     * Получает OAuth-токен GigaChat
     */
    private function getGigaChatToken(string $credentials, string $scope): ?string
    {
        $rquid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Authorization: Basic {$credentials}\r\nRqUID: {$rquid}\r\nContent-Type: application/x-www-form-urlencoded\r\n",
                'content'       => "scope={$scope}",
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false],
        ]);

        $response = @file_get_contents('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', false, $ctx);
        if ($response === false) return null;

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    /**
     * Возвращает предустановленные права из check-roles.csv по должности
     */
    private function getPresetAccess(array $options, string $position): array
    {
        if (!file_exists($this->checkRolesCsvPath)) {
            return array_fill_keys($options, false);
        }

        $handle = fopen($this->checkRolesCsvPath, 'r');
        if (!$handle) return array_fill_keys($options, false);

        $header = fgetcsv($handle);
        // Первые 2 колонки: position, department — остальные — опции доступа
        $accessColumns = array_slice($header, 2);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;
            if (trim($row[0]) === $position) {
                $values = array_slice($row, 2);
                $map    = [];
                foreach ($accessColumns as $i => $col) {
                    $map[$col] = strtolower(trim($values[$i] ?? 'false')) === 'true';
                }
                fclose($handle);
                return $map;
            }
        }

        fclose($handle);

        // Если должность не найдена — возвращаем минимум
        return array_fill_keys($accessColumns, false);
    }

    /**
     * Читает список опций доступа из заголовка check-roles.csv
     */
    private function loadCheckRolesOptions(): array
    {
        if (!file_exists($this->checkRolesCsvPath)) {
            return [];
        }

        $handle = fopen($this->checkRolesCsvPath, 'r');
        if (!$handle) return [];

        $header = fgetcsv($handle);
        fclose($handle);

        // Пропускаем первые 2 колонки (position, department)
        return array_slice($header ?? [], 2);
    }

    /**
     * Создаёт таблицу ролей, если не существует
     */
    private function ensureRolesTable($conn): void
    {
        pg_query($conn, "
            CREATE TABLE IF NOT EXISTS b_roles (
                id          SERIAL PRIMARY KEY,
                name        VARCHAR(200) NOT NULL UNIQUE,
                description TEXT,
                department  VARCHAR(200),
                created_at  TIMESTAMP NOT NULL DEFAULT NOW()
            )
        ");
    }

    /**
     * Создаёт таблицу прав доступа по ролям
     */
    private function ensureRoleAccessTable($conn): void
    {
        pg_query($conn, "
            CREATE TABLE IF NOT EXISTS b_role_access (
                id          SERIAL PRIMARY KEY,
                role_id     INTEGER NOT NULL REFERENCES b_roles(id) ON DELETE CASCADE,
                access_name VARCHAR(100) NOT NULL,
                is_granted  BOOLEAN NOT NULL DEFAULT FALSE,
                UNIQUE (role_id, access_name)
            )
        ");
    }

    /**
     * Возвращает подключение к PostgreSQL
     */
    private function getDbConnection()
    {
        $settingsFile = ($_SERVER["DOCUMENT_ROOT"] ?? '') . '/bitrix/.settings.php';
        if (file_exists($settingsFile)) {
            $settings = include $settingsFile;
            $db = $settings['connections']['value']['default'] ?? null;
            if ($db) {
                $conn = pg_connect(
                    "host={$db['host']} dbname={$db['database']} user={$db['login']} password={$db['password']}"
                );
                if ($conn) return $conn;
            }
        }

        $host     = getenv('DB_HOST')     ?: 'bitrix-db';
        $dbname   = getenv('DB_NAME')     ?: 'bitrix';
        $user     = getenv('DB_USER')     ?: 'bitrix';
        $password = getenv('DB_PASSWORD') ?: 'bitrix';

        $conn = pg_connect("host={$host} dbname={$dbname} user={$user} password={$password}");
        if (!$conn) {
            $this->error("Не удалось подключиться к PostgreSQL.");
            return null;
        }

        return $conn;
    }
}
