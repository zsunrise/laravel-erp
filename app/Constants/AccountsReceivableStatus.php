<?php

namespace App\Constants;

/**
 * 应收账款状态常量
 */
class AccountsReceivableStatus
{
    // 状态值（数字）
    const OUTSTANDING = 1;     // 未结清
    const PARTIAL = 2;         // 部分结清
    const SETTLED = 3;         // 已结清
    const OVERDUE = 4;         // 逾期

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::OUTSTANDING => '未结清',
        self::PARTIAL => '部分结清',
        self::SETTLED => '已结清',
        self::OVERDUE => '逾期',
    ];

    // 状态映射（字符串 => 数字）
    const STRING_TO_INT_MAP = [
        'outstanding' => self::OUTSTANDING,
        'partial' => self::PARTIAL,
        'settled' => self::SETTLED,
        'overdue' => self::OVERDUE,
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

