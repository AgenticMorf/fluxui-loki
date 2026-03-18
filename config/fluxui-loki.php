<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Loki URL
    |--------------------------------------------------------------------------
    | Base URL for the Loki API (e.g. http://loki:3100 when using Sail).
    */
    'url' => env('LOKI_URL', 'http://loki:3100'),

    /*
    |--------------------------------------------------------------------------
    | Service Label
    |--------------------------------------------------------------------------
    | Loki label used for the service dropdown (e.g. compose_service, job, container_name).
    */
    'service_label' => env('LOKI_SERVICE_LABEL', 'compose_service'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    | The Livewire layout component used for the logs dashboard.
    */
    'layout' => 'components.layouts.app.sidebar',

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route_path' => 'logs',
    'route_name' => 'logs',
    'middleware' => ['web', 'auth'],
];
