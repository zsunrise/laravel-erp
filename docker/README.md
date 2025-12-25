# Docker 配置说明

本目录包含 Docker 相关的配置文件。

## 目录结构

```
docker/
├── nginx/
│   └── default.conf          # Nginx 配置文件
├── php/
│   └── local.ini              # PHP 配置文件
└── mysql/
    └── my.cnf                 # MySQL 配置文件
```

## 使用方法

### 1. 启动所有服务

```bash
docker-compose up -d
```

### 2. 安装依赖

```bash
# 进入应用容器
docker-compose exec app bash

# 安装 PHP 依赖
composer install

# 安装前端依赖（在容器外或 node 容器中）
npm install
```

### 3. 配置环境变量

```bash
# 复制环境配置文件
cp .env.example .env

# 编辑 .env 文件，配置数据库连接
# DB_HOST=db
# DB_DATABASE=laravel_erp
# DB_USERNAME=laravel_user
# DB_PASSWORD=password
```

### 4. 生成应用密钥

```bash
docker-compose exec app php artisan key:generate
```

### 5. 运行数据库迁移

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### 6. 访问应用

- 应用地址: http://localhost:8000
- MySQL: localhost:3306
- Redis: localhost:6379

## 常用命令

```bash
# 查看运行状态
docker-compose ps

# 查看日志
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f db

# 停止所有服务
docker-compose down

# 停止并删除数据卷（注意：会删除数据库数据）
docker-compose down -v

# 重新构建镜像
docker-compose build --no-cache

# 进入容器
docker-compose exec app bash
docker-compose exec db mysql -u laravel_user -p laravel_erp
```

## 服务说明

- **app**: PHP-FPM 应用容器
- **nginx**: Nginx Web 服务器
- **db**: MySQL 8.0 数据库
- **redis**: Redis 缓存服务
- **node**: Node.js 服务（用于前端开发）

## 注意事项

1. 首次启动时，MySQL 需要一些时间来初始化
2. 确保端口 8000、3306、6379 未被占用
3. 生产环境请修改默认密码和配置
4. 数据卷会持久化数据库和 Redis 数据

