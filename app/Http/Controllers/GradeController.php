<?php

namespace App\Http\Controllers;

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
        $response = $this->gas->getInitialData();
        $data     = $response['data'] ?? [];

        $allSiswa  = $data['siswa']  ?? [];
        $allGrades = $data['nilai']  ?? [];
        $config    = $data['config'] ?? [];

        $classes          = collect($allSiswa)->pluck('Kelas')->filter()->unique()->sort()->values()->all();
        $semesters        = $config['SEMESTER_LIST'] ?? ['Ganjil', 'Genap'];
        $mode             = $request->query('mode', 'input');
        $selectedClass    = $request->query('kelas');
        $selectedSemester = $request->query('semester', $config['DEFAULT_SEMESTER'] ?? 'Ganjil');
        $selectedType     = $request->query('jenis', 'Tugas_1');
        $gradeTypes       = self::GRADE_TYPES;

        $students = [];
        $grades   = [];

        if ($selectedClass) {
            $students = collect($allSiswa)
                ->filter(fn ($s) => $s['Kelas'] === $selectedClass)
                ->values()->all();

            $grades = collect($allGrades)
                ->filter(fn ($g) => $g['Kelas'] === $selectedClass && $g['Semester'] === $selectedSemester)
                ->keyBy('NIS')
                ->all();
        }

        return view('grades', compact(
            'classes', 'semesters', 'students', 'grades', 'mode', 'gradeTypes',
            'selectedClass', 'selectedSemester', 'selectedType'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kelas'    => 'required|string',
            'semester' => 'required|string',
            'jenis'    => 'required|string|in:' . implode(',', array_keys(self::GRADE_TYPES)),
            'scores'   => 'required|array',
        ]);

        $payload = [
            'kelas'    => $request->kelas,
            'semester' => $request->semester,
            'type'     => $request->jenis,
            'grades'   => $request->scores,
        ];

        $response = $this->gas->saveGrades($payload);

        $redirect = redirect()->route('grades.index', [
            'mode'     => 'input',
            'kelas'    => $request->kelas,
            'semester' => $request->semester,
            'jenis'    => $request->jenis,
        ]);

        return $response['success']
            ? $redirect->with('success', $response['message'])
            : $redirect->with('error',   $response['message']);
    }
}
