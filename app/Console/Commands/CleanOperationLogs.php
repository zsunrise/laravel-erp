<?php

namespace App\Console\Commands;

use App\Models\OperationLog;
use Illuminate\Console\Command;

class CleanOperationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'operation-logs:clean {--days=30 : 清理多少天前的日志}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理过期的操作日志（默认清理30天前的日志）';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("开始清理 {$days} 天前的操作日志（截止日期: {$cutoffDate->format('Y-m-d H:i:s')}）");

        try {
            $deleted = OperationLog::where('created_at', '<', $cutoffDate)->delete();

            $this->info("✅ 成功清理 {$deleted} 条操作日志");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ 清理操作日志失败: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
