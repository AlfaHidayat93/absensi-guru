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
        // 1. Seed Default Subjects
        $defaultSubjects = [
            'Bahasa Indonesia',
            'Matematika',
            'Bahasa Inggris',
            'Pendidikan Agama Islam',
            'IPAS',
            'Informatika',
            'Pendidikan Pancasila',
            'PJOK',
            'Dasar-Dasar Perhotelan',
            'Koding dan Kecerdasan Buatan',
        ];

        foreach ($defaultSubjects as $subjectName) {
            \App\Models\Subject::firstOrCreate(['name' => $subjectName]);
        }

        // 2. Super Admin (Default)
        User::updateOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('admin123'),
                'role'     => 'super_admin',
                'status'   => 'verified',
            ]
        );

        // 3. Akun Demo Resmi SMKN 1 Pujut - Super Admin
        User::updateOrCreate(
            ['email' => 'admin@smkn1pujut.sch.id'],
            [
                'name'     => 'Admin SMKN 1 Pujut',
                'password' => bcrypt('adminpujut123'),
                'role'     => 'super_admin',
                'status'   => 'verified',
            ]
        );

        // 4. Akun Demo Resmi SMKN 1 Pujut - Wali Kelas (X-PH 5)
        User::updateOrCreate(
            ['email' => 'walikelas.demo@smkn1pujut.sch.id'],
            [
                'name'              => 'Ibu Nurul Hidayati, S.Pd. (Wali Kelas X-PH 5)',
                'password'          => bcrypt('walidemo123'),
                'role'              => 'wali_kelas',
                'nip'               => '198805202015032001',
                'status'            => 'verified',
                'homeroom_class'    => 'X-PH 5',
                'assigned_classes'  => ['X-PH 5', 'XI-RPL 1'],
                'assigned_subjects' => ['Bahasa Indonesia', 'IPAS'],
            ]
        );

        // 5. Akun Demo Resmi SMKN 1 Pujut - Guru Mata Pelajaran
        User::updateOrCreate(
            ['email' => 'guru.demo@guru.smk.belajar.id'],
            [
                'name'              => 'Drs. Lalu Hidayat, M.Pd.',
                'password'          => bcrypt('gurudemo123'),
                'role'              => 'guru',
                'nip'               => '198503152010011002',
                'subjects'          => 'Bahasa Indonesia, Matematika',
                'status'            => 'verified',
                'assigned_classes'  => ['X-PH 5'],
                'assigned_subjects' => ['Bahasa Indonesia', 'Matematika'],
            ]
        );
    }
}
