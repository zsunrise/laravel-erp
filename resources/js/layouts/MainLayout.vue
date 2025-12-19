<template>
    <el-container class="main-container">
        <el-aside :width="isCollapse ? '64px' : '200px'" class="sidebar">
            <div class="logo">
                <span v-if="!isCollapse">ERP系统</span>
                <span v-else>ERP</span>
            </div>
            <el-menu
                :default-active="activeMenu"
                :collapse="isCollapse"
                router
                background-color="#304156"
                text-color="#bfcbd9"
                active-text-color="#409EFF"
            >
                <el-menu-item index="/dashboard">
                    <el-icon><Odometer /></el-icon>
                    <template #title>仪表盘</template>
                </el-menu-item>
                
                <el-sub-menu index="system" v-if="hasMenuPermission('users.manage') || hasMenuPermission('roles.manage') || hasMenuPermission('workflows.manage')">
                    <template #title>
                        <el-icon><Setting /></el-icon>
                        <span>系统管理</span>
                    </template>
                    <el-menu-item index="/users" v-if="hasMenuPermission('users.manage')">用户管理</el-menu-item>
                    <el-menu-item index="/roles" v-if="hasMenuPermission('roles.manage')">角色管理</el-menu-item>
                    <el-menu-item index="/workflows" v-if="hasMenuPermission('workflows.manage')">审批流程</el-menu-item>
                </el-sub-menu>

                <el-menu-item index="/products">
                    <el-icon><Goods /></el-icon>
                    <template #title>商品管理</template>
                </el-menu-item>

                <el-sub-menu index="partner">
                    <template #title>
                        <el-icon><UserFilled /></el-icon>
                        <span>合作伙伴</span>
                    </template>
                    <el-menu-item index="/suppliers">供应商管理</el-menu-item>
                    <el-menu-item index="/customers">客户管理</el-menu-item>
                </el-sub-menu>

                <el-menu-item index="/warehouses">
                    <el-icon><House /></el-icon>
                    <template #title>仓库管理</template>
                </el-menu-item>

                <el-menu-item index="/inventory">
                    <el-icon><Box /></el-icon>
                    <template #title>库存管理</template>
                </el-menu-item>

                <el-sub-menu index="purchase">
                    <template #title>
                        <el-icon><ShoppingCart /></el-icon>
                        <span>采购管理</span>
                    </template>
                    <el-menu-item index="/purchase-orders">采购订单</el-menu-item>
                    <el-menu-item index="/purchase-returns">采购退货</el-menu-item>
                    <el-menu-item index="/purchase-settlements">采购结算</el-menu-item>
                </el-sub-menu>

                <el-sub-menu index="sales">
                    <template #title>
                        <el-icon><ShoppingBag /></el-icon>
                        <span>销售管理</span>
                    </template>
                    <el-menu-item index="/sales-orders">销售订单</el-menu-item>
                    <el-menu-item index="/sales-returns">销售退货</el-menu-item>
                    <el-menu-item index="/sales-settlements">销售结算</el-menu-item>
                </el-sub-menu>

                <el-menu-item index="/boms">
                    <el-icon><Document /></el-icon>
                    <template #title>BOM管理</template>
                </el-menu-item>

                <el-menu-item index="/production">
                    <el-icon><Setting /></el-icon>
                    <template #title>生产管理</template>
                </el-menu-item>

                <el-menu-item index="/financial">
                    <el-icon><Money /></el-icon>
                    <template #title>财务管理</template>
                </el-menu-item>

                <el-menu-item index="/reports">
                    <el-icon><DataAnalysis /></el-icon>
                    <template #title>报表分析</template>
                </el-menu-item>
            </el-menu>
        </el-aside>

        <el-container>
            <el-header class="header">
                <div class="header-left">
                    <el-icon class="collapse-icon" @click="toggleCollapse">
                        <Expand v-if="isCollapse" />
                        <Fold v-else />
                    </el-icon>
                </div>
                <div class="header-right">
                    <el-badge :value="unreadCount" class="notification-badge">
                        <el-icon class="icon"><Bell /></el-icon>
                    </el-badge>
                    <el-dropdown @command="handleCommand">
                        <span class="user-info">
                            <el-avatar :size="32" :src="user?.avatar">{{ user?.name?.charAt(0) }}</el-avatar>
                            <span class="username">{{ user?.name }}</span>
                            <el-icon><ArrowDown /></el-icon>
                        </span>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item command="profile">个人中心</el-dropdown-item>
                                <el-dropdown-item command="settings">系统设置</el-dropdown-item>
                                <el-dropdown-item divided command="logout">退出登录</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </el-header>

            <el-main class="main-content">
                <router-view />
            </el-main>
        </el-container>
    </el-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useConfirm } from '../utils/message';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { confirm } = useConfirm();

const isCollapse = ref(false);
const unreadCount = ref(0);

const user = computed(() => authStore.user);
const activeMenu = computed(() => route.path);

const toggleCollapse = () => {
    isCollapse.value = !isCollapse.value;
};

const hasMenuPermission = (permission) => {
    if (!permission) return true;
    return authStore.hasPermission(permission);
};

const handleCommand = async (command) => {
    if (command === 'logout') {
        try {
            await confirm('确定要退出登录吗？', '退出确认');
            await authStore.logout();
            router.push('/login');
        } catch (error) {
            // 用户取消
        }
    } else if (command === 'profile') {
        // 跳转到个人中心
        console.log('个人中心');
    } else if (command === 'settings') {
        // 跳转到系统设置
        console.log('系统设置');
    }
};

onMounted(async () => {
    if (!authStore.user) {
        await authStore.fetchUser();
    }
});
</script>

<style scoped>
.main-container {
    height: 100vh;
}

.sidebar {
    background-color: #304156;
    transition: width 0.3s;
    overflow: hidden;
}

.logo {
    height: 60px;
    line-height: 60px;
    text-align: center;
    color: #fff;
    font-size: 18px;
    font-weight: bold;
    background-color: #2b3a4a;
}

.el-menu {
    border-right: none;
}

.header {
    background-color: #fff;
    border-bottom: 1px solid #e4e7ed;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
}

.header-left {
    display: flex;
    align-items: center;
}

.collapse-icon {
    font-size: 20px;
    cursor: pointer;
    color: #606266;
}

.collapse-icon:hover {
    color: #409EFF;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-badge {
    cursor: pointer;
}

.icon {
    font-size: 20px;
    color: #606266;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.username {
    font-size: 14px;
    color: #606266;
}

.main-content {
    background-color: #f0f2f5;
    padding: 20px;
    overflow-y: auto;
}
</style>

