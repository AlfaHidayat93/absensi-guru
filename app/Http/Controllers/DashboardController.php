<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected GoogleSheetService $gas) {}

    public function index()
    {
        $response = $this->gas->getInitialData();

        if (! ($response['success'] ?? false)) {
            return view('dashboard', [
                'error'   => $response['message'] ?? 'Gagal terhubung ke Google Sheets.',
                'stats'   => $this->emptyStats(),
                'classes' => [],
            ]);
        }

        $data    = $response['data'];
        $stats   = $data['stats'];
        $classes = collect($data['siswa'])->pluck('Kelas')->filter()->unique()->sort()->values()->all();

        return view('dashboard', compact('stats', 'classes'));
    }

    private function emptyStats(): array
    {
        return [
            'totalSiswa'           => 0,
            'totalKelas'           => 0,
            'globalAttendanceRate' => 0,
            'globalGradesAvg'      => '0.0',
            'kehadiranKelas'       => [],
            'nilaiKelas'           => [],
        ];
    }
}
