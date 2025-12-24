<template>
    <div class="reports-page">
        <div class="bento-card">
            <div class="card-header-modern">
                <h2 class="page-title text-primary">报表分析</h2>
                <div class="header-actions">
                    <el-button type="primary" @click="handleExportExcel" class="interactive">
                        <Download :size="16" style="margin-right: 6px;" />
                        导出Excel
                    </el-button>
                    <el-button type="primary" @click="handleExportPDF" class="interactive">
                        <FileText :size="16" style="margin-right: 6px;" />
                        导出PDF
                    </el-button>
                </div>
            </div>

            <el-tabs v-model="activeTab" class="modern-tabs">
                <el-tab-pane label="销售报表" name="sales">
                    <el-form :inline="true" :model="salesReportForm" class="search-form-modern">
                                <el-form-item label="日期范围">
                                    <el-date-picker
                                        v-model="salesReportForm.date_range"
                                        type="daterange"
                                        range-separator="至"
                                        start-placeholder="开始日期"
                                        end-placeholder="结束日期"
                                        value-format="YYYY-MM-DD"
                                    />
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="primary" @click="handleSalesReportSearch">查询</el-button>
                                    <el-button @click="handleSalesReportReset">重置</el-button>
                                </el-form-item>
                            </el-form>

                            <el-row :gutter="20" style="margin-bottom: 20px;">
                                <el-col :span="6">
                                    <el-statistic title="销售总额" :value="salesStats.total_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="订单数量" :value="salesStats.order_count" />
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="平均订单额" :value="salesStats.avg_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="客户数量" :value="salesStats.customer_count" />
                                </el-col>
                            </el-row>

                            <div ref="salesChartRef" style="width: 100%; height: 400px; margin-bottom: 20px;"></div>
                            
                            <el-table :data="salesReportData" v-loading="salesReportLoading" style="width: 100%">
                                <el-table-column prop="date" label="日期" width="120" />
                                <el-table-column prop="order_count" label="订单数" width="100" />
                                <el-table-column prop="total_amount" label="销售金额" width="120">
                                    <template #default="{ row }">¥{{ row.total_amount }}</template>
                                </el-table-column>
                                <el-table-column prop="customer_count" label="客户数" width="100" />
                            </el-table>
                        </el-tab-pane>

                        <el-tab-pane label="采购报表" name="purchase">
                            <el-form :inline="true" :model="purchaseReportForm" class="search-form-modern">
                                <el-form-item label="日期范围">
                                    <el-date-picker
                                        v-model="purchaseReportForm.date_range"
                                        type="daterange"
                                        range-separator="至"
                                        start-placeholder="开始日期"
                                        end-placeholder="结束日期"
                                        value-format="YYYY-MM-DD"
                                    />
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="primary" @click="handlePurchaseReportSearch">查询</el-button>
                                    <el-button @click="handlePurchaseReportReset">重置</el-button>
                                </el-form-item>
                            </el-form>

                            <el-row :gutter="20" style="margin-bottom: 20px;">
                                <el-col :span="6">
                                    <el-statistic title="采购总额" :value="purchaseStats.total_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="订单数量" :value="purchaseStats.order_count" />
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="平均订单额" :value="purchaseStats.avg_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="供应商数量" :value="purchaseStats.supplier_count" />
                                </el-col>
                            </el-row>

                            <div ref="purchaseChartRef" style="width: 100%; height: 400px; margin-bottom: 20px;"></div>
                            
                            <el-table :data="purchaseReportData" v-loading="purchaseReportLoading" style="width: 100%">
                                <el-table-column prop="date" label="日期" width="120" />
                                <el-table-column prop="order_count" label="订单数" width="100" />
                                <el-table-column prop="total_amount" label="采购金额" width="120">
                                    <template #default="{ row }">¥{{ row.total_amount }}</template>
                                </el-table-column>
                                <el-table-column prop="supplier_count" label="供应商数" width="100" />
                            </el-table>
                        </el-tab-pane>

                        <el-tab-pane label="库存报表" name="inventory">
                            <el-form :inline="true" :model="inventoryReportForm" class="search-form-modern">
                                <el-form-item label="仓库">
                                    <el-select v-model="inventoryReportForm.warehouse_id" placeholder="全部" clearable>
                                        <el-option label="全部仓库" :value="''" />
                                        <el-option
                                            v-for="warehouse in warehouses"
                                            :key="warehouse.id"
                                            :label="warehouse.name"
                                            :value="warehouse.id"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="primary" @click="handleInventoryReportSearch">查询</el-button>
                                    <el-button @click="handleInventoryReportReset">重置</el-button>
                                </el-form-item>
                            </el-form>

                            <el-table :data="inventoryReportData" v-loading="inventoryReportLoading" style="width: 100%">
                                <el-table-column prop="product.name" label="商品名称" />
                                <el-table-column prop="product.sku" label="SKU" width="120" />
                                <el-table-column prop="warehouse.name" label="仓库" />
                                <el-table-column prop="quantity" label="库存数量" width="120" />
                                <el-table-column prop="total_value" label="库存价值" width="120">
                                    <template #default="{ row }">¥{{ row.total_value }}</template>
                                </el-table-column>
                            </el-table>
                        </el-tab-pane>

                        <el-tab-pane label="财务报表" name="financial">
                            <el-form :inline="true" :model="financialReportForm" class="search-form-modern">
                                <el-form-item label="日期范围">
                                    <el-date-picker
                                        v-model="financialReportForm.date_range"
                                        type="daterange"
                                        range-separator="至"
                                        start-placeholder="开始日期"
                                        end-placeholder="结束日期"
                                        value-format="YYYY-MM-DD"
                                    />
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="primary" @click="handleFinancialReportSearch">查询</el-button>
                                    <el-button @click="handleFinancialReportReset">重置</el-button>
                                </el-form-item>
                            </el-form>

                            <el-row :gutter="20" style="margin-bottom: 20px;">
                                <el-col :span="6">
                                    <el-statistic title="营业收入" :value="financialStats.revenue">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="营业成本" :value="financialStats.cost">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="毛利润" :value="financialStats.profit">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="利润率" :value="financialStats.profit_rate">
                                        <template #suffix>%</template>
                                    </el-statistic>
                                </el-col>
                            </el-row>
                    </el-tab-pane>
                </el-tabs>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, nextTick } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Download, FileText } from 'lucide-vue-next';
