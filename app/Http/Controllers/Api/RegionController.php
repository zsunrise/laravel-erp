<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $query = Region::query();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('tree')) {
            $regions = Region::whereNull('parent_id')
                ->with('children')
                ->where('is_active', true)
                ->orderBy('sort')
                ->get();
            return response()->json($regions);
        }

        return response()->json($query->where('is_active', true)->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    public function show($id)
    {
        $region = Region::with(['parent', 'children'])->findOrFail($id);
        return response()->json($region);
    }
}

