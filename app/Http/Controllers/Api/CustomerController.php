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
     * @queryParam search string 搜索关键词（按名称/编码/联系人/电话模糊匹配） Example: 华为
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam rating string 客户评级（A/B/C/D） Example: A
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 search（搜索关键词）、is_active（是否激活）和 rating（评级）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的客户列表，包含区域信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载区域信息
        $query = Customer::with(['region']);

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按客户评级筛选（A/B/C/D）
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // 关键词搜索：按名称、编码、联系人、电话模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        // 返回分页结果
        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新客户
     *
     * @bodyParam code string required 客户编码（唯一） Example: CUS001
     * @bodyParam name string required 客户名称 Example: 华为技术有限公司
     * @bodyParam contact_person string 联系人 Example: 张三
     * @bodyParam contact_phone string 联系电话 Example: 13800138000
     * @bodyParam email string 邮箱 Example: contact@huawei.com
     * @bodyParam region_id integer 所属区域ID Example: 1
     * @bodyParam address string 地址 Example: 深圳市龙岗区坂田华为基地
     * @bodyParam tax_number string 税号 Example: 91440300192444025N
     * @bodyParam bank_name string 开户行 Example: 中国工商银行
     * @bodyParam bank_account string 银行账号 Example: 6222024000000000000
     * @bodyParam rating string 客户评级（A/B/C/D） Example: A
     * @bodyParam credit_limit number 信用额度 Example: 100000
     * @bodyParam payment_days integer 账期天数 Example: 30
     * @bodyParam is_active boolean 是否激活 Example: 1
     * @bodyParam remark string 备注 Example: 优质客户
     * @param Request $request 请求对象，包含客户信息（编码、名称、联系方式等）
     * @return \Illuminate\Http\JsonResponse 返回创建的客户信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证客户信息参数
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:customers,code',    // 客户编码（唯一）
            'name' => 'required|string|max:255',                          // 客户名称
            'contact_person' => 'nullable|string|max:255',                // 联系人
            'contact_phone' => 'nullable|string|max:20',                  // 联系电话
            'email' => 'nullable|email|max:255',                          // 邮箱
            'region_id' => 'nullable|exists:regions,id',                  // 所属区域
            'address' => 'nullable|string|max:500',                       // 地址
            'tax_number' => 'nullable|string|max:255',                    // 税号
            'bank_name' => 'nullable|string|max:255',                     // 开户行
            'bank_account' => 'nullable|string|max:255',                  // 银行账号
            'rating' => 'sometimes|in:A,B,C,D',                           // 客户评级
            'credit_limit' => 'nullable|numeric|min:0',                   // 信用额度
            'payment_days' => 'nullable|integer|min:0',                   // 账期天数
            'is_active' => 'sometimes|boolean',                           // 是否激活
            'remark' => 'nullable|string',                                // 备注
        ]);

        // 创建客户记录
        $customer = Customer::create($validated);

        // 返回新建客户信息（包含区域）
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
        // 根据ID查询客户，预加载区域信息，找不到则抛出404
        $customer = Customer::with(['region'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($customer, '获取成功');
    }

    /**
     * 更新客户信息
     *
     * @bodyParam code string 客户编码（唯一，排除当前客户） Example: CUS001
     * @bodyParam name string 客户名称 Example: 华为技术有限公司
     * @bodyParam contact_person string 联系人 Example: 张三
     * @bodyParam contact_phone string 联系电话 Example: 13800138000
     * @bodyParam email string 邮箱 Example: contact@huawei.com
     * @bodyParam region_id integer 所属区域ID Example: 1
     * @bodyParam address string 地址 Example: 深圳市龙岗区坂田华为基地
     * @bodyParam tax_number string 税号 Example: 91440300192444025N
     * @bodyParam bank_name string 开户行 Example: 中国工商银行
     * @bodyParam bank_account string 银行账号 Example: 6222024000000000000
     * @bodyParam rating string 客户评级（A/B/C/D） Example: A
     * @bodyParam credit_limit number 信用额度 Example: 100000
     * @bodyParam payment_days integer 账期天数 Example: 30
     * @bodyParam is_active boolean 是否激活 Example: 1
     * @bodyParam remark string 备注 Example: 优质客户
     * @param Request $request 请求对象，包含要更新的客户字段
     * @param int $id 客户ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的客户信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询客户，找不到则抛出404
        $customer = Customer::findOrFail($id);

        // 验证更新参数（编码唯一性排除当前记录）
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

        // 更新客户信息
        $customer->update($validated);

        // 返回更新后的客户信息
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
        // 根据ID查询客户
        $customer = Customer::findOrFail($id);

        // 检查是否有关联的销售订单，有则不允许删除
        if ($customer->salesOrders()->count() > 0) {
            return response()->json(['message' => '该客户下有销售订单，无法删除'], 400);
        }

        // 删除客户记录
        $customer->delete();

        // 返回删除成功消息
        return response()->json(['message' => '客户删除成功']);
    }
}
