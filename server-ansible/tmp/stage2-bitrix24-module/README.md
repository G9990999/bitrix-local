# Snipe-IT — Bitrix-24 Module (Stage 2)

Портирование функционала Snipe-IT в виде модуля Bitrix-24 (1C-Битрикс).

## Структура

```
stage2-bitrix24-module/
└── local.snipeit/          — корень модуля (module_id = local.snipeit)
    ├── admin/              — страницы административного интерфейса
    │   ├── snipeit_assets.php
    │   └── snipeit_licenses.php
    ├── install/            — установщик модуля
    │   ├── index.php       — класс установки/удаления
    │   ├── version.php     — версия модуля
    │   ├── db/             — SQL-скрипты установки
    │   │   ├── install.sql
    │   │   └── uninstall.sql
    │   └── images/
    │       └── logo.png    — иконка модуля (placeholder)
    ├── lang/               — языковые файлы
    │   ├── ru/
    │   └── en/
    ├── lib/                — PHP-классы (Bitrix D7 ORM)
    │   ├── Model/
    │   │   ├── AssetTable.php
    │   │   ├── LicenseTable.php
    │   │   └── UserAssignmentTable.php
    │   └── Service/
    │       ├── AssetService.php
    │       └── CheckoutService.php
    ├── templates/          — шаблоны компонентов
    │   ├── snipeit.asset_list/
    │   │   └── template.php
    │   └── snipeit.asset_detail/
    │       └── template.php
    ├── include.php         — подключение модуля
    └── options.php         — страница настроек модуля
```

## Установка

1. Скопируйте папку `local.snipeit` в `/local/modules/` вашего Bitrix-сайта.
2. Перейдите в **Административная панель → Marketplace → Установленные решения**.
3. Найдите **Snipe-IT Asset Management** и нажмите **Установить**.
4. Убедитесь, что созданы необходимые таблицы в БД (SQL выполняется автоматически при установке).

## Использование компонентов в шаблоне

```php
// Список активов
<?$APPLICATION->IncludeComponent(
    'local:snipeit.asset_list',
    '',
    ['ITEMS_PER_PAGE' => 25, 'COMPANY_ID' => 0]
);?>

// Карточка актива
<?$APPLICATION->IncludeComponent(
    'local:snipeit.asset_detail',
    '',
    ['ASSET_ID' => $assetId]
);?>
```

## Ключевые сущности

| Таблица               | Описание                     |
|-----------------------|------------------------------|
| `snipeit_assets`      | Оборудование / активы        |
| `snipeit_licenses`    | Лицензии ПО                  |
| `snipeit_assignments` | Назначение актива пользователю |
| `snipeit_action_log`  | Журнал действий              |

## Требования

- 1C-Битрикс 23.0+ (или Bitrix24 On-Premise)
- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.5+
