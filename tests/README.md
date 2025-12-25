# 测试配置说明

## 数据库配置

测试使用独立的测试数据库 `laravel_erp_test`，避免影响开发数据库。

### 设置测试数据库

1. **创建测试数据库**

```bash
# 使用 MySQL 命令行
mysql -u root -p

# 在 MySQL 中执行
CREATE DATABASE laravel_erp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

或者使用 Laravel 命令（如果已配置）：

```bash
php artisan db:create laravel_erp_test
```

2. **配置测试环境**

测试配置在 `phpunit.xml` 中，默认使用以下配置：

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="laravel_erp_test"/>
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value=""/>
```

如果您的 MySQL 配置不同，请修改 `phpunit.xml` 中的相应值。

3. **运行数据库迁移**

```bash
php artisan migrate --database=mysql --env=testing
```

或者直接在测试中运行迁移（推荐）：

测试会自动运行迁移，无需手动执行。

## 运行测试

```bash
# 运行所有测试
php artisan test

# 运行特定测试文件
php artisan test --filter ExampleTest

# 运行单元测试
php artisan test tests/Unit

# 运行功能测试
php artisan test tests/Feature

# 生成覆盖率报告
php artisan test --coverage
```

## 使用 SQLite（可选）

如果您想使用 SQLite 进行测试（更快，无需配置数据库），需要：

1. **启用 SQLite 扩展**

编辑 `php.ini`，取消注释：
```ini
extension=pdo_sqlite
extension=sqlite3
```

2. **修改 phpunit.xml**

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

注意：某些测试可能需要 MySQL 特定功能，使用 SQLite 可能会失败。

## 故障排查

### 错误：could not find driver

**原因**：PHP 缺少数据库驱动扩展

**解决方案**：
- 对于 MySQL：确保已安装 `pdo_mysql` 扩展
- 对于 SQLite：确保已安装 `pdo_sqlite` 扩展

检查已安装的扩展：
```bash
php -m | grep pdo
```

### 错误：Access denied for user

**原因**：数据库用户名或密码错误

**解决方案**：修改 `phpunit.xml` 中的 `DB_USERNAME` 和 `DB_PASSWORD`

### 错误：Unknown database 'laravel_erp_test'

**原因**：测试数据库不存在

**解决方案**：创建测试数据库（见上方说明）




