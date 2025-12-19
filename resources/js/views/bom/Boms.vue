<template>
    <div class="boms-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>BOM管理</span>
                    <el-button type="primary" @click="handleAdd">新增BOM</el-button>
                </div>
            </template>

            <el-form :inline="true" :model="searchForm" class="search-form">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="产品名称/SKU/版本" clearable />
                </el-form-item>
                <el-form-item label="产品">
                    <el-select v-model="searchForm.product_id" filterable placeholder="全部" clearable style="width: 200px">
                        <el-option
                            v-for="product in products"
                            :key="product.id"
                            :label="`${product.name} (${product.sku})`"
                            :value="product.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.is_active" placeholder="全部" clearable>
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="默认版本">
                    <el-select v-model="searchForm.is_default" placeholder="全部" clearable>
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="boms" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="product.name" label="产品名称" />
                <el-table-column prop="product.sku" label="SKU" width="120" />
                <el-table-column prop="version" label="版本" width="120" />
                <el-table-column prop="effective_date" label="生效日期" width="120" />
                <el-table-column prop="expiry_date" label="失效日期" width="120" />
                <el-table-column prop="is_default" label="默认" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_default" type="success">是</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'danger'">
                            {{ row.is_active ? '启用' : '禁用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="total_cost" label="总成本" width="120">
                    <template #default="{ row }">
                        {{ row.total_cost ? `¥${row.total_cost}` : '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="300" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
                        <el-button type="warning" size="small" @click="handleEdit(row)">编辑</el-button>
                        <el-button type="info" size="small" @click="handleSetDefault(row)" v-if="!row.is_default">设默认</el-button>
                        <el-button type="success" size="small" @click="handleCopy(row)">复制</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.per_page"
                :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next, jumper"
                @size-change="handleSizeChange"
                @current-change="handlePageChange"
                style="margin-top: 20px;"
            />
        </el-card>

        <!-- BOM表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
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
                        <el-form-item label="产品" prop="product_id">
                            <el-select v-model="form.product_id" filterable placeholder="请选择产品" style="width: 100%" @change="handleProductChange">
                                <el-option
                                    v-for="product in products"
                                    :key="product.id"
                                    :label="`${product.name} (${product.sku})`"
                                    :value="product.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="版本" prop="version">
                            <el-input v-model="form.version" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="生效日期" prop="effective_date">
                            <el-date-picker v-model="form.effective_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="失效日期" prop="expiry_date">
                            <el-date-picker v-model="form.expiry_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="默认版本" prop="is_default">
                            <el-switch v-model="form.is_default" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="状态" prop="is_active">
                            <el-switch v-model="form.is_active" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="2" />
                </el-form-item>
                <el-form-item label="BOM明细" prop="items">
                    <el-button type="primary" size="small" @click="handleAddItem">添加物料</el-button>
                    <el-table :data="form.items" style="margin-top: 10px;" border>
                        <el-table-column prop="component_product.name" label="物料" width="200">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.component_product_id" filterable placeholder="请选择物料" @change="handleItemProductChange($index)" style="width: 100%">
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
                                <el-input-number v-model="row.quantity" :min="0" :precision="4" @change="handleItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="单位" width="150">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.unit_id" placeholder="请选择单位" style="width: 100%">
                                    <el-option
                                        v-for="unit in units"
                                        :key="unit.id"
                                        :label="unit.name"
                                        :value="unit.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="损耗率(%)" width="150">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.loss_rate" :min="0" :max="100" :precision="2" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="排序" width="100">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.sequence" :min="0" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="位置" width="150">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.position" placeholder="位置" />
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
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- BOM详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="BOM详情"
            width="1200px"
        >
            <el-descriptions :column="2" border v-if="currentBom">
                <el-descriptions-item label="产品名称">{{ currentBom.product?.name }}</el-descriptions-item>
                <el-descriptions-item label="SKU">{{ currentBom.product?.sku }}</el-descriptions-item>
                <el-descriptions-item label="版本">{{ currentBom.version }}</el-descriptions-item>
                <el-descriptions-item label="生效日期">{{ currentBom.effective_date }}</el-descriptions-item>
                <el-descriptions-item label="失效日期">{{ currentBom.expiry_date || '-' }}</el-descriptions-item>
                <el-descriptions-item label="默认版本">
                    <el-tag v-if="currentBom.is_default" type="success">是</el-tag>
                    <span v-else>否</span>
                </el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="currentBom.is_active ? 'success' : 'danger'">
                        {{ currentBom.is_active ? '启用' : '禁用' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="总成本">
                    {{ currentBom.total_cost ? `¥${currentBom.total_cost}` : '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="创建人">{{ currentBom.creator?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="描述" :span="2">{{ currentBom.description || '-' }}</el-descriptions-item>
            </el-descriptions>
            <el-table :data="currentBom?.items || []" style="margin-top: 20px;" border>
                <el-table-column prop="sequence" label="序号" width="80" />
                <el-table-column prop="component_product.name" label="物料名称" />
                <el-table-column prop="component_product.sku" label="SKU" width="120" />
                <el-table-column prop="quantity" label="数量" width="120" />
                <el-table-column prop="unit.name" label="单位" width="100" />
                <el-table-column prop="loss_rate" label="损耗率(%)" width="120" />
                <el-table-column prop="position" label="位置" width="150" />
                <el-table-column prop="remark" label="备注" />
            </el-table>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '../../services/api';

const loading = ref(false);
const submitLoading = ref(false);
const dialogVisible = ref(false);
const detailVisible = ref(false);
const dialogTitle = ref('新增BOM');
const formRef = ref(null);
const boms = ref([]);
const products = ref([]);
const units = ref([]);
const currentBom = ref(null);

const searchForm = reactive({
    search: '',
    product_id: null,
    is_active: null,
    is_default: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    id: null,
    product_id: null,
    version: '',
    effective_date: new Date().toISOString().split('T')[0],
    expiry_date: null,
    is_default: false,
    is_active: true,
    description: '',
    items: []
});

const rules = {
    product_id: [{ required: true, message: '请选择产品', trigger: 'change' }],
    version: [{ required: true, message: '请输入版本', trigger: 'blur' }],
    effective_date: [{ required: true, message: '请选择生效日期', trigger: 'change' }],
    items: [
        { required: true, message: '请添加BOM明细', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条BOM明细', trigger: 'change' }
    ]
};

const loadBoms = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/boms', { params });
        boms.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载BOM列表失败');
    } finally {
        loading.value = false;
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

const loadUnits = async () => {
    try {
        const response = await api.get('/units', { params: { per_page: 1000 } });
        units.value = response.data.data;
    } catch (error) {
        console.error('加载单位列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadBoms();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.product_id = null;
    searchForm.is_active = null;
    searchForm.is_default = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增BOM';
    Object.assign(form, {
        id: null,
        product_id: null,
        version: '',
        effective_date: new Date().toISOString().split('T')[0],
        expiry_date: null,
        is_default: false,
        is_active: true,
        description: '',
        items: []
    });
    dialogVisible.value = true;
};

const handleView = async (row) => {
    try {
        const response = await api.get(`/boms/${row.id}`);
        currentBom.value = response.data.data;
        detailVisible.value = true;
    } catch (error) {
        ElMessage.error('加载BOM详情失败');
    }
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/boms/${row.id}`);
        const bom = response.data.data;
        dialogTitle.value = '编辑BOM';
        Object.assign(form, {
            id: bom.id,
            product_id: bom.product_id,
            version: bom.version,
            effective_date: bom.effective_date,
            expiry_date: bom.expiry_date,
            is_default: bom.is_default,
            is_active: bom.is_active,
            description: bom.description || '',
            items: bom.items.map(item => ({
                component_product_id: item.component_product_id,
                component_product: item.component_product,
                quantity: item.quantity,
                unit_id: item.unit_id,
                unit: item.unit,
                loss_rate: item.loss_rate || 0,
                sequence: item.sequence || 0,
                position: item.position || '',
                remark: item.remark || ''
            }))
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载BOM失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该BOM吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/boms/${row.id}`);
        ElMessage.success('删除成功');
        loadBoms();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handleSetDefault = async (row) => {
    try {
        await ElMessageBox.confirm('确定要设置该BOM为默认版本吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/boms/${row.id}/set-default`);
        ElMessage.success('设置成功');
        loadBoms();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('设置失败');
        }
    }
};

const handleCopy = async (row) => {
    try {
        await ElMessageBox.confirm('确定要复制该BOM吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/boms/${row.id}/copy`);
        ElMessage.success('复制成功');
        loadBoms();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('复制失败');
        }
    }
};

const handleProductChange = () => {
    // 产品改变时的处理
};

const handleAddItem = () => {
    form.items.push({
        component_product_id: null,
        component_product: null,
        quantity: 1,
        unit_id: null,
        unit: null,
        loss_rate: 0,
        sequence: form.items.length + 1,
        position: '',
        remark: ''
    });
};

const handleRemoveItem = (index) => {
    form.items.splice(index, 1);
    // 重新排序
    form.items.forEach((item, idx) => {
        item.sequence = idx + 1;
    });
};

const handleItemProductChange = (index) => {
    const productId = form.items[index].component_product_id;
    const product = products.value.find(p => p.id == productId);
    if (product) {
        form.items[index].component_product = product;
        if (!form.items[index].unit_id && product.unit_id) {
            form.items[index].unit_id = product.unit_id;
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
                ElMessage.warning('请至少添加一条BOM明细');
                return;
            }
            submitLoading.value = true;
            try {
                const data = {
                    ...form,
                    items: form.items.map(item => ({
                        component_product_id: item.component_product_id,
                        quantity: item.quantity,
                        unit_id: item.unit_id,
                        loss_rate: item.loss_rate || 0,
                        sequence: item.sequence || 0,
                        position: item.position || '',
                        remark: item.remark || ''
                    }))
                };
                if (form.id) {
                    await api.put(`/boms/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/boms', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadBoms();
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
    loadBoms();
};

const handlePageChange = () => {
    loadBoms();
};

onMounted(() => {
    loadBoms();
    loadProducts();
    loadUnits();
});
</script>

<style scoped>
.boms-page {
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

