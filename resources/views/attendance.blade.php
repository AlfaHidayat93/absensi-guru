@extends('layouts.app')

@section('title', 'Absensi Kelas')
@section('page-title', 'Presensi & Keaktifan Kelas')

@section('content')

<style>
  @media print {
    .no-print { display: none !important; }
    body.print-rekap-mode .session-form-section,
    body.print-rekap-mode .print-header-session {
      display: none !important;
    }
    body.print-rekap-mode .print-rekap-header {
      display: block !important;
    }
    body.print-rekap-mode .print-rekap-footer {
      display: block !important;
    }
    body.print-rekap-mode #rekapContentSection {
      display: block !important;
      border: none !important;
      padding: 0 !important;
    }
    body.print-rekap-mode .rekap-card-container {
      border: none !important;
      box-shadow: none !important;
    }
  }

  .print-rekap-header, .print-rekap-footer {
    display: none;
  }
</style>

{{-- Print Header untuk Sesi Harian --}}
<div class="print-header-session text-center border-b-2 border-slate-800 pb-4 mb-6 hidden print:block">
  <h1 class="text-xl font-bold uppercase tracking-widest text-slate-900">Daftar Kehadiran Siswa (Sesi Pembelajaran)</h1>
  <p class="text-sm font-semibold text-slate-600 mt-1">
    Kelas: {{ $selectedClass ?? '-' }} &nbsp;|&nbsp; Semester: {{ $selectedSemester }} &nbsp;|&nbsp; Tanggal: {{ $selectedDate }}
  </p>
  @if($existingRecord)
    <p class="text-xs text-slate-500 mt-0.5">
      Jam: {{ $existingRecord['Jam_Mulai'] ?? '' }} – {{ $existingRecord['Jam_Selesai'] ?? '' }}
      &nbsp;|&nbsp; Mata Pelajaran: {{ $existingRecord['Mata_Pelajaran'] ?? '-' }}
      &nbsp;|&nbsp; Guru: {{ $existingRecord['Guru'] ?? '-' }}
    </p>
  @endif
</div>

{{-- Print Header khusus Laporan Rekapitulasi (BK / Wali Kelas) --}}
<div class="print-rekap-header text-center border-b-2 border-slate-900 pb-4 mb-6">
  <h1 class="text-xl font-bold uppercase tracking-widest text-slate-900">LAPORAN REKAPITULASI KEHADIRAN & KEAKTIFAN SISWA</h1>
  <p class="text-sm font-semibold text-slate-700 mt-1">
    Kelas: {{ $selectedClass }} &nbsp;|&nbsp; Semester: {{ $selectedSemester }}
    @if($selectedSubject) &nbsp;|&nbsp; Mata Pelajaran: {{ $selectedSubject }} @endif
    &nbsp;|&nbsp; Total Pertemuan: {{ $totalPertemuan }} Sesi
  </p>
  <p class="text-xs text-slate-500 mt-1">Laporan Resmi untuk Wali Kelas & Guru Bimbingan Konseling (BK) — Dicetak Tanggal: {{ date('d/m/Y') }}</p>
</div>

{{-- ── FILTER PANEL ──────────────────────────────────────────────────────── --}}
<div class="card no-print mb-6">
  <div class="card-header">
    <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
      <i class="fa-solid fa-sliders text-indigo-500"></i> Filter & Konfigurasi Kelas
    </h3>
  </div>
  <div class="card-body">
    <form action="{{ route('attendance.index') }}" method="GET"
          class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
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
        <label class="form-label">Mata Pelajaran</label>
        <select name="mata_pelajaran" onchange="this.form.submit()" class="form-input">
          <option value="">-- Semua Mapel --</option>
          @foreach($subjects as $subject)
            <option value="{{ $subject }}" {{ $selectedSubject === $subject ? 'selected' : '' }}>{{ $subject }}</option>
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
        <div class="sm:col-span-2 lg:col-span-2">
          <label class="form-label">Pilih Sesi Tersimpan Hari Ini</label>
          <select name="session" onchange="this.form.submit()" class="form-input">
            <option value="new" {{ empty($selectedSession) ? 'selected' : '' }}>-- Buat Sesi Baru --</option>
            @foreach($matchingRecords as $record)
              @php
                $recordId = $record['ID_Absen'] ?? $record['id'] ?? '';
                $label = trim((string)($record['Jam_Mulai'] ?? '')) . ' – ' . trim((string)($record['Jam_Selesai'] ?? ''));
                $label = trim($label) !== '–' ? $label : ($recordId ?: 'Sesi ' . ($loop->index + 1));
              @endphp
              <option value="{{ $recordId }}" {{ !empty($selectedSession) && (string)$selectedSession === (string)$recordId ? 'selected' : '' }}>
                Sesi: {{ $label }} ({{ $record['Mata_Pelajaran'] ?? 'Mapel' }})
              </option>
            @endforeach
          </select>
        </div>
      @endif
      <div class="flex items-end">
        <button type="submit" class="btn btn-outline w-full justify-center text-xs py-3">
          <i class="fa-solid fa-filter text-indigo-500"></i> Filter Data
        </button>
      </div>
    </form>
  </div>
