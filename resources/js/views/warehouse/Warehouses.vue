<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">仓库管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增仓库
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="仓库名称/编码" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.is_active" placeholder="全部" clearable style="width: 150px">
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="默认仓库">
                    <el-select v-model="searchForm.is_default" placeholder="全部" clearable style="width: 150px">
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="warehouses" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="code" label="编码" width="120" />
                <el-table-column prop="name" label="仓库名称" />
                <el-table-column prop="region.name" label="地区" width="150" />
                <el-table-column prop="address" label="地址" />
                <el-table-column prop="contact_person" label="联系人" width="120" />
                <el-table-column prop="contact_phone" label="联系电话" width="150" />
                <el-table-column prop="is_default" label="默认" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_default" type="success">是</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <span 
                            :class="row.is_active ? 'badge-success' : 'badge-muted'"
                            class="status-badge"
                        >
                            {{ row.is_active ? '启用' : '禁用' }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="250" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)" class="interactive">编辑</el-button>
                        <el-button type="info" size="small" @click="handleManageLocations(row)" class="interactive">库位</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)" class="interactive">删除</el-button>
                    </template>
                </el-table-column>
                </el-table>
            </div>

            <div class="modern-pagination">
                <el-pagination
                    v-model:current-page="pagination.page"
                    v-model:page-size="pagination.per_page"
                    :total="pagination.total"
                    :page-sizes="[10, 20, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="handleSizeChange"
                    @current-change="handlePageChange"
                />
            </div>
        </div>

        <!-- 仓库表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
            width="800px"
            @close="handleDialogClose"
        >
            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="120px"
            >
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="仓库编码" prop="code">
                            <el-input v-model="form.code" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="仓库名称" prop="name">
                            <el-input v-model="form.name" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="地区" prop="region_id">
                            <el-select v-model="form.region_id" filterable placeholder="请选择地区" style="width: 100%">
                                <el-option
                                    v-for="region in regions"
                                    :key="region.id"
                                    :label="region.name"
                                    :value="region.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="联系人" prop="contact_person">
                            <el-input v-model="form.contact_person" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="联系电话" prop="contact_phone">
                            <el-input v-model="form.contact_phone" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="默认仓库" prop="is_default">
                            <el-switch v-model="form.is_default" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="地址" prop="address">
                    <el-input v-model="form.address" />
                </el-form-item>
                <el-form-item label="描述" prop="description">
                    <el-input v-model="form.description" type="textarea" :rows="3" />
                </el-form-item>
                <el-form-item label="状态" prop="is_active">
                    <el-switch v-model="form.is_active" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>

        <!-- 库位管理对话框 -->
        <el-dialog
            v-model="locationDialogVisible"
            title="库位管理"
            width="900px"
        >
            <div style="margin-bottom: 15px;">
                <el-button type="primary" size="small" @click="handleAddLocation">新增库位</el-button>
            </div>
            <el-table :data="locations" v-loading="locationLoading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="code" label="库位编码" width="150" />
                <el-table-column prop="name" label="库位名称" />
                <el-table-column prop="sort" label="排序" width="100" />
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'danger'">
                            {{ row.is_active ? '启用' : '禁用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEditLocation(row)">编辑</el-button>
                        <el-button type="danger" size="small" @click="handleDeleteLocation(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 库位表单对话框 -->
            <el-dialog
                v-model="locationFormVisible"
                :title="locationFormTitle"
                width="600px"
                append-to-body
            >
                <el-form
                    ref="locationFormRef"
                    :model="locationForm"
                    :rules="locationRules"
                    label-width="100px"
                >
                    <el-form-item label="库位编码" prop="code">
                        <el-input v-model="locationForm.code" />
                    </el-form-item>
                    <el-form-item label="库位名称" prop="name">
                        <el-input v-model="locationForm.name" />
                    </el-form-item>
                    <el-form-item label="排序" prop="sort">
                        <el-input-number v-model="locationForm.sort" :min="0" style="width: 100%" />
                    </el-form-item>
                    <el-form-item label="状态" prop="is_active">
                        <el-switch v-model="locationForm.is_active" />
                    </el-form-item>
                </el-form>
                <template #footer>
                    <el-button @click="locationFormVisible = false">取消</el-button>
                    <el-button type="primary" @click="handleSubmitLocation">确定</el-button>
                </template>
            </el-dialog>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const locationLoading = ref(false);
const dialogVisible = ref(false);
const locationDialogVisible = ref(false);
const locationFormVisible = ref(false);
const dialogTitle = ref('新增仓库');
const locationFormTitle = ref('新增库位');
const formRef = ref(null);
const locationFormRef = ref(null);
const warehouses = ref([]);
const regions = ref([]);
const locations = ref([]);
const currentWarehouse = ref(null);

const searchForm = reactive({
    search: '',
    is_active: null,
    is_default: null
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const form = reactive({
    id: null,
    code: '',
    name: '',
    region_id: null,
    address: '',
    contact_person: '',
    contact_phone: '',
    is_default: false,
    is_active: true,
    description: ''
});

const locationForm = reactive({
    id: null,
    warehouse_id: null,
    code: '',
    name: '',
    sort: 0,
    is_active: true
});

const rules = {
    code: [{ required: true, message: '请输入仓库编码', trigger: 'blur' }],
    name: [{ required: true, message: '请输入仓库名称', trigger: 'blur' }]
};

const locationRules = {
    code: [{ required: true, message: '请输入库位编码', trigger: 'blur' }],
    name: [{ required: true, message: '请输入库位名称', trigger: 'blur' }]
};

const loadWarehouses = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page
        };
        // 只添加非空值参数
        if (searchForm.search) {
            params.search = searchForm.search;
        }
        if (searchForm.is_active !== null && searchForm.is_active !== undefined) {
            params.is_active = searchForm.is_active;
        }
        if (searchForm.is_default !== null && searchForm.is_default !== undefined) {
            params.is_default = searchForm.is_default;
        }
        const response = await api.get('/warehouses', { params });
        warehouses.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载仓库列表失败');
    } finally {
        loading.value = false;
    }
};

const loadRegions = async () => {
    try {
        const response = await api.get('/regions', { params: { per_page: 1000 } });
        regions.value = response.data.data;
    } catch (error) {
        console.error('加载地区列表失败:', error);
    }
};

const loadLocations = async (warehouseId) => {
    locationLoading.value = true;
    try {
        const response = await api.get('/warehouse-locations', {
            params: { warehouse_id: warehouseId, per_page: 1000 }
        });
        locations.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载库位列表失败');
    } finally {
        locationLoading.value = false;
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadWarehouses();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.is_active = null;
    searchForm.is_default = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增仓库';
    Object.assign(form, {
        id: null,
        code: '',
        name: '',
        region_id: null,
        address: '',
        contact_person: '',
        contact_phone: '',
        is_default: false,
        is_active: true,
        description: ''
    });
    dialogVisible.value = true;
};

const handleEdit = (row) => {
    dialogTitle.value = '编辑仓库';
    Object.assign(form, {
        id: row.id,
        code: row.code,
        name: row.name,
        region_id: row.region_id,
        address: row.address || '',
        contact_person: row.contact_person || '',
        contact_phone: row.contact_phone || '',
        is_default: row.is_default,
        is_active: row.is_active,
        description: row.description || ''
    });
    dialogVisible.value = true;
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该仓库吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/warehouses/${row.id}`);
        ElMessage.success('删除成功');
        loadWarehouses();
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
            try {
                const data = { ...form };
                if (form.id) {
                    await api.put(`/warehouses/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/warehouses', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadWarehouses();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

const handleManageLocations = async (row) => {
    currentWarehouse.value = row;
    locationDialogVisible.value = true;
    await loadLocations(row.id);
};

const handleAddLocation = () => {
    locationFormTitle.value = '新增库位';
    Object.assign(locationForm, {
        id: null,
        warehouse_id: currentWarehouse.value.id,
        code: '',
        name: '',
        sort: 0,
        is_active: true
    });
    locationFormVisible.value = true;
};

const handleEditLocation = (row) => {
    locationFormTitle.value = '编辑库位';
    Object.assign(locationForm, {
        id: row.id,
        warehouse_id: row.warehouse_id,
        code: row.code,
        name: row.name,
        sort: row.sort,
        is_active: row.is_active
    });
    locationFormVisible.value = true;
};

const handleDeleteLocation = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该库位吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/warehouse-locations/${row.id}`);
        ElMessage.success('删除成功');
        await loadLocations(currentWarehouse.value.id);
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleSubmitLocation = async () => {
    if (!locationFormRef.value) return;
    
    await locationFormRef.value.validate(async (valid) => {
        if (valid) {
            try {
                const data = { ...locationForm };
                if (locationForm.id) {
                    await api.put(`/warehouse-locations/${locationForm.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/warehouse-locations', data);
                    ElMessage.success('创建成功');
                }
                locationFormVisible.value = false;
                await loadLocations(currentWarehouse.value.id);
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            }
        }
    });
};

const handleSizeChange = () => {
    pagination.page = 1;
    loadWarehouses();
};

const handlePageChange = () => {
    loadWarehouses();
};

onMounted(() => {
    loadWarehouses();
    loadRegions();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

