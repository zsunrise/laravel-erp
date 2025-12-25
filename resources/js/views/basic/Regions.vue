<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <div class="header-left">
                    <MapPin :size="24" class="header-icon" />
                    <h2 class="page-title text-primary">地区管理</h2>
                </div>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增地区
                    </el-button>
                </div>
            </div>

            <div class="content-wrapper">
                <el-row :gutter="24">
                    <el-col :span="12">
                        <div class="tree-container">
                            <div class="tree-header">
                                <span class="tree-title">
                                    <Search :size="16" style="margin-right: 8px;" />
                                    地区树
                                </span>
                                <div class="header-actions">
                                    <el-input
                                        v-model="searchKeyword"
                                        placeholder="搜索地区..."
                                        size="small"
                                        clearable
                                        style="width: 200px; margin-right: 8px;"
                                        @input="handleSearch"
                                    >
                                        <template #prefix>
                                            <Search :size="14" />
                                        </template>
                                    </el-input>
                                    <el-tag v-if="treeData.length > 0" type="info" size="small">
                                        共 {{ getTotalCount(treeData) }} 项
                                    </el-tag>
                                </div>
                            </div>
                            <div class="tree-content" v-loading="loading">
                                <el-tree
                                    v-if="filteredTreeData.length > 0"
                                    ref="treeRef"
                                    :data="filteredTreeData"
                                    :props="{ children: 'children', label: 'name' }"
                                    node-key="id"
                                    default-expand-all
                                    :expand-on-click-node="false"
                                    @node-click="handleNodeClick"
                                    class="modern-tree"
                                >
                                    <template #default="{ node, data }">
                                        <span class="tree-node">
                                            <span class="tree-node-label">
                                                <MapPin :size="14" class="node-icon" />
                                                <span>{{ node.label }}</span>
                                                <el-tag :type="getLevelTagType(data.level)" size="small" effect="plain" class="level-tag">
                                                    {{ getLevelText(data.level) }}
                                                </el-tag>
                                            </span>
                                            <span class="tree-node-actions">
                                                <el-button type="primary" link size="small" @click.stop="handleAddChild(data)" v-if="data.level < 3" class="action-btn">
                                                    添加子地区
                                                </el-button>
                                                <el-button type="warning" link size="small" @click.stop="handleEdit(data)" class="action-btn">
                                                    编辑
                                                </el-button>
                                                <el-button type="danger" link size="small" @click.stop="handleDelete(data)" class="action-btn">
                                                    删除
                                                </el-button>
                                            </span>
                                        </span>
                                    </template>
                                </el-tree>
                                <el-empty v-else-if="treeData.length === 0" description="暂无地区数据" :image-size="120">
                                    <el-button type="primary" @click="handleAdd">创建第一个地区</el-button>
                                </el-empty>
                                <el-empty v-else description="未找到匹配的地区" :image-size="100" />
                            </div>
                        </div>
                    </el-col>
                    <el-col :span="12">
                        <div class="detail-container">
                            <div class="detail-header">
                                <span class="detail-title">地区详情</span>
                            </div>
                            <div class="detail-content" v-if="currentRegion">
                                <div class="detail-icon-wrapper">
                                    <div class="detail-icon">
                                        <MapPin :size="32" />
                                    </div>
                                </div>
                                <el-descriptions :column="1" border class="modern-descriptions">
                                    <el-descriptions-item label="地区名称">
                                        <span class="detail-value highlight">{{ currentRegion.name }}</span>
                                    </el-descriptions-item>
                                    <el-descriptions-item label="地区编码">
                                        <el-tag type="info" size="small" effect="plain">{{ currentRegion.code }}</el-tag>
                                    </el-descriptions-item>
                                    <el-descriptions-item label="级别">
                                        <el-tag :type="getLevelTagType(currentRegion.level)" effect="dark" round>
                                            {{ getLevelText(currentRegion.level) }}
                                        </el-tag>
                                    </el-descriptions-item>
                                    <el-descriptions-item label="父地区">
                                        <span class="detail-value">{{ currentRegion.parent?.name || '无' }}</span>
                                    </el-descriptions-item>
                                    <el-descriptions-item label="排序">
                                        <span class="detail-value">{{ currentRegion.sort }}</span>
                                    </el-descriptions-item>
                                    <el-descriptions-item label="状态">
                                        <el-tag :type="currentRegion.is_active ? 'success' : 'danger'" effect="dark" round>
                                            {{ currentRegion.is_active ? '启用' : '禁用' }}
                                        </el-tag>
                                    </el-descriptions-item>
                                </el-descriptions>
                            </div>
                            <el-empty v-else description="请选择一个地区查看详情" :image-size="100">
                                <template #image>
                                    <MapPin :size="80" style="opacity: 0.3;" />
                                </template>
                            </el-empty>
                        </div>
                    </el-col>
                </el-row>
            </div>
        </div>

        <!-- 地区表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
            width="600px"
            class="modern-dialog"
            @close="handleDialogClose"
            align-center
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="100px"
            >
                <el-form-item label="父地区" prop="parent_id">
                    <el-tree-select
                        v-model="form.parent_id"
                        :data="treeData"
                        :props="{ children: 'children', label: 'name', value: 'id' }"
                        placeholder="请选择父地区（可选）"
                        check-strictly
                        clearable
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="地区名称" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>
                <el-form-item label="地区编码" prop="code">
                    <el-input v-model="form.code" />
                </el-form-item>
                <el-form-item label="级别" prop="level">
                    <el-select v-model="form.level" placeholder="请选择级别" style="width: 100%">
                        <el-option label="省/直辖市" :value="1" />
                        <el-option label="市" :value="2" />
                        <el-option label="区/县" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="form.sort" :min="0" style="width: 100%" />
                </el-form-item>
                <el-form-item label="状态" prop="is_active">
                    <el-switch v-model="form.is_active" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit" :loading="submitLoading">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, MapPin, Search } from 'lucide-vue-next';
