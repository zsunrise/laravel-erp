<template>
    <el-container class="main-container">
        <!-- 左侧导航栏 - 靛蓝色主题 -->
        <el-aside :width="isCollapse ? '64px' : '240px'" class="sidebar">
            <div class="logo">
                <span v-if="!isCollapse" class="logo-text">ERP系统</span>
                <span v-else class="logo-text-short">ERP</span>
            </div>
            <el-menu
                :default-active="activeMenu"
                :collapse="isCollapse"
                router
                class="sidebar-menu"
                background-color="var(--color-primary)"
                text-color="rgba(255, 255, 255, 0.7)"
                active-text-color="#ffffff"
            >
                <el-menu-item index="/dashboard" class="menu-item">
                    <Gauge :size="20" />
                    <template #title>仪表盘</template>
                </el-menu-item>
                
                <el-sub-menu index="system" v-if="hasMenuPermission('users.manage') || hasMenuPermission('roles.manage') || hasMenuPermission('workflows.manage')">
                    <template #title>
                        <Settings :size="20" />
                        <span>系统管理</span>
                    </template>
                    <el-menu-item index="/users" v-if="hasMenuPermission('users.manage')">用户管理</el-menu-item>
                    <el-menu-item index="/roles" v-if="hasMenuPermission('roles.manage')">角色管理</el-menu-item>
                    <el-menu-item index="/workflows" v-if="hasMenuPermission('workflows.manage')">审批流程</el-menu-item>
                </el-sub-menu>

                <el-menu-item index="/products" class="menu-item">
                    <Package :size="20" />
                    <template #title>商品管理</template>
                </el-menu-item>

                <el-sub-menu index="partner">
                    <template #title>
                        <Users :size="20" />
                        <span>合作伙伴</span>
                    </template>
                    <el-menu-item index="/suppliers">供应商管理</el-menu-item>
                    <el-menu-item index="/customers">客户管理</el-menu-item>
                </el-sub-menu>

                <el-menu-item index="/warehouses" class="menu-item">
                    <Warehouse :size="20" />
                    <template #title>仓库管理</template>
                </el-menu-item>

                <el-menu-item index="/inventory" class="menu-item">
                    <Boxes :size="20" />
                    <template #title>库存管理</template>
                </el-menu-item>

                <el-sub-menu index="purchase">
                    <template #title>
                        <ShoppingCart :size="20" />
                        <span>采购管理</span>
                    </template>
                    <el-menu-item index="/purchase-orders">采购订单</el-menu-item>
                    <el-menu-item index="/purchase-returns">采购退货</el-menu-item>
                    <el-menu-item index="/purchase-settlements">采购结算</el-menu-item>
                </el-sub-menu>

                <el-sub-menu index="sales">
                    <template #title>
                        <ShoppingBag :size="20" />
                        <span>销售管理</span>
                    </template>
                    <el-menu-item index="/sales-orders">销售订单</el-menu-item>
                    <el-menu-item index="/sales-returns">销售退货</el-menu-item>
                    <el-menu-item index="/sales-settlements">销售结算</el-menu-item>
                </el-sub-menu>

                <el-menu-item index="/boms" class="menu-item">
                    <FileText :size="20" />
                    <template #title>BOM管理</template>
                </el-menu-item>

                <el-menu-item index="/production" class="menu-item">
                    <Cog :size="20" />
                    <template #title>生产管理</template>
                </el-menu-item>

                <el-menu-item index="/financial" class="menu-item">
                    <DollarSign :size="20" />
                    <template #title>财务管理</template>
                </el-menu-item>

                <el-menu-item index="/reports" class="menu-item">
                    <BarChart3 :size="20" />
                    <template #title>报表分析</template>
                </el-menu-item>
            </el-menu>
        </el-aside>

        <el-container>
            <!-- 顶部头部 - 包含搜索栏和用户信息 -->
            <el-header class="header">
                <div class="header-left">
                    <Menu 
                        v-if="isCollapse"
                        :size="20" 
                        class="collapse-icon interactive"
                        @click="toggleCollapse"
                    />
                    <X 
                        v-else
                        :size="20" 
                        class="collapse-icon interactive"
                        @click="toggleCollapse"
                    />
                    <el-input
                        v-model="searchQuery"
                        placeholder="全局搜索..."
                        class="global-search"
                        clearable
                    >
                        <template #prefix>
                            <Search :size="18" />
                        </template>
                    </el-input>
                </div>
                <div class="header-right">
                    <el-badge :value="unreadCount" class="notification-badge interactive">
                        <Bell :size="20" class="header-icon" />
                    </el-badge>
                    <el-dropdown @command="handleCommand">
                        <span class="user-info interactive">
                            <el-avatar :size="32" :src="user?.avatar">{{ user?.name?.charAt(0) }}</el-avatar>
                            <span class="username">{{ user?.name }}</span>
                            <ChevronDown :size="16" />
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

            <!-- 标签页 -->
            <TabsView />

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
import TabsView from '../components/TabsView.vue';
import { 
    Gauge, Settings, Package, Users, Warehouse, Boxes, 
    ShoppingCart, ShoppingBag, FileText, Cog, DollarSign, 
    BarChart3, Menu, X, Search, Bell, ChevronDown 
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { confirm } = useConfirm();

const isCollapse = ref(false);
const unreadCount = ref(0);
const searchQuery = ref('');

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
        router.push('/profile');
    } else if (command === 'settings') {
        router.push('/settings');
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

/* 左侧导航栏 - 靛蓝色主题 */
.sidebar {
    background-color: var(--color-primary);
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    overflow-x: hidden;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    height: 100vh;
    /* Firefox 滚动条样式 */
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) rgba(255, 255, 255, 0.05);
}

