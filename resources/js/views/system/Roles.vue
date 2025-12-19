<template>
    <div class="roles-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>角色管理</span>
                    <el-button type="primary" @click="handleAdd">新增角色</el-button>
                </div>
            </template>

            <el-table :data="roles" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="name" label="角色名称" />
                <el-table-column prop="slug" label="标识" />
                <el-table-column prop="description" label="描述" />
                <el-table-column prop="permissions" label="权限">
                    <template #default="{ row }">
                        <el-tag v-for="permission in row.permissions" :key="permission.id" style="margin-right: 5px;">
                            {{ permission.name }}
                        </el-tag>
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
                        <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- 角色表单对话框 -->
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
                <el-form-item label="角色名称" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="标识" prop="slug">
                    <el-input v-model="form.slug" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="3" />
                </el-form-item>
                <el-form-item label="权限" prop="permissions">
                    <el-tree
                        ref="permissionTreeRef"
                        :data="permissionTree"
                        show-checkbox
                        node-key="id"
                        :props="{ children: 'children', label: 'name' }"
                        :default-checked-keys="form.permissions"
                    />
                </el-form-item>
                <el-form-item label="状态" prop="is_active">
                    <el-switch v-model="form.is_active" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '../../services/api';

const loading = ref(false);
const dialogVisible = ref(false);
const dialogTitle = ref('新增角色');
const formRef = ref(null);
const permissionTreeRef = ref(null);
const roles = ref([]);
const permissionTree = ref([]);

const form = reactive({
    id: null,
    name: '',
    slug: '',
    description: '',
    permissions: [],
    is_active: true
});

const rules = {
    name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
    slug: [{ required: true, message: '请输入标识', trigger: 'blur' }]
};

const loadRoles = async () => {
    loading.value = true;
    try {
        const response = await api.get('/roles');
        roles.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载角色列表失败');
    } finally {
        loading.value = false;
    }
};

const loadPermissions = async () => {
    try {
        const response = await api.get('/permissions');
        permissionTree.value = response.data.data || [];
    } catch (error) {
        console.error('加载权限列表失败:', error);
    }
};

const handleAdd = () => {
    dialogTitle.value = '新增角色';
    Object.assign(form, {
        id: null,
        name: '',
        slug: '',
        description: '',
        permissions: [],
        is_active: true
    });
    dialogVisible.value = true;
    if (permissionTreeRef.value) {
        permissionTreeRef.value.setCheckedKeys([]);
    }
};

const handleEdit = (row) => {
    dialogTitle.value = '编辑角色';
    Object.assign(form, {
        id: row.id,
        name: row.name,
        slug: row.slug,
        description: row.description || '',
        permissions: row.permissions.map(p => p.id),
        is_active: row.is_active
    });
    dialogVisible.value = true;
    if (permissionTreeRef.value) {
        permissionTreeRef.value.setCheckedKeys(form.permissions);
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该角色吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/roles/${row.id}`);
        ElMessage.success('删除成功');
        loadRoles();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            try {
                if (permissionTreeRef.value) {
                    form.permissions = permissionTreeRef.value.getCheckedKeys();
                }
                if (form.id) {
                    await api.put(`/roles/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/roles', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadRoles();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

onMounted(() => {
    loadRoles();
    loadPermissions();
});
</script>

<style scoped>
.roles-page {
    padding: 0;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

