<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ClassManager</title>
  <meta name="description" content="Login ke Sistem Informasi Absensi dan Penilaian Siswa">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Tailwind CSS --}}
  <script src="https://cdn.tailwindcss.com"></script>
  {{-- FontAwesome --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  {{-- Google Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    * { font-family: 'Plus Jakarta Sans', sans-serif; }

    .login-bg {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #312e81 70%, #4338ca 100%);
      min-height: 100vh;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .login-input {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #f1f5f9;
      transition: all 0.3s ease;
    }

    .login-input::placeholder {
      color: rgba(148, 163, 184, 0.7);
    }

    .login-input:focus {
      outline: none;
      border-color: #818cf8;
      background: rgba(255, 255, 255, 0.12);
      box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2);
    }

    .btn-login {
      background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }

    .btn-login:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 25px rgba(99, 102, 241, 0.5);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .floating-shape {
      position: absolute;
      border-radius: 50%;
      background: rgba(99, 102, 241, 0.08);
      animation: float 8s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(5deg); }
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .animate-slide-up {
      animation: slideUp 0.6s ease-out forwards;
    }

    .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
    .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
    .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
  </style>
</head>
<body class="login-bg relative overflow-hidden">

  {{-- Floating decorative shapes --}}
  <div class="floating-shape w-72 h-72 -top-20 -right-20" style="animation-delay: 0s;"></div>
  <div class="floating-shape w-96 h-96 -bottom-32 -left-32" style="animation-delay: 3s;"></div>
  <div class="floating-shape w-48 h-48 top-1/3 right-1/4" style="animation-delay: 5s;"></div>

  <div class="flex items-center justify-center min-h-screen px-4 py-8 relative z-10">
    <div class="w-full max-w-md">

      {{-- Logo & Brand --}}
      <div class="text-center mb-8 animate-slide-up">
        <div class="inline-flex items-center justify-center p-4 bg-indigo-600 rounded-2xl shadow-2xl shadow-indigo-600/30 mb-4">
          <i class="fa-solid fa-graduation-cap text-3xl text-white"></i>
        </div>
        <h1 class="text-2xl font-extrabold text-white tracking-wide">ClassManager</h1>
        <p class="text-xs text-indigo-300 font-semibold uppercase tracking-[0.2em] mt-1">Sistem Absensi & Penilaian</p>
      </div>

      {{-- Login Card --}}
      <div class="glass-card rounded-3xl p-8 shadow-2xl animate-slide-up animate-delay-1">

        <div class="text-center mb-6">
          <h2 class="text-lg font-bold text-white">Selamat Datang</h2>
          <p class="text-sm text-slate-400 mt-1">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
          <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-sm font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
          </div>
        @endif

        @if(session('error'))
          <div class="mb-4 px-4 py-3 rounded-xl bg-rose-500/20 border border-rose-500/30 text-rose-300 text-sm font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark"></i>
            <span>{{ session('error') }}</span>
          </div>
        @endif

        <form action="{{ route('login.process') }}" method="POST" class="space-y-5">
          @csrf

          {{-- Email --}}
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
              <i class="fa-solid fa-envelope mr-1 text-indigo-400"></i> Alamat Email
            </label>
            <input type="email" name="email" value="{{ old('email') }}"
                   placeholder="contoh@sekolah.sch.id" required autofocus
                   class="login-input w-full px-4 py-3 rounded-xl text-sm font-medium">
            @error('email')
              <p class="text-rose-400 text-xs mt-1.5 font-semibold">{{ $message }}</p>
            @enderror
          </div>

          {{-- Password --}}
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
              <i class="fa-solid fa-lock mr-1 text-indigo-400"></i> Password
            </label>
            <div class="relative">
              <input type="password" name="password" id="passwordInput"
                     placeholder="••••••••" required
                     class="login-input w-full px-4 py-3 rounded-xl text-sm font-medium pr-12">
              <button type="button" onclick="togglePassword()"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-indigo-400 transition-colors p-1">
                <i class="fa-solid fa-eye" id="toggleIcon"></i>
              </button>
            </div>
            @error('password')
              <p class="text-rose-400 text-xs mt-1.5 font-semibold">{{ $message }}</p>
            @enderror
          </div>

          {{-- Remember Me --}}
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer select-none group">
              <input type="checkbox" name="remember"
                     class="w-4 h-4 rounded border-slate-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 bg-transparent">
              <span class="text-xs text-slate-400 font-semibold group-hover:text-slate-300 transition-colors">Ingat saya</span>
            </label>
          </div>

          {{-- Submit --}}
          <button type="submit"
                  class="btn-login w-full py-3.5 rounded-xl text-white font-bold text-sm uppercase tracking-wider flex items-center justify-center gap-2">
            <i class="fa-solid fa-right-to-bracket"></i>
            Masuk ke Aplikasi
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-sm text-slate-400">
            Belum punya akun? 
            <a href="{{ route('signup') }}" class="text-indigo-400 font-bold hover:text-indigo-300 transition-colors">Daftar sebagai Guru</a>
          </p>
        </div>
      </div>

      {{-- Footer --}}
      <div class="text-center mt-6 animate-slide-up animate-delay-3">
        <p class="text-xs text-slate-500 font-semibold">
          <i class="fa-brands fa-google text-xs mr-1"></i>
          Database: Google Sheets via Apps Script API
        </p>
      </div>

    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('passwordInput');
      const icon = document.getElementById('toggleIcon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>
