<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        // 注入通知服务
        $this->notificationService = $notificationService;
    }

    /**
     * 获取通知列表
     *
     * @param Request $request 请求对象，支持 status（状态）、type（类型）和 priority（优先级）筛选
     * @return \Illuminate\Http\JsonResponse 返回当前登录用户的分页通知列表
     */
    public function index(Request $request)
    {
        // 构建查询：只查询当前用户的通知
        $query = Notification::where('user_id', auth()->id());

        // 按通知状态筛选（unread/read/deleted）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按通知类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按优先级筛选
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取未读通知列表
     *
     * @return \Illuminate\Http\JsonResponse 返回当前登录用户的所有未读通知列表
     */
    public function unread()
    {
        // 获取当前用户的所有未读通知
        $notifications = Notification::where('user_id', auth()->id())
            ->where('status', 'unread')
            ->orderBy('created_at', 'desc')
            ->get();

        // 返回未读通知列表
        return response()->json($notifications);
    }

    /**
     * 获取未读通知数量
     *
     * @return \Illuminate\Http\JsonResponse 返回当前登录用户的未读通知数量
     */
    public function unreadCount()
    {
        // 调用通知服务获取未读数量
        $count = $this->notificationService->getUnreadCount(auth()->id());
        // 返回未读数量
        return response()->json(['count' => $count]);
    }

    /**
     * 获取指定通知详情
     *
     * @param int $id 通知ID
     * @return \Illuminate\Http\JsonResponse 返回通知详细信息，包含用户和日志信息，查看时自动标记为已读
     */
    public function show($id)
    {
        // 查询当前用户的指定通知，预加载用户和日志信息
        $notification = Notification::where('user_id', auth()->id())
            ->with(['user', 'logs'])
            ->findOrFail($id);

        // 如果未读则自动标记为已读
        if ($notification->status == 'unread') {
            $notification->markAsRead();
        }

        // 返回通知详情
        return ApiResponse::success($notification, '获取成功');
    }

    /**
     * 标记通知为已读
     *
     * @param int $id 通知ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的通知信息
     */
    public function markAsRead($id)
    {
        // 查询当前用户的指定通知
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        // 调用通知服务标记为已读
        $notification = $this->notificationService->markAsRead($notification->id);
        // 返回更新后的通知
        return response()->json($notification);
    }

    /**
     * 标记所有通知为已读
     *
     * @return \Illuminate\Http\JsonResponse 返回标记结果消息
     */
    public function markAllAsRead()
    {
        // 调用通知服务标记当前用户所有通知为已读
        $count = $this->notificationService->markAllAsRead(auth()->id());
        // 返回标记结果
        return response()->json(['message' => "已标记 {$count} 条消息为已读"]);
    }

    /**
     * 删除通知
     *
     * @param int $id 通知ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        // 查询当前用户的指定通知
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        // 标记为已删除（软删除）
        $notification->update(['status' => 'deleted']);
        // 返回删除成功消息
        return response()->json(['message' => '消息删除成功']);
    }

    /**
     * 发送通知
     *
     * @bodyParam user_id integer required 目标用户ID Example: 1
     * @bodyParam type string required 通知类型（system/approval/order/inventory/financial） Example: system
     * @bodyParam title string required 标题 Example: 系统通知
     * @bodyParam content string required 内容 Example: 您有一条新的消息
     * @bodyParam channel string 发送渠道（system/email/sms/push） Example: system
     * @bodyParam priority string 优先级（low/normal/high/urgent） Example: normal
     * @bodyParam reference_type string 关联业务类型 Example: purchase_order
     * @bodyParam reference_id integer 关联业务ID Example: 1
     * @bodyParam reference_no string 关联业务编号 Example: PO001
     * @bodyParam data array 额外数据 Example: 额外数据对象
     * @param Request $request 请求对象，包含通知信息（用户ID、类型、标题、内容等）
     * @return \Illuminate\Http\JsonResponse 返回创建的通知信息，状态码 201，失败时返回错误消息
     */
    public function send(Request $request)
    {
        // 验证发送通知参数
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',                      // 目标用户ID（必填）
            'type' => 'required|in:system,approval,order,inventory,financial', // 通知类型
            'title' => 'required|string|max:255',                         // 标题（必填）
            'content' => 'required|string',                               // 内容（必填）
            'channel' => 'sometimes|in:system,email,sms,push',            // 发送渠道
            'priority' => 'sometimes|in:low,normal,high,urgent',          // 优先级
            'reference_type' => 'nullable|string',                        // 关联业务类型
            'reference_id' => 'nullable|integer',                         // 关联业务ID
            'reference_no' => 'nullable|string',                          // 关联业务编号
            'data' => 'nullable|array',                                   // 额外数据
        ]);

        try {
            // 调用通知服务发送通知
            $notification = $this->notificationService->send(
                $validated['user_id'],
                $validated['type'],
                $validated['title'],
                $validated['content'],
                [
                    'channel' => $validated['channel'] ?? 'system',
                    'priority' => $validated['priority'] ?? 'normal',
                    'reference_type' => $validated['reference_type'] ?? null,
                    'reference_id' => $validated['reference_id'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'data' => $validated['data'] ?? null,
                ]
            );
            // 发送成功返回 201 状态码
            return response()->json($notification, 201);
        } catch (\Exception $e) {
            // 发送失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 使用模板发送通知
     *
     * @bodyParam user_id integer required 目标用户ID Example: 1
     * @bodyParam template_code string required 模板编码（必须存在） Example: order_approved
     * @bodyParam data array required 模板变量数据 Example: 模板变量数据对象
     * @bodyParam reference_type string 关联业务类型 Example: purchase_order
     * @bodyParam reference_id integer 关联业务ID Example: 1
     * @bodyParam reference_no string 关联业务编号 Example: PO001
     * @param Request $request 请求对象，包含用户ID、模板编码和模板数据
     * @return \Illuminate\Http\JsonResponse 返回创建的通知信息，状态码 201，失败时返回错误消息
     */
    public function sendByTemplate(Request $request)
    {
        // 验证模板发送参数
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',                       // 目标用户ID（必填）
            'template_code' => 'required|exists:notification_templates,code', // 模板编码（必填）
            'data' => 'required|array',                                    // 模板变量数据（必填）
            'reference_type' => 'nullable|string',                         // 关联业务类型
            'reference_id' => 'nullable|integer',                          // 关联业务ID
            'reference_no' => 'nullable|string',                           // 关联业务编号
        ]);

        try {
            // 调用通知服务使用模板发送通知
            $notification = $this->notificationService->sendByTemplate(
                $validated['user_id'],
                $validated['template_code'],
                $validated['data'],
                [
                    'reference_type' => $validated['reference_type'] ?? null,
                    'reference_id' => $validated['reference_id'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                ]
            );
            // 发送成功返回 201 状态码
            return response()->json($notification, 201);
        } catch (\Exception $e) {
            // 发送失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
