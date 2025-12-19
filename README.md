# Laravel ERP 系统

一个基于 Laravel 9 开发的企业资源规划（ERP）系统，提供完整的业务流程管理功能。

## 项目简介

本系统是一个功能完整的企业资源规划系统，涵盖采购、销售、库存、生产、财务等核心业务模块，支持多仓库管理、审批流程、报表分析等功能。

## 技术栈

- **后端框架**: Laravel 9.x
- **PHP 版本**: PHP 8.0.2+
- **认证方式**: Laravel Sanctum
- **前端构建**: Vite
- **数据库**: MySQL/MariaDB/PostgreSQL（支持 Laravel 支持的所有数据库）

## 核心功能模块

### 1. 系统管理模块
- 用户管理（CRUD、角色分配）
- 角色权限管理（RBAC 结构）
- 系统配置管理
- 操作日志记录

### 2. 基础数据模块
- 数据字典管理
- 商品分类管理（支持树形结构）
- 计量单位管理
- 币种管理
- 地区管理（支持省市区三级）

### 3. 商品管理模块
- 商品信息管理（SKU、条形码、价格等）
- 商品搜索和筛选

### 4. 仓库管理模块
- 仓库信息管理
- 库位管理

### 5. 库存管理模块
- 库存查询（支持多条件筛选、低库存预警）
- 入库管理（采购入库、生产入库、调拨入库）
- 出库管理（销售出库、生产领料、调拨出库）
- 库存调拨（仓库间调拨）
- 库存调整（盘点调整）
- 盘点管理（盘点计划、盘点执行、差异处理、自动调整）
- 库存交易记录查询

### 6. 供应商管理模块
- 供应商档案管理（评级、信用额度、账期等）

### 7. 客户管理模块
- 客户档案管理（评级、信用额度、账期等）

### 8. 采购管理模块
- 采购订单管理（创建、编辑、审核）
- 采购入库（与库存管理集成）
- 采购退货
- 采购对账与结算

### 9. 销售管理模块
- 销售订单管理（创建、编辑、审核）
- 销售出库（与库存管理集成，自动检查库存）
- 销售退货（自动入库）
- 销售对账与收款

### 10. BOM 管理模块
- 物料清单管理（创建、编辑、删除、复制、版本管理）
- 工艺路线管理（创建、编辑、删除、复制、版本管理）
- BOM 成本计算
- 工艺路线总工时计算

### 11. 生产管理模块
- 生产计划管理（创建、编辑、审核、删除）
- 工单管理（创建、编辑、审核、派工、进度跟踪）
- 生产领料（与库存管理集成，自动检查库存）
- 生产退料（自动入库）
- 生产报工（工时、产量、合格率）
- 生产入库（工单完成时自动入库）

### 12. 财务管理模块
- 会计科目管理（支持树形结构）
- 会计凭证管理（创建、编辑、删除、过账、借贷平衡检查）
- 总账查询（科目余额查询）
- 应收账款管理（创建、收款、账龄分析）
- 应付账款管理（创建、付款）
- 财务报表（资产负债表、利润表）

### 13. 审批流程模块
- 流程设计（创建、编辑、删除流程和节点）
- 流程启动（关联业务单据）
- 审批功能（通过、拒绝、转交）
- 待审批列表
- 审批历史记录

### 14. 报表分析模块
- 销售报表（销售统计、客户分析、产品分析、趋势分析）
- 采购报表（采购统计、供应商分析、产品分析、趋势分析）
- 库存报表（库存周转率、呆滞料分析、库存估值、库存变动）
- 财务报表（资产负债表、利润表）
- 自定义报表（报表定义、报表执行、动态查询）

### 15. 消息通知模块
- 系统消息（发送、接收、阅读、删除）
- 消息模板管理（创建、编辑、删除、预览）
- 邮件通知（集成 Laravel Mail）
- 短信通知（预留接口）
- 消息推送（预留接口）
- 批量发送（发送给多个用户、按角色发送）
- 未读消息统计

## 安装步骤

### 1. 克隆项目

```bash
git clone <repository-url>
cd laravel-erp
```

### 2. 安装依赖

```bash
composer install
npm install
```

