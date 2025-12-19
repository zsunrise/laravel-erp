<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
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

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ]);
    }
}
