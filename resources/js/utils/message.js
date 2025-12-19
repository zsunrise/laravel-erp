import { ElMessage, ElMessageBox } from 'element-plus';

/**
 * 统一消息提示工具
 */
export const useMessage = () => {
    const success = (message = '操作成功', duration = 3000) => {
        ElMessage({
            message,
            type: 'success',
            duration,
            showClose: true,
            grouping: true
        });
    };

    const error = (message = '操作失败', duration = 3000) => {
        ElMessage({
            message,
            type: 'error',
            duration,
            showClose: true,
            grouping: true
        });
    };

    const warning = (message = '警告', duration = 3000) => {
        ElMessage({
            message,
            type: 'warning',
            duration,
            showClose: true,
            grouping: true
        });
    };

    const info = (message = '提示', duration = 3000) => {
        ElMessage({
            message,
            type: 'info',
            duration,
            showClose: true,
            grouping: true
        });
    };

    return {
        success,
        error,
        warning,
        info
    };
};

/**
 * 统一确认对话框工具
 */
export const useConfirm = () => {
    const confirm = (message, title = '提示', options = {}) => {
        return ElMessageBox.confirm(message, title, {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning',
            ...options
        });
    };

    const deleteConfirm = (message = '确定要删除吗？', title = '删除确认') => {
        return confirm(message, title, {
            type: 'warning',
            confirmButtonText: '删除',
            confirmButtonClass: 'el-button--danger'
        });
    };

    const submitConfirm = (message = '确定要提交吗？', title = '提交确认') => {
        return confirm(message, title, {
            type: 'info'
        });
    };

    return {
        confirm,
        deleteConfirm,
        submitConfirm
    };
};

import { ElMessage } from 'element-plus';

/**
 * 统一错误处理工具
 */
export const useErrorHandler = () => {
    const handleError = (error, defaultMessage = '操作失败') => {
        let message = defaultMessage;

        if (error?.response?.data?.message) {
            message = error.response.data.message;
        } else if (error?.message) {
            message = error.message;
        } else if (typeof error === 'string') {
            message = error;
        }

        ElMessage({
            message,
            type: 'error',
            duration: 3000,
            showClose: true,
            grouping: true
        });

        return message;
    };

    const handleValidationError = (error) => {
        if (error?.response?.data?.errors) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0];
            if (Array.isArray(firstError) && firstError.length > 0) {
                return firstError[0];
            }
        }
        return error?.response?.data?.message || '数据验证失败';
    };

    return {
        handleError,
        handleValidationError
    };
};

