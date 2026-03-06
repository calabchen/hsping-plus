# 教师个人智能试卷批改系统 MVP 实施与集成清单

## 1. MVP 必做清单

### 1.1 目标边界（MVP）
- 仅支持单教师账号体系（不做多租户和学校管理后台）。
- 仅覆盖核心闭环：建卷 -> 生成答题卡 -> 上传 -> OCR/判分 -> 教师复核 -> 成绩查看。
- 仅支持基础统计（按试卷、班级、学生），高级可视化先简化。

### 1.2 功能必做项（按模块）

#### A. 认证与教师资料
- 教师注册/登录/退出。
- 忘记密码（邮件重置）。
- 个人资料编辑（姓名、学科、性别、电话、邮箱、密码）。

#### B. 班级与学生管理
- 班级 CRUD（班级号、入学年、毕业年、毕业状态）。
- 学生 CRUD（学号、姓名、性别、年龄、班级、头像路径）。
- 学生批量导入（CSV 模板）最小可用版。

#### C. 题库与试卷管理
- 题目 CRUD（单选、多选、判断、主观）。
- 试卷创建：选择题目、设置分值与排序。
- 试卷状态流转：草稿 -> 已发布 -> 已结束（归档可后置）。
- 发布到班级（生成试卷-班级关联关系）。

#### D. 答题卡与提交
- 生成答题卡图片（PNG）：含定位点、学生信息区、客观题填涂区、主观题作答区。
- PC 端上传答题卡图片（单张 + 批量）。
- 记录提交信息（submission + 原图路径 + 状态）。

#### E. 批改流程
- 调用 Python 服务进行 OCR 与结构化识别。
- 客观题自动判分并写入 `answer_details`。
- 主观题 OCR 文本提取 + AI 建议分写入 `ai_suggested_score`。
- 教师复核页面：可逐题修改最终得分并提交。
- 自动汇总总分并更新提交状态为已完成。

#### F. 成绩查看与导出
- 按试卷查看：总分列表、每题得分。
- 按班级查看：平均分、最高/最低分、及格率。
- 按学生查看：历史试卷成绩与平均分。
- 导出 Excel（至少包含学生、试卷、总分、各题得分）。

### 1.3 MVP 暂不做项（明确延期）
- 跨班级复杂对比看板（高级图表）。
- 移动端全部管理功能（MVP 仅保留上传与查分）。
- 主观题自动最终判分（必须人工确认）。
- 复杂权限系统、消息通知中心、操作审计高级能力。

---

## 2. 数据库迁移优先级

### P0（第一批，必须先落库）
1. `users`（认证基础）
2. `teachers`（教师扩展信息）
3. `classes`
4. `students`
5. `questions`
6. `quizzes`
7. `quiz_question_items`
8. `quiz_assignments`
9. `submissions`
10. `answer_details`

说明：P0 完成后可跑通主业务链路。

### P1（第二批，提升可用性与性能）
- 为高频查询补索引：
  - `students.class_id`
  - `questions.teacher_id`
  - `quizzes.teacher_id, quizzes.status`
  - `submissions.quiz_id, submissions.student_id, submissions.status`
  - `answer_details.submission_id, answer_details.question_id`
- 为关键唯一性加约束：
  - `students.student_id`（已是主键）
  - `teachers.user_id` 唯一
  - 可选：`classes(class_num, enrollment_year)` 组合唯一

### P2（第三批，稳定性与扩展）
- 增加软删除字段（可选）：`deleted_at`（学生、题目、试卷）。
- 增加处理追踪字段（建议）：
  - `submissions.processing_status`（uploaded/ocr_done/scored/reviewed/failed）
  - `submissions.processing_error`（文本）
- 可选新增任务表：`ocr_jobs`（异步队列可观测性）。

---

## 3. 接口清单（Laravel <-> Python）

## 3.1 总体原则
- Laravel 作为业务主入口，Python 作为算法服务。
- Laravel -> Python 使用 HTTP/JSON（内部服务）或消息队列（后续可演进）。
- 图片文件由 Laravel 持久化，Python 通过可访问路径或对象存储 URL 拉取。

## 3.2 Laravel 对 Python（调用方：Laravel）

### 1. 生成答题卡
- `POST /py/api/v1/answer-sheet/generate`
- 请求体：
```json
{
  "quiz_id": 1001,
  "title": "七年级上学期数学单元测",
  "questions": [
    {"question_id": 1, "type": "单选", "score": 2, "sort_order": 1},
    {"question_id": 2, "type": "主观", "score": 10, "sort_order": 2}
  ],
  "layout": {"paper": "A4", "dpi": 300}
}
```
- 响应体：
```json
{
  "answer_card_path": "answer_cards/quiz_1001_v1.png",
  "width": 2480,
  "height": 3508,
  "version": "v1"
}
```

