<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with(['region']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:warehouses,code',
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validated['is_default'] ?? false) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create($validated);

        return response()->json($warehouse->load('region'), 201);
    }

    public function show($id)
    {
        $warehouse = Warehouse::with(['region', 'locations'])->findOrFail($id);
        return ApiResponse::success($warehouse, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:warehouses,code,' . $id,
            'region_id' => 'nullable|exists:regions,id',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        if (($validated['is_default'] ?? false) && !$warehouse->is_default) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse->update($validated);

        return response()->json($warehouse->load('region'));
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        if ($warehouse->inventory()->count() > 0) {
            return response()->json(['message' => '该仓库下有库存，无法删除'], 400);
        }

        $warehouse->delete();

        return response()->json(['message' => '仓库删除成功']);
    }
}
