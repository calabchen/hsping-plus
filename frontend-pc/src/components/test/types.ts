// 试卷行数据
export type QuizRow = {
  id: number;
  testName: string;
  number: number;
  startDate: string;
  startTime: string | null;
  status: "草稿" | "已发布" | "已结束" | "已归档";
};

// 题目行数据
export type SheetRow = {
  id: number; // question_id (数据库主键)
  questionId: number; // sequence_number (测验内序号，用于显示)
  questionType: string;
  correctOption: string;
  score: number;
};

// 班级选择项
export type ClassOption = {
  value: string;
  content: string;
};

// 题目编辑弹窗返回结构
export type QuestionEditorResult = {
  sequence_number?: string; // 测验内序号(1,2,3...)
  sort_order?: string;
  score: string;
  type: "单选" | "多选" | "判断" | "主观";
  ans_A?: boolean;
  ans_B?: boolean;
  ans_C?: boolean;
  ans_D?: boolean;
  ans_E?: boolean;
  ans_F?: boolean;
  ans_G?: boolean;
  ans_H?: boolean;
  tf_true?: boolean;
  tf_false?: boolean;
  subjective_answer?: string;
};
