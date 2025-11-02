<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * CORS Filter Configuration
 */
class CorsFilter extends BaseConfig
{
    /**
     * Enable/disable CORS
     */
    public bool $enabled = true;

    /**
     * Allowed origins
     */
    public array $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:3000',
    ];

    /**
     * Allowed methods
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
     * Max age
     */
    public int $maxAge = 7200;

    /**
     * Allow credentials
     */
    public bool $allowCredentials = false;

    /**
     * Exposed headers
     */
    public array $exposedHeaders = [
        'Content-Type',
        'X-Total-Count',
    ];
}
