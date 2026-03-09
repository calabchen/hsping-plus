# 部署指南总览

## 1. 文档入口

- [环境配置指南](setup.md)：本地开发环境
- [生产环境部署](deployment-production.md)：上线部署

## 2. 端口口径

- Laravel：`8000`
- Vue3：`8001`
- Python：不固定（按部署配置）

## 3. 开发环境最短路径

1. 启动 Laravel（8000）
2. 启动 Python 服务（端口自定）
3. 启动 Vue3（8001）
4. 浏览器访问 `http://127.0.0.1:8001`

## 4. 生产环境要点

- Nginx 对外暴露 80/443
- Laravel 与 Python 走内网通信
- `PYTHON_SERVICE_URL` 使用实际 Python 地址
- 配置日志、备份、回滚策略

## 5. 常见检查项

- 前端无法请求：检查 `VITE_API_BASE_URL`
- Python 调用失败：检查 `PYTHON_SERVICE_URL` 与 Token
- 上传失败：检查 Nginx/PHP 上传限制

## 6. 相关文档

- [快速开始](quick-start.md)
- [系统架构](../technical/architecture.md)
