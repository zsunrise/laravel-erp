<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">采购订单</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增订单
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="订单号">
                    <el-input v-model="searchForm.order_no" placeholder="订单号" clearable />
                </el-form-item>
                <el-form-item label="供应商">
                    <el-input v-model="searchForm.supplier" placeholder="供应商名称" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 150px">
                        <el-option label="草稿" :value="1" />
                        <el-option label="待审核" :value="2" />
                        <el-option label="已审核" :value="3" />
                        <el-option label="部分入库" :value="4" />
                        <el-option label="已完成" :value="5" />
                        <el-option label="已取消" :value="6" />
                    </el-select>
                </el-form-item>
                <el-form-item label="日期">
                    <el-date-picker
                        v-model="searchForm.date_range"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="orders" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="order_no" label="订单号" width="150" />
                <el-table-column prop="supplier.name" label="供应商" />
                <el-table-column prop="total_amount" label="订单金额" width="120">
                    <template #default="{ row }">
                        ¥{{ row.total_amount }}
                    </template>
                </el-table-column>
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <span 
                            :class="getStatusClass(row.status)"
                            class="status-badge"
                        >
                            {{ row.status_text || getStatusText(row.status) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column prop="order_date" label="订单日期" width="120" />
                <el-table-column prop="expected_date" label="预计到货" width="120" />
                <el-table-column label="操作" width="300" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)" :loading="viewLoadingId === row.id" :disabled="viewLoadingId !== null" class="interactive">查看</el-button>
                        <el-button type="warning" size="small" @click="handleEdit(row)" v-if="row.status == 1" class="interactive">编辑</el-button>
                        <el-button type="info" size="small" @click="handleSubmit(row)" v-if="row.status == 1" class="interactive">提交审核</el-button>
                        <el-button type="success" size="small" @click="handleApprove(row)" v-if="row.status == 2" class="interactive">审核</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)" v-if="row.status == 1" class="interactive">删除</el-button>
                        <el-button type="danger" size="small" @click="handleCancel(row)" v-if="row.status != 5 && row.status != 6" class="interactive">取消</el-button>
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

        <!-- 订单详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="订单详情"
            width="1000px"
            :close-on-click-modal="false"
        >
            <div v-loading="detailLoading">
                <el-descriptions :column="2" border v-if="currentOrder">
                <el-descriptions-item label="订单号">{{ currentOrder.order_no }}</el-descriptions-item>
                <el-descriptions-item label="供应商">{{ currentOrder.supplier?.name }}</el-descriptions-item>
                <el-descriptions-item label="订单日期">{{ currentOrder.order_date }}</el-descriptions-item>
                <el-descriptions-item label="预计到货">{{ currentOrder.expected_date }}</el-descriptions-item>
                <el-descriptions-item label="订单金额">¥{{ currentOrder.total_amount }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getStatusType(currentOrder.status)">{{ currentOrder.status_text || getStatusText(currentOrder.status) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentOrder.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
                <el-table :data="currentOrder?.items || []" style="margin-top: 20px;" v-if="currentOrder">
                    <el-table-column prop="product.name" label="商品名称" />
                    <el-table-column prop="quantity" label="订单数量" width="100" />
                    <el-table-column prop="received_quantity" label="已收数量" width="100">
                        <template #default="{ row }">{{ row.received_quantity || 0 }}</template>
                    </el-table-column>
                    <el-table-column label="未收数量" width="100">
                        <template #default="{ row }">{{ (row.quantity || 0) - (row.received_quantity || 0) }}</template>
                    </el-table-column>
                    <el-table-column prop="unit_price" label="单价" width="120">
                        <template #default="{ row }">¥{{ row.unit_price || 0 }}</template>
                    </el-table-column>
                    <el-table-column prop="subtotal" label="小计" width="120">
                        <template #default="{ row }">
                            ¥{{ row.subtotal ? Number(row.subtotal).toFixed(2) : (row.quantity && row.unit_price ? (row.quantity * row.unit_price * (1 - (row.discount_rate || 0) / 100)).toFixed(2) : '0.00') }}
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <template #footer v-if="currentOrder">
                <el-button @click="detailVisible = false">关闭</el-button>
                <el-button v-if="currentOrder.status == 3 || currentOrder.status == 4" type="success" @click="handleReceive(currentOrder)">入库</el-button>
                <el-button v-if="currentOrder.status == 1" type="info" @click="handleSubmit(currentOrder)">提交审核</el-button>
                <el-button v-if="currentOrder.status == 2" type="success" @click="handleApprove(currentOrder)">审核</el-button>
                <el-button v-if="currentOrder.status != 5 && currentOrder.status != 6" type="danger" @click="handleCancelOrder(currentOrder)">取消订单</el-button>
            </template>
        </el-dialog>

        <!-- 新增/编辑订单对话框 -->
        <el-dialog
            v-model="formVisible"
            :title="formTitle"
            width="1200px"
        >
            <el-form :model="orderForm" :rules="orderRules" ref="orderFormRef" label-width="120px">
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="供应商" prop="supplier_id">
                            <el-select v-model="orderForm.supplier_id" filterable placeholder="请选择供应商" style="width: 100%">
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
                        <el-form-item label="订单日期" prop="order_date">
                            <el-date-picker v-model="orderForm.order_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="预计到货" prop="expected_date">
                            <el-date-picker v-model="orderForm.expected_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="仓库" prop="warehouse_id">
                            <el-select v-model="orderForm.warehouse_id" placeholder="请选择仓库" style="width: 100%">
                                <el-option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :label="warehouse.name"
                                    :value="warehouse.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="备注">
                    <el-input v-model="orderForm.remark" type="textarea" :rows="2" placeholder="请输入备注" />
                </el-form-item>
                <el-form-item label="订单明细" prop="items">
                    <el-button type="primary" size="small" @click="handleAddItem">添加商品</el-button>
                    <el-table :data="orderForm.items" style="margin-top: 10px;" border>
                        <el-table-column prop="product.name" label="商品名称" width="200">
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
                                <el-input-number v-model="row.quantity" :min="0.01" :precision="2" @change="handleItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="单价" width="150">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.unit_price" :min="0" :precision="2" @change="handleItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="小计" width="120">
                            <template #default="{ row }">
                                ¥{{ (row.quantity * row.unit_price).toFixed(2) }}
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ $index }">
                                <el-button type="danger" size="small" @click="handleRemoveItem($index)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div style="margin-top: 10px; text-align: right;">
                        <span style="font-size: 16px; font-weight: bold;">订单总额: ¥{{ totalAmount.toFixed(2) }}</span>
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="formVisible = false">取消</el-button>
                <el-button type="primary" @click="submitOrder" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const detailVisible = ref(false);
