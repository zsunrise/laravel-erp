<template>
    <div class="process-routes-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>工艺路线管理</span>
                    <el-button type="primary" @click="handleAdd">新增工艺路线</el-button>
                </div>
            </template>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
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

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="processRoutes" v-loading="loading" style="width: 100%">
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
                <el-table-column prop="total_time" label="总工时" width="120">
                    <template #default="{ row }">
                        {{ row.total_time ? `${row.total_time}小时` : '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="300" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)" :loading="viewLoadingId === row.id" :disabled="viewLoadingId !== null">查看</el-button>
                        <el-button type="warning" size="small" @click="handleEdit(row)">编辑</el-button>
                        <el-button type="info" size="small" @click="handleSetDefault(row)" v-if="!row.is_default">设默认</el-button>
                        <el-button type="success" size="small" @click="handleCopy(row)">复制</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
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

        <!-- 工艺路线表单对话框 -->
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
                <el-form-item label="工艺步骤" prop="steps">
                    <el-button type="primary" size="small" @click="handleAddStep">添加步骤</el-button>
                    <el-table :data="form.steps" style="margin-top: 10px;" border>
                        <el-table-column label="序号" width="100">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.sequence" :min="1" @change="handleStepChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="步骤名称" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.step_name" placeholder="步骤名称" />
                            </template>
                        </el-table-column>
                        <el-table-column label="步骤编码" width="150">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.step_code" placeholder="步骤编码" />
                            </template>
                        </el-table-column>
                        <el-table-column label="工作中心" width="150">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.work_center" placeholder="工作中心" />
                            </template>
                        </el-table-column>
                        <el-table-column label="标准工时" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.standard_time" :min="0" :precision="2" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="准备时间" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.setup_time" :min="0" :precision="2" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="排队时间" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.queue_time" :min="0" :precision="2" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="移动时间" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.move_time" :min="0" :precision="2" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="描述" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.description" placeholder="描述" />
                            </template>
                        </el-table-column>
                        <el-table-column label="备注" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.remark" placeholder="备注" />
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ $index }">
                                <el-button type="danger" size="small" @click="handleRemoveStep($index)">删除</el-button>
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

        <!-- 工艺路线详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="工艺路线详情"
            width="1200px"
            :close-on-click-modal="false"
        >
            <div v-loading="detailLoading">
                <el-descriptions :column="2" border v-if="currentProcessRoute">
                <el-descriptions-item label="产品名称">{{ currentProcessRoute.product?.name }}</el-descriptions-item>
                <el-descriptions-item label="SKU">{{ currentProcessRoute.product?.sku }}</el-descriptions-item>
                <el-descriptions-item label="版本">{{ currentProcessRoute.version }}</el-descriptions-item>
                <el-descriptions-item label="生效日期">{{ currentProcessRoute.effective_date }}</el-descriptions-item>
                <el-descriptions-item label="失效日期">{{ currentProcessRoute.expiry_date || '-' }}</el-descriptions-item>
                <el-descriptions-item label="默认版本">
                    <el-tag v-if="currentProcessRoute.is_default" type="success">是</el-tag>
                    <span v-else>否</span>
                </el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="currentProcessRoute.is_active ? 'success' : 'danger'">
                        {{ currentProcessRoute.is_active ? '启用' : '禁用' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="总工时">
                    {{ currentProcessRoute.total_time ? `${currentProcessRoute.total_time}小时` : '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="创建人">{{ currentProcessRoute.creator?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="描述" :span="2">{{ currentProcessRoute.description || '-' }}</el-descriptions-item>
            </el-descriptions>
                <el-table :data="currentProcessRoute?.steps || []" style="margin-top: 20px;" border v-if="currentProcessRoute">
                    <el-table-column prop="sequence" label="序号" width="80" />
                    <el-table-column prop="step_name" label="步骤名称" />
                    <el-table-column prop="step_code" label="步骤编码" width="120" />
                    <el-table-column prop="work_center" label="工作中心" width="150" />
                    <el-table-column prop="standard_time" label="标准工时" width="120" />
                    <el-table-column prop="setup_time" label="准备时间" width="120" />
                    <el-table-column prop="queue_time" label="排队时间" width="120" />
                    <el-table-column prop="move_time" label="移动时间" width="120" />
                    <el-table-column prop="description" label="描述" />
                    <el-table-column prop="remark" label="备注" />
                </el-table>
            </div>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const submitLoading = ref(false);
const dialogVisible = ref(false);
const detailVisible = ref(false);
const detailLoading = ref(false);
const viewLoadingId = ref(null);
const viewLoadingId = ref(null);
const dialogTitle = ref('新增工艺路线');
const formRef = ref(null);
const processRoutes = ref([]);
const products = ref([]);
const currentProcessRoute = ref(null);

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
    steps: []
});

const rules = {
    product_id: [{ required: true, message: '请选择产品', trigger: 'change' }],
    version: [{ required: true, message: '请输入版本', trigger: 'blur' }],
    effective_date: [{ required: true, message: '请选择生效日期', trigger: 'change' }],
    steps: [
        { required: true, message: '请添加工艺步骤', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条工艺步骤', trigger: 'change' }
    ]
};

const loadProcessRoutes = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/process-routes', { params });
        processRoutes.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载工艺路线列表失败');
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

const handleSearch = () => {
    pagination.page = 1;
    loadProcessRoutes();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.product_id = null;
    searchForm.is_active = null;
    searchForm.is_default = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增工艺路线';
    Object.assign(form, {
        id: null,
        product_id: null,
        version: '',
        effective_date: new Date().toISOString().split('T')[0],
        expiry_date: null,
        is_default: false,
        is_active: true,
        description: '',
        steps: []
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
    currentProcessRoute.value = null;
    
    try {
        const response = await api.get(`/process-routes/${row.id}`);
        currentProcessRoute.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载工艺路线详情失败');
        detailVisible.value = false;
    } finally {
        detailLoading.value = false;
        viewLoadingId.value = null;
    }
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/process-routes/${row.id}`);
        const route = response.data.data;
        dialogTitle.value = '编辑工艺路线';
        Object.assign(form, {
            id: route.id,
            product_id: route.product_id,
            version: route.version,
            effective_date: route.effective_date,
            expiry_date: route.expiry_date,
            is_default: route.is_default,
            is_active: route.is_active,
            description: route.description || '',
            steps: route.steps.map(step => ({
                step_name: step.step_name,
                step_code: step.step_code || '',
                sequence: step.sequence,
                work_center: step.work_center || '',
                standard_time: step.standard_time || 0,
                setup_time: step.setup_time || 0,
                queue_time: step.queue_time || 0,
                move_time: step.move_time || 0,
                description: step.description || '',
                remark: step.remark || ''
            }))
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载工艺路线失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该工艺路线吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/process-routes/${row.id}`);
        ElMessage.success('删除成功');
        loadProcessRoutes();
    } catch (error) {
        if (error != 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handleSetDefault = async (row) => {
    try {
        await ElMessageBox.confirm('确定要设置该工艺路线为默认版本吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/process-routes/${row.id}/set-default`);
        ElMessage.success('设置成功');
        loadProcessRoutes();
    } catch (error) {
        if (error != 'cancel') {
            ElMessage.error('设置失败');
        }
    }
};

const handleCopy = async (row) => {
    try {
        await ElMessageBox.confirm('确定要复制该工艺路线吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/process-routes/${row.id}/copy`);
        ElMessage.success('复制成功');
        loadProcessRoutes();
    } catch (error) {
        if (error != 'cancel') {
            ElMessage.error('复制失败');
        }
    }
};

const handleProductChange = () => {
    // 产品改变时的处理
};

const handleAddStep = () => {
    form.steps.push({
        step_name: '',
        step_code: '',
        sequence: form.steps.length + 1,
        work_center: '',
        standard_time: 0,
        setup_time: 0,
        queue_time: 0,
        move_time: 0,
        description: '',
        remark: ''
    });
};

const handleRemoveStep = (index) => {
    form.steps.splice(index, 1);
    // 重新排序
    form.steps.forEach((step, idx) => {
        step.sequence = idx + 1;
    });
};

const handleStepChange = () => {
    // 步骤改变时的处理
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            if (form.steps.length == 0) {
                ElMessage.warning('请至少添加一条工艺步骤');
                return;
            }
            submitLoading.value = true;
            try {
                const data = {
                    ...form,
                    steps: form.steps.map(step => ({
                        step_name: step.step_name,
                        step_code: step.step_code || null,
                        sequence: step.sequence,
                        work_center: step.work_center || null,
                        standard_time: step.standard_time || 0,
                        setup_time: step.setup_time || 0,
                        queue_time: step.queue_time || 0,
                        move_time: step.move_time || 0,
                        description: step.description || null,
                        remark: step.remark || null
                    }))
                };
                if (form.id) {
                    await api.put(`/process-routes/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/process-routes', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadProcessRoutes();
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
    loadProcessRoutes();
};

const handlePageChange = () => {
    loadProcessRoutes();
};

onMounted(() => {
    loadProcessRoutes();
    loadProducts();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

