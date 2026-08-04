<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    const GRADE_TYPES = [
        'Tugas_1' => 'Tugas 1',
        'Tugas_2' => 'Tugas 2',
        'Tugas_3' => 'Tugas 3',
        'PTS'     => 'PTS (Penilaian Tengah Semester)',
        'PAS'     => 'PAS (Penilaian Akhir Semester)',
        'Praktik' => 'Praktik / Portofolio',
    ];

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
        $selectedType     = $request->query('jenis', 'Tugas_1');
        $gradeTypes       = self::GRADE_TYPES;

        $students = [];
        $grades   = [];

        if ($selectedClass) {
            $students = Student::where('kelas', $selectedClass)
                ->orderBy('nama', 'asc')
                ->get()
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'NIS'   => $s->nis,
                    'Nama'  => $s->nama,
                    'Kelas' => $s->kelas,
                ])
                ->all();

            $dbGrades = Grade::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester)
                ->where('mata_pelajaran', $selectedSubject)
                ->get();

            foreach ($dbGrades as $g) {
                $grades[$g->nis] = [
                    'NIS'            => $g->nis,
                    'Kelas'          => $g->kelas,
                    'Semester'       => $g->semester,
                    'Mata_Pelajaran' => $g->mata_pelajaran,
                    'Tugas_1'        => $g->tugas_1,
                    'Tugas_2'        => $g->tugas_2,
                    'Tugas_3'        => $g->tugas_3,
                    'PTS'            => $g->pts,
                    'PAS'            => $g->pas,
                    'Praktik'        => $g->praktik,
                ];
            }
        }

        return view('grades', compact(
            'classes', 'semesters', 'subjects', 'students', 'grades', 'mode', 'gradeTypes',
            'selectedClass', 'selectedSemester', 'selectedSubject', 'selectedType'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kelas'          => 'required|string',
            'semester'       => 'required|string',
            'mata_pelajaran' => 'required|string',
            'jenis'          => 'required|string|in:' . implode(',', array_keys(self::GRADE_TYPES)),
            'scores'         => 'required|array',
        ]);

        $typeColumn = strtolower($request->jenis);

        foreach ($request->scores as $nis => $score) {
            if ($score === null || $score === '') {
                continue;
            }

            $val = floatval($score);

            Grade::updateOrCreate(
                [
                    'nis'            => (string)$nis,
                    'kelas'          => $request->kelas,
                    'semester'       => $request->semester,
                    'mata_pelajaran' => $request->mata_pelajaran,
                ],
                [
                    $typeColumn => $val,
                ]
            );
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

        $redirect = redirect()->route('grades.index', [
            'mode'           => 'input',
            'kelas'          => $request->kelas,
            'semester'       => $request->semester,
            'mata_pelajaran' => $request->mata_pelajaran,
            'jenis'          => $request->jenis,
        ]);

        return $redirect->with('success', 'Nilai siswa berhasil disimpan ke database!');
    }
}
