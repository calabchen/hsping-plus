<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
  private function resolveTeacherId(Request $request): int
  {
    $teacherId = DB::table('teachers')
      ->where('user_id', $request->user()->id)
      ->value('teacher_id');

    abort_unless($teacherId, 422, '请先完善教师资料后再管理题目');

    return (int) $teacherId;
  }

  public function index(Request $request)
  {
    $teacherId = $this->resolveTeacherId($request);

    $questions = DB::table('questions')
      ->where('teacher_id', $teacherId)
      ->select([
        'question_id',
        'type',
        'answer',
        'analysis',
        'created_at',
      ])
      ->orderByDesc('question_id')
      ->get();

    return response()->json(['questions' => $questions]);
  }

  public function store(Request $request)
  {
    $teacherId = $this->resolveTeacherId($request);

    $validated = $request->validate([
      'type' => 'required|in:单选,多选,判断,主观',
      'answer' => 'nullable|string',
      'analysis' => 'nullable|string',
    ]);

    $answer = isset($validated['answer']) ? trim((string) $validated['answer']) : null;
    if ($answer === '') {
      $answer = null;
    }

    if ($validated['type'] === '单选') {
      abort_unless(in_array($answer, ['A', 'B', 'C', 'D'], true), 422, '单选题答案必须是 A/B/C/D');
    }

    if ($validated['type'] === '多选') {
      abort_unless(
        is_string($answer) && preg_match('/^(?!.*(.).*\\1)[A-H]{1,8}$/', $answer) === 1,
        422,
        '多选题答案必须是 A-H 的组合且不能重复'
      );
    }

    if ($validated['type'] === '判断') {
      abort_unless(in_array($answer, ['T', 'F', '对', '错', '√', '×'], true), 422, '判断题答案必须是 T/F 或 对/错');
    }

    $questionId = DB::table('questions')->insertGetId([
      'teacher_id' => $teacherId,
      'type' => $validated['type'],
      'answer' => $answer,
      'analysis' => $validated['analysis'] ?? null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return response()->json([
      'message' => '题目创建成功',
      'question' => DB::table('questions')->where('question_id', $questionId)->first(),
    ]);
  }

  public function update(Request $request, int $questionId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $exists = DB::table('questions')
      ->where('question_id', $questionId)
      ->where('teacher_id', $teacherId)
      ->exists();

    abort_unless($exists, 404, '题目不存在');

    $validated = $request->validate([
      'type' => 'required|in:单选,多选,判断,主观',
      'answer' => 'nullable|string',
      'analysis' => 'nullable|string',
    ]);

    $answer = isset($validated['answer']) ? trim((string) $validated['answer']) : null;
    if ($answer === '') {
      $answer = null;
    }

    if ($validated['type'] === '单选') {
      abort_unless(in_array($answer, ['A', 'B', 'C', 'D'], true), 422, '单选题答案必须是 A/B/C/D');
    }

    if ($validated['type'] === '多选') {
      abort_unless(
        is_string($answer) && preg_match('/^(?!.*(.).*\\1)[A-H]{1,8}$/', $answer) === 1,
        422,
        '多选题答案必须是 A-H 的组合且不能重复'
      );
    }

    if ($validated['type'] === '判断') {
      abort_unless(in_array($answer, ['T', 'F', '对', '错', '√', '×'], true), 422, '判断题答案必须是 T/F 或 对/错');
    }

    DB::table('questions')
      ->where('question_id', $questionId)
      ->update([
        'type' => $validated['type'],
        'answer' => $answer,
        'analysis' => $validated['analysis'] ?? null,
        'updated_at' => now(),
      ]);

    return response()->json(['message' => '题目更新成功']);
  }

  public function destroy(Request $request, int $questionId)
  {
    $teacherId = $this->resolveTeacherId($request);

    $deleted = DB::table('questions')
      ->where('question_id', $questionId)
      ->where('teacher_id', $teacherId)
      ->delete();

    abort_if($deleted === 0, 404, '题目不存在');

    return response()->json(['message' => '题目已删除']);
  }
}
