<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\OperationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogOperation
{
    /**
     * 不需要记录日志的路径
     */
    protected $except = [
        'api/login',
        'api/logout',
        'api/me',
        'api/dashboard',
        'api/notifications',
        'api/notifications/unread',
        'api/notifications/unread-count',
        'api/operation-logs',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 检查是否应该跳过日志记录
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        // 获取请求信息
        $startTime = microtime(true);
        $method = $request->method();
        $path = $request->path();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $user = Auth::user();

        // 获取请求数据（排除敏感信息）
        $requestData = $this->getRequestData($request);

        // 执行请求
        $response = $next($request);

        // 获取响应信息
        $statusCode = $response->getStatusCode();
        $responseData = $this->getResponseData($response, $path, $method);

        // 异步记录日志（避免影响响应速度）
        try {
            $this->logOperation([
                'user_id' => $user ? $user->id : null,
                'module' => $this->getModule($path),
                'action' => $this->getAction($method, $path),
                'method' => $method,
                'path' => $path,
                'request_data' => $requestData,
                'response_data' => $responseData,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'status_code' => $statusCode,
                'message' => $this->getMessage($method, $path, $statusCode),
            ]);
        } catch (\Exception $e) {
            // 记录日志失败不影响主流程，只记录错误日志
            Log::error('操作日志记录失败', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
        }

        return $response;
    }

    /**
     * 判断是否应该跳过日志记录
     */
    protected function shouldSkipLogging(Request $request): bool
    {
        $path = $request->path();

        // 排除 GET 请求（查询操作通常不需要记录）
        if ($request->method() === 'GET') {
            return true;
        }

        // 排除指定路径
        foreach ($this->except as $except) {
            if (str_starts_with($path, $except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取请求数据（排除敏感信息）
     */
    protected function getRequestData(Request $request): ?string
    {
        $data = $request->all();

        // 排除敏感字段
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_token'];
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        // 编码为 JSON（不截断）
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return $json ?: null;
    }

    /**
     * 获取响应数据
     */
    protected function getResponseData($response, string $path, string $method): ?string
    {
        try {
            $content = $response->getContent();
            
            if (!$content || !is_string($content)) {
                return null;
            }

            // 检查是否为 JSON 响应
            $contentType = $response->headers->get('Content-Type', '');
            if (strpos($contentType, 'application/json') === false) {
                return null; // 非 JSON 响应不记录
            }

            // 尝试解析 JSON 并格式化
            $jsonData = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 格式化 JSON，便于阅读
                return json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            // 如果不是有效的 JSON，返回原始内容
            return $content;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 根据路径获取模块名称
     */
    protected function getModule(string $path): ?string
    {
        $pathParts = explode('/', $path);
        
        if (count($pathParts) < 2) {
            return null;
        }

        // 移除 'api' 前缀
        $module = $pathParts[1] ?? null;

        // 模块名称映射
        $moduleMap = [
            'users' => '用户管理',
            'roles' => '角色管理',
            'permissions' => '权限管理',
            'system-configs' => '系统配置',
            'data-dictionaries' => '数据字典',
            'products' => '商品管理',
            'product-categories' => '商品分类',
            'suppliers' => '供应商管理',
            'customers' => '客户管理',
            'warehouses' => '仓库管理',
            'warehouse-locations' => '库位管理',
            'inventory' => '库存管理',
            'purchase-orders' => '采购订单',
            'purchase-returns' => '采购退货',
            'purchase-settlements' => '采购结算',
            'sales-orders' => '销售订单',
            'sales-returns' => '销售退货',
            'sales-settlements' => '销售结算',
            'boms' => 'BOM管理',
            'process-routes' => '工艺路线',
            'production-plans' => '生产计划',
            'work-orders' => '工单管理',
            'production-material-issues' => '生产领料',
            'production-reports' => '生产报工',
            'chart-of-accounts' => '会计科目',
            'accounting-vouchers' => '会计凭证',
            'general-ledger' => '总账',
            'accounts-receivable' => '应收账款',
            'accounts-payable' => '应付账款',
            'cost-allocations' => '成本分配',
            'financial-reports' => '财务报表',
            'workflows' => '审批流程',
            'workflow-instances' => '流程实例',
            'approval-records' => '审批记录',
            'notification-templates' => '消息模板',
            'regions' => '地区管理',
            'units' => '计量单位',
            'currencies' => '币种管理',
        ];

        return $moduleMap[$module] ?? $module;
    }

    /**
     * 根据方法和路径获取操作动作
     */
    protected function getAction(string $method, string $path): string
    {
        $pathParts = explode('/', $path);
        $lastPart = end($pathParts);

        // 根据 HTTP 方法确定操作
        $actionMap = [
            'POST' => '创建',
            'PUT' => '更新',
            'PATCH' => '更新',
            'DELETE' => '删除',
        ];

        $baseAction = $actionMap[$method] ?? $method;

        // 检查是否有特殊操作（如审核、提交等）
        if (is_numeric($lastPart)) {
            // 路径格式：/api/resource/{id}/action
            $secondLastPart = $pathParts[count($pathParts) - 2] ?? null;
            if ($secondLastPart && !is_numeric($secondLastPart)) {
                // 特殊操作映射
                $specialActions = [
                    'submit' => '提交',
                    'approve' => '审核通过',
                    'reject' => '审核拒绝',
                    'cancel' => '取消',
                    'receive' => '收货',
                    'ship' => '发货',
                    'post' => '过账',
                    'pay' => '付款',
                    'receive-payment' => '收款',
                    'make-payment' => '付款',
                    'issue-material' => '领料',
                    'return-material' => '退料',
                    'complete' => '完成',
                    'set-default' => '设为默认',
                    'copy' => '复制',
                    'start-approval' => '启动审批',
                    'read' => '标记已读',
                    'read-all' => '全部标记已读',
                    'preview' => '预览',
                    'execute' => '执行',
                    'getByKey' => '查询',
                    'getByType' => '查询',
                    'getTypes' => '查询',
                ];

                if (isset($specialActions[$secondLastPart])) {
                    return $specialActions[$secondLastPart];
                }
            }
        }

        return $baseAction;
    }

    /**
     * 获取操作说明消息
     */
    protected function getMessage(string $method, string $path, int $statusCode): ?string
    {
        $module = $this->getModule($path);
        $action = $this->getAction($method, $path);

        if ($module && $action) {
            $status = $statusCode >= 200 && $statusCode < 300 ? '成功' : '失败';
            return "{$module} - {$action} - {$status}";
        }

        return null;
    }

    /**
     * 记录操作日志
     */
    protected function logOperation(array $data): void
    {
        // 使用队列异步记录，避免影响响应速度
        // 如果队列不可用，则同步记录
        try {
            OperationLog::create($data);
        } catch (\Exception $e) {
            // 如果记录失败，记录到 Laravel 日志
            Log::warning('操作日志记录失败', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