### 2. OCR + 自动判分
- `POST /py/api/v1/submissions/score`
- 请求体：
```json
{
  "submission_id": 90001,
  "quiz_id": 1001,
  "student_id": "20260101",
  "image_path": "submissions/2026/03/06/90001.jpg",
  "answer_key": [
    {"question_id": 1, "type": "单选", "correct_answer": "B", "score": 2},
    {"question_id": 2, "type": "主观", "score": 10}
  ]
}
```
- 响应体：
```json
{
  "submission_id": 90001,
  "objective_total": 18,
  "subjective_total_suggested": 12,
  "answers": [
    {
      "question_id": 1,
      "student_answer": "B",
      "is_correct": 1,
      "earned_score": 2,
      "ai_suggested_score": null,
      "confidence": 0.98
    },
    {
      "question_id": 2,
      "student_answer": "解：......",
      "is_correct": null,
      "earned_score": 0,
      "ai_suggested_score": 8,
      "confidence": 0.76
    }
  ],
  "raw_artifacts": {
    "aligned_image_path": "artifacts/aligned/90001.jpg",
    "ocr_json_path": "artifacts/ocr/90001.json"
  }
}
```

### 3. 健康检查
- `GET /py/api/v1/health`
- 响应：`{"status":"ok","ocr":"ready","llm":"ready"}`

## 3.3 Python 回 Laravel（可选，异步场景）

### 1. 批改结果回调
- `POST /api/internal/python/callbacks/scoring-result`
- 鉴权：`X-Internal-Token`
- 语义：Python 异步完成后推送结果，Laravel 入库并更新状态。

### 2. 失败回调
- `POST /api/internal/python/callbacks/scoring-failed`
- 语义：写入失败原因，标记 `processing_status=failed`，便于前端重试。

## 3.4 错误码约定（建议）
- `0`: success
- `1001`: image_not_found
- `1002`: image_quality_low
- `1003`: anchor_not_detected
- `1004`: ocr_failed
- `1005`: llm_timeout
- `1006`: invalid_answer_key
- `1099`: internal_error

---

## 4. 风险点与验收标准

## 4.1 核心风险点

### R1. 答题卡拍照质量不稳定
- 风险：倾斜、阴影、模糊导致定位失败和识别误差。
- 对策：
  - 上传前端增加清晰度与尺寸提示。
  - Python 侧增加图像质量检测与失败原因返回。
  - 支持失败后重传，不阻塞其它提交。

### R2. 客观题误判
- 风险：涂卡不规范、擦除痕迹导致多选/空选判断偏差。
- 对策：
  - 阈值可配置（灰度阈值、最小填涂面积）。
  - 低置信度题目标记给教师优先复核。

### R3. 主观题 OCR 与 AI 建议偏差
- 风险：手写体难识别；AI 建议分不稳定。
- 对策：
  - 明确 AI 分数仅建议值，默认不自动计入最终分。
  - 教师复核为强制步骤（提交前必须确认）。

### R4. 跨服务稳定性
- 风险：Laravel 与 Python 网络抖动、超时、重复请求。
- 对策：
  - 请求超时 + 重试（幂等键：`submission_id`）。
  - 任务状态机与错误日志可追踪。

### R5. 数据一致性
- 风险：`answer_details` 写入中断导致总分与明细不一致。
- 对策：
  - Laravel 入库采用事务。
  - 总分由明细重算，避免手工累计误差。

### R6. 隐私与安全
- 风险：学生信息、答题卡图片泄漏。
- 对策：
  - 文件访问鉴权（非公开目录 + 临时签名 URL）。
  - 内部接口 token 校验。
  - 操作日志最小化存储敏感信息。

## 4.2 MVP 验收标准（可测试）

### A. 流程验收
- 可从零完成一次完整流程：建卷 -> 生成答题卡 -> 上传 -> 自动批改 -> 教师复核 -> 成绩查询/导出。
- 单份试卷可发布至多个班级并产生独立提交记录。

### B. 功能正确性验收
- 客观题：标准样例集上自动判分准确率 >= 95%。
- 主观题：AI 建议分可生成，且教师可逐题修改并保存。
- 总分：等于所有 `answer_details.earned_score` 之和。

### C. 性能验收（MVP 基线）
- 单张答题卡（A4，清晰照片）处理时延 <= 8 秒（不含人工复核）。
- 批量 30 张提交时系统可稳定处理，失败任务可重试。

### D. 稳定性验收
- Python 服务不可用时，Laravel 可提示明确错误且不崩溃。
- 任意提交处理失败后可重新发起处理，不产生重复脏数据。

### E. 安全与数据验收
- 登录鉴权生效，未授权用户无法访问成绩与图片。
- 删除/编辑操作不破坏成绩历史（关键业务数据可追溯）。

---

## 5. 推荐实施顺序（2-4 周）
1. 第 1 周：数据库 P0 + Laravel 基础 CRUD（教师/班级/学生/题库/试卷）。
2. 第 2 周：答题卡生成接口联调 + 上传与提交链路打通。
3. 第 3 周：OCR/客观题判分 + 主观题建议分 + 教师复核页面。
4. 第 4 周：成绩查询导出 + 稳定性修复 + MVP 验收测试。
