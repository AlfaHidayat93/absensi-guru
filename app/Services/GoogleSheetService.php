<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GoogleSheetService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.gas.url', env('GAS_API_URL', ''));
    }

    /**
     * GET request ke Apps Script API
     */
    protected function get(string $action, array $params = []): array
    {
        try {
            $response = Http::withOptions([
                'allow_redirects' => true,
                'verify'          => false,  // Shared hosting terkadang punya SSL issue
                'timeout'         => 30,
            ])->get($this->apiUrl, array_merge(['action' => $action], $params));

            if ($response->successful()) {
                return $response->json() ?? ['success' => false, 'message' => 'Response kosong dari Apps Script.'];
            }

            return ['success' => false, 'message' => 'HTTP Error: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("[GoogleSheetService] GET `{$action}` error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke database gagal. Cek GAS_API_URL di .env'];
        }
    }

    /**
     * POST request ke Apps Script API
     */
    protected function post(string $action, mixed $data = []): array
    {
        try {
            $response = Http::withOptions([
                'allow_redirects' => true,
                'verify'          => false,
                'timeout'         => 30,
            ])->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'action' => $action,
                'data'   => $data,
            ]);

            if ($response->successful()) {
                return $response->json() ?? ['success' => false, 'message' => 'Response kosong dari Apps Script.'];
            }

            return ['success' => false, 'message' => 'HTTP Error: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("[GoogleSheetService] POST `{$action}` error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke database gagal. Cek GAS_API_URL di .env'];
        }
    }

    /**
     * Ambil seluruh data awal (dengan cache 2 menit agar tidak terlalu sering hit API)
     */
    public function getInitialData(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('gas_initial_data');
        }

        return Cache::remember('gas_initial_data', 120, fn () => $this->get('getInitialData'));
    }

    /**
     * Statistik Dashboard
     */
    public function getDashboardStats(): array
    {
        return $this->get('getDashboardStats');
    }

    /**
     * Tambah siswa baru
     */
    public function addStudent(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('addStudent', $data);
    }

    /**
     * Import massal siswa dari CSV
     */
    public function importStudents(array $rows): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('importStudents', $rows);
    }

    /**
     * Edit data siswa
     */
    public function editStudent(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('editStudent', $data);
    }

    /**
     * Hapus siswa
     */
    public function deleteStudent(string $nis): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('deleteStudent', ['nis' => $nis]);
    }

    /**
     * Tambah guru baru
     */
    public function addTeacher(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('addTeacher', $data);
    }

    /**
     * Edit data guru
     */
    public function editTeacher(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('editTeacher', $data);
    }

    /**
     * Hapus guru
     */
    public function deleteTeacher(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('deleteTeacher', $data);
    }

    /**
     * Tambah mata pelajaran baru
     */
    public function addSubject(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('addSubject', $data);
    }

    /**
     * Edit data mata pelajaran
     */
    public function editSubject(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('editSubject', $data);
    }

    /**
     * Hapus mata pelajaran
     */
    public function deleteSubject(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('deleteSubject', $data);
    }

    /**
     * Simpan data absensi kelas
     */
    public function saveAttendance(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('saveAttendance', $data);
    }

    /**
     * Simpan nilai akademik
     */
    public function saveGrades(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('saveGrades', $data);
    }

    /**
     * Tambah user terverifikasi ke sheet
     */
    public function addVerifiedUser(array $data): array
    {
        Cache::forget('gas_initial_data');
        return $this->post('addUser', $data);
    }
}
