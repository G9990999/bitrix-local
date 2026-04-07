<?php
return [
    'utf_mode' => ['value' => true, 'readonly' => true],

    'security' => [
        'value' => [
            'settings' => [
                'prevent_session_id_change' => false,
                'use_session_id_ttl' => false,
            ],
            'waf' => ['enabled' => false],
        ],
        'readonly' => false,
    ],

    'cache_flags' => [
        'value' => [
            'type' => 'none',
            //'config_options' => 3600,
            //'site_domain' => 3600,
        ],
        'readonly' => false,
    ],

    'exception_handling' => [
      'value' => [
        'debug' => false, // ВЫКЛЮЧИТЬ дебаг (он форсирует строгий режим)
        'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING,
        'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING,
        'ignore_silence' => false,
        'assertion_errors_types' => 252,
        'assertion_throws_exception' => true,
        'log' => [
            'settings' => [
                'file' => 'bitrix/modules/error.log',
                'log_size' => 1000000,
            ],
        ],
      ],
      'readonly' => false,
    ],
    'rolemodel.webhook' => [
      'value' => [
        'frontend_url' => 'http://localhost:3000/api/webhook',
        'timeout' => 2.0,
    ],
    'readonly' => false,
    ],
    'connections' => [
        'value' => [
            'default' => [
                'className' => '\\RoleModel\\Cli\\DB\\PostgresAdapter',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'bitrix',
                'login' => 'bitrix',
                'password' => 'bitrix',
                'options' => 2,
            ],
        ],
        'readonly' => true,
    ],
    'install' => ['value' => ['database' => 'postgres'], 'readonly' => true],
];
