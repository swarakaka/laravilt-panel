<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panel Path
    |--------------------------------------------------------------------------
    |
    | The default path for panels. Individual panels can override this.
    |
    */

    'path' => env('LARAVILT_PANEL_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Panel Middleware
    |--------------------------------------------------------------------------
    |
    | The default middleware stack for panels.
    |
    */

    'middleware' => [
        'web',
        'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Colors
    |--------------------------------------------------------------------------
    |
    | The default color scheme for panels.
    |
    */

    'colors' => [
        'primary' => '#6366f1', // Indigo
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Default branding configuration.
    |
    */

    'brand_name' => env('APP_NAME', 'Laravilt'),

    'brand_logo' => null,

    'favicon' => null,

    /*
    |--------------------------------------------------------------------------
    | Max Content Width
    |--------------------------------------------------------------------------
    |
    | The maximum content width for panel pages.
    |
    */

    'max_content_width' => '7xl',

];
