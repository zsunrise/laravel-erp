<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\OperationLog;
use Illuminate\Http\Request;

class OperationLogController extends Controller
{
    /**
     * 获取操作日志列表
     *
     * @param Request $request 请求对象，支持 user_id（用户ID）、module（模块）、action（操作）、method（请求方法）、status_code（状态码）、search（搜索关键词）和 date_from/date_to（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的操作日志列表，包含用户信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载用户信息
        $query = OperationLog::with(['user']);

        // 按用户ID筛选
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 按模块筛选
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        // 按操作模糊筛选
        if ($request->has('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        // 按HTTP请求方法筛选
        if ($request->has('method')) {
            $query->where('method', $request->input('method'));
        }

        // 按响应状态码筛选
        if ($request->has('status_code')) {
            $query->where('status_code', $request->status_code);
        }

        // 关键词搜索：支持搜索操作、路径、消息、用户名和邮箱
        if ($request->has('search')) {
            $search = $request->search;
            $query->leftJoin('users', 'operation_logs.user_id', '=', 'users.id')
                  ->where(function($q) use ($search) {
                      $q->where('operation_logs.action', 'like', "%{$search}%")
                        ->orWhere('operation_logs.path', 'like', "%{$search}%")
                        ->orWhere('operation_logs.message', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                  })
                  ->select('operation_logs.*')
                  ->distinct();
        }

        // 按日期范围筛选：开始日期
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // 按日期范围筛选：结束日期
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取指定操作日志详情
     *
     * @param int $id 日志ID
     * @return \Illuminate\Http\JsonResponse 返回日志详细信息，包含用户信息
     */
    public function show($id)
    {
        // 根据ID查询日志，预加载用户信息，找不到则抛出404
        $log = OperationLog::with(['user'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($log, '获取成功');
    }
}

