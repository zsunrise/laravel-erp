<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">系统设置</h2>
            </div>

            <el-tabs v-model="activeTab" type="border-card">
                <el-tab-pane label="系统配置" name="config">
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
                        <el-table-column label="操作" width="150" fixed="right" v-if="hasEditPermission">
                            <template #default="{ row }">
                                <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
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
                </el-tab-pane>

                <el-tab-pane label="系统信息" name="info">
                    <div style="padding: 24px;">
                        <el-descriptions :column="2" border>
                            <el-descriptions-item label="系统名称">ERP管理系统</el-descriptions-item>
                            <el-descriptions-item label="系统版本">1.0.0</el-descriptions-item>
                            <el-descriptions-item label="PHP版本">{{ systemInfo.php_version || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="Laravel版本">{{ systemInfo.laravel_version || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="数据库">{{ systemInfo.database || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="服务器时间">{{ currentTime }}</el-descriptions-item>
                        </el-descriptions>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>

        <!-- 配置编辑对话框 -->
        <el-dialog
            v-model="dialogVisible"
            title="编辑配置"
            width="600px"
            @close="handleDialogClose"
            v-if="hasEditPermission"
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="120px"
            >
                <el-form-item label="配置键">
                    <el-input v-model="form.key" disabled />
                </el-form-item>
                <el-form-item label="配置值" prop="value">
                    <el-input v-model="form.value" type="textarea" :rows="3" placeholder="配置值" />
                </el-form-item>
                <el-form-item label="类型">
                    <el-input v-model="form.type" disabled />
                </el-form-item>
                <el-form-item label="分组">
                    <el-input v-model="form.group" disabled />
                </el-form-item>
                <el-form-item label="描述">
                    <el-input v-model="form.description" type="textarea" :rows="2" disabled />
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
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage } from 'element-plus';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const loading = ref(false);
const submitLoading = ref(false);
const dialogVisible = ref(false);
const formRef = ref(null);
const activeTab = ref('config');
const configs = ref([]);

const searchForm = reactive({
    search: '',
    group: ''
});

const pagination = reactive({
    page: 1,
    per_page: 15,
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

const systemInfo = reactive({
    php_version: '',
    laravel_version: '',
    database: ''
});

const currentTime = computed(() => {
    return new Date().toLocaleString('zh-CN');
});

const hasEditPermission = computed(() => {
    return authStore.hasPermission('system-configs.manage');
});

const rules = {
    value: [{ required: true, message: '请输入配置值', trigger: 'blur' }]
};

const loadConfigs = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
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

const handleSizeChange = () => {
    loadConfigs();
};

const handlePageChange = () => {
    loadConfigs();
};

const handleEdit = async (row) => {
    if (!hasEditPermission.value) {
        ElMessage.warning('您没有编辑权限');
        return;
    }
    Object.assign(form, {
        id: row.id,
        key: row.key,
        value: row.value,
        type: row.type,
        group: row.group,
        description: row.description
    });
    dialogVisible.value = true;
};

const handleSubmit = async () => {
    try {
        await formRef.value.validate();
        submitLoading.value = true;
        await api.put(`/system-configs/${form.id}`, {
            value: form.value
        });
        ElMessage.success('保存成功');
        dialogVisible.value = false;
        loadConfigs();
    } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
            ElMessage.error(error.response.data.message);
        } else {
            ElMessage.error('保存失败');
        }
    } finally {
        submitLoading.value = false;
    }
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
    Object.assign(form, {
        id: null,
        key: '',
        value: '',
        type: 'string',
        group: '',
        description: ''
    });
};

onMounted(() => {
    loadConfigs();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>
