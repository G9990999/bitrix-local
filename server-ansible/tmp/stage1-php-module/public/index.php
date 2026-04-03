<?php

/**
 * Snipe-IT Standalone PHP Module — Front Controller
 *
 * Handles routing without any framework dependency.
 * Run with: php -S localhost:8080 public/index.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use SnipeIT\Module\Controllers\AssetController;
use SnipeIT\Module\Controllers\LicenseController;

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Simple router: [METHOD, pattern, controller, action]
$routes = [
    ['GET',  '/',                        AssetController::class,   'index'],

    // Assets
    ['GET',  '/assets',                  AssetController::class,   'index'],
    ['GET',  '/assets/create',           AssetController::class,   'create'],
    ['POST', '/assets',                  AssetController::class,   'store'],
    ['GET',  '/assets/{id}',             AssetController::class,   'show'],
    ['GET',  '/assets/{id}/edit',        AssetController::class,   'edit'],
    ['POST', '/assets/{id}/update',      AssetController::class,   'update'],
    ['POST', '/assets/{id}/delete',      AssetController::class,   'destroy'],
    ['POST', '/assets/{id}/checkout',    AssetController::class,   'checkout'],
    ['POST', '/assets/{id}/checkin',     AssetController::class,   'checkin'],

    // Licenses
    ['GET',  '/licenses',                LicenseController::class, 'index'],
    ['GET',  '/licenses/create',         LicenseController::class, 'create'],
    ['POST', '/licenses',                LicenseController::class, 'store'],
    ['GET',  '/licenses/{id}',           LicenseController::class, 'show'],
    ['POST', '/licenses/{id}/delete',    LicenseController::class, 'destroy'],
];

function matchRoute(string $pattern, string $uri): array|false
{
    $regex = preg_replace('/\{[^}]+\}/', '(\d+)', $pattern);
    $regex = '#^' . $regex . '$#';
    if (preg_match($regex, $uri, $m)) {
        array_shift($m);
        return $m;
    }
    return false;
}

foreach ($routes as [$routeMethod, $pattern, $controllerClass, $action]) {
    if ($routeMethod !== $method) {
        continue;
    }
    $params = matchRoute($pattern, $uri);
    if ($params === false) {
        continue;
    }

    $controller = new $controllerClass();
    $controller->$action(...array_map('intval', $params));
    exit;
}

// No route matched
http_response_code(404);
echo "<h1>404 Not Found</h1><p>No route matched: $method $uri</p>";
