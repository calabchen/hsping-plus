import * as dhx from "dhx-suite";
import type { QuestionEditorResult } from "./types";

type OpenQuestionEditorOptions = {
  title: string;
  includeQuestionId: boolean;
  initial: Partial<QuestionEditorResult>;
};

// 题目编辑器：新增与编辑共用同一套 UI
export const useQuestionEditorDialog = () => {
  const openQuestionEditorDialog = ({
    title,
    includeQuestionId,
    initial,
  }: OpenQuestionEditorOptions): Promise<QuestionEditorResult | null> => {
    return new Promise((resolve) => {
      const host = document.createElement("div");
      const modalWindow = new (dhx as any).Window({
        title,
        width: 640,
        height: 620,
        modal: true,
        movable: false,
        closable: true,
        resizable: false,
      });

      const rows: any[] = [];
      if (includeQuestionId) {
        rows.push({
          type: "input",
          name: "sequence_number",
          label: "题号",
          labelWidth: "100px",
          labelPosition: "left",
          required: true,
        });
      }
      rows.push(
        {
          type: "input",
          name: "score",
          label: "分数",
          labelWidth: "100px",
          labelPosition: "left",
        },
        {
          type: "select",
          name: "type",
          label: "题型",
          labelWidth: "100px",
          labelPosition: "left",
          options: [
            { value: "单选", content: "单选" },
            { value: "多选", content: "多选" },
            { value: "判断", content: "判断" },
            { value: "主观", content: "主观" },
          ],
        },
        { type: "text", name: "answerHint", value: "请选择正确答案" },
        {
          name: "abcdRow",
          align: "start",
          padding: "0 0 0 120px",
          cols: ["A", "B", "C", "D"].map((x) => ({
            type: "checkbox",
            name: `ans_${x}`,
            text: x,
            checked: x === "A",
            width: "25%",
          })),
        },
        {
          name: "efghRow",
          align: "start",
          padding: "0 0 0 120px",
          cols: ["E", "F", "G", "H"].map((x) => ({
            type: "checkbox",
            name: `ans_${x}`,
            text: x,
            checked: false,
            width: "25%",
          })),
        },
        {
          name: "judgeRow",
          align: "start",
          padding: "0 0 0 120px",
          cols: [
            {
              type: "checkbox",
              name: "tf_true",
              text: "对",
              checked: true,
              width: "50%",
            },
            {
              type: "checkbox",
              name: "tf_false",
              text: "错",
              checked: false,
              width: "50%",
            },
          ],
        },
        {
          type: "textarea",
          name: "subjective_answer",
          label: "参考答案",
          labelWidth: "100px",
          labelPosition: "left",
          height: "200px",
          placeholder: "请输入主观题参考答案",
        },
        {
          cols: [
            { type: "spacer" },
            { type: "button", name: "cancel", text: "取消", view: "link" },
            { type: "button", name: "submit", text: "保存", color: "primary" },
          ],
        },
      );

      // 统一构建表单，新增与编辑都走这一套组件
      const form = new (dhx as any).Form(host, {
        css: "dhx_widget--bordered",
        padding: 16,
        rows,
      });
      const toggle = (items: string[], show: boolean) =>
        items.forEach((n) => form.getItem(n)?.[show ? "show" : "hide"]());
      const abcd = ["ans_A", "ans_B", "ans_C", "ans_D"];
      const efgh = ["ans_E", "ans_F", "ans_G", "ans_H"];
      const judge = ["tf_true", "tf_false"];

      const setTypeUI = (type: string) => {
        // 按题型更新提示文案
        if (type === "单选")
          form.setValue(
            { answerHint: "单选：仅可勾选 A/B/C/D 其中一个" },
            true,
          );
        if (type === "多选")
          form.setValue({ answerHint: "多选：至少勾选 2 个答案（A-H）" }, true);
        if (type === "判断")
          form.setValue({ answerHint: "判断：仅可勾选“对”或“错”一个" }, true);
        if (type === "主观")
          form.setValue({ answerHint: "主观：请在下方输入参考答案" }, true);

        // 按题型精确显示/隐藏对应输入区（A-D、A-H、判断、主观）
        toggle(abcd, type === "单选" || type === "多选");
        toggle(efgh, type === "多选");
        toggle(judge, type === "判断");
        form
          .getItem("subjective_answer")
          ?.[type === "主观" ? "show" : "hide"]();
      };

      // 初始化表单值和 UI 状态
      const normalized = String(initial.subjective_answer || "")
        .toUpperCase()
        .replace(/\s+/g, "");
      const preset = {
        sequence_number: initial.sequence_number || "",
        score: initial.score || "1",
        type: initial.type || "单选",
        ans_A:
          normalized.includes("A") || (!normalized && initial.type === "单选"),
        ans_B:
          normalized.includes("B") || (!normalized && initial.type === "多选"),
        ans_C: normalized.includes("C"),
        ans_D: normalized.includes("D"),
        ans_E: normalized.includes("E"),
        ans_F: normalized.includes("F"),
        ans_G: normalized.includes("G"),
        ans_H: normalized.includes("H"),
        tf_true: ["T", "对", "TRUE", "√"].includes(normalized),
        tf_false: ["F", "错", "FALSE", "×"].includes(normalized),
        subjective_answer: initial.subjective_answer || "",
      };
      form.setValue(preset, true);
      setTypeUI(String(preset.type));

      modalWindow.attach(form);
      modalWindow.show();

      let resolved = false;
      let syncingChange = false;
      const safeResolve = (payload: QuestionEditorResult | null) => {
        if (resolved) return;
        resolved = true;
        resolve(payload);

        // 事件栈先完成，再销毁 DHX 组件，避免内部 fire/hide 访问空对象。
        setTimeout(() => {
          try {
            form.destructor();
            modalWindow.destructor();
          } catch {
            // 忽略重复销毁导致的异常
          }
        }, 0);
      };

      form.events.on("change", (name: string, value: any) => {
        // 防止 setValue 触发二次 change，造成递归或状态抖动
        if (syncingChange) return;
        const currentType = String(form.getValue().type || "单选");

        if (name === "type") {
          const next = String(value || "单选");
          setTypeUI(next);

          // 切换题型时清理不相关选项，避免旧题型残留值污染新题型
          syncingChange = true;
          if (next === "单选")
            form.setValue(
              {
                ans_A: true,
                ans_B: false,
                ans_C: false,
                ans_D: false,
                ans_E: false,
                ans_F: false,
                ans_G: false,
                ans_H: false,
                tf_true: false,
                tf_false: false,
                subjective_answer: "",
              },
              true,
            );
          if (next === "多选")
            form.setValue(
              {
                ans_A: true,
                ans_B: true,
                ans_C: false,
                ans_D: false,
                ans_E: false,
                ans_F: false,
                ans_G: false,
                ans_H: false,
                tf_true: false,
                tf_false: false,
                subjective_answer: "",
              },
              true,
            );
          if (next === "判断")
            form.setValue(
              {
                ans_A: false,
                ans_B: false,
                ans_C: false,
                ans_D: false,
                ans_E: false,
                ans_F: false,
                ans_G: false,
                ans_H: false,
                tf_true: true,
                tf_false: false,
                subjective_answer: "",
              },
              true,
            );
          if (next === "主观")
            form.setValue(
              {
                ans_A: false,
                ans_B: false,
                ans_C: false,
                ans_D: false,
                ans_E: false,
                ans_F: false,
                ans_G: false,
                ans_H: false,
                tf_true: false,
                tf_false: false,
              },
              true,
            );
          syncingChange = false;
          return;
        }

        if (currentType === "单选" && abcd.includes(name) && Boolean(value)) {
          // 单选实时互斥：勾选一个后，立即取消其余三个
          syncingChange = true;
          form.setValue(
            {
              ans_A: name === "ans_A",
              ans_B: name === "ans_B",
              ans_C: name === "ans_C",
              ans_D: name === "ans_D",
            },
            true,
          );
          syncingChange = false;
        }
        if (
          currentType === "判断" &&
          (name === "tf_true" || name === "tf_false") &&
          Boolean(value)
        ) {
          // 判断实时互斥：对/错只能同时存在一个真值
          syncingChange = true;
          form.setValue(
            { tf_true: name === "tf_true", tf_false: name === "tf_false" },
            true,
          );
          syncingChange = false;
        }
      });

      form.events.on("click", (name: string) => {
        if (name === "cancel") safeResolve(null);
        if (name === "submit") safeResolve(form.getValue());
      });

      modalWindow.events.on("beforeHide", () => {
        safeResolve(null);
        return true;
      });
    });
  };

  return { openQuestionEditorDialog };
};
