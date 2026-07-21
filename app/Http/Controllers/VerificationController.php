<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    protected $sheetService;

    public function __construct(GoogleSheetService $sheetService)
    {
        $this->sheetService = $sheetService;
    }

    public function index()
    {
        // Ambil data guru yang berstatus pending (belum diverifikasi)
        $pendingUsers = User::where('role', 'guru')->where('status', 'pending')->get();
        return view('admin.verifications', compact('pendingUsers'));
    }

    public function verify(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->status !== 'pending') {
            return back()->with('error', 'User ini sudah diverifikasi sebelumnya.');
        }

        // Siapkan data untuk dikirim ke Google Sheets
        $dataToGoogle = [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'nip'      => $user->nip ?? '',
            'subjects' => $user->subjects ?? '',
        ];

        // 1. Kirim data ke Google Sheets melalui API
        $response = $this->sheetService->addVerifiedUser($dataToGoogle);

        if (isset($response['success']) && $response['success']) {
            // 2. Jika berhasil disimpan di spreadsheet, ubah status di local DB
            $user->status = 'verified';
            $user->save();

            return back()->with('success', 'Guru ' . $user->name . ' berhasil diverifikasi dan data telah dikirim ke Spreadsheet.');
        }

        $errorMessage = $response['message'] ?? 'Gagal menghubungi server database (Google Sheets).';
        Log::error('Verifikasi gagal: ' . $errorMessage, ['user_id' => $user->id]);
        return back()->with('error', 'Verifikasi gagal: ' . $errorMessage);
    }
}
