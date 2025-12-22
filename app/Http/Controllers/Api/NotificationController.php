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

    public function unread()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->where('status', 'unread')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());
        return response()->json(['count' => $count]);
    }

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

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification = $this->notificationService->markAsRead($notification->id);
        return response()->json($notification);
    }

    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(auth()->id());
        return response()->json(['message' => "已标记 {$count} 条消息为已读"]);
    }

    public function destroy($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['status' => 'deleted']);
        return response()->json(['message' => '消息删除成功']);
    }

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
