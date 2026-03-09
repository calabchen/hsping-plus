# 系统架构

## 1. 架构概览

HsPing Plus 采用前后端分离架构：
- 前端：Vue3 PC 端 + uni-app 移动端
- 后端：Laravel 业务服务
- 算法：Python OCR/判分服务
- 数据：MySQL

```text
前端(PC/移动) -> Laravel API -> MySQL
                     |
                     -> Python OCR 服务
```

## 2. 模块职责

### 前端层
- 试卷、班级、学生、成绩页面
- 上传答题卡与批改复核
- 调用 Laravel REST API

### Laravel 层
- 认证鉴权（Sanctum）
- 业务流程（建卷、发布、提交、复核）
- 文件管理与导出
- 调用 Python 服务完成识别/判分

### Python 层
- 生成答题卡模板
- 识别客观题填涂
- 主观题 OCR 与 AI 建议分

## 3. 开发环境端口

- Laravel：`8000`
- Vue3：`8001`
- Python：不固定，按本地启动参数或默认配置
- MySQL：`3306`

## 4. 关键流程

### 建卷流程
1. 教师创建试卷
2. Laravel 保存试卷配置
3. Laravel 请求 Python 生成答题卡
4. 返回答题卡路径供下载/打印

### 批改流程
1. 上传答题卡图片
2. Laravel 记录 submission
3. Laravel 调用 Python 识别与判分
4. 回写 `answer_details`
5. 教师复核并确认总分

## 5. 安全与稳定性

- Token 鉴权（前端到 Laravel）
- 内部 Token（Laravel 到 Python）
- 关键入库事务化
- 失败可重试，避免脏数据

## 6. 性能建议

- 高频字段加索引
- 列表分页
- 批量处理异步化（可选队列）
- 低置信度题目优先人工复核

## 7. 相关文档

- [数据库设计](database.md)
- [API 集成总览](api-integration.md)
- [部署指南](../guides/deployment.md)
