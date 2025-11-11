<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;

class CleanupDeletedUsers extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Maintenance';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'cleanup:deleted-users';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = '清理超過 30 天的軟刪除會員資料';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'cleanup:deleted-users [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--dry-run' => '預覽模式，不實際刪除資料',
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $isDryRun = array_key_exists('dry-run', $params);

        CLI::write('開始清理軟刪除會員資料...', 'yellow');
        CLI::newLine();

        $userModel = new UserModel();

        // 計算 30 天前的日期
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));

        // 查詢需要清理的會員
        $deletedUsers = $userModel
            ->where('deleted_at IS NOT NULL')
            ->where('deleted_at <', $thirtyDaysAgo)
            ->withDeleted()
            ->findAll();

        if (empty($deletedUsers)) {
            CLI::write('沒有需要清理的會員資料', 'green');
            return;
        }

        $count = count($deletedUsers);
        CLI::write("找到 {$count} 個超過 30 天的軟刪除會員", 'cyan');
        CLI::newLine();

        if ($isDryRun) {
            CLI::write('【預覽模式】以下會員將被清理：', 'yellow');
            CLI::newLine();

            foreach ($deletedUsers as $user) {
                $deletedDays = floor((time() - strtotime($user['deleted_at'])) / 86400);
                CLI::write("  - ID: {$user['id']}", 'white');
                CLI::write("    LINE User ID: {$user['line_user_id']}", 'white');
                CLI::write("    顯示名稱: {$user['display_name']}", 'white');
                CLI::write("    刪除時間: {$user['deleted_at']} ({$deletedDays} 天前)", 'white');
                CLI::newLine();
            }

            CLI::write('若要實際執行清理，請移除 --dry-run 參數', 'yellow');
        } else {
            // 實際刪除
            CLI::write('開始永久刪除資料...', 'red');

            $deletedCount = 0;

            foreach ($deletedUsers as $user) {
                try {
                    // 永久刪除會員（會 CASCADE 刪除所有相關資料）
                    $userModel->delete($user['id'], true); // true = 強制永久刪除

                    $deletedCount++;
                    CLI::write("  ✓ 已刪除會員 ID: {$user['id']} ({$user['display_name']})", 'green');
                } catch (\Exception $e) {
                    CLI::write("  ✗ 刪除會員 ID: {$user['id']} 失敗: {$e->getMessage()}", 'red');
                    log_message('error', "Failed to cleanup deleted user {$user['id']}: {$e->getMessage()}");
                }
            }

            CLI::newLine();
            CLI::write("清理完成！共刪除 {$deletedCount} 個會員", 'green');

            // 記錄到日誌
            log_message('info', "Cleanup deleted users: {$deletedCount} users permanently deleted");
        }

        CLI::newLine();
    }
}
