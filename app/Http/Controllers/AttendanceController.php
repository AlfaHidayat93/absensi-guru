<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected const SUBJECT_LIST = [
        'Bahasa Indonesia',
        'Matematika',
        'IPA',
        'IPS',
        'Bahasa Inggris',
        'PKn',
        'PJOK',
        'Seni Budaya',
        'Prakarya',
        'Agama',
        'TIK',
    ];

    protected const TEACHER_LIST = [
        'Pak Agus',
        'Ibu Sari',
        'Pak Budi',
        'Ibu Lina',
        'Pak Joko',
    ];

    public function __construct(protected GoogleSheetService $gas) {}

    public function index(Request $request)
    {
        $response = $this->gas->getInitialData();
        $teachers = self::TEACHER_LIST;
        
        if ($response['success']) {
            $data = $response['data'] ?? [];
            $allSiswa   = $data['siswa']   ?? [];
            $allAbsensi = $data['absensi'] ?? [];
            $config     = $data['config']  ?? [];
        } else {
            // Fallback to local database for students
            $allSiswa = \App\Models\Student::all()->map(fn ($s) => [
                'Kelas' => $s->kelas,
                'NIS'   => $s->nis,
                'Nama'  => $s->nama,
                'id'    => $s->id,
            ])->toArray();
            $allAbsensi = [];
            $config     = [];
        }

        $classes          = collect($allSiswa)->pluck('Kelas')->filter()->unique()->sort()->values()->all();
        $semesters        = $config['SEMESTER_LIST'] ?? ['Ganjil', 'Genap'];
        $subjects         = collect($data['mapel'] ?? $config['SUBJECT_LIST'] ?? self::SUBJECT_LIST)
            ->map(fn ($item) => is_array($item) ? ($item['Mata_Pelajaran'] ?? $item['name'] ?? '') : (string)$item)
            ->filter()
            ->values()
            ->all();
        $teachers         = collect($data['guru'] ?? self::TEACHER_LIST)
            ->map(fn ($item) => is_array($item) ? ($item['Nama_Guru'] ?? $item['name'] ?? '') : (string)$item)
            ->filter()
            ->values()
            ->all();
        $selectedClass     = $request->query('kelas', !empty($classes) ? $classes[0] : null);
        $selectedSemester  = $request->query('semester', $config['DEFAULT_SEMESTER'] ?? 'Ganjil');
        $selectedDate      = $request->query('tanggal', date('Y-m-d'));
        $selectedSession   = $request->query('session');
        $selectedGuru      = $request->query('guru', '');
        $selectedSubject   = $request->query('mata_pelajaran', '');
        $selectedJamMulai  = $request->query('jam_mulai', '07:30');
        $selectedJamSelesai= $request->query('jam_selesai', '09:00');

        if ($selectedSession === 'new') {
            $selectedSession = null;
        }

        $students         = [];
        $existingRecord   = null;
        $detailKehadiran  = [];
        $matchingRecords  = [];
        $rekapSiswa       = [];
        $totalPertemuan   = 0;

        if ($selectedClass) {
            $students = collect($allSiswa)
                ->filter(fn ($s) => $s['Kelas'] === $selectedClass)
                ->values()->all();

            $matchingRecords = collect($allAbsensi)
                ->filter(fn ($a) => $a['Kelas'] === $selectedClass
                    && $a['Semester'] === $selectedSemester
                    && $a['Tanggal'] === $selectedDate)
                ->values()->all();

            $existingRecord = null;
            if (!empty($matchingRecords)) {
                if ($selectedSession) {
                    $existingRecord = collect($matchingRecords)->first(fn ($a) =>
                        (string)($a['ID_Absen'] ?? $a['id'] ?? '') === (string)$selectedSession
                    );
                } else {
                    $existingRecord = collect($matchingRecords)->first(fn ($a) =>
                        trim((string)($a['Jam_Mulai'] ?? '')) === trim((string)$selectedJamMulai) &&
                        trim((string)($a['Jam_Selesai'] ?? '')) === trim((string)$selectedJamSelesai) &&
                        trim((string)($a['Guru'] ?? '')) === trim((string)$selectedGuru)
                    );
                }
            }

            if ($existingRecord) {
                $detailKehadiran = json_decode($existingRecord['Detail_Kehadiran'] ?? '{}', true) ?? [];
                if (empty($selectedSubject) && !empty($existingRecord['Mata_Pelajaran'])) {
                    $selectedSubject = $existingRecord['Mata_Pelajaran'];
                }
                
                try {
                    if (!empty($existingRecord['Jam_Mulai'])) {
                        $existingRecord['Jam_Mulai'] = \Illuminate\Support\Carbon::parse($existingRecord['Jam_Mulai'])->format('H:i');
                    }
                    if (!empty($existingRecord['Jam_Selesai'])) {
                        $existingRecord['Jam_Selesai'] = \Illuminate\Support\Carbon::parse($existingRecord['Jam_Selesai'])->format('H:i');
                    }
                } catch (\Exception $e) {
                    if (isset($existingRecord['Jam_Mulai'])) {
                        $existingRecord['Jam_Mulai'] = substr($existingRecord['Jam_Mulai'], 0, 5);
                    }
                    if (isset($existingRecord['Jam_Selesai'])) {
                        $existingRecord['Jam_Selesai'] = substr($existingRecord['Jam_Selesai'], 0, 5);
                    }
                }
            }

            // Hitung Rekapitulasi Kehadiran & Keaktifan untuk kelas & semester (serta mapel jika dipilih)
            $classAbsensiList = collect($allAbsensi)
                ->filter(function ($a) use ($selectedClass, $selectedSemester, $selectedSubject) {
                    if ($a['Kelas'] !== $selectedClass || ($a['Semester'] ?? 'Ganjil') !== $selectedSemester) {
                        return false;
                    }
                    if (!empty($selectedSubject)) {
                        return ($a['Mata_Pelajaran'] ?? '') === $selectedSubject;
                    }
                    return true;
                })
                ->values();

            $totalPertemuan = $classAbsensiList->count();

            foreach ($students as $st) {
                $nis = (string)$st['NIS'];
                $rekapSiswa[$nis] = [
                    'hadir'      => 0,
                    'sakit'      => 0,
                    'izin'       => 0,
                    'alpa'       => 0,
                    'total'      => 0,
                    'persentase' => 0,
                    'bintang'    => 0,
                    'peringatan' => 0,
                    'catatan'    => [],
                ];
            }

            foreach ($classAbsensiList as $abs) {
                $rawDetail = json_decode($abs['Detail_Kehadiran'] ?? '{}', true) ?? [];
                $tgl = $abs['Tanggal'] ?? '';
                $mapel = $abs['Mata_Pelajaran'] ?? '';

                foreach ($rawDetail as $nis => $item) {
                    $nis = (string)$nis;
                    if (!isset($rekapSiswa[$nis])) {
                        continue;
                    }

                    $status = 'Hadir';
                    $note = '';
                    $keaktifan = 'normal';

                    if (is_array($item)) {
                        $status = $item['status'] ?? 'Hadir';
                        $note = $item['note'] ?? '';
                        $keaktifan = $item['keaktifan'] ?? 'normal';
                    } else {
                        $status = (string)$item;
                    }

                    $rekapSiswa[$nis]['total']++;

                    $statusLower = strtolower($status);
                    if ($statusLower === 'hadir' || $statusLower === 'h') {
                        $rekapSiswa[$nis]['hadir']++;
                    } elseif ($statusLower === 'sakit' || $statusLower === 's') {
                        $rekapSiswa[$nis]['sakit']++;
                    } elseif ($statusLower === 'izin' || $statusLower === 'i') {
                        $rekapSiswa[$nis]['izin']++;
                    } elseif ($statusLower === 'alpa' || $statusLower === 'a') {
                        $rekapSiswa[$nis]['alpa']++;
                    }

                    if ($keaktifan === 'aktif' || $keaktifan === 'bintang') {
                        $rekapSiswa[$nis]['bintang']++;
                    } elseif ($keaktifan === 'tidak_aktif' || $keaktifan === 'peringatan') {
                        $rekapSiswa[$nis]['peringatan']++;
                    }

                    if (!empty($note) || $keaktifan !== 'normal') {
                        $rekapSiswa[$nis]['catatan'][] = [
                            'tanggal'   => $tgl,
                            'mapel'     => $mapel,
                            'note'      => $note,
                            'keaktifan' => $keaktifan,
                            'status'    => $status,
                        ];
                    }
                }
            }

            foreach ($rekapSiswa as $nis => &$stat) {
                $stat['persentase'] = $stat['total'] > 0 ? round(($stat['hadir'] / $stat['total']) * 100) : 0;
            }
            unset($stat);
        }

        return view('attendance', compact(
            'classes', 'semesters', 'subjects', 'students', 'existingRecord',
            'detailKehadiran', 'selectedClass', 'selectedSemester', 'selectedDate',
            'matchingRecords', 'selectedSession', 'selectedGuru', 'selectedSubject',
            'selectedJamMulai', 'selectedJamSelesai', 'teachers', 'rekapSiswa', 'totalPertemuan'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kelas'          => 'required|string',
            'semester'       => 'required|string',
            'tanggal'        => 'required|date',
            'jam_mulai'      => 'required',
            'jam_selesai'    => 'required',
            'mata_pelajaran' => 'nullable|string',
            'guru'           => 'required|string',
            'status'         => 'required|array',
            'notes'          => 'nullable|array',
            'keaktifan'      => 'nullable|array',
        ]);

        $detail = [];
        foreach ($request->status as $nis => $st) {
            $note = trim($request->input("notes.$nis", ''));
            $keaktifan = $request->input("keaktifan.$nis", 'normal');

            if (!empty($note) || $keaktifan !== 'normal') {
                $detail[$nis] = [
                    'status'    => $st,
                    'note'      => $note,
                    'keaktifan' => $keaktifan,
                ];
            } else {
                $detail[$nis] = $st;
            }
        }

        $payload = [
            'kelas'         => $request->kelas,
            'semester'      => $request->semester,
            'tanggal'       => $request->tanggal,
            'jamMulai'      => $request->jam_mulai,
            'jamSelesai'    => $request->jam_selesai,
            'mataPelajaran' => $request->mata_pelajaran ?? '',
            'guru'          => $request->guru ?? '',
            'materi'        => $request->materi ?? '',
            'catatan'       => $request->catatan ?? '',
            'detail'        => $detail,
        ];

        if ($request->filled('id_absen')) {
            $payload['id']       = $request->id_absen;
            $payload['id_absen'] = $request->id_absen;
            $payload['ID_Absen'] = $request->id_absen;
        }

        $response = $this->gas->saveAttendance($payload);

        $redirectParams = [
            'kelas'          => $request->kelas,
            'semester'       => $request->semester,
            'tanggal'        => $request->tanggal,
            'guru'           => $request->guru,
            'mata_pelajaran' => $request->mata_pelajaran,
        ];

        if ($request->filled('session')) {
            $redirectParams['session'] = $request->session;
        }

        $redirect = redirect()->route('attendance.index', $redirectParams);

        return $response['success']
            ? $redirect->with('success', $response['message'])
            : $redirect->with('error',   $response['message']);
    }
}
