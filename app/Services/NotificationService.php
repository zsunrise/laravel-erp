<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function send($userId, $type, $title, $content, $options = [])
    {
        $channel = $options['channel'] ?? 'system';
        $priority = $options['priority'] ?? 'normal';
        $referenceType = $options['reference_type'] ?? null;
        $referenceId = $options['reference_id'] ?? null;
        $referenceNo = $options['reference_no'] ?? null;
        $data = $options['data'] ?? null;

        return DB::transaction(function () use ($userId, $type, $title, $content, $channel, $priority, $referenceType, $referenceId, $referenceNo, $data) {
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'channel' => $channel,
                'priority' => $priority,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'data' => $data,
            ]);

            if ($channel != 'system') {
                $this->sendToChannel($notification, $channel);
            }

            return $notification;
        });
    }

    public function sendByTemplate($userId, $templateCode, $data = [], $options = [])
    {
        $template = NotificationTemplate::where('code', $templateCode)
            ->where('is_active', true)
            ->firstOrFail();

        $rendered = $template->render($data);
        $title = $rendered['subject'] ?? $template->name;
        $content = $rendered['content'];

        return $this->send(
            $userId,
            $template->type,
            $title,
            $content,
            array_merge([
                'channel' => $template->channel,
            ], $options)
        );
    }

    public function sendToUsers($userIds, $type, $title, $content, $options = [])
    {
        $notifications = [];

        foreach ($userIds as $userId) {
            $notifications[] = $this->send($userId, $type, $title, $content, $options);
        }

        return $notifications;
    }

    public function sendToRole($roleId, $type, $title, $content, $options = [])
    {
        $users = User::whereHas('roles', function($query) use ($roleId) {
            $query->where('roles.id', $roleId);
        })->pluck('id');

        return $this->sendToUsers($users, $type, $title, $content, $options);
    }

    protected function sendToChannel(Notification $notification, $channel)
    {
        $user = $notification->user;

        try {
            switch ($channel) {
                case 'email':
                    if ($user->email) {
                        Mail::raw($notification->content, function($message) use ($notification, $user) {
                            $message->to($user->email)
                                   ->subject($notification->title);
                        });

                        NotificationLog::create([
                            'notification_id' => $notification->id,
                            'channel' => 'email',
                            'recipient_type' => 'email',
                            'recipient' => $user->email,
                            'status' => 'sent',
                            'content' => $notification->content,
                            'sent_at' => now(),
                        ]);
                    }
                    break;

                case 'sms':
                    if ($user->phone) {
                        // 这里需要集成短信服务商API
                        // 示例：调用短信服务
                        // $this->sendSMS($user->phone, $notification->content);

                        NotificationLog::create([
                            'notification_id' => $notification->id,
                            'channel' => 'sms',
                            'recipient_type' => 'phone',
                            'recipient' => $user->phone,
                            'status' => 'sent',
                            'content' => $notification->content,
                            'sent_at' => now(),
                        ]);
                    }
                    break;

                case 'push':
                    // 这里需要集成推送服务（如Firebase、极光推送等）
                    // 示例：调用推送服务
                    // $this->sendPush($user->id, $notification->title, $notification->content);

                    NotificationLog::create([
                        'notification_id' => $notification->id,
                        'channel' => 'push',
                        'recipient_type' => 'user',
                        'recipient' => $user->id,
                        'status' => 'sent',
                        'content' => $notification->content,
                        'sent_at' => now(),
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            NotificationLog::create([
                'notification_id' => $notification->id,
                'channel' => $channel,
                'recipient_type' => $channel == 'email' ? 'email' : ($channel == 'sms' ? 'phone' : 'user'),
                'recipient' => $channel == 'email' ? $user->email : ($channel == 'sms' ? $user->phone : $user->id),
                'status' => 'failed',
                'content' => $notification->content,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Notification send failed', [
                'notification_id' => $notification->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function markAsRead($notificationId)
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->markAsRead();
        return $notification;
    }

    public function markAllAsRead($userId)
    {
        return Notification::where('user_id', $userId)
            ->where('status', 'unread')
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
    }

    public function getUnreadCount($userId)
    {
        return Notification::where('user_id', $userId)
            ->where('status', 'unread')
            ->count();
    }
}

