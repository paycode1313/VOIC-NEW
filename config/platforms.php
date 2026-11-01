<?php

/**
 * Platform Configuration
 *
 * Mendefinisikan paths dan namespace untuk platform Web & Mobile.
 * Keduanya menggunakan 1 database dan shared business logic di app/.
 */

return [
    'web' => [
        'name' => 'Web Platform (VOIC Monolith)',
        'controllers_namespace' => 'App\\Http\\Controllers\\Web',
        'views_path' => resource_path('views'),
        'routes_path' => base_path('routes/web.php'),
    ],

    'mobile' => [
        'name' => 'Mobile API Platform',
        'controllers_namespace' => 'App\\Http\\Controllers\\Api\\v1',
        'routes_path' => base_path('routes/api.php'),
        'apps' => [
            'flutter' => base_path('mobile/flutter'),
            'react_native' => base_path('mobile/react-native'),
        ],
    ],

    'shared' => [
        'models_path' => base_path('app/Models'),
        'services_path' => base_path('app/Services'),
        'database_path' => base_path('database'),
    ],
];
