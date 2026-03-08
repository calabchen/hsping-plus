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
    if (Schema::hasTable('users')) {
      Schema::table('users', function (Blueprint $table) {
        $toDrop = [];

        if (Schema::hasColumn('users', 'subject')) {
          $toDrop[] = 'subject';
        }

        if (Schema::hasColumn('users', 'gender')) {
          $toDrop[] = 'gender';
        }

        if (!empty($toDrop)) {
          $table->dropColumn($toDrop);
        }
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    if (Schema::hasTable('users')) {
      Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'subject')) {
          $table->string('subject')->nullable()->after('email');
        }

        if (!Schema::hasColumn('users', 'gender')) {
          $table->string('gender')->nullable()->after('subject');
        }
      });
    }
  }
};
