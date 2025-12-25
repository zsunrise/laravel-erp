<?php

namespace App\Constants;

/**
 * 工单明细状态常量
 */
class WorkOrderItemStatus
{
    // 状态值（数字）
    const PENDING = 1;         // 待开始
    const IN_PROGRESS = 2;     // 进行中
    const COMPLETED = 3;       // 已完成

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::PENDING => '待开始',
        self::IN_PROGRESS => '进行中',
        self::COMPLETED => '已完成',
    ];

    // 状态映射（字符串 => 数字）
    const STRING_TO_INT_MAP = [
        'pending' => self::PENDING,
        'in_progress' => self::IN_PROGRESS,
        'completed' => self::COMPLETED,
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

