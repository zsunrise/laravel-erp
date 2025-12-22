<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 慢查询监控（仅在非生产环境或调试模式下启用）
        if (config('app.debug') || config('app.env') !== 'production') {
            DB::listen(function ($query) {
                // 记录超过 100ms 的慢查询
                if ($query->time > 100) {
                    Log::warning('慢查询检测', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                    ]);
                }
            });
        }
    }
}
