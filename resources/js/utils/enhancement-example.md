# 功能增强使用示例

## 1. 统一消息提示

```vue
<script setup>
import { useMessage } from '../utils/message';

const { success, error, warning, info } = useMessage();

// 成功提示
success('操作成功');

// 错误提示
error('操作失败');

// 警告提示
warning('请注意');

// 信息提示
info('提示信息');
</script>
```

## 2. 统一确认对话框

```vue
<script setup>
import { useConfirm } from '../utils/message';

const { confirm, deleteConfirm, submitConfirm } = useConfirm();

// 通用确认
const handleAction = async () => {
    try {
        await confirm('确定要执行此操作吗？');
        // 执行操作
    } catch (error) {
        // 用户取消
    }
};

// 删除确认
const handleDelete = async () => {
    try {
        await deleteConfirm('确定要删除该记录吗？');
        // 执行删除
    } catch (error) {
        // 用户取消
    }
};

// 提交确认
const handleSubmit = async () => {
    try {
        await submitConfirm('确定要提交吗？');
        // 执行提交
    } catch (error) {
        // 用户取消
    }
};
</script>
```

## 3. 统一错误处理

```vue
<script setup>
import { useErrorHandler } from '../utils/message';

const { handleError, handleValidationError } = useErrorHandler();

// 处理错误
const loadData = async () => {
    try {
        await api.get('/data');
    } catch (error) {
        handleError(error, '加载数据失败');
    }
};

// 处理验证错误
const submitForm = async () => {
    try {
        await api.post('/submit', formData);
    } catch (error) {
        if (error.response?.status == 422) {
            const message = handleValidationError(error);
            // 显示验证错误
        } else {
            handleError(error);
        }
    }
};
</script>
```

## 4. 统一加载状态管理

```vue
<script setup>
import { useLoading } from '../composables/useLoading';

const { loading, startLoading, stopLoading, withLoading } = useLoading();

// 方式1：手动控制
const loadData = async () => {
    startLoading();
    try {
        await api.get('/data');
    } finally {
        stopLoading();
    }
};

// 方式2：自动管理
const loadData2 = async () => {
    await withLoading(async () => {
        await api.get('/data');
    });
};

// 方式3：在模板中使用
</script>

<template>
    <div v-loading="loading">内容</div>
</template>
```

## 5. 统一 API 调用（推荐）

```vue
<script setup>
import { useApi } from '../composables/useApi';
import { useConfirm } from '../utils/message';

const { loading, get, post, put, delete: del, success, error, handleError } = useApi();
const { deleteConfirm } = useConfirm();

const data = ref([]);

// 加载数据
const loadData = async () => {
    try {
        const response = await get('/data');
        data.value = response.data;
    } catch (err) {
        handleError(err, '加载数据失败');
    }
};

// 创建数据
const createData = async (formData) => {
    try {
        await post('/data', formData);
        success('创建成功');
        loadData();
    } catch (err) {
        // 错误已在拦截器中处理
    }
};

// 更新数据
const updateData = async (id, formData) => {
    try {
        await put(`/data/${id}`, formData);
        success('更新成功');
        loadData();
    } catch (err) {
        // 错误已在拦截器中处理
    }
};

// 删除数据
const deleteData = async (id) => {
    try {
        await deleteConfirm('确定要删除吗？');
        await del(`/data/${id}`);
        success('删除成功');
        loadData();
    } catch (err) {
        if (err != 'cancel') {
            // 错误已在拦截器中处理
        }
    }
};
</script>

<template>
    <div v-loading="loading">
        <!-- 内容 -->
    </div>
</template>
```

## 6. 权限控制

### 在模板中使用 v-permission 指令

```vue
<template>
    <!-- 单个权限 -->
    <el-button v-permission="'users.manage'">新增用户</el-button>
    
    <!-- 多个权限（任一） -->
    <el-button v-permission="['users.manage', 'roles.manage']">管理</el-button>
    
    <!-- 对象形式 -->
    <el-button v-permission="{ permission: 'users.manage' }">新增</el-button>
    <el-button v-permission="{ any: ['users.manage', 'roles.manage'] }">管理</el-button>
    <el-button v-permission="{ all: ['users.manage', 'roles.manage'] }">管理</el-button>
</template>
```

### 在脚本中使用权限检查

```vue
<script setup>
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();

// 检查单个权限
if (authStore.hasPermission('users.manage')) {
    // 执行操作
}

// 检查多个权限（任一）
if (authStore.hasAnyPermission(['users.manage', 'roles.manage'])) {
    // 执行操作
}

// 检查多个权限（全部）
if (authStore.hasAllPermissions(['users.manage', 'roles.manage'])) {
    // 执行操作
}

// 检查角色
if (authStore.hasRole('admin')) {
    // 执行操作
}
</script>
```

### 路由权限配置

```javascript
// router/index.js
{
    path: 'users',
    name: 'Users',
    component: () => import('../views/system/Users.vue'),
    meta: { 
        title: '用户管理', 
        icon: 'User',
        permission: 'users.manage'  // 单个权限
    }
}

// 或者多个权限（任一）
{
    path: 'reports',
    name: 'Reports',
    component: () => import('../views/reports/Reports.vue'),
    meta: { 
        title: '报表分析', 
        icon: 'DataAnalysis',
        permissions: ['sales-reports.view', 'purchase-reports.view']  // 多个权限（任一）
    }
}
```

## 7. 完整示例

```vue
<template>
    <div class="page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>数据列表</span>
                    <el-button 
                        type="primary" 
                        v-permission="'data.create'"
                        @click="handleAdd"
                    >
                        新增
                    </el-button>
                </div>
            </template>
            
            <el-table v-loading="loading" :data="tableData">
                <el-table-column prop="name" label="名称" />
                <el-table-column label="操作">
                    <template #default="{ row }">
                        <el-button 
                            v-permission="'data.edit'"
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button 
                            v-permission="'data.delete'"
                            type="danger"
                            @click="handleDelete(row)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../composables/useApi';
import { useConfirm } from '../utils/message';

const { loading, get, post, put, delete: del, success } = useApi();
const { deleteConfirm } = useConfirm();

const tableData = ref([]);

const loadData = async () => {
    try {
        const response = await get('/data');
        tableData.value = response.data;
    } catch (error) {
        // 错误已在拦截器中处理
    }
};

const handleAdd = () => {
    // 打开新增对话框
};

const handleEdit = (row) => {
    // 打开编辑对话框
};

const handleDelete = async (row) => {
    try {
        await deleteConfirm(`确定要删除 ${row.name} 吗？`);
        await del(`/data/${row.id}`);
        success('删除成功');
        loadData();
    } catch (error) {
        // 用户取消或错误已在拦截器中处理
    }
};

onMounted(() => {
    loadData();
});
</script>
```

