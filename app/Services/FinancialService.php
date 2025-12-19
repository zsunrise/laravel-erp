<?php

namespace App\Services;

use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\GeneralLedger;
use App\Models\AccountsReceivable;
use App\Models\AccountsPayable;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    public function createVoucher($data)
    {
        return DB::transaction(function () use ($data) {
            $voucher = AccountingVoucher::create([
                'voucher_no' => 'V' . date('YmdHis') . rand(1000, 9999),
                'voucher_date' => $data['voucher_date'],
                'type' => $data['type'] ?? 'general',
                'attachment_count' => $data['attachment_count'] ?? 0,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'remark' => $data['remark'] ?? null,
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($data['items'] as $itemData) {
                $voucher->items()->create([
                    'account_id' => $itemData['account_id'],
                    'direction' => $itemData['direction'],
                    'amount' => $itemData['amount'],
                    'summary' => $itemData['summary'] ?? null,
                    'reference_type' => $itemData['reference_type'] ?? null,
                    'reference_id' => $itemData['reference_id'] ?? null,
                    'reference_no' => $itemData['reference_no'] ?? null,
                    'sequence' => $itemData['sequence'] ?? 0,
                ]);

                if ($itemData['direction'] == 'debit') {
                    $totalDebit += $itemData['amount'];
                } else {
                    $totalCredit += $itemData['amount'];
                }
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \Exception('借贷不平衡，借方总额：' . $totalDebit . '，贷方总额：' . $totalCredit);
            }

            return $voucher->load(['items.account', 'creator']);
        });
    }

    public function postVoucher($voucherId)
    {
        $voucher = AccountingVoucher::with('items')->findOrFail($voucherId);

        if ($voucher->status != 'draft') {
            throw new \Exception('凭证状态不允许过账');
        }

        if (!$voucher->isBalanced()) {
            throw new \Exception('凭证借贷不平衡，无法过账');
        }

        return DB::transaction(function () use ($voucher) {
            foreach ($voucher->items as $item) {
                $account = ChartOfAccount::find($item->account_id);
                
                $lastBalance = GeneralLedger::where('account_id', $account->id)
                    ->where('entry_date', '<=', $voucher->voucher_date)
                    ->orderBy('entry_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->value('balance') ?? 0;

                $balance = $lastBalance;
                if ($item->direction == 'debit') {
                    $balance += $item->amount;
                } else {
                    $balance -= $item->amount;
                }

                GeneralLedger::create([
                    'account_id' => $account->id,
                    'entry_date' => $voucher->voucher_date,
                    'voucher_id' => $voucher->id,
                    'voucher_no' => $voucher->voucher_no,
                    'summary' => $item->summary,
                    'debit_amount' => $item->direction == 'debit' ? $item->amount : 0,
                    'credit_amount' => $item->direction == 'credit' ? $item->amount : 0,
                    'balance' => $balance,
                    'reference_type' => $item->reference_type,
                    'reference_id' => $item->reference_id,
                    'reference_no' => $item->reference_no,
                ]);
            }

            $voucher->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $voucher->load(['items.account', 'poster']);
        });
    }

    public function createReceivable($data)
    {
        return DB::transaction(function () use ($data) {
            $receivable = AccountsReceivable::create([
                'customer_id' => $data['customer_id'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'original_amount' => $data['original_amount'],
                'received_amount' => $data['received_amount'] ?? 0,
                'currency_id' => $data['currency_id'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            return $receivable->load(['customer', 'currency']);
        });
    }

    public function receivePayment($receivableId, $amount)
    {
        $receivable = AccountsReceivable::findOrFail($receivableId);

        if ($receivable->remaining_amount < $amount) {
            throw new \Exception('收款金额不能大于剩余金额');
        }

        return DB::transaction(function () use ($receivable, $amount) {
            $receivable->increment('received_amount', $amount);
            $receivable->save();

            return $receivable->load(['customer', 'currency']);
        });
    }

    public function createPayable($data)
    {
        return DB::transaction(function () use ($data) {
            $payable = AccountsPayable::create([
                'supplier_id' => $data['supplier_id'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'original_amount' => $data['original_amount'],
                'paid_amount' => $data['paid_amount'] ?? 0,
                'currency_id' => $data['currency_id'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            return $payable->load(['supplier', 'currency']);
        });
    }

    public function makePayment($payableId, $amount)
    {
        $payable = AccountsPayable::findOrFail($payableId);

        if ($payable->remaining_amount < $amount) {
            throw new \Exception('付款金额不能大于剩余金额');
        }

        return DB::transaction(function () use ($payable, $amount) {
            $payable->increment('paid_amount', $amount);
            $payable->save();

            return $payable->load(['supplier', 'currency']);
        });
    }

    public function getAccountBalance($accountId, $startDate = null, $endDate = null)
    {
        $query = GeneralLedger::where('account_id', $accountId);

        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }

        $debit = $query->sum('debit_amount');
        $credit = $query->sum('credit_amount');
        $balance = $debit - $credit;

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
        ];
    }
}

