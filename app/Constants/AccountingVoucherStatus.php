<?php

namespace App\Constants;

/**
 * 会计凭证状态常量
 */
class AccountingVoucherStatus
{
    // 状态值（数字）
    const DRAFT = 1;           // 草稿
    const PENDING = 2;         // 待审核
    const UNDER_REVIEW = 3;    // 审核中
    const APPROVED = 4;        // 已审核
    const REJECTED = 5;        // 已拒绝
    const POSTED = 6;          // 已过账
    const CANCELLED = 7;       // 已取消

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::DRAFT => '草稿',
        self::PENDING => '待审核',
        self::UNDER_REVIEW => '审核中',
        self::APPROVED => '已审核',
        self::REJECTED => '已拒绝',
        self::POSTED => '已过账',
        self::CANCELLED => '已取消',
    ];

    // 状态映射（字符串 => 数字）
    const STRING_TO_INT_MAP = [
        'draft' => self::DRAFT,
        'pending' => self::PENDING,
        'under_review' => self::UNDER_REVIEW,
        'approved' => self::APPROVED,
        'rejected' => self::REJECTED,
        'posted' => self::POSTED,
        'cancelled' => self::CANCELLED,
    ];

    /**
     * 获取状态中文名称
     */
    public static function getText($status)
    {
        return self::STATUS_MAP[$status] ?? '未知';
    }

    /**
     * 获取所有状态
     */
    public static function getAll()
    {
        return self::STATUS_MAP;
    }

    /**
     * 检查状态是否有效
     */
    public static function isValid($status)
    {
        return isset(self::STATUS_MAP[$status]);
    }
}

