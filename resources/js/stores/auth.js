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

    return {
        user,
        token,
        isAuthenticated,
        login,
        logout,
        fetchUser,
        setToken,
        setUser
    };
});

