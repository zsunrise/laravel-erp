<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class WarehouseLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseLocation::query();

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $warehouse = \App\Models\Warehouse::findOrFail($validated['warehouse_id']);
        
        $exists = WarehouseLocation::where('warehouse_id', $validated['warehouse_id'])
            ->where('code', $validated['code'])
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => '该仓库下已存在相同编码的库位'], 400);
        }

        $location = WarehouseLocation::create($validated);

        return response()->json($location, 201);
    }

    public function show($id)
    {
        $location = WarehouseLocation::findOrFail($id);
        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $location = WarehouseLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['code']) && $validated['code'] != $location->code) {
            $exists = WarehouseLocation::where('warehouse_id', $location->warehouse_id)
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();
                
            if ($exists) {
                return response()->json(['message' => '该仓库下已存在相同编码的库位'], 400);
            }
        }

        $location->update($validated);

        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = WarehouseLocation::findOrFail($id);
        $location->delete();

        return response()->json(['message' => '库位删除成功']);
    }
}