/* Webkit 浏览器滚动条样式（Chrome, Edge, Safari） */
.sidebar::-webkit-scrollbar {
    width: 8px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 4px;
    margin: 8px 0;
    transition: background 0.3s ease;
}

.sidebar:hover::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.sidebar::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.2) 0%,
        rgba(255, 255, 255, 0.15) 100%
    );
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    min-height: 40px;
}

.sidebar:hover::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.3) 0%,
        rgba(255, 255, 255, 0.25) 100%
    );
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.45) 0%,
        rgba(255, 255, 255, 0.4) 100%
    );
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.15);
}

.sidebar::-webkit-scrollbar-thumb:active {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.55) 0%,
        rgba(255, 255, 255, 0.5) 100%
    );
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

.logo {
    height: 64px;
    line-height: 64px;
    text-align: center;
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
    background-color: var(--color-primary-light);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-text,
.logo-text-short {
    color: #ffffff;
    letter-spacing: 0.5px;
}

.sidebar-menu {
    border-right: none;
    padding: 8px 0;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar-menu :deep(.el-menu-item),
.sidebar-menu :deep(.el-sub-menu__title) {
    height: 48px;
    line-height: 48px;
    margin: 4px 8px;
    border-radius: var(--radius);
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-menu :deep(.el-menu-item svg),
.sidebar-menu :deep(.el-sub-menu__title svg) {
    color: rgba(255, 255, 255, 0.7);
    transition: var(--transition);
}

.sidebar-menu :deep(.el-menu-item.is-active svg),
.sidebar-menu :deep(.el-sub-menu__title:hover svg) {
    color: #ffffff;
}

.sidebar-menu :deep(.el-menu-item:hover),
.sidebar-menu :deep(.el-sub-menu__title:hover) {
    background-color: rgba(255, 255, 255, 0.08) !important;
}

.sidebar-menu :deep(.el-menu-item.is-active) {
    background-color: rgba(255, 255, 255, 0.12) !important;
    border-left: 3px solid #ffffff !important;
    color: #ffffff !important;
    font-weight: 500;
}

.sidebar-menu :deep(.el-sub-menu .el-menu-item) {
    padding-left: 48px !important;
}

.sidebar-menu :deep(.el-sub-menu .el-menu-item.is-active) {
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-left: 3px solid rgba(255, 255, 255, 0.6) !important;
}

/* 顶部头部 */
.header {
    background-color: var(--color-bg);
    border-bottom: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    height: 64px;
    box-shadow: var(--shadow-sm);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
    max-width: 600px;
}

.collapse-icon {
    color: var(--color-text-secondary);
    transition: var(--transition);
}

.collapse-icon:hover {
    color: var(--color-primary);
}

.global-search {
    flex: 1;
    max-width: 400px;
}

.global-search :deep(.el-input__wrapper) {
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.global-search :deep(.el-input__wrapper:hover) {
    box-shadow: var(--shadow);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-badge {
    cursor: pointer;
    padding: 8px;
    border-radius: var(--radius);
    transition: var(--transition);
}

.notification-badge:hover {
    background-color: var(--color-bg-secondary);
}

.header-icon {
    color: var(--color-text-secondary);
    transition: var(--transition);
}

.notification-badge:hover .header-icon {
    color: var(--color-primary);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: var(--radius);
    transition: var(--transition);
}

.user-info:hover {
    background-color: var(--color-bg-secondary);
}

.username {
    font-size: 14px;
    color: var(--color-text-primary);
    font-weight: 500;
}

.main-content {
    background-color: var(--color-bg-secondary);
    padding: 24px;
    overflow-y: auto;
    /* Firefox 滚动条样式 */
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.2) rgba(0, 0, 0, 0.05);
}

/* 主内容区域滚动条美化 */
.main-content::-webkit-scrollbar {
    width: 10px;
}

.main-content::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.02);
    border-radius: 5px;
    margin: 4px 0;
    transition: background 0.3s ease;
}

.main-content:hover::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.04);
}

.main-content::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0.15) 0%,
        rgba(0, 0, 0, 0.12) 100%
    );
    border-radius: 5px;
    border: 2px solid transparent;
    background-clip: padding-box;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 50px;
}

.main-content:hover::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0.22) 0%,
        rgba(0, 0, 0, 0.18) 100%
    );
    background-clip: padding-box;
}

.main-content::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0.32) 0%,
        rgba(0, 0, 0, 0.28) 100%
    );
    background-clip: padding-box;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.main-content::-webkit-scrollbar-thumb:active {
    background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0.42) 0%,
        rgba(0, 0, 0, 0.38) 100%
    );
    background-clip: padding-box;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
</style>

