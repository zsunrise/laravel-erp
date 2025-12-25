<?php

namespace App\Constants;

/**
 * 订单状态常量
 * 采购订单、销售订单等通用订单状态
 */
class OrderStatus
{
    // 状态值（数字）
    const DRAFT = 1;           // 草稿
    const PENDING = 2;          // 待审核
    const APPROVED = 3;         // 已审核
    const PARTIAL = 4;          // 部分入库/出库
    const COMPLETED = 5;        // 已完成
    const CANCELLED = 6;        // 已取消

    // 状态映射（数字 => 中文）
    const STATUS_MAP = [
        self::DRAFT => '草稿',
        self::PENDING => '待审核',
        self::APPROVED => '已审核',
        self::PARTIAL => '部分入库',
        self::COMPLETED => '已完成',
        self::CANCELLED => '已取消',
    ];

    // 状态映射（字符串 => 数字）- 用于迁移数据
    const STRING_TO_INT_MAP = [
        'draft' => self::DRAFT,
        'pending' => self::PENDING,
        'approved' => self::APPROVED,
        'partial' => self::PARTIAL,
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

