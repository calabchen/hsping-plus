from PIL import Image, ImageDraw, ImageFont
import argparse
import json
import os
from io import BytesIO
from datetime import datetime


OPTION_MAP = {
    "单选": ["A", "B", "C", "D"],
    "多选": ["A", "B", "C", "D", "E", "F", "G", "H"],
    "判断": ["T", "F"],
}


def parse_arguments():
    parser = argparse.ArgumentParser(description="生成 OMR 答题卡")
    parser.add_argument("--input_json", type=str, required=True, help="输入 JSON 路径")
    parser.add_argument(
        "--output_dir",
        type=str,
        default="",
        help="输出目录（不传则自动使用当前系统 Downloads 目录）",
    )
    parser.add_argument(
        "--output_format",
        type=str,
        default="png",
        choices=["png", "pdf"],
        help="输出格式: png 或 pdf",
    )
    parser.add_argument(
        "--output_filename",
        type=str,
        default="",
        help="输出文件名（可选，传入时会覆盖默认命名）",
    )
    return parser.parse_args()


def load_specific_font(font_paths, size):
    for path in font_paths:
        if not os.path.exists(path):
            continue
        try:
            return ImageFont.truetype(path, size)
        except IOError:
            continue
    return ImageFont.load_default()


def text_bbox(draw, font, text):
    try:
        return font.getbbox(text)
    except AttributeError:
        return draw.textbbox((0, 0), text, font=font)


def resolve_options(sheet):
    options = sheet.get("options")
    if isinstance(options, list) and options:
        return [str(o) for o in options]

    q_type = sheet.get("type") or sheet.get("questionType")
    return OPTION_MAP.get(str(q_type), [])


def resolve_question_number(sheet, index):
    # Prefer explicit sequence number from database-backed JSON.
    for key in ("sequence_number", "questionNumber", "question_id", "questionId"):
        value = sheet.get(key)
        if value is not None and str(value).strip() != "":
            return str(value)
    return str(index + 1)


def draw_marked_field(draw, x, y, label, font, marker_size=20, marker_gap=10):
    bbox = text_bbox(draw, font, label)
    text_center_y = y + (bbox[1] + bbox[3]) / 2
    marker_top = text_center_y - marker_size / 2
    draw.rectangle(
        (
            x - marker_size - marker_gap,
            marker_top,
            x - marker_gap,
            marker_top + marker_size,
        ),
        fill="black",
    )
    draw.text((x, y), label, fill="black", font=font)


def get_default_download_dir():
    home = os.path.expanduser("~")
    download_dir = os.path.join(home, "Downloads")
    if os.path.isdir(download_dir):
        return download_dir
    return home


def save_pdf_with_pymupdf(img, output_path):
    try:
        import fitz  # type: ignore

        rgb_img = img.convert("RGB")
        png_buffer = BytesIO()
        rgb_img.save(png_buffer, format="PNG", compress_level=6)
        png_data = png_buffer.getvalue()

        doc = fitz.open()
        page = doc.new_page(width=rgb_img.width, height=rgb_img.height)
        page.insert_image(
            fitz.Rect(0, 0, rgb_img.width, rgb_img.height), stream=png_data
        )
        doc.save(output_path, deflate=True)
        doc.close()
        return True
    except Exception:
        return False


def save_png_with_pdf_fallback(img, output_path):
    """保存PNG，使用PDF转换以提高兼容性"""
    temp_pdf_path = output_path + ".tmp.pdf"
    try:
        # 先转换为RGB模式（确保没有透明通道）
        rgb_img = img.convert("RGB")
        if not save_pdf_with_pymupdf(rgb_img, temp_pdf_path):
            rgb_img.save(temp_pdf_path, "PDF", resolution=300.0)

        try:
            import pymupdf  # type: ignore

            doc = pymupdf.open(temp_pdf_path)
            page = doc[0]
            pix = page.get_pixmap(dpi=300)
            pix.save(output_path)
            doc.close()
            return
        except Exception:
            # 兼容旧版 PyMuPDF 导入名
            try:
                import fitz  # type: ignore

                doc = fitz.open(temp_pdf_path)
                page = doc[0]
                pix = page.get_pixmap(dpi=300)
                pix.save(output_path)
                doc.close()
                return
            except Exception:
                pass
    finally:
        if os.path.exists(temp_pdf_path):
            os.remove(temp_pdf_path)

    # 回退方案：直接保存RGB格式的PNG
    rgb_img = img.convert("RGB")
    rgb_img.save(output_path, "PNG", optimize=True)


