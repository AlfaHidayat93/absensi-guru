<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('id_absen', 50)->unique();
            $table->string('kelas', 20)->index();
            $table->string('semester', 20)->default('Ganjil');
            $table->date('tanggal');
            $table->string('jam_mulai', 10)->nullable();
            $table->string('jam_selesai', 10)->nullable();
            $table->string('mata_pelajaran', 100)->nullable();
            $table->string('guru', 100)->nullable();
            $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('materi_pembelajaran')->nullable();
            $table->text('catatan_kelas')->nullable();
            $table->json('detail_kehadiran')->nullable(); // {"NIS": {"status":"Hadir","keaktifan":"aktif","note":"..."}}
            $table->timestamps();

            $table->index(['kelas', 'semester']);
            $table->index(['kelas', 'mata_pelajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
