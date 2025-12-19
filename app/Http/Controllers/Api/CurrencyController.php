<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Currency::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->where('is_active', true)->orderBy('is_default', 'desc')->paginate($request->get('per_page', 1000)));
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        return response()->json($currency);
    }
}

