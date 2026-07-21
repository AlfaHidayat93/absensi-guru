<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index()
    {
        // Tampilkan semua user kecuali super admin sendiri
        $users = User::where('id', '!=', auth()->id())->get();
        return view('admin.users', compact('users'));
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);
        
        // Proteksi: jangan sampai menghapus/mereset admin utama secara tidak sengaja
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
}
