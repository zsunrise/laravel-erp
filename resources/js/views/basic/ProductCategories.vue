<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">商品分类管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增分类
                    </el-button>
                </div>
            </div>

            <el-row :gutter="20" style="margin: 24px;">
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
                                <span>{{ node.label }}</span>
                                <span class="tree-node-actions">
                                    <el-button type="primary" link size="small" @click.stop="handleAddChild(data)">添加子分类</el-button>
                                    <el-button type="warning" link size="small" @click.stop="handleEdit(data)">编辑</el-button>
                                    <el-button type="danger" link size="small" @click.stop="handleDelete(data)">删除</el-button>
                                </span>
                            </span>
                        </template>
                    </el-tree>
                </el-col>
                <el-col :span="12">
                    <el-card v-if="currentCategory">
                        <template #header>
                            <span>分类详情</span>
                        </template>
                        <el-descriptions :column="1" border>
                            <el-descriptions-item label="分类名称">{{ currentCategory.name }}</el-descriptions-item>
                            <el-descriptions-item label="分类编码">{{ currentCategory.code }}</el-descriptions-item>
                            <el-descriptions-item label="父分类">{{ currentCategory.parent?.name || '无' }}</el-descriptions-item>
                            <el-descriptions-item label="排序">{{ currentCategory.sort }}</el-descriptions-item>
                            <el-descriptions-item label="状态">
                                <el-tag :type="currentCategory.is_active ? 'success' : 'danger'">
                                    {{ currentCategory.is_active ? '启用' : '禁用' }}
                                </el-tag>
                            </el-descriptions-item>
                            <el-descriptions-item label="描述">{{ currentCategory.description || '-' }}</el-descriptions-item>
                        </el-descriptions>
                    </el-card>
                </el-col>
            </el-row>
        </div>

        <!-- 分类表单对话框 -->
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
                <el-form-item label="父分类" prop="parent_id">
                    <el-tree-select
                        v-model="form.parent_id"
                        :data="treeData"
                        :props="{ children: 'children', label: 'name', value: 'id' }"
                        placeholder="请选择父分类（可选）"
                        check-strictly
                        clearable
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="分类名称" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="分类编码" prop="code">
                    <el-input v-model="form.code" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="form.sort" :min="0" style="width: 100%" />
                </el-form-item>
                <el-form-item label="状态" prop="is_active">
                    <el-switch v-model="form.is_active" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="3" />
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

const treeRef = ref(null);
const formRef = ref(null);
const treeData = ref([]);
const currentCategory = ref(null);
const dialogVisible = ref(false);
const dialogTitle = ref('新增分类');
const submitLoading = ref(false);

const form = reactive({
    id: null,
    parent_id: null,
    name: '',
    code: '',
    sort: 0,
    is_active: true,
    description: ''
});

const rules = {
    name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
    code: [{ required: true, message: '请输入分类编码', trigger: 'blur' }]
};

const loadTreeData = async () => {
    try {
        const response = await api.get('/product-categories', { params: { tree: true } });
        // API直接返回数组，不是包装在data字段中
        treeData.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
    } catch (error) {
        ElMessage.error('加载分类数据失败');
        console.error('加载分类数据失败:', error);
    }
};

const handleNodeClick = async (data) => {
    try {
        const response = await api.get(`/product-categories/${data.id}`);
        currentCategory.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载分类详情失败');
    }
};

const handleAdd = () => {
    dialogTitle.value = '新增分类';
    Object.assign(form, {
        id: null,
        parent_id: null,
        name: '',
        code: '',
        sort: 0,
        is_active: true,
        description: ''
    });
    dialogVisible.value = true;
};

const handleAddChild = (parent) => {
    dialogTitle.value = '新增子分类';
    Object.assign(form, {
        id: null,
        parent_id: parent.id,
        name: '',
        code: '',
        sort: 0,
        is_active: true,
        description: ''
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/product-categories/${row.id}`);
        const category = response.data.data;
        dialogTitle.value = '编辑分类';
        Object.assign(form, {
            id: category.id,
            parent_id: category.parent_id,
            name: category.name,
            code: category.code,
            sort: category.sort,
            is_active: category.is_active,
            description: category.description || ''
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载分类信息失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该分类吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/product-categories/${row.id}`);
        ElMessage.success('删除成功');
        loadTreeData();
        currentCategory.value = null;
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
                    await api.put(`/product-categories/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/product-categories', form);
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

