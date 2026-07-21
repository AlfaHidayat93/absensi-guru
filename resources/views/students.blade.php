@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Manajemen Data Siswa')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

  {{-- ── FORM TAMBAH SISWA ─────────────────────────────────────────────── --}}
  <div class="card">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-user-plus text-indigo-500"></i> Registrasi Siswa Baru
      </h3>
    </div>
    <div class="card-body space-y-4">
      <form action="{{ route('students.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
          <label class="form-label">Kelas / Rombongan Belajar</label>
          <input type="text" name="kelas" value="{{ old('kelas') }}" placeholder="Contoh: X-A, XI-IPA, XII-3" required
                 class="form-input @error('kelas') border-rose-400 @enderror">
          @error('kelas') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="form-label">NIS (Nomor Induk Siswa)</label>
          <input type="text" name="nis" value="{{ old('nis') }}" placeholder="Masukkan NIS unik siswa" required
                 class="form-input @error('nis') border-rose-400 @enderror">
          @error('nis') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="form-label">Nama Lengkap Siswa</label>
          <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Masukkan nama lengkap" required
                 class="form-input @error('nama') border-rose-400 @enderror">
          @error('nama') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center">
          <i class="fa-solid fa-plus"></i> Tambahkan Siswa
        </button>
      </form>
    </div>
  </div>

  {{-- ── IMPOR CSV ─────────────────────────────────────────────────────── --}}
  <div class="card lg:col-span-2">
    <div class="card-header flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-file-import text-indigo-500"></i> Impor Massal Data Siswa (CSV)
      </h3>
      <a href="{{ route('students.template') }}" 
         class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl text-xs font-bold transition-colors">
        <i class="fa-solid fa-download"></i> Unduh Template CSV
      </a>
    </div>
    <div class="card-body space-y-4">
      <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-800 leading-relaxed flex items-start gap-3">
        <i class="fa-solid fa-circle-info text-blue-500 text-base mt-0.5"></i>
        <div>
          <p class="font-bold mb-1">Panduan & Format File CSV:</p>
          <p>Baris 1 (Header) wajib berisi nama kolom: <code class="bg-white border border-blue-200 rounded px-1.5 py-0.5 font-mono font-bold text-blue-900">kelas, nis, nama</code></p>
          <p class="mt-1 text-blue-600">Contoh: <code class="font-mono">X-A, 2024001, Ahmad Ridwan</code> (Mendukung pemisah koma <code>,</code> maupun titik koma <code>;</code>)</p>
        </div>
      </div>

      <form id="csvForm" action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div id="dropzone"
             class="relative border-2 border-dashed border-slate-300 hover:border-indigo-500 bg-slate-50 hover:bg-indigo-50/40 rounded-2xl p-8 transition-all cursor-pointer text-center group">
          <input id="csvFileInput" type="file" name="csv_file" accept=".csv,.txt" class="hidden">
          
          <div id="dropzonePrompt" class="space-y-2">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100/60 text-indigo-600 group-hover:scale-110 transition-transform">
              <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">
              Tarik & Lepas File CSV di sini, atau <span class="text-indigo-600 underline">Pilih File</span>
            </p>
            <p class="text-xs text-slate-400">Format yang didukung: .csv, .txt (Maks 4MB)</p>
          </div>

          <div id="dropzonePreview" class="hidden space-y-3">
            <div class="inline-flex items-center gap-3 px-4 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm">
              <i class="fa-solid fa-file-csv text-2xl text-emerald-500"></i>
              <div class="text-left">
                <p id="fileName" class="text-xs font-bold text-slate-800 truncate max-w-[200px] sm:max-w-[300px]">nama_file.csv</p>
                <p id="fileSize" class="text-[10px] text-slate-400">0 KB</p>
              </div>
              <button type="button" onclick="resetCsvSelection(event)" class="text-slate-400 hover:text-rose-500 transition-colors p-1">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
            <div>
              <button type="submit" id="btnSubmitCsv" class="btn btn-primary px-6 py-2.5 shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-file-import mr-1.5"></i> Mulai Impor Siswa
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>

