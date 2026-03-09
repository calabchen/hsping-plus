// 统一处理日期时间格式，避免各处重复逻辑
export const useTestTime = (timezone: string) => {
  const pad2 = (value: number) => String(value).padStart(2, "0");

  const toDatePart = (value: unknown): string => {
    if (!value) return "";
    if (value instanceof Date) {
      return `${value.getFullYear()}-${pad2(value.getMonth() + 1)}-${pad2(value.getDate())}`;
    }

    const raw = String(value).trim();
    const match = raw.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})/);
    return match
      ? `${match[1]}-${pad2(Number(match[2]))}-${pad2(Number(match[3]))}`
      : "";
  };

  const toTimePart = (value: unknown): string => {
    if (!value) return "";
    if (value instanceof Date) {
      return `${pad2(value.getHours())}:${pad2(value.getMinutes())}`;
    }

    const raw = String(value).trim().replace(".", ":");
    const match = raw.match(/^(\d{1,2}):(\d{1,2})/);
    return match ? `${pad2(Number(match[1]))}:${pad2(Number(match[2]))}` : "";
  };

  const combineStartTime = (
    dateValue: unknown,
    timeValue: unknown,
  ): string | null => {
    const datePart = toDatePart(dateValue);
    if (!datePart) return null;
    const timePart = toTimePart(timeValue) || "00:00";
    return `${datePart} ${timePart}:00`;
  };

  const splitStartTime = (startTime: string | null) => {
    if (!startTime) return { start_date: "", start_clock: "" };
    const [datePart, timePart] = String(startTime).split(" ");
    return {
      start_date: datePart || "",
      start_clock: (timePart || "").slice(0, 5),
    };
  };

  const getCurrentStartFields = () => {
    const formatter = new Intl.DateTimeFormat("en-CA", {
      timeZone: timezone,
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    });

    const parts = formatter.formatToParts(new Date());
    const getPart = (type: string) =>
      parts.find((item) => item.type === type)?.value || "";

    return {
      start_date: `${getPart("year")}-${getPart("month")}-${getPart("day")}`,
      start_clock: `${getPart("hour")}:${getPart("minute")}`,
    };
  };

  return { combineStartTime, splitStartTime, getCurrentStartFields };
};
