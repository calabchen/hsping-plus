import * as dhx from "dhx-suite";
import api from "@/services/api";

// 试卷层操作：新建/编辑/删除/发布
export const useQuizActions = (deps: {
  selectedQuizId: { value: number | null };
  quizzesCache: { value: any[] };
  classOptions: { value: any[] };
  clearQuizRowHighlight: () => void;
  clearSheetRowHighlight: () => void;
  setQuestionActionsDisabled: (disabled: boolean) => void;
  selectedSheetId: { value: number | null };
  sheetsCache: { value: any[] };
  sheetGridRef: () => any;
  showError: (message: string) => void;
  showSuccess: (message: string) => void;
  openFormDialog: (...args: any[]) => Promise<Record<string, any> | null>;
  getCurrentStartFields: () => { start_date: string; start_clock: string };
  splitStartTime: (startTime: string | null) => {
    start_date: string;
    start_clock: string;
  };
  combineStartTime: (dateValue: unknown, timeValue: unknown) => string | null;
  loadQuizzes: () => Promise<void>;
  loadQuizItems: (quizId: number) => Promise<void>;
}) => {
  const createQuiz = async () => {
    const now = deps.getCurrentStartFields();
    const values = await deps.openFormDialog(
      "新建测验",
      [
        {
          type: "input",
          name: "title",
          label: "测验名称",
          labelPosition: "left",
          required: true,
        },
        {
          type: "select",
          name: "status",
          label: "状态",
          labelPosition: "left",
          options: [
            { value: "草稿", content: "草稿" },
            { value: "已发布", content: "已发布" },
            { value: "已结束", content: "已结束" },
          ],
        },
        {
          type: "datepicker",
          mode: "calendar",
          name: "start_date",
          label: "开始日期",
          labelWidth: "100px",
          labelPosition: "left",
          placeholder: "选择日期",
          required: true,
          editable: false,
          dateFormat: "%Y-%m-%d",
        },
        {
          type: "timepicker",
          name: "start_clock",
          label: "开始时间",
          labelWidth: "100px",
          labelPosition: "left",
          placeholder: "选择时间",
          required: true,
          editable: false,
          controls: false,
          timeFormat: 24,
        },
      ],
      {
        title: "",
        status: "草稿",
        start_date: now.start_date,
        start_clock: now.start_clock,
      },
    );
    if (!values) return;

    const startTime = deps.combineStartTime(
      values.start_date,
      values.start_clock,
    );
    if (!startTime) return deps.showError("开始日期和开始时间为必填项");

    try {
      await api.post("/api/quizzes", {
        title: String(values.title || "").trim(),
        status: String(values.status || "草稿"),
        start_time: startTime,
      });
      deps.showSuccess("测验创建成功");
      await deps.loadQuizzes();
    } catch (error: any) {
      deps.showError(error.response?.data?.message || "创建测验失败");
    }
  };

  const editQuiz = async () => {
    if (!deps.selectedQuizId.value) return deps.showError("请先选择一个测验");
    const quiz = deps.quizzesCache.value.find(
      (item) => item.id === deps.selectedQuizId.value,
    );
    if (!quiz) return;

    const now = deps.getCurrentStartFields();
    const cur = deps.splitStartTime(quiz.startTime);
    const values = await deps.openFormDialog(
      `编辑测验：${quiz.testName}`,
      [
        {
          type: "input",
          name: "title",
          label: "测验名称",
          labelPosition: "left",
          required: true,
        },
        {
          type: "select",
          name: "status",
          label: "状态",
          labelPosition: "left",
          options: [
            { value: "草稿", content: "草稿" },
            { value: "已发布", content: "已发布" },
            { value: "已结束", content: "已结束" },
            { value: "已归档", content: "已归档" },
          ],
        },
        {
          type: "datepicker",
          mode: "calendar",
          name: "start_date",
          label: "开始日期",
          labelWidth: "100px",
          labelPosition: "left",
          placeholder: "选择日期",
          required: true,
          editable: false,
          dateFormat: "%Y-%m-%d",
        },
        {
          type: "timepicker",
          name: "start_clock",
          label: "开始时间",
          labelWidth: "100px",
          labelPosition: "left",
          placeholder: "选择时间",
          required: true,
          editable: false,
          controls: false,
          timeFormat: 24,
        },
      ],
      {
        title: quiz.testName,
        status: quiz.status,
        start_date: cur.start_date || now.start_date,
        start_clock: cur.start_clock || now.start_clock,
      },
    );
    if (!values) return;

    const startTime = deps.combineStartTime(
      values.start_date,
      values.start_clock,
    );
    if (!startTime) return deps.showError("开始日期和开始时间为必填项");

    try {
      await api.put(`/api/quizzes/${deps.selectedQuizId.value}`, {
        title: String(values.title || "").trim(),
        status: String(values.status || "草稿"),
        start_time: startTime,
      });
      deps.showSuccess("测验更新成功");
      await deps.loadQuizzes();
    } catch (error: any) {
      deps.showError(error.response?.data?.message || "更新测验失败");
    }
  };

  const deleteQuiz = async () => {
    if (!deps.selectedQuizId.value) return deps.showError("请先选择一个测验");

    dhx
      .confirm({
        header: "确认删除测验",
        text: "删除后不可恢复，是否继续？",
        buttons: ["取消", "确认删除"],
        buttonsAlignment: "center",
      })
      .then(async (result) => {
        if (!result || !deps.selectedQuizId.value) return;
        try {
          await api.delete(`/api/quizzes/${deps.selectedQuizId.value}`);
          deps.showSuccess("测验已删除");
          deps.selectedQuizId.value = null;
          deps.selectedSheetId.value = null;
          deps.clearQuizRowHighlight();
          deps.clearSheetRowHighlight();
          deps.setQuestionActionsDisabled(true);
          deps.sheetsCache.value = [];
          deps.sheetGridRef()?.data.parse([]);
          await deps.loadQuizzes();
        } catch (error: any) {
          deps.showError(error.response?.data?.message || "删除测验失败");
        }
      });
  };

  const assignQuizToClasses = async () => {
    if (!deps.selectedQuizId.value) return deps.showError("请先选择一个测验");
    if (deps.classOptions.value.length === 0)
      return deps.showError("暂无可发布班级");

    const values = await deps.openFormDialog(
      "发布到班级",
      [
        {
          type: "combo",
          name: "classIds",
          label: "班级（可多选）",
          labelPosition: "left",
          multiselection: true,
          data: deps.classOptions.value,
        },
      ],
      { classIds: [] },
      620,
      280,
    );
    if (!values) return;

    const selectedValues = Array.isArray(values.classIds)
      ? values.classIds
      : String(values.classIds || "")
          .split(",")
          .map((item) => item.trim())
          .filter(Boolean);
    const classIds = selectedValues
      .map((item: string) => Number(item))
      .filter((item: number) => Number.isFinite(item));
    if (classIds.length === 0) return deps.showError("请选择至少一个班级");

    try {
      const response = await api.post(
        `/api/quizzes/${deps.selectedQuizId.value}/assignments`,
        { class_ids: classIds },
      );
      deps.showSuccess(
        `发布完成，新增${response.data?.created_count ?? 0}个班级`,
      );
      await deps.loadQuizItems(deps.selectedQuizId.value);
    } catch (error: any) {
      deps.showError(error.response?.data?.message || "发布失败");
    }
  };

  return { createQuiz, editQuiz, deleteQuiz, assignQuizToClasses };
};
