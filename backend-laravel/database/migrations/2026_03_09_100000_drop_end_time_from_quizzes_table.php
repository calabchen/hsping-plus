<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    if (!Schema::hasTable('quizzes') || !Schema::hasColumn('quizzes', 'end_time')) {
      return;
    }

    Schema::table('quizzes', function (Blueprint $table) {
      $table->dropColumn('end_time');
    });
  }

  public function down(): void
  {
    if (!Schema::hasTable('quizzes') || Schema::hasColumn('quizzes', 'end_time')) {
      return;
    }

    Schema::table('quizzes', function (Blueprint $table) {
      $table->dateTime('end_time')->nullable()->after('start_time');
    });
  }
};
