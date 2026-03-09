#!/usr/bin/env python3
"""
process_omr.py - OMR answer sheet processing in one step
Recognizes student info, answers, calculates scores, and generates visualization.
"""

import argparse
import json
import os
from PIL import Image, ImageDraw, ImageFont
import numpy as np


def parse_args():
    p = argparse.ArgumentParser(
        description="Process OMR sheet: recognize answers and generate visualization."
    )
    p.add_argument(
        "--image", required=True, help="Path to the OMR PNG image to process"
    )
    p.add_argument(
        "--key",
        required=True,
        help="Path to JSON file containing answer key and question layout",
    )
    p.add_argument(
        "--threshold",
        type=int,
        default=200,
        help="Brightness threshold (0-255) for detecting filled bubbles",
    )
    p.add_argument(
        "--output-dir",
        default="results",
        help="Output directory for results.json and visualization.png",
    )
    p.add_argument(
        "--debug",
        action="store_true",
        help="Print debug info for each bubble",
    )
    return p.parse_args()


def load_key(path):
    """Load answer key JSON"""
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def pil_to_gray_np(img: Image.Image):
    """Convert PIL image to grayscale numpy array"""
    return np.array(img.convert("L"))


def pil_to_rgb_np(img: Image.Image):
    """Convert PIL image to RGB numpy array"""
    return np.array(img.convert("RGB"))


def sample_circle_mean(gray_np, cx, cy, r):
    """Sample mean brightness in a circular area"""
    h, w = gray_np.shape
    x0 = int(max(0, cx - r))
    x1 = int(min(w, cx + r + 1))
    y0 = int(max(0, cy - r))
    y1 = int(min(h, cy + r + 1))

    sub = gray_np[y0:y1, x0:x1]
    yy, xx = np.ogrid[y0:y1, x0:x1]
    mask = (xx - cx) ** 2 + (yy - cy) ** 2 <= r * r
    if mask.sum() == 0:
        return 255
    return float(sub[mask].mean())


def detect_filled_rectangles(gray_np, min_brightness=150, min_area=100):
    """Detect filled dark rectangles (for marker boxes)."""
    h, w = gray_np.shape
    dark_map = gray_np < min_brightness

    filled_rects = []
    visited = np.zeros_like(dark_map, dtype=bool)

    for y in range(h):
        for x in range(w):
            if dark_map[y, x] and not visited[y, x]:
                y0, x0 = y, x
                y1, x1 = y, x
                stack = [(y, x)]
                region_pixels = []

                while stack:
                    cy, cx = stack.pop()
                    if cy < 0 or cy >= h or cx < 0 or cx >= w:
                        continue
                    if visited[cy, cx] or not dark_map[cy, cx]:
                        continue

                    visited[cy, cx] = True
                    region_pixels.append((cx, cy))
                    y0 = min(y0, cy)
                    y1 = max(y1, cy)
                    x0 = min(x0, cx)
                    x1 = max(x1, cx)

                    for dy, dx in [(-1, 0), (1, 0), (0, -1), (0, 1)]:
                        stack.append((cy + dy, cx + dx))

                if len(region_pixels) >= min_area:
                    filled_rects.append((x0, y0, x1, y1))

    return filled_rects


def extract_student_info(gray_np, img_width, img_height):
    """Extract student ID and name by detecting marker boxes."""
    filled_rects = detect_filled_rectangles(gray_np, min_brightness=150, min_area=50)

    margin = 50
    mark_size = 20
    marker_width = mark_size
    marker_gap_x = 10
    name_area_width_estimate = 400
    gap_between_fields = 60

    name_marker_x = margin - marker_width - marker_gap_x
    student_id_x = margin + name_area_width_estimate + gap_between_fields
    student_id_marker_x = student_id_x - marker_width - marker_gap_x

    student_info = {
        "studentId": "Unknown",
        "name": "Unknown",
        "studentIdBox": None,
        "nameBox": None,
    }

    name_marker = None
    student_id_marker = None

    for rect in filled_rects:
        x0, y0, x1, y1 = rect
        if y0 < 100:
            if abs(x0 - name_marker_x) < 20 and name_marker is None:
                name_marker = rect
            elif abs(x0 - student_id_marker_x) < 20 and student_id_marker is None:
                student_id_marker = rect

    if name_marker:
        marker_x0, marker_y0, marker_x1, marker_y1 = name_marker
        name_input_x0 = marker_x1 + marker_gap_x
        name_input_y0 = marker_y0
        name_input_x1 = name_input_x0 + name_area_width_estimate - 50
        name_input_y1 = marker_y1
        student_info["nameBox"] = (
            name_input_x0,
            name_input_y0,
            name_input_x1,
            name_input_y1,
        )
        student_info["name"] = "Name"

    if student_id_marker:
        marker_x0, marker_y0, marker_x1, marker_y1 = student_id_marker
        id_input_x0 = marker_x1 + marker_gap_x
        id_input_y0 = marker_y0
        id_input_x1 = id_input_x0 + 200
        id_input_y1 = marker_y1
        student_info["studentIdBox"] = (
            id_input_x0,
            id_input_y0,
            id_input_x1,
            id_input_y1,
        )
        student_info["studentId"] = "StudentID"

    return student_info


