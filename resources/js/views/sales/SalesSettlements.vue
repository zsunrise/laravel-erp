<template>
    <div class="sales-settlements-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>销售结算</span>
                    <el-button type="primary" @click="handleAdd">新增结算</el-button>
                </div>
            </template>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="结算单号">
                    <el-input v-model="searchForm.settlement_no" placeholder="结算单号" clearable />
                </el-form-item>
                <el-form-item label="客户">
                    <el-select v-model="searchForm.customer_id" filterable placeholder="全部" clearable style="width: 200px">
                        <el-option
                            v-for="customer in customers"
                            :key="customer.id"
                            :label="customer.name"
                            :value="customer.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.status" placeholder="全部" clearable>
                        <el-option label="待审核" value="pending" />
                        <el-option label="已审核" value="approved" />
                        <el-option label="已收款" value="paid" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="settlements" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="settlement_no" label="结算单号" width="150" />
                <el-table-column prop="customer.name" label="客户" />
                <el-table-column prop="total_amount" label="结算金额" width="120">
                    <template #default="{ row }">¥{{ row.total_amount }}</template>
                </el-table-column>
                <el-table-column prop="received_amount" label="已收金额" width="120">
                    <template #default="{ row }">¥{{ row.received_amount || 0 }}</template>
                </el-table-column>
                <el-table-column prop="balance" label="余额" width="120">
                    <template #default="{ row }">¥{{ (row.total_amount - (row.received_amount || 0)).toFixed(2) }}</template>
                </el-table-column>
                <el-table-column prop="settlement_date" label="结算日期" width="120" />
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="250" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)" :loading="viewLoadingId === row.id" :disabled="viewLoadingId !== null">查看</el-button>
                        <el-button type="success" size="small" @click="handleApprove(row)" v-if="row.status == 'pending'">审核</el-button>
                        <el-button type="warning" size="small" @click="handleReceive(row)" v-if="row.status == 'approved'">收款</el-button>
                    </template>
                </el-table-column>
                </el-table>
            </div>

            <div class="modern-pagination">
                <el-pagination
                    v-model:current-page="pagination.page"
                    v-model:page-size="pagination.per_page"
                    :total="pagination.total"
                    :page-sizes="[10, 20, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="handleSizeChange"
                    @current-change="handlePageChange"
                />
            </div>
        </div>

        <!-- 结算表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            title="新增销售结算"
            width="1200px"
            @close="handleDialogClose"
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="120px"
            >
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="客户" prop="customer_id">
                            <el-select v-model="form.customer_id" filterable placeholder="请选择客户" @change="handleCustomerChange" style="width: 100%">
                                <el-option
                                    v-for="customer in customers"
                                    :key="customer.id"
                                    :label="customer.name"
                                    :value="customer.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="结算日期" prop="settlement_date">
                            <el-date-picker v-model="form.settlement_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="币种" prop="currency_id">
                            <el-select v-model="form.currency_id" filterable placeholder="请选择币种" style="width: 100%">
                                <el-option
                                    v-for="currency in currencies"
                                    :key="currency.id"
                                    :label="`${currency.name} (${currency.code})`"
                                    :value="currency.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="form.remark" type="textarea" :rows="2" />
                </el-form-item>
                <el-form-item label="结算明细" prop="items">
                    <el-button type="primary" size="small" @click="handleAddItem">添加明细</el-button>
                    <el-table :data="form.items" style="margin-top: 10px;" border>
                        <el-table-column label="关联类型" width="150">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.reference_type" @change="handleItemTypeChange($index)" style="width: 100%">
                                    <el-option label="销售订单" value="sales_order" />
                                    <el-option label="销售退货" value="sales_return" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="关联单据" width="200">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.reference_id" filterable placeholder="请选择单据" @change="handleItemReferenceChange($index)" style="width: 100%">
                                    <el-option
                                        v-for="ref in getReferencesByType(row.reference_type)"
                                        :key="ref.id"
                                        :label="ref.order_no || ref.return_no"
                                        :value="ref.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="单据金额" width="120">
                            <template #default="{ row }">
                                ¥{{ row.reference_amount || 0 }}
                            </template>
                        </el-table-column>
                        <el-table-column label="备注" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.remark" placeholder="备注" />
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ $index }">
                                <el-button type="danger" size="small" @click="handleRemoveItem($index)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div style="margin-top: 10px; text-align: right;">
                        <strong>合计：¥{{ totalAmount.toFixed(2) }}</strong>
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 收款对话框 -->
        <el-dialog
            v-model="receiveDialogVisible"
            title="收款"
            width="500px"
        >
            <el-form
                ref="receiveFormRef"
                :model="receiveForm"
                :rules="receiveRules"
                label-width="120px"
            >
                <el-form-item label="收款金额" prop="received_amount">
                    <el-input-number v-model="receiveForm.received_amount" :min="0" :precision="2" :max="receiveForm.max_amount" style="width: 100%" />
                    <div style="margin-top: 5px; color: #999;">可收金额：¥{{ receiveForm.max_amount }}</div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="receiveDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitReceive" :loading="receiveSubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 结算详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="结算详情"
            width="1000px"
            :close-on-click-modal="false"
        >
            <div v-loading="detailLoading">
                <el-descriptions :column="2" border v-if="currentSettlement">
                <el-descriptions-item label="结算单号">{{ currentSettlement.settlement_no }}</el-descriptions-item>
                <el-descriptions-item label="客户">{{ currentSettlement.customer?.name }}</el-descriptions-item>
                <el-descriptions-item label="结算日期">{{ currentSettlement.settlement_date }}</el-descriptions-item>
                <el-descriptions-item label="结算金额">¥{{ currentSettlement.total_amount }}</el-descriptions-item>
                <el-descriptions-item label="已收金额">¥{{ currentSettlement.received_amount || 0 }}</el-descriptions-item>
                <el-descriptions-item label="余额">¥{{ (currentSettlement.total_amount - (currentSettlement.received_amount || 0)).toFixed(2) }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getStatusType(currentSettlement.status)">{{ getStatusText(currentSettlement.status) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentSettlement.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
                <el-table :data="currentSettlement?.items || []" style="margin-top: 20px;" v-if="currentSettlement">
                    <el-table-column prop="reference_type" label="关联类型" width="120">
                        <template #default="{ row }">
                            {{ row.reference_type == 'sales_order' ? '销售订单' : '销售退货' }}
                        </template>
                    </el-table-column>
                    <el-table-column prop="reference_no" label="关联单据号" width="150" />
                    <el-table-column prop="amount" label="金额" width="120">
                        <template #default="{ row }">¥{{ row.amount }}</template>
                    </el-table-column>
                    <el-table-column prop="remark" label="备注" />
                </el-table>
            </div>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const submitLoading = ref(false);
const receiveSubmitLoading = ref(false);
const dialogVisible = ref(false);
const receiveDialogVisible = ref(false);
const detailVisible = ref(false);
const detailLoading = ref(false);
const viewLoadingId = ref(null);
const formRef = ref(null);
const receiveFormRef = ref(null);
const settlements = ref([]);
const customers = ref([]);
const currencies = ref([]);
const salesOrders = ref([]);
const salesReturns = ref([]);
const currentSettlement = ref(null);
const currentReceiveSettlement = ref(null);

const searchForm = reactive({
    settlement_no: '',
    customer_id: null,
    status: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    customer_id: null,
    settlement_date: new Date().toISOString().split('T')[0],
    currency_id: null,
    remark: '',
    items: []
});

const receiveForm = reactive({
    received_amount: 0,
    max_amount: 0
});

const rules = {
    customer_id: [{ required: true, message: '请选择客户', trigger: 'change' }],
    settlement_date: [{ required: true, message: '请选择结算日期', trigger: 'change' }],
    items: [
        { required: true, message: '请添加结算明细', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条结算明细', trigger: 'change' }
    ]
};

const receiveRules = {
    received_amount: [{ required: true, message: '请输入收款金额', trigger: 'blur' }]
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (item.reference_amount || 0);
    }, 0);
});

