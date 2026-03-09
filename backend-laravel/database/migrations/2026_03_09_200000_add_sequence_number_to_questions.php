<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // 检查字段是否已存在
    if (Schema::hasColumn('questions', 'sequence_number')) {
      return;
    }

    // 1. 先添加字段（不带唯一约束）
    Schema::table('questions', function (Blueprint $table) {
      $table->unsignedInteger('sequence_number')->nullable()->after('quiz_id');
    });

    // 2. 为现有数据分配序号（按question_id排序）
    $quizzes = DB::table('questions')
      ->select('quiz_id')
      ->whereNotNull('quiz_id')
      ->distinct()
      ->pluck('quiz_id');

    foreach ($quizzes as $quizId) {
      $questions = DB::table('questions')
        ->where('quiz_id', $quizId)
        ->orderBy('question_id')
        ->pluck('question_id');

      foreach ($questions as $index => $questionId) {
        DB::table('questions')
          ->where('question_id', $questionId)
          ->update(['sequence_number' => $index + 1]);
      }
    }

    // 3. 设置字段为非空并添加唯一约束
    Schema::table('questions', function (Blueprint $table) {
      $table->unsignedInteger('sequence_number')->default(1)->nullable(false)->change();
      $table->unique(['quiz_id', 'sequence_number'], 'quiz_sequence_unique');
    });
  }

  public function down(): void
  {
    Schema::table('questions', function (Blueprint $table) {
      $table->dropUnique('quiz_sequence_unique');
      $table->dropColumn('sequence_number');
    });
  }
};
