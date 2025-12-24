<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">数据字典管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增字典
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="类型">
                    <el-select v-model="searchForm.type" filterable placeholder="全部" clearable style="width: 200px">
                        <el-option
                            v-for="type in types"
                            :key="type"
                            :label="type"
                            :value="type"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="编码">
                    <el-input v-model="searchForm.code" placeholder="编码" clearable />
                </el-form-item>
                <el-form-item label="标签">
                    <el-input v-model="searchForm.label" placeholder="标签" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.is_active" placeholder="全部" clearable style="width: 150px">
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="dictionaries" v-loading="loading" style="width: 100%" border>
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="type" label="类型" width="150" />
                <el-table-column prop="code" label="编码" width="150" />
                <el-table-column prop="label" label="标签" />
                <el-table-column prop="value" label="值" />
                <el-table-column prop="sort" label="排序" width="100" />
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'danger'">
                            {{ row.is_active ? '启用' : '禁用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="description" label="描述" show-overflow-tooltip />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
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

        <!-- 字典表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
            width="700px"
            @close="handleDialogClose"
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="100px"
            >
                <el-form-item label="类型" prop="type">
                    <el-input v-model="form.type" placeholder="例如：order_status, payment_method" />
                </el-form-item>
                <el-form-item label="编码" prop="code">
                    <el-input v-model="form.code" placeholder="唯一编码" />
                </el-form-item>
                <el-form-item label="标签" prop="label">
                    <el-input v-model="form.label" placeholder="显示名称" />
                </el-form-item>
                <el-form-item label="值" prop="value">
                    <el-input v-model="form.value" placeholder="字典值" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="form.sort" :min="0" style="width: 100%" />
                </el-form-item>
                <el-form-item label="状态" prop="is_active">
                    <el-switch v-model="form.is_active" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="3" placeholder="描述信息" />
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
const dictionaries = ref([]);
const types = ref([]);
const dialogVisible = ref(false);
const dialogTitle = ref('新增字典');
const submitLoading = ref(false);

const searchForm = reactive({
    type: null,
    code: '',
    label: '',
    is_active: null
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const form = reactive({
    id: null,
    type: '',
    code: '',
    label: '',
    value: '',
    sort: 0,
    is_active: true,
    description: ''
});

const rules = {
    type: [{ required: true, message: '请输入类型', trigger: 'blur' }],
    code: [{ required: true, message: '请输入编码', trigger: 'blur' }],
    label: [{ required: true, message: '请输入标签', trigger: 'blur' }],
    value: [{ required: true, message: '请输入值', trigger: 'blur' }]
};

const loadTypes = async () => {
    try {
        const response = await api.get('/data-dictionaries/types/list');
        types.value = response.data.data || [];
    } catch (error) {
        console.error('加载类型列表失败:', error);
    }
};

const loadDictionaries = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page
        };
        if (searchForm.type) {
            params.type = searchForm.type;
        }
        if (searchForm.code) {
            params.code = searchForm.code;
        }
        if (searchForm.label) {
            params.label = searchForm.label;
        }
        if (searchForm.is_active !== null) {
            params.is_active = searchForm.is_active;
        }
        const response = await api.get('/data-dictionaries', { params });
        dictionaries.value = response.data.data || [];
        pagination.total = response.data.total || 0;
    } catch (error) {
        ElMessage.error('加载字典列表失败');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadDictionaries();
};

const handleReset = () => {
    searchForm.type = null;
    searchForm.code = '';
    searchForm.label = '';
    searchForm.is_active = null;
    handleSearch();
};

const handleSizeChange = () => {
    pagination.page = 1;
    loadDictionaries();
};

const handlePageChange = () => {
    loadDictionaries();
};

const handleAdd = () => {
    dialogTitle.value = '新增字典';
    Object.assign(form, {
        id: null,
        type: '',
        code: '',
        label: '',
        value: '',
        sort: 0,
        is_active: true,
        description: ''
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/data-dictionaries/${row.id}`);
        const dictionary = response.data.data;
        dialogTitle.value = '编辑字典';
        Object.assign(form, {
            id: dictionary.id,
            type: dictionary.type,
            code: dictionary.code,
            label: dictionary.label,
            value: dictionary.value,
            sort: dictionary.sort,
            is_active: dictionary.is_active,
            description: dictionary.description || ''
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载字典信息失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该字典吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/data-dictionaries/${row.id}`);
        ElMessage.success('删除成功');
        loadDictionaries();
        loadTypes();
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
                    await api.put(`/data-dictionaries/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/data-dictionaries', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadDictionaries();
                loadTypes();
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
    loadTypes();
    loadDictionaries();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

