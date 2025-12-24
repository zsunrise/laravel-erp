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
        $query = Notification::where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取未读通知列表
     *
     * @return \Illuminate\Http\JsonResponse 返回当前登录用户的所有未读通知列表
     */
    public function unread()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->where('status', 'unread')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * 获取未读通知数量
     *
     * @return \Illuminate\Http\JsonResponse 返回当前登录用户的未读通知数量
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());
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
        $notification = Notification::where('user_id', auth()->id())
            ->with(['user', 'logs'])
            ->findOrFail($id);

        if ($notification->status == 'unread') {
            $notification->markAsRead();
        }

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
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification = $this->notificationService->markAsRead($notification->id);
        return response()->json($notification);
    }

    /**
     * 标记所有通知为已读
     *
     * @return \Illuminate\Http\JsonResponse 返回标记结果消息
     */
    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(auth()->id());
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
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['status' => 'deleted']);
        return response()->json(['message' => '消息删除成功']);
    }

    /**
     * 发送通知
     *
     * @param Request $request 请求对象，包含通知信息（用户ID、类型、标题、内容等）
     * @return \Illuminate\Http\JsonResponse 返回创建的通知信息，状态码 201，失败时返回错误消息
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:system,approval,order,inventory,financial',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'channel' => 'sometimes|in:system,email,sms,push',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        try {
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
            return response()->json($notification, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 使用模板发送通知
     *
     * @param Request $request 请求对象，包含用户ID、模板编码和模板数据
     * @return \Illuminate\Http\JsonResponse 返回创建的通知信息，状态码 201，失败时返回错误消息
     */
    public function sendByTemplate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'template_code' => 'required|exists:notification_templates,code',
            'data' => 'required|array',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
        ]);

        try {
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
            return response()->json($notification, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
