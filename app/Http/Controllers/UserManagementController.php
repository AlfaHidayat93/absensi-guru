<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', auth()->id())->get();
        $allClasses = Student::select('kelas')->distinct()->whereNotNull('kelas')->pluck('kelas')->sort()->values()->all();
        $allSubjects = Subject::pluck('name')->sort()->values()->all();

        return view('admin.users', compact('users', 'allClasses', 'allSubjects'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin' && $user->id === 1) {
            return back()->with('error', 'Tidak dapat mengubah data Super Admin utama.');
        }

        $validated = $request->validate([
            'role'              => 'required|in:super_admin,wali_kelas,guru',
            'homeroom_class'    => 'nullable|string|max:20',
            'assigned_classes'  => 'nullable|array',
            'assigned_subjects' => 'nullable|array',
        ]);

        $user->update([
            'role'              => $validated['role'],
            'homeroom_class'    => $validated['role'] === 'wali_kelas' ? ($validated['homeroom_class'] ?? null) : null,
            'assigned_classes'  => $validated['assigned_classes'] ?? [],
            'assigned_subjects' => $validated['assigned_subjects'] ?? [],
        ]);

        return back()->with('success', 'Hak akses dan peranan ' . $user->name . ' berhasil diperbarui.');
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);

        if ($user->role === 'super_admin' && $user->id === 1) {
            return back()->with('error', 'Tidak dapat mereset password Super Admin utama.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password akun ' . $user->name . ' berhasil direset.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin' && $user->id === 1) {
            return back()->with('error', 'Tidak dapat menghapus Super Admin utama.');
        }

        $user->delete();

        return back()->with('success', 'Akun ' . $user->name . ' berhasil dihapus dari sistem lokal.');
    }

    public function runUpdate()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');

            return back()->with('success', 'Database hosting, struktur tabel, dan seeder akun demo berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui database: ' . $e->getMessage());
        }
    }
}
