# 生产环境部署

## 1. 目标

部署 Laravel + Vue3 + Python OCR 三个服务，并保证可持续运维。

## 2. 基础准备

- 系统：Ubuntu 22.04+（推荐）
- 组件：Nginx、MySQL、PHP-FPM、Python、Node.js

## 3. Laravel 部署

```bash
cd /var/www/hsping-plus/backend-laravel
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

`.env` 关键项：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
PYTHON_SERVICE_URL=http://127.0.0.1:<python_port>
PYTHON_INTERNAL_TOKEN=your-token
```

## 4. Python 服务部署

```bash
cd /var/www/hsping-plus/backend-ocr/code
pip3 install -r requirements.txt
```

使用 `supervisor` 或 `systemd` 托管进程，端口按实际配置决定。

## 5. Vue3 前端部署

```bash
cd /var/www/hsping-plus/frontend-pc
pnpm install
pnpm build
```

部署 `dist/` 到 Nginx 静态目录。

## 6. Nginx 路由建议

- `/` -> Vue3 `dist`
- `/api` -> Laravel
- `/storage` -> Laravel public storage

## 7. 端口建议

- 对外：`80/443`（Nginx）
- 内网：Laravel `8000`（可选）
- 内网：Python 不固定
- MySQL：`3306`

## 8. 健康检查

- Laravel：`/api/health`（若项目已实现）
- Python：`/py/api/v1/health`
- 检查日志：Laravel、Nginx、Python 进程日志

## 9. 备份与更新

- 数据库每日定时备份
- 更新流程：拉代码 -> 迁移 -> 构建前端 -> 重启服务
- 保留回滚版本与备份文件

## 10. 常见问题

- 502：先查 Nginx upstream 与 PHP-FPM
- OCR 不可用：检查 Python 进程和 `PYTHON_SERVICE_URL`
- 上传失败：检查 Nginx/PHP 上传大小限制

## 11. 相关文档

- [环境配置](setup.md)
- [部署总览](deployment.md)
