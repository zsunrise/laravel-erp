# 后端接口性能优化指南

## ✅ 已完成的优化清单

- [x] **环境配置优化** - 创建了 `.env.performance.example` 配置文件示例
- [x] **SalesOrderController 查询优化** - 将 `orWhereHas` 改为高效的 `LEFT JOIN` 查询
- [x] **PurchaseOrderController 查询优化** - 将 `orWhereHas` 改为高效的 `LEFT JOIN` 查询
- [x] **BomController 查询优化** - 将 `orWhereHas` 改为高效的 `LEFT JOIN` 查询
- [x] **ProcessRouteController 查询优化** - 将 `orWhereHas` 改为高效的 `LEFT JOIN` 查询
- [x] **OperationLogController 查询优化** - 将 `orWhereHas` 改为高效的 `LEFT JOIN` 查询
- [x] **慢查询监控** - 在 `AppServiceProvider` 中添加了慢查询监控（超过 100ms 的查询会被记录）
- [x] **数据库索引优化** - 创建了迁移文件 `2025_12_22_052710_add_performance_indexes_to_tables.php`，为常用搜索字段添加索引

## 下一步操作

1. **运行数据库迁移**：
   ```bash
   php artisan migrate
   ```

2. **检查环境配置**：
   - 确保 `.env` 文件中 `APP_DEBUG=false`（生产环境）
   - 设置 `LOG_LEVEL=error` 或 `warning`
   - 如果可用，使用 `CACHE_DRIVER=redis`

3. **监控慢查询**：
   - 查看 `storage/logs/laravel.log` 中的慢查询警告
   - 根据日志优化具体的查询

## 问题诊断

如果后端接口速度慢，可能的原因包括：

### 1. 调试模式开启
**问题**: `APP_DEBUG=true` 会记录大量调试信息，严重影响性能

**解决方案**:
```env
APP_DEBUG=false
APP_ENV=production
```

### 2. 日志级别过高
**问题**: `LOG_LEVEL=debug` 会记录所有日志，包括查询日志

**解决方案**:
```env
LOG_LEVEL=error
# 或
LOG_LEVEL=warning
```

### 3. 缓存驱动使用文件系统
**问题**: 文件系统缓存比 Redis 慢很多

**解决方案**:
```env
CACHE_DRIVER=redis
# 或至少使用
CACHE_DRIVER=array  # 内存缓存（仅开发环境）
```

### 4. 数据库查询优化

#### 4.1 检查 N+1 查询问题
虽然代码中使用了 `with()` 预加载，但需要确保所有关联都被正确加载。

#### 4.2 添加数据库索引
检查常用查询字段是否已添加索引，特别是：
- 搜索字段（name, email, sku 等）
- 外键字段
- 状态字段
- 日期字段

#### 4.3 优化 LIKE 查询
`LIKE '%keyword%'` 无法使用索引，考虑：
- 使用全文搜索（MySQL FULLTEXT）
- 使用 Elasticsearch
- 限制搜索范围

### 5. 中间件性能
检查是否有不必要的中间件在 API 路由上运行。

### 6. 数据库连接配置
```env
DB_CONNECTION=mysql
# 确保连接池配置合理
```

## 快速优化步骤

### 步骤 1: 检查环境配置
检查 `.env` 文件中的以下配置：

```env
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 步骤 2: 启用 OPcache（生产环境）
在 `php.ini` 中启用 OPcache：
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 步骤 3: 配置 Redis（如果可用）
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

### 步骤 4: 优化数据库查询
- 使用 Laravel Debugbar 或 Telescope 检查慢查询
- 确保所有关联查询都使用 `with()` 预加载
- 添加必要的数据库索引

### 步骤 5: 启用查询缓存
对于不经常变化的数据，使用缓存：

```php
$users = Cache::remember('users_list', 3600, function () {
    return User::with('roles')->get();
});
```

### 步骤 6: 优化分页
确保分页查询使用索引：

```php
// 好的做法
$query->orderBy('created_at', 'desc')->paginate(15);

// 避免
$query->orderByRaw('RAND()')->paginate(15);
```

## 性能监控工具

### 1. Laravel Debugbar（开发环境）
```bash
composer require barryvdh/laravel-debugbar --dev
```

### 2. Laravel Telescope（开发环境）
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 3. 数据库查询日志
在 `AppServiceProvider` 中临时启用：

```php
if (config('app.debug')) {
    DB::listen(function ($query) {
        if ($query->time > 100) { // 超过 100ms 的查询
            \Log::warning('Slow query', [
                'sql' => $query->sql,
                'time' => $query->time,
            ]);
        }
    });
}
```

## 常见性能问题检查清单

- [ ] `APP_DEBUG=false` 在生产环境
- [ ] `LOG_LEVEL=error` 或 `warning`
- [ ] 使用 Redis 作为缓存驱动
- [ ] 所有关联查询使用 `with()` 预加载
- [ ] 常用查询字段已添加索引
- [ ] 避免在循环中执行数据库查询
- [ ] 使用分页而不是 `get()` 获取大量数据
- [ ] 启用 OPcache
- [ ] 使用队列处理耗时任务
- [ ] 定期清理日志文件

## 数据库索引建议

为以下常用查询字段添加索引：

```php
// users 表
$table->index('email');
$table->index('is_active');

// products 表
$table->index('name');
$table->index('is_active');
$table->index(['category_id', 'is_active']);

// sales_orders 表
$table->index('order_date');
$table->index('status');
$table->index(['customer_id', 'status']);

// inventory 表
$table->index(['product_id', 'warehouse_id']);
```

## 进一步优化

### 1. 使用数据库连接池
配置 MySQL 连接池以提高并发性能。

### 2. 使用 CDN
对于静态资源，使用 CDN 加速。

### 3. 启用 HTTP/2
如果使用 HTTPS，启用 HTTP/2 协议。

### 4. 使用队列
将耗时任务放入队列异步处理：

```php
dispatch(new ProcessLargeData($data));
```

### 5. 使用缓存
缓存频繁查询的数据：

```php
Cache::remember('key', 3600, function () {
    return ExpensiveQuery::get();
});
```

## 性能测试

使用以下工具测试接口性能：

1. **Apache Bench (ab)**
```bash
ab -n 1000 -c 10 http://your-api.com/api/users
```

2. **Laravel Benchmark**
```php
$start = microtime(true);
// 你的代码
$end = microtime(true);
Log::info('Execution time: ' . ($end - $start) . ' seconds');
```

## 联系支持

如果问题仍然存在，请检查：
1. 服务器资源（CPU、内存、磁盘 I/O）
2. 数据库服务器性能
3. 网络延迟
4. 是否有其他进程占用资源

