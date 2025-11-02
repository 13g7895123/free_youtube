<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

class JwtHelper
{
    private static string $secretKey;
    private static string $algorithm = 'HS256';

    /**
     * 初始化 JWT 設定
     */
    private static function init(): void
    {
        if (!isset(self::$secretKey)) {
            // 優先使用系統環境變數，否則從 .env 檔案讀取
            self::$secretKey = getenv('JWT_SECRET_KEY');
            $source = 'getenv';

            // 如果環境變數沒有，嘗試從 $_ENV 全域變數
            if (empty(self::$secretKey)) {
                self::$secretKey = $_ENV['JWT_SECRET_KEY'] ?? '';
                $source = '$_ENV';
            }

            // 如果還是沒有，嘗試從 .env 檔案讀取
            if (empty(self::$secretKey)) {
                $envFile = __DIR__ . '/../../.env';
                if (file_exists($envFile)) {
                    $envContent = file_get_contents($envFile);
                    if (preg_match('/JWT_SECRET_KEY\s*=\s*[\'"]?([^\'"\\r\\n]+)[\'"]?/', $envContent, $matches)) {
                        self::$secretKey = trim($matches[1], '\'" ');
                        $source = '.env file';
                    }
                } else {
                    $source = '.env file not found at: ' . $envFile;
                }
            }

            if (empty(self::$secretKey)) {
                $debugInfo = [
                    'getenv' => getenv('JWT_SECRET_KEY') ? 'has value' : 'empty',
                    '$_ENV' => isset($_ENV['JWT_SECRET_KEY']) ? 'has value' : 'not set',
                    'env_file' => file_exists(__DIR__ . '/../../.env') ? 'exists' : 'not found',
                    'source_tried' => $source,
                    'working_dir' => getcwd(),
                    'env_path' => realpath(__DIR__ . '/../../.env') ?: 'path not resolvable'
                ];
                throw new Exception('JWT_SECRET_KEY 未設置 - Debug: ' . json_encode($debugInfo));
            }

            log_message('debug', 'JWT initialized with secret key from: ' . $source);
        }
    }

