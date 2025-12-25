import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/Login.vue'),
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: () => import('../layouts/MainLayout.vue'),
        redirect: '/dashboard',
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('../views/Dashboard.vue'),
                meta: { title: '仪表盘', icon: 'Odometer' }
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('../views/system/Users.vue'),
                meta: { title: '用户管理', icon: 'User', permission: 'users.manage' }
            },
            {
                path: 'roles',
                name: 'Roles',
                component: () => import('../views/system/Roles.vue'),
                meta: { title: '角色管理', icon: 'UserFilled', permission: 'roles.manage' }
            },
            {
                path: 'permissions',
                name: 'Permissions',
                component: () => import('../views/system/Permissions.vue'),
                meta: { title: '权限管理', icon: 'Lock', permission: 'permissions.manage' }
            },
            {
                path: 'workflows',
                name: 'Workflows',
                component: () => import('../views/system/Workflows.vue'),
                meta: { title: '审批流程', icon: 'DocumentChecked', permission: 'workflows.manage' }
            },
            {
                path: 'notification-templates',
                name: 'NotificationTemplates',
                component: () => import('../views/system/NotificationTemplates.vue'),
                meta: { title: '消息模板', icon: 'MessageSquare', permission: 'system.config' }
            },
            {
                path: 'operation-logs',
                name: 'OperationLogs',
                component: () => import('../views/system/OperationLogs.vue'),
                meta: { title: '操作日志', icon: 'FileText', permission: 'system.config' }
            },
            {
                path: 'notifications',
                name: 'Notifications',
                component: () => import('../views/system/Notifications.vue'),
                meta: { title: '消息通知', icon: 'Bell' }
            },
            {
                path: 'products',
                name: 'Products',
                component: () => import('../views/product/Products.vue'),
                meta: { title: '商品管理', icon: 'Goods', permission: 'products.manage' }
            },
            {
                path: 'product-categories',
                name: 'ProductCategories',
                component: () => import('../views/basic/ProductCategories.vue'),
                meta: { title: '商品分类', icon: 'Folder', permission: 'product-categories.manage' }
            },
            {
                path: 'units',
                name: 'Units',
                component: () => import('../views/basic/Units.vue'),
                meta: { title: '计量单位', icon: 'Scale' }
            },
            {
                path: 'currencies',
                name: 'Currencies',
                component: () => import('../views/basic/Currencies.vue'),
                meta: { title: '币种管理', icon: 'Money' }
            },
            {
                path: 'regions',
                name: 'Regions',
                component: () => import('../views/basic/Regions.vue'),
                meta: { title: '地区管理', icon: 'Location' }
            },
            {
                path: 'data-dictionaries',
                name: 'DataDictionaries',
                component: () => import('../views/basic/DataDictionaries.vue'),
                meta: { title: '数据字典', icon: 'Document', permission: 'system.config' }
            },
            {
                path: 'suppliers',
                name: 'Suppliers',
                component: () => import('../views/supplier/Suppliers.vue'),
                meta: { title: '供应商管理', icon: 'Truck', permission: 'suppliers.manage' }
            },
            {
                path: 'customers',
                name: 'Customers',
                component: () => import('../views/customer/Customers.vue'),
                meta: { title: '客户管理', icon: 'User', permission: 'customers.manage' }
            },
            {
                path: 'warehouses',
                name: 'Warehouses',
                component: () => import('../views/warehouse/Warehouses.vue'),
                meta: { title: '仓库管理', icon: 'House', permission: 'warehouses.manage' }
            },
            {
                path: 'inventory',
                name: 'Inventory',
                component: () => import('../views/inventory/Inventory.vue'),
                meta: { title: '库存管理', icon: 'Box', permission: 'inventory.view' }
            },
            {
                path: 'purchase-orders',
                name: 'PurchaseOrders',
                component: () => import('../views/purchase/PurchaseOrders.vue'),
                meta: { title: '采购订单', icon: 'ShoppingCart', permission: 'purchase-orders.manage' }
            },
            {
                path: 'purchase-returns',
                name: 'PurchaseReturns',
                component: () => import('../views/purchase/PurchaseReturns.vue'),
                meta: { title: '采购退货', icon: 'RefreshLeft', permission: 'purchase-returns.manage' }
            },
            {
                path: 'purchase-settlements',
                name: 'PurchaseSettlements',
                component: () => import('../views/purchase/PurchaseSettlements.vue'),
                meta: { title: '采购结算', icon: 'CreditCard', permission: 'purchase-settlements.manage' }
            },
            {
                path: 'sales-orders',
                name: 'SalesOrders',
                component: () => import('../views/sales/SalesOrders.vue'),
                meta: { title: '销售订单', icon: 'ShoppingBag', permission: 'sales-orders.manage' }
            },
            {
                path: 'sales-returns',
                name: 'SalesReturns',
                component: () => import('../views/sales/SalesReturns.vue'),
                meta: { title: '销售退货', icon: 'RefreshRight', permission: 'sales-returns.manage' }
            },
            {
                path: 'sales-settlements',
                name: 'SalesSettlements',
                component: () => import('../views/sales/SalesSettlements.vue'),
                meta: { title: '销售结算', icon: 'Wallet', permission: 'sales-settlements.manage' }
            },
            {
                path: 'boms',
                name: 'Boms',
                component: () => import('../views/bom/Boms.vue'),
                meta: { title: 'BOM管理', icon: 'Document', permission: 'boms.manage' }
            },
            {
                path: 'production',
                name: 'Production',
                component: () => import('../views/production/Production.vue'),
                meta: { title: '生产管理', icon: 'Setting', permissions: ['production-plans.manage', 'work-orders.manage', 'production-reports.manage'] }
            },
            {
                path: 'financial',
                name: 'Financial',
                component: () => import('../views/financial/Financial.vue'),
                meta: { title: '财务管理', icon: 'Money', permissions: ['general-ledger.view', 'accounts-receivable.manage', 'accounts-payable.manage'] }
            },
            {
                path: 'reports',
                name: 'Reports',
                component: () => import('../views/reports/Reports.vue'),
                meta: { title: '报表分析', icon: 'DataAnalysis', permissions: ['sales-reports.view', 'purchase-reports.view', 'inventory-reports.view', 'financial-reports.view'] }
            },
            {
                path: 'profile',
                name: 'Profile',
                component: () => import('../views/Profile.vue'),
                meta: { title: '个人中心', requiresAuth: true }
            },
            {
                path: 'settings',
                name: 'Settings',
                component: () => import('../views/Settings.vue'),
                meta: { title: '系统设置', requiresAuth: true }
            },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    const token = localStorage.getItem('token');
    const isAuthenticated = !!token;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'Login' });
        return;
    }

    if (to.name === 'Login' && isAuthenticated) {
        next({ name: 'Dashboard' });
        return;
    }

    // 权限检查
    if (to.meta.requiresAuth && isAuthenticated) {
        const { useAuthStore } = await import('../stores/auth');
        const authStore = useAuthStore();

        // 如果用户信息未加载，先加载
        if (!authStore.user) {
            try {
                await authStore.fetchUser();
            } catch (error) {
                next({ name: 'Login' });
                return;
            }
        }

        // 检查路由权限
        if (to.meta.permission) {
            if (!authStore.hasPermission(to.meta.permission)) {
                next({ name: 'Dashboard' });
                return;
            }
        }

        if (to.meta.permissions && Array.isArray(to.meta.permissions)) {
            if (!authStore.hasAnyPermission(to.meta.permissions)) {
                next({ name: 'Dashboard' });
                return;
            }
        }
    }

    next();
});

export default router;

