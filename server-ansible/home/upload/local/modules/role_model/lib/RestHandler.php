<?php
namespace Local\RoleModel;

/**
 * Registers REST API endpoints for the role model module.
 *
 * Available endpoints after installing this module:
 *
 *   POST /rest/rolemodel.predict
 *     Params: position (string) — job title
 *     Returns: { permissions: { "role_name": true/false, ... }, csv: "...", json: {...} }
 *
 *   GET /rest/rolemodel.roles
 *     Returns: { roles: ["role1", "role2", ...] }
 */
class RestHandler
{
    public static function onBuildServiceDescription(): array
    {
        return [
            'rolemodel' => [
                'predict' => [
                    'callback' => [self::class, 'predict'],
                    'options' => [],
                ],
                'roles' => [
                    'callback' => [self::class, 'getRoles'],
                    'options' => [],
                ],
            ],
        ];
    }

    public static function predict(array $params): array
    {
        $position = trim($params['position'] ?? '');
        if ($position === '') {
            throw new \Bitrix\Rest\RestException('Parameter "position" is required', 'INVALID_PARAMS');
        }

        $serviceUrl = \Bitrix\Main\Config\Option::get('role_model', 'service_url', 'http://127.0.0.1:8765');
        $service = new PredictionService($serviceUrl);

        $permissions = $service->predict($position);

        return [
            'position' => $position,
            'permissions' => $permissions,
            'csv' => $service->predictCsv($position),
        ];
    }

    public static function getRoles(array $params): array
    {
        $serviceUrl = \Bitrix\Main\Config\Option::get('role_model', 'service_url', 'http://127.0.0.1:8765');
        $service = new PredictionService($serviceUrl);
        return ['roles' => $service->getRoles()];
    }
}
