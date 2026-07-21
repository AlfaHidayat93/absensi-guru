<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(protected GoogleSheetService $gas) {}

    public function index(Request $request)
    {
        // Try to get data from Google Sheets first, fallback to database
        $response = $this->gas->getInitialData();
        
        if ($response['success']) {
            $allSiswa = $response['data']['siswa'] ?? [];
        } else {
            // Fallback to local database
            $allSiswa = Student::all()->map(fn ($s) => [
                'Kelas' => $s->kelas,
                'NIS' => $s->nis,
                'Nama' => $s->nama,
                'id' => $s->id,
            ])->toArray();
        }
        
        $classes = collect($allSiswa)->pluck('Kelas')->filter()->unique()->sort()->values()->all();
        $selectedClass = $request->query('kelas', 'ALL');

        $siswa = $selectedClass === 'ALL'
            ? $allSiswa
            : collect($allSiswa)->filter(fn ($s) => $s['Kelas'] === $selectedClass)->values()->all();

        return view('students', compact('siswa', 'classes', 'selectedClass'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas' => 'required|string|max:20',
            'nis'   => 'required|string|max:20|unique:students,nis',
            'nama'  => 'required|string|max:100',
        ]);

        // Try to add to Google Sheets first
        $response = $this->gas->addStudent($validated);

        if ($response['success']) {
            return redirect()->route('students.index')->with('success', $response['message']);
        }

        // If Google Sheets fails, save to local database as fallback
        try {
            Student::create($validated);
            return redirect()->route('students.index')->with('success', 'Siswa berhasil ditambahkan (disimpan lokal)');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menambah siswa: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_impor_siswa.csv"',
        ];

        $content = "kelas,nis,nama\n";
        $content .= "X-A,2024001,Ahmad Ridwan\n";
        $content .= "X-A,2024002,Budi Santoso\n";
        $content .= "XI-IPA,2024003,Citra Dewi\n";

        return response($content, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:4096',
        ]);

        $file = $request->file('csv_file');
        $rawContent = file_get_contents($file->getRealPath());

        if (empty($rawContent)) {
            return back()->with('error', 'File CSV yang diunggah kosong.');
        }

        // Hapus UTF-8 BOM jika ada (sering ditemukan di CSV ekspor Windows/Excel)
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

        // Deteksi delimiter (; atau , atau tab)
        $firstLine = strtok($content, "\r\n");
        $countSemicolon = substr_count($firstLine, ';');
        $countComma     = substr_count($firstLine, ',');
        $countTab       = substr_count($firstLine, "\t");

        $delimiter = ',';
        if ($countSemicolon > $countComma && $countSemicolon > $countTab) {
            $delimiter = ';';
        } elseif ($countTab > $countComma && $countTab > $countSemicolon) {
            $delimiter = "\t";
        }

        // Baca baris CSV menggunakan fgetcsv dari stream memory
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rawHeaders = fgetcsv($stream, 0, $delimiter);
        if (!$rawHeaders) {
            fclose($stream);
            return back()->with('error', 'Header CSV tidak valid atau file kosong.');
        }

        // Bersihkan nama header (lowercase, buang karakter khusus)
        $headers = array_map(function ($h) {
            return preg_replace('/[^a-z0-9_]/', '', strtolower(trim($h)));
        }, $rawHeaders);

        // Cari posisi kolom
        $find = function (array $candidates) use ($headers) {
            foreach ($candidates as $cand) {
                $cleanCand = preg_replace('/[^a-z0-9_]/', '', strtolower($cand));
                $idx = array_search($cleanCand, $headers, true);
                if ($idx !== false) {
                    return $idx;
                }
            }
            return false;
        };

        $idxKelas = $find(['kelas', 'rombel', 'class']);
        $idxNis   = $find(['nis', 'no_induk', 'nomor_induk', 'id']);
        $idxNama  = $find(['nama', 'nama_siswa', 'namasiswa', 'name', 'nama siswa']);

        if ($idxKelas === false || $idxNis === false || $idxNama === false) {
            fclose($stream);
            $foundColumnsStr = implode(', ', array_filter($rawHeaders));
            return back()->with('error', 'Header CSV tidak cocok. Kolom yang dibaca: [' . $foundColumnsStr . ']. Wajib memiliki kolom: kelas, nis, nama.');
        }

        $rows = [];
        while (($cols = fgetcsv($stream, 0, $delimiter)) !== false) {
            if (count($cols) <= max($idxKelas, $idxNis, $idxNama)) {
                continue;
            }

            $kelas = trim($cols[$idxKelas]);
            $nis   = trim($cols[$idxNis]);
            $nama  = trim($cols[$idxNama]);

            if (!empty($kelas) && !empty($nis) && !empty($nama)) {
                $rows[] = [
                    'kelas' => $kelas,
                    'nis'   => (string)$nis,
                    'nama'  => $nama,
                ];
            }
        }
        fclose($stream);

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada data siswa yang valid dalam file CSV.');
        }

        // Try to import to Google Sheets first
        $response = $this->gas->importStudents($rows);

        if ($response['success']) {
            return redirect()->route('students.index')->with('success', $response['message']);
        }

        // If Google Sheets fails, save to local database as fallback
        try {
            foreach ($rows as $row) {
                Student::updateOrCreate(
                    ['nis' => $row['nis']],
                    $row
                );
            }
            return redirect()->route('students.index')->with('success', count($rows) . ' data siswa berhasil diimpor (disimpan lokal)');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor siswa: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $nis)
    {
        $validated = $request->validate([
            'kelas' => 'required|string|max:20',
            'nis'   => 'required|string|max:20',
            'nama'  => 'required|string|max:100',
        ]);

        $payload = array_merge($validated, ['old_nis' => $nis]);
        $response = $this->gas->editStudent($payload);

        if ($response['success']) {
            return back()->with('success', $response['message']);
        }
        
        // Fallback local db update
        try {
            $student = Student::where('nis', $nis)->first();
            if ($student) {
                $student->update($validated);
                return back()->with('success', 'Data siswa berhasil diubah (lokal)');
            }
            return back()->with('error', $response['message']);
        } catch (\Exception $e) {
            return back()->with('error', $response['message'] . ' | Error lokal: ' . $e->getMessage());
        }
    }

    public function destroy($nis)
    {
        $response = $this->gas->deleteStudent(['nis' => $nis]);
        
        if ($response['success']) {
            return back()->with('success', $response['message']);
        }

        // Fallback local db delete
        try {
            $student = Student::where('nis', $nis)->first();
            if ($student) {
                $student->delete();
                return back()->with('success', 'Data siswa berhasil dihapus (lokal)');
            }
            return back()->with('error', $response['message']);
        } catch (\Exception $e) {
            return back()->with('error', $response['message'] . ' | Error lokal: ' . $e->getMessage());
        }
    }
}
