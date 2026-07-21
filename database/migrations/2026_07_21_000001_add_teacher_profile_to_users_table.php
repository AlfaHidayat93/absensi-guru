<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 50)->nullable()->after('role');
            $table->text('subjects')->nullable()->after('nip');
            $table->string('status', 20)->default('pending')->after('subjects'); // pending, verified
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'subjects', 'status']);
        });
    }
};