const getStatusType = (status) => {
    const statusMap = {
        'pending': 'warning',
        'approved': 'success',
        'paid': 'info'
    };
    return statusMap[status] || 'info';
};

const getStatusText = (status) => {
    const statusMap = {
        'pending': '待审核',
        'approved': '已审核',
        'paid': '已收款'
    };
    return statusMap[status] || status;
};

const getReferencesByType = (type) => {
    if (type == 'sales_order') {
        return salesOrders.value.filter(order => order.customer_id == form.customer_id && order.status == 'approved');
    } else if (type == 'sales_return') {
        return salesReturns.value.filter(ret => ret.customer_id == form.customer_id && ret.status == 'approved');
    }
    return [];
};

const loadSettlements = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/sales-settlements', { params });
        settlements.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载结算列表失败');
    } finally {
        loading.value = false;
    }
};

const loadCustomers = async () => {
    try {
        const response = await api.get('/customers', { params: { per_page: 1000 } });
        customers.value = response.data.data;
    } catch (error) {
        console.error('加载客户列表失败:', error);
    }
};

const loadCurrencies = async () => {
    try {
        const response = await api.get('/currencies', { params: { per_page: 1000, is_active: 1 } });
        currencies.value = response.data.data;
        if (currencies.value.length > 0 && !form.currency_id) {
            const defaultCurrency = currencies.value.find(c => c.is_default) || currencies.value[0];
            form.currency_id = defaultCurrency.id;
        }
    } catch (error) {
        console.error('加载币种列表失败:', error);
    }
};

