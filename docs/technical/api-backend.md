# 后端服务 API 文档

本文档描述 Laravel 与 Python 服务之间的内部接口。

## 1. 约定

- 调用方：Laravel
- 被调用方：Python OCR 服务
- 鉴权头：`X-Internal-Token`
- Python 端口：不固定，由部署配置决定

## 2. 主要接口

### 2.1 生成答题卡

- `POST /py/api/v1/answer-sheet/generate`
- 输入：试卷标题、题目列表、分值、排序
- 输出：答题卡文件路径与版本信息

### 2.2 OCR 与自动判分

- `POST /py/api/v1/submissions/score`
- 输入：submission_id、图片路径、答案配置
- 输出：每题识别结果、客观题得分、主观题建议分

### 2.3 健康检查

- `GET /py/api/v1/health`
- 输出：服务状态

## 3. 回调接口（可选）

- `POST /api/internal/python/callbacks/scoring-result`
- `POST /api/internal/python/callbacks/scoring-failed`

用于异步任务完成后回传结果。

## 4. 错误码（Python）

- `1001` 图片不存在
- `1002` 图片质量低
- `1003` 定位点失败
- `1004` OCR 失败
- `1005` AI 超时
- `1006` 答案配置错误
- `1099` 内部错误

## 5. Laravel 配置示例

```env
PYTHON_SERVICE_URL=http://127.0.0.1:<python_port>
PYTHON_INTERNAL_TOKEN=your-token
```

## 6. 联调建议

- 先调通健康检查
- 再联调答题卡生成
- 最后联调 OCR/判分
- 低置信度题目标记人工复核

## 7. 相关文档

- [前端 API](api-frontend.md)
- [OMR API 使用](../../backend-ocr/code/README_API_OMR.md)
