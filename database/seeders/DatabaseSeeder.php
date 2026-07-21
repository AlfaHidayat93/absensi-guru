<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('admin123'),
                'role'     => 'super_admin',
                'status'   => 'verified',
            ]
        );

        // Guru
        User::updateOrCreate(
            ['email' => 'guru@sekolah.sch.id'],
            [
                'name'     => 'Guru',
                'password' => bcrypt('guru123'),
                'role'     => 'guru',
                'status'   => 'verified',
            ]
        );
    }
}
