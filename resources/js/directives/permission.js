import { useAuthStore } from '../stores/auth';

export default {
    mounted(el, binding) {
        const authStore = useAuthStore();
        const { value } = binding;
        
        if (!value) {
            return;
        }

        let hasPermission = false;
        
        if (Array.isArray(value)) {
            if (value.length == 0) {
                hasPermission = true;
            } else {
                hasPermission = value.some(perm => authStore.hasPermission(perm));
            }
        } else if (typeof value == 'string') {
            hasPermission = authStore.hasPermission(value);
        } else if (typeof value == 'object') {
            if (value.any) {
                hasPermission = authStore.hasAnyPermission(value.any);
            } else if (value.all) {
                hasPermission = authStore.hasAllPermissions(value.all);
            } else if (value.permission) {
                hasPermission = authStore.hasPermission(value.permission);
            }
        }

        if (!hasPermission) {
            el.style.display = 'none';
        }
    },
    updated(el, binding) {
        const authStore = useAuthStore();
        const { value } = binding;
        
        if (!value) {
            el.style.display = '';
            return;
        }

        let hasPermission = false;
        
        if (Array.isArray(value)) {
            if (value.length == 0) {
                hasPermission = true;
            } else {
                hasPermission = value.some(perm => authStore.hasPermission(perm));
            }
        } else if (typeof value == 'string') {
            hasPermission = authStore.hasPermission(value);
        } else if (typeof value == 'object') {
            if (value.any) {
                hasPermission = authStore.hasAnyPermission(value.any);
            } else if (value.all) {
                hasPermission = authStore.hasAllPermissions(value.all);
            } else if (value.permission) {
                hasPermission = authStore.hasPermission(value.permission);
            }
        }

        if (!hasPermission) {
            el.style.display = 'none';
        } else {
            el.style.display = '';
        }
    }
};

