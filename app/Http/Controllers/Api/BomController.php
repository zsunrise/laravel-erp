<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Bom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BomController extends Controller
{
    /**
     * 获取BOM列表
     *
     * @param Request $request 请求对象，支持 product_id（产品ID）、is_active（是否激活）、is_default（是否默认）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的BOM列表，包含产品和创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载产品和创建人信息
        $query = Bom::with(['product', 'creator']);

        // 按产品ID筛选
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按默认版本筛选
        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        // 关键词搜索：按BOM版本号、产品名称或SKU模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->leftJoin('products', 'boms.product_id', '=', 'products.id')
                  ->where(function($q) use ($search) {
                      $q->where('boms.version', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('products.sku', 'like', "%{$search}%");
                  })
                  ->select('boms.*')
                  ->distinct();
        }

        // 按生效日期倒序排列，返回分页结果
        return response()->json($query->orderBy('effective_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建BOM
     *
     * @param Request $request 请求对象，包含BOM信息和明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的BOM信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证BOM参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',        // 产品ID（必填）
            'version' => 'required|string|max:50',                // 版本号（必填）
            'effective_date' => 'required|date',                  // 生效日期（必填）
            'expiry_date' => 'nullable|date|after:effective_date', // 失效日期
            'is_default' => 'sometimes|boolean',                  // 是否默认版本
            'is_active' => 'sometimes|boolean',                   // 是否激活
            'description' => 'nullable|string',                   // 描述
            'items' => 'required|array|min:1',                    // 组件明细（至少一条）
            'items.*.component_product_id' => 'required|exists:products,id', // 组件产品ID
            'items.*.quantity' => 'required|numeric|min:0',       // 用量
            'items.*.unit_id' => 'nullable|exists:units,id',      // 单位
            'items.*.loss_rate' => 'nullable|numeric|min:0|max:100', // 损耗率
            'items.*.sequence' => 'nullable|integer|min:0',       // 序号
            'items.*.position' => 'nullable|string|max:100',      // 位置
            'items.*.remark' => 'nullable|string',                // 备注
        ]);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($validated) {
            // 如果设置为默认版本，先清除该产品其他BOM的默认标记
            if ($validated['is_default'] ?? false) {
                Bom::where('product_id', $validated['product_id'])
                    ->update(['is_default' => false]);
            }

            // 创建BOM主记录
            $bom = Bom::create([
                'product_id' => $validated['product_id'],
                'version' => $validated['version'],
                'effective_date' => $validated['effective_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(), // 记录创建人
            ]);

            // 创建BOM组件明细
            foreach ($validated['items'] as $itemData) {
                $bom->items()->create([
                    'component_product_id' => $itemData['component_product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_id' => $itemData['unit_id'] ?? null,
                    'loss_rate' => $itemData['loss_rate'] ?? 0,
                    'sequence' => $itemData['sequence'] ?? 0,
                    'position' => $itemData['position'] ?? null,
                    'remark' => $itemData['remark'] ?? null,
                ]);
            }

            // 返回新建BOM信息（包含关联）
            return response()->json($bom->load(['product', 'items.componentProduct', 'items.unit', 'creator']), 201);
        });
    }

    /**
     * 获取指定BOM详情
     *
     * @param int $id BOM ID
     * @return \Illuminate\Http\JsonResponse 返回BOM详细信息，包含产品、明细项和创建人信息
     */
    public function show($id)
    {
        // 根据ID查询BOM，预加载产品、组件明细和创建人信息
        $bom = Bom::with(['product', 'items.componentProduct', 'items.unit', 'creator'])
            ->findOrFail($id); // 找不到则抛出404异常
        // 返回标准化成功响应
        return ApiResponse::success($bom, '获取成功');
    }

    /**
     * 更新BOM信息
     *
     * @param Request $request 请求对象，包含要更新的BOM字段和明细项
     * @param int $id BOM ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的BOM信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询BOM
        $bom = Bom::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'version' => 'sometimes|required|string|max:50',
            'effective_date' => 'sometimes|required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.component_product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.loss_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.sequence' => 'nullable|integer|min:0',
            'items.*.position' => 'nullable|string|max:100',
            'items.*.remark' => 'nullable|string',
        ]);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($bom, $validated) {
            // 如果设置为默认版本，清除该产品其他BOM的默认标记
            if (isset($validated['is_default']) && $validated['is_default']) {
                Bom::where('product_id', $bom->product_id)
                    ->where('id', '!=', $bom->id)
                    ->update(['is_default' => false]);
            }

            // 更新BOM主记录
            $bom->update([
                'version' => $validated['version'] ?? $bom->version,
                'effective_date' => $validated['effective_date'] ?? $bom->effective_date,
                'expiry_date' => $validated['expiry_date'] ?? $bom->expiry_date,
                'is_default' => $validated['is_default'] ?? $bom->is_default,
                'is_active' => $validated['is_active'] ?? $bom->is_active,
                'description' => $validated['description'] ?? $bom->description,
            ]);

            // 如果提供了明细项，则重新创建
            if (isset($validated['items'])) {
                // 删除旧的明细项
                $bom->items()->delete();

                // 创建新的明细项
                foreach ($validated['items'] as $itemData) {
                    $bom->items()->create([
                        'component_product_id' => $itemData['component_product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_id' => $itemData['unit_id'] ?? null,
                        'loss_rate' => $itemData['loss_rate'] ?? 0,
                        'sequence' => $itemData['sequence'] ?? 0,
                        'position' => $itemData['position'] ?? null,
                        'remark' => $itemData['remark'] ?? null,
                    ]);
                }
            }

            // 返回更新后的BOM信息
            return response()->json($bom->load(['product', 'items.componentProduct', 'items.unit', 'creator']));
        });
    }

    /**
     * 删除BOM
     *
     * @param int $id BOM ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        // 根据ID查询BOM
        $bom = Bom::findOrFail($id);
        // 删除BOM记录（级联删除明细项）
        $bom->delete();

        // 返回删除成功消息
        return response()->json(['message' => 'BOM删除成功']);
    }

    /**
     * 设置BOM为默认版本
     *
     * @param int $id BOM ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的BOM信息
     */
    public function setDefault($id)
    {
        // 根据ID查询BOM
        $bom = Bom::findOrFail($id);

        // 使用事务确保数据一致性
        DB::transaction(function () use ($bom) {
            // 先清除该产品所有BOM的默认标记
            Bom::where('product_id', $bom->product_id)
                ->update(['is_default' => false]);

            // 将当前BOM设置为默认
            $bom->update(['is_default' => true]);
        });

        // 返回更新后的BOM信息
        return response()->json($bom->load(['product', 'items.componentProduct']));
    }

    /**
     * 复制BOM
     *
     * @param int $id BOM ID
     * @return \Illuminate\Http\JsonResponse 返回复制的BOM信息，状态码 201
     */
    public function copy($id)
    {
        // 根据ID查询源BOM，预加载明细项
        $sourceBom = Bom::with('items')->findOrFail($id);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($sourceBom) {
            // 复制BOM主记录
            $newBom = $sourceBom->replicate();
            $newBom->version = $sourceBom->version . '_copy'; // 版本号加后缀
            $newBom->is_default = false;                       // 新副BOM不是默认版本
            $newBom->created_by = auth()->id();               // 记录新的创建人
            $newBom->save();

            // 复制BOM明细项
            foreach ($sourceBom->items as $item) {
                $newItem = $item->replicate();
                $newItem->bom_id = $newBom->id; // 关联到新BOM
                $newItem->save();
            }

            // 返回复制后的BOM信息
            return response()->json($newBom->load(['product', 'items.componentProduct', 'items.unit', 'creator']), 201);
        });
    }
}
