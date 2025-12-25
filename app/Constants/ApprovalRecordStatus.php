<?php

namespace App\Constants;

/**
 * 审批记录状态常量
 */
class ApprovalRecordStatus
{
    // 状态值（数字）
    const PENDING = 1;         // 待审批
    const APPROVED = 2;        // 已通过
    const REJECTED = 3;        // 已拒绝
    const TRANSFERRED = 4;     // 已转交
    const WITHDRAWN = 5;      // 已撤回

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::PENDING => '待审批',
        self::APPROVED => '已通过',
        self::REJECTED => '已拒绝',
        self::TRANSFERRED => '已转交',
        self::WITHDRAWN => '已撤回',
    ];

    // 状态映射（字符串 => 数字）
    const STRING_TO_INT_MAP = [
        'pending' => self::PENDING,
        'approved' => self::APPROVED,
        'rejected' => self::REJECTED,
        'transferred' => self::TRANSFERRED,
        'withdrawn' => self::WITHDRAWN,
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

