<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\OperationLog;
use Illuminate\Http\Request;

class OperationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationLog::with(['user']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->has('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->has('status_code')) {
            $query->where('status_code', $request->status_code);
        }

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

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function show($id)
    {
        $log = OperationLog::with(['user'])->findOrFail($id);
        return ApiResponse::success($log, '获取成功');
    }
}

