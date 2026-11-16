<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | The web pages live in the frontend folder next to this backend
    | (VOIC-NEW/frontend/views), not in resources/views, so the repository
    | reads frontend / backend / mobile side by side.
    |
    */

    'paths' => [
        realpath(base_path('../frontend/views')) ?: base_path('../frontend/views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views')) ?: storage_path('framework/views'),
    ),

];
