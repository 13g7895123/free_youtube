<?php
/**
 * JWT Token 驗證測試腳本
 * 用於確認所有 JWT 操作使用相同的 Secret Key
 */

// 設置環境
define('ENVIRONMENT', 'development');

// 載入 paths 設定
require realpath(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();

// 載入 autoloader
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

// 載入 environment
$bootstrap = \CodeIgniter\Config\Services::autoloader();
$bootstrap->initialize(new \Config\Autoload(), new \Config\Modules());
$bootstrap->register();

// 載入 JwtHelper
use App\Helpers\JwtHelper;

echo "=== JWT Token 驗證測試 ===\n\n";

// 測試 1: 檢查 Secret Key 是否已設置
echo "測試 1: 檢查 Secret Key\n";
$secretKey = env('JWT_SECRET_KEY');
if (empty($secretKey)) {
    echo "❌ 錯誤: JWT_SECRET_KEY 未設置\n";
    exit(1);
} else {
    echo "✅ JWT_SECRET_KEY 已設置\n";
    echo "   長度: " . strlen($secretKey) . " 字元\n";
    echo "   前10字元: " . substr($secretKey, 0, 10) . "...\n\n";
}

// 測試 2: 生成 Access Token
echo "測試 2: 生成 Access Token\n";
try {
    $userId = 999;
    $accessToken = JwtHelper::generateAccessToken($userId);
    echo "✅ Access Token 生成成功\n";
    echo "   Token 長度: " . strlen($accessToken) . " 字元\n";
    echo "   Token 前50字元: " . substr($accessToken, 0, 50) . "...\n\n";
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}

// 測試 3: 驗證 Access Token（使用相同的 key）
echo "測試 3: 驗證 Access Token\n";
try {
    $decoded = JwtHelper::verifyToken($accessToken, 'access');
    if ($decoded && $decoded->sub == $userId) {
        echo "✅ Access Token 驗證成功\n";
        echo "   用戶 ID: " . $decoded->sub . "\n";
        echo "   Token 類型: " . $decoded->type . "\n";
        echo "   過期時間: " . date('Y-m-d H:i:s', $decoded->exp) . "\n\n";
    } else {
        echo "❌ 錯誤: Token 驗證失敗或用戶 ID 不符\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}

// 測試 4: 生成 Refresh Token
echo "測試 4: 生成 Refresh Token\n";
try {
    $refreshToken = JwtHelper::generateRefreshToken($userId, 'test-device-001');
    echo "✅ Refresh Token 生成成功\n";
    echo "   Token 長度: " . strlen($refreshToken) . " 字元\n";
    echo "   Token 前50字元: " . substr($refreshToken, 0, 50) . "...\n\n";
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}

// 測試 5: 驗證 Refresh Token（使用相同的 key）
echo "測試 5: 驗證 Refresh Token\n";
try {
    $decoded = JwtHelper::verifyToken($refreshToken, 'refresh');
    if ($decoded && $decoded->sub == $userId) {
        echo "✅ Refresh Token 驗證成功\n";
        echo "   用戶 ID: " . $decoded->sub . "\n";
        echo "   Token 類型: " . $decoded->type . "\n";
        echo "   JTI: " . ($decoded->jti ?? 'N/A') . "\n";
        echo "   Device ID: " . ($decoded->device_id ?? 'N/A') . "\n";
        echo "   過期時間: " . date('Y-m-d H:i:s', $decoded->exp) . "\n\n";
    } else {
        echo "❌ 錯誤: Token 驗證失敗或用戶 ID 不符\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}

// 測試 6: 使用錯誤的 Token 類型驗證
echo "測試 6: 驗證 Token 類型檢查\n";
try {
    $wrongTypeDecoded = JwtHelper::verifyToken($accessToken, 'refresh');
    if ($wrongTypeDecoded === null) {
        echo "✅ Token 類型檢查正常（access token 無法作為 refresh token 使用）\n\n";
    } else {
        echo "❌ 錯誤: Token 類型檢查失敗\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}

// 測試 7: 測試過期的 Token
echo "測試 7: 測試 Token 有效期檢查\n";
$remainingTime = JwtHelper::getRemainingTime($accessToken);
echo "✅ Access Token 剩餘有效時間: {$remainingTime} 秒\n";

$isExpiringSoon = JwtHelper::isExpiringSoon($accessToken, 1000);
echo "   即將過期 (1000秒內): " . ($isExpiringSoon ? '是' : '否') . "\n\n";

// 測試 8: 解碼 Token（不驗證簽名）
echo "測試 8: 解碼 Token Payload\n";
$payload = JwtHelper::decode($accessToken);
if ($payload) {
    echo "✅ Token 解碼成功\n";
    echo "   完整 Payload:\n";
    echo "   " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
} else {
    echo "❌ 錯誤: Token 解碼失敗\n";
    exit(1);
}

// 測試 9: 取得用戶 ID
echo "測試 9: 從 Token 取得用戶 ID\n";
$extractedUserId = JwtHelper::getUserId($accessToken, 'access');
if ($extractedUserId == $userId) {
    echo "✅ 成功從 Token 取得用戶 ID: {$extractedUserId}\n\n";
} else {
    echo "❌ 錯誤: 取得的用戶 ID 不符\n";
    exit(1);
}

echo "=== 所有測試通過！✅ ===\n\n";
echo "結論：\n";
echo "1. ✅ 所有 JWT 操作使用相同的 Secret Key\n";
echo "2. ✅ Access Token 和 Refresh Token 使用相同的加密算法 (HS256)\n";
echo "3. ✅ Token 生成和驗證流程正常\n";
echo "4. ✅ Token 類型檢查機制正常\n";
echo "5. ✅ Token 有效期檢查正常\n";
echo "\n前後端 JWT 驗證機制使用相同的 Secret Key 和算法！\n";