{{-- ── TABEL DATABASE SISWA ─────────────────────────────────────────────── --}}
<div class="card overflow-hidden">
  <div class="card-header">
    <div class="flex items-center gap-2">
      <h3 class="text-sm font-bold text-slate-700">Database Siswa Aktif</h3>
      <span class="badge bg-indigo-50 text-indigo-700">{{ count($siswa) }} siswa</span>
    </div>
    {{-- Filter Kelas --}}
    <form method="GET" action="{{ route('students.index') }}" class="flex items-center gap-2">
      <label class="text-xs font-bold text-slate-500 uppercase whitespace-nowrap">Filter:</label>
      <select name="kelas" onchange="this.form.submit()"
              class="form-input py-1.5 px-3 w-auto text-xs">
        <option value="ALL" {{ $selectedClass === 'ALL' ? 'selected' : '' }}>Semua Kelas</option>
        @foreach($classes as $c)
          <option value="{{ $c }}" {{ $selectedClass === $c ? 'selected' : '' }}>{{ $c }}</option>
        @endforeach
      </select>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead>
        <tr>
          <th class="w-14">No.</th>
          <th class="w-28">Kelas</th>
          <th class="w-32">NIS</th>
          <th>Nama Siswa</th>
          <th class="text-right w-32">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($siswa as $idx => $s)
          @php
              $nis = $s['NIS'];
              $nama = $s['Nama Siswa'] ?? $s['Nama'] ?? '-';
              $kelas = $s['Kelas'];
          @endphp
          <tr>
            <td class="text-slate-400 text-xs">{{ $idx + 1 }}</td>
            <td><span class="badge bg-indigo-50 text-indigo-700">{{ $kelas }}</span></td>
            <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
            <td class="font-semibold">{{ $nama }}</td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="openEditModal('{{ $nis }}', '{{ $nama }}', '{{ $kelas }}')" 
                        class="btn btn-outline text-xs px-2 py-1 text-blue-600 border-blue-200 hover:bg-blue-50">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <form action="{{ route('students.destroy', $nis) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini? Semua nilai dan absensinya juga akan terhapus.');">
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
            <td colspan="5" class="text-center py-12 text-slate-400 italic">
              Belum ada data siswa. Tambahkan siswa baru atau impor melalui file CSV.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── MODAL EDIT SISWA ─────────────────────────────────────────────── --}}
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden border border-slate-200">
    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
      <h3 class="font-bold text-slate-700"><i class="fa-solid fa-pen-to-square text-indigo-500 mr-2"></i> Edit Data Siswa</h3>
      <button onclick="closeEditModal()" class="text-slate-400 hover:text-rose-500 transition-colors">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>
    <form id="editForm" method="POST" action="" class="p-6 space-y-4">
      @csrf
      @method('PUT')
      <div>
        <label class="form-label">Kelas</label>
        <input type="text" name="kelas" id="edit_kelas" required class="form-input">
      </div>
      <div>
        <label class="form-label">NIS</label>
        <input type="text" name="nis" id="edit_nis" required class="form-input">
      </div>
      <div>
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama" id="edit_nama" required class="form-input">
      </div>
      <div class="pt-4 flex justify-end gap-3">
        <button type="button" onclick="closeEditModal()" class="btn btn-outline">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openEditModal(nis, nama, kelas) {
    document.getElementById('edit_nis').value = nis;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_kelas').value = kelas;
    document.getElementById('editForm').action = '/siswa/' + encodeURIComponent(nis);
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
  }

  // ── Drag & Drop CSV Handling ──
  document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('dropzone');
    const csvFileInput = document.getElementById('csvFileInput');
    const dropzonePrompt = document.getElementById('dropzonePrompt');
    const dropzonePreview = document.getElementById('dropzonePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');

    if (dropzone && csvFileInput) {
      dropzone.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        csvFileInput.click();
      });

      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('border-indigo-500', 'bg-indigo-50/60');
        }, false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/60');
        }, false);
      });

      dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
          csvFileInput.files = files;
          handleFileSelection(files[0]);
        }
      });

      csvFileInput.addEventListener('change', () => {
        if (csvFileInput.files.length > 0) {
          handleFileSelection(csvFileInput.files[0]);
        }
      });
    }

    window.handleFileSelection = function(file) {
      if (fileName && fileSize && dropzonePrompt && dropzonePreview) {
        fileName.innerText = file.name;
        fileSize.innerText = (file.size / 1024).toFixed(1) + ' KB';
        dropzonePrompt.classList.add('hidden');
        dropzonePreview.classList.remove('hidden');
      }
    };

    window.resetCsvSelection = function(e) {
      if (e) e.stopPropagation();
      if (csvFileInput) csvFileInput.value = '';
      if (dropzonePreview && dropzonePrompt) {
        dropzonePreview.classList.add('hidden');
        dropzonePrompt.classList.remove('hidden');
      }
    };
  });
</script>

@endsection