import api from '../../services/api';
import { exportToExcel, exportToPDF } from '../../utils/export';
import { useChart } from '../../composables/useChart';

const activeTab = ref('sales');
const salesReportLoading = ref(false);
const purchaseReportLoading = ref(false);
const inventoryReportLoading = ref(false);
const salesReportData = ref([]);
const purchaseReportData = ref([]);
const inventoryReportData = ref([]);
const warehouses = ref([]);

const salesChartRef = ref(null);
const purchaseChartRef = ref(null);

const salesChart = useChart(salesChartRef);
const purchaseChart = useChart(purchaseChartRef);

const salesStats = reactive({
    total_amount: 0,
    order_count: 0,
    avg_amount: 0,
    customer_count: 0
});

const purchaseStats = reactive({
    total_amount: 0,
    order_count: 0,
    avg_amount: 0,
    supplier_count: 0
});

const financialStats = reactive({
    revenue: 0,
    cost: 0,
    profit: 0,
    profit_rate: 0
});

const salesReportForm = reactive({
    date_range: null
});

const purchaseReportForm = reactive({
    date_range: null
});

const inventoryReportForm = reactive({
    warehouse_id: ''
});

const financialReportForm = reactive({
    date_range: null
});

const loadSalesReport = async () => {
    salesReportLoading.value = true;
    try {
        const params = {};
        if (salesReportForm.date_range && salesReportForm.date_range.length == 2) {
            params.start_date = salesReportForm.date_range[0];
            params.end_date = salesReportForm.date_range[1];
        }
        const response = await api.get('/sales-reports/summary', { params });
        if (response.data.success && response.data.data) {
            salesReportData.value = response.data.data.data || [];
            const stats = response.data.data.stats || {};
            Object.assign(salesStats, {
                total_amount: Number(parseFloat(stats.total_amount) || 0),
                order_count: Number(parseInt(stats.order_count) || 0),
                avg_amount: Number(parseFloat(stats.avg_amount) || 0),
                customer_count: Number(parseInt(stats.customer_count) || 0)
            });
        } else {
            salesReportData.value = [];
            Object.assign(salesStats, { total_amount: 0, order_count: 0, avg_amount: 0, customer_count: 0 });
        }
        
        // 更新图表
        await nextTick();
        if (salesChartRef.value) {
            updateSalesChart();
        }
    } catch (error) {
        console.error('加载销售报表失败:', error);
        ElMessage.error(error.response?.data?.message || '加载销售报表失败');
        salesReportData.value = [];
        Object.assign(salesStats, { total_amount: 0, order_count: 0, avg_amount: 0, customer_count: 0 });
    } finally {
        salesReportLoading.value = false;
    }
};

