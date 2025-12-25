# Laravel ERP 系统部署文档

本文档详细说明如何将 Laravel ERP 系统部署到生产环境。

## 📋 目录

- [服务器要求](#服务器要求)
- [环境准备](#环境准备)
- [部署步骤](#部署步骤)
- [Web 服务器配置](#web-服务器配置)
- [环境变量配置](#环境变量配置)
- [权限设置](#权限设置)
- [性能优化](#性能优化)
- [安全配置](#安全配置)
- [定时任务配置](#定时任务配置)
- [监控和维护](#监控和维护)
- [故障排查](#故障排查)

---

## 🖥️ 服务器要求

### 最低配置

- **操作系统**: Ubuntu 20.04+ / CentOS 7+ / Debian 10+
- **PHP**: 8.0.2 或更高版本
- **数据库**: MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 10+
- **Web 服务器**: Nginx 1.18+ / Apache 2.4+
- **内存**: 最低 2GB RAM（推荐 4GB+）
- **磁盘空间**: 最低 10GB（推荐 20GB+）

### PHP 扩展要求

确保安装以下 PHP 扩展：

```bash
php -m | grep -E "openssl|pdo|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo|gd|curl|zip"
```

必需的 PHP 扩展：
- `openssl`
- `pdo`
- `pdo_mysql` 或 `pdo_pgsql`
- `mbstring`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`
- `gd`（如果使用图片处理）
- `curl`
- `zip`

### 推荐配置

- **PHP**: 8.1+（性能更好）
- **OPcache**: 启用
- **Redis**: 用于缓存和队列
- **MySQL**: 8.0+（性能更好）
- **内存**: 8GB+
- **CPU**: 4 核+

---

## 🔧 环境准备

### 1. 安装 PHP 8.1（Ubuntu/Debian）

```bash
# 添加 PHP 仓库
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# 安装 PHP 8.1 及扩展
sudo apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
    php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd \
    php8.1-bcmath php8.1-tokenizer php8.1-opcache

# 验证安装
php -v
```

### 2. 安装 Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 3. 安装 Node.js 和 npm

```bash
# 使用 NodeSource 安装 Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

### 4. 安装 MySQL

```bash
sudo apt update
sudo apt install -y mysql-server
sudo mysql_secure_installation

# 创建数据库和用户
sudo mysql -u root -p
```

```sql
CREATE DATABASE laravel_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON laravel_erp.* TO 'laravel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. 安装 Redis（可选但推荐）

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping  # 应该返回 PONG
```

### 6. 安装 Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

## 🚀 部署步骤

### 1. 克隆项目

```bash
# 创建项目目录
sudo mkdir -p /var/www/laravel-erp
sudo chown -R $USER:$USER /var/www/laravel-erp

# 克隆项目（使用 Git）
cd /var/www
git clone <your-repository-url> laravel-erp
cd laravel-erp

# 或上传项目文件到服务器
```

### 2. 安装依赖

```bash
# 安装 PHP 依赖（生产环境）
composer install --optimize-autoloader --no-dev

# 安装前端依赖
npm install

# 构建前端资源（生产环境）
npm run build
```

### 3. 环境配置

```bash
# 复制环境配置文件
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 编辑 .env 文件
nano .env
```

### 4. 数据库迁移和填充

```bash
# 运行数据库迁移
php artisan migrate --force

# 填充初始数据（可选）
php artisan db:seed --force
```

### 5. 优化应用

```bash
# 缓存配置
php artisan config:cache

# 缓存路由
php artisan route:cache

# 缓存视图
php artisan view:cache

# 优化自动加载
composer dump-autoload --optimize
```

### 6. 设置权限

```bash
# 设置存储目录权限
sudo chown -R www-data:www-data /var/www/laravel-erp
sudo chmod -R 755 /var/www/laravel-erp
sudo chmod -R 775 /var/www/laravel-erp/storage
sudo chmod -R 775 /var/www/laravel-erp/bootstrap/cache
```

---

## 🌐 Web 服务器配置

### Nginx 配置

创建 Nginx 配置文件：

```bash
sudo nano /etc/nginx/sites-available/laravel-erp
```

配置内容：

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/laravel-erp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 静态资源缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 限制上传文件大小
    client_max_body_size 20M;
}
```

启用配置：

```bash
# 创建符号链接
sudo ln -s /etc/nginx/sites-available/laravel-erp /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl restart nginx
```

### Apache 配置

如果使用 Apache，创建虚拟主机配置：

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/laravel-erp/public

    <Directory /var/www/laravel-erp/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/laravel-erp-error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-erp-access.log combined
</VirtualHost>
```

启用配置：

```bash
sudo a2enmod rewrite
sudo a2ensite laravel-erp
sudo systemctl restart apache2
```

### SSL/HTTPS 配置（使用 Let's Encrypt）

```bash
# 安装 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取 SSL 证书
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 自动续期测试
sudo certbot renew --dry-run
```

---

## ⚙️ 环境变量配置

### 生产环境 `.env` 配置示例

```env
APP_NAME="Laravel ERP"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_erp
DB_USERNAME=laravel_user
DB_PASSWORD=your_secure_password

# 缓存和会话（推荐使用 Redis）
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# 邮件配置
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# CORS 配置（生产环境应限制来源）
CORS_ALLOWED_ORIGINS=https://your-domain.com

# Sanctum 配置
SANCTUM_STATEFUL_DOMAINS=your-domain.com,www.your-domain.com

# 时区和语言
APP_TIMEZONE=Asia/Shanghai
APP_LOCALE=zh_CN
APP_FALLBACK_LOCALE=en
```

### 安全检查清单

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `LOG_LEVEL=error` 或 `warning`
- [ ] `APP_KEY` 已生成且唯一
- [ ] 数据库密码足够复杂
- [ ] CORS 已限制允许的来源
- [ ] `.env` 文件权限设置为 600

---

## 🔐 权限设置

### 目录权限

```bash
# 设置所有者
sudo chown -R www-data:www-data /var/www/laravel-erp

# 设置目录权限
find /var/www/laravel-erp -type d -exec chmod 755 {} \;

# 设置文件权限
find /var/www/laravel-erp -type f -exec chmod 644 {} \;

# 设置存储和缓存目录权限
chmod -R 775 /var/www/laravel-erp/storage
chmod -R 775 /var/www/laravel-erp/bootstrap/cache

# 保护 .env 文件
chmod 600 /var/www/laravel-erp/.env
```

### SELinux 配置（如果启用）

```bash
# 设置上下文
sudo chcon -R -t httpd_sys_rw_content_t /var/www/laravel-erp/storage
sudo chcon -R -t httpd_sys_rw_content_t /var/www/laravel-erp/bootstrap/cache
```

---

## ⚡ 性能优化

### 1. PHP-FPM 优化

编辑 PHP-FPM 配置：

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

推荐配置：

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

重启 PHP-FPM：

```bash
sudo systemctl restart php8.1-fpm
```

### 2. OPcache 配置

编辑 PHP 配置：

```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 3. MySQL 优化

编辑 MySQL 配置：

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M
query_cache_type = 1
```

重启 MySQL：

```bash
sudo systemctl restart mysql
```

### 4. Redis 优化

编辑 Redis 配置：

```bash
sudo nano /etc/redis/redis.conf
```

```ini
maxmemory 256mb
maxmemory-policy allkeys-lru
```

重启 Redis：

```bash
sudo systemctl restart redis-server
```

### 5. Laravel 优化命令

```bash
# 缓存配置
php artisan config:cache

# 缓存路由
php artisan route:cache

# 缓存视图
php artisan view:cache

# 优化自动加载
composer dump-autoload --optimize --classmap-authoritative
```

---

## 🔒 安全配置

### 1. 防火墙配置

```bash
# 允许 SSH、HTTP、HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. 隐藏服务器信息

编辑 Nginx 配置：

```nginx
server_tokens off;
```

### 3. 限制请求大小

在 Nginx 配置中添加：

```nginx
client_max_body_size 20M;
client_body_buffer_size 128k;
```

### 4. 定期更新

```bash
# 更新系统包
sudo apt update && sudo apt upgrade -y

# 更新 Composer 依赖
composer update --no-dev

# 更新 npm 依赖
npm update
```

### 5. 备份策略

创建备份脚本：

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/backups/laravel-erp"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u laravel_user -p'your_password' laravel_erp > $BACKUP_DIR/db_$DATE.sql

# 备份文件
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/laravel-erp

# 删除 7 天前的备份
find $BACKUP_DIR -type f -mtime +7 -delete
```

设置定时任务：

```bash
chmod +x backup.sh
crontab -e
# 添加：0 2 * * * /path/to/backup.sh
```

---

## ⏰ 定时任务配置

Laravel 需要运行定时任务来处理队列、清理缓存等。

编辑 crontab：

```bash
sudo crontab -e -u www-data
```

添加以下内容：

```cron
* * * * * cd /var/www/laravel-erp && php artisan schedule:run >> /dev/null 2>&1
```

如果使用队列，还需要添加：

```cron
* * * * * cd /var/www/laravel-erp && php artisan queue:work --sleep=3 --tries=3 >> /dev/null 2>&1
```

或使用 Supervisor 管理队列进程（推荐）：

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

配置内容：

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel-erp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel-erp/storage/logs/worker.log
stopwaitsecs=3600
```

启动 Supervisor：

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## 📊 监控和维护

### 1. 日志监控

```bash
# 查看 Laravel 日志
tail -f /var/www/laravel-erp/storage/logs/laravel.log

# 查看 Nginx 错误日志
tail -f /var/log/nginx/error.log

# 查看 PHP-FPM 日志
tail -f /var/log/php8.1-fpm.log
```

### 2. 性能监控

安装监控工具：

```bash
# 安装 htop
sudo apt install -y htop

# 安装 iotop（监控磁盘 I/O）
sudo apt install -y iotop

# 安装 netstat
sudo apt install -y net-tools
```

### 3. 健康检查

创建健康检查端点：

```bash
# 在 routes/api.php 中添加
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::get('health_check') !== null ? 'working' : 'not working',
    ]);
});
```

### 4. 定期维护任务

```bash
# 清理缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 优化数据库
php artisan optimize:clear
php artisan optimize

# 清理日志（保留最近 7 天）
find /var/www/laravel-erp/storage/logs -name "*.log" -mtime +7 -delete
```

---

## 🐛 故障排查

### 常见问题

#### 1. 500 错误

```bash
# 检查日志
tail -f /var/www/laravel-erp/storage/logs/laravel.log

# 检查权限
ls -la /var/www/laravel-erp/storage
ls -la /var/www/laravel-erp/bootstrap/cache

# 清除缓存
php artisan cache:clear
php artisan config:clear
```

#### 2. 数据库连接错误

```bash
# 测试数据库连接
mysql -u laravel_user -p laravel_erp

# 检查 .env 配置
cat .env | grep DB_

# 检查 MySQL 服务状态
sudo systemctl status mysql
```

#### 3. 权限错误

```bash
# 重新设置权限
sudo chown -R www-data:www-data /var/www/laravel-erp
sudo chmod -R 755 /var/www/laravel-erp
sudo chmod -R 775 /var/www/laravel-erp/storage
sudo chmod -R 775 /var/www/laravel-erp/bootstrap/cache
```

#### 4. 队列不工作

```bash
# 检查队列配置
php artisan queue:work --once

# 检查 Supervisor 状态
sudo supervisorctl status

# 查看队列日志
tail -f /var/www/laravel-erp/storage/logs/worker.log
```

#### 5. 前端资源加载失败

```bash
# 重新构建前端资源
npm run build

# 检查 public/build 目录
ls -la /var/www/laravel-erp/public/build

# 检查 Nginx 配置中的静态文件路径
```

### 调试模式（仅开发环境）

如果需要临时启用调试模式：

```bash
# 编辑 .env
APP_DEBUG=true
LOG_LEVEL=debug

# 清除配置缓存
php artisan config:clear
```

**注意**: 生产环境必须关闭调试模式！

---

## 📝 部署检查清单

部署完成后，请检查以下项目：

- [ ] 应用可以正常访问
- [ ] 数据库连接正常
- [ ] 用户登录功能正常
- [ ] 文件上传功能正常
- [ ] 队列任务正常运行
- [ ] 定时任务正常运行
- [ ] 日志正常记录
- [ ] SSL 证书配置正确
- [ ] 备份脚本正常运行
- [ ] 监控工具正常工作
- [ ] 性能优化已应用
- [ ] 安全配置已应用

---

## 🔗 相关文档

- [README.md](README.md) - 项目说明文档

---

## 📞 支持

如遇到部署问题，请：

1. 查看日志文件
2. 检查本文档的故障排查部分
3. 提交 Issue 到项目仓库

---

**最后更新**: 2025-12-22

