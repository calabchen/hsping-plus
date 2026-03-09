import api from "@/services/api";
import type { ClassOption, QuizRow, SheetRow } from "./types";

// 数据加载层：只负责请求与映射
export const useTestData = (deps: {
  quizzesCache: { value: QuizRow[] };
  sheetsCache: { value: SheetRow[] };
  classOptions: { value: ClassOption[] };
  selectedQuizId: { value: number | null };
  selectedSheetId: { value: number | null };
  testGridRef: () => any;
  sheetGridRef: () => any;
  setQuizRowHighlight: (rowId: string | number) => void;
  clearSheetRowHighlight: () => void;
  setQuestionActionsDisabled: (disabled: boolean) => void;
  isSheetLoading: { value: boolean };
  showError: (message: string) => void;
}) => {
  const MIN_SHEET_LOADING_MS = 1000;
  const sleep = (ms: number) =>
    new Promise((resolve) => setTimeout(resolve, ms));

  const loadClassOptions = async () => {
    const response = await api.get("/api/classes");
    const rows = response.data?.classes || [];
    deps.classOptions.value = rows.map((item: any) => {
      const year = item.enrollment_year ? String(item.enrollment_year) : "";
      const num = item.class_num ? String(item.class_num) : "";
      return {
        value: String(item.class_id),
        content: year ? `${year}级${num}班` : num,
      };
    });
  };

  const loadQuizzes = async () => {
    try {
      const response = await api.get("/api/quizzes");
      deps.quizzesCache.value = (response.data?.quizzes || []).map(
        (quiz: any) => ({
          id: Number(quiz.quiz_id),
          testName: String(quiz.title || ""),
          number: Number(quiz.question_count || 0),
          startDate: String(quiz.start_time || quiz.created_at || "").slice(
            0,
            10,
          ),
          startTime: quiz.start_time ? String(quiz.start_time) : null,
          status: (quiz.status || "草稿") as QuizRow["status"],
        }),
      );

      const testGrid = deps.testGridRef();
      if (testGrid) {
        testGrid.data.parse(deps.quizzesCache.value);
        if (deps.selectedQuizId.value)
          deps.setQuizRowHighlight(deps.selectedQuizId.value);
      }
    } catch (error: any) {
      deps.showError(error.response?.data?.message || "加载试卷失败");
    }
  };

  const loadQuizItems = async (quizId: number) => {
    const startedAt = Date.now();
    deps.isSheetLoading.value = true;
    try {
      const response = await api.get(`/api/quizzes/${quizId}/items`);
      deps.sheetsCache.value = (response.data?.items || []).map(
        (item: any) => ({
          id: Number(item.question_id), // 数据库主键PK，用于API删除/更新
          questionId: Number(item.sequence_number), // 用户面向的题号，用于显示
          questionType: String(item.type || ""),
          correctOption: String(item.answer || ""),
          score: Number(item.score || 0),
        }),
      );

      const sheetGrid = deps.sheetGridRef();
      if (sheetGrid) {
        sheetGrid.data.parse(deps.sheetsCache.value);
        deps.selectedSheetId.value = null;
        deps.clearSheetRowHighlight();
        deps.setQuestionActionsDisabled(true);
      }
    } catch (error: any) {
      deps.showError(error.response?.data?.message || "加载试卷题目失败");
    } finally {
      const elapsed = Date.now() - startedAt;
      if (elapsed < MIN_SHEET_LOADING_MS) {
        await sleep(MIN_SHEET_LOADING_MS - elapsed);
      }
      deps.isSheetLoading.value = false;
    }
  };

  const loadSheetItemsByQuiz = async (quizId: number) => {
    deps.selectedQuizId.value = quizId;
    deps.selectedSheetId.value = null;
    deps.clearSheetRowHighlight();
    deps.setQuestionActionsDisabled(true);
    await loadQuizItems(quizId);
  };

  return { loadClassOptions, loadQuizzes, loadQuizItems, loadSheetItemsByQuiz };
};
