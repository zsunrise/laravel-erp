<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">客户管理</h2>
                <div class="page-actions">
                    <el-button type="primary" @click="handleAdd" class="interactive">
                        <Plus :size="16" style="margin-right: 6px;" />
                        新增客户
                    </el-button>
                </div>
            </div>

            <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="客户名称/编码/联系人/电话" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="searchForm.is_active" placeholder="全部" clearable>
                        <el-option label="启用" :value="1" />
                        <el-option label="禁用" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="评级">
                    <el-select v-model="searchForm.rating" placeholder="全部" clearable>
                        <el-option label="A级" value="A" />
                        <el-option label="B级" value="B" />
                        <el-option label="C级" value="C" />
                        <el-option label="D级" value="D" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="customers" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="code" label="编码" width="120" />
                <el-table-column prop="name" label="客户名称" />
                <el-table-column prop="contact_person" label="联系人" width="120" />
                <el-table-column prop="contact_phone" label="联系电话" width="150" />
                <el-table-column prop="region.name" label="地区" width="150" />
                <el-table-column prop="rating" label="评级" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getRatingType(row.rating)">{{ row.rating || '-' }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="credit_limit" label="信用额度" width="120">
                    <template #default="{ row }">
                        {{ row.credit_limit ? `¥${row.credit_limit}` : '-' }}
                    </template>
                </el-table-column>
                <el-table-column prop="payment_days" label="账期(天)" width="100" />
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
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)" class="interactive">编辑</el-button>
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

        <!-- 客户表单对话框 -->
        <el-dialog
            v-model="dialogVisible"
            :title="dialogTitle"
            width="900px"
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
                        <el-form-item label="客户编码" prop="code">
                            <el-input v-model="form.code" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="客户名称" prop="name">
                            <el-input v-model="form.name" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="联系人" prop="contact_person">
                            <el-input v-model="form.contact_person" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="联系电话" prop="contact_phone">
                            <el-input v-model="form.contact_phone" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="邮箱" prop="email">
                            <el-input v-model="form.email" type="email" />
                        </el-form-item>
                    </el-col>
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
                </el-row>
                <el-form-item label="地址" prop="address">
                    <el-input v-model="form.address" />
                </el-form-item>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="税号" prop="tax_number">
                            <el-input v-model="form.tax_number" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="评级" prop="rating">
                            <el-select v-model="form.rating" placeholder="请选择评级" style="width: 100%">
                                <el-option label="A级" value="A" />
                                <el-option label="B级" value="B" />
                                <el-option label="C级" value="C" />
                                <el-option label="D级" value="D" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="银行名称" prop="bank_name">
                            <el-input v-model="form.bank_name" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="银行账号" prop="bank_account">
                            <el-input v-model="form.bank_account" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="信用额度" prop="credit_limit">
                            <el-input-number v-model="form.credit_limit" :precision="2" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="账期(天)" prop="payment_days">
                            <el-input-number v-model="form.payment_days" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="form.remark" type="textarea" :rows="3" />
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
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const dialogVisible = ref(false);
const dialogTitle = ref('新增客户');
const formRef = ref(null);
const customers = ref([]);
const regions = ref([]);

const searchForm = reactive({
    search: '',
    is_active: null,
    rating: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    id: null,
    code: '',
    name: '',
    contact_person: '',
    contact_phone: '',
    email: '',
    region_id: null,
    address: '',
    tax_number: '',
    bank_name: '',
    bank_account: '',
    rating: null,
    credit_limit: null,
    payment_days: null,
    is_active: true,
    remark: ''
});

const rules = {
    code: [{ required: true, message: '请输入客户编码', trigger: 'blur' }],
    name: [{ required: true, message: '请输入客户名称', trigger: 'blur' }],
    email: [{ type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' }]
};

const getRatingType = (rating) => {
    const typeMap = {
        'A': 'success',
        'B': 'primary',
        'C': 'warning',
        'D': 'danger'
    };
    return typeMap[rating] || 'info';
};

const loadCustomers = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/customers', { params });
        customers.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载客户列表失败');
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

const handleSearch = () => {
    pagination.page = 1;
    loadCustomers();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.is_active = null;
    searchForm.rating = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增客户';
    Object.assign(form, {
        id: null,
        code: '',
        name: '',
        contact_person: '',
        contact_phone: '',
        email: '',
        region_id: null,
        address: '',
        tax_number: '',
        bank_name: '',
        bank_account: '',
        rating: null,
        credit_limit: null,
        payment_days: null,
        is_active: true,
        remark: ''
    });
    dialogVisible.value = true;
};

const handleEdit = (row) => {
    dialogTitle.value = '编辑客户';
    Object.assign(form, {
        id: row.id,
        code: row.code,
        name: row.name,
        contact_person: row.contact_person || '',
        contact_phone: row.contact_phone || '',
        email: row.email || '',
        region_id: row.region_id,
        address: row.address || '',
        tax_number: row.tax_number || '',
        bank_name: row.bank_name || '',
        bank_account: row.bank_account || '',
        rating: row.rating,
        credit_limit: row.credit_limit,
        payment_days: row.payment_days,
        is_active: row.is_active,
        remark: row.remark || ''
    });
    dialogVisible.value = true;
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该客户吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/customers/${row.id}`);
        ElMessage.success('删除成功');
        loadCustomers();
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
                    await api.put(`/customers/${form.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/customers', data);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadCustomers();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            }
        }
    });
};

const handleDialogClose = () => {
    formRef.value?.resetFields();
};

const handleSizeChange = () => {
    pagination.page = 1;
    loadCustomers();
};

const handlePageChange = () => {
    loadCustomers();
};

onMounted(() => {
    loadCustomers();
    loadRegions();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

