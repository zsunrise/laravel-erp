<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * 获取客户列表
     *
     * @param Request $request 请求对象，支持 search（搜索关键词）、is_active（是否激活）和 rating（评级）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的客户列表，包含区域信息
     */
    public function index(Request $request)
    {
        $query = Customer::with(['region']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新客户
     *
     * @param Request $request 请求对象，包含客户信息（编码、名称、联系方式等）
     * @return \Illuminate\Http\JsonResponse 返回创建的客户信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:customers,code',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'rating' => 'sometimes|in:A,B,C,D',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_days' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'remark' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer->load('region'), 201);
    }

    /**
     * 获取指定客户详情
     *
     * @param int $id 客户ID
     * @return \Illuminate\Http\JsonResponse 返回客户详细信息，包含区域信息
     */
    public function show($id)
    {
        $customer = Customer::with(['region'])->findOrFail($id);
        return ApiResponse::success($customer, '获取成功');
    }

    /**
     * 更新客户信息
     *
     * @param Request $request 请求对象，包含要更新的客户字段
     * @param int $id 客户ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的客户信息
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:255|unique:customers,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'rating' => 'sometimes|in:A,B,C,D',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_days' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'remark' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json($customer->load('region'));
    }

    /**
     * 删除客户
     *
     * @param int $id 客户ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果客户下有销售订单则返回错误消息
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->salesOrders()->count() > 0) {
            return response()->json(['message' => '该客户下有销售订单，无法删除'], 400);
        }

        $customer->delete();

        return response()->json(['message' => '客户删除成功']);
    }
}
