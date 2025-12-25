<?php

namespace App\Constants;

/**
 * 工单状态常量
 */
class WorkOrderStatus
{
    // 状态值（数字）
    const DRAFT = 1;               // 草稿
    const APPROVED = 2;            // 已审核
    const MATERIAL_ISSUED = 3;     // 已领料
    const IN_PROGRESS = 4;         // 进行中
    const COMPLETED = 5;           // 已完成
    const CANCELLED = 6;           // 已取消

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::DRAFT => '草稿',
        self::APPROVED => '已审核',
        self::MATERIAL_ISSUED => '已领料',
        self::IN_PROGRESS => '进行中',
        self::COMPLETED => '已完成',
        self::CANCELLED => '已取消',
    ];

    // 状态映射（字符串 => 数字）
    const STRING_TO_INT_MAP = [
        'draft' => self::DRAFT,
        'approved' => self::APPROVED,
        'material_issued' => self::MATERIAL_ISSUED,
        'in_progress' => self::IN_PROGRESS,
        'completed' => self::COMPLETED,
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