const detailLoading = ref(false);
const viewLoadingId = ref(null);
const formVisible = ref(false);
const submitLoading = ref(false);
const orders = ref([]);
const currentOrder = ref(null);
const suppliers = ref([]);
const products = ref([]);
const warehouses = ref([]);
const orderFormRef = ref(null);
const isEdit = ref(false);

const searchForm = reactive({
    order_no: '',
    supplier: '',
    status: null,
    date_range: null
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const orderForm = reactive({
    supplier_id: null,
    order_date: new Date().toISOString().split('T')[0],
    expected_date: null,
    warehouse_id: null,
    remark: '',
    items: []
});

const orderRules = {
    supplier_id: [{ required: true, message: '请选择供应商', trigger: 'change' }],
    order_date: [{ required: true, message: '请选择订单日期', trigger: 'change' }],
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    items: [
        { required: true, message: '请添加订单明细', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条订单明细', trigger: 'change' }
    ]
};

const totalAmount = computed(() => {
    return orderForm.items.reduce((sum, item) => {
        return sum + (item.quantity || 0) * (item.unit_price || 0);
    }, 0);
});

const formTitle = computed(() => {
    return isEdit.value ? '编辑订单' : '新增订单';
});

// 状态常量映射（数字 => 中文）
const STATUS_MAP = {
    1: '草稿',
    2: '待审核',
    3: '已审核',
    4: '部分入库',
    5: '已完成',
    6: '已取消'
};

const getStatusType = (status) => {
    const statusMap = {
        1: 'info',      // draft
        2: 'warning',   // pending
        3: 'success',   // approved
        4: 'primary',   // partial
        5: 'success',   // completed
        6: 'danger'     // cancelled
    };
    return statusMap[status] || 'info';
};

const getStatusClass = (status) => {
    const classMap = {
        1: 'badge-info',      // draft
        2: 'badge-warning',   // pending
        3: 'badge-success',   // approved
        4: 'badge-primary',   // partial
        5: 'badge-success',   // completed
        6: 'badge-muted'      // cancelled
    };
    return classMap[status] || 'badge-muted';
};

const getStatusText = (status) => {
    return STATUS_MAP[status] || status;
};

const loadOrders = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page
        };
        // 只添加非空值参数
        if (searchForm.order_no) {
            params.order_no = searchForm.order_no;
        }
        if (searchForm.supplier) {
            params.supplier = searchForm.supplier;
        }
        if (searchForm.status) {
            params.status = searchForm.status;
        }
        if (searchForm.date_range && searchForm.date_range.length == 2) {
            params.start_date = searchForm.date_range[0];
            params.end_date = searchForm.date_range[1];
        }
        const response = await api.get('/purchase-orders', { params });
        orders.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载订单列表失败');
    } finally {
        loading.value = false;
    }
};

