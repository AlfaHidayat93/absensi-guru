@extends('layouts.app')

@section('title', 'Data Mata Pelajaran')
@section('page-title', 'Manajemen Mata Pelajaran')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-book-open text-indigo-500"></i> Tambah Mata Pelajaran
      </h3>
    </div>
    <div class="card-body space-y-4">
      <form action="{{ route('admin.subjects.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
          <label class="form-label">Nama Mata Pelajaran</label>
          <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Matematika" required
                 class="form-input @error('name') border-rose-400 @enderror">
          @error('name') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center">
          <i class="fa-solid fa-plus"></i> Tambahkan Mata Pelajaran
        </button>
      </form>
    </div>
  </div>

  <div class="card lg:col-span-2">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-list-check text-indigo-500"></i> Daftar Mata Pelajaran
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th class="w-14">No.</th>
            <th>Mata Pelajaran</th>
            <th class="text-right w-32">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($subjects as $idx => $subject)
            <tr>
              <td class="text-slate-400 text-xs">{{ $idx + 1 }}</td>
              <td class="font-semibold">{{ $subject['name'] }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <button type="button" onclick="openEditModal('{{ $subject['id'] }}', '{{ $subject['name'] }}')"
                          class="btn btn-outline text-xs px-2 py-1 text-blue-600 border-blue-200 hover:bg-blue-50">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <form action="{{ route('admin.subjects.destroy', $subject['id']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus mata pelajaran ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline text-xs px-2 py-1 text-rose-600 border-rose-200 hover:bg-rose-50">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="text-center py-12 text-slate-400 italic">Belum ada data mata pelajaran.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden border border-slate-200">
    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
      <h3 class="font-bold text-slate-700"><i class="fa-solid fa-pen-to-square text-indigo-500 mr-2"></i> Edit Mata Pelajaran</h3>
      <button onclick="closeEditModal()" class="text-slate-400 hover:text-rose-500 transition-colors">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>
    <form id="editForm" method="POST" action="" class="p-6 space-y-4">
      @csrf
      @method('PUT')
      <div>
        <label class="form-label">Nama Mata Pelajaran</label>
        <input type="text" name="name" id="edit_name" required class="form-input">
      </div>
      <div class="pt-4 flex justify-end gap-3">
        <button type="button" onclick="closeEditModal()" class="btn btn-outline">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openEditModal(id, name) {
    document.getElementById('edit_name').value = name;
    document.getElementById('editForm').action = '/admin/subjects/' + encodeURIComponent(id);
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
  }
</script>

@endsection
