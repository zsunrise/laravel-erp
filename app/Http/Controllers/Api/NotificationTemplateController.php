<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    /**
     * 获取通知模板列表
     *
     * @param Request $request 请求对象，支持 type（类型）、channel（渠道）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的通知模板列表，包含创建人信息
     */
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

    /**
     * 创建通知模板
     *
     * @param Request $request 请求对象，包含模板信息（编码、名称、类型、渠道、内容等）
     * @return \Illuminate\Http\JsonResponse 返回创建的模板信息，状态码 201
     */
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

    /**
     * 获取指定模板详情
     *
     * @param int $id 模板ID
     * @return \Illuminate\Http\JsonResponse 返回模板详细信息，包含创建人信息
     */
    public function show($id)
    {
        $template = NotificationTemplate::with(['creator'])->findOrFail($id);
        return ApiResponse::success($template, '获取成功');
    }

    /**
     * 更新通知模板
     *
     * @param Request $request 请求对象，包含要更新的模板字段
     * @param int $id 模板ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的模板信息
     */
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

    /**
     * 删除通知模板
     *
     * @param int $id 模板ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => '模板删除成功']);
    }

    /**
     * 预览通知模板
     *
     * @param int $id 模板ID
     * @param Request $request 请求对象，包含模板数据
     * @return \Illuminate\Http\JsonResponse 返回渲染后的模板内容
     */
    public function preview($id, Request $request)
    {
        $template = NotificationTemplate::findOrFail($id);
        $data = $request->get('data', []);
        $rendered = $template->render($data);

        return response()->json($rendered);
    }
}
