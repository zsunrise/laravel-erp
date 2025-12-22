<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">币种管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增币种
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item>
                    <el-input v-model="searchForm.search" placeholder="币种名称/代码" clearable />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="currencies" v-loading="loading" style="width: 100%" border>
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="name" label="币种名称" />
                <el-table-column prop="code" label="币种代码" width="120" />
                <el-table-column prop="symbol" label="币种符号" width="120" />
                <el-table-column prop="exchange_rate" label="汇率" width="120">
                    <template #default="{ row }">
                        {{ row.exchange_rate }}
                    </template>
                </el-table-column>
                <el-table-column prop="is_default" label="默认" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_default" type="success">是</el-tag>
                        <span v-else>否</span>
                    </template>
                </el-table-column>
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'danger'">
                            {{ row.is_active ? '启用' : '禁用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)" :disabled="row.is_default">删除</el-button>
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

        <!-- 币种表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
            width="600px"
            @close="handleDialogClose"
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="100px"
            >
                <el-form-item label="币种名称" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="币种代码" prop="code">
                    <el-input v-model="form.code" maxlength="3" />
                </el-form-item>
                <el-form-item label="币种符号" prop="symbol">
                    <el-input v-model="form.symbol" />
                </el-form-item>
                <el-form-item label="汇率" prop="exchange_rate">
                    <el-input-number v-model="form.exchange_rate" :min="0" :precision="4" style="width: 100%" />
                </el-form-item>
                <el-form-item label="设为默认" prop="is_default">
                    <el-switch v-model="form.is_default" />
                </el-form-item>
                <el-form-item label="状态" prop="is_active">
                    <el-switch v-model="form.is_active" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const formRef = ref(null);
const loading = ref(false);
const currencies = ref([]);
const dialogVisible = ref(false);
const dialogTitle = ref('新增币种');
const submitLoading = ref(false);

const searchForm = reactive({
    search: ''
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    id: null,
    name: '',
    code: '',
    symbol: '',
    exchange_rate: 1,
    is_default: false,
    is_active: true
});

const rules = {
    name: [{ required: true, message: '请输入币种名称', trigger: 'blur' }],
    code: [{ required: true, message: '请输入币种代码', trigger: 'blur' }]
};

const loadCurrencies = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page
        };
        const response = await api.get('/currencies', { params });
        currencies.value = response.data.data || [];
        pagination.total = response.data.total || 0;
    } catch (error) {
        ElMessage.error('加载币种列表失败');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadCurrencies();
};

const handleReset = () => {
    searchForm.search = '';
    handleSearch();
};

const handleSizeChange = () => {
    pagination.page = 1;
    loadCurrencies();
};

const handlePageChange = () => {
    loadCurrencies();
};

const handleAdd = () => {
    dialogTitle.value = '新增币种';
    Object.assign(form, {
        id: null,
        name: '',
        code: '',
        symbol: '',
        exchange_rate: 1,
        is_default: false,
        is_active: true
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/currencies/${row.id}`);
        const currency = response.data.data;
        dialogTitle.value = '编辑币种';
        Object.assign(form, {
            id: currency.id,
            name: currency.name,
            code: currency.code,
            symbol: currency.symbol || '',
            exchange_rate: currency.exchange_rate,
            is_default: currency.is_default,
            is_active: currency.is_active
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载币种信息失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该币种吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/currencies/${row.id}`);
        ElMessage.success('删除成功');
        loadCurrencies();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            submitLoading.value = true;
            try {
                if (form.id) {
                    await api.put(`/currencies/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/currencies', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadCurrencies();
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

onMounted(() => {
    loadCurrencies();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

