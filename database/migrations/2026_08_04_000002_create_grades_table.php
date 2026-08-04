<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->index();
            $table->string('kelas', 20)->index();
            $table->string('semester', 20)->default('Ganjil');
            $table->string('mata_pelajaran', 100)->index();
            $table->decimal('tugas_1', 5, 2)->nullable();
            $table->decimal('tugas_2', 5, 2)->nullable();
            $table->decimal('tugas_3', 5, 2)->nullable();
            $table->decimal('pts', 5, 2)->nullable();
            $table->decimal('pas', 5, 2)->nullable();
            $table->decimal('praktik', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['nis', 'kelas', 'semester', 'mata_pelajaran'], 'grades_unique_key');
            $table->foreign('nis')->references('nis')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