</div>

@if($selectedClass)

{{-- ── REKAPITULASI KEHADIRAN & KEAKTIFAN SEBELUMNYA ────────────────────── --}}
<div class="card rekap-card-container mb-6">
  <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print">
    <div class="flex items-center gap-2 cursor-pointer select-none" onclick="toggleRekapSection()">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-chart-pie text-indigo-500"></i> Rekapitulasi Kelas {{ $selectedClass }}
      </h3>
      <span class="badge bg-indigo-50 text-indigo-700 text-xs">{{ $totalPertemuan }} Sesi</span>
      <i id="rekapToggleIcon" class="fa-solid fa-chevron-down text-slate-400 transition-transform ml-2"></i>
    </div>
    
    <div class="flex items-center gap-2">
      <button type="button" onclick="printRekapLaporan()"
              class="btn btn-outline text-xs px-3 py-2 shadow-sm w-full sm:w-auto justify-center">
        <i class="fa-solid fa-print text-indigo-600 mr-1"></i> Cetak Laporan Wali Kelas / BK
      </button>
    </div>
  </div>

  <div id="rekapContentSection" class="card-body border-t border-slate-100 hidden space-y-4">
    <div class="overflow-x-auto -mx-2 sm:mx-0">
      <table class="w-full text-left text-xs text-slate-600 border border-slate-200 rounded-xl overflow-hidden min-w-[700px]">
        <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-300 uppercase tracking-wider">
          <tr>
            <th class="px-3.5 py-3 w-10 text-center">No</th>
            <th class="px-3.5 py-3 w-24">NIS</th>
            <th class="px-3.5 py-3">Nama Siswa</th>
            <th class="px-3.5 py-3 text-center text-emerald-700 font-extrabold">Hadir</th>
            <th class="px-3.5 py-3 text-center text-amber-700 font-extrabold">Sakit</th>
            <th class="px-3.5 py-3 text-center text-blue-700 font-extrabold">Izin</th>
            <th class="px-3.5 py-3 text-center text-rose-700 font-extrabold">Alpa</th>
            <th class="px-3.5 py-3 text-center font-extrabold">Total</th>
            <th class="px-3.5 py-3 text-center">% Hadir</th>
            <th class="px-3.5 py-3">Status Keaktifan & Catatan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
          @forelse($students as $idx => $st)
            @php
              $nis = (string)$st['NIS'];
              $stat = $rekapSiswa[$nis] ?? ['hadir'=>0,'sakit'=>0,'izin'=>0,'alpa'=>0,'total'=>0,'persentase'=>0,'bintang'=>0,'peringatan'=>0,'catatan'=>[]];
              $pct = $stat['persentase'];
              $pctColor = $pct >= 85 ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : ($pct >= 75 ? 'bg-amber-50 text-amber-700 border-amber-300' : 'bg-rose-50 text-rose-700 border-rose-300');
            @endphp
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="px-3.5 py-2.5 text-center font-medium text-slate-500">{{ $idx + 1 }}</td>
              <td class="px-3.5 py-2.5 font-mono text-slate-600">{{ $nis }}</td>
              <td class="px-3.5 py-2.5 font-bold text-slate-900">{{ $st['Nama Siswa'] ?? $st['Nama'] ?? '-' }}</td>
              <td class="px-3.5 py-2.5 text-center font-bold text-emerald-700 bg-emerald-50/30">{{ $stat['hadir'] }}</td>
              <td class="px-3.5 py-2.5 text-center font-bold text-amber-700 bg-amber-50/30">{{ $stat['sakit'] }}</td>
              <td class="px-3.5 py-2.5 text-center font-bold text-blue-700 bg-blue-50/30">{{ $stat['izin'] }}</td>
              <td class="px-3.5 py-2.5 text-center font-bold text-rose-700 bg-rose-50/30">{{ $stat['alpa'] }}</td>
              <td class="px-3.5 py-2.5 text-center font-bold text-slate-800">{{ $stat['total'] }}</td>
              <td class="px-3.5 py-2.5 text-center">
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $pctColor }}">
                  {{ $pct }}%
                </span>
              </td>
              <td class="px-3.5 py-2.5">
                <div class="flex flex-wrap items-center gap-1.5">
                  @if($stat['bintang'] > 0)
                    <span class="px-2 py-0.5 bg-amber-100 border border-amber-300 text-amber-900 rounded-md font-bold text-[10px]">
                      ⭐ {{ $stat['bintang'] }}x Aktif
                    </span>
                  @endif
                  @if($stat['peringatan'] > 0)
                    <span class="px-2 py-0.5 bg-rose-100 border border-rose-300 text-rose-900 rounded-md font-bold text-[10px]">
                      ⚠️ {{ $stat['peringatan'] }}x Pasif
                    </span>
                  @endif
                  @if(!empty($stat['catatan']))
                    @php $lastNote = end($stat['catatan']); @endphp
                    <span class="text-[11px] text-slate-700 italic bg-slate-100 px-2 py-0.5 rounded-md truncate max-w-[200px]" title="{{ $lastNote['note'] ?? '' }}">
                      "{{ $lastNote['note'] ?? '' }}"
                    </span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="px-4 py-6 text-center text-slate-400 italic">Belum ada data rekapitulasi.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Tanda Tangan Cetak Rekap --}}
    <div class="print-rekap-footer text-center text-xs mt-12 pt-6 border-t border-slate-300">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; text-align: center; width: 100%;">
        <div style="flex: 1; text-align: center; padding: 0 10px;">
          <p class="font-semibold text-slate-700" style="margin-bottom: 60px;">Mengetahui,<br>Guru Mata Pelajaran {{ $selectedSubject ? $selectedSubject : '' }}</p>
          <p class="font-bold text-slate-900" style="border-bottom: 1px solid #0f172a; display: inline-block; padding: 0 16px 2px 16px;">{{ $selectedGuru ?: (auth()->user()->name ?? 'Guru') }}</p>
        </div>
        <div style="flex: 1; text-align: center; padding: 0 10px;">
          <p class="font-semibold text-slate-700" style="margin-bottom: 60px;">Mengetahui,<br>Guru Bimbingan Konseling (BK)</p>
          <p class="font-bold text-slate-900" style="border-bottom: 1px solid #0f172a; display: inline-block; padding: 0 16px 2px 16px;">( ........................................ )</p>
        </div>
        <div style="flex: 1; text-align: center; padding: 0 10px;">
          <p class="font-semibold text-slate-700" style="margin-bottom: 60px;">Mengetahui,<br>Wali Kelas {{ $selectedClass }}</p>
          <p class="font-bold text-slate-900" style="border-bottom: 1px solid #0f172a; display: inline-block; padding: 0 16px 2px 16px;">( ........................................ )</p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── FORM ABSENSI ─────────────────────────────────────────────────────── --}}
