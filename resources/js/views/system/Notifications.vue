<template>
    <div class="notifications-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>消息通知</span>
                    <div>
                        <el-button type="success" @click="handleMarkAllRead" :loading="markAllLoading">全部已读</el-button>
                        <el-button type="primary" @click="handleSend">发送消息</el-button>
                    </div>
                </div>
            </template>

            <el-form :inline="true" :model="searchForm" class="search-form">
                <el-form-item label="状态">
                    <el-select v-model="searchForm.status" placeholder="全部" clearable>
                        <el-option label="未读" value="unread" />
                        <el-option label="已读" value="read" />
                        <el-option label="已删除" value="deleted" />
                    </el-select>
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="searchForm.type" placeholder="全部" clearable>
                        <el-option label="系统消息" value="system" />
                        <el-option label="审批消息" value="approval" />
                        <el-option label="订单消息" value="order" />
                        <el-option label="库存消息" value="inventory" />
                        <el-option label="财务消息" value="financial" />
                    </el-select>
                </el-form-item>
                <el-form-item label="优先级">
                    <el-select v-model="searchForm.priority" placeholder="全部" clearable>
                        <el-option label="低" value="low" />
                        <el-option label="普通" value="normal" />
                        <el-option label="高" value="high" />
                        <el-option label="紧急" value="urgent" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="notifications" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="type" label="类型" width="120">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.type)">{{ getTypeText(row.type) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="title" label="标题" min-width="200" />
                <el-table-column prop="priority" label="优先级" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getPriorityTagType(row.priority)" size="small">{{ getPriorityText(row.priority) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.status)">{{ getStatusText(row.status) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="channel" label="渠道" width="100">
                    <template #default="{ row }">
                        <el-tag size="small">{{ getChannelText(row.channel) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="reference_no" label="关联单号" width="150" />
                <el-table-column prop="created_at" label="创建时间" width="180" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
                        <el-button type="success" size="small" @click="handleMarkRead(row)" v-if="row.status == 'unread'">标记已读</el-button>
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

        <!-- 消息详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="消息详情"
            width="800px"
        >
            <el-descriptions :column="2" border v-if="currentNotification">
                <el-descriptions-item label="消息ID">{{ currentNotification.id }}</el-descriptions-item>
                <el-descriptions-item label="类型">
                    <el-tag :type="getTypeTagType(currentNotification.type)">{{ getTypeText(currentNotification.type) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="标题" :span="2">{{ currentNotification.title }}</el-descriptions-item>
                <el-descriptions-item label="优先级">
                    <el-tag :type="getPriorityTagType(currentNotification.priority)">{{ getPriorityText(currentNotification.priority) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getStatusTagType(currentNotification.status)">{{ getStatusText(currentNotification.status) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="渠道">
                    <el-tag>{{ getChannelText(currentNotification.channel) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="关联单号">{{ currentNotification.reference_no || '-' }}</el-descriptions-item>
                <el-descriptions-item label="创建时间">{{ currentNotification.created_at }}</el-descriptions-item>
                <el-descriptions-item label="阅读时间" v-if="currentNotification.read_at">{{ currentNotification.read_at }}</el-descriptions-item>
                <el-descriptions-item label="内容" :span="2">
                    <div style="white-space: pre-wrap;">{{ currentNotification.content }}</div>
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>

        <!-- 发送消息对话框 -->
        <el-dialog
            v-model="sendDialogVisible"
            title="发送消息"
            width="600px"
            @close="handleSendDialogClose"
        >
            <el-form
                ref="sendFormRef"
                :model="sendForm"
                :rules="sendRules"
                label-width="100px"
            >
                <el-form-item label="接收用户" prop="user_id">
                    <el-select v-model="sendForm.user_id" filterable placeholder="请选择接收用户" style="width: 100%">
                        <el-option
                            v-for="user in users"
                            :key="user.id"
                            :label="`${user.name} (${user.email})`"
                            :value="user.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="消息类型" prop="type">
                    <el-select v-model="sendForm.type" placeholder="请选择消息类型" style="width: 100%">
                        <el-option label="系统消息" value="system" />
                        <el-option label="审批消息" value="approval" />
                        <el-option label="订单消息" value="order" />
                        <el-option label="库存消息" value="inventory" />
                        <el-option label="财务消息" value="financial" />
                    </el-select>
                </el-form-item>
                <el-form-item label="标题" prop="title">
                    <el-input v-model="sendForm.title" placeholder="请输入消息标题" />
                </el-form-item>
                <el-form-item label="内容" prop="content">
                    <el-input v-model="sendForm.content" type="textarea" :rows="5" placeholder="请输入消息内容" />
                </el-form-item>
                <el-form-item label="渠道">
                    <el-select v-model="sendForm.channel" placeholder="请选择发送渠道" style="width: 100%">
                        <el-option label="系统消息" value="system" />
                        <el-option label="邮件" value="email" />
                        <el-option label="短信" value="sms" />
                        <el-option label="推送" value="push" />
                    </el-select>
                </el-form-item>
                <el-form-item label="优先级">
                    <el-select v-model="sendForm.priority" placeholder="请选择优先级" style="width: 100%">
                        <el-option label="低" value="low" />
                        <el-option label="普通" value="normal" />
                        <el-option label="高" value="high" />
                        <el-option label="紧急" value="urgent" />
                    </el-select>
                </el-form-item>
                <el-form-item label="关联单号">
                    <el-input v-model="sendForm.reference_no" placeholder="请输入关联单号（可选）" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="sendDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSendSubmit" :loading="sendLoading">发送</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '../../services/api';

const loading = ref(false);
const markAllLoading = ref(false);
const sendLoading = ref(false);
const detailVisible = ref(false);
const sendDialogVisible = ref(false);
const sendFormRef = ref(null);
const notifications = ref([]);
const users = ref([]);
const currentNotification = ref(null);

const searchForm = reactive({
    status: null,
    type: null,
    priority: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const sendForm = reactive({
    user_id: null,
    type: 'system',
    title: '',
    content: '',
    channel: 'system',
    priority: 'normal',
    reference_no: ''
});

const sendRules = {
    user_id: [{ required: true, message: '请选择接收用户', trigger: 'change' }],
    type: [{ required: true, message: '请选择消息类型', trigger: 'change' }],
    title: [{ required: true, message: '请输入消息标题', trigger: 'blur' }],
    content: [{ required: true, message: '请输入消息内容', trigger: 'blur' }]
};

const getTypeTagType = (type) => {
    const typeMap = {
        'system': 'info',
        'approval': 'warning',
        'order': 'primary',
        'inventory': 'success',
        'financial': 'danger'
    };
    return typeMap[type] || 'info';
};

const getTypeText = (type) => {
    const typeMap = {
        'system': '系统消息',
        'approval': '审批消息',
        'order': '订单消息',
        'inventory': '库存消息',
        'financial': '财务消息'
    };
    return typeMap[type] || type;
};

const getPriorityTagType = (priority) => {
    const priorityMap = {
        'low': 'info',
        'normal': '',
        'high': 'warning',
        'urgent': 'danger'
    };
    return priorityMap[priority] || '';
};

const getPriorityText = (priority) => {
    const priorityMap = {
        'low': '低',
        'normal': '普通',
        'high': '高',
        'urgent': '紧急'
    };
    return priorityMap[priority] || priority;
};

const getStatusTagType = (status) => {
    const statusMap = {
        'unread': 'warning',
        'read': 'success',
        'deleted': 'info'
    };
    return statusMap[status] || 'info';
};

const getStatusText = (status) => {
    const statusMap = {
        'unread': '未读',
        'read': '已读',
        'deleted': '已删除'
    };
    return statusMap[status] || status;
};

const getChannelText = (channel) => {
    const channelMap = {
        'system': '系统',
        'email': '邮件',
        'sms': '短信',
        'push': '推送'
    };
    return channelMap[channel] || channel;
};

const loadNotifications = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/notifications', { params });
        notifications.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载消息列表失败');
    } finally {
        loading.value = false;
    }
};

const loadUsers = async () => {
    try {
        const response = await api.get('/users', { params: { per_page: 1000, is_active: 1 } });
        users.value = response.data.data;
    } catch (error) {
        console.error('加载用户列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadNotifications();
};

const handleReset = () => {
    searchForm.status = null;
    searchForm.type = null;
    searchForm.priority = null;
    handleSearch();
};

const handleView = async (row) => {
    try {
        const response = await api.get(`/notifications/${row.id}`);
        currentNotification.value = response.data;
        detailVisible.value = true;
        if (row.status == 'unread') {
            loadNotifications();
        }
    } catch (error) {
        ElMessage.error('加载消息详情失败');
    }
};

const handleMarkRead = async (row) => {
    try {
        await api.post(`/notifications/${row.id}/read`);
        ElMessage.success('标记已读成功');
        loadNotifications();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || '操作失败');
    }
};

const handleMarkAllRead = async () => {
    try {
        markAllLoading.value = true;
        await api.post('/notifications/read-all');
        ElMessage.success('全部标记已读成功');
        loadNotifications();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || '操作失败');
    } finally {
        markAllLoading.value = false;
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除这条消息吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/notifications/${row.id}`);
        ElMessage.success('删除成功');
        loadNotifications();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleSend = () => {
    Object.assign(sendForm, {
        user_id: null,
        type: 'system',
        title: '',
        content: '',
        channel: 'system',
        priority: 'normal',
        reference_no: ''
    });
    sendDialogVisible.value = true;
};

const handleSendSubmit = async () => {
    if (!sendFormRef.value) return;
    
    await sendFormRef.value.validate(async (valid) => {
        if (valid) {
            sendLoading.value = true;
            try {
                const data = {
                    user_id: sendForm.user_id,
                    type: sendForm.type,
                    title: sendForm.title,
                    content: sendForm.content,
                    channel: sendForm.channel,
                    priority: sendForm.priority,
                    reference_no: sendForm.reference_no || null
                };
                await api.post('/notifications/send', data);
                ElMessage.success('发送成功');
                sendDialogVisible.value = false;
                loadNotifications();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '发送失败');
            } finally {
                sendLoading.value = false;
            }
        }
    });
};

const handleSendDialogClose = () => {
    sendFormRef.value?.resetFields();
};

const handleSizeChange = () => {
    loadNotifications();
};

const handlePageChange = () => {
    loadNotifications();
};

onMounted(() => {
    loadNotifications();
    loadUsers();
});
</script>

<style scoped>
.notifications-page {
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

