# Snipe-IT — Standalone PHP Module

This is a self-contained PHP module extracted from the Snipe-IT Laravel application.
It provides asset management functionality **without requiring the Laravel framework**.

## Structure

```
stage1-php-module/
├── src/
│   ├── config/          — Module configuration
│   ├── Controllers/     — HTTP controllers (plain PHP)
│   ├── Models/          — Data models (PDO-based, no Eloquent)
│   ├── Views/           — PHP view templates
│   ├── Migrations/      — SQL migration files (raw SQL)
│   └── Services/        — Business-logic services
├── public/
│   └── index.php        — Entry point
├── composer.json
└── README.md
```

## Requirements

- PHP 8.2+
- PDO extension (MySQL/PostgreSQL/SQLite)
- Any web server (Apache, Nginx, PHP built-in)

## Quick Start

```bash
composer install
cp src/config/database.example.php src/config/database.php
# edit src/config/database.php with your credentials
php -S localhost:8080 public/index.php
```

## Running Migrations

```bash
php bin/migrate.php
```

## Key Entities

| Entity | Description |
|--------|-------------|
| Asset | Physical hardware item tracked by asset tag and serial number |
| AssetModel | Template defining type, manufacturer, and depreciation |
| Category | Classification for assets, accessories, consumables, licenses |
| Company | Organisation owning assets |
| Location | Physical location of asset or user |
| User | Person responsible for or assigned an asset |
| License | Software license with seat tracking |
| Accessory | Peripheral checked out to users |
| Consumable | Expendable item (not returned after use) |
| Component | Internal component assigned inside an asset |
| Supplier | Vendor who sold assets |
| Manufacturer | Producer of the asset model |
| Depreciation | Depreciation schedule for asset value |
| StatusLabel | Asset lifecycle status (deployable, pending, archived…) |
| Maintenance | Scheduled or completed maintenance record |

## Architecture Notes

- All database access uses **PDO** with prepared statements — no ORM dependency.
- Views are plain **PHP templates** with no Blade syntax.
- Controllers are plain PHP classes with no framework routing — routing is handled by `public/index.php`.
- Migrations are ordered SQL files executed by `bin/migrate.php`.
