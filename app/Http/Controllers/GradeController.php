<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradeSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function __construct(protected GoogleSheetService $gas) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        // Ambil kelas & mapel dari database lokal dengan saringan peranan user
        $allClasses = Student::select('kelas')
            ->distinct()
            ->whereNotNull('kelas')
            ->pluck('kelas')
            ->sort()
            ->values()
            ->all();

        $classes = $user->getAccessibleClasses($allClasses);

        $allSubjects = Subject::pluck('name')->sort()->values()->all();
        $subjects    = $user->getAccessibleSubjects($allSubjects);

        $semesters        = ['Ganjil', 'Genap'];
        $mode             = $request->query('mode', 'input');
        $selectedClass    = $request->query('kelas', !empty($classes) ? $classes[0] : null);
        $selectedSemester = $request->query('semester', 'Ganjil');
        $selectedSubject  = $request->query('mata_pelajaran', !empty($subjects) ? $subjects[0] : 'Umum');

        // Ambil Pengaturan Tugas untuk (kelas, semester, mata_pelajaran)
        $taskSetting = null;
        $taskColumns = GradeSetting::defaultTasks();

        if ($selectedClass) {
            $taskSetting = GradeSetting::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester)
                ->where('mata_pelajaran', $selectedSubject)
                ->first();

            if ($taskSetting && !empty($taskSetting->tasks)) {
                $taskColumns = $taskSetting->tasks;
            }
        }

        // Susun jenis penilaian dinamis
        $gradeTypes = [];
        foreach ($taskColumns as $t) {
            $gradeTypes[$t['id']] = $t['name'];
        }
        $gradeTypes['PTS']        = 'PTS (Penilaian Tengah Semester)';
        $gradeTypes['PAS']        = 'PAS (Penilaian Akhir Semester)';
        $gradeTypes['Praktik']    = 'Praktik / Portofolio';
        $gradeTypes['Poin_Sikap'] = 'Poin Sikap (Bonus Keaktifan Absensi)';

        $firstTypeKey = array_key_first($gradeTypes);
        $selectedType = $request->query('jenis', $firstTypeKey);

        if (!array_key_exists($selectedType, $gradeTypes)) {
            $selectedType = $firstTypeKey;
        }

        $students = [];
        $grades   = [];

        if ($selectedClass) {
            $students = Student::where('kelas', $selectedClass)
                ->orderBy('nama', 'asc')
                ->get()
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'NIS'   => (string)$s->nis,
                    'Nama'  => $s->nama,
                    'Kelas' => $s->kelas,
                ])
                ->all();

            // Hitung Otomatis Poin Sikap dari Bintang Keaktifan Absensi
            $attendances = Attendance::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester)
                ->when(!empty($selectedSubject), fn($q) => $q->where('mata_pelajaran', $selectedSubject))
                ->get();

            $bintangMap = [];
            foreach ($attendances as $att) {
                $detail = is_array($att->detail_kehadiran) ? $att->detail_kehadiran : (json_decode($att->detail_kehadiran, true) ?? []);
                foreach ($detail as $nis => $item) {
                    $nis = (string)$nis;
                    $keaktifan = is_array($item) ? ($item['keaktifan'] ?? 'normal') : 'normal';
                    if ($keaktifan === 'aktif' || $keaktifan === 'bintang') {
                        $bintangMap[$nis] = ($bintangMap[$nis] ?? 0) + 1;
                    }
                }
            }

            $dbGrades = Grade::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester)
                ->where('mata_pelajaran', $selectedSubject)
                ->get();

            foreach ($dbGrades as $g) {
                $nisStr = (string)$g->nis;
                $tScores = is_array($g->task_scores) ? $g->task_scores : (json_decode($g->task_scores, true) ?? []);

                // Map legacy columns if task_scores is empty
                if (empty($tScores)) {
                    $tScores = [
                        'tugas_1' => $g->tugas_1,
                        'tugas_2' => $g->tugas_2,
                        'tugas_3' => $g->tugas_3,
                    ];
                }

                $grades[$nisStr] = array_merge([
                    'NIS'            => $nisStr,
                    'Kelas'          => $g->kelas,
                    'Semester'       => $g->semester,
                    'Mata_Pelajaran' => $g->mata_pelajaran,
                    'PTS'            => $g->pts,
                    'PAS'            => $g->pas,
                    'Praktik'        => $g->praktik,
                    'Poin_Sikap'     => $g->poin_sikap !== null ? $g->poin_sikap : (($bintangMap[$nisStr] ?? 0) * 5),
                ], $tScores);
            }

            // Untuk siswa yang belum punya record grade, berikan Poin Sikap default dari bintang absensi
            foreach ($students as $st) {
                $nisStr = (string)$st['NIS'];
                if (!isset($grades[$nisStr])) {
                    $grades[$nisStr] = [
                        'NIS'            => $nisStr,
                        'Kelas'          => $selectedClass,
                        'Semester'       => $selectedSemester,
                        'Mata_Pelajaran' => $selectedSubject,
                        'PTS'            => null,
                        'PAS'            => null,
                        'Praktik'        => null,
                        'Poin_Sikap'     => ($bintangMap[$nisStr] ?? 0) * 5,
                    ];
                }
            }
        }

        return view('grades', compact(
            'classes', 'semesters', 'subjects', 'students', 'grades', 'mode', 'gradeTypes',
            'selectedClass', 'selectedSemester', 'selectedSubject', 'selectedType', 'taskColumns'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kelas'          => 'required|string',
            'semester'       => 'required|string',
            'mata_pelajaran' => 'required|string',
            'jenis'          => 'required|string',
            'scores'         => 'required|array',
        ]);

        $jenis = $request->jenis;

        foreach ($request->scores as $nis => $score) {
            $nisStr = (string)$nis;
            $val = ($score !== null && $score !== '') ? floatval($score) : null;

            $grade = Grade::firstOrNew([
                'nis'            => $nisStr,
                'kelas'          => $request->kelas,
                'semester'       => $request->semester,
                'mata_pelajaran' => $request->mata_pelajaran,
            ]);

            if ($jenis === 'PTS') {
                $grade->pts = $val;
            } elseif ($jenis === 'PAS') {
                $grade->pas = $val;
            } elseif ($jenis === 'Praktik') {
                $grade->praktik = $val;
            } elseif ($jenis === 'Poin_Sikap') {
                $grade->poin_sikap = $val;
            } else {
                // Merupakan jenis tugas dinamis (tugas_1, tugas_2, tugas_custom, dst)
                $currentTaskScores = is_array($grade->task_scores) ? $grade->task_scores : (json_decode($grade->task_scores, true) ?? []);
                if ($val !== null) {
                    $currentTaskScores[$jenis] = $val;
                } else {
                    unset($currentTaskScores[$jenis]);
                }
                $grade->task_scores = $currentTaskScores;

                // Sync ke kolom legacy jika tugas_1, tugas_2, atau tugas_3
                if ($jenis === 'tugas_1') $grade->tugas_1 = $val;
                if ($jenis === 'tugas_2') $grade->tugas_2 = $val;
                if ($jenis === 'tugas_3') $grade->tugas_3 = $val;
            }

            $grade->save();
        }

        // Simpan opsional ke Google Sheets di background
        try {
            $payload = [
                'kelas'    => $request->kelas,
                'semester' => $request->semester,
                'type'     => $request->jenis,
                'grades'   => $request->scores,
            ];
            $this->gas->saveGrades($payload);
        } catch (\Throwable $e) {
            // Abaikan error Google Sheets
        }

        return redirect()->route('grades.index', [
            'mode'           => 'input',
            'kelas'          => $request->kelas,
            'semester'       => $request->semester,
            'mata_pelajaran' => $request->mata_pelajaran,
            'jenis'          => $request->jenis,
        ])->with('success', 'Nilai siswa berhasil disimpan!');
    }

    public function updateTasks(Request $request)
    {
        $request->validate([
            'kelas'          => 'required|string',
            'semester'       => 'required|string',
            'mata_pelajaran' => 'required|string',
            'tasks'          => 'required|array',
            'tasks.*.id'     => 'required|string',
            'tasks.*.name'   => 'required|string',
        ]);

        $tasks = [];
        foreach ($request->tasks as $t) {
            $tasks[] = [
                'id'   => trim($t['id']),
                'name' => trim($t['name']),
            ];
        }

        GradeSetting::updateOrCreate(
            [
                'kelas'          => $request->kelas,
                'semester'       => $request->semester,
                'mata_pelajaran' => $request->mata_pelajaran,
            ],
            [
                'tasks' => $tasks,
            ]
        );

        return redirect()->route('grades.index', [
            'mode'           => $request->query('mode', 'input'),
            'kelas'          => $request->kelas,
            'semester'       => $request->semester,
            'mata_pelajaran' => $request->mata_pelajaran,
        ])->with('success', 'Daftar & Nama Tugas berhasil diperbarui!');
    }
}
