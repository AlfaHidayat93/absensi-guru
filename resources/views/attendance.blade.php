@extends('layouts.app')

@section('title', 'Absensi Kelas')
@section('page-title', 'Presensi & Keaktifan Kelas')

@section('content')

{{-- Print Header (Cetak Saja) --}}
<div class="print-header text-center border-b-2 border-slate-800 pb-4 mb-6">
  <h1 class="text-xl font-bold uppercase tracking-widest">Daftar Kehadiran Siswa</h1>
  <p class="text-sm font-semibold text-slate-600 mt-1">
    Kelas: {{ $selectedClass ?? '-' }} &nbsp;|&nbsp; Semester: {{ $selectedSemester }} &nbsp;|&nbsp; Tanggal: {{ $selectedDate }}
  </p>
  @if($existingRecord)
    <p class="text-xs text-slate-500 mt-0.5">
      Jam: {{ $existingRecord['Jam_Mulai'] ?? '' }} – {{ $existingRecord['Jam_Selesai'] ?? '' }}
      &nbsp;|&nbsp; Materi: {{ $existingRecord['Materi_Pembelajaran'] ?? '-' }}
    </p>
  @endif
</div>

{{-- ── FILTER PANEL ──────────────────────────────────────────────────────── --}}
<div class="card no-print">
  <div class="card-header">
    <h3 class="text-sm font-bold text-slate-700">Konfigurasi Sesi Pembelajaran</h3>
  </div>
  <div class="card-body">
    <form action="{{ route('attendance.index') }}" method="GET"
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <div>
        <label class="form-label">Kelas</label>
        <select name="kelas" onchange="this.form.submit()" class="form-input">
          <option value="" disabled {{ !$selectedClass ? 'selected' : '' }}>-- Pilih --</option>
          @foreach($classes as $c)
            <option value="{{ $c }}" {{ $selectedClass == $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Semester</label>
        <select name="semester" onchange="this.form.submit()" class="form-input">
          @foreach($semesters as $s)
            <option value="{{ $s }}" {{ $selectedSemester == $s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Tanggal</label>
        <input type="date" name="tanggal" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-input">
      </div>
      <div>
        <label class="form-label">Guru</label>
        <select name="guru" onchange="this.form.submit()" class="form-input">
          <option value="">-- Semua Guru --</option>
          @foreach($teachers as $teacher)
            <option value="{{ $teacher }}" {{ $selectedGuru === $teacher ? 'selected' : '' }}>{{ $teacher }}</option>
          @endforeach
        </select>
      </div>
      @if(!empty($matchingRecords))
        <div>
          <label class="form-label">Pilih Sesi</label>
          <select name="session" onchange="this.form.submit()" class="form-input">
            <option value="new" {{ empty($selectedSession) ? 'selected' : '' }}>-- Buat Absensi Baru --</option>
            @foreach($matchingRecords as $record)
              @php
                $recordId = $record['ID_Absen'] ?? $record['id'] ?? '';
                $label = trim((string)($record['Jam_Mulai'] ?? '')) . ' – ' . trim((string)($record['Jam_Selesai'] ?? ''));
                $label = trim($label) !== '–' ? $label : ($recordId ?: 'Sesi ' . ($loop->index + 1));
              @endphp
              <option value="{{ $recordId }}" {{ !empty($selectedSession) && (string)$selectedSession === (string)$recordId ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
      @endif
      {{-- Dummy spacer untuk trigger jika belum ada kelas --}}
      <div class="flex items-end">
        <button type="submit" class="btn btn-outline w-full justify-center text-xs py-2.5">
          <i class="fa-solid fa-filter text-indigo-500"></i> Tampilkan
        </button>
      </div>
    </form>
  </div>
</div>

@if($selectedClass)
{{-- ── FORM ABSENSI ─────────────────────────────────────────────────────── --}}
<form action="{{ route('attendance.store') }}" method="POST"
      @if($existingRecord) data-original-jam-mulai="{{ $existingRecord['Jam_Mulai'] ?? '' }}"
                           data-original-jam-selesai="{{ $existingRecord['Jam_Selesai'] ?? '' }}" @endif>
  @csrf
  <input type="hidden" name="kelas"    value="{{ $selectedClass }}">
  <input type="hidden" name="semester" value="{{ $selectedSemester }}">
  <input type="hidden" name="tanggal"  value="{{ $selectedDate }}">
  @if($existingRecord)
    <input type="hidden" name="id_absen" value="{{ $existingRecord['ID_Absen'] ?? ($existingRecord['id'] ?? '') }}">
  @endif
  <input type="hidden" name="session" value="{{ $selectedSession }}">

  {{-- Detail Sesi --}}
  <div class="card no-print">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700">Detail Sesi Pembelajaran</h3>
      @if($existingRecord)
        <span class="badge bg-amber-50 text-amber-700 border border-amber-200">
          <i class="fa-solid fa-pen-to-square mr-1"></i> Update Absensi
        </span>
      @endif
    </div>
    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Jam Mulai</label>
          <input type="time" name="jam_mulai" value="{{ $existingRecord['Jam_Mulai'] ?? '07:30' }}" class="form-input">
        </div>
        <div>
          <label class="form-label">Jam Selesai</label>
          <input type="time" name="jam_selesai" value="{{ $existingRecord['Jam_Selesai'] ?? '09:00' }}" class="form-input">
        </div>
      </div>
      <div>
        <label class="form-label">Mata Pelajaran</label>
        <select name="mata_pelajaran" class="form-input">
          <option value="">-- Pilih Mata Pelajaran --</option>
          @foreach($subjects as $subject)
            <option value="{{ $subject }}" {{ ($existingRecord['Mata_Pelajaran'] ?? '') === $subject ? 'selected' : '' }}>{{ $subject }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Guru</label>
        <select name="guru" class="form-input">
          <option value="">-- Pilih Guru --</option>
          @foreach($teachers as $teacher)
            <option value="{{ $teacher }}" {{ ($existingRecord['Guru'] ?? '') === $teacher ? 'selected' : '' }}>{{ $teacher }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Materi / Pokok Bahasan</label>
        <input type="text" name="materi" value="{{ $existingRecord['Materi_Pembelajaran'] ?? '' }}"
               placeholder="Tuliskan topik pembelajaran hari ini..." class="form-input">
      </div>
      <div class="md:col-span-2">
        <label class="form-label">Catatan Pembelajaran / Evaluasi</label>
        <textarea name="catatan" rows="2" placeholder="Catatan kelas, tugas, atau kejadian khusus..."
                  class="form-input resize-none">{{ $existingRecord['Catatan_Kelas'] ?? '' }}</textarea>
      </div>
    </div>
  </div>

  {{-- Tabel Kehadiran --}}
  <div class="card overflow-hidden">
    <div class="card-header">
      <div class="flex items-center gap-2">
        <h3 class="text-sm font-bold text-slate-700">Lembar Kehadiran</h3>
        <span class="badge bg-indigo-50 text-indigo-700">Kelas {{ $selectedClass }}</span>
      </div>
      <button type="button" onclick="window.print()"
              class="btn btn-outline text-xs px-3 py-1.5 no-print">
        <i class="fa-solid fa-print text-indigo-500"></i> Cetak
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th class="w-14">No.</th>
            <th class="w-28">NIS</th>
            <th>Nama Siswa</th>
            <th class="text-center">Status Kehadiran</th>
          </tr>
        </thead>
        <tbody>
          @forelse($students as $idx => $student)
            @php
              $nis     = $student['NIS'];
              $current = $detailKehadiran[$nis] ?? 'Hadir';
              $statuses = ['Hadir', 'Sakit', 'Izin', 'Alpa'];
              $colors  = [
                'Hadir' => 'text-emerald-600',
                'Sakit' => 'text-amber-500',
                'Izin'  => 'text-blue-500',
                'Alpa'  => 'text-rose-600',
              ];
            @endphp
            <tr>
              <td class="text-slate-400 text-xs">{{ $idx + 1 }}</td>
              <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
              <td class="font-semibold">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</td>
              <td>
                {{-- Screen: Radio buttons --}}
                <div class="no-print flex flex-wrap justify-center gap-3">
                  @foreach($statuses as $s)
                    <label class="flex items-center gap-1.5 cursor-pointer select-none group">
                      <input type="radio" name="status[{{ $nis }}]" value="{{ $s }}"
                             {{ $current === $s ? 'checked' : '' }}
                             class="w-4 h-4 accent-indigo-600">
                      <span class="text-xs font-semibold text-slate-600 group-has-[:checked]:{{ $colors[$s] }} transition-colors">
                        {{ $s }}
                      </span>
                    </label>
                  @endforeach
                </div>
                {{-- Print: Static text --}}
                <div class="hidden print:block text-center font-bold text-sm {{ $colors[$current] ?? '' }}">
                  {{ $current }}
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center py-10 text-slate-400 italic">
                Belum ada siswa terdaftar di Kelas {{ $selectedClass }}.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(!empty($students))
      <div class="px-6 py-4 border-t border-slate-100 flex justify-end no-print">
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-floppy-disk"></i>
          {{ $existingRecord ? 'Perbarui Absensi' : 'Simpan Absensi' }}
        </button>
      </div>
    @endif
  </div>
</form>
@else
  <div class="card">
    <div class="card-body text-center py-16 text-slate-400 italic">
      Silakan pilih kelas, semester, dan tanggal untuk memuat lembar absensi.
    </div>
  </div>
@endif

@endsection