const updateSalesChart = () => {
    if (!salesReportData.value || salesReportData.value.length == 0) return;
    
    const dates = salesReportData.value.map(item => item.date);
    const amounts = salesReportData.value.map(item => parseFloat(item.total_amount) || 0);
    const orderCounts = salesReportData.value.map(item => parseInt(item.order_count) || 0);
    
    salesChart.setOption({
        title: {
            text: '销售趋势分析',
            left: 'center'
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            }
        },
        legend: {
            data: ['销售金额', '订单数量'],
            top: 30
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: dates
        },
        yAxis: [
            {
                type: 'value',
                name: '金额(元)',
                position: 'left',
                axisLabel: {
                    formatter: '¥{value}'
                }
            },
            {
                type: 'value',
                name: '订单数',
                position: 'right'
            }
        ],
        series: [
            {
                name: '销售金额',
                type: 'line',
                yAxisIndex: 0,
                data: amounts,
                smooth: true,
                itemStyle: { color: '#409EFF' },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0,
                        y: 0,
                        x2: 0,
                        y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(64, 158, 255, 0.3)' },
                            { offset: 1, color: 'rgba(64, 158, 255, 0.1)' }
                        ]
                    }
                }
            },
            {
                name: '订单数量',
                type: 'bar',
                yAxisIndex: 1,
                data: orderCounts,
                itemStyle: { color: '#67C23A' }
            }
        ]
    });
};

const loadPurchaseReport = async () => {
    purchaseReportLoading.value = true;
    try {
        const params = {};
        if (purchaseReportForm.date_range && purchaseReportForm.date_range.length == 2) {
            params.start_date = purchaseReportForm.date_range[0];
            params.end_date = purchaseReportForm.date_range[1];
        }
        const response = await api.get('/purchase-reports/summary', { params });
        if (response.data.success && response.data.data) {
            purchaseReportData.value = response.data.data.data || [];
            const stats = response.data.data.stats || {};
            Object.assign(purchaseStats, {
                total_amount: Number(parseFloat(stats.total_amount) || 0),
                order_count: Number(parseInt(stats.order_count) || 0),
                avg_amount: Number(parseFloat(stats.avg_amount) || 0),
                supplier_count: Number(parseInt(stats.supplier_count) || 0)
            });
        } else {
            purchaseReportData.value = [];
            Object.assign(purchaseStats, { total_amount: 0, order_count: 0, avg_amount: 0, supplier_count: 0 });
        }
        
        // 更新图表
        await nextTick();
        if (purchaseChartRef.value) {
            updatePurchaseChart();
        }
    } catch (error) {
        console.error('加载采购报表失败:', error);
        ElMessage.error(error.response?.data?.message || '加载采购报表失败');
        purchaseReportData.value = [];
        Object.assign(purchaseStats, { total_amount: 0, order_count: 0, avg_amount: 0, supplier_count: 0 });
    } finally {
        purchaseReportLoading.value = false;
    }
};

const updatePurchaseChart = () => {
    if (!purchaseReportData.value || purchaseReportData.value.length == 0) return;
    
    const dates = purchaseReportData.value.map(item => item.date);
    const amounts = purchaseReportData.value.map(item => parseFloat(item.total_amount) || 0);
    const orderCounts = purchaseReportData.value.map(item => parseInt(item.order_count) || 0);
    
    purchaseChart.setOption({
        title: {
            text: '采购趋势分析',
            left: 'center'
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            }
        },
        legend: {
            data: ['采购金额', '订单数量'],
            top: 30
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: dates
        },
        yAxis: [
            {
                type: 'value',
                name: '金额(元)',
                position: 'left',
                axisLabel: {
                    formatter: '¥{value}'
                }
            },
            {
                type: 'value',
                name: '订单数',
                position: 'right'
            }
        ],
        series: [
            {
                name: '采购金额',
                type: 'line',
                yAxisIndex: 0,
                data: amounts,
                smooth: true,
                itemStyle: { color: '#E6A23C' },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0,
                        y: 0,
                        x2: 0,
                        y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(230, 162, 60, 0.3)' },
                            { offset: 1, color: 'rgba(230, 162, 60, 0.1)' }
                        ]
                    }
                }
            },
            {
                name: '订单数量',
                type: 'bar',
                yAxisIndex: 1,
                data: orderCounts,
                itemStyle: { color: '#F56C6C' }
            }
        ]
    });
};

