# 教师个人智能试卷批改系统 - 产品文档

## 1. 项目概述

### 1.1 项目背景
本系统旨在帮助中学教师个人高效管理试卷、生成答题卡，并通过上传学生填涂后的答题卡图片实现自动/半自动批改。系统针对教师单个学科使用，支持线下纸质答题卡与线上数字化批改的混合模式。客观题通过OCR自动判分，主观题使用AI大模型生成建议分数，由教师最终复核确认。同时提供班级及学生成绩的多维度统计分析。

目标用户：中学教师（单用户模式，无需多用户协作，无学生端登录）

核心特点：
- 生成独立电子答题卡图片（PNG/JPG），教师可插入Word试卷中打印
- 学生纸质填涂答题卡后，教师/学生用手机或电脑拍照上传
- 客观题自动判分，主观题OCR提取文字 + AI建议分数 + 教师复核
- 支持班级成绩统计、学生多试卷平均分分析等

### 1.2 项目目标
- 功能目标：实现试卷从创建、答题卡生成、批改到成绩分析的全流程
- 技术目标：前后端分离，轻量可维护；集成OCR和AI大模型辅助批改
- 用户体验目标：极简浅色风格，操作流畅；移动端重点支持拍照上传

### 1.3 假设与约束
- 假设：仅一名教师使用；学生信息由教师手动维护；AI/OCR准确率非100%，需教师复核最终得分
- 约束：无学校级多用户/多学科支持；不涉及学生端登录；数据存储在MySQL，图片文件存储在服务器本地或对象存储

## 2. 功能需求

### 2.1 用户管理模块（教师个人）
- 教师登录/注册：用户名 + 密码登录；支持忘记密码（邮箱重置）
- 个人信息管理：查看/编辑姓名、学科、性别、电话、邮箱；修改密码

### 2.2 班级与学生管理模块
- 班级管理：创建/编辑/删除班级（班级号、入学年、毕业年、是否毕业）
- 学生管理：增删改查学生信息（学号、姓名、性别、年龄、头像图片）；支持手动添加或简单批量导入

### 2.3 题库管理模块
- 题目创建/管理：支持单选、多选、判断、主观四种题型；字段包括：内容、选项（JSON）、正确答案、解析

### 2.4 试卷管理模块
- 试卷创建：设置试卷标题、题号列表、每题分数、排序；直接生成电子答题卡图片（PNG/JPG）
- 试卷编辑/删除：草稿状态下可编辑
- 试卷状态管理：草稿 / 已发布 / 已结束 / 已归档
- 向班级发布试卷：一份试卷可发布给多个班级（仅记录关联关系）

### 2.5 答题卡生成与批改模块
- 答题卡生成：基于试卷设置自动生成图片，包含：
  - 个人信息填写区（班级、学号、姓名）
  - 客观题填涂区（泡泡/方格）
  - 主观题答题空白区
  - 定位点（用于图像矫正与区域定位）
- 答题卡上传：支持PC网页上传或uni-app移动端（相机/相册）上传，支持批量上传
- 客观题自动判分：使用Python OCR识别填涂情况，比对正确答案计算得分
- 主观题处理：OCR提取手写/印刷文字 → 调用AI大模型生成建议分数 → 存入数据库
- 教师批改：查看OCR提取结果 + AI建议分数 → 确认或修改每题最终得分
- 提交记录：保存答题卡图片路径、每题得分、总分、批改状态

### 2.6 成绩查看与分析模块
- 成绩查看：按试卷、按学生、按班级查看总分及每题得分；支持导出Excel
- 学生个人分析：查看该学生多份试卷的平均分、错误率、成绩折线图
- 班级分析：多份试卷的班级平均分、题均得分、得分分布直方图
- 跨班级对比：单份试卷在不同班级的平均分、题均得分分布对比

### 2.7 系统级功能
- 全局搜索：支持试卷、学生、题目快速搜索
- 数据分页与加载优化

## 3. 数据库设计

使用MySQL，字符集utf8mb4_unicode_ci。

### 3.1 核心表结构

- **users**（教师用户表，仅一条记录或单用户模式）
  - user_id int PK AUTO_INCREMENT
  - username varchar(50) UNIQUE
  - password_hash varchar(255)
  - last_name varchar(50)
  - first_name varchar(50)
  - gender enum('男','女')
  - subject varchar(20)
  - email varchar(100)
  - phone varchar(20)
  - is_active tinyint DEFAULT 1
  - created_at timestamp DEFAULT CURRENT_TIMESTAMP
  - updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

