<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 每天凌晨 2 点清理 30 天前的操作日志
        $schedule->command('operation-logs:clean', ['--days' => 30])
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('操作日志定时清理任务失败');
                 })
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('操作日志定时清理任务成功执行');
                 });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
