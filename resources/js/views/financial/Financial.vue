<template>
    <div class="financial-page">
        <el-tabs v-model="activeTab">
            <el-tab-pane label="会计凭证" name="vouchers">
                <div class="page-card">
                    <div class="page-header">
                        <h2 class="page-title text-primary">会计凭证</h2>
                        <div class="page-actions">
                            <el-button type="primary" @click="handleAddVoucher" class="interactive">
                                <Plus :size="16" style="margin-right: 6px;" />
                                新增凭证
                            </el-button>
                        </div>
                    </div>

                    <el-form :inline="true" :model="voucherSearchForm" class="search-form-modern">
                        <el-form-item label="凭证号">
                            <el-input v-model="voucherSearchForm.voucher_no" placeholder="凭证号" clearable />
                        </el-form-item>
                        <el-form-item label="日期">
                            <el-date-picker
                                v-model="voucherSearchForm.date_range"
                                type="daterange"
                                range-separator="至"
                                start-placeholder="开始日期"
                                end-placeholder="结束日期"
                                value-format="YYYY-MM-DD"
                            />
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="handleVoucherSearch">查询</el-button>
                            <el-button @click="handleVoucherReset">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <div class="modern-table" style="margin: 0 24px;">
                        <el-table :data="vouchers" v-loading="voucherLoading" style="width: 100%">
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column prop="voucher_no" label="凭证号" width="150" />
                        <el-table-column prop="voucher_date" label="凭证日期" width="120" />
                        <el-table-column prop="type" label="类型" width="100">
                            <template #default="{ row }">
                                <el-tag>{{ getVoucherTypeText(row.type) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="total_debit" label="借方金额" width="120">
                            <template #default="{ row }">¥{{ row.total_debit || 0 }}</template>
                        </el-table-column>
                        <el-table-column prop="total_credit" label="贷方金额" width="120">
                            <template #default="{ row }">¥{{ row.total_credit || 0 }}</template>
                        </el-table-column>
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="row.status == 'posted' ? 'success' : 'warning'">
                                    {{ row.status == 'posted' ? '已过账' : '草稿' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="250" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" size="small" @click="handleViewVoucher(row)" :loading="voucherViewLoadingId === row.id" :disabled="voucherViewLoadingId !== null">查看</el-button>
                                <el-button type="warning" size="small" @click="handleEditVoucher(row)" v-if="row.status == 'draft'">编辑</el-button>
                                <el-button type="success" size="small" @click="handlePostVoucher(row)" v-if="row.status == 'draft'">过账</el-button>
                                <el-button type="danger" size="small" @click="handleDeleteVoucher(row)" v-if="row.status == 'draft'">删除</el-button>
                            </template>
                        </el-table-column>
                        </el-table>
                    </div>

                    <div class="modern-pagination">
                        <el-pagination
                            v-model:current-page="voucherPagination.page"
                            v-model:page-size="voucherPagination.per_page"
                            :total="voucherPagination.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="handleVoucherSizeChange"
                            @current-change="handleVoucherPageChange"
                        />
                    </div>
                </div>
            </el-tab-pane>

            <el-tab-pane label="应收应付" name="receivables">
                <div class="page-card">
                    <div class="page-header">
                        <h2 class="page-title text-primary">应收应付</h2>
                    </div>

                    <el-tabs v-model="receivableTab">
                        <el-tab-pane label="应收账款" name="receivable">
                            <!-- 统计卡片 -->
                            <el-row :gutter="20" style="margin-bottom: 20px;">
                                <el-col :span="6">
                                    <el-statistic title="应收总额" :value="receivableStats.total_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="已收总额" :value="receivableStats.received_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="未收余额" :value="receivableStats.balance">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="逾期笔数" :value="receivableStats.overdue_count">
                                        <template #suffix>笔</template>
                                    </el-statistic>
                                </el-col>
                            </el-row>

                            <el-form :inline="true" :model="receivableSearchForm" class="search-form-modern">
                                <el-form-item label="客户">
                                    <el-select v-model="receivableSearchForm.customer_id" filterable placeholder="全部客户" clearable style="width: 200px">
                                        <el-option
                                            v-for="customer in customers"
                                            :key="customer.id"
                                            :label="customer.name"
                                            :value="customer.id"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="状态">
                                    <el-select v-model="receivableSearchForm.status" placeholder="全部" clearable style="width: 150px">
                                        <el-option label="未结清" value="outstanding" />
                                        <el-option label="部分收款" value="partial" />
                                        <el-option label="已结清" value="settled" />
                                        <el-option label="逾期" value="overdue" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="日期范围">
                                    <el-date-picker
                                        v-model="receivableSearchForm.date_range"
                                        type="daterange"
                                        range-separator="至"
                                        start-placeholder="开始日期"
                                        end-placeholder="结束日期"
                                        value-format="YYYY-MM-DD"
                                    />
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="primary" @click="handleReceivableSearch">查询</el-button>
                                    <el-button @click="handleReceivableReset">重置</el-button>
                                </el-form-item>
                            </el-form>

                            <div class="modern-table" style="margin: 0 24px;">
                                <el-table :data="receivables" v-loading="receivableLoading" style="width: 100%">
                                <el-table-column prop="id" label="ID" width="80" />
                                <el-table-column prop="customer.name" label="客户" />
                                <el-table-column prop="reference_no" label="关联单据号" width="150" />
                                <el-table-column prop="invoice_date" label="发票日期" width="120" />
                                <el-table-column prop="original_amount" label="应收金额" width="120">
                                    <template #default="{ row }">¥{{ row.original_amount || 0 }}</template>
                                </el-table-column>
                                <el-table-column prop="received_amount" label="已收金额" width="120">
                                    <template #default="{ row }">¥{{ row.received_amount || 0 }}</template>
                                </el-table-column>
                                <el-table-column prop="remaining_amount" label="余额" width="120">
                                    <template #default="{ row }">
                                        <span :class="getBalanceClass(row)">¥{{ Number(row.remaining_amount || (row.original_amount || 0) - (row.received_amount || 0)).toFixed(2) }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="due_date" label="到期日" width="120">
                                    <template #default="{ row }">
                                        <span :class="getDueDateClass(row)">{{ row.due_date }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template #default="{ row }">
                                        <el-tag :type="getReceivableStatusType(row)">
                                            {{ getReceivableStatusText(row) }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="200" fixed="right">
                                    <template #default="{ row }">
                                        <el-button type="primary" size="small" @click="handleViewReceivable(row)" :loading="receivableViewLoadingId === row.id" :disabled="receivableViewLoadingId !== null" class="interactive">查看</el-button>
                                        <el-button type="success" size="small" @click="handleReceivePayment(row)" v-if="row.status != 'settled'" class="interactive">收款</el-button>
                                    </template>
                                </el-table-column>
                                </el-table>
                            </div>

                            <div class="modern-pagination">
                                <el-pagination
                                    v-model:current-page="receivablePagination.page"
                                    v-model:page-size="receivablePagination.per_page"
                                    :total="receivablePagination.total"
                                    :page-sizes="[10, 20, 50, 100]"
                                    layout="total, sizes, prev, pager, next, jumper"
                                    @size-change="handleReceivableSizeChange"
                                    @current-change="handleReceivablePageChange"
                                />
                            </div>
                        </el-tab-pane>

                        <el-tab-pane label="应付账款" name="payable">
                            <!-- 统计卡片 -->
                            <el-row :gutter="20" style="margin-bottom: 20px;">
                                <el-col :span="6">
                                    <el-statistic title="应付总额" :value="payableStats.total_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="已付总额" :value="payableStats.paid_amount">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="未付余额" :value="payableStats.balance">
                                        <template #prefix>¥</template>
                                    </el-statistic>
                                </el-col>
                                <el-col :span="6">
                                    <el-statistic title="逾期笔数" :value="payableStats.overdue_count">
                                        <template #suffix>笔</template>
                                    </el-statistic>
                                </el-col>
                            </el-row>

                            <el-form :inline="true" :model="payableSearchForm" class="search-form-modern">
                                <el-form-item label="供应商">
                                    <el-select v-model="payableSearchForm.supplier_id" filterable placeholder="全部供应商" clearable style="width: 200px">
                                        <el-option
                                            v-for="supplier in suppliers"
                                            :key="supplier.id"
                                            :label="supplier.name"
                                            :value="supplier.id"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="状态">
                                    <el-select v-model="payableSearchForm.status" placeholder="全部" clearable style="width: 150px">
                                        <el-option label="未结清" value="outstanding" />
                                        <el-option label="部分付款" value="partial" />
                                        <el-option label="已结清" value="settled" />
                                        <el-option label="逾期" value="overdue" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="日期范围">
                                    <el-date-picker
                                        v-model="payableSearchForm.date_range"
                                        type="daterange"
                                        range-separator="至"
                                        start-placeholder="开始日期"
                                        end-placeholder="结束日期"
                                        value-format="YYYY-MM-DD"
                                    />
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="primary" @click="handlePayableSearch">查询</el-button>
                                    <el-button @click="handlePayableReset">重置</el-button>
                                </el-form-item>
                            </el-form>

                            <div class="modern-table" style="margin: 0 24px;">
                                <el-table :data="payables" v-loading="payableLoading" style="width: 100%">
                                <el-table-column prop="id" label="ID" width="80" />
                                <el-table-column prop="supplier.name" label="供应商" />
                                <el-table-column prop="reference_no" label="关联单据号" width="150" />
                                <el-table-column prop="invoice_date" label="发票日期" width="120" />
                                <el-table-column prop="original_amount" label="应付金额" width="120">
                                    <template #default="{ row }">¥{{ row.original_amount || 0 }}</template>
                                </el-table-column>
                                <el-table-column prop="paid_amount" label="已付金额" width="120">
                                    <template #default="{ row }">¥{{ row.paid_amount || 0 }}</template>
                                </el-table-column>
                                <el-table-column prop="remaining_amount" label="余额" width="120">
                                    <template #default="{ row }">
                                        <span :class="getBalanceClass(row)">¥{{ Number(row.remaining_amount || (row.original_amount || 0) - (row.paid_amount || 0)).toFixed(2) }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="due_date" label="到期日" width="120">
                                    <template #default="{ row }">
                                        <span :class="getDueDateClass(row)">{{ row.due_date }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template #default="{ row }">
                                        <el-tag :type="getPayableStatusType(row)">
                                            {{ getPayableStatusText(row) }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="200" fixed="right">
                                    <template #default="{ row }">
                                        <el-button type="primary" size="small" @click="handleViewPayable(row)" :loading="payableViewLoadingId === row.id" :disabled="payableViewLoadingId !== null" class="interactive">查看</el-button>
                                        <el-button type="warning" size="small" @click="handleMakePayment(row)" v-if="row.status != 'settled'" class="interactive">付款</el-button>
                                    </template>
                                </el-table-column>
                                </el-table>
                            </div>

                            <div class="modern-pagination">
                                <el-pagination
                                    v-model:current-page="payablePagination.page"
                                    v-model:page-size="payablePagination.per_page"
                                    :total="payablePagination.total"
                                    :page-sizes="[10, 20, 50, 100]"
                                    layout="total, sizes, prev, pager, next, jumper"
                                    @size-change="handlePayableSizeChange"
                                    @current-change="handlePayablePageChange"
                                />
                            </div>
                        </el-tab-pane>
                    </el-tabs>
                </div>
            </el-tab-pane>
        </el-tabs>

        <!-- 凭证表单对话框 -->
        <el-dialog
            v-model="voucherDialogVisible"
            :title="voucherDialogTitle"
            width="1400px"
            @close="handleVoucherDialogClose"
        >
            <el-form
                ref="voucherFormRef"
                :model="voucherForm"
                :rules="voucherRules"
                label-width="120px"
            >
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="凭证日期" prop="voucher_date">
                            <el-date-picker v-model="voucherForm.voucher_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="凭证类型" prop="type">
                            <el-select v-model="voucherForm.type" placeholder="请选择类型" style="width: 100%">
                                <el-option label="普通凭证" value="general" />
                                <el-option label="调整凭证" value="adjustment" />
                                <el-option label="结账凭证" value="closing" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="附件数" prop="attachment_count">
                            <el-input-number v-model="voucherForm.attachment_count" :min="0" style="width: 100%" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="借贷平衡" prop="balance">
                            <el-tag :type="isVoucherBalanced ? 'success' : 'danger'" size="large">
                                {{ isVoucherBalanced ? '已平衡' : '不平衡' }}
                            </el-tag>
                            <span style="margin-left: 10px;">
                                借方: ¥{{ totalDebit.toFixed(2) }} | 贷方: ¥{{ totalCredit.toFixed(2) }}
                            </span>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="voucherForm.remark" type="textarea" :rows="2" />
                </el-form-item>
                <el-form-item label="凭证明细" prop="items">
                    <el-button type="primary" size="small" @click="handleAddVoucherItem">添加明细</el-button>
                    <el-table :data="voucherForm.items" style="margin-top: 10px;" border>
                        <el-table-column prop="sequence" label="序号" width="80">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.sequence" :min="0" @change="handleVoucherItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="account.name" label="会计科目" width="250">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.account_id" filterable placeholder="请选择科目" @change="handleVoucherItemAccountChange($index)" style="width: 100%">
                                    <el-option-group
                                        v-for="group in accountGroups"
                                        :key="group.type"
                                        :label="getAccountTypeText(group.type)"
                                    >
                                        <el-option
                                            v-for="account in group.accounts"
                                            :key="account.id"
                                            :label="`${account.code} ${account.name}`"
                                            :value="account.id"
                                        />
                                    </el-option-group>
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="方向" width="120">
                            <template #default="{ row, $index }">
                                <el-select v-model="row.direction" @change="handleVoucherItemChange($index)" style="width: 100%">
                                    <el-option label="借方" value="debit" />
                                    <el-option label="贷方" value="credit" />
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="金额" width="150">
                            <template #default="{ row, $index }">
                                <el-input-number v-model="row.amount" :min="0" :precision="2" @change="handleVoucherItemChange($index)" style="width: 100%" />
                            </template>
                        </el-table-column>
                        <el-table-column label="摘要" width="200">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.summary" placeholder="摘要" />
                            </template>
                        </el-table-column>
                        <el-table-column label="关联类型" width="120">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.reference_type" placeholder="关联类型" />
                            </template>
                        </el-table-column>
                        <el-table-column label="关联编号" width="120">
                            <template #default="{ row, $index }">
                                <el-input v-model="row.reference_no" placeholder="关联编号" />
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="100">
                            <template #default="{ $index }">
                                <el-button type="danger" size="small" @click="handleRemoveVoucherItem($index)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="voucherDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitVoucher" :loading="voucherSubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 凭证详情对话框 -->
        <el-dialog
            v-model="voucherDetailVisible"
            title="凭证详情"
            width="1200px"
            :close-on-click-modal="false"
        >
            <div v-loading="voucherDetailLoading">
                <el-descriptions :column="2" border v-if="currentVoucher">
                <el-descriptions-item label="凭证号">{{ currentVoucher.voucher_no }}</el-descriptions-item>
                <el-descriptions-item label="凭证日期">{{ currentVoucher.voucher_date }}</el-descriptions-item>
                <el-descriptions-item label="凭证类型">{{ getVoucherTypeText(currentVoucher.type) }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="currentVoucher.status == 'posted' ? 'success' : 'warning'">
                        {{ currentVoucher.status == 'posted' ? '已过账' : '草稿' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="借方总额">¥{{ currentVoucher.total_debit || 0 }}</el-descriptions-item>
                <el-descriptions-item label="贷方总额">¥{{ currentVoucher.total_credit || 0 }}</el-descriptions-item>
                <el-descriptions-item label="附件数">{{ currentVoucher.attachment_count || 0 }}</el-descriptions-item>
                <el-descriptions-item label="创建人">{{ currentVoucher.creator?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="过账人" v-if="currentVoucher.status == 'posted'">{{ currentVoucher.poster?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="过账时间" v-if="currentVoucher.status == 'posted'">{{ currentVoucher.posted_at || '-' }}</el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentVoucher.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
                <el-table :data="currentVoucher?.items || []" style="margin-top: 20px;" border v-if="currentVoucher">
                    <el-table-column prop="sequence" label="序号" width="80" />
                    <el-table-column prop="account.code" label="科目编码" width="120" />
                    <el-table-column prop="account.name" label="科目名称" />
                    <el-table-column prop="direction" label="方向" width="100">
                        <template #default="{ row }">
                            <el-tag :type="row.direction == 'debit' ? 'success' : 'primary'">
                                {{ row.direction == 'debit' ? '借方' : '贷方' }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="amount" label="金额" width="120">
                        <template #default="{ row }">¥{{ row.amount }}</template>
                    </el-table-column>
                    <el-table-column prop="summary" label="摘要" />
                    <el-table-column prop="reference_type" label="关联类型" width="120" />
                    <el-table-column prop="reference_no" label="关联编号" width="120" />
                </el-table>
            </div>
        </el-dialog>

        <!-- 应收账款详情对话框 -->
        <el-dialog
            v-model="receivableDetailVisible"
            title="应收账款详情"
            width="1000px"
            :close-on-click-modal="false"
        >
            <div v-loading="receivableViewLoadingId !== null" v-if="currentReceivable">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="客户">{{ currentReceivable.customer?.name }}</el-descriptions-item>
                    <el-descriptions-item label="关联单据号">{{ currentReceivable.reference_no || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="发票日期">{{ currentReceivable.invoice_date }}</el-descriptions-item>
                    <el-descriptions-item label="到期日">{{ currentReceivable.due_date }}</el-descriptions-item>
                    <el-descriptions-item label="应收金额">¥{{ currentReceivable.original_amount || 0 }}</el-descriptions-item>
                    <el-descriptions-item label="已收金额">¥{{ currentReceivable.received_amount || 0 }}</el-descriptions-item>
                    <el-descriptions-item label="余额">¥{{ Number(currentReceivable.remaining_amount || (currentReceivable.original_amount || 0) - (currentReceivable.received_amount || 0)).toFixed(2) }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getReceivableStatusType(currentReceivable)">
                            {{ getReceivableStatusText(currentReceivable) }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="币种">{{ currentReceivable.currency?.name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentReceivable.remark || '-' }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>

        <!-- 应付账款详情对话框 -->
        <el-dialog
            v-model="payableDetailVisible"
            title="应付账款详情"
            width="1000px"
            :close-on-click-modal="false"
        >
            <div v-loading="payableViewLoadingId !== null" v-if="currentPayable">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="供应商">{{ currentPayable.supplier?.name }}</el-descriptions-item>
                    <el-descriptions-item label="关联单据号">{{ currentPayable.reference_no || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="发票日期">{{ currentPayable.invoice_date }}</el-descriptions-item>
                    <el-descriptions-item label="到期日">{{ currentPayable.due_date }}</el-descriptions-item>
                    <el-descriptions-item label="应付金额">¥{{ currentPayable.original_amount || 0 }}</el-descriptions-item>
                    <el-descriptions-item label="已付金额">¥{{ currentPayable.paid_amount || 0 }}</el-descriptions-item>
                    <el-descriptions-item label="余额">¥{{ Number(currentPayable.remaining_amount || (currentPayable.original_amount || 0) - (currentPayable.paid_amount || 0)).toFixed(2) }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getPayableStatusType(currentPayable)">
                            {{ getPayableStatusText(currentPayable) }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="币种">{{ currentPayable.currency?.name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentPayable.remark || '-' }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>

        <!-- 收款对话框 -->
        <el-dialog
            v-model="receiveDialogVisible"
            title="收款"
            width="500px"
        >
            <el-form
                ref="receiveFormRef"
                :model="receiveForm"
                label-width="120px"
            >
                <el-form-item label="收款金额">
                    <el-input-number v-model="receiveForm.received_amount" :min="0" :precision="2" :max="receiveForm.max_amount" style="width: 100%" />
                    <div style="margin-top: 5px; color: #999;">可收金额：¥{{ receiveForm.max_amount }}</div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="receiveDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitReceive" :loading="receiveSubmitLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 付款对话框 -->
        <el-dialog
            v-model="paymentDialogVisible"
            title="付款"
            width="500px"
        >
            <el-form
                ref="paymentFormRef"
                :model="paymentForm"
                label-width="120px"
            >
                <el-form-item label="付款金额">
                    <el-input-number v-model="paymentForm.paid_amount" :min="0" :precision="2" :max="paymentForm.max_amount" style="width: 100%" />
                    <div style="margin-top: 5px; color: #999;">可付金额：¥{{ paymentForm.max_amount }}</div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="paymentDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitPayment" :loading="paymentSubmitLoading">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from 'lucide-vue-next';
import api from '../../services/api';

const activeTab = ref('vouchers');
const receivableTab = ref('receivable');
const voucherLoading = ref(false);
const receivableLoading = ref(false);
const payableLoading = ref(false);
const voucherDialogVisible = ref(false);
const voucherDetailVisible = ref(false);
const voucherDetailLoading = ref(false);
const voucherViewLoadingId = ref(null);
const voucherSubmitLoading = ref(false);
const voucherFormRef = ref(null);
const vouchers = ref([]);
const receivables = ref([]);
const payables = ref([]);
const accounts = ref([]);
const accountGroups = ref([]);
const customers = ref([]);
const suppliers = ref([]);
const currentVoucher = ref(null);
const currentReceivable = ref(null);
const currentPayable = ref(null);
const receivableViewLoadingId = ref(null);
const payableViewLoadingId = ref(null);
const receivableDetailVisible = ref(false);
const payableDetailVisible = ref(false);
const receiveDialogVisible = ref(false);
const paymentDialogVisible = ref(false);
const receiveForm = reactive({
    received_amount: 0,
    max_amount: 0
});
const paymentForm = reactive({
    paid_amount: 0,
    max_amount: 0
});
const receiveSubmitLoading = ref(false);
const paymentSubmitLoading = ref(false);
const receiveFormRef = ref(null);
const paymentFormRef = ref(null);

const voucherSearchForm = reactive({
    voucher_no: '',
    date_range: null
});

const voucherPagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const voucherForm = reactive({
    id: null,
    voucher_date: new Date().toISOString().split('T')[0],
    type: 'general',
    attachment_count: 0,
    remark: '',
    items: []
});

const voucherRules = {
    voucher_date: [{ required: true, message: '请选择凭证日期', trigger: 'change' }],
    type: [{ required: true, message: '请选择凭证类型', trigger: 'change' }],
    items: [
        { required: true, message: '请添加凭证明细', trigger: 'change' },
        { type: 'array', min: 2, message: '至少添加两条凭证明细', trigger: 'change' }
    ]
};

const voucherDialogTitle = ref('新增凭证');

const totalDebit = computed(() => {
    return voucherForm.items
        .filter(item => item.direction == 'debit')
        .reduce((sum, item) => sum + (item.amount || 0), 0);
});

const totalCredit = computed(() => {
    return voucherForm.items
        .filter(item => item.direction == 'credit')
        .reduce((sum, item) => sum + (item.amount || 0), 0);
});

const isVoucherBalanced = computed(() => {
    return Math.abs(totalDebit.value - totalCredit.value) < 0.01;
});

const getVoucherTypeText = (type) => {
    const typeMap = {
        'general': '普通凭证',
        'adjustment': '调整凭证',
        'closing': '结账凭证'
    };
    return typeMap[type] || type;
};

const getAccountTypeText = (type) => {
    const typeMap = {
        'asset': '资产',
        'liability': '负债',
        'equity': '权益',
        'revenue': '收入',
        'expense': '费用'
    };
    return typeMap[type] || type;
};

const receivableSearchForm = reactive({
    customer_id: null,
    status: null,
    date_range: null
});

const payableSearchForm = reactive({
    supplier_id: null,
    status: null,
    date_range: null
});

const receivablePagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const payablePagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const receivableStats = reactive({
    total_amount: 0,
    received_amount: 0,
    balance: 0,
    overdue_count: 0
});

const payableStats = reactive({
    total_amount: 0,
    paid_amount: 0,
    balance: 0,
    overdue_count: 0
});

const loadVouchers = async () => {
    voucherLoading.value = true;
    try {
        const params = {
            page: voucherPagination.page,
            per_page: voucherPagination.per_page
        };
        // 只添加非空值参数
        if (voucherSearchForm.voucher_no) {
            params.voucher_no = voucherSearchForm.voucher_no;
        }
        if (voucherSearchForm.date_range && voucherSearchForm.date_range.length == 2) {
            params.start_date = voucherSearchForm.date_range[0];
            params.end_date = voucherSearchForm.date_range[1];
        }
        const response = await api.get('/accounting-vouchers', { params });
        vouchers.value = response.data.data;
        voucherPagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载凭证列表失败');
    } finally {
        voucherLoading.value = false;
    }
};

const loadAccounts = async () => {
    try {
        const response = await api.get('/chart-of-accounts', { params: { per_page: 1000, is_active: 1 } });
        accounts.value = response.data.data;
        
        // 按类型分组
        const groups = {};
        accounts.value.forEach(account => {
            if (!groups[account.type]) {
                groups[account.type] = [];
            }
            groups[account.type].push(account);
        });
        
        accountGroups.value = Object.keys(groups).map(type => ({
            type,
            accounts: groups[type]
        }));
    } catch (error) {
        console.error('加载会计科目列表失败:', error);
    }
};

const loadReceivables = async () => {
    receivableLoading.value = true;
    try {
        const params = {
            page: receivablePagination.page,
            per_page: receivablePagination.per_page
        };
        // 只添加非空值参数
        if (receivableSearchForm.customer_id) {
            params.customer_id = receivableSearchForm.customer_id;
        }
        if (receivableSearchForm.status) {
            params.status = receivableSearchForm.status;
        }
        if (receivableSearchForm.date_range && receivableSearchForm.date_range.length == 2) {
            params.start_date = receivableSearchForm.date_range[0];
            params.end_date = receivableSearchForm.date_range[1];
        }
        const response = await api.get('/accounts-receivable', { params });
        receivables.value = response.data.data || [];
        receivablePagination.total = response.data.total || 0;
        
        // 计算统计信息
        calculateReceivableStats();
    } catch (error) {
        ElMessage.error('加载应收账款失败');
    } finally {
        receivableLoading.value = false;
    }
};

const loadPayables = async () => {
    payableLoading.value = true;
    try {
        const params = {
            page: payablePagination.page,
            per_page: payablePagination.per_page
        };
        // 只添加非空值参数
        if (payableSearchForm.supplier_id) {
            params.supplier_id = payableSearchForm.supplier_id;
        }
        if (payableSearchForm.status) {
            params.status = payableSearchForm.status;
        }
        if (payableSearchForm.date_range && payableSearchForm.date_range.length == 2) {
            params.start_date = payableSearchForm.date_range[0];
            params.end_date = payableSearchForm.date_range[1];
        }
        const response = await api.get('/accounts-payable', { params });
        payables.value = response.data.data || [];
        payablePagination.total = response.data.total || 0;
        
        // 计算统计信息
        calculatePayableStats();
    } catch (error) {
        ElMessage.error('加载应付账款失败');
    } finally {
        payableLoading.value = false;
    }
};

const loadCustomers = async () => {
    try {
        const response = await api.get('/customers', { params: { per_page: 1000 } });
        customers.value = response.data.data || [];
    } catch (error) {
        console.error('加载客户列表失败:', error);
    }
};

const loadSuppliers = async () => {
    try {
        const response = await api.get('/suppliers', { params: { per_page: 1000 } });
        suppliers.value = response.data.data || [];
    } catch (error) {
        console.error('加载供应商列表失败:', error);
    }
};

const calculateReceivableStats = () => {
    const stats = receivables.value.reduce((acc, item) => {
        acc.total_amount += Number(item.original_amount || 0);
        acc.received_amount += Number(item.received_amount || 0);
        const balance = Number(item.remaining_amount || (item.original_amount || 0) - (item.received_amount || 0));
        acc.balance += balance;
        if (item.status === 'overdue' || (item.due_date && new Date(item.due_date) < new Date() && balance > 0)) {
            acc.overdue_count++;
        }
        return acc;
    }, { total_amount: 0, received_amount: 0, balance: 0, overdue_count: 0 });
    
    Object.assign(receivableStats, stats);
};

const calculatePayableStats = () => {
    const stats = payables.value.reduce((acc, item) => {
        acc.total_amount += Number(item.original_amount || 0);
        acc.paid_amount += Number(item.paid_amount || 0);
        const balance = Number(item.remaining_amount || (item.original_amount || 0) - (item.paid_amount || 0));
        acc.balance += balance;
        if (item.status === 'overdue' || (item.due_date && new Date(item.due_date) < new Date() && balance > 0)) {
            acc.overdue_count++;
        }
        return acc;
    }, { total_amount: 0, paid_amount: 0, balance: 0, overdue_count: 0 });
    
    Object.assign(payableStats, stats);
};

const handleVoucherSearch = () => {
    voucherPagination.page = 1;
    loadVouchers();
};

const handleVoucherReset = () => {
    voucherSearchForm.voucher_no = '';
    voucherSearchForm.date_range = null;
    handleVoucherSearch();
};

const handleVoucherSizeChange = () => {
    voucherPagination.page = 1;
    loadVouchers();
};

const handleVoucherPageChange = () => {
    loadVouchers();
};

const handleReceivableSearch = () => {
    receivablePagination.page = 1;
    loadReceivables();
};

const handleReceivableReset = () => {
    receivableSearchForm.customer_id = null;
    receivableSearchForm.status = null;
    receivableSearchForm.date_range = null;
    handleReceivableSearch();
};

const handleReceivableSizeChange = () => {
    receivablePagination.page = 1;
    loadReceivables();
};

const handleReceivablePageChange = () => {
    loadReceivables();
};

const handlePayableSearch = () => {
    payablePagination.page = 1;
    loadPayables();
};

const handlePayableReset = () => {
    payableSearchForm.supplier_id = null;
    payableSearchForm.status = null;
    payableSearchForm.date_range = null;
    handlePayableSearch();
};

const handlePayableSizeChange = () => {
    payablePagination.page = 1;
    loadPayables();
};

const handlePayablePageChange = () => {
    loadPayables();
};

const getReceivableStatusType = (row) => {
    if (row.status === 'settled') return 'success';
    if (row.status === 'overdue') return 'danger';
    if (row.status === 'partial') return 'warning';
    return 'info';
};

const getReceivableStatusText = (row) => {
    const statusMap = {
        'settled': '已结清',
        'partial': '部分收款',
        'outstanding': '未结清',
        'overdue': '逾期'
    };
    return statusMap[row.status] || row.status;
};

const getPayableStatusType = (row) => {
    if (row.status === 'settled') return 'success';
    if (row.status === 'overdue') return 'danger';
    if (row.status === 'partial') return 'warning';
    return 'info';
};

const getPayableStatusText = (row) => {
    const statusMap = {
        'settled': '已结清',
        'partial': '部分付款',
        'outstanding': '未结清',
        'overdue': '逾期'
    };
    return statusMap[row.status] || row.status;
};

const getBalanceClass = (row) => {
    const balance = Number(row.remaining_amount || (row.original_amount || 0) - (row.received_amount || row.paid_amount || 0));
    if (balance > 0 && row.due_date && new Date(row.due_date) < new Date()) {
        return 'text-danger';
    }
    return '';
};

const getDueDateClass = (row) => {
    if (row.due_date && new Date(row.due_date) < new Date()) {
        const balance = Number(row.remaining_amount || (row.original_amount || 0) - (row.received_amount || row.paid_amount || 0));
        if (balance > 0) {
            return 'text-danger';
        }
    }
    return '';
};

const handleViewReceivable = async (row) => {
    if (receivableViewLoadingId.value !== null) {
        return;
    }
    
    receivableViewLoadingId.value = row.id;
    receivableDetailVisible.value = true;
    currentReceivable.value = null;
    
    try {
        const response = await api.get(`/accounts-receivable/${row.id}`);
        currentReceivable.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载应收账款详情失败');
        receivableDetailVisible.value = false;
    } finally {
        receivableViewLoadingId.value = null;
    }
};

const handleViewPayable = async (row) => {
    if (payableViewLoadingId.value !== null) {
        return;
    }
    
    payableViewLoadingId.value = row.id;
    payableDetailVisible.value = true;
    currentPayable.value = null;
    
    try {
        const response = await api.get(`/accounts-payable/${row.id}`);
        currentPayable.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载应付账款详情失败');
        payableDetailVisible.value = false;
    } finally {
        payableViewLoadingId.value = null;
    }
};

const handleReceivePayment = async (row) => {
    try {
        const response = await api.get(`/accounts-receivable/${row.id}`);
        const receivable = response.data.data;
        currentReceivable.value = receivable;
        const remaining = receivable.remaining_amount || (receivable.original_amount - (receivable.received_amount || 0));
        receiveForm.received_amount = parseFloat(remaining) || 0;
        receiveForm.max_amount = parseFloat(remaining) || 0;
        receiveDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载应收账款信息失败');
    }
};

const handleMakePayment = async (row) => {
    try {
        const response = await api.get(`/accounts-payable/${row.id}`);
        const payable = response.data.data;
        currentPayable.value = payable;
        const remaining = payable.remaining_amount || (payable.original_amount - (payable.paid_amount || 0));
        paymentForm.paid_amount = parseFloat(remaining) || 0;
        paymentForm.max_amount = parseFloat(remaining) || 0;
        paymentDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载应付账款信息失败');
    }
};

const handleSubmitReceive = async () => {
    if (!currentReceivable.value) return;
    
    receiveSubmitLoading.value = true;
    try {
        await api.post(`/accounts-receivable/${currentReceivable.value.id}/receive-payment`, {
            amount: receiveForm.received_amount
        });
        ElMessage.success('收款成功');
        receiveDialogVisible.value = false;
        loadReceivables();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || '收款失败');
    } finally {
        receiveSubmitLoading.value = false;
    }
};

const handleSubmitPayment = async () => {
    if (!currentPayable.value) return;
    
    paymentSubmitLoading.value = true;
    try {
        await api.post(`/accounts-payable/${currentPayable.value.id}/make-payment`, {
            amount: paymentForm.paid_amount
        });
        ElMessage.success('付款成功');
        paymentDialogVisible.value = false;
        loadPayables();
    } catch (error) {
        ElMessage.error(error.response?.data?.message || '付款失败');
    } finally {
        paymentSubmitLoading.value = false;
    }
};

const handleAddVoucher = () => {
    voucherDialogTitle.value = '新增凭证';
    Object.assign(voucherForm, {
        id: null,
        voucher_date: new Date().toISOString().split('T')[0],
        type: 'general',
        attachment_count: 0,
        remark: '',
        items: []
    });
    voucherDialogVisible.value = true;
};

const handleEditVoucher = async (row) => {
    try {
        const response = await api.get(`/accounting-vouchers/${row.id}`);
        const voucher = response.data.data;
        if (voucher.status != 'draft') {
            ElMessage.warning('只能编辑草稿状态的凭证');
            return;
        }
        voucherDialogTitle.value = '编辑凭证';
        Object.assign(voucherForm, {
            id: voucher.id,
            voucher_date: voucher.voucher_date,
            type: voucher.type || 'general',
            attachment_count: voucher.attachment_count || 0,
            remark: voucher.remark || '',
            items: voucher.items.map(item => ({
                account_id: item.account_id,
                account: item.account,
                direction: item.direction,
                amount: item.amount,
                summary: item.summary || '',
                reference_type: item.reference_type || '',
                reference_id: item.reference_id,
                reference_no: item.reference_no || '',
                sequence: item.sequence || 0
            }))
        });
        voucherDialogVisible.value = true;
    } catch (error) {
        ElMessage.error('加载凭证失败');
    }
};

const handleViewVoucher = async (row) => {
    // 防止重复点击
    if (voucherViewLoadingId.value !== null) {
        return;
    }
    
    voucherViewLoadingId.value = row.id;
    voucherDetailLoading.value = true;
    voucherDetailVisible.value = true;
    currentVoucher.value = null;
    
    try {
        const response = await api.get(`/accounting-vouchers/${row.id}`);
        currentVoucher.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载凭证详情失败');
        voucherDetailVisible.value = false;
    } finally {
        voucherDetailLoading.value = false;
        voucherViewLoadingId.value = null;
    }
};

const handleDeleteVoucher = async (row) => {
    try {
        await ElMessageBox.confirm('确定要删除该凭证吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.delete(`/accounting-vouchers/${row.id}`);
        ElMessage.success('删除成功');
        loadVouchers();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '删除失败');
        }
    }
};

const handleAddVoucherItem = () => {
    voucherForm.items.push({
        account_id: null,
        account: null,
        direction: 'debit',
        amount: 0,
        summary: '',
        reference_type: '',
        reference_id: null,
        reference_no: '',
        sequence: voucherForm.items.length + 1
    });
};

const handleRemoveVoucherItem = (index) => {
    voucherForm.items.splice(index, 1);
    voucherForm.items.forEach((item, idx) => {
        item.sequence = idx + 1;
    });
};

const handleVoucherItemAccountChange = (index) => {
    const accountId = voucherForm.items[index].account_id;
    const account = accounts.value.find(a => a.id == accountId);
    if (account) {
        voucherForm.items[index].account = account;
    }
};

const handleVoucherItemChange = () => {
    // 触发计算
};

const handleSubmitVoucher = async () => {
    if (!voucherFormRef.value) return;
    
    await voucherFormRef.value.validate(async (valid) => {
        if (valid) {
            if (voucherForm.items.length < 2) {
                ElMessage.warning('至少添加两条凭证明细');
                return;
            }
            if (!isVoucherBalanced.value) {
                ElMessage.warning('借贷不平衡，请检查金额');
                return;
            }
            voucherSubmitLoading.value = true;
            try {
                const data = {
                    ...voucherForm,
                    items: voucherForm.items.map(item => ({
                        account_id: item.account_id,
                        direction: item.direction,
                        amount: item.amount,
                        summary: item.summary || null,
                        reference_type: item.reference_type || null,
                        reference_id: item.reference_id || null,
                        reference_no: item.reference_no || null,
                        sequence: item.sequence || 0
                    }))
                };
                // 清理空值
                if (!data.remark) data.remark = null;
                if (!data.attachment_count) data.attachment_count = 0;
                if (voucherForm.id) {
                    await api.put(`/accounting-vouchers/${voucherForm.id}`, data);
                    ElMessage.success('更新成功');
                } else {
                    await api.post('/accounting-vouchers', data);
                    ElMessage.success('创建成功');
                }
                voucherDialogVisible.value = false;
                loadVouchers();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                voucherSubmitLoading.value = false;
            }
        }
    });
};

const handleVoucherDialogClose = () => {
    voucherFormRef.value?.resetFields();
};

const handlePostVoucher = async (row) => {
    try {
        await ElMessageBox.confirm('确定要过账该凭证吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/accounting-vouchers/${row.id}/post`);
        ElMessage.success('过账成功');
        loadVouchers();
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error('过账失败');
        }
    }
};

onMounted(() => {
    loadVouchers();
    loadReceivables();
    loadPayables();
    loadAccounts();
    loadCustomers();
    loadSuppliers();
});
</script>

<style scoped>
.text-danger {
    color: #f56c6c;
    font-weight: 600;
}

:deep(.el-statistic) {
    text-align: center;
}

:deep(.el-statistic__head) {
    color: var(--color-text-muted);
    font-size: 14px;
    margin-bottom: 8px;
}

:deep(.el-statistic__number) {
    color: var(--color-text-primary);
    font-weight: 600;
    font-size: 24px;
}
</style>

