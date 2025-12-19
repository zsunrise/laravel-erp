<template>
    <div class="regions-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>地区管理</span>
                    <el-button type="primary" @click="handleAdd">新增地区</el-button>
                </div>
            </template>

            <el-row :gutter="20">
                <el-col :span="12">
                    <el-tree
                        ref="treeRef"
                        :data="treeData"
                        :props="{ children: 'children', label: 'name' }"
                        node-key="id"
                        default-expand-all
                        :expand-on-click-node="false"
                        @node-click="handleNodeClick"
                    >
                        <template #default="{ node, data }">
                            <span class="tree-node">
                                <span>{{ node.label }} ({{ getLevelText(data.level) }})</span>
                                <span class="tree-node-actions">
                                    <el-button type="primary" link size="small" @click.stop="handleAddChild(data)" v-if="data.level < 3">添加子地区</el-button>
                                    <el-button type="warning" link size="small" @click.stop="handleEdit(data)">编辑</el-button>
                                    <el-button type="danger" link size="small" @click.stop="handleDelete(data)">删除</el-button>
                                </span>
                            </span>
                        </template>
                    </el-tree>
                </el-col>
                <el-col :span="12">
                    <el-card v-if="currentRegion">
                        <template #header>
                            <span>地区详情</span>
                        </template>
                        <el-descriptions :column="1" border>
                            <el-descriptions-item label="地区名称">{{ currentRegion.name }}</el-descriptions-item>
                            <el-descriptions-item label="地区编码">{{ currentRegion.code }}</el-descriptions-item>
                            <el-descriptions-item label="级别">{{ getLevelText(currentRegion.level) }}</el-descriptions-item>
                            <el-descriptions-item label="父地区">{{ currentRegion.parent?.name || '无' }}</el-descriptions-item>
                            <el-descriptions-item label="排序">{{ currentRegion.sort }}</el-descriptions-item>
                            <el-descriptions-item label="状态">
                                <el-tag :type="currentRegion.is_active ? 'success' : 'danger'">
                                    {{ currentRegion.is_active ? '启用' : '禁用' }}
                                </el-tag>
                            </el-descriptions-item>
                        </el-descriptions>
                    </el-card>
                </el-col>
            </el-row>
        </el-card>

        <!-- 地区表单对话框 -->
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
                <el-form-item label="父地区" prop="parent_id">
                    <el-tree-select
                        v-model="form.parent_id"
                        :data="treeData"
                        :props="{ children: 'children', label: 'name', value: 'id' }"
                        placeholder="请选择父地区（可选）"
                        check-strictly
                        clearable
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="地区名称" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="地区编码" prop="code">
                    <el-input v-model="form.code" />
                </el-form-item>
                <el-form-item label="级别" prop="level">
                    <el-select v-model="form.level" placeholder="请选择级别" style="width: 100%">
                        <el-option label="省/直辖市" :value="1" />
                        <el-option label="市" :value="2" />
                        <el-option label="区/县" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="form.sort" :min="0" style="width: 100%" />
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
import api from '../../services/api';

const treeRef = ref(null);
const formRef = ref(null);
const treeData = ref([]);
const currentRegion = ref(null);
const dialogVisible = ref(false);
const dialogTitle = ref('新增地区');
const submitLoading = ref(false);

const form = reactive({
    id: null,
    parent_id: null,
    name: '',
    code: '',
    level: 1,
    sort: 0,
    is_active: true
});

const rules = {
    name: [{ required: true, message: '请输入地区名称', trigger: 'blur' }],
    code: [{ required: true, message: '请输入地区编码', trigger: 'blur' }],
    level: [{ required: true, message: '请选择级别', trigger: 'change' }]
};

const getLevelText = (level) => {
    const levelMap = {
        1: '省/直辖市',
        2: '市',
        3: '区/县'
    };
    return levelMap[level] || '未知';
};

const loadTreeData = async () => {
    try {
        const response = await api.get('/regions', { params: { tree: true } });
        treeData.value = response.data.data || [];
    } catch (error) {
        ElMessage.error('加载地区数据失败');
    }
};

const handleNodeClick = async (data) => {
    try {
        const response = await api.get(`/regions/${data.id}`);
        currentRegion.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载地区详情失败');
    }
};

const handleAdd = () => {
    dialogTitle.value = '新增地区';
    Object.assign(form, {
        id: null,
        parent_id: null,
        name: '',
        code: '',
        level: 1,
        sort: 0,
        is_active: true
    });
    dialogVisible.value = true;
};

const handleAddChild = (parent) => {
    dialogTitle.value = '新增子地区';
    Object.assign(form, {
        id: null,
        parent_id: parent.id,
        name: '',
        code: '',
        level: parent.level + 1,
        sort: 0,
        is_active: true
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/regions/${row.id}`);
        const region = response.data.data;
        dialogTitle.value = '编辑地区';
        Object.assign(form, {
            id: region.id,
            parent_id: region.parent_id,
            name: region.name,
            code: region.code,
            level: region.level,
            sort: region.sort,
            is_active: region.is_active
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载地区信息失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该地区吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/regions/${row.id}`);
        ElMessage.success('删除成功');
        loadTreeData();
        currentRegion.value = null;
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
                    await api.put(`/regions/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/regions', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadTreeData();
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
    loadTreeData();
});
</script>

<style scoped>
.regions-page {
    padding: 0;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tree-node {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 14px;
    padding-right: 8px;
}

.tree-node-actions {
    margin-left: 10px;
}
</style>