<div class="session-form-section">
  <form action="{{ route('attendance.store') }}" method="POST">
    @csrf
    <input type="hidden" name="kelas"    value="{{ $selectedClass }}">
    <input type="hidden" name="semester" value="{{ $selectedSemester }}">
    <input type="hidden" name="tanggal"  value="{{ $selectedDate }}">
    @if($existingRecord)
      <input type="hidden" name="id_absen" value="{{ $existingRecord['ID_Absen'] ?? ($existingRecord['id'] ?? '') }}">
    @endif
    <input type="hidden" name="session" value="{{ $selectedSession }}">

    {{-- Detail Sesi & Pilihan Jam 1-10 --}}
    <div class="card no-print mb-6">
      <div class="card-header">
        <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fa-solid fa-clock text-indigo-500"></i> Detail Jam / Sesi Pembelajaran
        </h3>
        @if($existingRecord)
          <span class="badge bg-amber-50 text-amber-700 border border-amber-200">
            <i class="fa-solid fa-pen-to-square mr-1"></i> Mode Update
          </span>
        @endif
      </div>
      <div class="card-body space-y-4">
        
        {{-- Pilihan Jam Pembelajaran 1 sampai 10 --}}
        <div class="bg-indigo-50/70 p-4 rounded-xl border border-indigo-100">
          <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wider mb-2">
            ⏰ Pilih Jam Pembelajaran (Jam Ke-1 s/d Jam Ke-10)
          </label>
          <select id="presetSesiSelect" onchange="applyPresetSesi(this.value)" class="form-input font-bold text-indigo-800 bg-white border-indigo-200 focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Pilih Jam / Sesi Pembelajaran (1 – 10) --</option>
            @foreach($sesiList as $labelKey => $info)
              <option value="{{ $info['mulai'] }}|{{ $info['selesai'] }}">{{ $info['label'] }}</option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="form-label">Jam Mulai</label>
            <input type="time" name="jam_mulai" id="jamMulaiInput" value="{{ $existingRecord['Jam_Mulai'] ?? '07:30' }}" class="form-input">
          </div>
          <div>
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="jam_selesai" id="jamSelesaiInput" value="{{ $existingRecord['Jam_Selesai'] ?? '09:00' }}" class="form-input">
          </div>
          <div>
            <label class="form-label">Mata Pelajaran</label>
            <select name="mata_pelajaran" class="form-input">
              <option value="">-- Pilih Mata Pelajaran --</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject }}" {{ ($existingRecord['Mata_Pelajaran'] ?? $selectedSubject) === $subject ? 'selected' : '' }}>{{ $subject }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="form-label">Guru Pengampu</label>
            <select name="guru" class="form-input">
              <option value="">-- Pilih Guru --</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher }}" {{ ($existingRecord['Guru'] ?? $selectedGuru) === $teacher ? 'selected' : '' }}>{{ $teacher }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Materi / Pokok Bahasan</label>
            <input type="text" name="materi" value="{{ $existingRecord['Materi_Pembelajaran'] ?? '' }}"
                   placeholder="Tuliskan topik pembelajaran hari ini..." class="form-input">
          </div>
          <div>
            <label class="form-label">Catatan Kelas / Evaluasi Umum</label>
            <textarea name="catatan" rows="1" placeholder="Catatan umum kelas, tugas, atau kejadian khusus..."
                      class="form-input resize-none">{{ $existingRecord['Catatan_Kelas'] ?? '' }}</textarea>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabel Kehadiran (Mobile & Tablet Touch Responsive) --}}
    <div class="card overflow-hidden">
      <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-bold text-slate-700">Lembar Presensi & Keaktifan Siswa</h3>
          <span class="badge bg-indigo-50 text-indigo-700">Kelas {{ $selectedClass }}</span>
        </div>
        <button type="button" onclick="window.print()"
                class="btn btn-outline text-xs px-3 py-2 no-print w-full sm:w-auto justify-center">
          <i class="fa-solid fa-print text-indigo-500"></i> Cetak Presensi Harian
        </button>
      </div>

      <div class="overflow-x-auto -mx-2 sm:mx-0">
        <table class="data-table min-w-[750px]">
          <thead>
            <tr>
              <th class="w-10 text-center">No</th>
              <th class="w-24">NIS</th>
              <th>Nama Siswa & Rekap</th>
              <th class="text-center w-56">Status Presensi</th>
              <th class="text-center w-52 no-print">Keaktifan</th>
              <th class="w-56 no-print">Catatan Siswa</th>
            </tr>
          </thead>
          <tbody>
            @forelse($students as $idx => $student)
              @php
                $nis        = (string)$student['NIS'];
                $rawCurrent = $detailKehadiran[$nis] ?? 'Hadir';
                
                if (is_array($rawCurrent)) {
                    $currentStatus    = $rawCurrent['status'] ?? 'Hadir';
                    $currentNote      = $rawCurrent['note'] ?? '';
                    $currentKeaktifan = $rawCurrent['keaktifan'] ?? 'normal';
                } else {
                    $currentStatus    = (string)$rawCurrent;
                    $currentNote      = '';
                    $currentKeaktifan = 'normal';
                }

                $statuses = ['Hadir', 'Sakit', 'Izin', 'Alpa'];
                $colors  = [
                  'Hadir' => 'text-emerald-600',
                  'Sakit' => 'text-amber-600',
                  'Izin'  => 'text-blue-600',
                  'Alpa'  => 'text-rose-600',
                ];

                $stat = $rekapSiswa[$nis] ?? ['hadir'=>0,'sakit'=>0,'izin'=>0,'alpa'=>0,'total'=>0,'persentase'=>0];
              @endphp
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="text-slate-400 text-xs text-center font-medium">{{ $idx + 1 }}</td>
                <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
                <td>
                  <div class="font-bold text-slate-900 text-xs sm:text-sm">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</div>
                  <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5 no-print">
                    <span class="px-1.5 py-0.2 bg-slate-100 border border-slate-200 rounded text-[10px] font-mono">
                      H:{{ $stat['hadir'] }} | S:{{ $stat['sakit'] }} | I:{{ $stat['izin'] }} | A:{{ $stat['alpa'] }}
                    </span>
                    <span class="font-bold {{ $stat['persentase'] >= 85 ? 'text-emerald-600' : ($stat['persentase'] >= 75 ? 'text-amber-600' : 'text-rose-600') }}">
                      ({{ $stat['persentase'] }}%)
                    </span>
                  </div>
                </td>
                <td>
                  {{-- Touch-Friendly Status Selection --}}
                  <div class="no-print flex items-center justify-center gap-1 sm:gap-2">
                    @foreach($statuses as $s)
                      <label class="flex items-center justify-center min-h-[38px] px-2 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 cursor-pointer select-none transition-all group">
                        <input type="radio" name="status[{{ $nis }}]" value="{{ $s }}"
                               {{ $currentStatus === $s ? 'checked' : '' }}
                               class="w-4 h-4 accent-indigo-600">
                        <span class="text-xs font-bold text-slate-700 ml-1">
                          {{ substr($s, 0, 1) }}<span class="hidden md:inline">{{ substr($s, 1) }}</span>
                        </span>
                      </label>
                    @endforeach
                  </div>
                  {{-- Print View --}}
                  <div class="hidden print:block text-center font-bold text-sm {{ $colors[$currentStatus] ?? '' }}">
                    {{ $currentStatus }}
                  </div>
                </td>
                <td class="no-print text-center">
                  {{-- Keaktifan (Aktif / Biasa / Pasif) --}}
                  <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200 text-xs select-none">
                    <label class="cursor-pointer px-2 py-1.5 rounded-lg transition-all flex items-center gap-1 {{ $currentKeaktifan === 'aktif' ? 'bg-amber-400 text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                      <input type="radio" name="keaktifan[{{ $nis }}]" value="aktif" {{ $currentKeaktifan === 'aktif' ? 'checked' : '' }} class="hidden">
                      <span>⭐ Aktif</span>
                    </label>
                    <label class="cursor-pointer px-2 py-1.5 rounded-lg transition-all flex items-center gap-1 {{ $currentKeaktifan === 'normal' ? 'bg-white text-slate-800 font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                      <input type="radio" name="keaktifan[{{ $nis }}]" value="normal" {{ $currentKeaktifan === 'normal' ? 'checked' : '' }} class="hidden">
                      <span>Biasa</span>
                    </label>
                    <label class="cursor-pointer px-2 py-1.5 rounded-lg transition-all flex items-center gap-1 {{ $currentKeaktifan === 'tidak_aktif' ? 'bg-rose-500 text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                      <input type="radio" name="keaktifan[{{ $nis }}]" value="tidak_aktif" {{ $currentKeaktifan === 'tidak_aktif' ? 'checked' : '' }} class="hidden">
                      <span>⚠️ Pasif</span>
                    </label>
                  </div>
                </td>
                <td class="no-print">
                  <input type="text" name="notes[{{ $nis }}]" value="{{ $currentNote }}"
                         placeholder="Catatan keaktifan/sikap..."
                         class="form-input text-xs py-2 px-3">
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-10 text-slate-400 italic">
                  Belum ada siswa terdaftar di Kelas {{ $selectedClass }}.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if(!empty($students))
        <div class="px-6 py-4 border-t border-slate-100 flex justify-end no-print bg-slate-50/50">
          <button type="submit" class="btn btn-primary w-full sm:w-auto py-3 px-6 text-sm">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ $existingRecord ? 'Perbarui Absensi' : 'Simpan Absensi' }}
          </button>
        </div>
      @endif
    </div>
  </form>
