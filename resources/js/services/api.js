import axios from 'axios';
import { ElMessage } from 'element-plus';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// 请求拦截器
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// 响应拦截器
api.interceptors.response.use(
    response => {
        return response;
    },
    error => {
        if (error.response) {
            const { status, data } = error.response;
            
            if (status === 401) {
                localStorage.removeItem('token');
                window.location.href = '/login';
                return Promise.reject(error);
            }
            
            if (status === 403) {
                ElMessage({
                    message: data?.message || '没有权限执行此操作',
                    type: 'error',
                    duration: 3000,
                    showClose: true,
                    grouping: true
                });
                return Promise.reject(error);
            }
            
            if (status === 404) {
                ElMessage({
                    message: data?.message || '资源不存在',
                    type: 'error',
                    duration: 3000,
                    showClose: true,
                    grouping: true
                });
                return Promise.reject(error);
            }
            
            if (status === 422) {
                // 验证错误，不在这里显示，由调用方处理
                return Promise.reject(error);
            }
            
            if (status >= 500) {
                ElMessage({
                    message: data?.message || '服务器内部错误，请稍后重试',
                    type: 'error',
                    duration: 3000,
                    showClose: true,
                    grouping: true
                });
                return Promise.reject(error);
            }
            
            ElMessage({
                message: data?.message || '请求失败',
                type: 'error',
                duration: 3000,
                showClose: true,
                grouping: true
            });
        } else if (error.request) {
            ElMessage({
                message: '网络错误，请检查网络连接',
                type: 'error',
                duration: 3000,
                showClose: true,
                grouping: true
            });
        } else {
            ElMessage({
                message: '请求配置错误',
                type: 'error',
                duration: 3000,
                showClose: true,
                grouping: true
            });
        }
        
        return Promise.reject(error);
    }
);

export default api;

