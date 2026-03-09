import argparse
import json
import os
import subprocess
import sys
import tempfile
from typing import Any, Dict, List

import requests


OBJECTIVE_TYPES = {"单选", "多选", "判断"}


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="通过 Laravel API 拉取试卷与题目并调用 omr_circle_generator.py 生成答题卡"
    )
    parser.add_argument(
        "--api_base",
        type=str,
        required=True,
        help="Laravel API 基地址，如 http://127.0.0.1:8000",
    )
    parser.add_argument("--quiz_id", type=int, required=True, help="试卷 ID")
    parser.add_argument("--output_dir", type=str, default=".", help="输出目录")
    parser.add_argument(
        "--output_format",
        type=str,
        default="png",
        choices=["png", "pdf"],
        help="输出格式: png 或 pdf",
    )
    parser.add_argument(
        "--token",
        type=str,
        default="",
        help="可选 Bearer Token（若 API 需要认证）",
    )
    parser.add_argument(
        "--cookie",
        type=str,
        default="",
        help="可选 Cookie 头（如 laravel_session=...; XSRF-TOKEN=...）",
    )
    parser.add_argument(
        "--timeout",
        type=int,
        default=30,
        help="HTTP 请求超时秒数",
    )
    return parser.parse_args()


def build_headers(token: str, cookie: str) -> Dict[str, str]:
    headers = {"Accept": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    if cookie:
        headers["Cookie"] = cookie
    return headers


def fetch_quiz_items(
    api_base: str, quiz_id: int, headers: Dict[str, str], timeout: int
) -> Dict[str, Any]:
    url = f"{api_base.rstrip('/')}/api/quizzes/{quiz_id}/items"
    response = requests.get(url, headers=headers, timeout=timeout)
    response.raise_for_status()
    payload = response.json()

    if not isinstance(payload, dict):
        raise ValueError("API 返回格式错误：顶层不是 JSON 对象")

    return payload


def to_omr_json(payload: Dict[str, Any]) -> Dict[str, Any]:
    quiz = payload.get("quiz") or {}
    items = payload.get("items") or []

    test_name = str(quiz.get("title") or "Generated Test")
    start_time_str = str(quiz.get("start_time") or "")
    start_date = ""
    start_time = ""
    if start_time_str:
        parts = start_time_str.replace("T", " ").split(" ")
        if parts:
            start_date = parts[0]
        if len(parts) > 1:
            start_time = parts[1][:8]

    omr_sheets: List[Dict[str, Any]] = []
    for item in items:
        q_type = str(item.get("type") or "")
        if q_type not in OBJECTIVE_TYPES:
            continue

        question_id = item.get("sequence_number")
        if question_id is None:
            question_id = item.get("question_id")

        omr_sheets.append(
            {
                "questionId": int(question_id),
                "type": q_type,
            }
        )

    if not omr_sheets:
        raise ValueError("该试卷没有可生成答题卡的客观题")

    return {
        "testName": test_name,
        "startDate": start_date,
        "startTime": start_time,
        "omrSheets": omr_sheets,
    }


def run_generator(input_json_path: str, output_dir: str, output_format: str) -> str:
    script_dir = os.path.dirname(os.path.abspath(__file__))
    generator_script = os.path.join(script_dir, "omr_circle_generator.py")

    if not os.path.exists(generator_script):
        raise FileNotFoundError(f"未找到生成器脚本: {generator_script}")

    cmd = [
        sys.executable,
        generator_script,
        "--input_json",
        input_json_path,
        "--output_dir",
        output_dir,
        "--output_format",
        output_format,
    ]

    completed = subprocess.run(cmd, capture_output=True, text=True)
    if completed.returncode != 0:
        raise RuntimeError(
            "omr_circle_generator.py 执行失败:\n"
            + (completed.stdout or "")
            + "\n"
            + (completed.stderr or "")
        )

    output_text = (completed.stdout or "").strip()
    if output_text:
        print(output_text)

    output_path = ""
    for line in output_text.splitlines()[::-1]:
        marker = "OMR 答题卡已生成:"
        if marker in line:
            output_path = line.split(marker, 1)[1].strip()
            break

    if not output_path:
        raise RuntimeError("未能从 omr_circle_generator.py 输出中解析生成文件路径")

    return output_path


def main() -> None:
    args = parse_arguments()
    headers = build_headers(args.token, args.cookie)

    payload = fetch_quiz_items(args.api_base, args.quiz_id, headers, args.timeout)
    omr_json = to_omr_json(payload)

    os.makedirs(args.output_dir, exist_ok=True)

    with tempfile.NamedTemporaryFile(
        "w", encoding="utf-8", suffix=".json", delete=False
    ) as fp:
        temp_json_path = fp.name
        json.dump(omr_json, fp, ensure_ascii=False, indent=2)

    try:
        output_path = run_generator(temp_json_path, args.output_dir, args.output_format)
        print(f"OUTPUT_PATH={output_path}")
    finally:
        if os.path.exists(temp_json_path):
            os.remove(temp_json_path)


if __name__ == "__main__":
    main()
