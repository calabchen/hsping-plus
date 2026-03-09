import * as dhx from "dhx-suite";

// 通用弹窗表单，给试卷与发布等场景复用
export const useDhxFormDialog = () => {
  const openFormDialog = (
    title: string,
    rows: any[],
    values: Record<string, any> = {},
    width = 560,
    height = 420,
  ): Promise<Record<string, any> | null> => {
    return new Promise((resolve) => {
      const host = document.createElement("div");
      const modalWindow = new (dhx as any).Window({
        title,
        width,
        height,
        modal: true,
        movable: false,
        closable: true,
        resizable: false,
      });

      const form = new (dhx as any).Form(host, {
        css: "dhx_widget--bordered",
        padding: 16,
        rows: [
          ...rows,
          {
            cols: [
              { type: "spacer" },
              { type: "button", name: "cancel", text: "取消", view: "link" },
              {
                type: "button",
                name: "submit",
                text: "保存",
                color: "primary",
              },
            ],
          },
        ],
      });

      form.setValue(values);
      modalWindow.attach(form);
      modalWindow.show();

      let resolved = false;
      const safeResolve = (payload: Record<string, any> | null) => {
        if (resolved) return;
        resolved = true;
        form.destructor();
        modalWindow.destructor();
        resolve(payload);
      };

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

  return { openFormDialog };
};
