# OMR API Generator

`omr_from_api.py` 用于从 Laravel API 获取试卷与题目数据，自动组装为临时 JSON，并调用 `omr_circle_generator.py` 生成答题卡。

## 依赖

- Python 3.9+
- `requests`
- 可选：`pypdfium2`（用于将 PDF 高兼容转换为 PNG）

安装示例：

```bash
pip install requests
pip install pypdfium2
```

## 用法

```bash
python omr_from_api.py \
  --api_base http://127.0.0.1:8000 \
  --quiz_id 12 \
  --output_dir ./out \
  --output_format pdf \
  --token YOUR_BEARER_TOKEN
```

如果使用 Session Cookie 认证，可改为：

```bash
python omr_from_api.py \
  --api_base http://127.0.0.1:8000 \
  --quiz_id 12 \
  --output_dir ./out \
  --output_format png \
  --cookie "laravel_session=...; XSRF-TOKEN=..."
```

参数说明：

- `--api_base`: Laravel 服务地址。
- `--quiz_id`: 试卷 ID。
- `--output_dir`: 答题卡输出目录。
- `--output_format`: `png` 或 `pdf`。
- `--token`: 可选；API 需要认证时传入 Bearer Token。
- `--cookie`: 可选；API 走 Cookie 会话认证时传入完整 Cookie 头。
