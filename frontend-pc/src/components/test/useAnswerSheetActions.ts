import api from "@/services/api";
import type { QuizRow } from "./types";

// 答题卡操作
export const useAnswerSheetActions = (deps: {
  selectedQuizId: { value: number | null };
  quizzesCache: { value: QuizRow[] };
  showError: (message: string) => void;
  showSuccess: (message: string) => void;
}) => {
  const downloadAnswerSheet = async (format: "png" | "pdf" | "zip" = "zip") => {
    if (!deps.selectedQuizId.value) {
      deps.showError("请先选择一个测验");
      return;
    }

    const quizId = deps.selectedQuizId.value;
    const selectedQuiz = deps.quizzesCache.value.find((q) => q.id === quizId);
    const quizTitle = (selectedQuiz?.testName || `测验${quizId}`).trim();

    try {
      deps.showSuccess("正在生成答题卡，请稍候...");

      const response = await api.get(
        `/api/quizzes/${quizId}/answer-sheet?format=${format}`,
        {
          responseType: "blob",
        },
      );

      // 从响应头获取文件名，优先支持 RFC 5987 的 filename*=UTF-8''...
      const contentDisposition = response.headers["content-disposition"];
      let filename = `答题卡_${quizTitle}.${format}`;

      if (contentDisposition) {
        const filenameStarMatch = contentDisposition.match(
          /filename\*=UTF-8''([^;]+)/i,
        );
        const filenameMatch = contentDisposition.match(/filename=([^;]+)/i);

        if (filenameStarMatch?.[1]) {
          try {
            filename = decodeURIComponent(filenameStarMatch[1].trim());
          } catch {
            // 忽略解码失败，保留兜底文件名
          }
        } else if (filenameMatch?.[1]) {
          filename = filenameMatch[1].trim().replace(/^"|"$/g, "");
        }
      }

      // 创建下载链接
      const contentType =
        response.headers["content-type"] || "application/octet-stream";
      const blob = new Blob([response.data], { type: contentType });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      deps.showSuccess("答题卡下载成功");
    } catch (error: any) {
      console.error("下载答题卡失败:", error);

      let errorMessage = "下载答题卡失败";
      if (error.response?.data instanceof Blob) {
        const text = await error.response.data.text();
        try {
          const json = JSON.parse(text);
          errorMessage = json.message || errorMessage;
        } catch {
          // 非 JSON 错误体，保留默认提示
        }
      } else if (error.response?.data?.message) {
        errorMessage = error.response.data.message;
      }

      deps.showError(errorMessage);
    }
  };

  return { downloadAnswerSheet };
};
