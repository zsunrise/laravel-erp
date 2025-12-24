<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">权限管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增权限
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="权限名称/标识" clearable />
                </el-form-item>
                <el-form-item label="分组">
                    <el-input v-model="searchForm.group" placeholder="权限分组" clearable />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="permissions" v-loading="loading" style="width: 100%">
                    <el-table-column prop="id" label="ID" width="80" />
                    <el-table-column prop="name" label="权限名称" />
                    <el-table-column prop="slug" label="标识" />
                    <el-table-column prop="group" label="分组" />
                    <el-table-column prop="description" label="描述" />
                    <el-table-column prop="roles" label="关联角色">
                        <template #default="{ row }">
                            <el-tag v-for="role in row.roles" :key="role.id" style="margin-right: 5px;">
                                {{ role.name }}
                            </el-tag>
                            <span v-if="!row.roles || row.roles.length === 0" class="text-muted">无</span>
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

        <!-- 权限表单对话框 -->
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
                <el-form-item label="权限名称" prop="name">
                    <el-input v-model="form.name" placeholder="请输入权限名称" />
                </el-form-item>
                <el-form-item label="标识" prop="slug">
                    <el-input v-model="form.slug" placeholder="例如: users.manage" />
                    <div class="form-tip">权限标识，用于代码中检查权限，格式：模块.操作</div>
                </el-form-item>
                <el-form-item label="分组" prop="group">
                    <el-input v-model="form.group" placeholder="例如: 用户管理" />
                    <div class="form-tip">用于权限分组显示，方便管理</div>
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="3" placeholder="请输入权限描述" />
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
const dialogTitle = ref('新增权限');
const formRef = ref(null);
const permissions = ref([]);

const searchForm = reactive({
    search: '',
    group: ''
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const form = reactive({
    id: null,
    name: '',
    slug: '',
    group: '',
    description: ''
});

const rules = {
    name: [{ required: true, message: '请输入权限名称', trigger: 'blur' }],
    slug: [
        { required: true, message: '请输入权限标识', trigger: 'blur' },
        { pattern: /^[a-z0-9._-]+$/, message: '权限标识只能包含小写字母、数字、点、下划线和连字符', trigger: 'blur' }
    ]
};

const loadPermissions = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page
        };
        
        if (searchForm.search) {
            params.search = searchForm.search;
        }
        
        if (searchForm.group) {
            params.group = searchForm.group;
        }
        
        const response = await api.get('/permissions', { params });
        // 处理分页响应格式
        if (response.data.pagination) {
            // 使用 ApiResponse::paginated 格式
            permissions.value = response.data.data || [];
            pagination.total = response.data.pagination.total || 0;
        } else if (response.data.data && Array.isArray(response.data.data)) {
            // 使用 Laravel 默认分页格式
            permissions.value = response.data.data;
            pagination.total = response.data.total || 0;
        } else {
            // 兼容其他格式
            permissions.value = response.data.data || [];
            pagination.total = response.data.total || 0;
        }
    } catch (error) {
        ElMessage.error('加载权限列表失败');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadPermissions();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.group = '';
    pagination.page = 1;
    loadPermissions();
};

const handleAdd = () => {
    dialogTitle.value = '新增权限';
    Object.assign(form, {
        id: null,
        name: '',
        slug: '',
        group: '',
        description: ''
    });
    dialogVisible.value = true;
};

const handleEdit = (row) => {
    dialogTitle.value = '编辑权限';
    Object.assign(form, {
        id: row.id,
        name: row.name,
        slug: row.slug,
        group: row.group || '',
        description: row.description || ''
    });
    dialogVisible.value = true;
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该权限吗？删除后无法恢复。', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/permissions/${row.id}`);
        ElMessage.success('删除成功');
        loadPermissions();
    } catch (error) {
        if (error !== 'cancel') {
            const errorMessage = error.response?.data?.message || '删除失败';
            ElMessage.error(errorMessage);
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            try {
                if (form.id) {
                    await api.put(`/permissions/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/permissions', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadPermissions();
            } catch (error) {
                const errorMessage = error.response?.data?.message || '操作失败';
                ElMessage.error(errorMessage);
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

const handlePageChange = (page) => {
    pagination.page = page;
    loadPermissions();
};

const handleSizeChange = (size) => {
    pagination.per_page = size;
    pagination.page = 1;
    loadPermissions();
};

onMounted(() => {
    loadPermissions();
});
</script>

<style scoped>
.form-tip {
    font-size: 12px;
    color: var(--color-text-secondary);
    margin-top: 4px;
}

.text-muted {
    color: var(--color-text-secondary);
    font-size: 12px;
}
</style>

