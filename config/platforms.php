<?php

/**
 * Platform Configuration
 *
 * Mendefinisikan paths untuk berbagai platform (web & mobile)
 * Keduanya menggunakan 1 database dan shared logic di app/
 */

return [
    'web' => [
        'name' => 'Web Platform',
        'controllers_namespace' => 'App\\Http\\Controllers\\Web',
        'views_path' => base_path('platforms/web/resources/views'),
        'routes_path' => base_path('platforms/web/routes'),
    ],

    'mobile' => [
        'name' => 'Mobile API Platform',
        'controllers_namespace' => 'App\\Http\\Controllers\\Mobile\\Api',
        'routes_path' => base_path('platforms/mobile/routes'),
    ],

    'shared' => [
        'models_path' => base_path('app/Models'),
        'services_path' => base_path('app/Services'),
        'database_path' => base_path('database'),
    ],
];
