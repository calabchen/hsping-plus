import api from "@/services/api";
import type { QuestionEditorResult, SheetRow } from "./types";

// 题目层操作：新增/编辑/删除
export const useQuestionActions = (deps: {
  selectedQuizId: { value: number | null };
  selectedSheetId: { value: number | null };
  sheetsCache: { value: SheetRow[] };
  quizzesCache: { value: any[] };
  sheetGridRef: () => any;
  setQuestionActionsDisabled: (disabled: boolean) => void;
  showError: (message: string) => void;
  showSuccess: (message: string) => void;
  openQuestionEditorDialog: (args: any) => Promise<QuestionEditorResult | null>;
  loadQuizzes: () => Promise<void>;
  loadQuizItems: (quizId: number) => Promise<void>;
}) => {
  // 统一从弹窗值提取标准答案，确保新增/编辑规则一致
  const collectAnswer = (values: QuestionEditorResult) => {
    const type = String(values.type || "单选");
    const abcd = ["A", "B", "C", "D"];
    const letters = ["A", "B", "C", "D", "E", "F", "G", "H"];
    const selectedABCD = abcd.filter((x) =>
      Boolean((values as any)[`ans_${x}`]),
    );
    const selectedLetters = letters.filter((x) =>
      Boolean((values as any)[`ans_${x}`]),
    );

    if (type === "单选") {
      // 单选仅能选择一个
      if (selectedABCD.length !== 1)
        throw new Error("单选题只能勾选一个答案（A-D）");
      return selectedABCD[0];
    }
    if (type === "多选") {
      // 多选至少 2 个，避免误填成单选
      if (selectedLetters.length < 2)
        throw new Error("多选题至少勾选两个答案（A-H）");
      return selectedLetters.join("");
    }
    if (type === "判断") {
      const tfTrue = Boolean(values.tf_true);
      const tfFalse = Boolean(values.tf_false);
      // 判断只能选一个
      if ((tfTrue ? 1 : 0) + (tfFalse ? 1 : 0) !== 1)
        throw new Error("判断题只能勾选一个答案（对/错）");
      return tfTrue ? "T" : "F";
    }

    const subjectiveAnswer = String(values.subjective_answer || "").trim();
    if (!subjectiveAnswer) throw new Error("主观题请填写参考答案");
    return subjectiveAnswer;
  };

  const addQuestionToQuiz = async () => {
    if (!deps.selectedQuizId.value) return deps.showError("请先选择一个测验");

    // 从当前测验的题目中找最大序号
    const nextSequenceNumber =
      deps.sheetsCache.value.reduce((maxSeq, item) => {
        const current = Number(item.questionId || 0);
        return current > maxSeq ? current : maxSeq;
      }, 0) + 1;

    const values = await deps.openQuestionEditorDialog({
      title: "新建题目并加入测验",
      includeQuestionId: true,
      initial: {
        sequence_number: String(nextSequenceNumber),
        type: "单选",
        score: "1",
      },
    });
    if (!values) return;

    const targetSequenceNumber = Number(values.sequence_number);
    if (!Number.isInteger(targetSequenceNumber) || targetSequenceNumber <= 0) {
      return deps.showError("题号必须是正整数");
    }

    try {
      const answer = collectAnswer(values);
      await api.post(`/api/quizzes/${deps.selectedQuizId.value}/items`, {
        sequence_number: targetSequenceNumber,
        type: values.type,
        answer,
        analysis: null,
        score: Number(values.score || 5),
      });
      deps.showSuccess("题目已加入试卷");
      await deps.loadQuizzes();
      await deps.loadQuizItems(deps.selectedQuizId.value);
    } catch (error: any) {
      deps.showError(
        error?.message || error.response?.data?.message || "新增题目失败",
      );
    }
  };

  const removeQuestionFromQuiz = async () => {
    if (!deps.selectedQuizId.value) return deps.showError("请先选择一个测验");

    const selected = deps.sheetGridRef()?.selection?.getCell()?.row?.id;
    if (!selected) return deps.showError("请先选择一个题目");

    const row = deps.sheetGridRef().data.getItem(selected) as SheetRow;
    if (!row?.id) return deps.showError("题目数据无效");

    try {
      // 使用question_id（数据库主键）来删除
      await api.delete(
        `/api/quizzes/${deps.selectedQuizId.value}/items/${row.id}`,
      );
      deps.showSuccess("题目已移除");
      deps.selectedSheetId.value = null;
      deps.setQuestionActionsDisabled(true);
      await deps.loadQuizzes();
      await deps.loadQuizItems(deps.selectedQuizId.value);
    } catch (error: any) {
      deps.showError(error.response?.data?.message || "移除题目失败");
    }
  };

  const editQuestionInQuiz = async () => {
    if (!deps.selectedQuizId.value) return deps.showError("请先选择一个测验");

    const selected = deps.sheetGridRef()?.selection?.getCell()?.row?.id;
    if (!selected) {
      deps.setQuestionActionsDisabled(true);
      return deps.showError("请先高亮选中一个题目");
    }

    const row = deps.sheetGridRef().data.getItem(selected) as SheetRow;
    if (!row?.id || !row?.questionId) return deps.showError("题目数据无效");

    const values = await deps.openQuestionEditorDialog({
      title: `编辑测验题目：第${row.questionId}题`,
      includeQuestionId: true,
      initial: {
        sequence_number: String(row.questionId),
        type: row.questionType as any,
        score: String(row.score || 0),
        subjective_answer: row.correctOption,
      },
    });
    if (!values) return;

    const targetSequenceNumber = Number(values.sequence_number);
    if (!Number.isInteger(targetSequenceNumber) || targetSequenceNumber <= 0) {
      return deps.showError("题号必须是正整数");
    }

    try {
      const answer = collectAnswer(values);
      await api.put(
        `/api/quizzes/${deps.selectedQuizId.value}/items/${row.id}`,
        {
          sequence_number: targetSequenceNumber,
          type: values.type,
          answer,
          analysis: null,
          score: Number(values.score || row.score || 0),
        },
      );

      deps.showSuccess("题目更新成功");
      await deps.loadQuizzes();
      await deps.loadQuizItems(deps.selectedQuizId.value);
    } catch (error: any) {
      deps.showError(
        error?.message || error.response?.data?.message || "编辑题目失败",
      );
    }
  };

  return { addQuestionToQuiz, editQuestionInQuiz, removeQuestionFromQuiz };
};
