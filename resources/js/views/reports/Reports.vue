<template>
    <div class="reports-page">
        <el-row :gutter="20">
            <el-col :span="24">
                <el-card>
                    <template #header>
                        <div class="card-header">
                            <span>报表分析</span>
                            <el-button type="primary" @click="handleExport">导出报表</el-button>
                        </div>
                    </template>

                    <el-tabs v-model="activeTab">
                        <el-tab-pane label="销售报表" name="sales">
                            <el-form :inline="true" :model="salesReportForm" class="search-form">
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
                            <el-form :inline="true" :model="purchaseReportForm" class="search-form">
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
                            <el-form :inline="true" :model="inventoryReportForm" class="search-form">
                                <el-form-item label="仓库">
                                    <el-select v-model="inventoryReportForm.warehouse_id" placeholder="全部" clearable>
                                        <el-option label="全部仓库" :value="null" />
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
                            <el-form :inline="true" :model="financialReportForm" class="search-form">
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
                </el-card>
            </el-col>
        </el-row>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import api from '../../services/api';

const activeTab = ref('sales');
const salesReportLoading = ref(false);
const purchaseReportLoading = ref(false);
const inventoryReportLoading = ref(false);
const salesReportData = ref([]);
const purchaseReportData = ref([]);
const inventoryReportData = ref([]);

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
    warehouse_id: null
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
        salesReportData.value = response.data.data || [];
        Object.assign(salesStats, response.data.stats || {});
    } catch (error) {
        ElMessage.error('加载销售报表失败');
    } finally {
        salesReportLoading.value = false;
    }
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
        purchaseReportData.value = response.data.data || [];
        Object.assign(purchaseStats, response.data.stats || {});
    } catch (error) {
        ElMessage.error('加载采购报表失败');
    } finally {
        purchaseReportLoading.value = false;
    }
};

const loadInventoryReport = async () => {
    inventoryReportLoading.value = true;
    try {
        const params = { ...inventoryReportForm };
        const response = await api.get('/inventory-reports/valuation', { params });
        inventoryReportData.value = response.data.data || [];
    } catch (error) {
        ElMessage.error('加载库存报表失败');
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
        Object.assign(financialStats, response.data.stats || {});
    } catch (error) {
        ElMessage.error('加载财务报表失败');
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
    inventoryReportForm.warehouse_id = null;
    handleInventoryReportSearch();
};

const handleFinancialReportSearch = () => {
    loadFinancialReport();
};

const handleFinancialReportReset = () => {
    financialReportForm.date_range = null;
    handleFinancialReportSearch();
};

const handleExport = () => {
    ElMessage.info('导出报表功能开发中');
};

onMounted(() => {
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

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-form {
    margin-bottom: 20px;
}
</style>

