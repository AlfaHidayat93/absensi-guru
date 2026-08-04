<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>@yield('title', 'Aplikasi Absensi Guru') — ClassManager</title>
  <meta name="description" content="Sistem Informasi Absensi dan Penilaian Siswa">
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
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col lg:flex-row antialiased">

  {{-- Overlay Mobile Sidebar Backdrop --}}
  <div id="sidebarBackdrop" onclick="closeSidebar()" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm hidden lg:hidden transition-opacity"></div>

  {{-- ══════════════════════════ SIDEBAR (Mobile Offcanvas & Desktop Fixed) ══════════════════════════ --}}
  <aside id="mainSidebar" class="no-print fixed lg:static inset-y-0 left-0 z-50 w-72 lg:w-64 bg-slate-900 text-slate-100 flex flex-col shrink-0 min-h-screen transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
    
    {{-- Brand & Close Button for Mobile --}}
    <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="p-2.5 bg-indigo-600 rounded-xl shadow-lg shadow-indigo-600/30 shrink-0">
          <i class="fa-solid fa-graduation-cap text-base text-white"></i>
        </div>
        <div class="min-w-0">
          <h1 class="font-extrabold text-sm leading-tight tracking-wide text-white truncate">ClassManager</h1>
          <p class="text-[10px] text-indigo-400 font-semibold uppercase tracking-widest">Absensi & Penilaian</p>
        </div>
      </div>
      <button type="button" onclick="closeSidebar()" class="lg:hidden text-slate-400 hover:text-white p-2 text-lg">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-5 space-y-1 overflow-y-auto">
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
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">Super Admin</p>
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

      {{-- Role Info Badge --}}
      <div class="pt-4">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">Peran Login</p>
        <div class="mx-4 flex items-center gap-2 bg-slate-800/80 rounded-xl p-3 border border-slate-700/50">
          <i class="fa-solid fa-shield-halved text-indigo-400 text-sm"></i>
          <div class="min-w-0">
            <span class="text-xs font-bold text-slate-200 block truncate">{{ auth()->user()->role_label }}</span>
            @if(auth()->user()->isWaliKelas() && auth()->user()->homeroom_class)
              <span class="text-[10px] text-amber-400 font-semibold block">Wali {{ auth()->user()->homeroom_class }}</span>
            @endif
          </div>
        </div>
      </div>
    </nav>

    {{-- User Info & Logout --}}
    <div class="px-4 py-4 border-t border-slate-800 bg-slate-950/40">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0 font-bold text-white text-xs">
          {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-bold text-slate-200 truncate">{{ auth()->user()->name }}</p>
          <p class="text-[10px] text-slate-400 font-semibold truncate">{{ auth()->user()->email }}</p>
        </div>
      </div>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 transition-all">
          <i class="fa-solid fa-right-from-bracket"></i>
          Logout Akun
        </button>
      </form>
    </div>
  </aside>

  {{-- ══════════════════════════ MAIN CONTENT ══════════════════════════ --}}
  <main class="flex-1 flex flex-col min-w-0 w-full">
    {{-- Top Responsive Header --}}
    <header class="no-print bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 flex items-center justify-between sticky top-0 z-30 shadow-sm">
      <div class="flex items-center gap-3">
        {{-- Hamburger Button for Mobile & Pad --}}
        <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">
          <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <h2 class="text-sm sm:text-base font-bold text-slate-800 truncate">@yield('page-title', 'Dashboard')</h2>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ url()->current() }}" class="btn btn-outline text-xs px-3 py-2">
          <i class="fa-solid fa-rotate text-indigo-500"></i>
          <span class="hidden md:inline">Refresh</span>
        </a>
      </div>
    </header>

    {{-- Page Content Container --}}
    <div class="p-3 sm:p-5 lg:p-6 flex-1 space-y-5 sm:space-y-6">

      {{-- Flash Notifications --}}
      @if(session('success'))
        <div class="alert no-print bg-emerald-600 text-white shadow-md">
          <i class="fa-solid fa-circle-check text-base"></i>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      @if(session('error'))
        <div class="alert no-print bg-rose-600 text-white shadow-md">
          <i class="fa-solid fa-circle-xmark text-base"></i>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      @yield('content')
    </div>
  </main>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('mainSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      const isHidden = sidebar.classList.contains('-translate-x-full');

      if (isHidden) {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
      } else {
        closeSidebar();
      }
    }

    function closeSidebar() {
      const sidebar = document.getElementById('mainSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      sidebar.classList.add('-translate-x-full');
      backdrop.classList.add('hidden');
    }
  </script>

  @stack('scripts')
</body>
</html>
