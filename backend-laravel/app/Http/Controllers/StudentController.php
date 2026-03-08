<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
  public function store(Request $request)
  {
    $validated = $request->validate([
      'student_id' => 'required|string|max:20|unique:students,student_id',
      'class_id' => 'required|integer|exists:classes,class_id',
      'last_name' => 'required|string|max:50',
      'first_name' => 'required|string|max:50',
      'gender' => 'nullable|string|in:男,女',
      'age' => 'nullable|integer|min:1|max:120',
      'avatar_path' => 'nullable|string|max:255',
    ]);

    $student = Student::create($validated);

    return response()->json([
      'message' => '学生创建成功',
      'student' => $student,
    ]);
  }

  public function bulkStore(Request $request)
  {
    $validated = $request->validate([
      'class_id' => 'required|integer|exists:classes,class_id',
      'students' => 'required|array|min:1',
      'students.*.student_id' => 'required|string|max:20',
      'students.*.last_name' => 'required|string|max:50',
      'students.*.first_name' => 'required|string|max:50',
      'students.*.gender' => 'nullable|string|in:男,女',
      'students.*.age' => 'nullable|integer|min:1|max:120',
      'students.*.avatar_path' => 'nullable|string|max:255',
    ]);

    $classId = $validated['class_id'];
    $students = $validated['students'];

    $existingIds = DB::table('students')
      ->whereIn('student_id', array_map(fn($s) => $s['student_id'], $students))
      ->pluck('student_id')
      ->all();

    if (!empty($existingIds)) {
      return response()->json([
        'message' => '存在重复学号，导入已终止',
        'duplicated_student_ids' => $existingIds,
      ], 422);
    }

    $rows = array_map(function ($item) use ($classId) {
      return [
        'student_id' => $item['student_id'],
        'class_id' => $classId,
        'last_name' => $item['last_name'],
        'first_name' => $item['first_name'],
        'gender' => $item['gender'] ?? null,
        'age' => $item['age'] ?? null,
        'avatar_path' => $item['avatar_path'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }, $students);

    DB::table('students')->insert($rows);

    return response()->json([
      'message' => '批量导入成功',
      'count' => count($rows),
    ]);
  }

  public function update(Request $request, string $studentId)
  {
    $student = Student::findOrFail($studentId);

    $validated = $request->validate([
      'last_name' => 'required|string|max:50',
      'first_name' => 'required|string|max:50',
      'gender' => 'nullable|string|in:男,女',
      'age' => 'nullable|integer|min:1|max:120',
      'avatar_path' => 'nullable|string|max:255',
    ]);

    $student->fill($validated);
    $student->save();

    return response()->json([
      'message' => '学生信息更新成功',
      'student' => $student,
    ]);
  }

  public function destroy(string $studentId)
  {
    $student = Student::findOrFail($studentId);
    $student->delete();

    return response()->json([
      'message' => '学生已删除',
    ]);
  }
}
