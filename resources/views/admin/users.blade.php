@extends('layouts.app')

@section('title', 'Manajemen Akun')
@section('page-title', 'Manajemen Akun (Lokal)')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
  <div class="p-6 border-b border-slate-100 flex items-center justify-between">
    <div>
      <h3 class="font-bold text-slate-800 text-lg">Daftar Akun Website</h3>
      <p class="text-sm text-slate-500 mt-1">Daftar user yang memiliki akses login ke website ini.</p>
    </div>
    <div class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-sm">
      Total: {{ $users->count() }} Akun
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm text-slate-600">
      <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
        <tr>
          <th class="px-6 py-4">No</th>
          <th class="px-6 py-4">Nama Lengkap</th>
          <th class="px-6 py-4">Email</th>
          <th class="px-6 py-4">Peran (Role)</th>
          <th class="px-6 py-4">Status</th>
          <th class="px-6 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($users as $index => $user)
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-medium text-slate-900">{{ $index + 1 }}</td>
            <td class="px-6 py-4 font-bold text-slate-800">{{ $user->name }}</td>
            <td class="px-6 py-4">{{ $user->email }}</td>
            <td class="px-6 py-4">
              @if($user->role === 'super_admin')
                <span class="px-2.5 py-1 bg-purple-50 text-purple-600 rounded-lg text-xs font-bold uppercase tracking-wider">Super Admin</span>
              @else
                <span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold uppercase tracking-wider">Guru</span>
              @endif
            </td>
            <td class="px-6 py-4">
              @if($user->status === 'verified')
                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-bold uppercase tracking-wider">Verified</span>
              @else
                <span class="px-2.5 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-bold uppercase tracking-wider">Pending</span>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                {{-- Tombol Reset Password --}}
                <button type="button" 
                        onclick="openResetModal('{{ $user->id }}', '{{ $user->name }}')"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors tooltip"
                        title="Reset Password">
                  <i class="fa-solid fa-key"></i>
                </button>
                
                {{-- Tombol Hapus Akun --}}
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Peringatan: Aksi ini akan menghapus akun secara permanen dari website ini. Lanjutkan?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" 
                          class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors tooltip"
                          title="Hapus Akun Lokal">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
              <p class="font-semibold">Belum ada user lain terdaftar.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Modal Reset Password --}}
<div id="resetModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-slide-up">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
      <h3 class="font-bold text-lg text-slate-800">Reset Password</h3>
      <button onclick="closeResetModal()" class="text-slate-400 hover:text-rose-500 transition-colors">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>
    <form id="resetForm" method="POST" action="">
      @csrf
      <div class="p-6 space-y-4">
        <p class="text-sm text-slate-500">Masukkan password baru untuk <span id="resetUserName" class="font-bold text-indigo-600">User</span>.</p>
        
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Password Baru</label>
          <input type="password" name="password" required minlength="6"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ulangi Password</label>
          <input type="password" name="password_confirmation" required minlength="6"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:border-indigo-500 focus:bg-white transition-colors">
        </div>
      </div>
      <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
        <button type="button" onclick="closeResetModal()" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">Batal</button>
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-200 transition-all">Reset Sekarang</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openResetModal(userId, userName) {
    document.getElementById('resetModal').classList.remove('hidden');
    document.getElementById('resetUserName').innerText = userName;
    
    // Set form action dinamis
    const form = document.getElementById('resetForm');
    form.action = `/admin/users/${userId}/reset-password`;
  }

  function closeResetModal() {
    document.getElementById('resetModal').classList.add('hidden');
    document.getElementById('resetForm').reset();
  }
</script>
@endsection
