<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
  private function normalizeAnswer(array $validated): ?string
  {
    $answer = isset($validated['answer']) ? trim((string) $validated['answer']) : null;
    if ($answer === '') {
      $answer = null;
    }

    if ($validated['type'] === '单选') {
      abort_unless(in_array($answer, ['A', 'B', 'C', 'D'], true), 422, '单选题答案必须是 A/B/C/D');
    }

    if ($validated['type'] === '多选') {
      abort_unless(
        is_string($answer) && preg_match('/^(?!.*(.).*\\1)[A-H]{2,8}$/', $answer) === 1,
        422,
        '多选题答案必须是 A-H 的组合且不能重复，且至少 2 个'
      );
    }

    if ($validated['type'] === '判断') {
      abort_unless(in_array($answer, ['T', 'F', '对', '错', '√', '×'], true), 422, '判断题答案必须是 T/F 或 对/错');
    }

    if ($validated['type'] === '主观') {
      abort_unless(is_string($answer) && $answer !== null && $answer !== '', 422, '主观题请填写参考答案');
    }

    return $answer;
  }

  private function resolveTeacherId(Request $request): int
  {
    $teacherId = DB::table('teachers')
      ->where('user_id', $request->user()->id)
      ->value('teacher_id');

    abort_unless($teacherId, 422, '请先完善教师资料后再管理试卷');

    return (int) $teacherId;
  }

  private function sanitizeTextForJson(string $text): string
  {
    if ($text === '') {
      return $text;
    }

    if (preg_match('//u', $text) === 1) {
      return $text;
    }

    if (function_exists('mb_convert_encoding')) {
      $converted = @mb_convert_encoding($text, 'UTF-8', 'UTF-8,GB18030,GBK,BIG5,ISO-8859-1,Windows-1252');
      if (is_string($converted) && preg_match('//u', $converted) === 1) {
        return $converted;
      }
    }

    if (function_exists('iconv')) {
      $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
      if (is_string($converted) && preg_match('//u', $converted) === 1) {
        return $converted;
      }
    }

    return '[non-utf8-output]';
  }

  private function sanitizeOutputLines(array $lines): string
  {
    $sanitized = array_map(function ($line) {
      return $this->sanitizeTextForJson((string) $line);
    }, $lines);

    return implode("\n", $sanitized);
  }

  public function index(Request $request)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quizzes = DB::table('quizzes as q')
      ->where('q.teacher_id', $teacherId)
      ->select([
        'q.quiz_id',
        'q.title',
        'q.start_time',
        'q.status',
        'q.created_at',
      ])
      ->selectSub(function ($query) {
        $query->from('questions as qs')
          ->selectRaw('COUNT(*)')
          ->whereColumn('qs.quiz_id', 'q.quiz_id');
      }, 'question_count')
      ->selectSub(function ($query) {
        $query->from('quiz_assignments as qa')
          ->selectRaw('COUNT(*)')
          ->whereColumn('qa.quiz_id', 'q.quiz_id');
      }, 'assignment_count')
      ->orderByDesc('q.quiz_id')
      ->get();

    return response()->json(['quizzes' => $quizzes]);
  }

  public function store(Request $request)
  {
    $teacherId = $this->resolveTeacherId($request);

    $validated = $request->validate([
      'title' => 'required|string|max:100',
      'status' => 'nullable|in:草稿,已发布,已结束,已归档',
      'start_time' => 'required|date',
    ]);

    $quizId = DB::table('quizzes')->insertGetId([
      'teacher_id' => $teacherId,
      'title' => $validated['title'],
      'status' => $validated['status'] ?? '草稿',
      'start_time' => $validated['start_time'],
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return response()->json([
      'message' => '试卷创建成功',
      'quiz' => DB::table('quizzes')->where('quiz_id', $quizId)->first(),
    ]);
  }

  public function update(Request $request, int $quizId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quiz = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->first();

    abort_unless($quiz, 404, '试卷不存在');

    $validated = $request->validate([
      'title' => 'required|string|max:100',
      'status' => 'required|in:草稿,已发布,已结束,已归档',
      'start_time' => 'required|date',
    ]);

    DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->update([
        'title' => $validated['title'],
        'status' => $validated['status'],
        'start_time' => $validated['start_time'],
        'updated_at' => now(),
      ]);

    return response()->json(['message' => '试卷更新成功']);
  }

  public function destroy(Request $request, int $quizId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $deleted = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->delete();

    abort_if($deleted === 0, 404, '试卷不存在');

    return response()->json(['message' => '试卷已删除']);
  }

  public function items(Request $request, int $quizId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quiz = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->first();

    abort_unless($quiz, 404, '试卷不存在');

    $items = DB::table('questions as q')
      ->where('q.quiz_id', $quizId)
      ->where('q.teacher_id', $teacherId)
      ->select([
        'q.question_id',
        'q.sequence_number',
        'q.type',
        'q.answer',
        'q.score',
      ])
      ->orderBy('q.sequence_number')
      ->get();

    $assignments = DB::table('quiz_assignments as qa')
      ->join('classes as c', 'c.class_id', '=', 'qa.class_id')
      ->where('qa.quiz_id', $quizId)
      ->select([
        'qa.assignment_id',
        'qa.class_id',
        'c.class_num',
        'c.enrollment_year',
      ])
      ->orderByDesc('qa.assignment_id')
      ->get()
      ->map(function ($assignment) {
        $year = $assignment->enrollment_year ? (string) $assignment->enrollment_year : '';
        $num = $assignment->class_num ? (string) $assignment->class_num : '';
        $assignment->display_name = $year ? ($year . '级' . $num . '班') : $num;
        return $assignment;
      });

    return response()->json([
      'quiz' => $quiz,
      'items' => $items,
      'assignments' => $assignments,
    ]);
  }

  public function addItem(Request $request, int $quizId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quiz = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->first();

    abort_unless($quiz, 404, '试卷不存在');

    $validated = $request->validate([
      'sequence_number' => 'required|integer|min:1',
      'type' => 'required|in:单选,多选,判断,主观',
      'answer' => 'nullable|string',
      'analysis' => 'nullable|string',
      'score' => 'nullable|numeric|min:0|max:999.99',
    ]);

    $targetSequenceNumber = (int) $validated['sequence_number'];
    $sequenceExists = DB::table('questions')
      ->where('quiz_id', $quizId)
      ->where('sequence_number', $targetSequenceNumber)
      ->exists();
    abort_if($sequenceExists, 422, '该测验已存在题号 ' . $targetSequenceNumber . '，请更换');

    $answer = $this->normalizeAnswer($validated);

    $questionId = DB::table('questions')->insertGetId([
      'teacher_id' => $teacherId,
      'quiz_id' => $quizId,
      'sequence_number' => $targetSequenceNumber,
      'type' => $validated['type'],
      'answer' => $answer,
      'analysis' => $validated['analysis'] ?? null,
      'score' => $validated['score'] ?? 1,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return response()->json([
      'message' => '题目已加入试卷',
      'question' => DB::table('questions')->where('question_id', $questionId)->first(),
    ]);
  }

  public function updateItem(Request $request, int $quizId, int $questionId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quizExists = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->exists();

    abort_unless($quizExists, 404, '试卷不存在');

    $question = DB::table('questions')
      ->where('question_id', $questionId)
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->first();

    abort_unless($question, 404, '题目不存在');

    $validated = $request->validate([
      'sequence_number' => 'required|integer|min:1',
      'type' => 'required|in:单选,多选,判断,主观',
      'answer' => 'nullable|string',
      'analysis' => 'nullable|string',
      'score' => 'nullable|numeric|min:0|max:999.99',
    ]);

    $targetSequenceNumber = (int) $validated['sequence_number'];
    if ($targetSequenceNumber !== $question->sequence_number) {
      $targetExists = DB::table('questions')
        ->where('quiz_id', $quizId)
        ->where('sequence_number', $targetSequenceNumber)
        ->exists();
      abort_if($targetExists, 422, '该测验已存在题号 ' . $targetSequenceNumber . '，请更换');

      $hasAnswerDetails = DB::table('answer_details')
        ->where('question_id', $questionId)
        ->exists();
      abort_if($hasAnswerDetails, 422, '该题已有答题记录，暂不允许修改题号');
    }

    $answer = $this->normalizeAnswer($validated);

    DB::table('questions')
      ->where('question_id', $questionId)
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->update([
        'sequence_number' => $targetSequenceNumber,
        'type' => $validated['type'],
        'answer' => $answer,
        'analysis' => $validated['analysis'] ?? null,
        'score' => $validated['score'] ?? $question->score,
        'updated_at' => now(),
      ]);

    return response()->json(['message' => '题目更新成功']);
  }

  public function removeItem(Request $request, int $quizId, int $questionId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quizExists = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->exists();

    abort_unless($quizExists, 404, '试卷不存在');

    $deleted = DB::table('questions')
      ->where('teacher_id', $teacherId)
      ->where('quiz_id', $quizId)
      ->where('question_id', $questionId)
      ->delete();

    abort_if($deleted === 0, 404, '题目不存在');

    return response()->json(['message' => '题目已移除']);
  }

  public function assignClasses(Request $request, int $quizId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quizExists = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->exists();

    abort_unless($quizExists, 404, '试卷不存在');

    $validated = $request->validate([
      'class_ids' => 'required|array|min:1',
      'class_ids.*' => 'integer|exists:classes,class_id',
    ]);

    $created = 0;
    foreach ($validated['class_ids'] as $classId) {
      $exists = DB::table('quiz_assignments')
        ->where('quiz_id', $quizId)
        ->where('class_id', $classId)
        ->exists();

      if ($exists) {
        continue;
      }

      DB::table('quiz_assignments')->insert([
        'quiz_id' => $quizId,
        'class_id' => $classId,
        'assigned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $created += 1;
    }

    return response()->json(['message' => '发布成功', 'created_count' => $created]);
  }

  public function removeAssignment(Request $request, int $quizId, int $assignmentId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $quizExists = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->exists();

    abort_unless($quizExists, 404, '试卷不存在');

    DB::table('quiz_assignments')
      ->where('assignment_id', $assignmentId)
      ->where('quiz_id', $quizId)
      ->delete();

    return response()->json(['message' => '已取消发布']);
  }

  public function generateAnswerSheet(Request $request, int $quizId)
  {
    $teacherId = $this->resolveTeacherId($request);

    // 获取试卷信息
    $quiz = DB::table('quizzes')
      ->where('quiz_id', $quizId)
      ->where('teacher_id', $teacherId)
      ->first(['quiz_id', 'title', 'start_time', 'created_at']);

    abort_unless($quiz, 404, '试卷不存在');

    // 获取客观题（单选、多选、判断）
    $questions = DB::table('questions')
      ->where('quiz_id', $quizId)
      ->whereIn('type', ['单选', '多选', '判断'])
      ->orderBy('sequence_number')
      ->get(['question_id', 'sequence_number', 'type', 'answer', 'score']);

    if ($questions->isEmpty()) {
      return response()->json(['message' => '该测验没有客观题，无法生成答题卡'], 400);
    }

    // 解析 start_time 获取测验名称和时间
    $startTime = $quiz->start_time ?? '';
    [$startDate, $startTimeOnly] = array_pad(explode(' ', $startTime, 2), 2, '');

    // 组装JSON数据
    $jsonData = [
      'testName' => $quiz->title ?? "测验{$quizId}",
      'startDate' => $startDate,
      'startTime' => $startTimeOnly,
      'omrSheets' => [],
    ];

    foreach ($questions as $question) {
      $jsonData['omrSheets'][] = [
        'sequence_number' => $question->sequence_number,
        'type' => $question->type,
        'answer' => $question->answer,
        'score' => $question->score,
      ];
    }

    $format = strtolower((string) $request->query('format', 'zip'));
    if (!in_array($format, ['png', 'pdf', 'zip'], true)) {
      $format = 'zip';
    }

    // 先将本次生成参数保存到 backend-ocr/rejson
    $rejsonDir = base_path('../backend-ocr/rejson');
    if (!is_dir($rejsonDir)) {
      mkdir($rejsonDir, 0755, true);
    }
    $safeJsonTitle = preg_replace('#[\\/\:*?"<>|]+#u', '_', (string) $quiz->title) ?: ('测验' . $quizId);
    $rejsonPath = $rejsonDir . DIRECTORY_SEPARATOR . 'rejson_' . $quizId . '_' . $safeJsonTitle . '_' . date('Ymd_His') . '.json';
    file_put_contents($rejsonPath, json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $safeTitle = preg_replace('#[\\/\:*?"<>|]+#u', '_', (string) $quiz->title) ?: ('测验' . $quizId);
    $baseName = '答题卡_' . $safeTitle;

    $outputDir = storage_path('app/omr_cards');
    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0755, true);
    }

    $pythonScript = base_path('../backend-ocr/code/omr_circle_generator.py');
    $venvPython = base_path('../backend-ocr/code/.venv/Scripts/python.exe');
    $pythonExecutable = file_exists($venvPython) ? $venvPython : 'python';

    $runGenerator = function (string $targetFormat, string $targetFilename) use ($pythonExecutable, $pythonScript, $rejsonPath) {
      $command = sprintf(
        '%s %s --input_json %s --output_format %s --output_filename %s 2>&1',
        escapeshellarg($pythonExecutable),
        escapeshellarg($pythonScript),
        escapeshellarg($rejsonPath),
        escapeshellarg($targetFormat),
        escapeshellarg($targetFilename)
      );

      $output = [];
      $returnCode = 0;
      exec($command, $output, $returnCode);

      $outputPath = null;
      foreach ($output as $line) {
        if (str_starts_with((string) $line, 'OUTPUT_PATH=')) {
          $candidate = trim(substr((string) $line, strlen('OUTPUT_PATH=')));
          if ($candidate !== '' && file_exists($candidate)) {
            $outputPath = $candidate;
            break;
          }
        }

        if (strpos((string) $line, 'OMR 答题卡已生成:') === false) {
          continue;
        }

        $parts = explode(':', (string) $line, 2);
        if (count($parts) === 2) {
          $outputPath = trim($parts[1]);
        }

        if ($outputPath && file_exists($outputPath)) {
          break;
        }
      }

      return [
        'command' => $command,
        'output' => $output,
        'return_code' => $returnCode,
        'output_path' => $outputPath,
      ];
    };

    if ($format === 'zip') {
      $pdfName = $baseName . '.pdf';
      $pngName = $baseName . '.png';

      $pdfResult = $runGenerator('pdf', $pdfName);
      if ($pdfResult['return_code'] !== 0 || !$pdfResult['output_path'] || !file_exists($pdfResult['output_path'])) {
        Log::error('Python PDF generation failed', $pdfResult);
        return response()->json([
          'message' => '答题卡PDF生成失败',
          'error' => $this->sanitizeOutputLines($pdfResult['output']),
        ], 500);
      }

      $pngResult = $runGenerator('png', $pngName);
      if ($pngResult['return_code'] !== 0 || !$pngResult['output_path'] || !file_exists($pngResult['output_path'])) {
        Log::error('Python PNG generation failed', $pngResult);
        return response()->json([
          'message' => '答题卡PNG生成失败',
          'error' => $this->sanitizeOutputLines($pngResult['output']),
        ], 500);
      }

      $zipPath = $outputDir . DIRECTORY_SEPARATOR . uniqid('answer_sheet_', true) . '.zip';
      $zip = new \ZipArchive();
      if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        return response()->json(['message' => '创建ZIP文件失败'], 500);
      }

      $zip->addFile($rejsonPath, $baseName . '.json');
      $zip->addFile($pdfResult['output_path'], $pdfName);
      $zip->addFile($pngResult['output_path'], $pngName);
      $zip->close();

      $zipContent = file_get_contents($zipPath);
      if ($zipContent === false || strncmp($zipContent, 'PK', 2) !== 0) {
        return response()->json(['message' => '读取ZIP文件失败'], 500);
      }

      @unlink($pdfResult['output_path']);
      @unlink($pngResult['output_path']);
      @unlink($zipPath);

      $zipDownloadName = $baseName . '.zip';
      return response($zipContent, 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="' . addslashes($zipDownloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($zipDownloadName),
        'Content-Length' => (string) strlen($zipContent),
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
      ]);
    }

    $downloadName = $baseName . '.' . $format;
    $singleResult = $runGenerator($format, $downloadName);
    if ($singleResult['return_code'] !== 0 || !$singleResult['output_path'] || !file_exists($singleResult['output_path'])) {
      Log::error('Python script failed', $singleResult);

      return response()->json([
        'message' => '答题卡生成失败，未找到输出文件',
        'output' => $this->sanitizeOutputLines($singleResult['output']),
      ], 500);
    }

    $fileContent = file_get_contents($singleResult['output_path']);
    if ($fileContent === false) {
      return response()->json(['message' => '读取答题卡文件失败'], 500);
    }

    if ($format === 'png') {
      $pngHeader = "\x89PNG\r\n\x1a\n";
      if (strncmp($fileContent, $pngHeader, 8) !== 0) {
        Log::error('答题卡PNG签名校验失败', ['output_path' => $singleResult['output_path'], 'size' => strlen($fileContent)]);
        return response()->json(['message' => '生成的PNG文件格式无效'], 500);
      }
    }

    if ($format === 'pdf') {
      if (strncmp($fileContent, '%PDF-', 5) !== 0) {
        Log::error('答题卡PDF签名校验失败', ['output_path' => $singleResult['output_path'], 'size' => strlen($fileContent)]);
        return response()->json(['message' => '生成的PDF文件格式无效'], 500);
      }
    }

    @unlink($singleResult['output_path']);

    $mimeType = $format === 'pdf' ? 'application/pdf' : 'image/png';
    return response($fileContent, 200, [
      'Content-Type' => $mimeType,
      'Content-Disposition' => 'attachment; filename="' . addslashes($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName),
      'Content-Length' => (string) strlen($fileContent),
      'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
      'Pragma' => 'no-cache',
    ]);
  }
}
