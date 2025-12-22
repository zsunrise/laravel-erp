<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::with(['region']);

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:suppliers,code',
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

        $supplier = Supplier::create($validated);

        return response()->json($supplier->load('region'), 201);
    }

    public function show($id)
    {
        $supplier = Supplier::with(['region'])->findOrFail($id);
        return ApiResponse::success($supplier, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:255|unique:suppliers,code,' . $id,
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

        $supplier->update($validated);

        return response()->json($supplier->load('region'));
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchaseOrders()->count() > 0) {
            return response()->json(['message' => '该供应商下有采购订单，无法删除'], 400);
        }

        $supplier->delete();

        return response()->json(['message' => '供应商删除成功']);
    }
}
