<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">采购结算</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增结算
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="结算单号">
                    <el-input v-model="searchForm.settlement_no" placeholder="结算单号" clearable />
                </el-form-item>
                <el-form-item label="供应商">
                    <el-select v-model="searchForm.supplier_id" filterable placeholder="全部" clearable style="width: 200px">
                        <el-option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :label="supplier.name"
                            :value="supplier.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.status" placeholder="全部" clearable>
                        <el-option label="待审核" value="pending" />
                        <el-option label="已审核" value="approved" />
                        <el-option label="已付款" value="paid" />
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
                <el-table-column prop="supplier.name" label="供应商" />
                <el-table-column prop="total_amount" label="结算金额" width="120">
                    <template #default="{ row }">¥{{ row.total_amount }}</template>
                </el-table-column>
                <el-table-column prop="paid_amount" label="已付金额" width="120">
                    <template #default="{ row }">¥{{ row.paid_amount || 0 }}</template>
                </el-table-column>
                <el-table-column prop="balance" label="余额" width="120">
                    <template #default="{ row }">¥{{ (row.total_amount - (row.paid_amount || 0)).toFixed(2) }}</template>
                </el-table-column>
                <el-table-column prop="settlement_date" label="结算日期" width="120" />
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="250" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
                        <el-button type="success" size="small" @click="handleApprove(row)" v-if="row.status == 'pending'">审核</el-button>
                        <el-button type="warning" size="small" @click="handlePay(row)" v-if="row.status == 'approved'">付款</el-button>
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
            title="新增采购结算"
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
                        <el-form-item label="供应商" prop="supplier_id">
                            <el-select v-model="form.supplier_id" filterable placeholder="请选择供应商" @change="handleSupplierChange" style="width: 100%">
                                <el-option
                                    v-for="supplier in suppliers"
                                    :key="supplier.id"
                                    :label="supplier.name"
                                    :value="supplier.id"
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
                                    <el-option label="采购订单" value="purchase_order" />
                                    <el-option label="采购退货" value="purchase_return" />
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

        <!-- 付款对话框 -->
        <el-dialog
            v-model="payDialogVisible"
            title="付款"
            width="500px"
        >
            <el-form
                ref="payFormRef"
                :model="payForm"
                :rules="payRules"
                label-width="120px"
            >
                <el-form-item label="付款金额" prop="paid_amount">
                    <el-input-number v-model="payForm.paid_amount" :min="0" :precision="2" :max="payForm.max_amount" style="width: 100%" />
                    <div style="margin-top: 5px; color: #999;">可付金额：¥{{ payForm.max_amount }}</div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="payDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitPay" :loading="paySubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 结算详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="结算详情"
            width="1000px"
        >
            <el-descriptions :column="2" border v-if="currentSettlement">
                <el-descriptions-item label="结算单号">{{ currentSettlement.settlement_no }}</el-descriptions-item>
                <el-descriptions-item label="供应商">{{ currentSettlement.supplier?.name }}</el-descriptions-item>
                <el-descriptions-item label="结算日期">{{ currentSettlement.settlement_date }}</el-descriptions-item>
                <el-descriptions-item label="结算金额">¥{{ currentSettlement.total_amount }}</el-descriptions-item>
                <el-descriptions-item label="已付金额">¥{{ currentSettlement.paid_amount || 0 }}</el-descriptions-item>
                <el-descriptions-item label="余额">¥{{ (currentSettlement.total_amount - (currentSettlement.paid_amount || 0)).toFixed(2) }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getStatusType(currentSettlement.status)">{{ getStatusText(currentSettlement.status) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentSettlement.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
            <el-table :data="currentSettlement?.items || []" style="margin-top: 20px;">
                <el-table-column prop="reference_type" label="关联类型" width="120">
                    <template #default="{ row }">
                        {{ row.reference_type == 'purchase_order' ? '采购订单' : '采购退货' }}
                    </template>
                </el-table-column>
                <el-table-column prop="reference_no" label="关联单据号" width="150" />
                <el-table-column prop="amount" label="金额" width="120">
                    <template #default="{ row }">¥{{ row.amount }}</template>
                </el-table-column>
                <el-table-column prop="remark" label="备注" />
            </el-table>
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
const paySubmitLoading = ref(false);
const dialogVisible = ref(false);
const payDialogVisible = ref(false);
const detailVisible = ref(false);
const formRef = ref(null);
const payFormRef = ref(null);
const settlements = ref([]);
const suppliers = ref([]);
const currencies = ref([]);
const purchaseOrders = ref([]);
const purchaseReturns = ref([]);
const currentSettlement = ref(null);
const currentPaySettlement = ref(null);

const searchForm = reactive({
    settlement_no: '',
    supplier_id: null,
    status: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    supplier_id: null,
    settlement_date: new Date().toISOString().split('T')[0],
    currency_id: null,
    remark: '',
    items: []
});

const payForm = reactive({
    paid_amount: 0,
    max_amount: 0
});

const rules = {
    supplier_id: [{ required: true, message: '请选择供应商', trigger: 'change' }],
    settlement_date: [{ required: true, message: '请选择结算日期', trigger: 'change' }],
    items: [
        { required: true, message: '请添加结算明细', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条结算明细', trigger: 'change' }
    ]
};

const payRules = {
    paid_amount: [{ required: true, message: '请输入付款金额', trigger: 'blur' }]
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
        'paid': '已付款'
    };
    return statusMap[status] || status;
};

const getReferencesByType = (type) => {
    if (type == 'purchase_order') {
        return purchaseOrders.value.filter(order => order.supplier_id == form.supplier_id && order.status == 'approved');
    } else if (type == 'purchase_return') {
        return purchaseReturns.value.filter(ret => ret.supplier_id == form.supplier_id && ret.status == 'approved');
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
        const response = await api.get('/purchase-settlements', { params });
        settlements.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载结算列表失败');
    } finally {
        loading.value = false;
    }
};

const loadSuppliers = async () => {
    try {
        const response = await api.get('/suppliers', { params: { per_page: 1000 } });
        suppliers.value = response.data.data;
    } catch (error) {
        console.error('加载供应商列表失败:', error);
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

const loadPurchaseOrders = async () => {
    try {
        const response = await api.get('/purchase-orders', { params: { per_page: 1000, status: 'approved' } });
        purchaseOrders.value = response.data.data;
    } catch (error) {
        console.error('加载采购订单列表失败:', error);
    }
};

const loadPurchaseReturns = async () => {
    try {
        const response = await api.get('/purchase-returns', { params: { per_page: 1000, status: 'approved' } });
        purchaseReturns.value = response.data.data;
    } catch (error) {
        console.error('加载采购退货列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadSettlements();
};

const handleReset = () => {
    searchForm.settlement_no = '';
    searchForm.supplier_id = null;
    searchForm.status = null;
    handleSearch();
};

const handleAdd = () => {
    Object.assign(form, {
        supplier_id: null,
        settlement_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.value.find(c => c.is_default)?.id || currencies.value[0]?.id || null,
        remark: '',
        items: []
    });
    dialogVisible.value = true;
};

const handleView = async (row) => {
    try {
        const response = await api.get(`/purchase-settlements/${row.id}`);
        currentSettlement.value = response.data.data;
        detailVisible.value = true;
    } catch (error) {
        ElMessage.error('加载结算详情失败');
    }
};

const handleApprove = async (row) => {
    try {
        await ElMessageBox.confirm('确定要审核该结算单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/purchase-settlements/${row.id}/approve`);
        ElMessage.success('审核成功');
        loadSettlements();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '审核失败');
        }
    }
};

const handlePay = async (row) => {
    try {
        const response = await api.get(`/purchase-settlements/${row.id}`);
        const settlement = response.data.data;
        currentPaySettlement.value = settlement;
        payForm.paid_amount = settlement.total_amount - (settlement.paid_amount || 0);
        payForm.max_amount = settlement.total_amount - (settlement.paid_amount || 0);
        payDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载结算信息失败');
    }
};

const handleSubmitPay = async () => {
    if (!payFormRef.value) return;
    
    await payFormRef.value.validate(async (valid) => {
        if (valid) {
            paySubmitLoading.value = true;
            try {
                await api.post(`/purchase-settlements/${currentPaySettlement.value.id}/pay`, {
                    paid_amount: payForm.paid_amount
                });
                ElMessage.success('付款成功');
                payDialogVisible.value = false;
                loadSettlements();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '付款失败');
            } finally {
                paySubmitLoading.value = false;
            }
        }
    });
};

const handleSupplierChange = () => {
    form.items = [];
    loadPurchaseOrders();
    loadPurchaseReturns();
};

const handleAddItem = () => {
    form.items.push({
        reference_type: 'purchase_order',
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
            if (item.reference_type == 'purchase_order') {
                const response = await api.get(`/purchase-orders/${item.reference_id}`);
                item.reference_amount = response.data.data.total_amount;
            } else if (item.reference_type == 'purchase_return') {
                const response = await api.get(`/purchase-returns/${item.reference_id}`);
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
                await api.post('/purchase-settlements', data);
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
    loadSuppliers();
    loadCurrencies();
    loadPurchaseOrders();
    loadPurchaseReturns();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

