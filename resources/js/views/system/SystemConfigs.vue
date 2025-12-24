<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">系统配置管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增配置
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="配置键/描述" clearable />
                </el-form-item>
                <el-form-item label="分组">
                    <el-input v-model="searchForm.group" placeholder="配置分组" clearable />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="configs" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="key" label="配置键" min-width="200" />
                <el-table-column prop="value" label="配置值" min-width="200" show-overflow-tooltip />
                <el-table-column prop="type" label="类型" width="100">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.type || 'string' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="group" label="分组" width="150" />
                <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
                </el-table>
            </div>

            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.per_page"
                :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next, jumper"
                @size-change="handleSizeChange"
                @current-change="handlePageChange"
                class="modern-pagination"
            />
        </div>

        <!-- 配置表单对话框 -->
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
                label-width="120px"
            >
                <el-form-item label="配置键" prop="key">
                    <el-input v-model="form.key" :disabled="!!form.id" placeholder="如：app.name" />
                </el-form-item>
                <el-form-item label="配置值" prop="value">
                    <el-input v-model="form.value" type="textarea" :rows="3" placeholder="配置值" />
                </el-form-item>
                <el-form-item label="类型" prop="type">
                    <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%">
                        <el-option label="字符串" value="string" />
                        <el-option label="数字" value="number" />
                        <el-option label="布尔值" value="boolean" />
                        <el-option label="JSON" value="json" />
                    </el-select>
                </el-form-item>
                <el-form-item label="分组" prop="group">
                    <el-input v-model="form.group" placeholder="配置分组（可选）" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="2" placeholder="配置描述（可选）" />
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

const loading = ref(false);
const submitLoading = ref(false);
const dialogVisible = ref(false);
const dialogTitle = ref('新增配置');
const formRef = ref(null);
const configs = ref([]);

const searchForm = reactive({
    search: '',
    group: ''
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const form = reactive({
    id: null,
    key: '',
    value: '',
    type: 'string',
    group: '',
    description: ''
});

const rules = {
    key: [{ required: true, message: '请输入配置键', trigger: 'blur' }],
    type: [{ required: true, message: '请选择类型', trigger: 'change' }]
};

const loadConfigs = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page
        };
        // 只添加非空值参数
        if (searchForm.search) {
            params.search = searchForm.search;
        }
        if (searchForm.group) {
            params.group = searchForm.group;
        }
        const response = await api.get('/system-configs', { params });
        configs.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载配置列表失败');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadConfigs();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.group = '';
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增配置';
    Object.assign(form, {
        id: null,
        key: '',
        value: '',
        type: 'string',
        group: '',
        description: ''
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/system-configs/${row.id}`);
        const config = response.data.data;
        dialogTitle.value = '编辑配置';
        Object.assign(form, {
            id: config.id,
            key: config.key,
            value: config.value || '',
            type: config.type || 'string',
            group: config.group || '',
            description: config.description || ''
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载配置失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该配置吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/system-configs/${row.id}`);
        ElMessage.success('删除成功');
        loadConfigs();
    } catch (error) {
        if (error != 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            submitLoading.value = true;
            try {
                const data = {
                    key: form.key,
                    value: form.value || null,
                    type: form.type,
                    group: form.group || null,
                    description: form.description || null
                };
                if (form.id) {
                    await api.put(`/system-configs/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/system-configs', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadConfigs();
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
    pagination.page = 1;
    loadConfigs();
};

const handlePageChange = () => {
    loadConfigs();
};

onMounted(() => {
    loadConfigs();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

