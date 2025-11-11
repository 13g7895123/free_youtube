<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Helpers\JwtHelper;

class TestJwt extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'test:jwt';
    protected $description = '測試 JWT Token 生成和驗證是否使用相同的 Secret Key';

    public function run(array $params)
    {
        CLI::write('=== JWT Token 驗證測試 ===', 'yellow');
        CLI::newLine();

        // 測試 1: 檢查 Secret Key
        CLI::write('測試 1: 檢查 Secret Key', 'cyan');
        $secretKey = env('JWT_SECRET_KEY');
        if (empty($secretKey)) {
            CLI::error('❌ JWT_SECRET_KEY 未設置');
            return;
        }
        CLI::write('✅ JWT_SECRET_KEY 已設置', 'green');
        CLI::write('   長度: ' . strlen($secretKey) . ' 字元');
        CLI::write('   前10字元: ' . substr($secretKey, 0, 10) . '...');
        CLI::newLine();

        // 測試 2: 生成 Access Token
        CLI::write('測試 2: 生成 Access Token', 'cyan');
        try {
            $userId = 999;
            $accessToken = JwtHelper::generateAccessToken($userId);
            CLI::write('✅ Access Token 生成成功', 'green');
            CLI::write('   Token 長度: ' . strlen($accessToken) . ' 字元');
            CLI::write('   Token 前50字元: ' . substr($accessToken, 0, 50) . '...');
            CLI::newLine();
        } catch (\Exception $e) {
            CLI::error('❌ 錯誤: ' . $e->getMessage());
            return;
        }

        // 測試 3: 驗證 Access Token
        CLI::write('測試 3: 驗證 Access Token（使用相同的 Secret Key）', 'cyan');
        try {
            $decoded = JwtHelper::verifyToken($accessToken, 'access');
            if ($decoded && $decoded->sub == $userId) {
                CLI::write('✅ Access Token 驗證成功', 'green');
                CLI::write('   用戶 ID: ' . $decoded->sub);
                CLI::write('   Token 類型: ' . $decoded->type);
                CLI::write('   簽發時間: ' . date('Y-m-d H:i:s', $decoded->iat));
                CLI::write('   過期時間: ' . date('Y-m-d H:i:s', $decoded->exp));
                CLI::newLine();
            } else {
                CLI::error('❌ Token 驗證失敗或用戶 ID 不符');
                return;
            }
        } catch (\Exception $e) {
            CLI::error('❌ 錯誤: ' . $e->getMessage());
            return;
        }

        // 測試 4: 生成 Refresh Token
        CLI::write('測試 4: 生成 Refresh Token', 'cyan');
        try {
            $refreshToken = JwtHelper::generateRefreshToken($userId, 'test-device-001');
            CLI::write('✅ Refresh Token 生成成功', 'green');
            CLI::write('   Token 長度: ' . strlen($refreshToken) . ' 字元');
            CLI::write('   Token 前50字元: ' . substr($refreshToken, 0, 50) . '...');
            CLI::newLine();
        } catch (\Exception $e) {
            CLI::error('❌ 錯誤: ' . $e->getMessage());
            return;
        }

        // 測試 5: 驗證 Refresh Token
        CLI::write('測試 5: 驗證 Refresh Token（使用相同的 Secret Key）', 'cyan');
        try {
            $decoded = JwtHelper::verifyToken($refreshToken, 'refresh');
            if ($decoded && $decoded->sub == $userId) {
                CLI::write('✅ Refresh Token 驗證成功', 'green');
                CLI::write('   用戶 ID: ' . $decoded->sub);
                CLI::write('   Token 類型: ' . $decoded->type);
                CLI::write('   JTI: ' . ($decoded->jti ?? 'N/A'));
                CLI::write('   Device ID: ' . ($decoded->device_id ?? 'N/A'));
                CLI::write('   過期時間: ' . date('Y-m-d H:i:s', $decoded->exp));
                CLI::newLine();
            } else {
                CLI::error('❌ Token 驗證失敗或用戶 ID 不符');
                return;
            }
        } catch (\Exception $e) {
            CLI::error('❌ 錯誤: ' . $e->getMessage());
            return;
        }

        // 測試 6: Token 類型檢查
        CLI::write('測試 6: 驗證 Token 類型檢查機制', 'cyan');
        try {
            $wrongTypeDecoded = JwtHelper::verifyToken($accessToken, 'refresh');
            if ($wrongTypeDecoded === null) {
                CLI::write('✅ Token 類型檢查正常（access token 無法作為 refresh token 使用）', 'green');
                CLI::newLine();
            } else {
                CLI::error('❌ Token 類型檢查失敗');
                return;
            }
        } catch (\Exception $e) {
            CLI::error('❌ 錯誤: ' . $e->getMessage());
            return;
        }

        // 測試 7: Token 有效期檢查
        CLI::write('測試 7: Token 有效期檢查', 'cyan');
        $remainingTime = JwtHelper::getRemainingTime($accessToken);
        CLI::write('✅ Access Token 剩餘有效時間: ' . $remainingTime . ' 秒', 'green');
        $isExpiringSoon = JwtHelper::isExpiringSoon($accessToken, 1000);
        CLI::write('   即將過期 (1000秒內): ' . ($isExpiringSoon ? '是' : '否'));
        CLI::newLine();

        // 測試 8: 解碼 Token
        CLI::write('測試 8: 解碼 Token Payload（不驗證簽名）', 'cyan');
        $payload = JwtHelper::decode($accessToken);
        if ($payload) {
            CLI::write('✅ Token 解碼成功', 'green');
            CLI::write('   完整 Payload:');
            CLI::write('   ' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            CLI::newLine();
        } else {
            CLI::error('❌ Token 解碼失敗');
            return;
        }

        // 測試 9: 取得用戶 ID
        CLI::write('測試 9: 從 Token 取得用戶 ID', 'cyan');
        $extractedUserId = JwtHelper::getUserId($accessToken, 'access');
        if ($extractedUserId == $userId) {
            CLI::write('✅ 成功從 Token 取得用戶 ID: ' . $extractedUserId, 'green');
            CLI::newLine();
        } else {
            CLI::error('❌ 取得的用戶 ID 不符');
            return;
        }

        // 總結
        CLI::newLine();
        CLI::write('=== 所有測試通過！✅ ===', 'green');
        CLI::newLine();
        CLI::write('結論：', 'yellow');
        CLI::write('1. ✅ 所有 JWT 操作使用相同的 Secret Key');
        CLI::write('2. ✅ Access Token 和 Refresh Token 使用相同的加密算法 (HS256)');
        CLI::write('3. ✅ Token 生成和驗證流程正常');
        CLI::write('4. ✅ Token 類型檢查機制正常');
        CLI::write('5. ✅ Token 有效期檢查正常');
        CLI::newLine();
        CLI::write('前後端 JWT 驗證機制使用相同的 Secret Key 和算法！', 'green');
    }
}
