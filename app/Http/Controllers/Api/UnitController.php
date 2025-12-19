<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->where('is_active', true)->orderBy('sort')->paginate($request->get('per_page', 1000)));
    }

    public function show($id)
    {
        $unit = Unit::findOrFail($id);
        return response()->json($unit);
    }
}

