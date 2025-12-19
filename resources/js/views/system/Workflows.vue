<template>
    <div class="workflows-page">
        <el-tabs v-model="activeTab">
            <el-tab-pane label="流程设计" name="design">
                <el-card>
                    <template #header>
                        <div class="card-header">
                            <span>审批流程</span>
                            <el-button type="primary" @click="handleAdd">新增流程</el-button>
                        </div>
                    </template>

                    <el-form :inline="true" :model="searchForm" class="search-form">
                        <el-form-item label="流程名称">
                            <el-input v-model="searchForm.name" placeholder="流程名称" clearable />
                        </el-form-item>
                        <el-form-item label="流程类型">
                            <el-select v-model="searchForm.type" placeholder="全部" clearable>
                                <el-option label="采购订单" value="purchase_order" />
                                <el-option label="销售订单" value="sales_order" />
                                <el-option label="费用报销" value="expense" />
                                <el-option label="工单" value="work_order" />
                                <el-option label="生产计划" value="production_plan" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="searchForm.is_active" placeholder="全部" clearable>
                                <el-option label="启用" :value="1" />
                                <el-option label="禁用" :value="0" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="handleSearch">查询</el-button>
                            <el-button @click="handleReset">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <el-table :data="workflows" v-loading="loading" style="width: 100%">
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column prop="name" label="流程名称" />
                        <el-table-column prop="code" label="流程编码" width="150" />
                        <el-table-column prop="type" label="流程类型" width="150">
                            <template #default="{ row }">
                                {{ getTypeText(row.type) }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="is_active" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="row.is_active ? 'success' : 'danger'">
                                    {{ row.is_active ? '启用' : '禁用' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="nodes_count" label="节点数" width="100">
                            <template #default="{ row }">
                                {{ row.nodes?.length || 0 }}
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="250" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" size="small" @click="handleView(row)">查看</el-button>
                                <el-button type="warning" size="small" @click="handleEdit(row)">编辑</el-button>
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
            </el-tab-pane>

            <el-tab-pane label="待审批" name="pending">
                <el-card>
                    <template #header>
                        <span>待审批事项</span>
                    </template>

                    <el-table :data="pendingApprovals" v-loading="pendingLoading" style="width: 100%">
                        <el-table-column prop="instance_no" label="实例编号" width="180" />
                        <el-table-column prop="workflow.name" label="流程名称" />
                        <el-table-column prop="reference_type" label="关联类型" width="150">
                            <template #default="{ row }">
                                {{ getReferenceTypeText(row.reference_type) }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="reference_no" label="关联单据号" width="150" />
                        <el-table-column prop="current_node.node_name" label="当前节点" width="150" />
                        <el-table-column prop="started_at" label="发起时间" width="180" />
                        <el-table-column label="操作" width="200" fixed="right">
                            <template #default="{ row }">
                                <el-button type="success" size="small" @click="handleApprove(row)">同意</el-button>
                                <el-button type="danger" size="small" @click="handleReject(row)">拒绝</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </el-tab-pane>

            <el-tab-pane label="流程实例" name="instances">
                <el-card>
                    <template #header>
                        <span>流程实例</span>
                    </template>

                    <el-form :inline="true" :model="instanceSearchForm" class="search-form">
                        <el-form-item label="实例编号">
                            <el-input v-model="instanceSearchForm.instance_no" placeholder="实例编号" clearable />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="instanceSearchForm.status" placeholder="全部" clearable>
                                <el-option label="待审批" value="pending" />
                                <el-option label="已通过" value="approved" />
                                <el-option label="已拒绝" value="rejected" />
                                <el-option label="已取消" value="cancelled" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="handleInstanceSearch">查询</el-button>
                            <el-button @click="handleInstanceReset">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <el-table :data="instances" v-loading="instanceLoading" style="width: 100%">
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column prop="instance_no" label="实例编号" width="180" />
                        <el-table-column prop="workflow.name" label="流程名称" />
                        <el-table-column prop="reference_type" label="关联类型" width="150">
                            <template #default="{ row }">
                                {{ getReferenceTypeText(row.reference_type) }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="reference_no" label="关联单据号" width="150" />
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getInstanceStatusType(row.status)">{{ getInstanceStatusText(row.status) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="started_at" label="发起时间" width="180" />
                        <el-table-column label="操作" width="150" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" size="small" @click="handleViewInstance(row)">查看</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <el-pagination
                        v-model:current-page="instancePagination.page"
                        v-model:page-size="instancePagination.per_page"
                        :total="instancePagination.total"
                        :page-sizes="[10, 20, 50, 100]"
                        layout="total, sizes, prev, pager, next, jumper"
                        @size-change="handleInstanceSizeChange"
                        @current-change="handleInstancePageChange"
                        style="margin-top: 20px;"
                    />
                </el-card>
            </el-tab-pane>
        </el-tabs>

        <!-- 流程表单对话框 -->
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
                        <el-form-item label="流程名称" prop="name">
                            <el-input v-model="form.name" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="流程编码" prop="code">
                            <el-input v-model="form.code" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="流程类型" prop="type">
                            <el-select v-model="form.type" placeholder="请选择流程类型" style="width: 100%">
                                <el-option label="采购订单" value="purchase_order" />
                                <el-option label="销售订单" value="sales_order" />
                                <el-option label="费用报销" value="expense" />
                                <el-option label="工单" value="work_order" />
                                <el-option label="生产计划" value="production_plan" />
                            </el-select>
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
                <el-form-item label="流程节点" prop="nodes">
                    <el-button type="primary" size="small" @click="handleAddNode">添加节点</el-button>
                    <el-table :data="form.nodes" style="margin-top: 10px;" border>
                        <el-table-column prop="node_name" label="节点名称" width="150">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.node_name" placeholder="节点名称" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="node_type" label="节点类型" width="150">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.node_type" style="width: 100%">
                                    <el-option label="开始" value="start" />
                                    <el-option label="审批" value="approval" />
                                    <el-option label="条件" value="condition" />
                                    <el-option label="结束" value="end" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column prop="sequence" label="序号" width="100">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.sequence" :min="1" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="approval_type" label="审批类型" width="120" v-if="form.nodes.some(n => n.node_type == 'approval')">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.approval_type" v-if="row.node_type == 'approval'" style="width: 100%">
                                    <el-option label="单人审批" value="single" />
                                    <el-option label="全部通过" value="all" />
                                    <el-option label="任一通过" value="any" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column prop="timeout_hours" label="超时(小时)" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.timeout_hours" :min="0" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="is_required" label="必填" width="80">
                            <template #default="{ row, $index }">
                                <el-switch v-model="row.is_required" v-if="row.node_type == 'approval'" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="remark" label="备注" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.remark" placeholder="备注" />
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ $index }">
                                <el-button type="danger" size="small" @click="handleRemoveNode($index)">删除</el-button>
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

        <!-- 流程详情对话框 -->
        <el-dialog
            v-model="detailVisible"
            title="流程详情"
            width="1000px"
        >
            <el-descriptions :column="2" border v-if="currentWorkflow">
                <el-descriptions-item label="流程名称">{{ currentWorkflow.name }}</el-descriptions-item>
                <el-descriptions-item label="流程编码">{{ currentWorkflow.code }}</el-descriptions-item>
                <el-descriptions-item label="流程类型">{{ getTypeText(currentWorkflow.type) }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="currentWorkflow.is_active ? 'success' : 'danger'">
                        {{ currentWorkflow.is_active ? '启用' : '禁用' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="描述" :span="2">{{ currentWorkflow.description || '-' }}</el-descriptions-item>
            </el-descriptions>
            <el-table :data="currentWorkflow?.nodes || []" style="margin-top: 20px;" border>
                <el-table-column prop="node_name" label="节点名称" />
                <el-table-column prop="node_type" label="节点类型" width="120">
                    <template #default="{ row }">
                        {{ getNodeTypeText(row.node_type) }}
                    </template>
                </el-table-column>
                <el-table-column prop="sequence" label="序号" width="80" />
                <el-table-column prop="approval_type" label="审批类型" width="120">
                    <template #default="{ row }">
                        {{ row.approval_type ? getApprovalTypeText(row.approval_type) : '-' }}
                    </template>
                </el-table-column>
                <el-table-column prop="timeout_hours" label="超时(小时)" width="120" />
                <el-table-column prop="is_required" label="必填" width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_required" type="success">是</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="remark" label="备注" />
            </el-table>
        </el-dialog>

        <!-- 审批/拒绝对话框 -->
        <el-dialog
            v-model="approvalDialogVisible"
            :title="approvalDialogTitle"
            width="600px"
        >
            <el-form
                ref="approvalFormRef"
                :model="approvalForm"
                label-width="100px"
            >
                <el-form-item label="审批意见">
                    <el-input v-model="approvalForm.comment" type="textarea" :rows="4" placeholder="请输入审批意见" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="approvalDialogVisible = false">取消</el-button>
                <el-button :type="approvalAction == 'approve' ? 'success' : 'danger'" @click="handleSubmitApproval" :loading="approvalSubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 实例详情对话框 -->
        <el-dialog
            v-model="instanceDetailVisible"
            title="流程实例详情"
            width="1000px"
        >
            <el-descriptions :column="2" border v-if="currentInstance">
                <el-descriptions-item label="实例编号">{{ currentInstance.instance_no }}</el-descriptions-item>
                <el-descriptions-item label="流程名称">{{ currentInstance.workflow?.name }}</el-descriptions-item>
                <el-descriptions-item label="关联类型">{{ getReferenceTypeText(currentInstance.reference_type) }}</el-descriptions-item>
                <el-descriptions-item label="关联单据号">{{ currentInstance.reference_no }}</el-descriptions-item>
                <el-descriptions-item label="当前节点">{{ currentInstance.current_node?.node_name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getInstanceStatusType(currentInstance.status)">{{ getInstanceStatusText(currentInstance.status) }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="发起人">{{ currentInstance.starter?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="发起时间">{{ currentInstance.started_at || '-' }}</el-descriptions-item>
            </el-descriptions>
            <el-divider>审批记录</el-divider>
            <el-table :data="approvalHistory || []" style="margin-top: 20px;" border>
                <el-table-column prop="node.node_name" label="节点" width="150" />
                <el-table-column prop="approver.name" label="审批人" width="120" />
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.status == 'approved' ? 'success' : row.status == 'rejected' ? 'danger' : 'warning'">
                            {{ row.status == 'approved' ? '已同意' : row.status == 'rejected' ? '已拒绝' : '待审批' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="comment" label="审批意见" />
                <el-table-column prop="created_at" label="审批时间" width="180" />
            </el-table>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '../../services/api';

const activeTab = ref('design');
const loading = ref(false);
const pendingLoading = ref(false);
const instanceLoading = ref(false);
const submitLoading = ref(false);
const approvalSubmitLoading = ref(false);
const dialogVisible = ref(false);
const detailVisible = ref(false);
const approvalDialogVisible = ref(false);
const instanceDetailVisible = ref(false);
const formRef = ref(null);
const approvalFormRef = ref(null);
const workflows = ref([]);
const pendingApprovals = ref([]);
const instances = ref([]);
const approvalHistory = ref([]);
const currentWorkflow = ref(null);
const currentInstance = ref(null);
const currentApprovalInstance = ref(null);
const approvalAction = ref('approve');
const approvalDialogTitle = ref('审批');

const searchForm = reactive({
    name: '',
    type: null,
    is_active: null
});

const instanceSearchForm = reactive({
    instance_no: '',
    status: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const instancePagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    id: null,
    name: '',
    code: '',
    type: '',
    description: '',
    is_active: true,
    nodes: []
});

const approvalForm = reactive({
    comment: ''
});

const rules = {
    name: [{ required: true, message: '请输入流程名称', trigger: 'blur' }],
    code: [{ required: true, message: '请输入流程编码', trigger: 'blur' }],
    type: [{ required: true, message: '请选择流程类型', trigger: 'change' }],
    nodes: [
        { required: true, message: '请添加流程节点', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一个节点', trigger: 'change' }
    ]
};

const dialogTitle = ref('新增流程');

const getTypeText = (type) => {
    const typeMap = {
        'purchase_order': '采购订单',
        'sales_order': '销售订单',
        'expense': '费用报销',
        'work_order': '工单',
        'production_plan': '生产计划'
    };
    return typeMap[type] || type;
};

const getNodeTypeText = (type) => {
    const typeMap = {
        'start': '开始',
        'approval': '审批',
        'condition': '条件',
        'end': '结束'
    };
    return typeMap[type] || type;
};

const getApprovalTypeText = (type) => {
    const typeMap = {
        'single': '单人审批',
        'all': '全部通过',
        'any': '任一通过'
    };
    return typeMap[type] || type;
};

const getReferenceTypeText = (type) => {
    const typeMap = {
        'purchase_order': '采购订单',
        'sales_order': '销售订单',
        'expense': '费用报销',
        'work_order': '工单',
        'production_plan': '生产计划'
    };
    return typeMap[type] || type;
};

const getInstanceStatusType = (status) => {
    const statusMap = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'cancelled': 'info'
    };
    return statusMap[status] || 'info';
};

const getInstanceStatusText = (status) => {
    const statusMap = {
        'pending': '待审批',
        'approved': '已通过',
        'rejected': '已拒绝',
        'cancelled': '已取消'
    };
    return statusMap[status] || status;
};

const loadWorkflows = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/workflows', { params });
        workflows.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载流程列表失败');
    } finally {
        loading.value = false;
    }
};

const loadPendingApprovals = async () => {
    pendingLoading.value = true;
    try {
        const response = await api.get('/approval-records/pending');
        pendingApprovals.value = response.data.data || [];
    } catch (error) {
        ElMessage.error('加载待审批列表失败');
    } finally {
        pendingLoading.value = false;
    }
};

const loadInstances = async () => {
    instanceLoading.value = true;
    try {
        const params = {
            page: instancePagination.page,
            per_page: instancePagination.per_page,
            ...instanceSearchForm
        };
        const response = await api.get('/workflow-instances', { params });
        instances.value = response.data.data;
        instancePagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载流程实例列表失败');
    } finally {
        instanceLoading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadWorkflows();
};

const handleReset = () => {
    searchForm.name = '';
    searchForm.type = null;
    searchForm.is_active = null;
    handleSearch();
};

const handleInstanceSearch = () => {
    instancePagination.page = 1;
    loadInstances();
};

const handleInstanceReset = () => {
    instanceSearchForm.instance_no = '';
    instanceSearchForm.status = null;
    handleInstanceSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增流程';
    Object.assign(form, {
        id: null,
        name: '',
        code: '',
        type: '',
        description: '',
        is_active: true,
        nodes: []
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/workflows/${row.id}`);
        const workflow = response.data.data;
        dialogTitle.value = '编辑流程';
        Object.assign(form, {
            id: workflow.id,
            name: workflow.name,
            code: workflow.code,
            type: workflow.type,
            description: workflow.description || '',
            is_active: workflow.is_active,
            nodes: workflow.nodes.map(node => ({
                node_name: node.node_name,
                node_type: node.node_type,
                sequence: node.sequence,
                approval_type: node.approval_type || null,
                approver_config: node.approver_config || null,
                condition_config: node.condition_config || null,
                next_nodes: node.next_nodes || null,
                timeout_hours: node.timeout_hours || 0,
                is_required: node.is_required || false,
                remark: node.remark || ''
            }))
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载流程失败');
    }
};

const handleView = async (row) => {
    try {
        const response = await api.get(`/workflows/${row.id}`);
        currentWorkflow.value = response.data.data;
        detailVisible.value = true;
    } catch (error) {
        ElMessage.error('加载流程详情失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该流程吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/workflows/${row.id}`);
        ElMessage.success('删除成功');
        loadWorkflows();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleAddNode = () => {
    form.nodes.push({
        node_name: '',
        node_type: 'approval',
        sequence: form.nodes.length + 1,
        approval_type: 'single',
        approver_config: null,
        condition_config: null,
        next_nodes: null,
        timeout_hours: 0,
        is_required: false,
        remark: ''
    });
};

const handleRemoveNode = (index) => {
    form.nodes.splice(index, 1);
    form.nodes.forEach((node, idx) => {
        node.sequence = idx + 1;
    });
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            if (form.nodes.length == 0) {
                ElMessage.warning('请至少添加一个节点');
                return;
            }
            submitLoading.value = true;
            try {
                const data = {
                    ...form,
                    nodes: form.nodes.map(node => ({
                        node_name: node.node_name,
                        node_type: node.node_type,
                        sequence: node.sequence,
                        approval_type: node.approval_type || null,
                        approver_config: node.approver_config || null,
                        condition_config: node.condition_config || null,
                        next_nodes: node.next_nodes || null,
                        timeout_hours: node.timeout_hours || 0,
                        is_required: node.is_required || false,
                        remark: node.remark || ''
                    }))
                };
                if (form.id) {
                    await api.put(`/workflows/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/workflows', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadWorkflows();
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

const handleApprove = (row) => {
    currentApprovalInstance.value = row;
    approvalAction.value = 'approve';
    approvalDialogTitle.value = '审批';
    approvalForm.comment = '';
    approvalDialogVisible.value = true;
};

const handleReject = (row) => {
    currentApprovalInstance.value = row;
    approvalAction.value = 'reject';
    approvalDialogTitle.value = '拒绝';
    approvalForm.comment = '';
    approvalDialogVisible.value = true;
};

const handleSubmitApproval = async () => {
    approvalSubmitLoading.value = true;
    try {
        const endpoint = approvalAction.value == 'approve' ? 'approve' : 'reject';
        await api.post(`/approval-records/${currentApprovalInstance.value.id}/${endpoint}`, {
            comment: approvalForm.comment
        });
        ElMessage.success(approvalAction.value == 'approve' ? '审批成功' : '拒绝成功');
        approvalDialogVisible.value = false;
        loadPendingApprovals();
        loadInstances();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || '操作失败');
    } finally {
        approvalSubmitLoading.value = false;
    }
};

const handleViewInstance = async (row) => {
    try {
        const response = await api.get(`/approval-records/${row.id}/history`);
        currentInstance.value = response.data.data.instance;
        approvalHistory.value = response.data.data.records || [];
        instanceDetailVisible.value = true;
    } catch (error) {
        ElMessage.error('加载实例详情失败');
    }
};

const handleSizeChange = () => {
    loadWorkflows();
};

const handlePageChange = () => {
    loadWorkflows();
};

const handleInstanceSizeChange = () => {
    loadInstances();
};

const handleInstancePageChange = () => {
    loadInstances();
};

onMounted(() => {
    loadWorkflows();
    loadPendingApprovals();
    loadInstances();
});
</script>

<style scoped>
.workflows-page {
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

