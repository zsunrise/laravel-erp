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
     * @queryParam type string 模板类型（system/approval/order/inventory/financial） Example: system
     * @queryParam channel string 发送渠道（system/email/sms/push） Example: email
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 type（类型）、channel（渠道）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的通知模板列表，包含创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载创建人信息
        $query = NotificationTemplate::with(['creator']);

        // 按模板类型筛选
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按发送渠道筛选
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建通知模板
     *
     * @bodyParam code string required 模板编码（唯一） Example: order_approved
     * @bodyParam name string required 模板名称 Example: 订单审批通过通知
     * @bodyParam type string required 模板类型（system/approval/order/inventory/financial） Example: order
     * @bodyParam channel string required 发送渠道（system/email/sms/push） Example: email
     * @bodyParam subject string 邮件主题 Example: 订单审批通过
     * @bodyParam content string required 模板内容 Example: 您的订单{{order_no}}已审批通过
     * @bodyParam variables array 模板变量定义 Example: 模板变量定义对象
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含模板信息（编码、名称、类型、渠道、内容等）
     * @return \Illuminate\Http\JsonResponse 返回创建的模板信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证模板参数
        $validated = $request->validate([
            'code' => 'required|string|unique:notification_templates,code', // 模板编码（唯一）
            'name' => 'required|string|max:255',                            // 模板名称（必填）
            'type' => 'required|in:system,approval,order,inventory,financial', // 类型
            'channel' => 'required|in:system,email,sms,push',               // 发送渠道
            'subject' => 'nullable|string|max:255',                         // 邮件主题
            'content' => 'required|string',                                 // 模板内容（必填）
            'variables' => 'nullable|array',                                // 模板变量定义
            'is_active' => 'sometimes|boolean',                             // 是否激活
        ]);

        // 创建模板记录
        $template = NotificationTemplate::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'],
            'variables' => $validated['variables'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(), // 记录创建人
        ]);

        // 返回新建模板信息
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
        // 根据ID查询模板，预加载创建人信息，找不到则抛出404
        $template = NotificationTemplate::with(['creator'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($template, '获取成功');
    }

    /**
     * 更新通知模板
     *
     * @bodyParam code string 模板编码（唯一，排除当前模板） Example: order_approved
     * @bodyParam name string 模板名称 Example: 订单审批通过通知
     * @bodyParam type string 模板类型（system/approval/order/inventory/financial） Example: order
     * @bodyParam channel string 发送渠道（system/email/sms/push） Example: email
     * @bodyParam subject string 邮件主题 Example: 订单审批通过
     * @bodyParam content string 模板内容 Example: 您的订单{{order_no}}已审批通过
     * @bodyParam variables array 模板变量定义 Example: 模板变量定义对象
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含要更新的模板字段
     * @param int $id 模板ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的模板信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询模板
        $template = NotificationTemplate::findOrFail($id);

        // 验证更新参数
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

        // 更新模板信息
        $template->update($validated);

        // 返回更新后的模板信息
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
        // 根据ID查询模板
        $template = NotificationTemplate::findOrFail($id);
        // 删除模板记录
        $template->delete();

        // 返回删除成功消息
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
        // 根据ID查询模板
        $template = NotificationTemplate::findOrFail($id);
        // 获取预览数据
        $data = $request->get('data', []);
        // 渲染模板内容
        $rendered = $template->render($data);

        // 返回渲染结果
        return response()->json($rendered);
    }
}
