<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Aplikasi Absensi Guru') — ClassManager</title>
  <meta name="description" content="Sistem Informasi Absensi dan Penilaian Siswa berbasis Google Sheets">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Tailwind CSS --}}
  <script src="https://cdn.tailwindcss.com"></script>
  {{-- FontAwesome --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  {{-- Google Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col lg:flex-row">

  {{-- ══════════════════════════ SIDEBAR ══════════════════════════ --}}
  <aside class="no-print w-full lg:w-64 bg-slate-900 text-slate-100 flex flex-col shrink-0 lg:min-h-screen">
    {{-- Brand --}}
    <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-3">
      <div class="p-2.5 bg-indigo-600 rounded-xl shadow-lg shadow-indigo-600/30 shrink-0">
        <i class="fa-solid fa-graduation-cap text-base text-white"></i>
      </div>
      <div class="min-w-0">
        <h1 class="font-extrabold text-sm leading-tight tracking-wide text-white truncate">ClassManager</h1>
        <p class="text-[10px] text-indigo-400 font-semibold uppercase tracking-widest">Absensi & Penilaian</p>
      </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-5 space-y-1">
      <a href="{{ route('dashboard') }}"
         class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
        <i class="fa-solid fa-chart-pie w-4 text-center"></i>
        <span>Dashboard</span>
      </a>

      <a href="{{ route('attendance.index') }}"
         class="nav-link {{ Route::is('attendance.*') ? 'active' : '' }}">
        <i class="fa-solid fa-calendar-check w-4 text-center"></i>
        <span>Absensi Kelas</span>
      </a>

      <a href="{{ route('grades.index') }}"
         class="nav-link {{ Route::is('grades.*') ? 'active' : '' }}">
        <i class="fa-solid fa-star-half-stroke w-4 text-center"></i>
        <span>Penilaian & Leger</span>
      </a>

      @if(auth()->user()->isSuperAdmin())
      <div class="pt-4">
        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest px-4 mb-2">Super Admin</p>
        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ Route::is('admin.users.*') ? 'active' : '' }}">
          <i class="fa-solid fa-users-gear w-4 text-center"></i>
          <span>Manajemen Akun</span>
        </a>
        <a href="{{ route('admin.verifications.index') }}"
           class="nav-link {{ Route::is('admin.verifications.*') ? 'active' : '' }}">
          <i class="fa-solid fa-user-check w-4 text-center"></i>
          <span>Verifikasi Guru</span>
        </a>
        <a href="{{ route('students.index') }}"
           class="nav-link {{ Route::is('students.*') ? 'active' : '' }}">
          <i class="fa-solid fa-user-group w-4 text-center"></i>
          <span>Data Siswa</span>
        </a>
        <a href="{{ route('admin.subjects.index') }}"
           class="nav-link {{ Route::is('admin.subjects.*') ? 'active' : '' }}">
          <i class="fa-solid fa-book-open w-4 text-center"></i>
          <span>Data Mata Pelajaran</span>
        </a>
      </div>
      @endif

      {{-- Divider --}}
      <div class="pt-4">
        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest px-4 mb-2">Terhubung ke</p>
        <div class="mx-4 flex items-center gap-2 bg-slate-800/60 rounded-xl p-3">
          <i class="fa-brands fa-google text-emerald-400 text-xs"></i>
          <span class="text-xs font-semibold text-slate-300 truncate">Google Sheets</span>
          <span class="ml-auto w-2 h-2 rounded-full bg-emerald-400 shrink-0 animate-pulse"></span>
        </div>
      </div>
    </nav>

    {{-- User Info & Logout --}}
    <div class="px-4 py-4 border-t border-slate-800">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 rounded-xl bg-indigo-600/80 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-user text-xs text-white"></i>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-bold text-slate-200 truncate">{{ auth()->user()->name }}</p>
          <p class="text-[10px] text-slate-500 font-semibold truncate">
            {{ auth()->user()->isSuperAdmin() ? 'Super Admin' : 'Guru' }}
          </p>
        </div>
      </div>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 transition-all duration-200">
          <i class="fa-solid fa-right-from-bracket"></i>
          Logout
        </button>
      </form>
    </div>
  </aside>

  {{-- ══════════════════════════ MAIN CONTENT ══════════════════════════ --}}
  <main class="flex-1 flex flex-col min-w-0">
    {{-- Top Header --}}
    <header class="no-print bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
      <h2 class="text-base font-bold text-slate-800">@yield('page-title', 'Dashboard')</h2>
      <div class="flex items-center gap-2">
        <a href="{{ url()->current() }}" class="btn btn-outline text-xs px-3 py-1.5">
          <i class="fa-solid fa-rotate text-indigo-500"></i>
          <span class="hidden sm:inline">Refresh Data</span>
        </a>
      </div>
    </header>

    {{-- Page Content --}}
    <div class="p-5 sm:p-6 flex-1 space-y-6">

      {{-- Flash Notifications --}}
      @if(session('success'))
        <div class="alert no-print bg-emerald-600 text-white">
          <i class="fa-solid fa-circle-check text-base"></i>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      @if(session('error'))
        <div class="alert no-print bg-rose-600 text-white">
          <i class="fa-solid fa-circle-xmark text-base"></i>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      @if(isset($error))
        <div class="alert no-print bg-amber-50 border border-amber-300 text-amber-800">
          <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
          <div>
            <p class="font-bold">Koneksi Database Bermasalah</p>
            <p class="text-xs font-normal mt-0.5">{{ $error }}</p>
          </div>
        </div>
      @endif

      @yield('content')
    </div>
  </main>

  @stack('scripts')
</body>
</html>
