<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::with(['creator']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:notification_templates,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:system,approval,order,inventory,financial',
            'channel' => 'required|in:system,email,sms,push',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $template = NotificationTemplate::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'],
            'variables' => $validated['variables'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        return response()->json($template->load('creator'), 201);
    }

    public function show($id)
    {
        $template = NotificationTemplate::with(['creator'])->findOrFail($id);
        return ApiResponse::success($template, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|unique:notification_templates,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:system,approval,order,inventory,financial',
            'channel' => 'sometimes|required|in:system,email,sms,push',
            'subject' => 'nullable|string|max:255',
            'content' => 'sometimes|required|string',
            'variables' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json($template->load('creator'));
    }

    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => '模板删除成功']);
    }

    public function preview($id, Request $request)
    {
        $template = NotificationTemplate::findOrFail($id);
        $data = $request->get('data', []);
        $rendered = $template->render($data);

        return response()->json($rendered);
    }
}
