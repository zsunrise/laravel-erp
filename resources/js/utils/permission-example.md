# 权限控制使用说明

## 1. 在组件中使用权限检查

### 使用 authStore
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
</script>
```

### 使用工具函数
```vue
<script setup>
import { hasPermission, hasAnyPermission, hasAllPermissions } from '../utils/permission';

// 检查单个权限
if (hasPermission('users.manage')) {
    // 执行操作
}
</script>
```

## 2. 使用 v-permission 指令控制按钮显示

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
    
    <!-- 在表格操作列中使用 -->
    <el-table-column label="操作">
        <template #default="{ row }">
            <el-button v-permission="'users.manage'" @click="handleEdit(row)">编辑</el-button>
            <el-button v-permission="'users.manage'" @click="handleDelete(row)">删除</el-button>
        </template>
    </el-table-column>
</template>
```

## 3. 在模板中使用 v-if

```vue
<template>
    <el-button v-if="authStore.hasPermission('users.manage')">新增用户</el-button>
    
    <el-button v-if="authStore.hasAnyPermission(['users.manage', 'roles.manage'])">管理</el-button>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
const authStore = useAuthStore();
</script>
```

## 4. 路由权限

路由权限在 `router/index.js` 中通过 `meta.permission` 或 `meta.permissions` 配置：

```javascript
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

## 5. 菜单权限

菜单权限在 `MainLayout.vue` 中通过 `v-if` 和权限检查函数控制显示。

