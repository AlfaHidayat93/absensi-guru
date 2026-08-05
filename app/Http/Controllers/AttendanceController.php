<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public const JAM_LIST = [
        'I'    => ['code' => 'I',    'num' => 1,  'label' => 'Jam I',    'mulai' => '07:15', 'selesai' => '07:55'],
        'II'   => ['code' => 'II',   'num' => 2,  'label' => 'Jam II',   'mulai' => '07:55', 'selesai' => '08:35'],
        'III'  => ['code' => 'III',  'num' => 3,  'label' => 'Jam III',  'mulai' => '08:35', 'selesai' => '09:15'],
        'IV'   => ['code' => 'IV',   'num' => 4,  'label' => 'Jam IV',   'mulai' => '09:15', 'selesai' => '09:55'],
        'V'    => ['code' => 'V',    'num' => 5,  'label' => 'Jam V',    'mulai' => '09:55', 'selesai' => '10:35'],
        'VI'   => ['code' => 'VI',   'num' => 6,  'label' => 'Jam VI',   'mulai' => '10:50', 'selesai' => '11:30'],
        'VII'  => ['code' => 'VII',  'num' => 7,  'label' => 'Jam VII',  'mulai' => '11:30', 'selesai' => '12:10'],
        'VIII' => ['code' => 'VIII', 'num' => 8,  'label' => 'Jam VIII', 'mulai' => '12:10', 'selesai' => '12:50'],
        'IX'   => ['code' => 'IX',   'num' => 9,  'label' => 'Jam IX',   'mulai' => '13:05', 'selesai' => '13:45'],
        'X'    => ['code' => 'X',    'num' => 10, 'label' => 'Jam X',    'mulai' => '13:45', 'selesai' => '14:20'],
    ];

    public function __construct(protected GoogleSheetService $gas) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Ambil seluruh kelas dari database lokal
        $allClasses = Student::select('kelas')
            ->distinct()
            ->whereNotNull('kelas')
            ->pluck('kelas')
            ->sort()
            ->values()
            ->all();

        // Filter kelas sesuai hak akses peranan user (Super Admin / Wali Kelas / Guru)
        $classes = $user->getAccessibleClasses($allClasses);

        // 2. Ambil seluruh mata pelajaran dari database lokal
        $allSubjects = Subject::pluck('name')->sort()->values()->all();
        $subjects    = $user->getAccessibleSubjects($allSubjects);

        // 3. Daftar Guru
        $teachers = User::where('status', 'verified')
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->all();

        $semesters = ['Ganjil', 'Genap'];

        $selectedClass      = $request->query('kelas', !empty($classes) ? $classes[0] : null);
        $selectedSemester   = $request->query('semester', 'Ganjil');
        $selectedDate       = $request->query('tanggal', date('Y-m-d'));
        $selectedSession    = $request->query('session');
        $selectedGuru       = $request->query('guru', $user->name);
        $selectedSubject    = $request->query('mata_pelajaran', !empty($subjects) ? $subjects[0] : '');
        $selectedJamMulai   = $request->query('jam_mulai', '07:30');
        $selectedJamSelesai = $request->query('jam_selesai', '09:00');

        if ($selectedSession === 'new') {
            $selectedSession = null;
        }

        $students        = [];
        $existingRecord  = null;
        $detailKehadiran = [];
        $matchingRecords = [];
        $rekapSiswa      = [];
        $totalPertemuan  = 0;

        // Cek izin akses ke kelas yang dipilih
        if ($selectedClass && !$user->canAccessClass($selectedClass) && !$user->isSuperAdmin()) {
            // Jika user mencoba mengakses kelas di luar wewenangnya, kembalikan ke kelas teratas miliknya
            $selectedClass = !empty($classes) ? $classes[0] : null;
        }

        if ($selectedClass) {
            // Ambil daftar siswa untuk kelas terpilih
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

            // Ambil sesi absensi pada tanggal tersebut
            $dbMatching = Attendance::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester)
                ->where('tanggal', $selectedDate)
                ->get();

            $matchingRecords = $dbMatching->map(function ($a) {
                return [
                    'id'                  => $a->id,
                    'ID_Absen'            => $a->id_absen,
                    'Kelas'               => $a->kelas,
                    'Semester'            => $a->semester,
                    'Tanggal'             => $a->tanggal->format('Y-m-d'),
                    'Jam_Mulai'           => $a->jam_mulai,
                    'Jam_Selesai'         => $a->jam_selesai,
                    'Mata_Pelajaran'      => $a->mata_pelajaran,
                    'Guru'                => $a->guru,
                    'Materi_Pembelajaran' => $a->materi_pembelajaran,
                    'Catatan_Kelas'       => $a->catatan_kelas,
                    'Detail_Kehadiran'    => is_array($a->detail_kehadiran) ? json_encode($a->detail_kehadiran) : $a->detail_kehadiran,
                ];
            })->all();

            if (!empty($matchingRecords)) {
                if ($selectedSession) {
                    $existingRecord = collect($matchingRecords)->first(fn ($a) => (string)$a['ID_Absen'] === (string)$selectedSession || (string)$a['id'] === (string)$selectedSession);
                } else {
                    $existingRecord = collect($matchingRecords)->first(fn ($a) =>
                        trim((string)($a['Jam_Mulai'] ?? '')) === trim((string)$selectedJamMulai) &&
                        trim((string)($a['Jam_Selesai'] ?? '')) === trim((string)$selectedJamSelesai)
                    );
                }
            }

            if ($existingRecord) {
                $rawDetail = $existingRecord['Detail_Kehadiran'];
                $detailKehadiran = is_array($rawDetail) ? $rawDetail : (json_decode($rawDetail, true) ?? []);

                if (empty($selectedSubject) && !empty($existingRecord['Mata_Pelajaran'])) {
                    $selectedSubject = $existingRecord['Mata_Pelajaran'];
                }
            }

            // Hitung Rekapitulasi Kehadiran & Keaktifan Kumulatif dari DB Lokal
            $queryAbsensi = Attendance::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester);

            if (!empty($selectedSubject)) {
                $queryAbsensi->where('mata_pelajaran', $selectedSubject);
            }

            $classAbsensiList = $queryAbsensi->get();
            $totalPertemuan   = $classAbsensiList->count();

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
                $rawDetail = $abs->detail_kehadiran ?? [];
                $tgl       = $abs->tanggal ? $abs->tanggal->format('d/m/Y') : '';
                $mapel     = $abs->mata_pelajaran ?? '';

                foreach ($rawDetail as $nis => $item) {
                    $nis = (string)$nis;
                    if (!isset($rekapSiswa[$nis])) {
                        continue;
                    }

                    $status    = 'Hadir';
                    $note      = '';
                    $keaktifan = 'normal';

                    if (is_array($item)) {
                        $status    = $item['status'] ?? 'Hadir';
                        $note      = $item['note'] ?? '';
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
                $stat['poin_sikap'] = $stat['bintang'] * 5;
            }
            unset($stat);

            // Ambil catatan & materi pertemuan sebelumnya untuk refleksi guru
            $previousRecord = Attendance::where('kelas', $selectedClass)
                ->where('semester', $selectedSemester)
                ->when(!empty($selectedSubject), fn($q) => $q->where('mata_pelajaran', $selectedSubject))
                ->where('tanggal', '<', $selectedDate)
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $previousRecord = null;
        }

        $jamList = self::JAM_LIST;

        return view('attendance', compact(
            'classes', 'semesters', 'subjects', 'students', 'existingRecord',
            'detailKehadiran', 'selectedClass', 'selectedSemester', 'selectedDate',
            'matchingRecords', 'selectedSession', 'selectedGuru', 'selectedSubject',
            'selectedJamMulai', 'selectedJamSelesai', 'teachers', 'rekapSiswa', 'totalPertemuan', 'jamList', 'previousRecord'
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
            $note      = trim($request->input("notes.$nis", ''));
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

        $idAbsen = $request->id_absen ?? ('ABS-' . date('Ymd-His', strtotime($request->tanggal . ' ' . $request->jam_mulai)));

        // Simpan ke Database Lokal 100% Instant
        Attendance::updateOrCreate(
            ['id_absen' => $idAbsen],
            [
                'kelas'               => $request->kelas,
                'semester'            => $request->semester,
                'tanggal'             => $request->tanggal,
                'jam_mulai'           => $request->jam_mulai,
                'jam_selesai'         => $request->jam_selesai,
                'mata_pelajaran'      => $request->mata_pelajaran ?? '',
                'guru'                => $request->guru,
                'guru_id'             => auth()->id(),
                'materi_pembelajaran' => $request->materi ?? '',
                'catatan_kelas'       => $request->catatan ?? '',
                'detail_kehadiran'    => $detail,
            ]
        );

        // Operasi Sinkronisasi Opsional ke Google Sheets (Tanpa Menghambat Aplikasi)
        try {
            $payload = [
                'id'            => $idAbsen,
                'id_absen'      => $idAbsen,
                'ID_Absen'      => $idAbsen,
                'kelas'         => $request->kelas,
                'semester'      => $request->semester,
                'tanggal'       => $request->tanggal,
                'jamMulai'      => $request->jam_mulai,
                'jamSelesai'    => $request->jam_selesai,
                'mataPelajaran' => $request->mata_pelajaran ?? '',
                'guru'          => $request->guru,
                'materi'        => $request->materi ?? '',
                'catatan'       => $request->catatan ?? '',
                'detail'        => $detail,
            ];
            $this->gas->saveAttendance($payload);
        } catch (\Throwable $e) {
            // Abaikan error Google Sheets agar user tetap mendapatkan respon instant
        }

        $redirectParams = [
            'kelas'          => $request->kelas,
            'semester'       => $request->semester,
            'tanggal'        => $request->tanggal,
            'guru'           => $request->guru,
            'mata_pelajaran' => $request->mata_pelajaran,
            'session'        => $idAbsen,
        ];

        return redirect()->route('attendance.index', $redirectParams)
            ->with('success', 'Data presensi berhasil disimpan ke database!');
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();

        $record = Attendance::findOrFail($id);

        // Hanya guru yang mengisi, wali kelas dari kelas tersebut, atau super admin yang bisa hapus
        $canDelete = $user->isSuperAdmin()
            || (int)$record->guru_id === (int)$user->id
            || ($user->isWaliKelas() && $user->canAccessClass($record->kelas))
            || $user->canAccessClass($record->kelas);

        if (!$canDelete) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus data absensi ini.');
        }

        $record->delete();

        return redirect()->route('attendance.index', [
            'kelas'    => $request->query('kelas', $record->kelas),
            'semester' => $request->query('semester', $record->semester),
            'tanggal'  => $request->query('tanggal', $record->tanggal?->format('Y-m-d')),
        ])->with('success', 'Data presensi sesi ' . ($record->mata_pelajaran ?? '') . ' (' . ($record->jam_mulai ?? '') . ' - ' . ($record->jam_selesai ?? '') . ') berhasil dihapus.');
    }
}

