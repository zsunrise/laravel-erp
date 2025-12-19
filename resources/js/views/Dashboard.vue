<template>
    <div class="dashboard">
        <el-row :gutter="20">
            <el-col :span="6">
                <el-card class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon" style="background-color: #409EFF;">
                            <el-icon><ShoppingBag /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ stats.salesOrders }}</div>
                            <div class="stat-label">销售订单</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon" style="background-color: #67C23A;">
                            <el-icon><ShoppingCart /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ stats.purchaseOrders }}</div>
                            <div class="stat-label">采购订单</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon" style="background-color: #E6A23C;">
                            <el-icon><Box /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ stats.inventory }}</div>
                            <div class="stat-label">库存商品</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon" style="background-color: #F56C6C;">
                            <el-icon><Money /></el-icon>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ stats.revenue }}</div>
                            <div class="stat-label">本月营收</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <el-row :gutter="20" style="margin-top: 20px;">
            <el-col :span="12">
                <el-card>
                    <template #header>
                        <span>待处理事项</span>
                    </template>
                    <el-table :data="pendingTasks" style="width: 100%">
                        <el-table-column prop="type" label="类型" width="100" />
                        <el-table-column prop="title" label="标题" />
                        <el-table-column prop="date" label="日期" width="120" />
                        <el-table-column label="操作" width="100">
                            <template #default>
                                <el-button type="primary" size="small">处理</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </el-col>
            <el-col :span="12">
                <el-card>
                    <template #header>
                        <span>最近订单</span>
                    </template>
                    <el-table :data="recentOrders" style="width: 100%">
                        <el-table-column prop="orderNo" label="单号" width="150" />
                        <el-table-column prop="type" label="类型" width="100" />
                        <el-table-column prop="amount" label="金额" width="120" />
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getStatusType(row.status)">{{ row.status }}</el-tag>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </el-col>
        </el-row>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const stats = ref({
    salesOrders: 0,
    purchaseOrders: 0,
    inventory: 0,
    revenue: 0
});

const pendingTasks = ref([]);
const recentOrders = ref([]);

const getStatusType = (status) => {
    const statusMap = {
        '已审核': 'success',
        '待审核': 'warning',
        '已完成': 'info',
        '已取消': 'danger'
    };
    return statusMap[status] || 'info';
};

onMounted(async () => {
    // 加载统计数据
    try {
        // 这里可以调用实际的API
        stats.value = {
            salesOrders: 125,
            purchaseOrders: 89,
            inventory: 156,
            revenue: '¥125,680'
        };
        
        pendingTasks.value = [
            { type: '审批', title: '采购订单PO001待审核', date: '2025-12-18' },
            { type: '审批', title: '销售订单SO001待审核', date: '2025-12-18' },
        ];
        
        recentOrders.value = [
            { orderNo: 'SO001', type: '销售', amount: '¥12,500', status: '已审核' },
            { orderNo: 'PO001', type: '采购', amount: '¥8,900', status: '待审核' },
        ];
    } catch (error) {
        console.error('加载数据失败:', error);
    }
});
</script>

<style scoped>
.dashboard {
    padding: 20px;
}

.stat-card {
    margin-bottom: 20px;
}

.stat-content {
    display: flex;
    align-items: center;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
    margin-right: 15px;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #303133;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #909399;
}
</style>

