<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => '未授权，请先登录',
            ], 401);
        }

        $user = auth()->user();

        if ($permission && !$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => '没有权限执行此操作',
            ], 403);
        }

        return $next($request);
    }
}
