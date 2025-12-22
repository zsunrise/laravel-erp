<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AccountsReceivable;
use App\Services\FinancialService;
use Illuminate\Http\Request;

class AccountsReceivableController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request)
    {
        $query = AccountsReceivable::with(['customer', 'currency']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
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
            'customer_id' => 'required|exists:customers,id',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'original_amount' => 'required|numeric|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
        ]);

        try {
            $receivable = $this->financialService->createReceivable($validated);
            return response()->json($receivable, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $receivable = AccountsReceivable::with(['customer', 'currency'])->findOrFail($id);
        return ApiResponse::success($receivable, '获取成功');
    }

    public function receivePayment($id, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $receivable = $this->financialService->receivePayment($id, $validated['amount']);
            return response()->json($receivable);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function ageAnalysis(Request $request)
    {
        $query = AccountsReceivable::with(['customer'])
            ->where('status', '!=', 'settled');

        $ageGroups = [
            '0-30' => $query->clone()->where('age_days', '<=', 30)->sum('remaining_amount'),
            '31-60' => $query->clone()->whereBetween('age_days', [31, 60])->sum('remaining_amount'),
            '61-90' => $query->clone()->whereBetween('age_days', [61, 90])->sum('remaining_amount'),
            '90+' => $query->clone()->where('age_days', '>', 90)->sum('remaining_amount'),
        ];

        return response()->json($ageGroups);
    }
}
