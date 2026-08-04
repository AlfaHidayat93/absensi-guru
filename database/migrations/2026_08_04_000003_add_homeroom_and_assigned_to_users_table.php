<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('homeroom_class', 20)->nullable()->after('status');   // Kelas binaan (untuk Wali Kelas)
            $table->json('assigned_classes')->nullable()->after('homeroom_class');  // ["X-PH 5", "XI-IPA 1"]
            $table->json('assigned_subjects')->nullable()->after('assigned_classes'); // ["Bahasa Indonesia", "Matematika"]
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['homeroom_class', 'assigned_classes', 'assigned_subjects']);
        });
    }
};
