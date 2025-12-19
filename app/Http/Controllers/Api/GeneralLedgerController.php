<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedger;
use App\Services\FinancialService;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request)
    {
        $query = GeneralLedger::with(['account', 'voucher']);

        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('entry_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('entry_date', '<=', $request->end_date);
        }

        return response()->json($query->orderBy('entry_date', 'desc')->orderBy('id', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function accountBalance($accountId, Request $request)
    {
        $balance = $this->financialService->getAccountBalance(
            $accountId,
            $request->start_date,
            $request->end_date
        );

        return response()->json($balance);
    }
}
