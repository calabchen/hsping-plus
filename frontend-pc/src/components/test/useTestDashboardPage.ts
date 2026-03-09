import { onBeforeUnmount, onMounted } from "vue";
import { useRouter } from "vue-router";
import * as dhx from "dhx-suite";
import { Layout as dhxLayout } from "dhx-suite";
import "dhx-suite/codebase/suite.min.css";
import { authStore } from "@/stores/auth";
import api from "@/services/api";
import { useTestState } from "./useTestState";
import { useTestTime } from "./useTestTime";
import { useDhxFormDialog } from "./useDhxFormDialog";
import { useTestData } from "./useTestData";
import { useQuizActions } from "./useQuizActions";
import { useQuestionEditorDialog } from "./useQuestionEditorDialog";
import { useQuestionActions } from "./useQuestionActions";
import { useAnswerSheetActions } from "./useAnswerSheetActions";

// 入口函数
export const useTestDashboardPage = () => {
  const router = useRouter();
  const state = useTestState();
  const time = useTestTime("Asia/Shanghai");
  const { openFormDialog } = useDhxFormDialog();
  const { openQuestionEditorDialog } = useQuestionEditorDialog();

  const data = useTestData({
    quizzesCache: state.quizzesCache,
    sheetsCache: state.sheetsCache,
    classOptions: state.classOptions,
    selectedQuizId: state.selectedQuizId,
    selectedSheetId: state.selectedSheetId,
    testGridRef: () => state.testGrid,
    sheetGridRef: () => state.sheetGrid,
    setQuizRowHighlight: state.setQuizRowHighlight,
    clearSheetRowHighlight: state.clearSheetRowHighlight,
    setQuestionActionsDisabled: state.setQuestionActionsDisabled,
    isSheetLoading: state.isSheetLoading,
    showError: state.showError,
  });

  const quizActions = useQuizActions({
    selectedQuizId: state.selectedQuizId,
    quizzesCache: state.quizzesCache,
    classOptions: state.classOptions,
    clearQuizRowHighlight: state.clearQuizRowHighlight,
    clearSheetRowHighlight: state.clearSheetRowHighlight,
    setQuestionActionsDisabled: state.setQuestionActionsDisabled,
    selectedSheetId: state.selectedSheetId,
    sheetsCache: state.sheetsCache,
    sheetGridRef: () => state.sheetGrid,
    showError: state.showError,
    showSuccess: state.showSuccess,
    openFormDialog,
    getCurrentStartFields: time.getCurrentStartFields,
    splitStartTime: time.splitStartTime,
    combineStartTime: time.combineStartTime,
    loadQuizzes: data.loadQuizzes,
    loadQuizItems: data.loadQuizItems,
  });

  const questionActions = useQuestionActions({
    selectedQuizId: state.selectedQuizId,
    selectedSheetId: state.selectedSheetId,
    sheetsCache: state.sheetsCache,
    quizzesCache: state.quizzesCache,
    sheetGridRef: () => state.sheetGrid,
    setQuestionActionsDisabled: state.setQuestionActionsDisabled,
    showError: state.showError,
    showSuccess: state.showSuccess,
    openQuestionEditorDialog,
    loadQuizzes: data.loadQuizzes,
    loadQuizItems: data.loadQuizItems,
  });

  const answerSheetActions = useAnswerSheetActions({
    selectedQuizId: state.selectedQuizId,
    quizzesCache: state.quizzesCache,
    showError: state.showError,
    showSuccess: state.showSuccess,
  });

  const refreshCurrent = async () => {
    await data.loadQuizzes();
    await data.loadClassOptions();
    if (state.selectedQuizId.value)
      await data.loadSheetItemsByQuiz(state.selectedQuizId.value);
  };

  const createTestGrid = () => {
    const host = document.createElement("div");
    state.testGrid = new (dhx as any).Grid(host, {
      columns: [
        {
          id: "testName",
          header: [
            { text: "测验名称", align: "center" },
            { content: "comboFilter", tooltipTemplate: () => "选择一个测验" },
          ],
          align: "center",
          resizable: true,
        },
        {
          id: "number",
          header: [
            { text: "题目数量", align: "center" },
            { content: "inputFilter" },
          ],
          align: "center",
        },
        {
          id: "startDate",
          header: [
            { text: "创建日期", align: "center" },
            { content: "inputFilter" },
          ],
          align: "center",
        },
        {
          id: "status",
          header: [
            { text: "状态", align: "center" },
            { content: "selectFilter" },
          ],
          align: "center",
          template: state.quizStatusTemplate,
          htmlEnable: true,
        },
      ],
      autoWidth: true,
      height: "auto",
      selection: "row",
      editable: false,
      dragItem: "both",
      keyNavigation: true,
      leftSplit: 1,
    });

    state.testGrid.events.on("cellClick", async (row: any) => {
      const quizId = Number(row?.id);
      if (!quizId) return;
      state.setQuizRowHighlight(row.id);
      state.setQuizActionsDisabled(false);
      await data.loadSheetItemsByQuiz(quizId);
    });
  };

  const createSheetGrid = () => {
    const host = document.createElement("div");
    state.sheetGrid = new (dhx as any).Grid(host, {
      columns: [
        {
          id: "questionId",
          header: [{ text: "题号", align: "center" }],
          align: "center",
        },
        {
          id: "questionType",
          header: [{ text: "题型", align: "center" }],
          template: state.questionTypeTemplate,
          htmlEnable: true,
        },
        {
          id: "correctOption",
          header: [{ text: "设置正确选项", align: "center" }],
          align: "center",
        },
        {
          id: "score",
          header: [{ text: "分数", align: "center" }],
          align: "center",
        },
      ],
      autoWidth: true,
      height: "auto",
      selection: "row",
      editable: false,
      dragItem: "both",
      keyNavigation: true,
      leftSplit: 1,
    });

    state.sheetGrid.events.on("cellClick", (row: any) => {
      const itemId = Number(row?.id);
      if (!itemId) return;
      state.setSheetRowHighlight(row.id);
      state.selectedSheetId.value = itemId;
      state.setQuestionActionsDisabled(false);
    });
  };

  onMounted(async () => {
    if (!authStore.isAuthenticated()) return void router.push("/login");
    await api.get("/sanctum/csrf-cookie");
    if (!state.layoutContainer.value) return;

    state.layout = new dhxLayout(state.layoutContainer.value, {
      cols: [
        {
          id: "testColumn",
          type: "line",
          width: "40%",
          rows: [{ id: "testToolbar", height: 56 }, { id: "testGrid" }],
        },
        {
          id: "sheetColumn",
          type: "line",
          width: "60%",
          rows: [{ id: "sheetToolbar", height: 56 }, { id: "sheetGrid" }],
        },
      ],
      css: "dhx_layout_cell--overflow-auto test-layout",
      type: "line",
    });

    state.testToolbar = new (dhx as any).Toolbar(
      document.createElement("div"),
      {
        css: "app-test-toolbar",
        data: [
          { id: "refreshQuiz", type: "button", value: "刷新" },
          { type: "spacer" },
          { id: "createQuiz", type: "button", value: "新建测验" },
          { id: "editQuiz", type: "button", value: "编辑测验", disabled: true },
          {
            id: "deleteQuiz",
            type: "button",
            value: "删除测验",
            disabled: true,
          },
          { id: "scanQuiz", type: "button", value: "扫描" },
          { id: "reportQuiz", type: "button", value: "报告" },
        ],
      },
    );

    state.sheetToolbar = new (dhx as any).Toolbar(
      document.createElement("div"),
      {
        css: "app-sheet-toolbar",
        data: [
          {
            id: "return",
            type: "button",
            view: "link",
            color: "secondary",
            icon: "mdi mdi-arrow-left",
          },
          { type: "spacer" },
          { id: "addQuestion", type: "button", value: "新建题目" },
          {
            id: "editQuestion",
            type: "button",
            value: "编辑题目",
            disabled: true,
          },
          {
            id: "deleteQuestion",
            type: "button",
            value: "删除题目",
            disabled: true,
          },
          {
            id: "downloadAnswerSheet",
            type: "button",
            value: "下载答题卡",
          },
        ],
      },
    );

    createTestGrid();
    createSheetGrid();

    state.layout.getCell("testToolbar").attach(state.testToolbar);
    state.layout.getCell("testGrid").attach(state.testGrid);
    state.layout.getCell("sheetToolbar").attach(state.sheetToolbar);
    state.layout.getCell("sheetGrid").attach(state.sheetGrid);

    state.testToolbar.events.on("click", (id: string) => {
      if (id === "createQuiz") quizActions.createQuiz();
      if (id === "editQuiz") quizActions.editQuiz();
      if (id === "deleteQuiz") quizActions.deleteQuiz();
      if (id === "refreshQuiz") refreshCurrent();
      if (id === "scanQuiz") console.log("扫描功能待实现");
      if (id === "reportQuiz") console.log("报告功能待实现");
    });
    state.sheetToolbar.events.on("click", (id: string) => {
      if (id === "return") {
        state.selectedQuizId.value = null;
        state.selectedSheetId.value = null;
        state.clearQuizRowHighlight();
        state.clearSheetRowHighlight();
        state.setQuizActionsDisabled(true);
        state.setQuestionActionsDisabled(true);
        state.sheetsCache.value = [];
        state.sheetGrid?.data.parse([]);
      }
      if (id === "downloadAnswerSheet") {
        answerSheetActions.downloadAnswerSheet("zip");
      }
      if (id === "addQuestion") questionActions.addQuestionToQuiz();
      if (id === "editQuestion") questionActions.editQuestionInQuiz();
      if (id === "deleteQuestion") questionActions.removeQuestionFromQuiz();
    });

    await refreshCurrent();
  });

  onBeforeUnmount(() => {
    state.sheetGrid?.destructor();
    state.testGrid?.destructor();
    state.sheetToolbar?.destructor();
    state.testToolbar?.destructor();
    state.layout?.destructor();
  });

  return {
    layoutContainer: state.layoutContainer,
    errorMessage: state.errorMessage,
    successMessage: state.successMessage,
    isSheetLoading: state.isSheetLoading,
  };
};
