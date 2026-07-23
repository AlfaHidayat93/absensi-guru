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
        // Super Admin (Default)
        User::updateOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('admin123'),
                'role'     => 'super_admin',
                'status'   => 'verified',
            ]
        );

        // Guru (Default)
        User::updateOrCreate(
            ['email' => 'guru@sekolah.sch.id'],
            [
                'name'     => 'Guru',
                'password' => bcrypt('guru123'),
                'role'     => 'guru',
                'status'   => 'verified',
            ]
        );

        // Akun Demo Resmi SMKN 1 Pujut - Super Admin
        User::updateOrCreate(
            ['email' => 'admin@smkn1pujut.sch.id'],
            [
                'name'     => 'Admin SMKN 1 Pujut',
                'password' => bcrypt('adminpujut123'),
                'role'     => 'super_admin',
                'status'   => 'verified',
            ]
        );

        // Akun Demo Resmi SMKN 1 Pujut - Guru
        User::updateOrCreate(
            ['email' => 'guru.demo@guru.smk.belajar.id'],
            [
                'name'     => 'Drs. Lalu Hidayat, M.Pd.',
                'password' => bcrypt('gurudemo123'),
                'role'     => 'guru',
                'nip'      => '198503152010011002',
                'subjects' => 'Bahasa Indonesia, Matematika',
                'status'   => 'verified',
            ]
        );
    }
}