</div>

@else
  <div class="card">
    <div class="card-body text-center py-16 text-slate-400 italic">
      Silakan pilih kelas, semester, dan tanggal untuk memuat lembar absensi.
    </div>
  </div>
@endif

<script>
  function applyPresetSesi(val) {
    if (!val) return;
    const parts = val.split('|');
    if (parts.length === 2) {
      document.getElementById('jamMulaiInput').value = parts[0];
      document.getElementById('jamSelesaiInput').value = parts[1];
    }
  }

  function toggleRekapSection() {
    const sec = document.getElementById('rekapContentSection');
    const icon = document.getElementById('rekapToggleIcon');
    if (sec.classList.contains('hidden')) {
      sec.classList.remove('hidden');
      icon.style.transform = 'rotate(180deg)';
    } else {
      sec.classList.add('hidden');
      icon.style.transform = 'rotate(0deg)';
    }
  }

  function printRekapLaporan() {
    const sec = document.getElementById('rekapContentSection');
    const icon = document.getElementById('rekapToggleIcon');
    sec.classList.remove('hidden');
    icon.style.transform = 'rotate(180deg)';

    document.body.classList.add('print-rekap-mode');
    window.print();
    setTimeout(() => {
      document.body.classList.remove('print-rekap-mode');
    }, 1000);
  }
</script>

@endsection
