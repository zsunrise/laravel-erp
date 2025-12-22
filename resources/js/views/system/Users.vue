<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">用户管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增用户
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="姓名/邮箱/手机" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.is_active" placeholder="全部" clearable>
                        <el-option label="启用" :value="true" />
                        <el-option label="禁用" :value="false" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="users" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="name" label="姓名" />
                <el-table-column prop="email" label="邮箱" />
                <el-table-column prop="phone" label="手机" />
                <el-table-column prop="roles" label="角色">
                    <template #default="{ row }">
                        <el-tag v-for="role in row.roles" :key="role.id" style="margin-right: 5px;">
                            {{ role.name }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <span 
                            :class="row.is_active ? 'badge-success' : 'badge-muted'"
                            class="status-badge"
                        >
                            {{ row.is_active ? '启用' : '禁用' }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)" class="interactive">编辑</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)" class="interactive">删除</el-button>
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

        <!-- 用户表单对话框 -->
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
                <el-form-item label="姓名" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="邮箱" prop="email">
                    <el-input v-model="form.email" />
                </el-form-item>
                <el-form-item label="密码" prop="password" v-if="!form.id">
                    <el-input v-model="form.password" type="password" />
                </el-form-item>
                <el-form-item label="手机" prop="phone">
                    <el-input v-model="form.phone" />
                </el-form-item>
                <el-form-item label="角色" prop="roles">
                    <el-select v-model="form.roles" multiple placeholder="请选择角色">
                        <el-option
                            v-for="role in roles"
                            :key="role.id"
                            :label="role.name"
                            :value="role.id"
                        />
                    </el-select>
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
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const dialogVisible = ref(false);
const dialogTitle = ref('新增用户');
const formRef = ref(null);

const users = ref([]);
const roles = ref([]);

const searchForm = reactive({
    search: '',
    is_active: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    id: null,
    name: '',
    email: '',
    password: '',
    phone: '',
    roles: [],
    is_active: true
});

const rules = {
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    email: [
        { required: true, message: '请输入邮箱', trigger: 'blur' },
        { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
    ],
    password: [
        { required: true, message: '请输入密码', trigger: 'blur' },
        { min: 6, message: '密码长度不能少于6位', trigger: 'blur' }
    ]
};

const loadUsers = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/users', { params });
        users.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载用户列表失败');
    } finally {
        loading.value = false;
    }
};

const loadRoles = async () => {
    try {
        const response = await api.get('/roles');
        roles.value = response.data.data;
    } catch (error) {
        console.error('加载角色列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadUsers();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.is_active = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增用户';
    Object.assign(form, {
        id: null,
        name: '',
        email: '',
        password: '',
        phone: '',
        roles: [],
        is_active: true
    });
    dialogVisible.value = true;
};

const handleEdit = (row) => {
    dialogTitle.value = '编辑用户';
    Object.assign(form, {
        id: row.id,
        name: row.name,
        email: row.email,
        password: '',
        phone: row.phone,
        roles: row.roles.map(r => r.id),
        is_active: row.is_active
    });
    dialogVisible.value = true;
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该用户吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/users/${row.id}`);
        ElMessage.success('删除成功');
        loadUsers();
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
                if (form.id) {
                    await api.put(`/users/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/users', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadUsers();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

const handleSizeChange = () => {
    loadUsers();
};

const handlePageChange = () => {
    loadUsers();
};

onMounted(() => {
    loadUsers();
    loadRoles();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