import api from '../../services/api';

const treeRef = ref(null);
const formRef = ref(null);
const treeData = ref([]);
const currentRegion = ref(null);
const dialogVisible = ref(false);
const dialogTitle = ref('新增地区');
const submitLoading = ref(false);
const loading = ref(false);
const searchKeyword = ref('');

const form = reactive({
    id: null,
    parent_id: null,
    name: '',
    code: '',
    level: 1,
    sort: 0,
    is_active: true
});

const rules = {
    name: [{ required: true, message: '请输入地区名称', trigger: 'blur' }],
    code: [{ required: true, message: '请输入地区编码', trigger: 'blur' }],
    level: [{ required: true, message: '请选择级别', trigger: 'change' }]
};

const getLevelText = (level) => {
    const levelMap = {
        1: '省/直辖市',
        2: '市',
        3: '区/县'
    };
    return levelMap[level] || '未知';
};

const getLevelTagType = (level) => {
    const typeMap = {
        1: 'danger',
        2: 'warning',
        3: 'info'
    };
    return typeMap[level] || 'info';
};

const getTotalCount = (data) => {
    let count = 0;
    const countNodes = (nodes) => {
        nodes.forEach(node => {
            count++;
            if (node.children && node.children.length > 0) {
                countNodes(node.children);
            }
        });
    };
    countNodes(data);
    return count;
};

const filterTreeData = (data, keyword) => {
    if (!keyword || !data || !Array.isArray(data)) return data || [];
    
    const filterNodes = (nodes) => {
        if (!nodes || !Array.isArray(nodes)) return [];
        
        const filtered = [];
        nodes.forEach(node => {
            if (!node) return;
            
            const name = (node.name || '').toLowerCase();
            const code = (node.code || '').toLowerCase();
            const keywordLower = keyword.toLowerCase();
            
            const matches = name.includes(keywordLower) || code.includes(keywordLower);
            const children = Array.isArray(node.children) && node.children.length > 0 
                ? filterNodes(node.children) 
                : [];
            
            if (matches || children.length > 0) {
                filtered.push({
                    ...node,
                    children: children.length > 0 ? children : (Array.isArray(node.children) ? node.children : [])
                });
            }
        });
        return filtered;
    };
    
    return filterNodes(data);
};

const filteredTreeData = computed(() => {
    if (!treeData.value || !Array.isArray(treeData.value)) {
        return [];
    }
    return filterTreeData(treeData.value, searchKeyword.value);
});

const handleSearch = () => {
    // 搜索时自动展开所有节点
    if (treeRef.value && searchKeyword.value) {
        const expandNode = (node) => {
            treeRef.value.store.nodesMap[node.id]?.expand();
            if (node.children) {
                node.children.forEach(expandNode);
            }
        };
        filteredTreeData.value.forEach(expandNode);
    }
};

const loadTreeData = async () => {
    loading.value = true;
    try {
        const response = await api.get('/regions', { params: { tree: true } });
        // API直接返回数组，不是包装在data字段中
        treeData.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
    } catch (error) {
        ElMessage.error('加载地区数据失败');
        console.error('加载地区数据失败:', error);
    } finally {
        loading.value = false;
    }
};

const handleNodeClick = async (data) => {
    try {
        const response = await api.get(`/regions/${data.id}`);
        currentRegion.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载地区详情失败');
    }
};

const handleAdd = () => {
    dialogTitle.value = '新增地区';
    Object.assign(form, {
        id: null,
        parent_id: null,
        name: '',
        code: '',
        level: 1,
        sort: 0,
        is_active: true
    });
    dialogVisible.value = true;
};