### 3. 环境配置

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件，配置数据库连接信息：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_erp
DB_USERNAME=root
DB_PASSWORD=
```

### 4. 数据库迁移和填充

```bash
php artisan migrate
php artisan db:seed
```

### 5. 启动开发服务器

```bash
php artisan serve
```

前端资源编译（开发模式）：

```bash
npm run dev
```

## 数据库结构

系统共包含 60 张数据库表，涵盖所有业务模块：

- 用户权限相关：users, roles, permissions, role_user, permission_role
- 系统配置：system_configs, operation_logs, data_dictionaries
- 基础数据：product_categories, units, currencies, regions, products
- 仓库库存：warehouses, warehouse_locations, inventory, inventory_transactions, inventory_stocktakes, inventory_stocktake_items
- 供应商客户：suppliers, customers
- 采购相关：purchase_orders, purchase_order_items, purchase_returns, purchase_return_items, purchase_settlements, purchase_settlement_items
- 销售相关：sales_orders, sales_order_items, sales_returns, sales_return_items, sales_settlements, sales_settlement_items
- BOM 生产：boms, bom_items, process_routes, process_route_steps, production_plans, production_plan_items, work_orders, work_order_items, production_material_issues, production_material_issue_items, production_reports
- 财务相关：chart_of_accounts, accounting_vouchers, accounting_voucher_items, general_ledger, accounts_receivable, accounts_payable, cost_allocations
- 审批流程：workflows, workflow_nodes, workflow_instances, approval_records
- 报表分析：report_definitions, report_schedules
- 消息通知：notifications, notification_templates, notification_logs

## API 接口

系统提供完整的 RESTful API 接口，使用 Laravel Sanctum 进行身份认证。

### 认证接口

- `POST /api/login` - 用户登录
- `POST /api/logout` - 用户登出（需要认证）
- `GET /api/me` - 获取当前用户信息（需要认证）

### 主要接口模块

所有接口都需要通过 `auth:sanctum` 中间件认证，主要接口包括：

- 用户管理：`/api/users`
- 角色权限：`/api/roles`, `/api/permissions`
- 商品管理：`/api/products`, `/api/product-categories`
- 库存管理：`/api/inventory`, `/api/inventory/stock-in`, `/api/inventory/stock-out` 等
- 采购管理：`/api/purchase-orders`, `/api/purchase-returns`, `/api/purchase-settlements` 等
- 销售管理：`/api/sales-orders`, `/api/sales-returns`, `/api/sales-settlements` 等
- 生产管理：`/api/production-plans`, `/api/work-orders`, `/api/production-material-issues` 等
- 财务管理：`/api/chart-of-accounts`, `/api/accounting-vouchers`, `/api/general-ledger` 等
- 审批流程：`/api/workflows`, `/api/workflow-instances`, `/api/approval-records` 等
- 报表分析：`/api/sales-reports`, `/api/purchase-reports`, `/api/inventory-reports` 等
- 消息通知：`/api/notifications`, `/api/notification-templates` 等

详细接口文档请参考 `routes/api.php` 文件。

## 默认数据

系统初始化时会自动填充以下数据：

- **角色权限数据**：管理员、经理、操作员等角色及相应权限
- **系统配置数据**：系统基础配置项
- **基础数据**：计量单位、币种、地区、仓库等基础数据
- **会计科目数据**：标准会计科目表
- **用户数据**：默认管理员账户

## 开发指南

### 代码规范

项目使用 Laravel Pint 进行代码格式化：

```bash
./vendor/bin/pint
```

### 测试

运行测试：

```bash
php artisan test
```

### 目录结构

```
app/
├── Console/          # 命令行任务
├── Exceptions/       # 异常处理
├── Http/
│   ├── Controllers/  # 控制器
│   ├── Middleware/   # 中间件
│   └── Responses/    # 响应类
├── Models/           # 模型
├── Providers/        # 服务提供者
└── Services/         # 业务服务类

database/
├── factories/        # 模型工厂
├── migrations/       # 数据库迁移
└── seeders/          # 数据填充

routes/
├── api.php           # API 路由
├── web.php           # Web 路由
└── channels.php      # 广播频道
```

## 安全说明

- 所有 API 接口使用 Laravel Sanctum 进行身份认证
- 支持基于角色的访问控制（RBAC）
- 所有用户操作记录在操作日志中
- 敏感数据使用加密存储

## 许可证

本项目采用 [MIT 许可证](https://opensource.org/licenses/MIT)。

## 更新日志

- **2025-12-18**: 完成所有核心功能模块开发，包括系统管理、基础数据、商品管理、库存管理、采购管理、销售管理、BOM 管理、生产管理、财务管理、审批流程、报表分析、消息通知等模块

## 贡献

欢迎提交 Issue 和 Pull Request。

## 联系方式

如有问题或建议，请通过 Issue 反馈。
