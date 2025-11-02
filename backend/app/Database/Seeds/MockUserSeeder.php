<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MockUserSeeder extends Seeder
{
    /**
     * 建立 Mock 測試使用者
     * 僅在開發環境執行
     */
    public function run()
    {
        // 安全檢查：僅在開發環境執行
        if (env('CI_ENVIRONMENT') === 'production') {
            echo "⚠️  Mock 使用者 Seeder 僅在開發環境可用\n";
            return;
        }

        $mockUserId = 1;

        // 檢查 Mock 使用者是否已存在
        $exists = $this->db->table('users')
            ->where('id', $mockUserId)
            ->countAllResults() > 0;

        if ($exists) {
            echo "✅ Mock 使用者已存在 (ID: {$mockUserId})\n";
            return;
        }

        // 建立 Mock 使用者
        $userData = [
            'id' => $mockUserId,
            'line_user_id' => 'mock_line_user_001',
            'display_name' => 'Mock 測試使用者',
            'avatar_url' => 'https://via.placeholder.com/150/667eea/ffffff?text=MOCK',
            'email' => 'mock@example.com',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($userData);

        echo "✅ Mock 使用者建立成功！\n";
        echo "   ID: {$mockUserId}\n";
        echo "   名稱: {$userData['display_name']}\n";
        echo "   LINE User ID: {$userData['line_user_id']}\n";
    }
}
