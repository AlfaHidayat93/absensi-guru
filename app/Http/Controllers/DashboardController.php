<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;

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
        if (!$user->isSuperAdmin() && !empty($classes)) {
            $studentsQuery->whereIn('kelas', $classes);
        }

        $students   = $studentsQuery->get();
        $totalSiswa = $students->count();
        $totalKelas = count($classes);

        // Ambil data absensi sesuai kelas yang diizinkan
        $attendancesQuery = Attendance::query();
        if (!$user->isSuperAdmin() && !empty($classes)) {
            $attendancesQuery->whereIn('kelas', $classes);
        }
        $attendances = $attendancesQuery->get();

        $totalHadir        = 0;
        $totalEntriKehadiran = 0;
        $kehadiranKelasMap = [];

        foreach ($attendances as $att) {
            $rawDetail = $att->detail_kehadiran ?? [];
            $cls       = $att->kelas;

            if (!isset($kehadiranKelasMap[$cls])) {
                $kehadiranKelasMap[$cls] = ['hadir' => 0, 'total' => 0];
            }

            foreach ($rawDetail as $item) {
                $status      = is_array($item) ? ($item['status'] ?? 'Hadir') : (string)$item;
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
        usort($kehadiranKelas, fn($a, $b) => $b['rate'] <=> $a['rate']);

        // Ambil data nilai
        $gradesQuery = Grade::query();
        if (!$user->isSuperAdmin() && !empty($classes)) {
            $gradesQuery->whereIn('kelas', $classes);
        }
        $grades = $gradesQuery->get();

        $totalNilaiSum   = 0;
        $totalNilaiCount = 0;
        $nilaiKelasMap   = [];

        foreach ($grades as $g) {
            $cls = $g->kelas;
            if (!isset($nilaiKelasMap[$cls])) {
                $nilaiKelasMap[$cls] = ['sum' => 0, 'count' => 0];
            }

            // Gabungkan nilai lama & task_scores baru
            $legacyScores = [$g->tugas_1, $g->tugas_2, $g->tugas_3, $g->pts, $g->pas, $g->praktik];
            $taskScores   = is_array($g->task_scores) ? array_values($g->task_scores) : [];
            $allScores    = !empty($taskScores) ? array_merge($taskScores, [$g->pts, $g->pas, $g->praktik]) : $legacyScores;

            foreach ($allScores as $s) {
                if ($s !== null && $s !== '') {
                    $val = floatval($s);
                    $totalNilaiSum += $val;
                    $totalNilaiCount++;
                    $nilaiKelasMap[$cls]['sum']   += $val;
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
                'kelas' => $cls,
                'avg'   => $data['count'] > 0 ? number_format($data['sum'] / $data['count'], 1) : '0.0',
            ];
        }
        usort($nilaiKelas, fn($a, $b) => floatval($b['avg']) <=> floatval($a['avg']));

        // ──────────────────────────────────────────────────────────────────────
        // DASHBOARD KHUSUS WALI KELAS — rekapitulasi per siswa per mapel
        // ──────────────────────────────────────────────────────────────────────
        $waliKelasRekap = null;

        if ($user->isWaliKelas() && $user->homeroom_class) {
            $homeroomClass = trim(preg_replace('/^Wali\s+/i', '', $user->homeroom_class));

            // Cari kelas yang cocok di DB (fleksibel terhadap tanda hubung & spasi)
            $normTarget = strtolower(str_replace(['-', ' ', '_'], '', $homeroomClass));
            $realClass  = $allClasses[0] ?? $homeroomClass;
            foreach ($allClasses as $ac) {
                if (strtolower(str_replace(['-', ' ', '_'], '', $ac)) === $normTarget) {
                    $realClass = $ac;
                    break;
                }
            }

            // Siswa di kelas binaan
            $siswaBinaan = Student::where('kelas', $realClass)
                ->orderBy('nama', 'asc')
                ->get();

            // Semua mapel
            $allSubjects = Subject::pluck('name')->sort()->values()->all();

            // Rekap absensi per siswa (semua mapel, semua semester)
            $allAttendances = Attendance::where('kelas', $realClass)->get();
            $rekapAbsensi   = []; // [nis => [hadir, sakit, izin, alpa, bintang, peringatan]]
            $rekapMapel     = []; // [nis => [mapel => [hadir, total]]]

            foreach ($siswaBinaan as $s) {
                $nis = (string)$s->nis;
                $rekapAbsensi[$nis] = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'total' => 0, 'bintang' => 0, 'peringatan' => 0];
            }

            foreach ($allAttendances as $att) {
                $mapel     = $att->mata_pelajaran ?? '-';
                $rawDetail = $att->detail_kehadiran ?? [];

                foreach ($rawDetail as $nis => $item) {
                    $nis = (string)$nis;
                    if (!isset($rekapAbsensi[$nis])) continue;

                    $status    = is_array($item) ? ($item['status'] ?? 'Hadir') : (string)$item;
                    $keaktifan = is_array($item) ? ($item['keaktifan'] ?? 'normal') : 'normal';
                    $stLow     = strtolower($status);

                    $rekapAbsensi[$nis]['total']++;
                    if ($stLow === 'hadir' || $stLow === 'h')       $rekapAbsensi[$nis]['hadir']++;
                    elseif ($stLow === 'sakit' || $stLow === 's')   $rekapAbsensi[$nis]['sakit']++;
                    elseif ($stLow === 'izin' || $stLow === 'i')    $rekapAbsensi[$nis]['izin']++;
                    elseif ($stLow === 'alpa' || $stLow === 'a')    $rekapAbsensi[$nis]['alpa']++;

                    if ($keaktifan === 'aktif' || $keaktifan === 'bintang')               $rekapAbsensi[$nis]['bintang']++;
                    elseif ($keaktifan === 'tidak_aktif' || $keaktifan === 'peringatan')  $rekapAbsensi[$nis]['peringatan']++;

                    // Per mapel
                    if (!isset($rekapMapel[$nis][$mapel])) {
                        $rekapMapel[$nis][$mapel] = ['hadir' => 0, 'total' => 0, 'bintang' => 0];
                    }
                    $rekapMapel[$nis][$mapel]['total']++;
                    if ($stLow === 'hadir' || $stLow === 'h') $rekapMapel[$nis][$mapel]['hadir']++;
                    if ($keaktifan === 'aktif' || $keaktifan === 'bintang') $rekapMapel[$nis][$mapel]['bintang']++;
                }
            }

            // Rekap nilai per siswa (semua mapel)
            $allGrades   = Grade::where('kelas', $realClass)->get();
            $rekapNilai  = []; // [nis => [mapel => avg]]

            foreach ($allGrades as $g) {
                $nis   = (string)$g->nis;
                $mapel = $g->mata_pelajaran;

                $taskScores   = is_array($g->task_scores) ? array_values($g->task_scores) : [];
                $legacyScores = [$g->tugas_1, $g->tugas_2, $g->tugas_3];
                $baseScores   = !empty($taskScores) ? $taskScores : $legacyScores;

                $allScores  = array_merge($baseScores, [$g->pts, $g->pas, $g->praktik]);
                $valid      = array_filter($allScores, fn($v) => $v !== null && $v !== '');
                $avg        = !empty($valid) ? round(array_sum($valid) / count($valid), 1) : null;
                $poinSikap  = $g->poin_sikap ?? 0;
                $finalScore = $avg !== null ? min(100, $avg + $poinSikap) : null;

                $rekapNilai[$nis][$mapel] = [
                    'avg'        => $avg,
                    'poin_sikap' => $poinSikap,
                    'final'      => $finalScore,
                ];
            }

            $waliKelasRekap = [
                'kelas'      => $realClass,
                'siswa'      => $siswaBinaan,
                'mapel_list' => $allSubjects,
                'absensi'    => $rekapAbsensi,
                'mapel'      => $rekapMapel,
                'nilai'      => $rekapNilai,
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

        return view('dashboard', compact('stats', 'classes', 'waliKelasRekap'));
    }
}
