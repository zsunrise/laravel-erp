<template>
    <div class="products-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>商品管理</span>
                    <el-button type="primary" @click="handleAdd">新增商品</el-button>
                </div>
            </template>

            <el-form :inline="true" :model="searchForm" class="search-form">
                <el-form-item label="搜索">
                    <el-input v-model="searchForm.search" placeholder="商品名称/SKU/条码" clearable />
                </el-form-item>
                <el-form-item label="分类">
                    <el-select v-model="searchForm.category_id" placeholder="全部" clearable>
                        <el-option
                            v-for="category in categories"
                            :key="category.id"
                            :label="category.name"
                            :value="category.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="products" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="name" label="商品名称" />
                <el-table-column prop="sku" label="SKU" />
                <el-table-column prop="category.name" label="分类" />
                <el-table-column prop="sale_price" label="售价" width="120">
                    <template #default="{ row }">
                        ¥{{ row.sale_price }}
                    </template>
                </el-table-column>
                <el-table-column prop="is_active" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.is_active ? 'success' : 'danger'">
                            {{ row.is_active ? '启用' : '禁用' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
                        <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.per_page"
                :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next, jumper"
                @size-change="handleSizeChange"
                @current-change="handlePageChange"
                style="margin-top: 20px;"
            />
        </el-card>

        <!-- 商品表单对话框 -->
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
                label-width="100px"
            >
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="商品名称" prop="name">
                            <el-input v-model="form.name" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="SKU" prop="sku">
                            <el-input v-model="form.sku" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="商品分类" prop="category_id">
                            <el-select v-model="form.category_id" placeholder="请选择分类" style="width: 100%">
                                <el-option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :label="category.name"
                                    :value="category.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="单位" prop="unit_id">
                            <el-select v-model="form.unit_id" placeholder="请选择单位" style="width: 100%">
                                <el-option label="个" :value="1" />
                                <el-option label="件" :value="2" />
                                <el-option label="箱" :value="3" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="采购价" prop="purchase_price">
                            <el-input-number v-model="form.purchase_price" :precision="2" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="销售价" prop="sale_price">
                            <el-input-number v-model="form.sale_price" :precision="2" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="条码">
                    <el-input v-model="form.barcode" />
                </el-form-item>
                <el-form-item label="描述">
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
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '../../services/api';

const loading = ref(false);
const dialogVisible = ref(false);
const dialogTitle = ref('新增商品');
const formRef = ref(null);
const products = ref([]);
const categories = ref([]);

const searchForm = reactive({
    search: '',
    category_id: null
});

const pagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const form = reactive({
    id: null,
    name: '',
    sku: '',
    category_id: null,
    unit_id: null,
    purchase_price: 0,
    sale_price: 0,
    barcode: '',
    description: '',
    is_active: true
});

const rules = {
    name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
    sku: [{ required: true, message: '请输入SKU', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择商品分类', trigger: 'change' }],
    sale_price: [{ required: true, message: '请输入销售价', trigger: 'blur' }]
};

const loadProducts = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.page,
            per_page: pagination.per_page,
            ...searchForm
        };
        const response = await api.get('/products', { params });
        products.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载商品列表失败');
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await api.get('/product-categories');
        categories.value = response.data.data;
    } catch (error) {
        console.error('加载分类列表失败:', error);
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadProducts();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.category_id = null;
    handleSearch();
};

const handleAdd = () => {
    dialogTitle.value = '新增商品';
    Object.assign(form, {
        id: null,
        name: '',
        sku: '',
        category_id: null,
        unit_id: null,
        purchase_price: 0,
        sale_price: 0,
        barcode: '',
        description: '',
        is_active: true
    });
    dialogVisible.value = true;
};

const handleEdit = (row) => {
    dialogTitle.value = '编辑商品';
    Object.assign(form, {
        id: row.id,
        name: row.name,
        sku: row.sku,
        category_id: row.category_id,
        unit_id: row.unit_id,
        purchase_price: row.purchase_price,
        sale_price: row.sale_price,
        barcode: row.barcode || '',
        description: row.description || '',
        is_active: row.is_active
    });
    dialogVisible.value = true;
};

const handleDelete = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该商品吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/products/${row.id}`);
        ElMessage.success('删除成功');
        loadProducts();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('删除失败');
        }
    }
};

const handleSubmit = async () => {
    if (!formRef.value) return;
    
    await formRef.value.validate(async (valid) => {
        if (valid) {
            try {
                if (form.id) {
                    await api.put(`/products/${form.id}`, form);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/products', form);
                    ElMessage.success('创建成功');
                }
                dialogVisible.value = false;
                loadProducts();
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
    loadProducts();
};

const handlePageChange = () => {
    loadProducts();
};

onMounted(() => {
    loadProducts();
    loadCategories();
});
</script>

<style scoped>
.products-page {
    padding: 0;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-form {
    margin-bottom: 20px;
}
</style>

