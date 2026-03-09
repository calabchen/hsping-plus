<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    if (!Schema::hasTable('teachers')) {
      Schema::create('teachers', function (Blueprint $table) {
        $table->id('teacher_id');
        $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
        $table->string('last_name', 50)->nullable();
        $table->string('first_name', 50)->nullable();
        $table->enum('gender', ['男', '女'])->nullable();
        $table->enum('education_stage', ['小学', '初中', '高中', '大学'])->nullable();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('classes')) {
      Schema::create('classes', function (Blueprint $table) {
        $table->id('class_id');
        $table->string('class_num', 20);
        $table->year('enrollment_year')->nullable();
        $table->year('graduation_year')->nullable();
        $table->boolean('is_graduated')->default(false);
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('students')) {
      Schema::create('students', function (Blueprint $table) {
        $table->string('student_id', 20)->primary();
        $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
        $table->string('last_name', 50);
        $table->string('first_name', 50);
        $table->enum('gender', ['男', '女'])->nullable();
        $table->unsignedTinyInteger('age')->nullable();
        $table->string('avatar_path', 255)->nullable();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('questions')) {
      Schema::create('questions', function (Blueprint $table) {
        $table->id('question_id');
        $table->foreignId('teacher_id')->constrained('teachers', 'teacher_id')->cascadeOnDelete();
        $table->enum('type', ['单选', '多选', '判断', '主观']);
        $table->longText('options')->nullable();
        $table->text('answer')->nullable();
        $table->text('analysis')->nullable();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('quizzes')) {
      Schema::create('quizzes', function (Blueprint $table) {
        $table->id('quiz_id');
        $table->foreignId('teacher_id')->constrained('teachers', 'teacher_id')->cascadeOnDelete();
        $table->string('title', 100);
        $table->dateTime('start_time')->nullable();
        $table->dateTime('end_time')->nullable();
        $table->enum('status', ['草稿', '已发布', '已结束', '已归档'])->default('草稿');
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('quiz_question_items')) {
      Schema::create('quiz_question_items', function (Blueprint $table) {
        $table->foreignId('quiz_id')->constrained('quizzes', 'quiz_id')->cascadeOnDelete();
        $table->foreignId('question_id')->constrained('questions', 'question_id')->cascadeOnDelete();
        $table->decimal('score', 5, 2)->default(1.00);
        $table->integer('sort_order')->default(0);
        $table->primary(['quiz_id', 'question_id']);
      });
    }

    if (!Schema::hasTable('quiz_assignments')) {
      Schema::create('quiz_assignments', function (Blueprint $table) {
        $table->id('assignment_id');
        $table->foreignId('quiz_id')->constrained('quizzes', 'quiz_id')->cascadeOnDelete();
        $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
        $table->timestamp('assigned_at')->useCurrent();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('submissions')) {
      Schema::create('submissions', function (Blueprint $table) {
        $table->id('submission_id');
        $table->foreignId('quiz_id')->constrained('quizzes', 'quiz_id')->cascadeOnDelete();
        $table->string('student_id', 20);
        $table->foreign('student_id')->references('student_id')->on('students')->cascadeOnDelete();
        $table->decimal('total_score', 5, 2)->default(0.00);
        $table->enum('status', ['待批改', '已完成'])->default('待批改');
        $table->dateTime('submit_time')->useCurrent();
        $table->string('answer_card_path', 255)->nullable();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('answer_details')) {
      Schema::create('answer_details', function (Blueprint $table) {
        $table->id('detail_id');
        $table->foreignId('submission_id')->constrained('submissions', 'submission_id')->cascadeOnDelete();
        $table->foreignId('question_id')->constrained('questions', 'question_id')->cascadeOnDelete();
        $table->text('student_answer')->nullable();
        $table->boolean('is_correct')->nullable();
        $table->decimal('earned_score', 5, 2)->default(0.00);
        $table->decimal('ai_suggested_score', 5, 2)->nullable();
        $table->timestamps();
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('answer_details');
    Schema::dropIfExists('submissions');
    Schema::dropIfExists('quiz_assignments');
    Schema::dropIfExists('quiz_question_items');
    Schema::dropIfExists('quizzes');
    Schema::dropIfExists('questions');
    Schema::dropIfExists('students');
    Schema::dropIfExists('classes');
    Schema::dropIfExists('teachers');
  }
};
