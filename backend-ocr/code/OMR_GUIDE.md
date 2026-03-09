# OMR 合并指南（快速 + 详细）

本文档为合并版，包含原有的 `PROCESS_OMR_GUIDE.md`、`QUICK_TEST.md` 与 `TEST_INSTRUCTIONS.md` 的精华与步骤。适合快速上手与深入理解 OMR 处理流程。

---

## 目录
- 简介
- 快速命令（快速测试）
- 完整测试流程
- 输出说明
- 命令详解与参数
- 故障排查与常见问题
- 高级用法与批量处理
- 技术与实现细节
- 下一步建议

---

## 简介

本项目用于答题卡（OMR）生成与识别，包含：

- 前端：Vue + DHTMLX Suite（位于 `resources/js/`）
- OMR 脚本：Python（位于 `resources/code/`）
- 静态输出：`results/` 中的 JSON 与可视化 PNG

可通过 `process_omr.py` 对答题卡图片进行识别并生成结果。可视化图片用于人工复核和教学查看。

---

## 快速命令（快速测试）

在 macOS / zsh 中快速运行：

```bash
cd /Users/chenyuyang/Code/hsping && mkdir -p results omr_output

# 运行一次识别（替换为实际的 omr 输出文件名）
python3 resources/code/process_omr.py \
  --image omr_output/omr_card_*.png \
  --key resources/asset/2022年12月02日小测_key.json

# 查看结果并打开可视化图片
ls -lh results/visualization_*.png && open results/visualization_*.png
```

更多快速命令和调试命令见下面“完整测试流程 / 调试”部分。

---

## 完整测试流程（步骤化）

1. 进入项目目录：

```bash
cd /Users/chenyuyang/Code/hsping
```

2. 确保输出目录存在：

```bash
mkdir -p omr_output results
```

3. （可选）查看已有答题卡：

```bash
ls -lh omr_output/
```

4. 查看 Key JSON：

```bash
cat resources/asset/2022年12月02日小测_key.json | python3 -m json.tool
```

5. 运行 `process_omr.py`（替换实际文件名）：

```bash
python3 resources/code/process_omr.py \
  --image omr_output/omr_card_*.png \
  --key resources/asset/2022年12月02日小测_key.json
```

如果文件名包含空格或特殊字符，请用引号。

6. 验证生成文件：

```bash
ls -lh results/
cat results/results_omr_card_*.json | python3 -m json.tool
```

7. 打开可视化图片进行人工复核：

```bash
open results/visualization_omr_card_*.png
```

---

## 输出说明

- JSON 结果：`results/results_*.json`，包含 `studentInfo`、`scoring`、`questionScores` 和 `details`。
- 可视化图片：`results/visualization_*.png`，标注填涂和对错，用于人工查看。

注意：Web 服务通常将 `public/` 作为静态资源根目录。如果希望浏览器通过 `/results/...` 直接访问图片，请把可视化 PNG 复制到 `public/results/`：

```bash
mkdir -p public/results
cp results/visualization_*.png public/results/ || true
```

---

## 命令详解与参数

常用参数说明：

- `--image`：必须，答题卡 PNG 路径或通配符
- `--key`：必须，Answer Key JSON 路径
- `--threshold`：可选，默认 `200`，判定为已填涂的亮度阈值（0-255）
- `--output-dir`：可选，默认 `results`

示例：

```bash
python3 resources/code/process_omr.py \
  --image omr_output/omr_card_*.png \
  --key resources/asset/2022年12月02日小测_key.json \
  --threshold 180 \
  --output-dir my_results
```

---

## 故障排查与常见问题

1. ModuleNotFoundError: No module named 'PIL' —— 安装依赖：

```bash
pip install Pillow numpy opencv-python
```

2. 找不到答题卡文件 —— 检查 `omr_output/` 是否存在或使用生成脚本：

```bash
python3 resources/code/omr_circle_generator.py \
  --input_json resources/asset/2022年12月02日小测_key.json \
  --output_dir omr_output/
```

3. JSON 解析错误 —— 验证 Key JSON：

```bash
python3 -c "import json; json.load(open('resources/asset/2022年12月02日小测_key.json'))"
```

4. 识别效果不佳 —— 调整 `--threshold` 参数并重试。也可在 `process_omr.py` 中调整采样/检测逻辑参数。

---

## 高级用法与批量处理

批量处理所有答题卡：

```bash
for img in omr_output/omr_*.png; do
  echo "Processing: $img"
  python3 resources/code/process_omr.py --image "$img" --key resources/asset/2022年12月02日小测_key.json
done
```

---

## 技术与实现细节（摘要）

- 定位块检测：基于 flood-fill / 连通组件检测黑色矩形，并据此定位学号/姓名与题盘区域。
- 答案识别：在每个气泡位置采样平均亮度，低于 `threshold` 则认为被填涂。
- 可视化：将结果绘制到新图片，显示不同颜色表示正确/错误/遗漏等。

建议查看 `resources/code/process_omr.py` 里的注释以了解具体实现细节与可调参数。

---

## 下一步建议

- 若希望在前端 Grid 内联显示可视化图片，请确保 `public/results/` 下有这些 PNG（通过复制 `results/` 中的图片）。
- 可选：为识别添加 OCR（pytesseract）以自动识别学号/姓名文本。
- 可选：实现基于 overlay 的行内预览（如果 DHTMLX Grid 的 `subRow` 在当前构建中表现不稳定）。

---

## 参考与原始文档

保留原始文档以便查阅：

- `PROCESS_OMR_GUIDE.md`（原始详细说明）
- `QUICK_TEST.md`（原始快速命令）
- `TEST_INSTRUCTIONS.md`（原始测试流程与验收标准）

本文件为合并与精简版本，日后可继续扩展为更详细的开发者文档或操作手册。
