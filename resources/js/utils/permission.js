import { useAuthStore } from '../stores/auth';

export function hasPermission(permission) {
    const authStore = useAuthStore();
    return authStore.hasPermission(permission);
}

export function hasAnyPermission(permissions) {
    const authStore = useAuthStore();
    return authStore.hasAnyPermission(permissions);
}

export function hasAllPermissions(permissions) {
    const authStore = useAuthStore();
    return authStore.hasAllPermissions(permissions);
}

