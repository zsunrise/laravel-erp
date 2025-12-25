<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Currency;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\AccountsPayable;
use App\Models\AccountsReceivable;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * 获取货币列表
     *
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 1000
     * @param Request $request 请求对象，支持 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的货币列表，默认按是否默认排序
     */
    public function index(Request $request)
    {
        // 构建查询
        $query = Currency::query();

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 搜索功能：按名称或代码搜索
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 如果没有指定is_active，默认只返回激活状态的货币
        if (!$request->has('is_active')) {
            $query->where('is_active', true);
        }

        // 返回货币列表，默认货币排在前面
        return response()->json($query->orderBy('is_default', 'desc')->orderBy('code')->paginate($request->get('per_page', 1000)));
    }

    /**
     * 创建新币种
     *
     * @bodyParam name string required 币种名称 Example: 人民币
     * @bodyParam code string required 币种代码（唯一，3位） Example: CNY
     * @bodyParam symbol string 币种符号 Example: ¥
     * @bodyParam exchange_rate decimal 汇率 Example: 1.0000
     * @bodyParam is_default boolean 是否默认币种 Example: false
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含币种信息
     * @return \Illuminate\Http\JsonResponse 返回创建的币种信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证币种信息参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                           // 币种名称（必填）
            'code' => 'required|string|max:3|unique:currencies,code',     // 币种代码（唯一，最多3位）
            'symbol' => 'nullable|string|max:10',                          // 币种符号
            'exchange_rate' => 'nullable|numeric|min:0',                   // 汇率
            'is_default' => 'sometimes|boolean',                          // 是否默认
            'is_active' => 'sometimes|boolean',                          // 是否激活
        ]);

        // 如果设置为默认币种，清除其他币种的默认标记
        if ($validated['is_default'] ?? false) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        // 创建币种记录
        $currency = Currency::create($validated);

        // 返回新建币种信息
        return response()->json($currency, 201);
    }

    /**
     * 获取指定货币详情
     *
     * @param int $id 货币ID
     * @return \Illuminate\Http\JsonResponse 返回货币详细信息
     */
    public function show($id)
    {
        // 根据ID查询货币，找不到则抛出404
        $currency = Currency::findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($currency, '获取成功');
    }

    /**
     * 更新币种信息
     *
     * @bodyParam name string 币种名称 Example: 人民币
     * @bodyParam code string 币种代码（唯一，排除当前币种） Example: CNY
     * @bodyParam symbol string 币种符号 Example: ¥
     * @bodyParam exchange_rate decimal 汇率 Example: 1.0000
     * @bodyParam is_default boolean 是否默认币种 Example: false
     * @bodyParam is_active boolean 是否激活 Example: true
     * @param Request $request 请求对象，包含要更新的币种字段
     * @param int $id 币种ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的币种信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询币种
        $currency = Currency::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:3|unique:currencies,code,' . $id,
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // 如果设置为默认币种且当前不是默认，清除其他币种的默认标记
        if (($validated['is_default'] ?? false) && !$currency->is_default) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        // 更新币种信息
        $currency->update($validated);

        // 返回更新后的币种信息
        return response()->json($currency);
    }

    /**
     * 删除币种
     *
     * @param int $id 币种ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果币种被使用或为默认币种则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询币种
        $currency = Currency::findOrFail($id);

        // 检查是否为默认币种，默认币种不允许删除
        if ($currency->is_default) {
            return response()->json(['message' => '默认币种不允许删除'], 400);
        }

        // 检查是否有关联的采购订单，有则不允许删除
        if (PurchaseOrder::where('currency_id', $id)->count() > 0) {
            return response()->json(['message' => '该币种下有关联的采购订单，无法删除'], 400);
        }

        // 检查是否有关联的销售订单，有则不允许删除
        if (SalesOrder::where('currency_id', $id)->count() > 0) {
            return response()->json(['message' => '该币种下有关联的销售订单，无法删除'], 400);
        }

        // 检查是否有关联的应付账款，有则不允许删除
        if (AccountsPayable::where('currency_id', $id)->count() > 0) {
            return response()->json(['message' => '该币种下有关联的应付账款，无法删除'], 400);
        }

        // 检查是否有关联的应收账款，有则不允许删除
        if (AccountsReceivable::where('currency_id', $id)->count() > 0) {
            return response()->json(['message' => '该币种下有关联的应收账款，无法删除'], 400);
        }

        // 删除币种记录
        $currency->delete();

        // 返回删除成功消息
        return response()->json(['message' => '币种删除成功']);
    }
}

