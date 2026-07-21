<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            
            // Cek status verifikasi (Super Admin default verified, Guru perlu diverifikasi)
            if ($user->status === 'pending') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withInput($request->only('email'))
                    ->with('error', 'Akun Anda sedang menunggu verifikasi oleh Super Admin.');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'Email atau password salah. Silakan coba lagi.');
    }

    public function showSignup()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('signup');
    }

    public function processSignup(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required', 
                'email', 
                'max:255', 
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (!str_ends_with(strtolower($value), '@guru.smk.belajar.id')) {
                        $fail('Email harus menggunakan domain @guru.smk.belajar.id');
                    }
                },
            ],
            'password' => 'required|string|min:6|confirmed',
            'nip'      => 'nullable|string|max:50',
            'subjects' => 'required|string', // comma separated list
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role'     => 'guru',
            'nip'      => $request->nip,
            'subjects' => $request->subjects,
            'status'   => 'pending',
        ]);

        return redirect()->route('login')->with('success', 'Pendaftaran berhasil! Akun Anda sedang menunggu verifikasi oleh Super Admin.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}