- **teachers**
  - teacher_id int PK AUTO_INCREMENT
  - user_id int UNIQUE FK → users.user_id
  - last_name varchar(50)
  - first_name varchar(50)
  - gender enum('男','女')
  - teacher_no varchar(20) UNIQUE  （教师工号/标识，可为空或用于打印）

- **classes**
  - class_id int PK AUTO_INCREMENT
  - class_num varchar(20)
  - enrollment_year year(4)
  - graduation_year year(4)
  - is_graduated tinyint DEFAULT 0

- **students**
  - student_id varchar(20) PK  （学号）
  - class_id int FK → classes.class_id
  - last_name varchar(50)
  - first_name varchar(50)
  - gender enum('男','女')
  - age tinyint
  - avatar_path varchar(255)  （头像图片存储路径，例如 avatars/stu001.jpg）

  **头像存储说明**：使用文件路径存储（varchar），实际图片文件存放于Laravel storage目录（public disk）或对象存储（OSS/MinIO）。不推荐使用BLOB字段存储二进制图片。

- **questions**
  - question_id int PK AUTO_INCREMENT
  - teacher_id int FK → teachers.teacher_id
  - type enum('单选','多选','判断','主观')
  - content text
  - options longtext  （JSON格式）
  - answer text
  - analysis text

- **quizzes**
  - quiz_id int PK AUTO_INCREMENT
  - teacher_id int FK → teachers.teacher_id
  - title varchar(100)
  - start_time datetime DEFAULT NULL
  - end_time datetime DEFAULT NULL
  - status enum('草稿','已发布','已结束','已归档') DEFAULT '草稿'

- **quiz_question_items**
  - quiz_id int FK → quizzes.quiz_id
  - question_id int FK → questions.question_id
  - score decimal(5,2) DEFAULT 1.00
  - sort_order int DEFAULT 0
  - PK: (quiz_id, question_id)

- **quiz_assignments**
  - assignment_id int PK AUTO_INCREMENT
  - quiz_id int FK → quizzes.quiz_id
  - class_id int FK → classes.class_id
  - assigned_at timestamp DEFAULT CURRENT_TIMESTAMP

- **submissions**
  - submission_id int PK AUTO_INCREMENT
  - quiz_id int FK → quizzes.quiz_id
  - student_id varchar(20) FK → students.student_id
  - total_score decimal(5,2) DEFAULT 0.00
  - status enum('待批改','已完成') DEFAULT '待批改'
  - submit_time datetime DEFAULT CURRENT_TIMESTAMP
  - answer_card_path varchar(255)  （答题卡图片存储路径）

- **answer_details**
  - detail_id int PK AUTO_INCREMENT
  - submission_id int FK → submissions.submission_id
  - question_id int FK → questions.question_id
  - student_answer text  （OCR提取的原始答案文本）
  - is_correct tinyint DEFAULT NULL  （客观题）
  - earned_score decimal(5,2) DEFAULT 0.00  （教师最终确认得分）
  - ai_suggested_score decimal(5,2) DEFAULT NULL  （AI建议分数，仅主观题）

## 4. 技术栈与实现要求

### 4.1 前端
- PC网页：Vue3 + DHTMLX JS Library（极简风格，浅色模式）
- 移动端前端：uni-app（主要实现拍照上传、查看成绩）

### 4.2 后端
- 主后端：Laravel（数据管理、API、文件上传、认证）
- 辅助处理：Python（答题卡生成、OCR识别、AI大模型调用）

### 4.3 数据库
- MySQL

### 4.4 其他
- 部署：云服务器或本地部署

## 5. 交互流程

1. 教师登录 → 试卷管理 → 创建试卷（设置题号、分值、排序） → 生成答题卡图片 → 下载并插入Word试卷打印 → 发布到班级
2. 学生纸质作答 → 教师/学生拍照上传答题卡 → 系统OCR处理 → 客观题自动判分 → 主观题AI建议分数 → 教师复核修改 → 出分
3. 班级管理 → 创建班级 → 添加学生（含头像上传）
4. 成绩分析 → 选择试卷/学生/班级 → 查看统计图表与数据

如果以上内容仍有任何不符合你预期的细节，请直接指出，我会继续调整。