<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 获取当前用户的消息列表
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Notification::where('user_id', $user->id)
            ->with('user');

        // 按状态筛选
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // 按类型筛选
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // 按优先级筛选
        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 10))
        );
    }

    /**
     * 获取未读消息列表
     */
    public function unread(Request $request)
    {
        $user = Auth::user();
        return response()->json(
            Notification::where('user_id', $user->id)
                ->where('status', 'unread')
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10))
        );
    }

    /**
     * 获取未读消息数量
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = Notification::where('user_id', $user->id)
            ->where('status', 'unread')
            ->count();

        return ApiResponse::success(['count' => $count], '获取成功');
    }

    /**
     * 获取指定消息详情
     */
    public function show($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)
            ->with('user')
            ->findOrFail($id);

        return ApiResponse::success($notification, '获取成功');
    }

    /**
     * 标记单条消息为已读
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);

        if ($notification->status == 'unread') {
            $notification->update([
                'status' => 'read',
                'read_at' => now()
            ]);
        }

        return ApiResponse::success($notification, '标记已读成功');
    }

    /**
     * 标记全部消息为已读
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)
            ->where('status', 'unread')
            ->update([
                'status' => 'read',
                'read_at' => now()
            ]);

        return ApiResponse::success(null, '全部标记已读成功');
    }

    /**
     * 删除消息
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $notification->delete();

        return ApiResponse::success(null, '删除成功');
    }

    /**
     * 发送消息
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'required|string|in:system,approval,order,inventory,financial',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'channel' => 'required|string|in:system,email,sms,push',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'reference_no' => 'nullable|string|max:255'
        ]);

        $notification = Notification::create([
            'user_id' => $validated['user_id'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'channel' => $validated['channel'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'unread',
            'reference_no' => $validated['reference_no']
        ]);

        return ApiResponse::success($notification, '发送成功');
    }

    /**
     * 根据模板发送消息
     */
    public function sendByTemplate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'template_id' => 'required|integer|exists:notification_templates,id',
            'data' => 'nullable|array'
        ]);

        // 获取模板
        $template = \App\Models\NotificationTemplate::findOrFail($validated['template_id']);

        // 替换变量
        $content = $template->content;
        $subject = $template->subject;
        if (!empty($validated['data'])) {
            foreach ($validated['data'] as $key => $value) {
                $content = str_replace('{' . $key . '}', $value, $content);
                $subject = str_replace('{' . $key . '}', $value, $subject);
            }
        }

        $notification = Notification::create([
            'user_id' => $validated['user_id'],
            'type' => $template->type,
            'title' => $subject,
            'content' => $content,
            'channel' => $template->channel,
            'priority' => 'normal',
            'status' => 'unread'
        ]);

        return ApiResponse::success($notification, '发送成功');
    }
}
