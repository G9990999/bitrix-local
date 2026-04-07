<?php
use Bitrix\Main\Routing\RoutingConfigurator;
use RoleModel\Cli\Controllers\EventController;

return function (RoutingConfigurator $routes) {
    
    // Группа маршрутов для событий
    $routes->prefix('api/events')->group(function (RoutingConfigurator $routes) {
        
        // Список всех событий: GET /api/events/
        $routes->get('/', [EventController::class, 'list']);
        
        // Извлечение и удаление: GET /api/events/pop/
        $routes->get('pop', [EventController::class, 'pop']);
        
    });
};
