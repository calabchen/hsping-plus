<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    if (!Schema::hasTable('questions')) {
      return;
    }

    Schema::table('questions', function (Blueprint $table) {
      if (!Schema::hasColumn('questions', 'quiz_id')) {
        $table->foreignId('quiz_id')->nullable()->after('teacher_id')->constrained('quizzes', 'quiz_id')->cascadeOnDelete();
      }
      if (!Schema::hasColumn('questions', 'score')) {
        $table->decimal('score', 5, 2)->default(1.00)->after('analysis');
      }
      if (!Schema::hasColumn('questions', 'sort_order')) {
        $table->integer('sort_order')->default(0)->after('score');
      }
    });

    if (Schema::hasTable('quiz_question_items')) {
      // 将旧中间表中的分值和排序回填到 questions，迁移到“题目归属试卷”模型。
      DB::statement(
        'UPDATE questions q '
          . 'INNER JOIN quiz_question_items qqi ON qqi.question_id = q.question_id '
          . 'SET q.quiz_id = qqi.quiz_id, q.score = qqi.score, q.sort_order = qqi.sort_order '
          . 'WHERE q.quiz_id IS NULL'
      );

      Schema::drop('quiz_question_items');
    }
  }

  public function down(): void
  {
    if (!Schema::hasTable('questions')) {
      return;
    }

    if (!Schema::hasTable('quiz_question_items')) {
      Schema::create('quiz_question_items', function (Blueprint $table) {
        $table->foreignId('quiz_id')->constrained('quizzes', 'quiz_id')->cascadeOnDelete();
        $table->foreignId('question_id')->constrained('questions', 'question_id')->cascadeOnDelete();
        $table->decimal('score', 5, 2)->default(1.00);
        $table->integer('sort_order')->default(0);
        $table->primary(['quiz_id', 'question_id']);
      });

      DB::statement(
        'INSERT INTO quiz_question_items (quiz_id, question_id, score, sort_order) '
          . 'SELECT quiz_id, question_id, score, sort_order FROM questions WHERE quiz_id IS NOT NULL'
      );
    }

    Schema::table('questions', function (Blueprint $table) {
      if (Schema::hasColumn('questions', 'quiz_id')) {
        $table->dropConstrainedForeignId('quiz_id');
      }
      if (Schema::hasColumn('questions', 'sort_order')) {
        $table->dropColumn('sort_order');
      }
      if (Schema::hasColumn('questions', 'score')) {
        $table->dropColumn('score');
      }
    });
  }
};
