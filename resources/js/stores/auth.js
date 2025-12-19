import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('token') || null);

    const isAuthenticated = computed(() => !!token.value);

    function setToken(newToken) {
        token.value = newToken;
        if (newToken) {
            localStorage.setItem('token', newToken);
        } else {
            localStorage.removeItem('token');
        }
    }

    function setUser(userData) {
        user.value = userData;
    }

    async function login(credentials) {
        try {
            const response = await api.post('/login', credentials);
            setToken(response.data.token);
            setUser(response.data.user);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    async function logout() {
        try {
            await api.post('/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            setToken(null);
            setUser(null);
        }
    }

    async function fetchUser() {
        try {
            const response = await api.get('/me');
            setUser(response.data);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    function hasPermission(permission) {
        if (!user.value) return false;
        if (!user.value.permissions) return false;
        return user.value.permissions.some(p => p.slug == permission);
    }

    function hasAnyPermission(permissions) {
        if (!Array.isArray(permissions)) return false;
        return permissions.some(permission => hasPermission(permission));
    }

    function hasAllPermissions(permissions) {
        if (!Array.isArray(permissions)) return false;
        return permissions.every(permission => hasPermission(permission));
    }

    function hasRole(role) {
        if (!user.value) return false;
        if (!user.value.roles) return false;
        if (typeof role == 'string') {
            return user.value.roles.some(r => r.slug == role);
        }
        return user.value.roles.some(r => r.id == role);
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        logout,
        fetchUser,
        setToken,
        setUser,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole
    };
});