const loadInventoryReport = async () => {
    inventoryReportLoading.value = true;
    try {
        const params = {};
        if (inventoryReportForm.warehouse_id) {
            params.warehouse_id = inventoryReportForm.warehouse_id;
        }
        const response = await api.get('/inventory-reports/valuation', { params });
        if (response.data.success && response.data.data) {
            inventoryReportData.value = response.data.data || [];
        } else {
            inventoryReportData.value = [];
        }
    } catch (error) {
        console.error('加载库存报表失败:', error);
        ElMessage.error(error.response?.data?.message || '加载库存报表失败');
        inventoryReportData.value = [];
    } finally {
        inventoryReportLoading.value = false;
    }
};

const loadFinancialReport = async () => {
    try {
        const params = {};
        if (financialReportForm.date_range && financialReportForm.date_range.length == 2) {
            params.start_date = financialReportForm.date_range[0];
            params.end_date = financialReportForm.date_range[1];
        }
        const response = await api.get('/financial-reports/income-statement', { params });
        if (response.data.success && response.data.data && response.data.data.stats) {
            const stats = response.data.data.stats;
            Object.assign(financialStats, {
                revenue: Number(parseFloat(stats.revenue) || 0),
                cost: Number(parseFloat(stats.cost) || 0),
                profit: Number(parseFloat(stats.profit) || 0),
                profit_rate: Number(parseFloat(stats.profit_rate) || 0)
            });
        } else {
            Object.assign(financialStats, { revenue: 0, cost: 0, profit: 0, profit_rate: 0 });
        }
    } catch (error) {
        console.error('加载财务报表失败:', error);
        ElMessage.error(error.response?.data?.message || '加载财务报表失败');
        Object.assign(financialStats, { revenue: 0, cost: 0, profit: 0, profit_rate: 0 });
    }
};

const handleSalesReportSearch = () => {
    loadSalesReport();
};

const handleSalesReportReset = () => {
    salesReportForm.date_range = null;
    handleSalesReportSearch();
};

const handlePurchaseReportSearch = () => {
    loadPurchaseReport();
};

const handlePurchaseReportReset = () => {
    purchaseReportForm.date_range = null;
    handlePurchaseReportSearch();
};

const handleInventoryReportSearch = () => {
    loadInventoryReport();
};

const handleInventoryReportReset = () => {
    inventoryReportForm.warehouse_id = '';
    handleInventoryReportSearch();
};

const handleFinancialReportSearch = () => {
    loadFinancialReport();
};

const handleFinancialReportReset = () => {
    financialReportForm.date_range = null;
    handleFinancialReportSearch();
};

const loadWarehouses = async () => {
    try {
        const response = await api.get('/warehouses', { params: { per_page: 1000, is_active: 1 } });
        warehouses.value = response.data.data || [];
    } catch (error) {
        console.error('加载仓库列表失败:', error);
        warehouses.value = [];
    }
};

const handleExportExcel = async () => {
    try {
        let data = [];
        let columns = [];
        let filename = '';
        let title = '';
        
        if (activeTab.value == 'sales') {
            data = salesReportData.value;
            columns = [
                { key: 'date', label: '日期' },
                { key: 'order_count', label: '订单数' },
                { key: 'total_amount', label: '销售金额' },
                { key: 'customer_count', label: '客户数' }
            ];
            filename = `销售报表_${new Date().toISOString().split('T')[0]}`;
            title = '销售报表';
        } else if (activeTab.value == 'purchase') {
            data = purchaseReportData.value;
            columns = [
                { key: 'date', label: '日期' },
                { key: 'order_count', label: '订单数' },
                { key: 'total_amount', label: '采购金额' },
                { key: 'supplier_count', label: '供应商数' }
            ];
            filename = `采购报表_${new Date().toISOString().split('T')[0]}`;
            title = '采购报表';
        } else if (activeTab.value == 'inventory') {
            data = inventoryReportData.value;
            columns = [
                { key: 'product.name', label: '商品名称' },
                { key: 'product.sku', label: 'SKU' },
                { key: 'warehouse.name', label: '仓库' },
                { key: 'quantity', label: '库存数量' },
                { key: 'total_value', label: '库存价值' }
            ];
            filename = `库存报表_${new Date().toISOString().split('T')[0]}`;
            title = '库存报表';
        } else {
            ElMessage.warning('当前报表暂无数据可导出');
            return;
        }
        
        if (data.length == 0) {
            ElMessage.warning('当前报表暂无数据可导出');
            return;
        }
        
        exportToExcel(data, columns, filename);
        ElMessage.success('导出成功');
    } catch (error) {
        ElMessage.error('导出失败');
    }
};