def parse_correct_option(raw):
    """Normalize correct answer format"""
    if raw is None:
        return []
    if isinstance(raw, list):
        return [str(x).strip() for x in raw]
    return [s.strip() for s in str(raw).split(",") if s.strip()]


def recognize_answers(gray_np, key, threshold, debug=False):
    """Recognize answers and calculate scores."""
    omr_sheets = key.get("omrSheets", [])
    if not omr_sheets:
        print("Error: key JSON has no 'omrSheets' array")
        return None, 0, 0, {}

    margin = 50
    max_cols = 4
    row_height = 70
    circle_diameter = 40
    circle_radius = circle_diameter // 2
    option_spacing = 50
    # Match generator: always use max_options=5 for consistent spacing
    max_options = 5
    col_spacing_max = max_options * option_spacing + 80
    start_y = margin + 60 + 20

    results = []
    total_score = 0.0
    max_total = 0.0
    question_scores = {}

    for idx, sheet in enumerate(omr_sheets):
        q_index = idx + 1
        row = (q_index - 1) // max_cols
        col = (q_index - 1) % max_cols
        base_x = margin + col * col_spacing_max
        base_y = start_y + row * row_height

        if debug:
            print(
                f"\n[Q{q_index}] Row={row}, Col={col}, base_x={base_x}, base_y={base_y}"
            )

        options = sheet.get("options", [])
        if not options:
            results.append(
                {
                    "questionId": sheet.get("questionId", q_index),
                    "selected": [],
                    "reason": "no options",
                }
            )
            continue

        selected = []
        per_option_means = []
        bubble_boxes = []

        for j, opt in enumerate(options):
            cx = int(base_x + 60 + j * option_spacing)
            cy = int(base_y)
            mean = sample_circle_mean(gray_np, cx, cy, int(circle_radius * 0.8))
            per_option_means.append({"label": opt, "cx": cx, "cy": cy, "mean": mean})

            bubble_box = (
                cx - circle_radius,
                cy - circle_radius,
                cx + circle_radius,
                cy + circle_radius,
            )
            bubble_boxes.append({"label": opt, "box": bubble_box, "mean": mean})

            is_filled = mean < threshold
            if debug:
                print(f"  {opt}: cx={cx}, cy={cy}, mean={mean:.1f}, filled={is_filled}")

            if is_filled:
                selected.append(opt)

        correct_raw = sheet.get("correctOption")
        correct = parse_correct_option(correct_raw)
        q_score = float(sheet.get("score", 1))
        max_total += q_score

        is_correct = False
        q_type = sheet.get("questionType", "")

        if q_type.startswith("单选") or q_type.startswith("判断"):
            if len(selected) == 1 and selected[0] in correct:
                total_score += q_score
                is_correct = True
        else:
            sel_set = set([s.strip() for s in selected])
            corr_set = set([s.strip() for s in correct])
            if sel_set == corr_set and len(sel_set) > 0:
                total_score += q_score
                is_correct = True

        if debug:
            print(f"  selected={selected}, correct={correct}, is_correct={is_correct}")

        q_result = {
            "questionId": sheet.get("questionId", q_index),
            "questionType": q_type,
            "selected": selected,
            "correct": correct,
            "score": q_score if is_correct else 0.0,
            "maxScore": q_score,
            "isCorrect": is_correct,
            "samples": per_option_means,
            "bubbleBoxes": bubble_boxes,
        }
        results.append(q_result)
        question_scores[q_index] = q_result["score"]

    return results, total_score, max_total, question_scores


