# 文档重组状态

更新时间：2026-03-10

## 1. 当前约束

- Laravel 端口：`8000`
- Vue3 端口：`8001`
- Python 端口：不固定（由环境配置决定）
- 项目主文档：每个 Markdown 控制在 200 行以内
- 文档示例：保留最小必要信息，不放大段样例

## 2. 本次调整结果

已精简并统一口径的文档：
- `docs/technical/architecture.md`
- `docs/technical/database.md`
- `docs/technical/api-frontend.md`
- `docs/technical/api-backend.md`
- `docs/guides/quick-start.md`
- `docs/guides/setup.md`
- `docs/guides/deployment-production.md`
- `docs/product/implementation.md`
- `docs/guides/deployment.md`

## 3. 行数检查（已通过）

- `docs/guides/setup.md`: 89
- `docs/guides/deployment-production.md`: 88
- `docs/product/implementation.md`: 80
- `docs/technical/architecture.md`: 75
- `docs/technical/database.md`: 71
- `docs/technical/api-backend.md`: 65
- `docs/technical/api-frontend.md`: 64
- `docs/guides/quick-start.md`: 59
- `docs/guides/deployment.md`: 37

## 4. 说明

- 历史开发日志与归档文档保持原状（`docs/dev-logs/`、`PRDs/`）。
- 若你希望我继续把历史文档也压到 200 行以内，我可以按按月拆分/按主题拆分再处理一轮。