const handleExportPDF = async () => {
    try {
        let data = [];
        let columns = [];
        let filename = '';
        let title = '';
        let stats = null;
        
        if (activeTab.value == 'sales') {
            data = salesReportData.value;
            columns = [
                { key: 'date', label: '日期' },
                { key: 'order_count', label: '订单数' },
                { key: 'total_amount', label: '销售金额' },
                { key: 'customer_count', label: '客户数' }
            ];
            filename = `销售报表_${new Date().toISOString().split('T')[0]}`;
            title = '销售报表';
            stats = {
                '销售总额': `¥${salesStats.total_amount}`,
                '订单数量': salesStats.order_count,
                '平均订单额': `¥${salesStats.avg_amount}`,
                '客户数量': salesStats.customer_count
            };
        } else if (activeTab.value == 'purchase') {
            data = purchaseReportData.value;
            columns = [
                { key: 'date', label: '日期' },
                { key: 'order_count', label: '订单数' },
                { key: 'total_amount', label: '采购金额' },
                { key: 'supplier_count', label: '供应商数' }
            ];
            filename = `采购报表_${new Date().toISOString().split('T')[0]}`;
            title = '采购报表';
            stats = {
                '采购总额': `¥${purchaseStats.total_amount}`,
                '订单数量': purchaseStats.order_count,
                '平均订单额': `¥${purchaseStats.avg_amount}`,
                '供应商数量': purchaseStats.supplier_count
            };
        } else if (activeTab.value == 'inventory') {
            data = inventoryReportData.value;
            columns = [
                { key: 'product.name', label: '商品名称' },
                { key: 'product.sku', label: 'SKU' },
                { key: 'warehouse.name', label: '仓库' },
                { key: 'quantity', label: '库存数量' },
                { key: 'total_value', label: '库存价值' }
            ];
            filename = `库存报表_${new Date().toISOString().split('T')[0]}`;
            title = '库存报表';
        } else if (activeTab.value == 'financial') {
            ElMessage.warning('财务报表暂不支持导出');
            return;
        } else {
            ElMessage.warning('当前报表暂无数据可导出');
            return;
        }
        
        if (data.length == 0) {
            ElMessage.warning('当前报表暂无数据可导出');
            return;
        }
        
        exportToPDF(data, columns, filename, title, stats);
        ElMessage.success('导出成功');
    } catch (error) {
        ElMessage.error('导出失败');
    }
};

watch(activeTab, () => {
    nextTick(() => {
        if (activeTab.value == 'sales' && salesChartRef.value) {
            salesChart.resize();
            updateSalesChart();
        } else if (activeTab.value == 'purchase' && purchaseChartRef.value) {
            purchaseChart.resize();
            updatePurchaseChart();
        }
    });
});

onMounted(() => {
    loadWarehouses();
    loadSalesReport();
    loadPurchaseReport();
    loadInventoryReport();
    loadFinancialReport();
});
</script>

<style scoped>
.reports-page {
    padding: 0;
}

.card-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid var(--color-border);
}

.page-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.search-form {
    margin: 24px;
    padding: 20px;
    background-color: var(--color-bg-secondary);
    border-radius: var(--radius);
}

.modern-tabs {
    padding: 0 24px 24px;
}

.modern-tabs :deep(.el-tabs__header) {
    margin-bottom: 24px;
}

.modern-tabs :deep(.el-tabs__item) {
    font-weight: 500;
    color: var(--color-text-secondary);
    transition: var(--transition);
}

.modern-tabs :deep(.el-tabs__item.is-active) {
    color: var(--color-primary);
    font-weight: 600;
}

.modern-tabs :deep(.el-tabs__active-bar) {
    background-color: var(--color-primary);
}

.modern-tabs :deep(.el-table) {
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    overflow: hidden;
}

.modern-tabs :deep(.el-table th) {
    background-color: var(--color-bg-secondary);
    color: var(--color-text-primary);
    font-weight: 600;
}

.modern-tabs :deep(.el-table td) {
    border-bottom: 1px solid var(--color-border-light);
}

.modern-tabs :deep(.el-table tr:hover) {
    background-color: var(--color-bg-secondary);
}

/* 统计卡片样式 */
:deep(.el-statistic) {
    text-align: center;
}

:deep(.el-statistic__head) {
    color: var(--color-text-muted);
    font-size: 14px;
    margin-bottom: 8px;
}

:deep(.el-statistic__number) {
    color: var(--color-text-primary);
    font-weight: 600;
    font-size: 24px;
}
</style>

