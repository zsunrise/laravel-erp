<template>
    <div class="production-page">
        <el-tabs v-model="activeTab">
            <el-tab-pane label="生产计划" name="plans">
                <div class="page-card">
                    <div class="page-header">
                        <h2 class="page-title text-primary">生产计划</h2>
                        <div class="page-actions">
                            <el-button type="primary" @click="handleAddPlan" class="interactive">
                                <Plus :size="16" style="margin-right: 6px;" />
                                新增计划
                            </el-button>
                        </div>
                    </div>

                    <el-form :inline="true" :model="planSearchForm" class="search-form-modern">
                        <el-form-item label="计划号">
                            <el-input v-model="planSearchForm.plan_no" placeholder="计划号" clearable />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="planSearchForm.status" placeholder="全部" clearable>
                                <el-option label="待开始" value="pending" />
                                <el-option label="进行中" value="processing" />
                                <el-option label="已完成" value="completed" />
                                <el-option label="已取消" value="cancelled" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="handlePlanSearch">查询</el-button>
                            <el-button @click="handlePlanReset">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <div class="modern-table" style="margin: 0 24px;">
                        <el-table :data="plans" v-loading="planLoading" style="width: 100%">
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column prop="plan_no" label="计划号" width="150" />
                        <el-table-column prop="plan_date" label="计划日期" width="120" />
                        <el-table-column prop="warehouse.name" label="仓库" width="150" />
                        <el-table-column prop="start_date" label="开始日期" width="120" />
                        <el-table-column prop="end_date" label="结束日期" width="120" />
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getPlanStatusType(row.status)">{{ getPlanStatusText(row.status) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="250" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" size="small" @click="handleViewPlan(row)" :loading="planViewLoadingId === row.id" :disabled="planViewLoadingId !== null">查看</el-button>
                                <el-button type="warning" size="small" @click="handleEditPlan(row)" v-if="row.status == 'draft'">编辑</el-button>
                                <el-button type="success" size="small" @click="handleApprovePlan(row)" v-if="row.status == 'draft'">审批</el-button>
                                <el-button type="danger" size="small" @click="handleDeletePlan(row)" v-if="row.status == 'draft'">删除</el-button>
                            </template>
                        </el-table-column>
                        </el-table>
                    </div>

                    <div class="modern-pagination">
                        <el-pagination
                            v-model:current-page="planPagination.page"
                            v-model:page-size="planPagination.per_page"
                            :total="planPagination.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="handlePlanSizeChange"
                            @current-change="handlePlanPageChange"
                        />
                    </div>
                </div>
            </el-tab-pane>

            <el-tab-pane label="工单管理" name="workorders">
                <div class="page-card">
                    <div class="page-header">
                        <h2 class="page-title text-primary">工单管理</h2>
                        <div class="page-actions">
                            <el-button type="primary" @click="handleAddWorkOrder" class="interactive">
                                <Plus :size="16" style="margin-right: 6px;" />
                                新增工单
                            </el-button>
                        </div>
                    </div>

                    <el-form :inline="true" :model="workOrderSearchForm" class="search-form-modern">
                        <el-form-item label="工单号">
                            <el-input v-model="workOrderSearchForm.work_order_no" placeholder="工单号" clearable />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="workOrderSearchForm.status" placeholder="全部" clearable>
                                <el-option label="待开始" value="pending" />
                                <el-option label="进行中" value="processing" />
                                <el-option label="已完成" value="completed" />
                                <el-option label="已暂停" value="paused" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="handleWorkOrderSearch">查询</el-button>
                            <el-button @click="handleWorkOrderReset">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <div class="modern-table" style="margin: 0 24px;">
                        <el-table :data="workOrders" v-loading="workOrderLoading" style="width: 100%">
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column prop="work_order_no" label="工单号" width="150" />
                        <el-table-column prop="product.name" label="产品" />
                        <el-table-column prop="quantity" label="数量" width="120" />
                        <el-table-column prop="start_date" label="开始日期" width="120" />
                        <el-table-column prop="planned_end_date" label="计划结束日期" width="120" />
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getWorkOrderStatusType(row.status)">{{ getWorkOrderStatusText(row.status) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="250" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" size="small" @click="handleViewWorkOrder(row)" :loading="workOrderViewLoadingId === row.id" :disabled="workOrderViewLoadingId !== null">查看</el-button>
                                <el-button type="warning" size="small" @click="handleEditWorkOrder(row)" v-if="row.status == 'draft'">编辑</el-button>
                                <el-button type="success" size="small" @click="handleApproveWorkOrder(row)" v-if="row.status == 'draft'">审批</el-button>
                                <el-button type="danger" size="small" @click="handleDeleteWorkOrder(row)" v-if="row.status == 'draft'">删除</el-button>
                            </template>
                        </el-table-column>
                        </el-table>
                    </div>

                    <div class="modern-pagination">
                        <el-pagination
                            v-model:current-page="workOrderPagination.page"
                            v-model:page-size="workOrderPagination.per_page"
                            :total="workOrderPagination.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="handleWorkOrderSizeChange"
                            @current-change="handleWorkOrderPageChange"
                        />
                    </div>
                </div>
            </el-tab-pane>
        </el-tabs>

        <!-- 生产计划表单对话框 -->
        <el-dialog
            v-model="planDialogVisible"
            :title="planDialogTitle"
            width="1400px"
            @close="handlePlanDialogClose"
        >
            <el-form
                ref="planFormRef"
                :model="planForm"
                :rules="planRules"
                label-width="120px"
            >
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="计划日期" prop="plan_date">
                            <el-date-picker v-model="planForm.plan_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="销售订单" prop="sales_order_id">
                            <el-select v-model="planForm.sales_order_id" filterable placeholder="请选择销售订单（可选）" clearable style="width: 100%">
                                <el-option
                                    v-for="order in salesOrders"
                                    :key="order.id"
                                    :label="`${order.order_no} - ${order.customer?.name || ''}`"
                                    :value="order.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="开始日期" prop="start_date">
                            <el-date-picker v-model="planForm.start_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="结束日期" prop="end_date">
                            <el-date-picker v-model="planForm.end_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="仓库" prop="warehouse_id">
                            <el-select v-model="planForm.warehouse_id" filterable placeholder="请选择仓库" style="width: 100%">
                                <el-option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :label="warehouse.name"
                                    :value="warehouse.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="planForm.remark" type="textarea" :rows="2" />
                </el-form-item>
                <el-form-item label="计划明细" prop="items">
                    <el-button type="primary" size="small" @click="handleAddPlanItem">添加明细</el-button>
                    <el-table :data="planForm.items" style="margin-top: 10px;" border>
                        <el-table-column prop="product.name" label="产品" width="200">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.product_id" filterable placeholder="请选择产品" @change="handlePlanItemProductChange($index)" style="width: 100%">
                                    <el-option
                                        v-for="product in products"
                                        :key="product.id"
                                        :label="`${product.name} (${product.sku})`"
                                        :value="product.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="BOM" width="200">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.bom_id" filterable placeholder="请选择BOM（可选）" clearable style="width: 100%">
                                    <el-option
                                        v-for="bom in getBomsByProduct(row.product_id)"
                                        :key="bom.id"
                                        :label="bom.version"
                                        :value="bom.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="工艺路线" width="200">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.process_route_id" filterable placeholder="请选择工艺路线（可选）" clearable style="width: 100%">
                                    <el-option
                                        v-for="route in getProcessRoutesByProduct(row.product_id)"
                                        :key="route.id"
                                        :label="route.version"
                                        :value="route.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="计划数量" width="150">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.planned_quantity" :min="1" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="计划开始日期" width="180">
                            <template #default="{ row, $index }">
                                <el-date-picker v-model="row.planned_start_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="计划结束日期" width="180">
                            <template #default="{ row, $index }">
                                <el-date-picker v-model="row.planned_end_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="优先级" width="120">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.priority" :min="0" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="备注" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.remark" placeholder="备注" />
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ $index }">
                                <el-button type="danger" size="small" @click="handleRemovePlanItem($index)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="planDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitPlan" :loading="planSubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 工单表单对话框 -->
        <el-dialog
            v-model="workOrderDialogVisible"
            :title="workOrderDialogTitle"
            width="1000px"
            @close="handleWorkOrderDialogClose"
        >
            <el-form
                ref="workOrderFormRef"
                :model="workOrderForm"
                :rules="workOrderRules"
                label-width="120px"
            >
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="产品" prop="product_id">
                            <el-select v-model="workOrderForm.product_id" filterable placeholder="请选择产品" @change="handleWorkOrderProductChange" style="width: 100%">
                                <el-option
                                    v-for="product in products"
                                    :key="product.id"
                                    :label="`${product.name} (${product.sku})`"
                                    :value="product.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="数量" prop="quantity">
                            <el-input-number v-model="workOrderForm.quantity" :min="1" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="BOM" prop="bom_id">
                            <el-select v-model="workOrderForm.bom_id" filterable placeholder="请选择BOM（可选）" clearable style="width: 100%">
                                <el-option
                                    v-for="bom in getBomsByProduct(workOrderForm.product_id)"
                                    :key="bom.id"
                                    :label="bom.version"
                                    :value="bom.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="工艺路线" prop="process_route_id">
                            <el-select v-model="workOrderForm.process_route_id" filterable placeholder="请选择工艺路线（可选）" clearable style="width: 100%">
                                <el-option
                                    v-for="route in getProcessRoutesByProduct(workOrderForm.product_id)"
                                    :key="route.id"
                                    :label="route.version"
                                    :value="route.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="仓库" prop="warehouse_id">
                            <el-select v-model="workOrderForm.warehouse_id" filterable placeholder="请选择仓库" style="width: 100%">
                                <el-option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :label="warehouse.name"
                                    :value="warehouse.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="分配人员" prop="assigned_to">
                            <el-select v-model="workOrderForm.assigned_to" filterable placeholder="请选择分配人员（可选）" clearable style="width: 100%">
                                <el-option
                                    v-for="user in users"
                                    :key="user.id"
                                    :label="user.name"
                                    :value="user.id"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="开始日期" prop="start_date">
                            <el-date-picker v-model="workOrderForm.start_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="计划结束日期" prop="planned_end_date">
                            <el-date-picker v-model="workOrderForm.planned_end_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="workOrderForm.remark" type="textarea" :rows="2" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="workOrderDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitWorkOrder" :loading="workOrderSubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 生产计划详情对话框 -->
        <el-dialog
            v-model="planDetailVisible"
            title="生产计划详情"
            width="1200px"
            :close-on-click-modal="false"
        >
            <div v-loading="planDetailLoading" style="padding: 20px;">
                <div v-if="currentPlan">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="计划号">{{ currentPlan.plan_no }}</el-descriptions-item>
                    <el-descriptions-item label="计划日期">{{ currentPlan.plan_date }}</el-descriptions-item>
                    <el-descriptions-item label="仓库">{{ currentPlan.warehouse?.name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getPlanStatusType(currentPlan.status)">
                            {{ getPlanStatusText(currentPlan.status) }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="开始日期">{{ currentPlan.start_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="结束日期">{{ currentPlan.end_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="关联销售订单" :span="2">
                        {{ currentPlan.sales_order?.order_no || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentPlan.remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <el-divider content-position="left">计划明细</el-divider>
                
                <el-table :data="currentPlan.items || []" border style="width: 100%">
                    <el-table-column type="index" label="序号" width="60" />
                    <el-table-column prop="product.name" label="产品名称" min-width="200" />
                    <el-table-column prop="product.sku" label="SKU" width="150" />
                    <el-table-column prop="bom.version" label="BOM版本" width="120" />
                    <el-table-column prop="processRoute.version" label="工艺路线版本" width="150" />
                    <el-table-column prop="planned_quantity" label="计划数量" width="120" align="right" />
                    <el-table-column prop="planned_start_date" label="计划开始日期" width="150" />
                    <el-table-column prop="planned_end_date" label="计划结束日期" width="150" />
                    <el-table-column prop="priority" label="优先级" width="100" align="center" />
                    <el-table-column prop="remark" label="备注" min-width="200" show-overflow-tooltip />
                </el-table>
                </div>
            </div>
            <template #footer>
                <el-button @click="planDetailVisible = false">关闭</el-button>
            </template>
        </el-dialog>

        <!-- 工单详情对话框 -->
        <el-dialog
            v-model="workOrderDetailVisible"
            title="工单详情"
            width="1200px"
            :close-on-click-modal="false"
        >
            <div v-loading="workOrderDetailLoading">
                <el-descriptions :column="2" border v-if="currentWorkOrder">
                    <el-descriptions-item label="工单号">{{ currentWorkOrder.work_order_no }}</el-descriptions-item>
                    <el-descriptions-item label="产品">{{ currentWorkOrder.product?.name }}</el-descriptions-item>
                    <el-descriptions-item label="SKU">{{ currentWorkOrder.product?.sku }}</el-descriptions-item>
                    <el-descriptions-item label="仓库">{{ currentWorkOrder.warehouse?.name }}</el-descriptions-item>
                    <el-descriptions-item label="计划数量">{{ currentWorkOrder.planned_quantity }}</el-descriptions-item>
                    <el-descriptions-item label="完成数量">{{ currentWorkOrder.completed_quantity || 0 }}</el-descriptions-item>
                    <el-descriptions-item label="开始日期">{{ currentWorkOrder.start_date }}</el-descriptions-item>
                    <el-descriptions-item label="结束日期">{{ currentWorkOrder.end_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getWorkOrderStatusType(currentWorkOrder.status)">
                            {{ getWorkOrderStatusText(currentWorkOrder.status) }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="负责人">{{ currentWorkOrder.assigned_to_user?.name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentWorkOrder.remark || '-' }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const activeTab = ref('plans');
const planLoading = ref(false);
const workOrderLoading = ref(false);
const planDialogVisible = ref(false);
const planDetailVisible = ref(false);
const planDetailLoading = ref(false);
const planViewLoadingId = ref(null);
const workOrderDialogVisible = ref(false);
const workOrderDetailVisible = ref(false);
const workOrderDetailLoading = ref(false);
const workOrderViewLoadingId = ref(null);
const planSubmitLoading = ref(false);
const workOrderSubmitLoading = ref(false);
const planFormRef = ref(null);
const workOrderFormRef = ref(null);
const plans = ref([]);
const workOrders = ref([]);
const products = ref([]);
const salesOrders = ref([]);
const warehouses = ref([]);
const boms = ref([]);
const processRoutes = ref([]);
const users = ref([]);
const currentPlan = ref(null);
const currentWorkOrder = ref(null);

const planSearchForm = reactive({
    plan_no: '',
    status: null
});

const workOrderSearchForm = reactive({
    work_order_no: '',
    status: null
});

const planPagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const workOrderPagination = reactive({
    page: 1,
    per_page: 15,
    total: 0
});

const planForm = reactive({
    id: null,
    sales_order_id: null,
    plan_date: new Date().toISOString().split('T')[0],
    start_date: new Date().toISOString().split('T')[0],
    end_date: null,
    warehouse_id: null,
    remark: '',
    items: []
});

const workOrderForm = reactive({
    id: null,
    production_plan_id: null,
    production_plan_item_id: null,
    product_id: null,
    bom_id: null,
    process_route_id: null,
    warehouse_id: null,
    quantity: 1,
    start_date: new Date().toISOString().split('T')[0],
    planned_end_date: null,
    assigned_to: null,
    remark: ''
});

const planRules = {
    plan_date: [{ required: true, message: '请选择计划日期', trigger: 'change' }],
    start_date: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
    end_date: [{ required: true, message: '请选择结束日期', trigger: 'change' }],
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    items: [
        { required: true, message: '请添加计划明细', trigger: 'change' },
        { type: 'array', min: 1, message: '至少添加一条计划明细', trigger: 'change' }
    ]
};

const workOrderRules = {
    product_id: [{ required: true, message: '请选择产品', trigger: 'change' }],
    quantity: [{ required: true, message: '请输入数量', trigger: 'change' }],
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    start_date: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
    planned_end_date: [{ required: true, message: '请选择计划结束日期', trigger: 'change' }]
};

const planDialogTitle = ref('新增生产计划');
const workOrderDialogTitle = ref('新增工单');

const getPlanStatusType = (status) => {
    const statusMap = {
        'draft': 'info',
        'approved': 'success',
        'in_progress': 'warning',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return statusMap[status] || 'info';
};

const getPlanStatusText = (status) => {
    const statusMap = {
        'draft': '草稿',
        'approved': '已审批',
        'in_progress': '进行中',
        'completed': '已完成',
        'cancelled': '已取消'
    };
    return statusMap[status] || status;
};

const getWorkOrderStatusType = (status) => {
    const statusMap = {
        'draft': 'info',
        'approved': 'success',
        'material_issued': 'warning',
        'in_progress': 'warning',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return statusMap[status] || 'info';
};

const getWorkOrderStatusText = (status) => {
    const statusMap = {
        'draft': '草稿',
        'approved': '已审批',
        'material_issued': '已领料',
        'in_progress': '进行中',
        'completed': '已完成',
        'cancelled': '已取消'
    };
    return statusMap[status] || status;
};

const getBomsByProduct = (productId) => {
    if (!productId) return [];
    return boms.value.filter(bom => bom.product_id == productId && bom.is_active);
};

const getProcessRoutesByProduct = (productId) => {
    if (!productId) return [];
    return processRoutes.value.filter(route => route.product_id == productId && route.is_active);
};

const loadPlans = async () => {
    planLoading.value = true;
    try {
        const params = {
            page: planPagination.page,
            per_page: planPagination.per_page,
            ...planSearchForm
        };
        const response = await api.get('/production-plans', { params });
        plans.value = response.data.data;
        planPagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载生产计划失败');
    } finally {
        planLoading.value = false;
    }
};

const loadWorkOrders = async () => {
    workOrderLoading.value = true;
    try {
        const params = {
            page: workOrderPagination.page,
            per_page: workOrderPagination.per_page,
            ...workOrderSearchForm
        };
        const response = await api.get('/work-orders', { params });
        workOrders.value = response.data.data;
        workOrderPagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载工单列表失败');
    } finally {
        workOrderLoading.value = false;
    }
};

const loadProducts = async () => {
    try {
        const response = await api.get('/products', { params: { per_page: 1000 } });
        products.value = response.data.data;
    } catch (error) {
        console.error('加载产品列表失败:', error);
    }
};

const loadSalesOrders = async () => {
    try {
        const response = await api.get('/sales-orders', { params: { per_page: 1000, status: 'approved' } });
        salesOrders.value = response.data.data;
    } catch (error) {
        console.error('加载销售订单列表失败:', error);
    }
};

const loadWarehouses = async () => {
    try {
        const response = await api.get('/warehouses', { params: { per_page: 1000, is_active: 1 } });
        warehouses.value = response.data.data;
    } catch (error) {
        console.error('加载仓库列表失败:', error);
    }
};

const loadBoms = async () => {
    try {
        const response = await api.get('/boms', { params: { per_page: 1000, is_active: 1 } });
        boms.value = response.data.data;
    } catch (error) {
        console.error('加载BOM列表失败:', error);
    }
};

const loadProcessRoutes = async () => {
    try {
        const response = await api.get('/process-routes', { params: { per_page: 1000, is_active: 1 } });
        processRoutes.value = response.data.data;
    } catch (error) {
        console.error('加载工艺路线列表失败:', error);
    }
};

const loadUsers = async () => {
    try {
        const response = await api.get('/users', { params: { per_page: 1000 } });
        users.value = response.data.data;
    } catch (error) {
        console.error('加载用户列表失败:', error);
    }
};

const handlePlanSearch = () => {
    planPagination.page = 1;
    loadPlans();
};

const handlePlanReset = () => {
    planSearchForm.plan_no = '';
    planSearchForm.status = null;
    handlePlanSearch();
};

const handlePlanSizeChange = () => {
    loadPlans();
};

const handlePlanPageChange = () => {
    loadPlans();
};

const handleWorkOrderSearch = () => {
    workOrderPagination.page = 1;
    loadWorkOrders();
};

const handleWorkOrderReset = () => {
    workOrderSearchForm.work_order_no = '';
    workOrderSearchForm.status = null;
    handleWorkOrderSearch();
};

const handleWorkOrderSizeChange = () => {
    loadWorkOrders();
};

const handleWorkOrderPageChange = () => {
    loadWorkOrders();
};

const handleAddPlan = () => {
    planDialogTitle.value = '新增生产计划';
    Object.assign(planForm, {
        id: null,
        sales_order_id: null,
        plan_date: new Date().toISOString().split('T')[0],
        start_date: new Date().toISOString().split('T')[0],
        end_date: null,
        warehouse_id: null,
        remark: '',
        items: []
    });
    planDialogVisible.value = true;
};

const handleEditPlan = async (row) => {
    try {
        const response = await api.get(`/production-plans/${row.id}`);
        const plan = response.data.data;
        if (plan.status != 'draft') {
            ElMessage.warning('只能编辑草稿状态的计划');
            return;
        }
        planDialogTitle.value = '编辑生产计划';
        Object.assign(planForm, {
            id: plan.id,
            sales_order_id: plan.sales_order_id,
            plan_date: plan.plan_date,
            start_date: plan.start_date,
            end_date: plan.end_date,
            warehouse_id: plan.warehouse_id,
            remark: plan.remark || '',
            items: plan.items.map(item => ({
                product_id: item.product_id,
                product: item.product,
                bom_id: item.bom_id,
                bom: item.bom,
                process_route_id: item.process_route_id,
                processRoute: item.processRoute,
                planned_quantity: item.planned_quantity,
                planned_start_date: item.planned_start_date,
                planned_end_date: item.planned_end_date,
                priority: item.priority || 0,
                remark: item.remark || ''
            }))
        });
        planDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载生产计划失败');
    }
};

const handleViewPlan = async (row) => {
    // 防止重复点击
    if (planViewLoadingId.value !== null) {
        return;
    }
    
    planViewLoadingId.value = row.id;
    planDetailLoading.value = true;
    planDetailVisible.value = true;
    currentPlan.value = null;
    
    try {
        const response = await api.get(`/production-plans/${row.id}`);
        currentPlan.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载生产计划详情失败');
        planDetailVisible.value = false;
    } finally {
        planDetailLoading.value = false;
        planViewLoadingId.value = null;
    }
};

const handleDeletePlan = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该生产计划吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/production-plans/${row.id}`);
        ElMessage.success('删除成功');
        loadPlans();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleApprovePlan = async (row) => {
    try {
        await ElMessageBox.confirm('确定要审批该生产计划吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/production-plans/${row.id}/approve`);
        ElMessage.success('审批成功');
        loadPlans();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '审批失败');
        }
    }
};

const handleAddPlanItem = () => {
    planForm.items.push({
        product_id: null,
        product: null,
        bom_id: null,
        bom: null,
        process_route_id: null,
        processRoute: null,
        planned_quantity: 1,
        planned_start_date: planForm.start_date,
        planned_end_date: planForm.end_date,
        priority: 0,
        remark: ''
    });
};

const handleRemovePlanItem = (index) => {
    planForm.items.splice(index, 1);
};

const handlePlanItemProductChange = (index) => {
    const productId = planForm.items[index].product_id;
    const product = products.value.find(p => p.id == productId);
    if (product) {
        planForm.items[index].product = product;
    }
};

const handleSubmitPlan = async () => {
    if (!planFormRef.value) return;
    
    await planFormRef.value.validate(async (valid) => {
        if (valid) {
            if (planForm.items.length == 0) {
                ElMessage.warning('请至少添加一条计划明细');
                return;
            }
            planSubmitLoading.value = true;
            try {
                const data = {
                    ...planForm,
                    sales_order_id: planForm.sales_order_id || null,
                    remark: planForm.remark || null,
                    items: planForm.items.map(item => ({
                        product_id: item.product_id,
                        bom_id: item.bom_id || null,
                        process_route_id: item.process_route_id || null,
                        planned_quantity: item.planned_quantity,
                        planned_start_date: item.planned_start_date,
                        planned_end_date: item.planned_end_date,
                        priority: item.priority || 0,
                        remark: item.remark || null
                    }))
                };
                if (planForm.id) {
                    await api.put(`/production-plans/${planForm.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/production-plans', data);
                    ElMessage.success('创建成功');
                }
                planDialogVisible.value = false;
                loadPlans();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                planSubmitLoading.value = false;
            }
        }
    });
};

const handlePlanDialogClose = () => {
    planFormRef.value?.resetFields();
};

const handleAddWorkOrder = () => {
    workOrderDialogTitle.value = '新增工单';
    Object.assign(workOrderForm, {
        id: null,
        production_plan_id: null,
        production_plan_item_id: null,
        product_id: null,
        bom_id: null,
        process_route_id: null,
        warehouse_id: null,
        quantity: 1,
        start_date: new Date().toISOString().split('T')[0],
        planned_end_date: null,
        assigned_to: null,
        remark: ''
    });
    workOrderDialogVisible.value = true;
};

const handleEditWorkOrder = async (row) => {
    try {
        const response = await api.get(`/work-orders/${row.id}`);
        const workOrder = response.data.data;
        if (workOrder.status != 'draft') {
            ElMessage.warning('只能编辑草稿状态的工单');
            return;
        }
        workOrderDialogTitle.value = '编辑工单';
        Object.assign(workOrderForm, {
            id: workOrder.id,
            production_plan_id: workOrder.production_plan_id,
            production_plan_item_id: workOrder.production_plan_item_id,
            product_id: workOrder.product_id,
            bom_id: workOrder.bom_id,
            process_route_id: workOrder.process_route_id,
            warehouse_id: workOrder.warehouse_id,
            quantity: workOrder.quantity,
            start_date: workOrder.start_date,
            planned_end_date: workOrder.planned_end_date,
            assigned_to: workOrder.assigned_to,
            remark: workOrder.remark || ''
        });
        workOrderDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载工单失败');
    }
};

const handleViewWorkOrder = async (row) => {
    // 防止重复点击
    if (workOrderViewLoadingId.value !== null) {
        return;
    }
    
    workOrderViewLoadingId.value = row.id;
    workOrderDetailLoading.value = true;
    workOrderDetailVisible.value = true;
    currentWorkOrder.value = null;
    
    try {
        const response = await api.get(`/work-orders/${row.id}`);
        currentWorkOrder.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载工单详情失败');
        workOrderDetailVisible.value = false;
    } finally {
        workOrderDetailLoading.value = false;
        workOrderViewLoadingId.value = null;
    }
};

const handleDeleteWorkOrder = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该工单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/work-orders/${row.id}`);
        ElMessage.success('删除成功');
        loadWorkOrders();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleApproveWorkOrder = async (row) => {
    try {
        await ElMessageBox.confirm('确定要审批该工单吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/work-orders/${row.id}/approve`);
        ElMessage.success('审批成功');
        loadWorkOrders();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '审批失败');
        }
    }
};

const handleWorkOrderProductChange = () => {
    // 产品改变时的处理
};

const handleSubmitWorkOrder = async () => {
    if (!workOrderFormRef.value) return;
    
    await workOrderFormRef.value.validate(async (valid) => {
        if (valid) {
            workOrderSubmitLoading.value = true;
            try {
                const data = {
                    ...workOrderForm,
                    production_plan_id: workOrderForm.production_plan_id || null,
                    production_plan_item_id: workOrderForm.production_plan_item_id || null,
                    bom_id: workOrderForm.bom_id || null,
                    process_route_id: workOrderForm.process_route_id || null,
                    assigned_to: workOrderForm.assigned_to || null,
                    remark: workOrderForm.remark || null
                };
                if (workOrderForm.id) {
                    await api.put(`/work-orders/${workOrderForm.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/work-orders', data);
                    ElMessage.success('创建成功');
                }
                workOrderDialogVisible.value = false;
                loadWorkOrders();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                workOrderSubmitLoading.value = false;
            }
        }
    });
};

const handleWorkOrderDialogClose = () => {
    workOrderFormRef.value?.resetFields();
};

onMounted(() => {
    loadPlans();
    loadWorkOrders();
    loadProducts();
    loadSalesOrders();
    loadWarehouses();
    loadBoms();
    loadProcessRoutes();
    loadUsers();
});
</script>

<style scoped>
/* 使用全局样式类 */
</style>

