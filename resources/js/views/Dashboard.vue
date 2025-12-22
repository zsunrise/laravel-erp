<template>
    <div class="dashboard">
        <!-- Bento-style Grid 统计卡片 -->
        <div class="bento-grid">
            <div class="bento-card stat-card">
                <div class="stat-content">
                    <div class="stat-icon stat-icon-primary">
                        <ShoppingBag :size="24" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value text-primary">{{ stats.salesOrders }}</div>
                        <div class="stat-label text-muted">销售订单</div>
                    </div>
                </div>
            </div>
            
            <div class="bento-card stat-card">
                <div class="stat-content">
                    <div class="stat-icon stat-icon-success">
                        <ShoppingCart :size="24" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value text-primary">{{ stats.purchaseOrders }}</div>
                        <div class="stat-label text-muted">采购订单</div>
                    </div>
                </div>
            </div>
            
            <div class="bento-card stat-card">
                <div class="stat-content">
                    <div class="stat-icon stat-icon-warning">
                        <Boxes :size="24" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value text-primary">{{ stats.inventory }}</div>
                        <div class="stat-label text-muted">库存商品</div>
                    </div>
                </div>
            </div>
            
            <div class="bento-card stat-card">
                <div class="stat-content">
                    <div class="stat-icon stat-icon-success">
                        <TrendingUp :size="24" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value text-primary">{{ stats.revenue }}</div>
                        <div class="stat-label text-muted">本月营收</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 内容卡片区域 -->
        <div class="bento-grid-content">
            <div class="bento-card content-card">
                <div class="card-header">
                    <h3 class="card-title text-primary">待处理事项</h3>
                    <span class="badge-warning">{{ pendingTasks.length }}</span>
                </div>
                <el-table 
                    :data="pendingTasks" 
                    style="width: 100%"
                    :row-class-name="tableRowClassName"
                >
                    <el-table-column prop="type" label="类型" width="100">
                        <template #default="{ row }">
                            <span class="text-secondary">{{ row.type }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="title" label="标题">
                        <template #default="{ row }">
                            <span class="text-primary">{{ row.title }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="date" label="日期" width="120">
                        <template #default="{ row }">
                            <span class="text-muted">{{ row.date }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="100">
                        <template #default>
                            <el-button type="primary" size="small" class="interactive">处理</el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            
            <div class="bento-card content-card">
                <div class="card-header">
                    <h3 class="card-title text-primary">最近订单</h3>
                </div>
                <el-table 
                    :data="recentOrders" 
                    style="width: 100%"
                    :row-class-name="tableRowClassName"
                >
                    <el-table-column prop="orderNo" label="单号" width="150">
                        <template #default="{ row }">
                            <span class="text-primary">{{ row.orderNo }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="type" label="类型" width="100">
                        <template #default="{ row }">
                            <span class="text-secondary">{{ row.type }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="amount" label="金额" width="120">
                        <template #default="{ row }">
                            <span class="text-primary">{{ row.amount }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status" label="状态" width="100">
                        <template #default="{ row }">
                            <span 
                                :class="getStatusClass(row.status)"
                                class="status-badge"
                            >
                                {{ row.status }}
                            </span>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ShoppingBag, ShoppingCart, Boxes, TrendingUp } from 'lucide-vue-next';
import api from '../services/api';

const stats = ref({
    salesOrders: 0,
    purchaseOrders: 0,
    inventory: 0,
    revenue: 0
});

const pendingTasks = ref([]);
const recentOrders = ref([]);

const getStatusClass = (status) => {
    const statusMap = {
        '已审核': 'badge-success',
        '待审核': 'badge-warning',
        '已完成': 'badge-success',
        '已取消': 'badge-muted'
    };
    return statusMap[status] || 'badge-muted';
};

const tableRowClassName = ({ row, rowIndex }) => {
    return 'table-row-interactive';
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
    padding: 0;
}

/* Bento-style Grid 布局 */
.bento-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.bento-grid-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

/* 统计卡片 */
.stat-card {
    padding: 24px;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    flex-shrink: 0;
}

.stat-icon-primary {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-lighter) 100%);
}

.stat-icon-success {
    background: linear-gradient(135deg, var(--color-success) 0%, var(--color-success-light) 100%);
}

.stat-icon-warning {
    background: linear-gradient(135deg, var(--color-warning) 0%, var(--color-warning-light) 100%);
}

.stat-info {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 4px;
    line-height: 1.2;
}

.stat-label {
    font-size: 14px;
    line-height: 1.4;
}

/* 内容卡片 */
.content-card {
    padding: 0;
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--color-border);
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

/* 表格样式 */
:deep(.el-table) {
    border: none;
}

:deep(.el-table th) {
    background-color: var(--color-bg-secondary);
    color: var(--color-text-secondary);
    font-weight: 500;
    border-bottom: 1px solid var(--color-border);
}

:deep(.el-table td) {
    border-bottom: 1px solid var(--color-border-light);
}

:deep(.table-row-interactive) {
    transition: var(--transition);
    cursor: pointer;
}

:deep(.table-row-interactive:hover) {
    background-color: var(--color-bg-secondary) !important;
}

/* 状态标签 */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--radius-sm);
    font-size: 12px;
    font-weight: 500;
}

/* 响应式设计 */
@media (max-width: 1200px) {
    .bento-grid-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .bento-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard {
        padding: 0;
    }
}
</style>

