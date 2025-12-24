<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">消息模板管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增模板
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="模板编码/名称" clearable />
                </el-form-item>
                <el-form-item label="类型">
                    <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 150px">
                        <el-option label="系统消息" value="system" />
                        <el-option label="审批消息" value="approval" />
                        <el-option label="订单消息" value="order" />
                        <el-option label="库存消息" value="inventory" />
                        <el-option label="财务消息" value="financial" />
                    </el-select>
                </el-form-item>
                <el-form-item label="渠道">
                    <el-select v-model="searchForm.channel" placeholder="全部" clearable style="width: 150px">
                        <el-option label="系统消息" value="system" />
                        <el-option label="邮件" value="email" />
                        <el-option label="短信" value="sms" />
                        <el-option label="推送" value="push" />
                    </el-select>
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
                <el-table :data="templates" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="code" label="模板编码" width="150" />
                <el-table-column prop="name" label="模板名称" />
                <el-table-column prop="type" label="类型" width="120">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.type)">{{ getTypeText(row.type) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="channel" label="渠道" width="100">
                    <template #default="{ row }">
                        <el-tag size="small">{{ getChannelText(row.channel) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'danger'">
                            {{ row.is_active ? '启用' : '禁用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="creator.name" label="创建人" width="120" />
                <el-table-column prop="created_at" label="创建时间" width="180" />
                <el-table-column label="操作" width="250" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)" :loading="viewLoadingId === row.id" :disabled="viewLoadingId !== null">查看</el-button>
                        <el-button type="warning" size="small" @click="handleEdit(row)">编辑</el-button>
                        <el-button type="info" size="small" @click="handlePreview(row)">预览</el-button>
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

        <!-- 模板表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
            width="800px"
            @close="handleDialogClose"
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="120px"
            >
                <el-form-item label="模板编码" prop="code">
                    <el-input v-model="form.code" :disabled="!!form.id" />
                </el-form-item>
                <el-form-item label="模板名称" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="类型" prop="type">
                    <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%">
                        <el-option label="系统消息" value="system" />
                        <el-option label="审批消息" value="approval" />
                        <el-option label="订单消息" value="order" />
                        <el-option label="库存消息" value="inventory" />
                        <el-option label="财务消息" value="financial" />
                    </el-select>
                </el-form-item>
                <el-form-item label="渠道" prop="channel">
                    <el-select v-model="form.channel" placeholder="请选择渠道" style="width: 100%">
                        <el-option label="系统消息" value="system" />
                        <el-option label="邮件" value="email" />
                        <el-option label="短信" value="sms" />
                        <el-option label="推送" value="push" />
                    </el-select>
                </el-form-item>
                <el-form-item label="主题" prop="subject" v-if="form.channel == 'email'">
                    <el-input v-model="form.subject" placeholder="邮件主题（可使用变量，如：{name}）" />
                </el-form-item>
                <el-form-item label="内容" prop="content">
                    <el-input v-model="form.content" type="textarea" :rows="8" placeholder="模板内容（可使用变量，如：{name}、{amount}等）" />
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

        <!-- 模板详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="模板详情"
            width="800px"
            :close-on-click-modal="false"
        >
            <div v-loading="detailLoading">
                <el-descriptions :column="2" border v-if="currentTemplate">
                <el-descriptions-item label="模板编码">{{ currentTemplate.code }}</el-descriptions-item>
                <el-descriptions-item label="模板名称">{{ currentTemplate.name }}</el-descriptions-item>
                <el-descriptions-item label="类型">
                    <el-tag :type="getTypeTagType(currentTemplate.type)">{{ getTypeText(currentTemplate.type) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="渠道">
                    <el-tag>{{ getChannelText(currentTemplate.channel) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="currentTemplate.is_active ? 'success' : 'danger'">
                        {{ currentTemplate.is_active ? '启用' : '禁用' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="创建人">{{ currentTemplate.creator?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="创建时间">{{ currentTemplate.created_at }}</el-descriptions-item>
                <el-descriptions-item label="主题" v-if="currentTemplate.subject" :span="2">{{ currentTemplate.subject }}</el-descriptions-item>
                <el-descriptions-item label="内容" :span="2">
                    <div style="white-space: pre-wrap;">{{ currentTemplate.content }}</div>
                </el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>

        <!-- 预览对话框 -->
        <el-dialog
            v-model="previewVisible"
            title="模板预览"
            width="800px"
        >
            <el-form label-width="120px">
                <el-form-item label="测试数据">
                    <el-input v-model="previewData" type="textarea" :rows="3" placeholder='请输入JSON格式的测试数据，如：{"name":"张三","amount":"1000"}' />
                </el-form-item>
            </el-form>
            <el-divider />
            <div v-if="previewResult">
                <el-descriptions :column="1" border>
                    <el-descriptions-item label="主题" v-if="previewResult.subject">{{ previewResult.subject }}</el-descriptions-item>
                    <el-descriptions-item label="内容">
                        <div style="white-space: pre-wrap;">{{ previewResult.content }}</div>
                    </el-descriptions-item>
                </el-descriptions>
            </div>
            <template #footer>
                <el-button @click="previewVisible = false">关闭</el-button>
                <el-button type="primary" @click="handlePreviewSubmit" :loading="previewLoading">预览</el-button>
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
const previewLoading = ref(false);
const dialogVisible = ref(false);
const detailVisible = ref(false);
const detailLoading = ref(false);
const viewLoadingId = ref(null);
const previewVisible = ref(false);
const dialogTitle = ref('新增模板');
const formRef = ref(null);
const templates = ref([]);
const currentTemplate = ref(null);
const previewData = ref('{}');
const previewResult = ref(null);

const searchForm = reactive({
    search: '',
    type: null,
    channel: null,
    is_active: null
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const form = reactive({
    id: null,
    code: '',
    name: '',
    type: 'system',
    channel: 'system',
    subject: '',
    content: '',
    is_active: true
});

const rules = {
    code: [{ required: true, message: '请输入模板编码', trigger: 'blur' }],
    name: [{ required: true, message: '请输入模板名称', trigger: 'blur' }],
    type: [{ required: true, message: '请选择类型', trigger: 'change' }],
    channel: [{ required: true, message: '请选择渠道', trigger: 'change' }],
    content: [{ required: true, message: '请输入模板内容', trigger: 'blur' }]
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

const getChannelText = (channel) => {
    const channelMap = {
        'system': '系统',
        'email': '邮件',
        'sms': '短信',
        'push': '推送'
    };
    return channelMap[channel] || channel;
};

const loadTemplates = async () => {
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
        if (searchForm.type) {
            params.type = searchForm.type;
        }
        if (searchForm.channel) {
            params.channel = searchForm.channel;
        }
        if (searchForm.is_active !== null && searchForm.is_active !== undefined) {
            params.is_active = searchForm.is_active;
        }
        const response = await api.get('/notification-templates', { params });
        templates.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载模板列表失败');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadTemplates();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.type = null;
    searchForm.channel = null;
    searchForm.is_active = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增模板';
    Object.assign(form, {
        id: null,
        code: '',
        name: '',
        type: 'system',
        channel: 'system',
        subject: '',
        content: '',
        is_active: true
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
    currentTemplate.value = null;
    
    try {
        const response = await api.get(`/notification-templates/${row.id}`);
        currentTemplate.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载模板详情失败');
        detailVisible.value = false;
    } finally {
        detailLoading.value = false;
        viewLoadingId.value = null;
    }
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/notification-templates/${row.id}`);
        const template = response.data.data;
        dialogTitle.value = '编辑模板';
        Object.assign(form, {
            id: template.id,
            code: template.code,
            name: template.name,
            type: template.type,
            channel: template.channel,
            subject: template.subject || '',
            content: template.content,
            is_active: template.is_active
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载模板失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该模板吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/notification-templates/${row.id}`);
        ElMessage.success('删除成功');
        loadTemplates();
    } catch (error) {
        if (error != 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handlePreview = async (row) => {
    currentTemplate.value = row;
    previewData.value = '{}';
    previewResult.value = null;
    previewVisible.value = true;
};

const handlePreviewSubmit = async () => {
    if (!currentTemplate.value) return;
    
    try {
        let data = {};
        try {
            data = JSON.parse(previewData.value);
        } catch (e) {
            ElMessage.error('测试数据格式错误，请输入有效的JSON格式');
            return;
        }
        
        previewLoading.value = true;
        const response = await api.post(`/notification-templates/${currentTemplate.value.id}/preview`, { data });
        previewResult.value = response.data.data;
    } catch (error) {
        ElMessage.error(error.response?.data?.message || '预览失败');
    } finally {
        previewLoading.value = false;
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            submitLoading.value = true;
            try {
                const data = {
                    code: form.code,
                    name: form.name,
                    type: form.type,
                    channel: form.channel,
                    subject: form.subject || null,
                    content: form.content,
                    is_active: form.is_active
                };
                if (form.id) {
                    await api.put(`/notification-templates/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/notification-templates', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadTemplates();
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
    loadTemplates();
};

const handlePageChange = () => {
    loadTemplates();
};

onMounted(() => {
    loadTemplates();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

