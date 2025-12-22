<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AccountsPayable;
use App\Services\FinancialService;
use Illuminate\Http\Request;

class AccountsPayableController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request)
    {
        $query = AccountsPayable::with(['supplier', 'currency']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('overdue')) {
            $query->where('status', 'overdue');
        }

        return response()->json($query->orderBy('due_date', 'asc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'original_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
        ]);

        try {
            $payable = $this->financialService->createPayable($validated);
            return response()->json($payable, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $payable = AccountsPayable::with(['supplier', 'currency'])->findOrFail($id);
        return ApiResponse::success($payable, '获取成功');
    }

    public function makePayment($id, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $payable = $this->financialService->makePayment($id, $validated['amount']);
            return response()->json($payable);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
