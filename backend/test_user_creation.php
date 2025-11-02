<?php

// 測試使用者建立功能

require __DIR__ . '/vendor/autoload.php';

// 初始化 CodeIgniter
$pathsConfig = require __DIR__ . '/app/Config/Paths.php';
$paths = new \Config\Paths();

// 模擬環境
$_SERVER['CI_ENVIRONMENT'] = 'development';

// 載入 .env
$bootstrap = new \CodeIgniter\Config\DotEnv(ROOTPATH);
$bootstrap->load();

// 建立資料庫連線
$db = \Config\Database::connect();

// 測試 1: 使用 UserModel 建立使用者（有 status）
echo "測試 1: 建立使用者（包含 status 欄位）\n";
echo "==========================================\n";

$userModel = new \App\Models\UserModel();

$testUserData = [
    'line_user_id' => 'test_user_' . time(),
    'display_name' => '測試使用者 A',
    'avatar_url' => 'https://example.com/avatar.jpg',
    'email' => 'test@example.com',
    'status' => 'active'
];

try {
    $userId = $userModel->insert($testUserData);
    if ($userId) {
        echo "✓ 成功建立使用者！User ID: {$userId}\n";

        // 驗證資料
        $user = $userModel->find($userId);
        echo "  - LINE User ID: {$user['line_user_id']}\n";
        echo "  - Display Name: {$user['display_name']}\n";
        echo "  - Status: {$user['status']}\n";

        // 清理測試資料
        $userModel->delete($userId);
        echo "✓ 測試資料已清理\n";
    } else {
        echo "✗ 建立失敗：" . json_encode($userModel->errors()) . "\n";
    }
} catch (\Exception $e) {
    echo "✗ 例外錯誤：" . $e->getMessage() . "\n";
}

echo "\n";

// 測試 2: 測試沒有 status 欄位的情況（應該會失敗）
echo "測試 2: 建立使用者（不包含 status 欄位 - 預期會失敗）\n";
echo "========================================================\n";

$testUserData2 = [
    'line_user_id' => 'test_user_no_status_' . time(),
    'display_name' => '測試使用者 B',
    'avatar_url' => 'https://example.com/avatar2.jpg',
    'email' => 'test2@example.com'
    // 故意不包含 status
];

try {
    $userId2 = $userModel->insert($testUserData2);
    if ($userId2) {
        echo "✗ 意外成功（這不應該發生）！User ID: {$userId2}\n";
        $userModel->delete($userId2);
    } else {
        echo "✓ 預期的驗證失敗：" . json_encode($userModel->errors()) . "\n";
    }
} catch (\Exception $e) {
    echo "✓ 預期的例外錯誤：" . $e->getMessage() . "\n";
}

echo "\n完成測試！\n";
