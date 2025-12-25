<template>
    <div class="page-container">
        <div class="page-card">
            <div class="page-header">
                <h2 class="page-title text-primary">库存管理</h2>
                <div class="page-actions">
                    <el-button type="success" @click="handleStockIn" class="interactive">
                        <ArrowDownCircle :size="16" style="margin-right: 6px;" />
                        入库
                    </el-button>
                    <el-button type="warning" @click="handleStockOut" class="interactive">
                        <ArrowUpCircle :size="16" style="margin-right: 6px;" />
                        出库
                    </el-button>
                    <el-button type="info" @click="handleTransfer" class="interactive">
                        <ArrowRightLeft :size="16" style="margin-right: 6px;" />
                        调拨
                    </el-button>
                    <el-button type="primary" @click="handleStocktake" class="interactive" v-if="activeTab === 'inventory'">
                        <ClipboardList :size="16" style="margin-right: 6px;" />
                        盘点
                    </el-button>
                    <el-button type="primary" @click="handleStocktake" class="interactive" v-if="activeTab === 'stocktakes'">
                        <Plus :size="16" style="margin-right: 6px;" />
                        创建盘点单
                    </el-button>
                </div>
            </div>

            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <el-tab-pane label="库存列表" name="inventory">
                    <el-form :inline="true" :model="searchForm" class="search-form-modern">
                <el-form-item label="商品">
                    <el-input v-model="searchForm.search" placeholder="商品名称/SKU" clearable />
                </el-form-item>
                <el-form-item label="仓库">
                    <el-select v-model="searchForm.warehouse_id" placeholder="全部" clearable style="width: 200px">
                        <el-option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :label="warehouse.name"
                            :value="warehouse.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="库存状态">
                    <el-select v-model="searchForm.stock_status" placeholder="全部" clearable style="width: 150px">
                        <el-option label="充足" value="sufficient" />
                        <el-option label="不足" value="low" />
                        <el-option label="缺货" value="out" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <div class="modern-table" style="margin: 0 24px;">
                <el-table :data="inventory" v-loading="loading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="product.name" label="商品名称" />
                <el-table-column prop="product.sku" label="SKU" width="120" />
                <el-table-column prop="warehouse.name" label="仓库" />
                <el-table-column prop="warehouse_location?.name" label="库位" />
                <el-table-column prop="quantity" label="库存数量" width="120">
                    <template #default="{ row }">
                        <span :style="{ color: getStockColor(row.quantity, row.product?.min_stock || 0) }">
                            {{ row.quantity }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column prop="product.min_stock" label="最低库存" width="120" />
                <el-table-column prop="product.unit?.name" label="单位" width="80" />
                <el-table-column label="库存状态" width="100">
                    <template #default="{ row }">
                        <span 
                            :class="getStockStatusClass(row.quantity, row.product?.min_stock || 0)"
                            class="status-badge"
                        >
                            {{ getStockStatus(row.quantity, row.product?.min_stock || 0) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="250" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" @click="handleView(row)" :loading="viewLoadingId === row.id" :disabled="viewLoadingId !== null" class="interactive">查看</el-button>
                        <el-button type="info" size="small" @click="handleTransactions(row)" class="interactive">流水</el-button>
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
                </el-tab-pane>

                <el-tab-pane label="盘点单" name="stocktakes">
                    <el-form :inline="true" :model="stocktakeSearchForm" class="search-form-modern">
                        <el-form-item label="盘点单号">
                            <el-input v-model="stocktakeSearchForm.stocktake_no" placeholder="盘点单号" clearable />
                        </el-form-item>
                        <el-form-item label="仓库">
                            <el-select v-model="stocktakeSearchForm.warehouse_id" placeholder="全部" clearable style="width: 200px">
                                <el-option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :label="warehouse.name"
                                    :value="warehouse.id"
                                />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="stocktakeSearchForm.status" placeholder="全部" clearable style="width: 150px">
                                <el-option label="草稿" value="draft" />
                                <el-option label="盘点中" value="counting" />
                                <el-option label="已完成" value="completed" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="handleStocktakeSearch">查询</el-button>
                            <el-button @click="handleStocktakeReset">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <div class="modern-table" style="margin: 0 24px;">
                        <el-table :data="stocktakes" v-loading="stocktakeListLoading" style="width: 100%">
                            <el-table-column prop="id" label="ID" width="80" />
                            <el-table-column prop="stocktake_no" label="盘点单号" width="180" />
                            <el-table-column prop="warehouse.name" label="仓库" />
                            <el-table-column prop="stocktake_date" label="盘点日期" width="120" />
                            <el-table-column label="状态" width="100">
                                <template #default="{ row }">
                                    <el-tag :type="getStocktakeStatusType(row.status)">
                                        {{ getStocktakeStatusText(row.status) }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column prop="creator.name" label="创建人" width="120" />
                            <el-table-column prop="created_at" label="创建时间" width="180" />
                            <el-table-column prop="completer.name" label="完成人" width="120" v-if="false" />
                            <el-table-column prop="completed_at" label="完成时间" width="180" />
                            <el-table-column label="操作" width="200" fixed="right">
                                <template #default="{ row }">
                                    <el-button type="primary" size="small" @click="handleViewStocktake(row)" class="interactive">查看</el-button>
                                    <el-button 
                                        type="success" 
                                        size="small" 
                                        @click="handleCompleteStocktake(row)" 
                                        v-if="row.status === 'counting'"
                                        class="interactive"
                                    >
                                        完成
                                    </el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>

                    <div class="modern-pagination">
                        <el-pagination
                            v-model:current-page="stocktakePagination.page"
                            v-model:page-size="stocktakePagination.per_page"
                            :total="stocktakePagination.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next, jumper"
                            @size-change="handleStocktakeSizeChange"
                            @current-change="handleStocktakePageChange"
                        />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>

        <!-- 入库对话框 -->
        <el-dialog v-model="stockInVisible" title="入库操作" width="600px">
            <el-form :model="stockInForm" :rules="stockInRules" ref="stockInFormRef" label-width="100px">
                <el-form-item label="入库类型" prop="type">
                    <el-select v-model="stockInForm.type" placeholder="请选择入库类型">
                        <el-option label="采购入库" value="purchase" />
                        <el-option label="生产入库" value="production" />
                        <el-option label="调拨入库" value="transfer" />
                        <el-option label="其他入库" value="other" />
                    </el-select>
                </el-form-item>
                <el-form-item label="商品" prop="product_id">
                    <el-select v-model="stockInForm.product_id" filterable placeholder="请选择商品" @change="handleProductChange">
                        <el-option
                            v-for="product in products"
                            :key="product.id"
                            :label="`${product.name} (${product.sku})`"
                            :value="product.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="仓库" prop="warehouse_id">
                    <el-select v-model="stockInForm.warehouse_id" placeholder="请选择仓库" @change="handleWarehouseChange">
                        <el-option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :label="warehouse.name"
                            :value="warehouse.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="库位" prop="warehouse_location_id">
                    <el-select v-model="stockInForm.warehouse_location_id" placeholder="请选择库位">
                        <el-option
                            v-for="location in locations"
                            :key="location.id"
                            :label="location.name"
                            :value="location.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="数量" prop="quantity">
                    <el-input-number v-model="stockInForm.quantity" :min="0" :step="1" :precision="0" :controls="true" style="width: 100%" />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="stockInForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="stockInVisible = false">取消</el-button>
                <el-button type="primary" @click="submitStockIn" :loading="stockInLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 出库对话框 -->
        <el-dialog v-model="stockOutVisible" title="出库操作" width="600px">
            <el-form :model="stockOutForm" :rules="stockOutRules" ref="stockOutFormRef" label-width="100px">
                <el-form-item label="出库类型" prop="type">
                    <el-select v-model="stockOutForm.type" placeholder="请选择出库类型">
                        <el-option label="销售出库" value="sales" />
                        <el-option label="生产领料" value="production" />
                        <el-option label="调拨出库" value="transfer" />
                        <el-option label="其他出库" value="other" />
                    </el-select>
                </el-form-item>
                <el-form-item label="商品" prop="product_id">
                    <el-select v-model="stockOutForm.product_id" filterable placeholder="请选择商品" @change="handleProductChangeOut">
                        <el-option
                            v-for="product in products"
                            :key="product.id"
                            :label="`${product.name} (${product.sku})`"
                            :value="product.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="仓库" prop="warehouse_id">
                    <el-select v-model="stockOutForm.warehouse_id" placeholder="请选择仓库" @change="handleWarehouseChangeOut">
                        <el-option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :label="warehouse.name"
                            :value="warehouse.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="库位" prop="warehouse_location_id">
                    <el-select v-model="stockOutForm.warehouse_location_id" placeholder="请选择库位">
                        <el-option
                            v-for="location in locationsOut"
                            :key="location.id"
                            :label="location.name"
                            :value="location.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="数量" prop="quantity">
                    <el-input-number v-model="stockOutForm.quantity" :min="0" :step="1" :precision="0" :controls="true" style="width: 100%" />
                    <div v-if="currentStock" style="margin-top: 5px; color: #909399; font-size: 12px;">
                        当前库存: {{ currentStock.quantity }} {{ currentStock.product?.unit?.name || '' }}
                    </div>
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="stockOutForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="stockOutVisible = false">取消</el-button>
                <el-button type="primary" @click="submitStockOut" :loading="stockOutLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 调拨对话框 -->
        <el-dialog v-model="transferVisible" title="库存调拨" width="600px">
            <el-form :model="transferForm" :rules="transferRules" ref="transferFormRef" label-width="100px">
                <el-form-item label="商品" prop="product_id">
                    <el-select v-model="transferForm.product_id" filterable placeholder="请选择商品" @change="handleProductChangeTransfer">
                        <el-option
                            v-for="product in products"
                            :key="product.id"
                            :label="`${product.name} (${product.sku})`"
                            :value="product.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="调出仓库" prop="from_warehouse_id">
                    <el-select v-model="transferForm.from_warehouse_id" placeholder="请选择调出仓库" @change="handleFromWarehouseChange">
                        <el-option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :label="warehouse.name"
                            :value="warehouse.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="调出库位" prop="from_warehouse_location_id">
                    <el-select v-model="transferForm.from_warehouse_location_id" placeholder="请选择调出库位">
                        <el-option
                            v-for="location in fromLocations"
                            :key="location.id"
                            :label="location.name"
                            :value="location.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="调入仓库" prop="to_warehouse_id">
                    <el-select v-model="transferForm.to_warehouse_id" placeholder="请选择调入仓库" @change="handleToWarehouseChange">
                        <el-option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :label="warehouse.name"
                            :value="warehouse.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="调入库位" prop="to_warehouse_location_id">
                    <el-select v-model="transferForm.to_warehouse_location_id" placeholder="请选择调入库位">
                        <el-option
                            v-for="location in toLocations"
                            :key="location.id"
                            :label="location.name"
                            :value="location.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="数量" prop="quantity">
                    <el-input-number v-model="transferForm.quantity" :min="0" :step="1" :precision="0" :controls="true" style="width: 100%" />
                    <div v-if="fromStock" style="margin-top: 5px; color: #909399; font-size: 12px;">
                        调出库存: {{ fromStock.quantity }} {{ fromStock.product?.unit?.name || '' }}
                    </div>
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="transferForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="transferVisible = false">取消</el-button>
                <el-button type="primary" @click="submitTransfer" :loading="transferLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 盘点对话框 -->
        <el-dialog v-model="stocktakeVisible" title="创建盘点单" width="600px">
            <el-form :model="stocktakeForm" :rules="stocktakeRules" ref="stocktakeFormRef" label-width="100px">
                <el-form-item label="盘点仓库" prop="warehouse_id">
                    <el-select v-model="stocktakeForm.warehouse_id" placeholder="请选择仓库">
                        <el-option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :label="warehouse.name"
                            :value="warehouse.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="盘点日期" prop="stocktake_date">
                    <el-date-picker v-model="stocktakeForm.stocktake_date" type="date" placeholder="选择日期" value-format="YYYY-MM-DD" style="width: 100%" />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="stocktakeForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="stocktakeVisible = false">取消</el-button>
                <el-button type="primary" @click="submitStocktake" :loading="stocktakeLoading">确定</el-button>
            </template>
        </el-dialog>

        <!-- 详情对话框 -->
        <el-dialog v-model="detailVisible" title="库存详情" width="800px" :close-on-click-modal="false">
            <div v-loading="detailLoading">
                <el-descriptions :column="2" border v-if="currentInventory">
                <el-descriptions-item label="商品名称">{{ currentInventory.product?.name }}</el-descriptions-item>
                <el-descriptions-item label="SKU">{{ currentInventory.product?.sku }}</el-descriptions-item>
                <el-descriptions-item label="仓库">{{ currentInventory.warehouse?.name }}</el-descriptions-item>
                <el-descriptions-item label="库位">{{ currentInventory.warehouse_location?.name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="库存数量">{{ currentInventory.quantity }}</el-descriptions-item>
                <el-descriptions-item label="单位">{{ currentInventory.product?.unit?.name }}</el-descriptions-item>
                <el-descriptions-item label="最低库存">{{ currentInventory.product?.min_stock || 0 }}</el-descriptions-item>
                <el-descriptions-item label="库存状态">
                    <el-tag :type="getStockStatusType(currentInventory.quantity, currentInventory.product?.min_stock || 0)">
                        {{ getStockStatus(currentInventory.quantity, currentInventory.product?.min_stock || 0) }}
                    </el-tag>
                </el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>

        <!-- 交易流水对话框 -->
        <el-dialog v-model="transactionsVisible" title="库存交易流水" width="1000px">
            <el-table :data="transactions" v-loading="transactionsLoading" style="width: 100%">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="transaction_type" label="交易类型" width="120">
                    <template #default="{ row }">
                        <el-tag :type="getTransactionTypeTag(row.transaction_type)">
                            {{ getTransactionTypeText(row.transaction_type) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="quantity" label="数量" width="120" />
                <el-table-column prop="warehouse.name" label="仓库" />
                <el-table-column prop="warehouse_location?.name" label="库位" />
                <el-table-column prop="reference_type" label="关联类型" width="120" />
                <el-table-column prop="reference_id" label="关联ID" width="100" />
                <el-table-column prop="created_at" label="交易时间" width="180" />
                <el-table-column prop="remark" label="备注" />
            </el-table>
            <el-pagination
                v-model:current-page="transactionsPagination.page"
                v-model:page-size="transactionsPagination.per_page"
                :total="transactionsPagination.total"
                :page-sizes="[10, 20, 50]"
                layout="total, sizes, prev, pager, next"
                @size-change="handleTransactionsSizeChange"
                @current-change="handleTransactionsPageChange"
                style="margin-top: 20px;"
            />
        </el-dialog>

        <!-- 盘点单详情对话框 -->
        <el-dialog v-model="stocktakeDetailVisible" title="盘点单详情" width="1200px" :close-on-click-modal="false">
            <div v-loading="stocktakeDetailLoading">
                <el-descriptions :column="2" border v-if="currentStocktake" style="margin-bottom: 20px;">
                    <el-descriptions-item label="盘点单号">{{ currentStocktake.stocktake_no }}</el-descriptions-item>
                    <el-descriptions-item label="仓库">{{ currentStocktake.warehouse?.name }}</el-descriptions-item>
                    <el-descriptions-item label="盘点日期">{{ currentStocktake.stocktake_date }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getStocktakeStatusType(currentStocktake.status)">
                            {{ getStocktakeStatusText(currentStocktake.status) }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="创建人">{{ currentStocktake.creator?.name }}</el-descriptions-item>
                    <el-descriptions-item label="创建时间">{{ currentStocktake.created_at }}</el-descriptions-item>
                    <el-descriptions-item label="完成人" v-if="currentStocktake.completer">{{ currentStocktake.completer?.name }}</el-descriptions-item>
                    <el-descriptions-item label="完成时间" v-if="currentStocktake.completed_at">{{ currentStocktake.completed_at }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentStocktake.remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <el-divider>
                    <span>盘点明细</span>
                    <el-button 
                        type="primary" 
                        size="small" 
                        @click="handleAddStocktakeItem" 
                        v-if="currentStocktake && (currentStocktake.status === 'draft' || currentStocktake.status === 'counting')"
                        style="margin-left: 10px;"
                    >
                        <Plus :size="14" style="margin-right: 4px;" />
                        添加明细
                    </el-button>
                </el-divider>
                <el-table :data="currentStocktake?.items || []" border style="width: 100%">
                    <el-table-column prop="product.name" label="商品名称" />
                    <el-table-column prop="product.sku" label="SKU" width="120" />
                    <el-table-column prop="location.name" label="库位" width="120" />
                    <el-table-column prop="book_quantity" label="账面数量" width="120" />
                    <el-table-column prop="actual_quantity" label="实盘数量" width="120" />
                    <el-table-column label="差异数量" width="120">
                        <template #default="{ row }">
                            <span :style="{ color: row.difference_quantity > 0 ? '#67C23A' : row.difference_quantity < 0 ? '#F56C6C' : '#909399' }">
                                {{ row.difference_quantity > 0 ? '+' : '' }}{{ row.difference_quantity }}
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="unit_cost" label="单位成本" width="120" />
                    <el-table-column prop="remark" label="备注" />
                </el-table>
            </div>
        </el-dialog>

        <!-- 添加盘点明细对话框 -->
        <el-dialog v-model="addItemVisible" title="添加盘点明细" width="600px">
            <el-form :model="stocktakeItemForm" :rules="stocktakeItemRules" ref="stocktakeItemFormRef" label-width="100px">
                <el-form-item label="商品" prop="product_id">
                    <el-select v-model="stocktakeItemForm.product_id" filterable placeholder="请选择商品" @change="handleItemProductChange" style="width: 100%">
                        <el-option
                            v-for="product in products"
                            :key="product.id"
                            :label="`${product.name} (${product.sku})`"
                            :value="product.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="库位" prop="location_id">
                    <el-select v-model="stocktakeItemForm.location_id" placeholder="请选择库位（可选）" clearable style="width: 100%">
                        <el-option
                            v-for="location in stocktakeLocations"
                            :key="location.id"
                            :label="location.name"
                            :value="location.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="账面数量">
                    <el-input-number v-model="stocktakeItemForm.book_quantity" :disabled="true" style="width: 100%" />
                    <div style="margin-top: 5px; color: #909399; font-size: 12px;">
                        系统自动获取当前库存数量
                    </div>
                </el-form-item>
                <el-form-item label="实盘数量" prop="actual_quantity">
                    <el-input-number v-model="stocktakeItemForm.actual_quantity" :min="0" :step="1" :precision="0" :controls="true" style="width: 100%" />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="stocktakeItemForm.remark" type="textarea" :rows="3" placeholder="请输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="addItemVisible = false">取消</el-button>
                <el-button type="primary" @click="submitStocktakeItem" :loading="addItemLoading">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { ArrowDownCircle, ArrowUpCircle, ArrowRightLeft, ClipboardList } from 'lucide-vue-next';
import api from '../../services/api';

const loading = ref(false);
const inventory = ref([]);
const warehouses = ref([]);
const products = ref([]);
const locations = ref([]);
const locationsOut = ref([]);
const fromLocations = ref([]);
const toLocations = ref([]);
const currentStock = ref(null);
const fromStock = ref(null);
const transactions = ref([]);
const transactionsLoading = ref(false);

const stockInVisible = ref(false);
const stockOutVisible = ref(false);
const transferVisible = ref(false);
const stocktakeVisible = ref(false);
const detailVisible = ref(false);
const detailLoading = ref(false);
const viewLoadingId = ref(null);
const transactionsVisible = ref(false);
const currentInventory = ref(null);
const activeTab = ref('inventory');
const stocktakes = ref([]);
const stocktakeListLoading = ref(false);
const stocktakeDetailVisible = ref(false);
const stocktakeDetailLoading = ref(false);
const currentStocktake = ref(null);
const addItemVisible = ref(false);
const addItemLoading = ref(false);
const stocktakeItemFormRef = ref(null);
const stocktakeLocations = ref([]);

const stockInLoading = ref(false);
const stockOutLoading = ref(false);
const transferLoading = ref(false);
const stocktakeLoading = ref(false);

const stockInFormRef = ref(null);
const stockOutFormRef = ref(null);
const transferFormRef = ref(null);
const stocktakeFormRef = ref(null);

const searchForm = reactive({
    search: '',
    warehouse_id: null,
    stock_status: null
});

const pagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const stockInForm = reactive({
    type: 'purchase',
    product_id: null,
    warehouse_id: null,
    warehouse_location_id: null,
    quantity: null,
    remark: ''
});

const stockOutForm = reactive({
    type: 'sales',
    product_id: null,
    warehouse_id: null,
    warehouse_location_id: null,
    quantity: null,
    remark: ''
});

const transferForm = reactive({
    product_id: null,
    from_warehouse_id: null,
    from_warehouse_location_id: null,
    to_warehouse_id: null,
    to_warehouse_location_id: null,
    quantity: null,
    remark: ''
});

const stocktakeForm = reactive({
    warehouse_id: null,
    stocktake_date: new Date().toISOString().split('T')[0],
    remark: ''
});

const stocktakeSearchForm = reactive({
    stocktake_no: '',
    warehouse_id: null,
    status: null
});

const stocktakePagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const stocktakeItemForm = reactive({
    product_id: null,
    location_id: null,
    actual_quantity: null,
    remark: '',
    book_quantity: 0
});

const stocktakeItemRules = {
    product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
    actual_quantity: [
        { required: true, message: '请输入实盘数量', trigger: 'blur' },
        { type: 'number', min: 0, message: '实盘数量不能小于0', trigger: 'blur' }
    ]
};

const transactionsPagination = reactive({
    page: 1,
    per_page: 10,
    total: 0
});

const stockInRules = {
    type: [{ required: true, message: '请选择入库类型', trigger: 'change' }],
    product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    quantity: [
        { required: true, message: '请输入数量', trigger: 'blur' },
        { type: 'number', min: 1, message: '数量必须大于0', trigger: 'blur' }
    ]
};

const stockOutRules = {
    type: [{ required: true, message: '请选择出库类型', trigger: 'change' }],
    product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    quantity: [
        { required: true, message: '请输入数量', trigger: 'blur' },
        { type: 'number', min: 1, message: '数量必须大于0', trigger: 'blur' }
    ]
};

const transferRules = {
    product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
    from_warehouse_id: [{ required: true, message: '请选择调出仓库', trigger: 'change' }],
    to_warehouse_id: [{ required: true, message: '请选择调入仓库', trigger: 'change' }],
    quantity: [
        { required: true, message: '请输入数量', trigger: 'blur' },
        { type: 'number', min: 1, message: '数量必须大于0', trigger: 'blur' }
    ]
};

const stocktakeRules = {
    warehouse_id: [{ required: true, message: '请选择仓库', trigger: 'change' }],
    stocktake_date: [{ required: true, message: '请选择盘点日期', trigger: 'change' }]
};

const getStockColor = (quantity, minStock) => {
    if (quantity <= 0) return '#F56C6C';
    if (quantity < minStock) return '#E6A23C';
    return '#67C23A';
};

const getStockStatus = (quantity, minStock) => {
    if (quantity <= 0) return '缺货';
    if (quantity < minStock) return '不足';
    return '充足';
};

const getStockStatusType = (quantity, minStock) => {
    if (quantity <= 0) return 'danger';
    if (quantity < minStock) return 'warning';
    return 'success';
};

const getStockStatusClass = (quantity, minStock) => {
    if (quantity <= 0) return 'badge-muted';
    if (quantity < minStock) return 'badge-warning';
    return 'badge-success';
};

const getTransactionTypeText = (type) => {
    const typeMap = {
        'stock_in': '入库',
        'stock_out': '出库',
        'transfer': '调拨',
        'adjust': '调整'
    };
    return typeMap[type] || type;
};

const getTransactionTypeTag = (type) => {
    const tagMap = {
        'stock_in': 'success',
        'stock_out': 'warning',
        'transfer': 'info',
        'adjust': 'danger'
    };
    return tagMap[type] || 'info';
};

const loadInventory = async () => {
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
        if (searchForm.warehouse_id) {
            params.warehouse_id = searchForm.warehouse_id;
        }
        if (searchForm.stock_status) {
            params.stock_status = searchForm.stock_status;
        }
        const response = await api.get('/inventory', { params });
        inventory.value = response.data.data;
        pagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载库存列表失败');
    } finally {
        loading.value = false;
    }
};

const loadWarehouses = async () => {
    try {
        const response = await api.get('/warehouses');
        warehouses.value = response.data.data;
    } catch (error) {
        console.error('加载仓库列表失败:', error);
    }
};

const loadProducts = async () => {
    try {
        const response = await api.get('/products', { params: { per_page: 1000 } });
        products.value = response.data.data;
    } catch (error) {
        console.error('加载商品列表失败:', error);
    }
};

const loadLocations = async (warehouseId) => {
    try {
        const response = await api.get(`/warehouses/${warehouseId}`);
        return response.data.data.locations || [];
    } catch (error) {
        return [];
    }
};

const handleSearch = () => {
    pagination.page = 1;
    loadInventory();
};

const handleReset = () => {
    searchForm.search = '';
    searchForm.warehouse_id = null;
    searchForm.stock_status = null;
    handleSearch();
};

const handleStockIn = () => {
    stockInForm.type = 'purchase';
    stockInForm.product_id = null;
    stockInForm.warehouse_id = null;
    stockInForm.warehouse_location_id = null;
    stockInForm.quantity = null;
    stockInForm.remark = '';
    locations.value = [];
    stockInVisible.value = true;
};

const handleStockOut = () => {
    stockOutForm.type = 'sales';
    stockOutForm.product_id = null;
    stockOutForm.warehouse_id = null;
    stockOutForm.warehouse_location_id = null;
    stockOutForm.quantity = null;
    stockOutForm.remark = '';
    locationsOut.value = [];
    currentStock.value = null;
    stockOutVisible.value = true;
};

const handleTransfer = () => {
    transferForm.product_id = null;
    transferForm.from_warehouse_id = null;
    transferForm.from_warehouse_location_id = null;
    transferForm.to_warehouse_id = null;
    transferForm.to_warehouse_location_id = null;
    transferForm.quantity = null;
    transferForm.remark = '';
    fromLocations.value = [];
    toLocations.value = [];
    fromStock.value = null;
    transferVisible.value = true;
};

const handleStocktake = () => {
    stocktakeForm.warehouse_id = null;
    stocktakeForm.stocktake_date = new Date().toISOString().split('T')[0];
    stocktakeForm.remark = '';
    stocktakeVisible.value = true;
};

const handleProductChange = () => {
    stockInForm.warehouse_location_id = null;
};

const handleWarehouseChange = async () => {
    stockInForm.warehouse_location_id = null;
    if (stockInForm.warehouse_id) {
        locations.value = await loadLocations(stockInForm.warehouse_id);
    }
};

const handleProductChangeOut = async () => {
    stockOutForm.warehouse_location_id = null;
    currentStock.value = null;
    if (stockOutForm.product_id && stockOutForm.warehouse_id) {
        await checkStock();
    }
};

const handleWarehouseChangeOut = async () => {
    stockOutForm.warehouse_location_id = null;
    currentStock.value = null;
    if (stockOutForm.warehouse_id) {
        locationsOut.value = await loadLocations(stockOutForm.warehouse_id);
        if (stockOutForm.product_id) {
            await checkStock();
        }
    }
};

const checkStock = async () => {
    try {
        const response = await api.get('/inventory', {
            params: {
                product_id: stockOutForm.product_id,
                warehouse_id: stockOutForm.warehouse_id,
                warehouse_location_id: stockOutForm.warehouse_location_id
            }
        });
        if (response.data.data && response.data.data.length > 0) {
            currentStock.value = response.data.data[0];
        } else {
            currentStock.value = null;
        }
    } catch (error) {
        currentStock.value = null;
    }
};

const handleProductChangeTransfer = async () => {
    transferForm.from_warehouse_location_id = null;
    transferForm.to_warehouse_location_id = null;
    fromStock.value = null;
    if (transferForm.product_id && transferForm.from_warehouse_id) {
        await checkFromStock();
    }
};

const handleFromWarehouseChange = async () => {
    transferForm.from_warehouse_location_id = null;
    fromStock.value = null;
    if (transferForm.from_warehouse_id) {
        fromLocations.value = await loadLocations(transferForm.from_warehouse_id);
        if (transferForm.product_id) {
            await checkFromStock();
        }
    }
};

const handleToWarehouseChange = async () => {
    transferForm.to_warehouse_location_id = null;
    if (transferForm.to_warehouse_id) {
        toLocations.value = await loadLocations(transferForm.to_warehouse_id);
    }
};

const checkFromStock = async () => {
    try {
        const response = await api.get('/inventory', {
            params: {
                product_id: transferForm.product_id,
                warehouse_id: transferForm.from_warehouse_id,
                warehouse_location_id: transferForm.from_warehouse_location_id
            }
        });
        if (response.data.data && response.data.data.length > 0) {
            fromStock.value = response.data.data[0];
        } else {
            fromStock.value = null;
        }
    } catch (error) {
        fromStock.value = null;
    }
};

const submitStockIn = async () => {
    if (!stockInFormRef.value) return;
    await stockInFormRef.value.validate(async (valid) => {
        if (valid) {
            stockInLoading.value = true;
            try {
                // 库存管理页面的直接入库操作统一设置为"其他入库"
                // 真正的采购入库、生产入库等应通过对应的业务模块操作
                const submitData = {
                    ...stockInForm,
                    location_id: stockInForm.warehouse_location_id,
                    reference_type: 'other', // 直接入库操作统一为"其他"
                    reference_id: null,
                    reference_no: null
                };
                
                // 删除不需要的字段
                delete submitData.warehouse_location_id;
                delete submitData.type;
                
                await api.post('/inventory/stock-in', submitData);
                ElMessage.success('入库成功');
                stockInVisible.value = false;
                loadInventory();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '入库失败');
            } finally {
                stockInLoading.value = false;
            }
        }
    });
};

const submitStockOut = async () => {
    if (!stockOutFormRef.value) return;
    await stockOutFormRef.value.validate(async (valid) => {
        if (valid) {
            if (currentStock.value && stockOutForm.quantity > currentStock.value.quantity) {
                ElMessage.warning('出库数量不能大于当前库存');
                return;
            }
            stockOutLoading.value = true;
            try {
                // 库存管理页面的直接出库操作统一设置为"其他出库"
                // 真正的销售出库、生产领料等应通过对应的业务模块操作
                const submitData = {
                    ...stockOutForm,
                    location_id: stockOutForm.warehouse_location_id,
                    reference_type: 'other', // 直接出库操作统一为"其他"
                    reference_id: null,
                    reference_no: null
                };
                
                // 删除不需要的字段
                delete submitData.warehouse_location_id;
                delete submitData.type;
                
                await api.post('/inventory/stock-out', submitData);
                ElMessage.success('出库成功');
                stockOutVisible.value = false;
                loadInventory();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '出库失败');
            } finally {
                stockOutLoading.value = false;
            }
        }
    });
};

const submitTransfer = async () => {
    if (!transferFormRef.value) return;
    await transferFormRef.value.validate(async (valid) => {
        if (valid) {
            if (transferForm.from_warehouse_id == transferForm.to_warehouse_id) {
                ElMessage.warning('调出仓库和调入仓库不能相同');
                return;
            }
            if (fromStock.value && transferForm.quantity > fromStock.value.quantity) {
                ElMessage.warning('调拨数量不能大于当前库存');
                return;
            }
            transferLoading.value = true;
            try {
                await api.post('/inventory/transfer', transferForm);
                ElMessage.success('调拨成功');
                transferVisible.value = false;
                loadInventory();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '调拨失败');
            } finally {
                transferLoading.value = false;
            }
        }
    });
};

const submitStocktake = async () => {
    if (!stocktakeFormRef.value) return;
    await stocktakeFormRef.value.validate(async (valid) => {
        if (valid) {
            stocktakeLoading.value = true;
            try {
                await api.post('/inventory-stocktakes', stocktakeForm);
                ElMessage.success('盘点单创建成功');
                stocktakeVisible.value = false;
                if (activeTab.value === 'stocktakes') {
                    loadStocktakes();
                }
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '创建盘点单失败');
            } finally {
                stocktakeLoading.value = false;
            }
        }
    });
};

const loadStocktakes = async () => {
    stocktakeListLoading.value = true;
    try {
        const params = {
            page: stocktakePagination.page,
            per_page: stocktakePagination.per_page
        };
        if (stocktakeSearchForm.stocktake_no) {
            params.stocktake_no = stocktakeSearchForm.stocktake_no;
        }
        if (stocktakeSearchForm.warehouse_id) {
            params.warehouse_id = stocktakeSearchForm.warehouse_id;
        }
        if (stocktakeSearchForm.status) {
            params.status = stocktakeSearchForm.status;
        }
        const response = await api.get('/inventory-stocktakes', { params });
        stocktakes.value = response.data.data;
        stocktakePagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载盘点单列表失败');
    } finally {
        stocktakeListLoading.value = false;
    }
};

const handleStocktakeSearch = () => {
    stocktakePagination.page = 1;
    loadStocktakes();
};

const handleStocktakeReset = () => {
    stocktakeSearchForm.stocktake_no = '';
    stocktakeSearchForm.warehouse_id = null;
    stocktakeSearchForm.status = null;
    handleStocktakeSearch();
};

const handleStocktakeSizeChange = (size) => {
    stocktakePagination.per_page = size;
    loadStocktakes();
};

const handleStocktakePageChange = (page) => {
    stocktakePagination.page = page;
    loadStocktakes();
};

const handleTabChange = (tab) => {
    if (tab === 'stocktakes') {
        loadStocktakes();
    }
};

const handleViewStocktake = async (row) => {
    stocktakeDetailLoading.value = true;
    stocktakeDetailVisible.value = true;
    currentStocktake.value = null;
    try {
        const response = await api.get(`/inventory-stocktakes/${row.id}`);
        currentStocktake.value = response.data.data;
        // 加载该仓库的库位列表
        if (currentStocktake.value.warehouse_id) {
            stocktakeLocations.value = await loadLocations(currentStocktake.value.warehouse_id);
        }
    } catch (error) {
        ElMessage.error('加载盘点单详情失败');
        stocktakeDetailVisible.value = false;
    } finally {
        stocktakeDetailLoading.value = false;
    }
};

const handleAddStocktakeItem = () => {
    if (!currentStocktake.value) return;
    stocktakeItemForm.product_id = null;
    stocktakeItemForm.location_id = null;
    stocktakeItemForm.actual_quantity = null;
    stocktakeItemForm.remark = '';
    stocktakeItemForm.book_quantity = 0;
    addItemVisible.value = true;
};

const handleItemProductChange = async () => {
    if (!stocktakeItemForm.product_id || !currentStocktake.value) return;
    // 获取当前库存数量
    try {
        const response = await api.get('/inventory', {
            params: {
                product_id: stocktakeItemForm.product_id,
                warehouse_id: currentStocktake.value.warehouse_id,
                location_id: stocktakeItemForm.location_id || null
            }
        });
        if (response.data.data && response.data.data.length > 0) {
            stocktakeItemForm.book_quantity = response.data.data[0].quantity || 0;
        } else {
            stocktakeItemForm.book_quantity = 0;
        }
    } catch (error) {
        stocktakeItemForm.book_quantity = 0;
    }
};

const submitStocktakeItem = async () => {
    if (!stocktakeItemFormRef.value || !currentStocktake.value) return;
    await stocktakeItemFormRef.value.validate(async (valid) => {
        if (valid) {
            addItemLoading.value = true;
            try {
                await api.post(`/inventory-stocktakes/${currentStocktake.value.id}/items`, {
                    product_id: stocktakeItemForm.product_id,
                    location_id: stocktakeItemForm.location_id || null,
                    actual_quantity: stocktakeItemForm.actual_quantity,
                    remark: stocktakeItemForm.remark || null
                });
                ElMessage.success('添加明细成功');
                addItemVisible.value = false;
                // 重新加载盘点单详情
                await handleViewStocktake({ id: currentStocktake.value.id });
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '添加明细失败');
            } finally {
                addItemLoading.value = false;
            }
        }
    });
};

const handleCompleteStocktake = async (row) => {
    try {
        await ElMessageBox.confirm('确定要完成该盘点单吗？完成后将自动调整库存差异。', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
        });
        await api.post(`/inventory-stocktakes/${row.id}/complete`);
        ElMessage.success('盘点单完成成功');
        loadStocktakes();
        // 如果当前正在查看该盘点单，刷新详情
        if (currentStocktake.value && currentStocktake.value.id === row.id) {
            await handleViewStocktake({ id: row.id });
        }
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(error.response?.data?.message || '完成盘点单失败');
        }
    }
};

const getStocktakeStatusText = (status) => {
    const statusMap = {
        'draft': '草稿',
        'counting': '盘点中',
        'completed': '已完成'
    };
    return statusMap[status] || status;
};

const getStocktakeStatusType = (status) => {
    const typeMap = {
        'draft': 'info',
        'counting': 'warning',
        'completed': 'success'
    };
    return typeMap[status] || 'info';
};

const handleView = async (row) => {
    // 防止重复点击
    if (viewLoadingId.value !== null) {
        return;
    }
    
    viewLoadingId.value = row.id;
    detailLoading.value = true;
    detailVisible.value = true;
    currentInventory.value = null;
    
    try {
        const response = await api.get(`/inventory/${row.id}`);
        currentInventory.value = response.data.data;
    } catch (error) {
        ElMessage.error('加载详情失败');
        detailVisible.value = false;
    } finally {
        detailLoading.value = false;
        viewLoadingId.value = null;
    }
};

const handleTransactions = async (row) => {
    transactionsVisible.value = true;
    transactionsPagination.page = 1;
    await loadTransactions(row);
};

const loadTransactions = async (row) => {
    transactionsLoading.value = true;
    try {
        const params = {
            page: transactionsPagination.page,
            per_page: transactionsPagination.per_page,
            product_id: row.product_id,
            warehouse_id: row.warehouse_id
        };
        const response = await api.get('/inventory-transactions', { params });
        transactions.value = response.data.data;
        transactionsPagination.total = response.data.total;
    } catch (error) {
        ElMessage.error('加载交易流水失败');
    } finally {
        transactionsLoading.value = false;
    }
};

const handleTransactionsSizeChange = () => {
    transactionsPagination.page = 1;
    if (currentInventory.value) {
        loadTransactions(currentInventory.value);
    }
};

const handleTransactionsPageChange = () => {
    if (currentInventory.value) {
        loadTransactions(currentInventory.value);
    }
};

const handleSizeChange = () => {
    pagination.page = 1;
    loadInventory();
};

const handlePageChange = () => {
    loadInventory();
};

onMounted(() => {
    loadInventory();
    loadWarehouses();
    loadProducts();
});
</script>

<style scoped>
.inventory-page {
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

