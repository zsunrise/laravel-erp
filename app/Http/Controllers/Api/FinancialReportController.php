<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    /**
     * 资产负债表
     *
     * @param Request $request 请求对象，支持 date（报表日期，默认为当前日期）
     * @return \Illuminate\Http\JsonResponse 返回资产负债表数据，包含资产、负债、所有者权益及其合计
     */
    public function balanceSheet(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $assets = ChartOfAccount::where('type', 'asset')
            ->with(['ledgerEntries' => function($q) use ($date) {
                $q->where('entry_date', '<=', $date);
            }])
            ->get()
            ->map(function($account) {
                $balance = $account->debit_balance;
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            });

        $liabilities = ChartOfAccount::where('type', 'liability')
            ->with(['ledgerEntries' => function($q) use ($date) {
                $q->where('entry_date', '<=', $date);
            }])
            ->get()
            ->map(function($account) {
                $balance = abs($account->debit_balance);
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            });

        $equity = ChartOfAccount::where('type', 'equity')
            ->with(['ledgerEntries' => function($q) use ($date) {
                $q->where('entry_date', '<=', $date);
            }])
            ->get()
            ->map(function($account) {
                $balance = abs($account->debit_balance);
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            });

        return response()->json([
            'date' => $date,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets->sum('balance'),
            'total_liabilities' => $liabilities->sum('balance'),
            'total_equity' => $equity->sum('balance'),
        ]);
    }

    /**
     * 利润表（损益表）
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回利润表数据，包含收入、费用、利润和利润率统计
     */
    public function incomeStatement(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $revenue = ChartOfAccount::where('type', 'revenue')
            ->with(['ledgerEntries' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('entry_date', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($account) {
                $credit = $account->ledgerEntries->sum('credit_amount');
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $credit,
                ];
            });

        $expenses = ChartOfAccount::where('type', 'expense')
            ->with(['ledgerEntries' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('entry_date', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($account) {
                $debit = $account->ledgerEntries->sum('debit_amount');
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $debit,
                ];
            });

        $totalRevenue = $revenue->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netIncome = $totalRevenue - $totalExpenses;
        $profitRate = $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 2) : 0;

        $stats = [
            'revenue' => $totalRevenue,
            'cost' => $totalExpenses,
            'profit' => $netIncome,
            'profit_rate' => $profitRate,
        ];

        return ApiResponse::success([
            'stats' => $stats,
            'revenue' => $revenue,
            'expenses' => $expenses,
        ], '获取成功');
    }
}
