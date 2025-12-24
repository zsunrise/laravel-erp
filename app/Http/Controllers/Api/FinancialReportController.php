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
        // 获取报表日期，默认为当前日期
        $date = $request->date ?? now()->toDateString();

        // 查询资产类科目及其余额
        $assets = ChartOfAccount::where('type', 'asset')
            ->with(['ledgerEntries' => function($q) use ($date) {
                $q->where('entry_date', '<=', $date);  // 只查询截至报表日期的分录
            }])
            ->get()
            ->map(function($account) {
                $balance = $account->debit_balance;  // 资产类使用借方余额
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            });

        // 查询负债类科目及其余额
        $liabilities = ChartOfAccount::where('type', 'liability')
            ->with(['ledgerEntries' => function($q) use ($date) {
                $q->where('entry_date', '<=', $date);
            }])
            ->get()
            ->map(function($account) {
                $balance = abs($account->debit_balance);  // 负债类取绝对值
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            });

        // 查询所有者权益类科目及其余额
        $equity = ChartOfAccount::where('type', 'equity')
            ->with(['ledgerEntries' => function($q) use ($date) {
                $q->where('entry_date', '<=', $date);
            }])
            ->get()
            ->map(function($account) {
                $balance = abs($account->debit_balance);  // 所有者权益取绝对值
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            });

        // 返回资产负债表数据
        return response()->json([
            'date' => $date,
            'assets' => $assets,                        // 资产明细
            'liabilities' => $liabilities,              // 负债明细
            'equity' => $equity,                        // 所有者权益明细
            'total_assets' => $assets->sum('balance'), // 资产合计
            'total_liabilities' => $liabilities->sum('balance'), // 负债合计
            'total_equity' => $equity->sum('balance'), // 所有者权益合计
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
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 查询收入类科目及其发生额
        $revenue = ChartOfAccount::where('type', 'revenue')
            ->with(['ledgerEntries' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('entry_date', [$startDate, $endDate]);  // 查询期间内的分录
            }])
            ->get()
            ->map(function($account) {
                $credit = $account->ledgerEntries->sum('credit_amount');  // 收入用贷方发生额
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $credit,
                ];
            });

        // 查询费用类科目及其发生额
        $expenses = ChartOfAccount::where('type', 'expense')
            ->with(['ledgerEntries' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('entry_date', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($account) {
                $debit = $account->ledgerEntries->sum('debit_amount');  // 费用用借方发生额
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $debit,
                ];
            });

        // 计算利润统计数据
        $totalRevenue = $revenue->sum('amount');   // 总收入
        $totalExpenses = $expenses->sum('amount'); // 总费用
        $netIncome = $totalRevenue - $totalExpenses; // 净利润
        $profitRate = $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 2) : 0; // 利润率

        $stats = [
            'revenue' => $totalRevenue,     // 营业收入
            'cost' => $totalExpenses,       // 营业成本
            'profit' => $netIncome,         // 净利润
            'profit_rate' => $profitRate,   // 利润率(%)
        ];

        // 返回利润表数据
        return ApiResponse::success([
            'stats' => $stats,
            'revenue' => $revenue,   // 收入明细
            'expenses' => $expenses, // 费用明细
        ], '获取成功');
    }
}
