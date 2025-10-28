<?php

/**
 * API 回應格式化輔助函數
 * CodeIgniter helper functions must be in the global scope
 */

if (!function_exists('api_success')) {
    /**
     * 格式化成功的 API 回應
     */
    function api_success($data = null, string $message = 'Success', int $code = 200)
    {
        return [
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ];
    }
}

if (!function_exists('api_error')) {
    /**
     * 格式化失敗的 API 回應
     */
    function api_error(string $message = 'Error', int $code = 500, $errors = null)
    {
        return [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c'),
        ];
    }
}

if (!function_exists('api_paginated')) {
    /**
     * 格式化分頁的 API 回應
     */
    function api_paginated($data, int $currentPage = 1, int $perPage = 20, int $total = 0)
    {
        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Success',
            'data' => $data,
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
            'timestamp' => date('c'),
        ];
    }
}

if (!function_exists('api_validation_error')) {
    /**
     * 格式化驗證錯誤的 API 回應
     */
    function api_validation_error($errors)
    {
        return api_error('Validation failed', 422, $errors);
    }
}