const loadSuppliers = async () => {
    try {
        const response = await api.get('/suppliers', { params: { per_page: 1000 } });
        suppliers.value = response.data.data || [];
    } catch (error) {
        console.error('加载供应商列表失败:', error);
        ElMessage.error('加载供应商列表失败');
        suppliers.value = [];
    }
};

const loadProducts = async () => {
    try {
        const response = await api.get('/products', { params: { per_page: 1000 } });
        products.value = response.data.data || [];
    } catch (error) {
        console.error('加载商品列表失败:', error);
        ElMessage.error('加载商品列表失败');
        products.value = [];
    }
};

const loadWarehouses = async () => {
    try {
        const response = await api.get('/warehouses', { params: { per_page: 1000 } });
        warehouses.value = response.data.data || [];
    } catch (error) {
        console.error('加载仓库列表失败:', error);
        ElMessage.error('加载仓库列表失败');
        warehouses.value = [];
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadOrders();
};

const handleReset = () => {
    searchForm.order_no = '';
    searchForm.supplier = '';
    searchForm.status = null;
    searchForm.date_range = null;
    handleSearch();
};

const handleAdd = () => {
    isEdit.value = false;
    orderForm.supplier_id = null;
    orderForm.order_date = new Date().toISOString().split('T')[0];
    orderForm.expected_date = null;
    orderForm.warehouse_id = null;
    orderForm.remark = '';
    orderForm.items = [];
    // 确保数据已加载
    if (suppliers.value.length === 0) {
        loadSuppliers();
    }
    if (products.value.length === 0) {
        loadProducts();
    }
    if (warehouses.value.length === 0) {
        loadWarehouses();
    }
    formVisible.value = true;
};

const handleView = async (row) => {
    // 防止重复点击
    if (viewLoadingId.value !== null) {
        return;
    }
    
    viewLoadingId.value = row.id;
    detailLoading.value = true;
    detailVisible.value = true;
    currentOrder.value = null;
    
    try {
        const response = await api.get(`/purchase-orders/${row.id}`);
        currentOrder.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载订单详情失败');
        detailVisible.value = false;
    } finally {
        detailLoading.value = false;
        viewLoadingId.value = null;
    }
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/purchase-orders/${row.id}`);
        const order = response.data.data;
        isEdit.value = true;
        orderForm.supplier_id = order.supplier_id;
        orderForm.order_date = order.order_date;
        orderForm.expected_date = order.expected_date;
        orderForm.warehouse_id = order.warehouse_id;
        orderForm.remark = order.remark || '';
        orderForm.items = order.items.map(item => ({
            product_id: item.product_id,
            product: item.product,
            quantity: item.quantity,
            unit_price: item.unit_price
        }));
        orderForm.id = order.id;
        formVisible.value = true;
    } catch (error) {
        ElMessage.error('加载订单失败');
    }
};

const handleAddItem = () => {
    orderForm.items.push({
        product_id: null,
        product: null,
        quantity: 1,
        unit_price: 0
    });
};

const handleRemoveItem = (index) => {
    orderForm.items.splice(index, 1);
};

const handleItemProductChange = (index) => {
    const productId = orderForm.items[index].product_id;
    const product = products.value.find(p => p.id == productId);
    if (product) {
        orderForm.items[index].product = product;
        if (!orderForm.items[index].unit_price) {
            orderForm.items[index].unit_price = product.purchase_price || 0;
        }
    }
};

const handleItemChange = () => {
    // 触发计算
};

const submitOrder = async () => {
    if (!orderFormRef.value) return;
    await orderFormRef.value.validate(async (valid) => {
        if (valid) {
            if (orderForm.items.length == 0) {
                ElMessage.warning('请至少添加一条订单明细');
                return;
            }
            submitLoading.value = true;
            try {
                const data = {
                    ...orderForm,
                    expected_date: orderForm.expected_date || null,
                    currency_id: orderForm.currency_id || null,
                    remark: orderForm.remark || null,
                    items: orderForm.items.map(item => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        tax_rate: item.tax_rate || null,
                        discount_rate: item.discount_rate || null,
                        remark: item.remark || null
                    }))
                };
                if (isEdit.value) {
                    await api.put(`/purchase-orders/${orderForm.id}`, data);
                    ElMessage.success('订单更新成功');
                } else {
                    await api.post('/purchase-orders', data);
                    ElMessage.success('订单创建成功');
                }
                formVisible.value = false;
                loadOrders();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                submitLoading.value = false;
            }
        }
    });
};

const handleReceive = async (order) => {
    try {
        // 检查订单是否有明细项
        if (!order.items || order.items.length === 0) {
            ElMessage.warning('订单没有明细项');
            return;
        }

        // 构建收货明细项：收货所有未收货完的商品，收货数量为剩余未收货数量
        const receiveItems = order.items
            .filter(item => {
                const receivedQty = item.received_quantity || 0;
                const orderQty = item.quantity || 0;
                return receivedQty < orderQty; // 只收货未收货完的商品
            })
            .map(item => {
                const receivedQty = item.received_quantity || 0;
                const orderQty = item.quantity || 0;
                const remainingQty = orderQty - receivedQty;
                return {
                    item_id: item.id,
                    quantity: remainingQty // 收货剩余数量
                };
            });

        if (receiveItems.length === 0) {
            ElMessage.warning('所有商品已收货完成');
            return;
        }

        await ElMessageBox.confirm(
            `确定要执行入库操作吗？将收货 ${receiveItems.length} 个商品。`,
            '提示',
            {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }
        );

        await api.post(`/purchase-orders/${order.id}/receive`, {
            items: receiveItems
        });
        
        ElMessage.success('入库成功');
        detailVisible.value = false;
        loadOrders();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '入库失败');
        }
    }
};

const handleSubmit = async (row) => {
    try {
        await ElMessageBox.confirm('确定要提交该订单审核吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/purchase-orders/${row.id}/submit`);
        ElMessage.success('提交成功');
        loadOrders();
        if (detailVisible.value && currentOrder.value?.id === row.id) {
            detailVisible.value = false;
        }
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '提交失败');
        }
    }
};

const handleApprove = async (row) => {
    try {
        await ElMessageBox.confirm('确定要审核通过该订单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/purchase-orders/${row.id}/approve`);
        ElMessage.success('审核成功');
        loadOrders();
        if (detailVisible.value && currentOrder.value?.id === row.id) {
            detailVisible.value = false;
        }
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '审核失败');
        }
    }
};

const handleCancel = async (row) => {
    try {
        await ElMessageBox.confirm('确定要取消该订单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/purchase-orders/${row.id}/cancel`);
        ElMessage.success('取消成功');
        loadOrders();
        if (detailVisible.value && currentOrder.value?.id === row.id) {
            detailVisible.value = false;
        }
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '取消失败');
        }
    }
};

const handleCancelOrder = async (order) => {
    await handleCancel(order);
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该订单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/purchase-orders/${row.id}`);
        ElMessage.success('删除成功');
        loadOrders();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handleSizeChange = () => {
    pagination.page = 1;
    loadOrders();
};

const handlePageChange = () => {
    loadOrders();
};

onMounted(() => {
    loadOrders();
    loadSuppliers();
    loadProducts();
    loadWarehouses();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

