import { ref } from "vue";
import type { ClassOption, QuizRow, SheetRow } from "./types";

// 页面共享状态与 UI 工具函数
export const useTestState = () => {
  const layoutContainer = ref<HTMLElement>();

  let layout: any = null;
  let testToolbar: any = null;
  let testGrid: any = null;
  let sheetToolbar: any = null;
  let sheetGrid: any = null;

  let highlightedQuizRowId: string | number | null = null;
  let highlightedSheetRowId: string | number | null = null;

  const errorMessage = ref("");
  const successMessage = ref("");
  const isSheetLoading = ref(false);
  const selectedQuizId = ref<number | null>(null);
  const selectedSheetId = ref<number | null>(null);

  const quizzesCache = ref<QuizRow[]>([]);
  const sheetsCache = ref<SheetRow[]>([]);
  const classOptions = ref<ClassOption[]>([]);

  const showSuccess = (message: string) => {
    successMessage.value = message;
    errorMessage.value = "";
    setTimeout(() => (successMessage.value = ""), 3000);
  };

  const showError = (message: string) => {
    errorMessage.value = message;
    successMessage.value = "";
  };

  const quizStatusTemplate = (value: string) => {
    if (!value) return "";
    let statusClass = "";
    if (value === "草稿") statusClass = "dhx-demo_grid-status--not-started";
    if (value === "已发布") statusClass = "dhx-demo_grid-status--done";
    if (value === "已结束" || value === "已归档")
      statusClass = "dhx-demo_grid-status--in-progress";

    return `<div class='dhx-demo_grid-template'><div class='dhx-demo_grid-status ${statusClass}'></div><span>${value}</span></div>`;
  };

  const questionTypeTemplate = (value: string) => {
    if (!value) return "";
    let statusClass = "";
    if (value === "单选" || value === "判断")
      statusClass = "dhx-demo_grid-status--done";
    if (value === "多选") statusClass = "dhx-demo_grid-status--in-progress";
    if (value === "主观") statusClass = "dhx-demo_grid-status--not-started";

    return `<div class='dhx-demo_grid-template'><div class='dhx-demo_grid-status ${statusClass}'></div><span>${value}</span></div>`;
  };

  const setQuizRowHighlight = (rowId: string | number) => {
    if (!testGrid) return;
    if (highlightedQuizRowId !== null && highlightedQuizRowId !== rowId) {
      testGrid.removeRowCss(highlightedQuizRowId, "selected-test-row");
    }
    testGrid.addRowCss(rowId, "selected-test-row");
    highlightedQuizRowId = rowId;
  };

  const clearQuizRowHighlight = () => {
    if (!testGrid || highlightedQuizRowId === null) return;
    testGrid.removeRowCss(highlightedQuizRowId, "selected-test-row");
    highlightedQuizRowId = null;
  };

  const setSheetRowHighlight = (rowId: string | number) => {
    if (!sheetGrid) return;
    if (highlightedSheetRowId !== null && highlightedSheetRowId !== rowId) {
      sheetGrid.removeRowCss(highlightedSheetRowId, "selected-sheet-row");
    }
    sheetGrid.addRowCss(rowId, "selected-sheet-row");
    highlightedSheetRowId = rowId;
  };

  const clearSheetRowHighlight = () => {
    if (!sheetGrid || highlightedSheetRowId === null) return;
    sheetGrid.removeRowCss(highlightedSheetRowId, "selected-sheet-row");
    highlightedSheetRowId = null;
  };

  const setQuestionActionsDisabled = (disabled: boolean) => {
    if (!sheetToolbar?.data) return;
    sheetToolbar.data.update("editQuestion", { disabled });
    sheetToolbar.data.update("deleteQuestion", { disabled });
  };

  const setQuizActionsDisabled = (disabled: boolean) => {
    if (!testToolbar?.data) return;
    testToolbar.data.update("editQuiz", { disabled });
    testToolbar.data.update("deleteQuiz", { disabled });
  };

  return {
    layoutContainer,
    get layout() {
      return layout;
    },
    set layout(v: any) {
      layout = v;
    },
    get testToolbar() {
      return testToolbar;
    },
    set testToolbar(v: any) {
      testToolbar = v;
    },
    get testGrid() {
      return testGrid;
    },
    set testGrid(v: any) {
      testGrid = v;
    },
    get sheetToolbar() {
      return sheetToolbar;
    },
    set sheetToolbar(v: any) {
      sheetToolbar = v;
    },
    get sheetGrid() {
      return sheetGrid;
    },
    set sheetGrid(v: any) {
      sheetGrid = v;
    },
    errorMessage,
    successMessage,
    isSheetLoading,
    selectedQuizId,
    selectedSheetId,
    quizzesCache,
    sheetsCache,
    classOptions,
    showSuccess,
    showError,
    quizStatusTemplate,
    questionTypeTemplate,
    setQuizRowHighlight,
    clearQuizRowHighlight,
    setSheetRowHighlight,
    clearSheetRowHighlight,
    setQuestionActionsDisabled,
    setQuizActionsDisabled,
  };
};