const loadSalesOrders = async () => {
    try {
        const response = await api.get('/sales-orders', { params: { per_page: 1000, status: 'approved' } });
        salesOrders.value = response.data.data;
    } catch (error) {
        console.error('加载销售订单列表失败:', error);
    }
};

const loadSalesReturns = async () => {
    try {
        const response = await api.get('/sales-returns', { params: { per_page: 1000, status: 'approved' } });
        salesReturns.value = response.data.data;
    } catch (error) {
        console.error('加载销售退货列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadSettlements();
};

const handleReset = () => {
    searchForm.settlement_no = '';
    searchForm.customer_id = null;
    searchForm.status = null;
    handleSearch();
};

const handleAdd = () => {
    Object.assign(form, {
        customer_id: null,
        settlement_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.value.find(c => c.is_default)?.id || currencies.value[0]?.id || null,
        remark: '',
        items: []
    });
    dialogVisible.value = true;
};

const handleView = async (row) => {
    // 防止重复点击
    if (viewLoadingId.value !== null) {
        return;
    }
    
    viewLoadingId.value = row.id;
    detailLoading.value = true;
    detailVisible.value = true;
    currentSettlement.value = null;
    
    try {
        const response = await api.get(`/sales-settlements/${row.id}`);
        currentSettlement.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载结算详情失败');
        detailVisible.value = false;
    } finally {
        detailLoading.value = false;
        viewLoadingId.value = null;
    }
};

const handleApprove = async (row) => {
    try {
        await ElMessageBox.confirm('确定要审核该结算单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/sales-settlements/${row.id}/approve`);
        ElMessage.success('审核成功');
        loadSettlements();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '审核失败');
        }
    }
};

const handleReceive = async (row) => {
    try {
        const response = await api.get(`/sales-settlements/${row.id}`);
        const settlement = response.data.data;
        currentReceiveSettlement.value = settlement;
        receiveForm.received_amount = settlement.total_amount - (settlement.received_amount || 0);
        receiveForm.max_amount = settlement.total_amount - (settlement.received_amount || 0);
        receiveDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载结算信息失败');
    }
};

const handleSubmitReceive = async () => {
    if (!receiveFormRef.value) return;
    
    await receiveFormRef.value.validate(async (valid) => {
        if (valid) {
            receiveSubmitLoading.value = true;
            try {
                await api.post(`/sales-settlements/${currentReceiveSettlement.value.id}/receive`, {
                    received_amount: receiveForm.received_amount
                });
                ElMessage.success('收款成功');
                receiveDialogVisible.value = false;
                loadSettlements();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '收款失败');
            } finally {
                receiveSubmitLoading.value = false;
            }
        }
    });
};

const handleCustomerChange = () => {
    form.items = [];
    loadSalesOrders();
    loadSalesReturns();
};

const handleAddItem = () => {
    form.items.push({
        reference_type: 'sales_order',
        reference_id: null,
        reference_amount: 0,
        remark: ''
    });
};

const handleRemoveItem = (index) => {
    form.items.splice(index, 1);
};

const handleItemTypeChange = (index) => {
    form.items[index].reference_id = null;
    form.items[index].reference_amount = 0;
};

const handleItemReferenceChange = async (index) => {
    const item = form.items[index];
    if (item.reference_id && item.reference_type) {
        try {
            if (item.reference_type == 'sales_order') {
                const response = await api.get(`/sales-orders/${item.reference_id}`);
                item.reference_amount = response.data.data.total_amount;
            } else if (item.reference_type == 'sales_return') {
                const response = await api.get(`/sales-returns/${item.reference_id}`);
                item.reference_amount = response.data.data.total_amount;
            }
        } catch (error) {
            ElMessage.error('加载单据信息失败');
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            if (form.items.length == 0) {
                ElMessage.warning('请至少添加一条结算明细');
                return;
            }
            submitLoading.value = true;
            try {
                const data = {
                    ...form,
                    currency_id: form.currency_id || null,
                    remark: form.remark || null,
                    items: form.items.map(item => ({
                        reference_type: item.reference_type,
                        reference_id: item.reference_id,
                        remark: item.remark || null
                    }))
                };
                await api.post('/sales-settlements', data);
                ElMessage.success('创建成功');
                dialogVisible.value = false;
                loadSettlements();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                submitLoading.value = false;
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

const handleSizeChange = () => {
    loadSettlements();
};

const handlePageChange = () => {
    loadSettlements();
};

onMounted(() => {
    loadSettlements();
    loadCustomers();
    loadCurrencies();
    loadSalesOrders();
    loadSalesReturns();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

