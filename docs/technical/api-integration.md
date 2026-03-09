# API 集成总览

本文档提供 HsPing Plus 系统各层级 API 接口的导航。

## 接口架构

```
前端 ──REST API──> Laravel ──HTTP API──> Python OCR
                      ↓
                   MySQL
```

## 文档导航

### [前端 API 文档](api-frontend.md)

前端（Vue/uni-app）与 Laravel 后端的 REST API 接口

**包含内容**：

- 用户认证接口
- 试卷管理接口
- 答题卡上传接口
- 批改管理接口
- 成绩统计接口
- 错误码规范

### [后端服务 API 文档](api-backend.md)

Laravel 与 Python OCR 服务之间的内部接口

**包含内容**：

- 答题卡生成接口
- OCR 识别与判分接口
- 服务健康检查
- 回调接口（可选）
- 错误码说明

### 外部文档

- [OMR API 使用指南](../../backend-ocr/code/README_API_OMR.md) - Python OCR 服务详细使用说明
- [OMR 处理指南](../../backend-ocr/code/OMR_GUIDE.md) - 答题卡生成与识别技术细节

## 接口设计原则

### RESTful 规范

- 使用标准 HTTP 方法（GET、POST、PUT、DELETE）
- 资源路径清晰语义化
- 统一的响应格式

### 数据格式

- 请求与响应均使用 JSON
- 日期时间格式：`YYYY-MM-DD HH:mm:ss`
- 字符编码：UTF-8

### 错误处理

- HTTP 状态码语义化
- 统一的错误响应格式
- 详细的错误信息

### 安全机制

- 前端使用 Laravel Sanctum Token 认证
- 内部服务使用 `X-Internal-Token` 鉴权
- HTTPS 加密传输（生产环境）

## 通用响应格式

### 成功响应

```json
{
  "code": 0,
  "message": "success",
  "data": {
    /* 业务数据 */
  }
}
```

### 错误响应

```json
{
  "code": 3001,
  "message": "试卷不存在",
  "error": "详细错误信息"
}
```

## 错误码分类

- `0`：成功
- `1xxx`：Python 服务错误
- `2xxx`：用户认证错误
- `3xxx`：试卷相关错误
- `4xxx`：提交批改错误
- `5xxx`：系统内部错误

## 开发工具推荐

- **API 测试**：Postman、Insomnia
- **命令行**：curl
- **浏览器插件**：REST Client（VS Code）

---

相关文档：

- [系统架构](architecture.md)
- [数据库设计](database.md)
- [快速开始](../guides/quick-start.md)
