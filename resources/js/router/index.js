import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

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
                meta: { title: '用户管理', icon: 'User' }
            },
            {
                path: 'roles',
                name: 'Roles',
                component: () => import('../views/system/Roles.vue'),
                meta: { title: '角色管理', icon: 'UserFilled' }
            },
            {
                path: 'products',
                name: 'Products',
                component: () => import('../views/product/Products.vue'),
                meta: { title: '商品管理', icon: 'Goods' }
            },
            {
                path: 'suppliers',
                name: 'Suppliers',
                component: () => import('../views/supplier/Suppliers.vue'),
                meta: { title: '供应商管理', icon: 'Truck' }
            },
            {
                path: 'customers',
                name: 'Customers',
                component: () => import('../views/customer/Customers.vue'),
                meta: { title: '客户管理', icon: 'User' }
            },
            {
                path: 'warehouses',
                name: 'Warehouses',
                component: () => import('../views/warehouse/Warehouses.vue'),
                meta: { title: '仓库管理', icon: 'House' }
            },
            {
                path: 'inventory',
                name: 'Inventory',
                component: () => import('../views/inventory/Inventory.vue'),
                meta: { title: '库存管理', icon: 'Box' }
            },
            {
                path: 'purchase-orders',
                name: 'PurchaseOrders',
                component: () => import('../views/purchase/PurchaseOrders.vue'),
                meta: { title: '采购订单', icon: 'ShoppingCart' }
            },
            {
                path: 'sales-orders',
                name: 'SalesOrders',
                component: () => import('../views/sales/SalesOrders.vue'),
                meta: { title: '销售订单', icon: 'ShoppingBag' }
            },
            {
                path: 'boms',
                name: 'Boms',
                component: () => import('../views/bom/Boms.vue'),
                meta: { title: 'BOM管理', icon: 'Document' }
            },
            {
                path: 'production',
                name: 'Production',
                component: () => import('../views/production/Production.vue'),
                meta: { title: '生产管理', icon: 'Setting' }
            },
            {
                path: 'financial',
                name: 'Financial',
                component: () => import('../views/financial/Financial.vue'),
                meta: { title: '财务管理', icon: 'Money' }
            },
            {
                path: 'reports',
                name: 'Reports',
                component: () => import('../views/reports/Reports.vue'),
                meta: { title: '报表分析', icon: 'DataAnalysis' }
            },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    const isAuthenticated = !!token;
    
    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'Login' });
    } else if (to.name === 'Login' && isAuthenticated) {
        next({ name: 'Dashboard' });
    } else {
        next();
    }
});

export default router;

