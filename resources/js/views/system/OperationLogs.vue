<template>
    <div class="operation-logs-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>操作日志</span>
                </div>
            </template>

            <el-form :inline="true" :model="searchForm" class="search-form">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="操作动作/路径/用户" clearable />
                </el-form-item>
                <el-form-item label="用户">
                    <el-select v-model="searchForm.user_id" filterable placeholder="全部" clearable style="width: 200px">
                        <el-option
                            v-for="user in users"
                            :key="user.id"
                            :label="`${user.name} (${user.email})`"
                            :value="user.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="模块">
                    <el-input v-model="searchForm.module" placeholder="模块名称" clearable />
                </el-form-item>
                <el-form-item label="请求方法">
                    <el-select v-model="searchForm.method" placeholder="全部" clearable>
                        <el-option label="GET" value="GET" />
                        <el-option label="POST" value="POST" />
                        <el-option label="PUT" value="PUT" />
                        <el-option label="DELETE" value="DELETE" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态码">
                    <el-select v-model="searchForm.status_code" placeholder="全部" clearable>
                        <el-option label="200" :value="200" />
                        <el-option label="400" :value="400" />
                        <el-option label="401" :value="401" />
                        <el-option label="403" :value="403" />
                        <el-option label="404" :value="404" />
                        <el-option label="500" :value="500" />
                    </el-select>
                </el-form-item>
                <el-form-item label="日期范围">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        @change="handleDateRangeChange"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="logs" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="user.name" label="用户" width="150" />
                <el-table-column prop="module" label="模块" width="120" />
                <el-table-column prop="action" label="操作动作" min-width="150" />
                <el-table-column prop="method" label="方法" width="80">
                    <template #default="{ row }">
                        <el-tag :type="getMethodTagType(row.method)" size="small">{{ row.method }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="path" label="路径" min-width="200" show-overflow-tooltip />
                <el-table-column prop="status_code" label="状态码" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.status_code)" size="small">{{ row.status_code }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="ip" label="IP地址" width="130" />
                <el-table-column prop="created_at" label="操作时间" width="180" />
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
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

        <!-- 日志详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="日志详情"
            width="900px"
        >
            <el-descriptions :column="2" border v-if="currentLog">
                <el-descriptions-item label="日志ID">{{ currentLog.id }}</el-descriptions-item>
                <el-descriptions-item label="用户">{{ currentLog.user?.name || '-' }} ({{ currentLog.user?.email || '-' }})</el-descriptions-item>
                <el-descriptions-item label="模块">{{ currentLog.module || '-' }}</el-descriptions-item>
                <el-descriptions-item label="操作动作">{{ currentLog.action }}</el-descriptions-item>
                <el-descriptions-item label="请求方法">
                    <el-tag :type="getMethodTagType(currentLog.method)" size="small">{{ currentLog.method }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="请求路径">{{ currentLog.path || '-' }}</el-descriptions-item>
                <el-descriptions-item label="状态码">
                    <el-tag :type="getStatusTagType(currentLog.status_code)" size="small">{{ currentLog.status_code }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="IP地址">{{ currentLog.ip || '-' }}</el-descriptions-item>
                <el-descriptions-item label="用户代理">{{ currentLog.user_agent || '-' }}</el-descriptions-item>
                <el-descriptions-item label="操作时间">{{ currentLog.created_at }}</el-descriptions-item>
                <el-descriptions-item label="操作说明" :span="2">{{ currentLog.message || '-' }}</el-descriptions-item>
                <el-descriptions-item label="请求数据" :span="2">
                    <pre style="max-height: 200px; overflow: auto; background: #f5f5f5; padding: 10px; border-radius: 4px;">{{ formatJson(currentLog.request_data) }}</pre>
                </el-descriptions-item>
                <el-descriptions-item label="响应数据" :span="2">
                    <pre style="max-height: 200px; overflow: auto; background: #f5f5f5; padding: 10px; border-radius: 4px;">{{ formatJson(currentLog.response_data) }}</pre>
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import api from '../../services/api';

const loading = ref(false);
const detailVisible = ref(false);
const logs = ref([]);
const users = ref([]);
const currentLog = ref(null);
const dateRange = ref(null);

const searchForm = reactive({
    search: '',
    user_id: null,
    module: '',
    method: null,
    status_code: null,
    date_from: null,
    date_to: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const getMethodTagType = (method) => {
    const methodMap = {
        'GET': 'success',
        'POST': 'primary',
        'PUT': 'warning',
        'DELETE': 'danger'
    };
    return methodMap[method] || '';
};

const getStatusTagType = (statusCode) => {
    if (!statusCode) return '';
    if (statusCode >= 200 && statusCode < 300) return 'success';
    if (statusCode >= 400 && statusCode < 500) return 'warning';
    if (statusCode >= 500) return 'danger';
    return '';
};

const formatJson = (data) => {
    if (!data) return '-';
    try {
        const parsed = typeof data == 'string' ? JSON.parse(data) : data;
        return JSON.stringify(parsed, null, 2);
    } catch (e) {
        return data;
    }
};

const handleDateRangeChange = (dates) => {
    if (dates && dates.length == 2) {
        searchForm.date_from = dates[0];
        searchForm.date_to = dates[1];
    } else {
        searchForm.date_from = null;
        searchForm.date_to = null;
    }
};

const loadLogs = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/operation-logs', { params });
        logs.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载日志列表失败');
    } finally {
        loading.value = false;
    }
};

const loadUsers = async () => {
    try {
        const response = await api.get('/users', { params: { per_page: 1000 } });
        users.value = response.data.data;
    } catch (error) {
        console.error('加载用户列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadLogs();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.user_id = null;
    searchForm.module = '';
    searchForm.method = null;
    searchForm.status_code = null;
    searchForm.date_from = null;
    searchForm.date_to = null;
    dateRange.value = null;
    handleSearch();
};

const handleView = async (row) => {
    try {
        const response = await api.get(`/operation-logs/${row.id}`);
        currentLog.value = response.data.data;
        detailVisible.value = true;
    } catch (error) {
        ElMessage.error('加载日志详情失败');
    }
};

const handleSizeChange = () => {
    loadLogs();
};

const handlePageChange = () => {
    loadLogs();
};

onMounted(() => {
    loadLogs();
    loadUsers();
});
</script>

<style scoped>
.operation-logs-page {
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

