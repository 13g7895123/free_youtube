<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    /**
     * Allowed origins for CORS requests
     */
    public array $allowedOrigins = [
        'http://localhost:5173',  // Vue.js dev server
        'http://localhost:3000',  // Alternative
        'http://localhost:8080',  // This server
    ];

    /**
     * Allowed HTTP methods
     */
    public array $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS',
    ];

    /**
     * Allowed headers
     */
    public array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
    ];

    /**
     * Exposed headers
     */
    public array $exposedHeaders = [
        'Content-Type',
        'X-Total-Count',
    ];

    /**
     * Max age for preflight cache
     */
    public int $maxAge = 7200;

    /**
     * Allow credentials
     */
    public bool $allowCredentials = true;
}
