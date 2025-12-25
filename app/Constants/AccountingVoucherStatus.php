<?php

namespace App\Constants;

/**
 * 会计凭证状态常量
 */
class AccountingVoucherStatus
{
    // 状态值（数字）
    const DRAFT = 1;           // 草稿
    const POSTED = 2;          // 已过账
    const CANCELLED = 3;       // 已取消

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::DRAFT => '草稿',
        self::POSTED => '已过账',
        self::CANCELLED => '已取消',
    ];

    // 状态映射（字符串 => 数字）
    const STRING_TO_INT_MAP = [
        'draft' => self::DRAFT,
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

