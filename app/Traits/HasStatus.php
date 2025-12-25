<?php

namespace App\Traits;

/**
 * 状态处理 Trait
 * 提供状态字段的访问器和修改器
 */
trait HasStatus
{
    /**
     * 获取状态文本（访问器）
     * 将数据库中的数字状态转换为中文文本
     */
    public function getStatusTextAttribute()
    {
        $statusClass = $this->getStatusClass();
        if ($statusClass && method_exists($statusClass, 'getText')) {
            return $statusClass::getText($this->attributes['status'] ?? $this->status);
        }
        return '未知';
    }

    /**
     * 设置状态（修改器）
     * 支持字符串和数字状态
     */
    public function setStatusAttribute($value)
    {
        $statusClass = $this->getStatusClass();
        if ($statusClass) {
            // 如果是字符串，转换为数字
            if (is_string($value) && isset($statusClass::STRING_TO_INT_MAP[$value])) {
                $this->attributes['status'] = $statusClass::STRING_TO_INT_MAP[$value];
            } elseif (is_numeric($value)) {
                // 如果是数字，直接使用
                $this->attributes['status'] = (int)$value;
            } else {
                // 无效状态，使用默认值
                $this->attributes['status'] = $statusClass::DRAFT ?? 1;
            }
        } else {
            $this->attributes['status'] = $value;
        }
    }

    /**
     * 获取状态类名
     * 子类需要重写此方法返回对应的状态常量类
     */
    protected function getStatusClass()
    {
        return null;
    }

    /**
     * 检查状态是否等于指定值（支持字符串和数字）
     */
    public function isStatus($status)
    {
        $statusClass = $this->getStatusClass();
        if ($statusClass && is_string($status)) {
            $intStatus = $statusClass::STRING_TO_INT_MAP[$status] ?? null;
            return $this->status == $intStatus;
        }
        return $this->status == $status;
    }
}

