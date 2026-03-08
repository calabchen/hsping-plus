<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{
  public function index()
  {
    $classes = SchoolClass::query()
      ->select('classes.*')
      ->selectSub(function ($query) {
        $query->from('students')
          ->selectRaw('COUNT(*)')
          ->whereColumn('students.class_id', 'classes.class_id');
      }, 'student_count')
      ->orderByDesc('class_id')
      ->get();

    return response()->json([
      'classes' => $classes,
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'class_num' => 'required|string|max:10|regex:/^\d+$/|unique:classes,class_num',
      'enrollment_year' => 'nullable|integer|min:1900|max:9999',
      'graduation_year' => 'nullable|integer|min:1900|max:9999|gte:enrollment_year',
      'is_graduated' => 'nullable|boolean',
    ]);

    $class = SchoolClass::create([
      'class_num' => $validated['class_num'],
      'enrollment_year' => $validated['enrollment_year'] ?? null,
      'graduation_year' => $validated['graduation_year'] ?? null,
      'is_graduated' => $validated['is_graduated'] ?? false,
    ]);

    return response()->json([
      'message' => '班级创建成功',
      'class' => [
        'class_id' => $class->class_id,
        'class_num' => $class->class_num,
        'enrollment_year' => $class->enrollment_year,
        'graduation_year' => $class->graduation_year,
        'is_graduated' => $class->is_graduated,
        'student_count' => 0,
      ],
    ]);
  }

  public function update(Request $request, int $classId)
  {
    $class = SchoolClass::findOrFail($classId);

    $validated = $request->validate([
      'class_num' => 'required|string|max:10|regex:/^\d+$/|unique:classes,class_num,' . $class->class_id . ',class_id',
      'enrollment_year' => 'nullable|integer|min:1900|max:9999',
      'graduation_year' => 'nullable|integer|min:1900|max:9999|gte:enrollment_year',
      'is_graduated' => 'nullable|boolean',
    ]);

    $class->class_num = $validated['class_num'];
    $class->enrollment_year = $validated['enrollment_year'] ?? null;
    $class->graduation_year = $validated['graduation_year'] ?? null;
    $class->is_graduated = $validated['is_graduated'] ?? false;
    $class->save();

    $studentCount = DB::table('students')->where('class_id', $class->class_id)->count();

    return response()->json([
      'message' => '班级更新成功',
      'class' => [
        'class_id' => $class->class_id,
        'class_num' => $class->class_num,
        'enrollment_year' => $class->enrollment_year,
        'graduation_year' => $class->graduation_year,
        'is_graduated' => $class->is_graduated,
        'student_count' => $studentCount,
      ],
    ]);
  }

  public function destroy(int $classId)
  {
    $class = SchoolClass::findOrFail($classId);
    $class->delete();

    return response()->json([
      'message' => '班级已删除',
    ]);
  }

  public function students(int $classId)
  {
    $class = SchoolClass::findOrFail($classId);

    $students = DB::table('students')
      ->where('class_id', $class->class_id)
      ->orderBy('student_id')
      ->get([
        'student_id',
        'last_name',
        'first_name',
        'gender',
        'age',
        'avatar_path',
      ]);

    return response()->json([
      'class' => [
        'class_id' => $class->class_id,
        'class_num' => $class->class_num,
        'enrollment_year' => $class->enrollment_year,
      ],
      'students' => $students,
    ]);
  }
}