def main():
    args = parse_arguments()

    with open(args.input_json, "r", encoding="utf-8") as f:
        data = json.load(f)

    omr_sheets = data.get("omrSheets", [])
    if not omr_sheets:
        raise ValueError("JSON 中缺少 omrSheets")

    test_name = data.get("testName", "Generated Test")
    start_date = str(data.get("startDate", "")).strip()
    start_time = str(data.get("startTime", "")).strip()

    # A4纸张宽度: 210mm at 300dpi = 2480px
    a4_width = 2480
    margin = 80
    max_cols = 4
    row_height = 70
    circle_diameter = 40
    circle_radius = circle_diameter // 2
    option_spacing = 50
    max_options = 8
    col_spacing = max_options * option_spacing + 80
    mark_size = 30

    valid_sheets = []
    for index, sheet in enumerate(omr_sheets):
        options = resolve_options(sheet)
        if not options:
            continue
        valid_sheets.append(
            {
                "questionNumber": resolve_question_number(sheet, index),
                "answer": sheet.get("answer"),
                "score": sheet.get("score"),
                "options": options,
            }
        )

    if not valid_sheets:
        raise ValueError("没有可生成 OMR 的客观题")

    total_questions = len(valid_sheets)
    num_rows = (total_questions + max_cols - 1) // max_cols
    img_width = a4_width
    img_height = margin + 200 + num_rows * row_height + margin

    img = Image.new("RGB", (img_width, img_height), "white")
    draw = ImageDraw.Draw(img)

    label_font = load_specific_font(
        [
            "/System/Library/Fonts/Supplemental/Songti.ttc",
            "C:/Windows/Fonts/simsun.ttc",
            "C:/Windows/Fonts/simhei.ttf",
            "/usr/share/fonts/truetype/noto/NotoSerifCJK-Regular.ttc",
        ],
        22,
    )
    option_font = load_specific_font(
        [
            "C:/Windows/Fonts/times.ttf",
            "C:/Windows/Fonts/timesbd.ttf",
            "/System/Library/Fonts/Supplemental/Times New Roman.ttf",
            "/System/Library/Fonts/Times.ttc",
            "/usr/share/fonts/truetype/msttcorefonts/Times_New_Roman.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf",
        ],
        22,
    )

    # 四个角的定位块
    draw.rectangle((0, 0, mark_size, mark_size), fill="black")
    draw.rectangle((img_width - mark_size, 0, img_width, mark_size), fill="black")
    draw.rectangle((0, img_height - mark_size, mark_size, img_height), fill="black")
    draw.rectangle(
        (img_width - mark_size, img_height - mark_size, img_width, img_height),
        fill="black",
    )

    # 标题区：左上角测验名 + 创建时间，右上角分数 + 定位块
    title_font = load_specific_font(
        [
            "C:/Windows/Fonts/simhei.ttf",
            "C:/Windows/Fonts/simsun.ttc",
            "/System/Library/Fonts/Supplemental/Songti.ttc",
            "/usr/share/fonts/truetype/noto/NotoSerifCJK-Regular.ttc",
        ],
        32,
    )

    # 左上角：测验名称
    header_y = 40
    draw.text(
        (margin, header_y), f"测验名称：{test_name}", fill="black", font=title_font
    )

    # 左上角：创建时间
    start_at_text = ""
    if start_date and start_time:
        start_at_text = f"创建时间：{start_date} {start_time}"
    elif start_date:
        start_at_text = f"创建时间：{start_date}"
    elif start_time:
        start_at_text = f"创建时间：{start_time}"
    if start_at_text:
        draw.text((margin, header_y + 45), start_at_text, fill="black", font=label_font)

    # 右上角：分数文字，定位块在文字左边
    score_text = "分数：_______"
    score_bbox = text_bbox(draw, title_font, score_text)
    score_width = score_bbox[2] - score_bbox[0]
    score_x = img_width - margin - score_width - mark_size - 20
    draw.text((score_x, header_y), score_text, fill="black", font=title_font)

    # 右上角额外定位块（在分数左侧）
    extra_mark_x = score_x - mark_size - 16
    extra_mark_y = header_y + 10
    draw.rectangle(
        (
            extra_mark_x,
            extra_mark_y,
            extra_mark_x + mark_size,
            extra_mark_y + mark_size,
        ),
        fill="black",
    )

    text_y = header_y + 110

    # 先绘制学号
    draw_marked_field(
        draw, margin, text_y, "学号：____________________", label_font, mark_size
    )

    # 再绘制分开的姓与名
    surname_x = margin + 550
    given_name_x = surname_x + 300
    draw_marked_field(
        draw, surname_x, text_y, "姓：_________________", label_font, mark_size
    )
    draw_marked_field(
        draw, given_name_x, text_y, "名：_________________", label_font, mark_size
    )

    # 最后绘制题目
    start_y = text_y + 80
    for index, sheet in enumerate(valid_sheets):
        q_num = sheet["questionNumber"]
        options = sheet["options"]

        row = index // max_cols
        col = index % max_cols
        base_x = margin + col * col_spacing
        base_y = start_y + row * row_height

        draw.text((base_x, base_y - 10), f"{q_num}.", fill="black", font=option_font)

        for j, opt in enumerate(options):
            cx = base_x + 60 + j * option_spacing
            cy = base_y
            draw.ellipse(
                (
                    cx - circle_radius,
                    cy - circle_radius,
                    cx + circle_radius,
                    cy + circle_radius,
                ),
                outline="black",
                width=4,
            )

            bbox = text_bbox(draw, option_font, opt)
            text_w = bbox[2] - bbox[0]
            text_x = cx - text_w // 2
            text_y = cy - (bbox[1] + bbox[3]) / 2
            draw.text((text_x, text_y), opt, fill="black", font=option_font)

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    suffix = args.output_format.lower()
    if args.output_filename.strip():
        output_filename = args.output_filename.strip()
        if not output_filename.lower().endswith(f".{suffix}"):
            output_filename = f"{output_filename}.{suffix}"
    else:
        output_filename = f"omr_card_{test_name.replace(' ', '_')}_{total_questions}Q_{timestamp}.{suffix}"

    output_dir = args.output_dir.strip() if args.output_dir else ""
    if output_dir == "":
        output_dir = get_default_download_dir()

    os.makedirs(output_dir, exist_ok=True)
    output_path = os.path.abspath(os.path.join(output_dir, output_filename))

    if suffix == "pdf":
        if not save_pdf_with_pymupdf(img, output_path):
            img.convert("RGB").save(output_path, "PDF", resolution=300.0)
    else:
        save_png_with_pdf_fallback(img, output_path)

    print(f"OMR 答题卡已生成: {output_path}")
    print(f"OUTPUT_PATH={output_path}")


if __name__ == "__main__":
    main()
