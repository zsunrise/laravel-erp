<template>
    <div class="dashboard">
        <!-- 顶部操作栏 -->
        <div class="dashboard-header">
            <div class="header-left">
                <h2 class="page-title">仪表盘</h2>
                <span class="last-update text-muted" v-if="lastUpdateTime">
                    最后更新: {{ lastUpdateTime }}
                </span>
            </div>
            <div class="header-right">
                <el-button 
                    :icon="RefreshCw" 
                    @click="refreshData" 
                    :loading="loading"
                    class="interactive"
                >
                    刷新
                </el-button>
            </div>
        </div>

        <!-- 加载状态 -->
        <div v-if="loading && !stats" class="loading-container">
            <el-skeleton :rows="3" animated />
        </div>

        <!-- 主内容区 -->
        <div v-else class="dashboard-content">
            <!-- 快捷操作区 -->
            <div class="quick-actions bento-card">
                <div class="actions-title">
                    <Zap :size="20" class="text-primary" />
                    <span>快捷操作</span>
                </div>
                <div class="actions-grid">
                    <router-link to="/sales-orders" class="action-item">
                        <div class="action-icon stat-icon-primary">
                            <ShoppingBag :size="20" />
                        </div>
                        <span>新建销售订单</span>
                    </router-link>
                    <router-link to="/purchase-orders" class="action-item">
                        <div class="action-icon stat-icon-success">
                            <ShoppingCart :size="20" />
                        </div>
                        <span>新建采购订单</span>
                    </router-link>
                    <router-link to="/production" class="action-item">
                        <div class="action-icon stat-icon-warning">
                            <Factory :size="20" />
                        </div>
                        <span>生产管理</span>
                    </router-link>
                    <router-link to="/inventory" class="action-item">
                        <div class="action-icon stat-icon-info">
                            <Package :size="20" />
                        </div>
                        <span>库存管理</span>
                    </router-link>
                </div>
            </div>

            <!-- Bento-style Grid 统计卡片 -->
            <div class="bento-grid">
                <!-- 销售订单 -->
                <div class="bento-card stat-card" @click="navigateTo('/sales-orders')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-primary">
                            <ShoppingBag :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.salesOrders?.value || 0 }}
                            </div>
                            <div class="stat-label text-muted">销售订单</div>
                            <div class="stat-trend" v-if="stats?.salesOrders?.trend">
                                <TrendingUp 
                                    :size="14" 
                                    :class="stats.salesOrders.trend > 0 ? 'text-success' : 'text-danger'"
                                />
                                <span 
                                    :class="stats.salesOrders.trend > 0 ? 'text-success' : 'text-danger'"
                                >
                                    {{ Math.abs(stats.salesOrders.trend) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 采购订单 -->
                <div class="bento-card stat-card" @click="navigateTo('/purchase-orders')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-success">
                            <ShoppingCart :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.purchaseOrders?.value || 0 }}
                            </div>
                            <div class="stat-label text-muted">采购订单</div>
                            <div class="stat-trend" v-if="stats?.purchaseOrders?.trend">
                                <TrendingUp 
                                    :size="14" 
                                    :class="stats.purchaseOrders.trend > 0 ? 'text-success' : 'text-danger'"
                                />
                                <span 
                                    :class="stats.purchaseOrders.trend > 0 ? 'text-success' : 'text-danger'"
                                >
                                    {{ Math.abs(stats.purchaseOrders.trend) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 库存商品 -->
                <div class="bento-card stat-card" @click="navigateTo('/inventory')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-warning">
                            <Boxes :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.inventory?.value || 0 }}
                            </div>
                            <div class="stat-label text-muted">库存商品</div>
                            <div class="stat-warning" v-if="stats?.inventory?.warning > 0">
                                <AlertTriangle :size="14" class="text-warning" />
                                <span class="text-warning">{{ stats.inventory.warning }} 项预警</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 本月营收 -->
                <div class="bento-card stat-card">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-success">
                            <TrendingUp :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.revenue?.formatted || '¥0.00' }}
                            </div>
                            <div class="stat-label text-muted">本月营收</div>
                            <div class="stat-trend" v-if="stats?.revenue?.trend">
                                <TrendingUp 
                                    :size="14" 
                                    :class="stats.revenue.trend > 0 ? 'text-success' : 'text-danger'"
                                />
                                <span 
                                    :class="stats.revenue.trend > 0 ? 'text-success' : 'text-danger'"
                                >
                                    {{ Math.abs(stats.revenue.trend) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 待审批 -->
                <div class="bento-card stat-card" @click="navigateTo('/workflows')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-info">
                            <FileCheck :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.pendingApprovals?.value || 0 }}
                            </div>
                            <div class="stat-label text-muted">待审批</div>
                        </div>
                    </div>
                </div>

                <!-- 生产工单 -->
                <div class="bento-card stat-card" @click="navigateTo('/production')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-warning">
                            <Factory :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.workOrders?.value || 0 }}
                            </div>
                            <div class="stat-label text-muted">生产工单</div>
                        </div>
                    </div>
                </div>

                <!-- 应收账款 -->
                <div class="bento-card stat-card" @click="navigateTo('/financial')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-primary">
                            <Wallet :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.receivables?.formatted || '¥0.00' }}
                            </div>
                            <div class="stat-label text-muted">应收账款</div>
                        </div>
                    </div>
                </div>

                <!-- 应付账款 -->
                <div class="bento-card stat-card" @click="navigateTo('/financial')">
                    <div class="stat-content">
                        <div class="stat-icon stat-icon-danger">
                            <CreditCard :size="24" />
                        </div>
                        <div class="stat-info">
                            <div class="stat-value text-primary">
                                {{ stats?.payables?.formatted || '¥0.00' }}
                            </div>
                            <div class="stat-label text-muted">应付账款</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 内容卡片区域 -->
            <div class="bento-grid-content">
                <!-- 待处理事项 -->
                <div class="bento-card content-card">
                    <div class="card-header">
                        <h3 class="card-title text-primary">待处理事项</h3>
                        <span class="badge-warning" v-if="pendingTasks.length > 0">
                            {{ pendingTasks.length }}
                        </span>
                    </div>
                    <div v-if="pendingTasks.length === 0" class="empty-state">
                        <CheckCircle :size="48" class="text-success" />
                        <p class="text-muted">暂无待处理事项</p>
                    </div>
                    <el-table 
                        v-else
                        :data="pendingTasks" 
                        style="width: 100%"
                        :row-class-name="tableRowClassName"
                    >
                        <el-table-column prop="type" label="类型" width="80">
                            <template #default="{ row }">
                                <el-tag 
                                    :type="getTaskTypeTagType(row.type)" 
                                    size="small"
                                >
                                    {{ row.type }}
                                </el-tag>
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
                        <el-table-column prop="priority" label="优先级" width="80">
                            <template #default="{ row }">
                                <el-tag 
                                    :type="getPriorityTagType(row.priority)" 
                                    size="small"
                                >
                                    {{ getPriorityLabel(row.priority) }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ row }">
                                <el-button 
                                    type="primary" 
                                    size="small" 
                                    class="interactive"
                                    @click="handleTask(row)"
                                >
                                    处理
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                
                <!-- 最近订单 -->
                <div class="bento-card content-card">
                    <div class="card-header">
                        <h3 class="card-title text-primary">最近订单</h3>
                        <router-link to="/sales-orders" class="view-more">
                            查看更多 <ChevronRight :size="16" />
                        </router-link>
                    </div>
                    <div v-if="recentOrders.length === 0" class="empty-state">
                        <FileText :size="48" class="text-muted" />
                        <p class="text-muted">暂无订单</p>
                    </div>
                    <el-table 
                        v-else
                        :data="recentOrders" 
                        style="width: 100%"
                        :row-class-name="tableRowClassName"
                        @row-click="handleOrderClick"
                    >
                        <el-table-column prop="orderNo" label="单号" width="150">
                            <template #default="{ row }">
                                <span class="text-primary font-medium">{{ row.orderNo }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="type" label="类型" width="80">
                            <template #default="{ row }">
                                <el-tag 
                                    :type="row.type === '销售' ? 'success' : 'warning'" 
                                    size="small"
                                >
                                    {{ row.type }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="customer" label="客户/供应商" min-width="120">
                            <template #default="{ row }">
                                <span class="text-secondary">{{ row.customer }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="amount" label="金额" width="120">
                            <template #default="{ row }">
                                <span class="text-primary font-medium">{{ row.amount }}</span>
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { 
    ShoppingBag, 
    ShoppingCart, 
    Boxes, 
    TrendingUp, 
    RefreshCw,
    Zap,
    Factory,
    Package,
    FileCheck,
    Wallet,
    CreditCard,
    AlertTriangle,
    CheckCircle,
    FileText,
    ChevronRight
} from 'lucide-vue-next';
import api from '../services/api';
import { ElMessage } from 'element-plus';

const router = useRouter();

const loading = ref(false);
const stats = ref(null);
const pendingTasks = ref([]);
const recentOrders = ref([]);
const lastUpdateTime = ref('');

// 获取统计数据
const fetchStats = async () => {
    try {
        const response = await api.get('/dashboard/stats');
        stats.value = response.data.stats;
    } catch (error) {
        console.error('获取统计数据失败:', error);
        ElMessage.error('获取统计数据失败');
    }
};

// 获取待处理事项
const fetchPendingTasks = async () => {
    try {
        const response = await api.get('/dashboard/pending-tasks');
        pendingTasks.value = response.data.tasks || [];
    } catch (error) {
        console.error('获取待处理事项失败:', error);
    }
};

// 获取最近订单
const fetchRecentOrders = async () => {
    try {
        const response = await api.get('/dashboard/recent-orders');
        recentOrders.value = response.data.orders || [];
    } catch (error) {
        console.error('获取最近订单失败:', error);
    }
};

// 刷新所有数据
const refreshData = async () => {
    loading.value = true;
    try {
        await Promise.all([
            fetchStats(),
            fetchPendingTasks(),
            fetchRecentOrders()
        ]);
        lastUpdateTime.value = new Date().toLocaleString('zh-CN', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        ElMessage.success('数据已刷新');
    } catch (error) {
        console.error('刷新数据失败:', error);
    } finally {
        loading.value = false;
    }
};

// 导航到指定页面
const navigateTo = (path) => {
    router.push(path);
};

// 处理任务
const handleTask = (task) => {
    const moduleRoutes = {
        'workflow': '/workflows',
        'inventory': '/inventory',
        'receivable': '/financial'
    };
    const route = moduleRoutes[task.module] || '/';
    router.push(route);
};

// 处理订单点击
const handleOrderClick = (row) => {
    const route = row.module === 'sales' ? '/sales-orders' : '/purchase-orders';
    router.push(route);
};

// 获取状态样式类
const getStatusClass = (status) => {
    const statusMap = {
        '已审核': 'badge-success',
        '待审核': 'badge-warning',
        '已完成': 'badge-success',
        '已取消': 'badge-muted',
        '草稿': 'badge-muted',
        '进行中': 'badge-info'
    };
    return statusMap[status] || 'badge-muted';
};

// 获取任务类型标签类型
const getTaskTypeTagType = (type) => {
    const typeMap = {
        '审批': 'primary',
        '预警': 'warning',
        '逾期': 'danger'
    };
    return typeMap[type] || 'info';
};

// 获取优先级标签类型
const getPriorityTagType = (priority) => {
    const priorityMap = {
        'high': 'danger',
        'normal': 'primary',
        'low': 'info'
    };
    return priorityMap[priority] || 'info';
};

// 获取优先级标签文本
const getPriorityLabel = (priority) => {
    const labelMap = {
        'high': '高',
        'normal': '中',
        'low': '低'
    };
    return labelMap[priority] || priority;
};

// 表格行样式
const tableRowClassName = ({ row, rowIndex }) => {
    return 'table-row-interactive';
};

// 初始化加载数据
onMounted(async () => {
    await refreshData();
});
</script>

<style scoped>
.dashboard {
    padding: 0;
}

/* 顶部操作栏 */
.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding: 20px 0;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.page-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    color: var(--color-text);
}

.last-update {
    font-size: 14px;
}

.header-right {
    display: flex;
    gap: 12px;
}

/* 加载状态 */
.loading-container {
    padding: 40px;
}

/* 快捷操作区 */
.quick-actions {
    padding: 20px 24px;
    margin-bottom: 24px;
}

.actions-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--color-text);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: var(--radius-md);
    background: var(--color-bg-secondary);
    transition: var(--transition);
    text-decoration: none;
    color: var(--color-text);
    cursor: pointer;
}

.action-item:hover {
    background: var(--color-bg-hover);
    transform: translateY(-2px);
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    flex-shrink: 0;
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
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
}

/* 统计卡片 */
.stat-card {
    padding: 24px;
    transition: var(--transition);
    cursor: pointer;
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

.stat-icon-info {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
}

.stat-icon-danger {
    background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
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
    margin-bottom: 4px;
}

.stat-trend, .stat-warning {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 4px;
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

.view-more {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--color-primary);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.view-more:hover {
    opacity: 0.8;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    gap: 12px;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
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

.font-medium {
    font-weight: 500;
}

/* 响应式设计 */
@media (max-width: 1200px) {
    .bento-grid-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .header-left {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }

    .bento-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .dashboard {
        padding: 0;
    }

    .stat-card {
        padding: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
    }

    .stat-value {
        font-size: 24px;
    }

    .stat-label {
        font-size: 13px;
    }

    .card-header {
        padding: 16px;
    }

    .card-title {
        font-size: 15px;
    }

    .page-title {
        font-size: 20px;
    }
}
</style>