def main():
    args = parse_args()

    if not os.path.exists(args.image):
        print(f"❌ Error: image not found: {args.image}")
        return

    if not os.path.exists(args.key):
        print(f"❌ Error: key file not found: {args.key}")
        return

    os.makedirs(args.output_dir, exist_ok=True)

    key = load_key(args.key)
    img = Image.open(args.image)
    img_w, img_h = img.size
    gray = pil_to_gray_np(img)
    rgb = pil_to_rgb_np(img)

    student_info = extract_student_info(gray, img_w, img_h)
    print(
        f"📝 Student info detected: {student_info['studentId']} / {student_info['name']}"
    )

    results, total_score, max_total, question_scores = recognize_answers(
        gray, key, args.threshold, debug=args.debug
    )
    if results is None:
        return

    summary = {
        "testName": key.get("testName"),
        "studentInfo": student_info,
        "scoring": {
            "total": total_score,
            "maxTotal": max_total,
            "percentage": (total_score / max_total * 100) if max_total > 0 else 0,
        },
        "questionScores": question_scores,
        "details": results,
    }

    base_name = os.path.splitext(os.path.basename(args.image))[0]
    results_json_path = os.path.join(args.output_dir, f"results_{base_name}.json")

    with open(results_json_path, "w", encoding="utf-8") as f:
        json.dump(summary, f, ensure_ascii=False, indent=2)
    print(f"💾 Results JSON saved: {results_json_path}")

    # Visualization
    vis_img = Image.fromarray(rgb)
    draw = ImageDraw.Draw(vis_img)

    try:
        # Try to load a larger font, fall back to default
        large_font = ImageFont.truetype("/System/Library/Fonts/Helvetica.ttc", 24)
    except Exception:
        large_font = ImageFont.load_default()

    # Draw student info boxes
    if student_info["nameBox"]:
        x0, y0, x1, y1 = student_info["nameBox"]
        draw.rectangle([(x0, y0), (x1, y1)], outline="blue", width=2)

    if student_info["studentIdBox"]:
        x0, y0, x1, y1 = student_info["studentIdBox"]
        draw.rectangle([(x0, y0), (x1, y1)], outline="blue", width=2)

    # Draw answer bubbles (only filled circles)
    for detail in results:
        is_correct = detail.get("isCorrect", False)
        bubbles = detail.get("bubbleBoxes", [])
        selected = detail.get("selected", [])

        for bubble_info in bubbles:
            label = bubble_info.get("label")
            x0, y0, x1, y1 = bubble_info.get("box")

            if label in selected:
                color = "green" if is_correct else "red"
                draw.ellipse([(x0, y0), (x1, y1)], outline=color, width=3)

    # Draw total score at top-right with separate colors
    score_actual = f"{total_score:.0f}"
    score_max = f"{max_total:.0f}"

    score_x = img_w - 150
    score_y = 20

    # Draw background box for score
    try:
        # Calculate bounding box for the complete score string
        test_text = f"{score_actual}/{score_max}"
        bbox_test = draw.textbbox((score_x, score_y), test_text, font=large_font)

        min_x = bbox_test[0] - 10
        min_y = bbox_test[1] - 5
        max_x = bbox_test[2] + 10
        max_y = bbox_test[3] + 5

        draw.rectangle(
            [min_x, min_y, max_x, max_y], fill="white", outline="black", width=2
        )
    except Exception:
        pass

    # Draw score text in parts with different colors
    # Red part: actual score
    draw.text((score_x, score_y), score_actual, fill="red", font=large_font)

    # Get the width of the actual score to position the slash
    bbox_actual = draw.textbbox((score_x, score_y), score_actual, font=large_font)
    slash_x = bbox_actual[2] + 2

    # Black part: slash
    draw.text((slash_x, score_y), "/", fill="black", font=large_font)

    # Get the width of the slash to position the max score
    bbox_slash = draw.textbbox((slash_x, score_y), "/", font=large_font)
    max_score_x = bbox_slash[2] + 2

    # Black part: max score
    draw.text((max_score_x, score_y), score_max, fill="black", font=large_font)

    viz_path = os.path.join(args.output_dir, f"visualization_{base_name}.png")
    vis_img.save(viz_path)
    print(f"🎨 Visualization image saved: {viz_path}")

    print("\n" + "=" * 50)
    print("✅ Processing complete!")
    print("=" * 50)
    print(f"📊 Score: {total_score:.1f}/{max_total:.1f}")
    print(f"📋 Student: {student_info['studentId']} / {student_info['name']}")
    print(f"📂 Results stored in: {args.output_dir}/")
    print("=" * 50)


if __name__ == "__main__":
    main()
