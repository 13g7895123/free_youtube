<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LineLoginLogsTestSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'session_id' => 'test_session_001',
                'step' => 'callback_start',
                'status' => 'success',
                'line_user_id' => 'U1234567890abcdef',
                'request_data' => json_encode(['query_params' => ['code' => 'abc123', 'state' => 'xyz789']]),
                'response_data' => null,
                'error_message' => null,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'session_id' => 'test_session_001',
                'step' => 'get_token',
                'status' => 'success',
                'line_user_id' => 'U1234567890abcdef',
                'request_data' => json_encode(['grant_type' => 'authorization_code', 'has_code' => true]),
                'response_data' => json_encode(['has_access_token' => true, 'token_type' => 'Bearer', 'expires_in' => 2592000]),
                'error_message' => null,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours +5 seconds'))
            ],
            [
                'session_id' => 'test_session_001',
                'step' => 'get_profile',
                'status' => 'success',
                'line_user_id' => 'U1234567890abcdef',
                'request_data' => null,
                'response_data' => json_encode([
                    'user_id' => 'U1234567890abcdef',
                    'display_name' => '測試使用者 A',
                    'has_picture' => true,
                    'has_email' => true
                ]),
                'error_message' => null,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours +10 seconds'))
            ],
            [
                'session_id' => 'test_session_001',
                'step' => 'complete',
                'status' => 'success',
                'line_user_id' => 'U1234567890abcdef',
                'request_data' => null,
                'response_data' => json_encode(['user_id' => 1, 'was_restored' => false]),
                'error_message' => null,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours +15 seconds'))
            ],
            [
                'session_id' => 'test_session_002',
                'step' => 'callback_start',
                'status' => 'error',
                'line_user_id' => null,
                'request_data' => json_encode(['query_params' => ['error' => 'access_denied']]),
                'response_data' => null,
                'error_message' => 'User cancelled: access_denied - The user cancelled authorization',
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'session_id' => 'test_session_003',
                'step' => 'callback_start',
                'status' => 'success',
                'line_user_id' => null,
                'request_data' => json_encode(['query_params' => ['code' => 'def456', 'state' => 'uvw321']]),
                'response_data' => null,
                'error_message' => null,
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
            ],
            [
                'session_id' => 'test_session_003',
                'step' => 'get_token',
                'status' => 'error',
                'line_user_id' => null,
                'request_data' => json_encode(['grant_type' => 'authorization_code']),
                'response_data' => json_encode(['http_code' => 400, 'response_body' => '{"error":"invalid_grant"}']),
                'error_message' => 'HTTP 400: {"error":"invalid_grant"}',
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes +5 seconds'))
            ],
            [
                'session_id' => 'test_session_004',
                'step' => 'validate_state',
                'status' => 'warning',
                'line_user_id' => null,
                'request_data' => json_encode(['state' => 'mismatch_state', 'session_state' => 'original_state']),
                'response_data' => null,
                'error_message' => 'State mismatch - CSRF validation failed',
                'ip_address' => '192.168.1.103',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
            ]
        ];

        $db = \Config\Database::connect();

        foreach ($data as $row) {
            $db->table('line_login_logs')->insert($row);
        }

        echo "成功插入 " . count($data) . " 筆測試資料\n";
    }
}
