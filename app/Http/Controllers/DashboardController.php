<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Ambil semua kelas dari DB lokal
        $allClasses = Student::select('kelas')
            ->distinct()
            ->whereNotNull('kelas')
            ->pluck('kelas')
            ->sort()
            ->values()
            ->all();

        // Filter kelas sesuai hak akses peranan user
        $classes = $user->getAccessibleClasses($allClasses);

        // Filter data siswa sesuai kelas yang berhak diakses user
        $studentsQuery = Student::query();
        if (!$user->isSuperAdmin()) {
            $studentsQuery->whereIn('kelas', $classes);
        }

        $students = $studentsQuery->get();
        $totalSiswa = $students->count();
        $totalKelas = count($classes);

        // Ambil data absensi sesuai kelas yang diizinkan
        $attendancesQuery = Attendance::query();
        if (!$user->isSuperAdmin()) {
            $attendancesQuery->whereIn('kelas', $classes);
        }
        $attendances = $attendancesQuery->get();

        $totalHadir = 0;
        $totalEntriKehadiran = 0;
        $kehadiranKelasMap = [];

        foreach ($attendances as $att) {
            $rawDetail = $att->detail_kehadiran ?? [];
            $cls       = $att->kelas;

            if (!isset($kehadiranKelasMap[$cls])) {
                $kehadiranKelasMap[$cls] = ['hadir' => 0, 'total' => 0];
            }

            foreach ($rawDetail as $item) {
                $status = is_array($item) ? ($item['status'] ?? 'Hadir') : (string)$item;
                $statusLower = strtolower($status);

                $totalEntriKehadiran++;
                $kehadiranKelasMap[$cls]['total']++;

                if ($statusLower === 'hadir' || $statusLower === 'h') {
                    $totalHadir++;
                    $kehadiranKelasMap[$cls]['hadir']++;
                }
            }
        }

        $globalAttendanceRate = $totalEntriKehadiran > 0
            ? round(($totalHadir / $totalEntriKehadiran) * 100, 1)
            : 0;

        $kehadiranKelas = [];
        foreach ($kehadiranKelasMap as $cls => $data) {
            $kehadiranKelas[] = [
                'kelas' => $cls,
                'rate'  => $data['total'] > 0 ? round(($data['hadir'] / $data['total']) * 100, 1) : 0,
            ];
        }

        // Ambil data nilai
        $gradesQuery = Grade::query();
        if (!$user->isSuperAdmin()) {
            $gradesQuery->whereIn('kelas', $classes);
        }
        $grades = $gradesQuery->get();

        $totalNilaiSum = 0;
        $totalNilaiCount = 0;
        $nilaiKelasMap = [];

        foreach ($grades as $g) {
            $cls = $g->kelas;
            if (!isset($nilaiKelasMap[$cls])) {
                $nilaiKelasMap[$cls] = ['sum' => 0, 'count' => 0];
            }

            $scores = [$g->tugas_1, $g->tugas_2, $g->tugas_3, $g->pts, $g->pas, $g->praktik];
            foreach ($scores as $s) {
                if ($s !== null && $s !== '') {
                    $val = floatval($s);
                    $totalNilaiSum += $val;
                    $totalNilaiCount++;
                    $nilaiKelasMap[$cls]['sum'] += $val;
                    $nilaiKelasMap[$cls]['count']++;
                }
            }
        }

        $globalGradesAvg = $totalNilaiCount > 0
            ? number_format($totalNilaiSum / $totalNilaiCount, 1)
            : '0.0';

        $nilaiKelas = [];
        foreach ($nilaiKelasMap as $cls => $data) {
            $nilaiKelas[] = [
                'kelas font-bold' => $cls,
                'kelas'           => $cls,
                'avg'             => $data['count'] > 0 ? number_format($data['sum'] / $data['count'], 1) : '0.0',
            ];
        }

        $stats = [
            'totalSiswa'           => $totalSiswa,
            'totalKelas'           => $totalKelas,
            'globalAttendanceRate' => $globalAttendanceRate,
            'globalGradesAvg'      => $globalGradesAvg,
            'kehadiranKelas'       => $kehadiranKelas,
            'nilaiKelas'           => $nilaiKelas,
        ];

        return view('dashboard', compact('stats', 'classes'));
    }
}
