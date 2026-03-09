# HsPing Plus - 教师智能试卷批改系统

> 一个面向中学教师的个人智能试卷批改与成绩分析系统

## 📖 项目简介

HsPing Plus 是一个完整的教学辅助工具，帮助中学教师高效管理试卷、自动生成答题卡、智能批改学生作业并提供多维度成绩分析。系统支持线下纸质答题卡与线上数字化批改的混合模式，客观题通过 OCR 自动判分，主观题使用 AI 大模型生成建议分数供教师复核。

### 核心功能

- 📝 **试卷管理**：题库管理、试卷创建、答题卡自动生成
- 📷 **智能批改**：OCR 识别、客观题自动判分、主观题 AI 辅助评分
- 📊 **成绩分析**：班级统计、学生个人分析、多维度数据可视化
- 👥 **班级管理**：学生信息维护、班级组织、批量导入
- 📱 **多端支持**：PC 网页端管理 + 移动端拍照上传

## 🚀 快速开始

### 测试账号

1. 账号：`lihua@teacher.com`；密码：`abcdefgh`
2. 账号：`xiaohong@teacher.com`；密码：`12345678`

### 本地部署

详细的部署指南请查看 [部署指南](docs/guides/deployment.md)

## 📁 项目结构

```
hsping-plus/
├── backend-laravel/    # Laravel 后端服务
├── backend-ocr/        # Python OCR 服务
│   └── code/          # OCR 核心代码
├── frontend-pc/        # Vue3 PC 网页端
├── frontend-mobile/    # uni-app 移动端
├── docs/              # 项目文档
│   ├── index.md       # 开发历程
│   ├── product/       # 产品文档
│   ├── technical/     # 技术文档
│   ├── guides/        # 使用指南
│   └── dev-logs/      # 开发日志
└── PRDs/              # 产品需求文档（归档）
```

## 🛠 技术栈

### 前端

- **PC 端**：Vue 3 + TypeScript + Vite + DHTMLX Suite
- **移动端**：uni-app（微信小程序/H5）

### 后端

- **主服务**：Laravel 11 + PHP 8.2
- **OCR 服务**：Python 3.9+ + OpenCV + PIL
- **数据库**：MySQL 8.0
- **认证**：Laravel Sanctum

### AI & OCR

- OCR 识别：自研答题卡识别引擎
- AI 评分：大模型 API 集成（主观题辅助）

## 📚 文档导航

### 产品文档

- [产品概述](docs/product/overview.md) - 系统背景、目标和功能规划
- [实施清单](docs/product/implementation.md) - MVP 功能清单和验收标准

### 技术文档

- [系统架构](docs/technical/architecture.md) - 整体架构设计和模块划分
- [数据库设计](docs/technical/database.md) - 数据表结构和关系
- [API 集成](docs/technical/api-integration.md) - Laravel 与 Python 服务接口

### 使用指南

- [快速开始](docs/guides/quick-start.md) - 快速上手指南
- [部署指南](docs/guides/deployment.md) - 本地和生产环境部署

### 开发日志

- [开发历程](docs/index.md) - 4 个月开发节点回顾
- [2026年03月](docs/dev-logs/2026-03.md) - 论文编写期
- [2026年02月](docs/dev-logs/2026-02.md) - 性能冲刺期
- [2026年01月](docs/dev-logs/2026-01.md) - 核心开发期
- [2025年12月](docs/dev-logs/2025-12.md) - 需求调研期

### OCR 相关

- [OMR API 使用](backend-ocr/code/README_API_OMR.md) - OCR API 调用说明
- [OMR 处理指南](backend-ocr/code/OMR_GUIDE.md) - 答题卡生成与识别详细指南

## 🔧 开发与维护

### 环境要求

- Node.js 18+
- PHP 8.2+
- Python 3.9+
- MySQL 8.0+
- Composer
- pnpm

### 分支说明

- `main` - 生产环境稳定版本
- `develop` - 开发分支
- `feature/*` - 功能分支

## 📄 许可证

本项目为毕业设计项目，仅供学习和研究使用。

## 👨‍💻 作者

毕业设计项目 - 2025.12 至 2026.03
