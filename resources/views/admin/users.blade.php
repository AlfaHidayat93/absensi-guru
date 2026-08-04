@extends('layouts.app')

@section('title', 'Manajemen Akun & Hak Akses')
@section('page-title', 'Manajemen Akun & Hak Akses User')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
  <div class="p-6 border-b border-slate-100 flex items-center justify-between">
    <div>
      <h3 class="font-bold text-slate-800 text-lg">Daftar Akun & Peranan Pengguna</h3>
      <p class="text-sm text-slate-500 mt-1">Kelola peran (Super Admin, Wali Kelas, Guru Mapel) dan tentukan hak akses kelas/mata pelajaran.</p>
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
          <th class="px-6 py-4">Nama Lengkap & NIP</th>
          <th class="px-6 py-4">Email</th>
          <th class="px-6 py-4">Peran (Role)</th>
          <th class="px-6 py-4">Kelas Binaan</th>
          <th class="px-6 py-4">Hak Akses Mapel & Kelas</th>
          <th class="px-6 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($users as $index => $user)
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-medium text-slate-900">{{ $index + 1 }}</td>
            <td class="px-6 py-4 font-bold text-slate-800">
              <div>{{ $user->name }}</div>
              @if($user->nip)
                <div class="text-xs font-normal text-slate-400">NIP: {{ $user->nip }}</div>
              @endif
            </td>
            <td class="px-6 py-4">{{ $user->email }}</td>
            <td class="px-6 py-4">
              @if($user->role === 'super_admin')
                <span class="px-2.5 py-1 bg-purple-50 text-purple-700 rounded-lg text-xs font-bold uppercase tracking-wider">Super Admin</span>
              @elseif($user->role === 'wali_kelas')
                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold uppercase tracking-wider">Wali Kelas</span>
              @else
                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold uppercase tracking-wider">Guru Mapel</span>
              @endif
            </td>
            <td class="px-6 py-4">
              @if($user->isWaliKelas() && $user->homeroom_class)
                <span class="px-3 py-1 bg-amber-100 text-amber-800 font-bold rounded-full text-xs">
                  🏫 {{ $user->homeroom_class }}
                </span>
              @else
                <span class="text-slate-400 text-xs">-</span>
              @endif
            </td>
            <td class="px-6 py-4 text-xs space-y-1">
              @if($user->isSuperAdmin())
                <span class="text-purple-600 font-semibold">Akses Penuh Seluruh Sistem</span>
              @else
                <div>
                  <span class="font-bold text-slate-700">Kelas:</span>
                  @if(!empty($user->assigned_classes))
                    <span class="text-slate-600">{{ implode(', ', $user->assigned_classes) }}</span>
                  @else
                    <span class="text-slate-400 italic">Belum diatur</span>
                  @endif
                </div>
                <div>
                  <span class="font-bold text-slate-700">Mapel:</span>
                  @if(!empty($user->assigned_subjects))
                    <span class="text-slate-600">{{ implode(', ', $user->assigned_subjects) }}</span>
                  @else
                    <span class="text-slate-400 italic">Belum diatur</span>
                  @endif
                </div>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                {{-- Tombol Edit Role & Akses --}}
                <button type="button"
                        onclick="openEditModal({{ json_encode($user) }})"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors tooltip"
                        title="Edit Peran & Hak Akses">
                  <i class="fa-solid fa-user-gear"></i>
                </button>

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
                          title="Hapus Akun">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
              <p class="font-semibold">Belum ada user lain terdaftar.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Modal Edit Peran & Hak Akses --}}
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden animate-slide-up my-8">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
      <div>
        <h3 class="font-bold text-lg text-slate-800">Edit Peran & Hak Akses</h3>
        <p class="text-xs text-slate-500" id="editUserNameDisplay">User</p>
      </div>
      <button onclick="closeEditModal()" class="text-slate-400 hover:text-rose-500 transition-colors">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>
    <form id="editForm" method="POST" action="">
      @csrf
      @method('PUT')
      <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
        
        {{-- Peran / Role --}}
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Peran Pengguna (Role)</label>
          <select name="role" id="editRole" onchange="toggleRoleFields()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="guru">Guru Mata Pelajaran</option>
            <option value="wali_kelas">Wali Kelas</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>

        {{-- Kelas Binaan Wali Kelas --}}
        <div id="homeroomContainer" class="hidden bg-amber-50/60 p-4 rounded-xl border border-amber-200">
          <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-2">🏫 Kelas Binaan Wali Kelas</label>
          <input type="text" name="homeroom_class" id="editHomeroomClass" placeholder="Contoh: X-PH 5" class="w-full px-4 py-2.5 rounded-xl border border-amber-300 bg-white text-slate-800 focus:outline-none focus:border-amber-500">
          <p class="text-xs text-amber-700 mt-1">Wali Kelas memiliki akses otomatis ke data kumulatif & presensi kelas binaannya.</p>
        </div>

        {{-- Kelas yang Diampu --}}
        <div id="classesContainer">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Kelas yang Diampu</label>
          <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200 max-h-40 overflow-y-auto">
            @foreach($allClasses as $cls)
              <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer text-xs font-medium text-slate-700">
                <input type="checkbox" name="assigned_classes[]" value="{{ $cls }}" class="editClassCheckbox rounded text-indigo-600 focus:ring-indigo-500">
                <span>{{ $cls }}</span>
              </label>
            @endforeach
          </div>
        </div>

        {{-- Mapel yang Diampu --}}
        <div id="subjectsContainer">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Mata Pelajaran yang Diampu</label>
          <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200 max-h-40 overflow-y-auto">
            @foreach($allSubjects as $sbj)
              <label class="flex items-center gap-2 p-2 hover:bg-white rounded-lg cursor-pointer text-xs font-medium text-slate-700">
                <input type="checkbox" name="assigned_subjects[]" value="{{ $sbj }}" class="editSubjectCheckbox rounded text-indigo-600 focus:ring-indigo-500">
                <span>{{ $sbj }}</span>
              </label>
            @endforeach
          </div>
        </div>

      </div>
      <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
        <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">Batal</button>
        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-200 transition-all">Simpan Perubahan</button>
      </div>
    </form>
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
  function openEditModal(user) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editUserNameDisplay').innerText = user.name + ' (' + user.email + ')';
    document.getElementById('editForm').action = '/admin/users/' + user.id;

    document.getElementById('editRole').value = user.role;
    document.getElementById('editHomeroomClass').value = user.homeroom_class || '';

    const assignedClasses = user.assigned_classes || [];
    document.querySelectorAll('.editClassCheckbox').forEach(cb => {
      cb.checked = assignedClasses.includes(cb.value);
    });

    const assignedSubjects = user.assigned_subjects || [];
    document.querySelectorAll('.editSubjectCheckbox').forEach(cb => {
      cb.checked = assignedSubjects.includes(cb.value);
    });

    toggleRoleFields();
  }

  function toggleRoleFields() {
    const role = document.getElementById('editRole').value;
    const homeroomContainer = document.getElementById('homeroomContainer');
    const classesContainer = document.getElementById('classesContainer');
    const subjectsContainer = document.getElementById('subjectsContainer');

    if (role === 'wali_kelas') {
      homeroomContainer.classList.remove('hidden');
      classesContainer.classList.remove('hidden');
      subjectsContainer.classList.remove('hidden');
    } else if (role === 'guru') {
      homeroomContainer.classList.add('hidden');
      classesContainer.classList.remove('hidden');
      subjectsContainer.classList.remove('hidden');
    } else { // super_admin
      homeroomContainer.classList.add('hidden');
      classesContainer.classList.add('hidden');
      subjectsContainer.classList.add('hidden');
    }
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
  }

  function openResetModal(userId, userName) {
    document.getElementById('resetModal').classList.remove('hidden');
    document.getElementById('resetUserName').innerText = userName;
    document.getElementById('resetForm').action = `/admin/users/${userId}/reset-password`;
  }

  function closeResetModal() {
    document.getElementById('resetModal').classList.add('hidden');
    document.getElementById('resetForm').reset();
  }
</script>
@endsection
