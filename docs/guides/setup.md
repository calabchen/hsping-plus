# 环境配置指南

## 1. 依赖要求

- Node.js 18+
- PHP 8.2+
- Python 3.9+
- MySQL 8.0+
- Composer、pnpm

## 2. 端口约定（开发）

- Laravel：`8000`
- Vue3：`8001`
- Python：不固定，按本地配置

## 3. Laravel 配置

```bash
cd backend-laravel
composer install
cp .env.example .env
php artisan key:generate
```

`.env` 关键项：

```env
APP_URL=http://127.0.0.1:8000
DB_DATABASE=hsping_plus
DB_USERNAME=root
DB_PASSWORD=your_password
PYTHON_SERVICE_URL=http://127.0.0.1:<python_port>
PYTHON_INTERNAL_TOKEN=your-token
```

初始化：

```bash
php artisan migrate
php artisan storage:link
php artisan serve --port=8000
```

## 4. Python 配置

```bash
cd backend-ocr/code
pip install -r requirements.txt
python omr_from_api.py
```

说明：如需指定端口，请在启动参数中自行设置，并同步到 `PYTHON_SERVICE_URL`。

## 5. Vue3 配置

```bash
cd frontend-pc
pnpm install
```

`.env`：

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

启动：

```bash
pnpm dev --port 8001
```

## 6. 验证联通

- 打开 `http://127.0.0.1:8001`
- 登录后检查试卷列表是否加载
- 上传一张答题卡测试批改链路

## 7. 常见问题

- Laravel 启动失败：检查 `.env` 与数据库连接
- Python 调用失败：检查 `PYTHON_SERVICE_URL` 与 token
- 前端跨域问题：检查 Laravel CORS 与 Sanctum 配置

## 8. 相关文档

- [快速开始](quick-start.md)
- [生产部署](deployment-production.md)
