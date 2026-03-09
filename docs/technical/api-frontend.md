# 前端 API 文档

本文档描述前端（Vue3/uni-app）调用 Laravel 的主要接口。

## 1. 基础约定

- Base URL：`http://127.0.0.1:8000/api`
- 认证：`Authorization: Bearer <token>`
- 返回结构：`{ code, message, data }`

## 2. 认证接口

- `POST /login` 登录
- `POST /logout` 登出
- `GET /me` 当前用户

## 3. 试卷接口

- `GET /quizzes` 试卷列表（支持状态、分页）
- `POST /quizzes` 新建试卷
- `PUT /quizzes/{id}` 编辑试卷
- `POST /quizzes/{id}/publish` 发布试卷
- `POST /quizzes/{id}/generate-answer-card` 生成答题卡

## 4. 班级与学生接口

- `GET /classes` 班级列表
- `POST /classes` 创建班级
- `GET /students` 学生列表
- `POST /students` 新增学生
- `POST /students/import` 批量导入

## 5. 提交与批改接口

- `POST /submissions` 单张上传答题卡
- `POST /submissions/batch` 批量上传
- `GET /submissions/{id}` 查看批改详情
- `PUT /submissions/{id}/review` 教师复核打分

## 6. 成绩接口

- `GET /statistics/quiz/{quiz_id}` 按试卷统计
- `GET /statistics/class/{class_id}` 按班级统计
- `GET /statistics/student/{student_id}` 按学生统计
- `GET /exports/quiz/{quiz_id}/scores` 导出 Excel

## 7. 常见错误码

- `0` 成功
- `2001` 登录失败
- `3001` 试卷不存在
- `3002` 试卷状态不允许编辑
- `4001` 提交记录不存在
- `4002` 重复提交

## 8. 前端端口说明

- Vue3 开发端口：`8001`
- 后端 Laravel 端口：`8000`

## 9. 相关文档

- [后端服务 API](api-backend.md)
- [API 集成总览](api-integration.md)
