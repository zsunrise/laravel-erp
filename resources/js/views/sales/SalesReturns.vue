<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">销售退货</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增退货
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="退货单号">
                    <el-input v-model="searchForm.return_no" placeholder="退货单号" clearable />
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
                        <el-option label="已取消" value="cancelled" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="returns" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="return_no" label="退货单号" width="150" />
                <el-table-column prop="customer.name" label="客户" />
                <el-table-column prop="warehouse.name" label="仓库" width="150" />
                <el-table-column prop="total_amount" label="退货金额" width="120">
                    <template #default="{ row }">¥{{ row.total_amount }}</template>
                </el-table-column>
                <el-table-column prop="return_date" label="退货日期" width="120" />
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
                        <el-button type="success" size="small" @click="handleApprove(row)" v-if="row.status == 'pending'">审核</el-button>
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

        <!-- 退货表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            title="新增销售退货"
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
                        <el-form-item label="销售订单" prop="sales_order_id">
                            <el-select v-model="form.sales_order_id" filterable placeholder="请选择销售订单（可选）" clearable @change="handleOrderChange" style="width: 100%">
                                <el-option
                                    v-for="order in salesOrders"
                                    :key="order.id"
                                    :label="`${order.order_no} - ${order.customer?.name || ''}`"
                                    :value="order.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
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
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="仓库" prop="warehouse_id">
                            <el-select v-model="form.warehouse_id" filterable placeholder="请选择仓库" style="width: 100%">
                                <el-option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :label="warehouse.name"
                                    :value="warehouse.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="退货日期" prop="return_date">
                            <el-date-picker v-model="form.return_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
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
                <el-form-item label="退货明细" prop="items">
                    <el-button type="primary" size="small" @click="handleAddItem">添加明细</el-button>
                    <el-table :data="form.items" style="margin-top: 10px;" border>
                        <el-table-column prop="product.name" label="商品" width="200">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.product_id" filterable placeholder="请选择商品" @change="handleItemProductChange($index)" style="width: 100%">
                                    <el-option
                                        v-for="product in products"
                                        :key="product.id"
                                        :label="`${product.name} (${product.sku})`"
                                        :value="product.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="数量" width="150">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.quantity" :min="1" @change="handleItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="单价" width="150">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.unit_price" :min="0" :precision="2" @change="handleItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="税率(%)" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.tax_rate" :min="0" :max="100" :precision="2" @change="handleItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="小计" width="120">
                            <template #default="{ row }">
                                ¥{{ (row.quantity * row.unit_price * (1 + (row.tax_rate || 0) / 100)).toFixed(2) }}
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

        <!-- 退货详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="退货详情"
            width="1000px"
        >
            <el-descriptions :column="2" border v-if="currentReturn">
                <el-descriptions-item label="退货单号">{{ currentReturn.return_no }}</el-descriptions-item>
                <el-descriptions-item label="客户">{{ currentReturn.customer?.name }}</el-descriptions-item>
                <el-descriptions-item label="仓库">{{ currentReturn.warehouse?.name }}</el-descriptions-item>
                <el-descriptions-item label="退货日期">{{ currentReturn.return_date }}</el-descriptions-item>
                <el-descriptions-item label="退货金额">¥{{ currentReturn.total_amount }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getStatusType(currentReturn.status)">{{ getStatusText(currentReturn.status) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentReturn.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
            <el-table :data="currentReturn?.items || []" style="margin-top: 20px;">
                <el-table-column prop="product.name" label="商品名称" />
                <el-table-column prop="quantity" label="数量" width="100" />
                <el-table-column prop="unit_price" label="单价" width="120">
                    <template #default="{ row }">¥{{ row.unit_price }}</template>
                </el-table-column>
                <el-table-column prop="tax_rate" label="税率(%)" width="100" />
                <el-table-column label="小计" width="120">
                    <template #default="{ row }">
                        ¥{{ (row.quantity * row.unit_price * (1 + (row.tax_rate || 0) / 100)).toFixed(2) }}
                    </template>
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
const dialogVisible = ref(false);
const detailVisible = ref(false);
const formRef = ref(null);
const returns = ref([]);
const customers = ref([]);
const warehouses = ref([]);
const currencies = ref([]);
const products = ref([]);
const salesOrders = ref([]);
const currentReturn = ref(null);

const searchForm = reactive({
    return_no: '',
    customer_id: null,
    status: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    sales_order_id: null,
    customer_id: null,
    warehouse_id: null,
    return_date: new Date().toISOString().split('T')[0],
    currency_id: null,
    remark: '',
    items: []
});

const rules = {
    customer_id: [{ required: true, message: '请选择客户', trigger: 'change' }],
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    return_date: [{ required: true, message: '请选择退货日期', trigger: 'change' }],
    items: [
        { required: true, message: '请添加退货明细', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条退货明细', trigger: 'change' }
    ]
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (item.quantity || 0) * (item.unit_price || 0) * (1 + ((item.tax_rate || 0) / 100));
    }, 0);
});

