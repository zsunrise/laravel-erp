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
        // 注入财务服务
        $this->financialService = $financialService;
    }

    /**
     * 获取总账列表
     *
     * @param Request $request 请求对象，支持 account_id（科目ID）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的总账列表，包含科目和凭证信息，按日期和ID降序排列
     */
    public function index(Request $request)
    {
        // 构建查询，预加载科目和凭证信息
        $query = GeneralLedger::with(['account', 'voucher']);

        // 按科目ID筛选
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // 按日期范围筛选：开始日期
        if ($request->has('start_date')) {
            $query->whereDate('entry_date', '>=', $request->start_date);
        }

        // 按日期范围筛选：结束日期
        if ($request->has('end_date')) {
            $query->whereDate('entry_date', '<=', $request->end_date);
        }

        // 按分录日期和ID倒序排列，返回分页结果
        return response()->json($query->orderBy('entry_date', 'desc')->orderBy('id', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取科目余额
     *
     * @param int $accountId 科目ID
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回指定科目的余额信息
     */
    public function accountBalance($accountId, Request $request)
    {
        // 调用财务服务获取科目余额
        $balance = $this->financialService->getAccountBalance(
            $accountId,
            $request->start_date,  // 开始日期
            $request->end_date     // 结束日期
        );

        // 返回科目余额信息（包含期初、本期借贷发生额、期末余额）
        return response()->json($balance);
    }
}
