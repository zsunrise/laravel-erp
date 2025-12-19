import { useAuthStore } from '../stores/auth';

const checkPermission = (el, binding) => {
    const authStore = useAuthStore();
    const { value } = binding;

    if (!value) {
        return true;
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

    return hasPermission;
};

export default {
    mounted(el, binding) {
        const hasPermission = checkPermission(el, binding);
        if (!hasPermission) {
            el.style.display = 'none';
            el._permissionHidden = true;
        }
    },
    updated(el, binding) {
        const hasPermission = checkPermission(el, binding);
        if (!hasPermission) {
            if (!el._permissionHidden) {
                el.style.display = 'none';
                el._permissionHidden = true;
            }
        } else {
            if (el._permissionHidden) {
                el.style.display = '';
                el._permissionHidden = false;
            }
        }
    }
};