const getStatusType = (status) => {
    const statusMap = {
        'pending': 'warning',
        'approved': 'success',
        'cancelled': 'danger'
    };
    return statusMap[status] || 'info';
};

const getStatusText = (status) => {
    const statusMap = {
        'pending': '待审核',
        'approved': '已审核',
        'cancelled': '已取消'
    };
    return statusMap[status] || status;
};

const loadReturns = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/sales-returns', { params });
        returns.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载退货列表失败');
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

const loadWarehouses = async () => {
    try {
        const response = await api.get('/warehouses', { params: { per_page: 1000, is_active: 1 } });
        warehouses.value = response.data.data;
    } catch (error) {
        console.error('加载仓库列表失败:', error);
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

const loadProducts = async () => {
    try {
        const response = await api.get('/products', { params: { per_page: 1000 } });
        products.value = response.data.data;
    } catch (error) {
        console.error('加载产品列表失败:', error);
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

const handleSearch = () => {
    pagination.page = 1;
    loadReturns();
};

const handleReset = () => {
    searchForm.return_no = '';
    searchForm.customer_id = null;
    searchForm.status = null;
    handleSearch();
};

const handleAdd = () => {
    Object.assign(form, {
        sales_order_id: null,
        customer_id: null,
        warehouse_id: null,
        return_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.value.find(c => c.is_default)?.id || currencies.value[0]?.id || null,
        remark: '',
        items: []
    });
    dialogVisible.value = true;
};

const handleView = async (row) => {
    try {
        const response = await api.get(`/sales-returns/${row.id}`);
        currentReturn.value = response.data.data;
        detailVisible.value = true;
    } catch (error) {
        ElMessage.error('加载退货详情失败');
    }
};

const handleApprove = async (row) => {
    try {
        await ElMessageBox.confirm('确定要审核该退货单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/sales-returns/${row.id}/approve`);
        ElMessage.success('审核成功');
        loadReturns();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '审核失败');
        }
    }
};

const handleOrderChange = async (orderId) => {
    if (orderId) {
        try {
            const response = await api.get(`/sales-orders/${orderId}`);
            const order = response.data.data;
            form.customer_id = order.customer_id;
            form.warehouse_id = order.warehouse_id;
            form.currency_id = order.currency_id;
            form.items = order.items.map(item => ({
                product_id: item.product_id,
                product: item.product,
                quantity: item.quantity,
                unit_price: item.unit_price,
                tax_rate: item.tax_rate || 0,
                remark: ''
            }));
        } catch (error) {
            ElMessage.error('加载订单信息失败');
        }
    }
};

const handleCustomerChange = () => {
    // 客户改变时的处理
};

const handleAddItem = () => {
    form.items.push({
        product_id: null,
        product: null,
        quantity: 1,
        unit_price: 0,
        tax_rate: 0,
        remark: ''
    });
};

const handleRemoveItem = (index) => {
    form.items.splice(index, 1);
};

const handleItemProductChange = (index) => {
    const productId = form.items[index].product_id;
    const product = products.value.find(p => p.id == productId);
    if (product) {
        form.items[index].product = product;
        if (!form.items[index].unit_price) {
            form.items[index].unit_price = product.sale_price || 0;
        }
    }
};

const handleItemChange = () => {
    // 触发计算
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            if (form.items.length == 0) {
                ElMessage.warning('请至少添加一条退货明细');
                return;
            }
            submitLoading.value = true;
            try {
                const data = {
                    ...form,
                    sales_order_id: form.sales_order_id || null,
                    currency_id: form.currency_id || null,
                    remark: form.remark || null,
                    items: form.items.map(item => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        tax_rate: item.tax_rate || null,
                        remark: item.remark || null
                    }))
                };
                await api.post('/sales-returns', data);
                ElMessage.success('创建成功');
                dialogVisible.value = false;
                loadReturns();
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
    loadReturns();
};

const handlePageChange = () => {
    loadReturns();
};

onMounted(() => {
    loadReturns();
    loadCustomers();
    loadWarehouses();
    loadCurrencies();
    loadProducts();
    loadSalesOrders();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

