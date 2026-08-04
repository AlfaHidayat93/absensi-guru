<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('grade_settings')) {
            Schema::create('grade_settings', function (Blueprint $table) {
                $table->id();
                $table->string('kelas', 20)->index();
                $table->string('semester', 20)->default('Ganjil');
                $table->string('mata_pelajaran', 100)->index();
                $table->json('tasks')->nullable();
                $table->timestamps();

                $table->unique(['kelas', 'semester', 'mata_pelajaran'], 'grade_settings_unique');
            });
        }

        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'poin_sikap')) {
                    $table->decimal('poin_sikap', 5, 2)->nullable()->after('praktik');
                }
                if (!Schema::hasColumn('grades', 'task_scores')) {
                    $table->json('task_scores')->nullable()->after('poin_sikap');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_settings');
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (Schema::hasColumn('grades', 'poin_sikap')) {
                    $table->dropColumn('poin_sikap');
                }
                if (Schema::hasColumn('grades', 'task_scores')) {
                    $table->dropColumn('task_scores');
                }
            });
        }
    }
};