const handleAddChild = (parent) => {
    dialogTitle.value = '新增子地区';
    Object.assign(form, {
        id: null,
        parent_id: parent.id,
        name: '',
        code: '',
        level: parent.level + 1,
        sort: 0,
        is_active: true
    });
    dialogVisible.value = true;
};

const handleEdit = async (row) => {
    try {
        const response = await api.get(`/regions/${row.id}`);
        const region = response.data.data;
        dialogTitle.value = '编辑地区';
        Object.assign(form, {
            id: region.id,
            parent_id: region.parent_id,
            name: region.name,
            code: region.code,
            level: region.level,
            sort: region.sort,
            is_active: region.is_active
        });
        dialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载地区信息失败');
    }
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该地区吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/regions/${row.id}`);
        ElMessage.success('删除成功');
        loadTreeData();
        currentRegion.value = null;
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            submitLoading.value = true;
            try {
                if (form.id) {
                    await api.put(`/regions/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/regions', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadTreeData();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                submitLoading.value = false;
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

onMounted(() => {
    loadTreeData();
});
</script>

<style scoped>
.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    color: var(--color-primary);
}

.content-wrapper {
    padding: 24px;
}

.tree-container,
.detail-container {
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    height: calc(100vh - 200px);
    min-height: 500px;
    display: flex;
    flex-direction: column;
}

.tree-header,
.detail-header {
    padding: 16px 20px;
    background: var(--color-bg-secondary);
    border-bottom: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tree-title,
.detail-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-text-primary);
}

.tree-content,
.detail-content {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.modern-tree {
    background: transparent;
}

.modern-tree :deep(.el-tree-node) {
    margin-bottom: 4px;
}

.modern-tree :deep(.el-tree-node__content) {
    height: 40px;
    border-radius: var(--radius);
    transition: var(--transition);
    padding: 0 8px;
}

.modern-tree :deep(.el-tree-node__content:hover) {
    background-color: var(--color-bg-secondary);
}

.modern-tree :deep(.el-tree-node.is-current > .el-tree-node__content) {
    background-color: var(--color-primary);
    color: white;
}

.modern-tree :deep(.el-tree-node.is-current > .el-tree-node__content .tree-node-label),
.modern-tree :deep(.el-tree-node.is-current > .el-tree-node__content .node-icon) {
    color: white;
}

.modern-tree :deep(.el-tree-node.is-current > .el-tree-node__content .level-tag) {
    background-color: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.tree-node {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 14px;
    padding-right: 8px;
    width: 100%;
}

.tree-node-label {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
}

.node-icon {
    color: var(--color-primary);
    flex-shrink: 0;
}

.level-tag {
    margin-left: 8px;
    font-size: 11px;
    padding: 2px 6px;
}

.tree-node-actions {
    display: flex;
    gap: 4px;
    margin-left: 12px;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity var(--transition);
}

.tree-node:hover .tree-node-actions {
    opacity: 1;
}

.action-btn {
    padding: 4px 8px;
    font-size: 12px;
}

.modern-descriptions {
    background: transparent;
}

.modern-descriptions :deep(.el-descriptions__label) {
    font-weight: 600;
    color: var(--color-text-secondary);
    width: 120px;
}

.modern-descriptions :deep(.el-descriptions__content) {
    color: var(--color-text-primary);
}

.detail-value {
    font-weight: 500;
}

/* 滚动条美化 */
.tree-content::-webkit-scrollbar,
.detail-content::-webkit-scrollbar {
    width: 6px;
}

.tree-content::-webkit-scrollbar-track,
.detail-content::-webkit-scrollbar-track {
    background: var(--color-bg-secondary);
    border-radius: 3px;
}

.tree-content::-webkit-scrollbar-thumb,
.detail-content::-webkit-scrollbar-thumb {
    background: var(--color-border);
    border-radius: 3px;
}

.tree-content::-webkit-scrollbar-thumb:hover,
.detail-content::-webkit-scrollbar-thumb:hover {
    background: var(--color-text-muted);
}

.tree-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-icon-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.detail-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: var(--shadow-md);
}

.detail-value.highlight {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-primary);
}

.modern-dialog :deep(.el-dialog__header) {
    padding: 24px 24px 0;
    border-bottom: 1px solid var(--color-border);
}

.modern-dialog :deep(.el-dialog__title) {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text-primary);
}

.modern-dialog :deep(.el-dialog__body) {
    padding: 24px;
}

.modern-dialog :deep(.el-dialog__footer) {
    padding: 16px 24px 24px;
    border-top: 1px solid var(--color-border);
}

/* 响应式 */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 16px;
    }

    .tree-container,
    .detail-container {
        height: auto;
        min-height: 400px;
    }

    .tree-node-actions {
        opacity: 1;
    }

    .header-actions {
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
    }

    .header-actions .el-input {
        width: 100% !important;
    }
}
</style>