    /**
     * 生成 Access Token
     *
     * @param int $userId 用戶 ID
     * @param array $extraData 額外資料（可選）
     * @return string JWT token
     * @throws Exception 當 token 生成失敗時
     */
    public static function generateAccessToken(int $userId, array $extraData = []): string
    {
        try {
            self::init();

            $expireSeconds = (int) (getenv('JWT_ACCESS_TOKEN_EXPIRE') ?: self::getEnvValue('JWT_ACCESS_TOKEN_EXPIRE', 900)); // 預設 15 分鐘
            $issuedAt = time();
            $expiresAt = $issuedAt + $expireSeconds;

            $payload = [
                'iss' => getenv('app.baseURL') ?: 'http://localhost:8080', // Issuer
                'aud' => getenv('app.baseURL') ?: 'http://localhost:8080', // Audience
                'iat' => $issuedAt,    // Issued at
                'nbf' => $issuedAt,    // Not before
                'exp' => $expiresAt,   // Expiration time
                'sub' => $userId,      // Subject (user ID)
                'type' => 'access',    // Token type
            ];

            // 合併額外資料
            $payload = array_merge($payload, $extraData);

            // 記錄 payload 資訊用於除錯
            log_message('debug', 'Generating access token for user ' . $userId . ' with expire seconds: ' . $expireSeconds);

            $token = JWT::encode($payload, self::$secretKey, self::$algorithm);

            // 驗證生成的 token 格式
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new Exception('Generated access token has invalid format. Parts count: ' . count($parts));
            }

            return $token;

        } catch (Exception $e) {
            $errorInfo = [
                'user_id' => $userId,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'has_secret_key' => !empty(self::$secretKey),
                'algorithm' => self::$algorithm,
                'expire_seconds' => $expireSeconds ?? null
            ];

            log_message('error', 'Failed to generate access token: ' . json_encode($errorInfo));
            throw new Exception('Access token generation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 生成 Refresh Token
     *
     * @param int $userId 用戶 ID
     * @param string|null $deviceId 裝置 ID（可選）
     * @return string JWT token
     * @throws Exception 當 token 生成失敗時
     */
    public static function generateRefreshToken(int $userId, ?string $deviceId = null): string
    {
        try {
            self::init();

            $expireSeconds = (int) (getenv('JWT_REFRESH_TOKEN_EXPIRE') ?: self::getEnvValue('JWT_REFRESH_TOKEN_EXPIRE', 2592000)); // 預設 30 天
            $issuedAt = time();
            $expiresAt = $issuedAt + $expireSeconds;

            // 生成唯一的 JWT ID
            $jti = null;
            try {
                $jti = bin2hex(random_bytes(16));
            } catch (Exception $e) {
                // 如果 random_bytes 失敗，使用備用方案
                $jti = uniqid('jti_', true) . '_' . mt_rand();
                log_message('warning', 'Failed to generate secure random bytes for jti, using fallback: ' . $e->getMessage());
            }

            $payload = [
                'iss' => getenv('app.baseURL') ?: 'http://localhost:8080',
                'aud' => getenv('app.baseURL') ?: 'http://localhost:8080',
                'iat' => $issuedAt,
                'nbf' => $issuedAt,
                'exp' => $expiresAt,
                'sub' => $userId,
                'type' => 'refresh',
                'jti' => $jti,
            ];

            if ($deviceId) {
                $payload['device_id'] = $deviceId;
            }

            // 記錄 payload 資訊用於除錯
            log_message('debug', 'Generating refresh token for user ' . $userId . ' with jti: ' . $jti . ' and expire seconds: ' . $expireSeconds);

            $token = JWT::encode($payload, self::$secretKey, self::$algorithm);

            // 驗證生成的 token 格式
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new Exception('Generated refresh token has invalid format. Parts count: ' . count($parts));
            }

            return $token;

        } catch (Exception $e) {
            $errorInfo = [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'has_secret_key' => !empty(self::$secretKey),
                'algorithm' => self::$algorithm,
                'expire_seconds' => $expireSeconds ?? null,
                'jti' => $jti ?? null
            ];

            log_message('error', 'Failed to generate refresh token: ' . json_encode($errorInfo));
            throw new Exception('Refresh token generation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 驗證並解碼 Token
     *
     * @param string $token JWT token
     * @param string $expectedType 期望的 token 類型 ('access' 或 'refresh')
     * @return object|null 解碼後的 payload，失敗返回 null
     */
    public static function verifyToken(string $token, string $expectedType = 'access'): ?object
    {
        try {
            self::init();

            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));

            // 檢查 token 類型
            if (isset($decoded->type) && $decoded->type !== $expectedType) {
                log_message('warning', "JWT type mismatch: expected {$expectedType}, got {$decoded->type}");
                return null;
            }

            return $decoded;
        } catch (ExpiredException $e) {
            log_message('warning', 'JWT expired: ' . $e->getMessage());
            return null;
        } catch (SignatureInvalidException $e) {
            log_message('error', 'JWT signature invalid: ' . $e->getMessage());
            return null;
        } catch (Exception $e) {
            log_message('error', 'JWT verification failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 解碼 Token（不驗證簽名，僅用於檢查 payload）
     *
     * @param string $token JWT token
     * @return object|null 解碼後的 payload
     */
    public static function decode(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')));
            return $payload;
        } catch (Exception $e) {
            log_message('error', 'JWT decode failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 從 Token 取得用戶 ID
     *
     * @param string $token JWT token
     * @param string $expectedType Token 類型
     * @return int|null 用戶 ID
     */
    public static function getUserId(string $token, string $expectedType = 'access'): ?int
    {
        $decoded = self::verifyToken($token, $expectedType);

        if ($decoded && isset($decoded->sub)) {
            return (int) $decoded->sub;
        }

        return null;
    }

    /**
     * 檢查 Token 是否即將過期
     *
     * @param string $token JWT token
     * @param int $thresholdSeconds 閾值（秒），預設 300 秒（5 分鐘）
     * @return bool true 表示即將過期
     */
    public static function isExpiringSoon(string $token, int $thresholdSeconds = 300): bool
    {
        $decoded = self::decode($token);

        if ($decoded && isset($decoded->exp)) {
            $remainingTime = $decoded->exp - time();
            return $remainingTime < $thresholdSeconds;
        }

        return false;
    }

    /**
     * 取得 Token 剩餘有效時間（秒）
     *
     * @param string $token JWT token
     * @return int|null 剩餘秒數，null 表示 token 無效
     */
    public static function getRemainingTime(string $token): ?int
    {
        $decoded = self::decode($token);

        if ($decoded && isset($decoded->exp)) {
            $remainingTime = $decoded->exp - time();
            return max(0, $remainingTime);
        }

        return null;
    }

    /**
     * 從 .env 檔案讀取環境變數
     *
     * @param string $key 變數名稱
     * @param mixed $default 預設值
     * @return mixed
     */
    private static function getEnvValue(string $key, $default = null)
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (preg_match('/' . preg_quote($key, '/') . '\s*=\s*[\'"]?([^\'"\\r\\n]+)[\'"]?/', $envContent, $matches)) {
                return trim($matches[1], '\'" ');
            }
        }
        return $default;
    }
}
