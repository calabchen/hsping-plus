# 数据库设计

## 1. 设计目标

- 支撑建卷、发布、提交、批改、统计全流程
- 保证数据一致性与可追溯
- 满足教师单用户场景的简洁实现

## 2. 核心表

### 用户与教师
- `users`：登录账号
- `teachers`：教师资料（与 users 1:1）

### 班级与学生
- `classes`：班级信息
- `students`：学生信息（与 classes N:1）

### 试卷与题目
- `questions`：题库
- `quizzes`：试卷
- `quiz_question_items`：试卷-题目关联（含题序、分值）
- `quiz_assignments`：试卷发布到班级

### 提交与批改
- `submissions`：学生提交记录
- `answer_details`：每题识别结果与得分

## 3. 关键字段建议

- `quizzes.status`：草稿/已发布/已结束/已归档
- `submissions.status`：待批改/已完成
- `answer_details.ai_suggested_score`：主观题 AI 建议分
- `answer_details.earned_score`：教师最终分

## 4. 索引建议

- `students.class_id`
- `questions.teacher_id`
- `quizzes.teacher_id, quizzes.status`
- `submissions.quiz_id, submissions.student_id, submissions.status`
- `answer_details.submission_id, answer_details.question_id`

## 5. 约束建议

- `teachers.user_id` 唯一
- `students.student_id` 主键
- `submissions(quiz_id, student_id)` 唯一（防重复提交）
- 外键完整性约束开启

## 6. 关系说明

- 一个班级有多个学生
- 一个试卷可发布到多个班级
- 一个试卷包含多个题目
- 一次提交对应多条答题明细

## 7. 存储策略

- 图片只存路径，不存 BLOB
- 文件落地到 Laravel storage 或对象存储
- 统计结果尽量由明细重算，减少冗余

## 8. 迁移位置

- Laravel 迁移文件目录：`backend-laravel/database/migrations/`

## 9. 相关文档

- [系统架构](architecture.md)
- [API 集成总览](api-integration.md)
