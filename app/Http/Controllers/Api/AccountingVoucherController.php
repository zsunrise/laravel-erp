<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingVoucher;
use App\Services\FinancialService;
use Illuminate\Http\Request;

class AccountingVoucherController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request)
    {
        $query = AccountingVoucher::with(['creator', 'poster']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date')) {
            $query->whereDate('voucher_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('voucher_date', '<=', $request->end_date);
        }

        return response()->json($query->orderBy('voucher_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'voucher_date' => 'required|date',
            'type' => 'sometimes|in:general,adjustment,closing',
            'attachment_count' => 'nullable|integer|min:0',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.direction' => 'required|in:debit,credit',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.summary' => 'nullable|string',
            'items.*.reference_type' => 'nullable|string',
            'items.*.reference_id' => 'nullable|integer',
            'items.*.reference_no' => 'nullable|string',
            'items.*.sequence' => 'nullable|integer|min:0',
        ]);

        try {
            $voucher = $this->financialService->createVoucher($validated);
            return response()->json($voucher, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $voucher = AccountingVoucher::with(['items.account', 'creator', 'poster'])->findOrFail($id);
        return response()->json($voucher);
    }

    public function update(Request $request, $id)
    {
        $voucher = AccountingVoucher::findOrFail($id);

        if ($voucher->status != 'draft') {
            return response()->json(['message' => '只能修改草稿状态的凭证'], 400);
        }

        $validated = $request->validate([
            'voucher_date' => 'sometimes|required|date',
            'type' => 'sometimes|in:general,adjustment,closing',
            'attachment_count' => 'nullable|integer|min:0',
            'remark' => 'nullable|string',
            'items' => 'sometimes|required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.direction' => 'required|in:debit,credit',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.summary' => 'nullable|string',
            'items.*.reference_type' => 'nullable|string',
            'items.*.reference_id' => 'nullable|integer',
            'items.*.reference_no' => 'nullable|string',
            'items.*.sequence' => 'nullable|integer|min:0',
        ]);

        try {
            if (isset($validated['items'])) {
                $voucher->items()->delete();
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($validated['items'] as $itemData) {
                    $voucher->items()->create($itemData);
                    if ($itemData['direction'] == 'debit') {
                        $totalDebit += $itemData['amount'];
                    } else {
                        $totalCredit += $itemData['amount'];
                    }
                }

                if (abs($totalDebit - $totalCredit) > 0.01) {
                    throw new \Exception('借贷不平衡');
                }
            }

            $voucher->update($validated);

            return response()->json($voucher->load(['items.account', 'creator']));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $voucher = AccountingVoucher::findOrFail($id);

        if ($voucher->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的凭证'], 400);
        }

        $voucher->delete();

        return response()->json(['message' => '凭证删除成功']);
    }

    public function post($id)
    {
        try {
            $voucher = $this->financialService->postVoucher($id);
            return response()->json($voucher);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
