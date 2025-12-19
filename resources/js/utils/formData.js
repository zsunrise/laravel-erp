/**
 * 表单数据工具函数
 * 用于清理表单数据，将空字符串转换为 null
 */

/**
 * 清理表单数据，将空字符串和空值转换为 null
 * @param {Object} data - 原始表单数据
 * @param {Array} nullableFields - 可选字段列表，这些字段的空值会被转换为 null
 * @returns {Object} 清理后的数据
 */
export function cleanFormData(data, nullableFields = []) {
    const cleaned = { ...data };
    
    // 如果没有指定字段列表，则处理所有字段
    const fieldsToClean = nullableFields.length > 0 ? nullableFields : Object.keys(cleaned);
    
    fieldsToClean.forEach(field => {
        if (cleaned[field] === '' || cleaned[field] === undefined) {
            cleaned[field] = null;
        }
    });
    
    return cleaned;
}

/**
 * 清理嵌套对象中的空值
 * @param {Object} data - 原始数据
 * @returns {Object} 清理后的数据
 */
export function deepCleanFormData(data) {
    if (data === null || data === undefined) {
        return null;
    }
    
    if (Array.isArray(data)) {
        return data.map(item => deepCleanFormData(item));
    }
    
    if (typeof data === 'object') {
        const cleaned = {};
        for (const key in data) {
            const value = data[key];
            if (value === '' || value === undefined) {
                cleaned[key] = null;
            } else if (Array.isArray(value)) {
                cleaned[key] = deepCleanFormData(value);
            } else if (typeof value === 'object' && value !== null) {
                cleaned[key] = deepCleanFormData(value);
            } else {
                cleaned[key] = value;
            }
        }
        return cleaned;
    }
    
    return data;
}

