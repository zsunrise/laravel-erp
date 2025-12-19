import api from '../services/api';
import { useLoading } from './useLoading';
import { useMessage, useErrorHandler } from '../utils/message';

/**
 * 统一 API 调用 composable
 * 集成加载状态、错误处理和消息提示
 */
export const useApi = () => {
    const { loading, withLoading } = useLoading();
    const { success, error } = useMessage();
    const { handleError, handleValidationError } = useErrorHandler();

    const get = async (url, config = {}) => {
        return await withLoading(async () => {
            try {
                const response = await api.get(url, config);
                return response.data;
            } catch (err) {
                throw err;
            }
        });
    };

    const post = async (url, data = {}, config = {}) => {
        return await withLoading(async () => {
            try {
                const response = await api.post(url, data, config);
                return response.data;
            } catch (err) {
                throw err;
            }
        });
    };

    const put = async (url, data = {}, config = {}) => {
        return await withLoading(async () => {
            try {
                const response = await api.put(url, data, config);
                return response.data;
            } catch (err) {
                throw err;
            }
        });
    };

    const del = async (url, config = {}) => {
        return await withLoading(async () => {
            try {
                const response = await api.delete(url, config);
                return response.data;
            } catch (err) {
                throw err;
            }
        });
    };

    const request = async (config) => {
        return await withLoading(async () => {
            try {
                const response = await api.request(config);
                return response.data;
            } catch (err) {
                throw err;
            }
        });
    };

    return {
        loading,
        get,
        post,
        put,
        delete: del,
        request,
        success,
        error,
        handleError,
        handleValidationError
    };
};

