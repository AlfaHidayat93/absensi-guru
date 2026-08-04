@extends('layouts.app')

@section('title', 'Penilaian & Leger')
@section('page-title', 'Input Nilai & Leger Kelas')

@section('content')

{{-- Print Header --}}
<div class="print-header text-center border-b-2 border-slate-800 pb-4 mb-6 hidden print:block">
  <h1 class="text-xl font-bold uppercase tracking-widest">Leger Nilai Siswa</h1>
  <p class="text-sm font-semibold text-slate-600 mt-1">
    Kelas: {{ $selectedClass ?? '-' }} &nbsp;|&nbsp; Semester: {{ $selectedSemester }} &nbsp;|&nbsp; Mapel: {{ $selectedSubject ?? '-' }}
  </p>
</div>

{{-- ── MODE TABS + FILTER ─────────────────────────────────────────────────── --}}
<div class="card no-print mb-6">
  <div class="card-body flex flex-col lg:flex-row lg:items-end gap-4">
    {{-- Tabs --}}
    <div class="flex bg-slate-100 rounded-xl p-1 gap-1 w-full lg:w-auto shrink-0">
      <a href="{{ route('grades.index', array_merge(request()->query(), ['mode'=>'input'])) }}"
         class="btn flex-1 lg:flex-initial text-xs py-2 px-4 {{ $mode === 'input' ? 'btn-primary shadow-sm' : 'btn-outline border-0 bg-transparent text-slate-600 hover:bg-white' }}">
        <i class="fa-solid fa-pencil"></i> Input Nilai
      </a>
      <a href="{{ route('grades.index', array_merge(request()->query(), ['mode'=>'leger'])) }}"
         class="btn flex-1 lg:flex-initial text-xs py-2 px-4 {{ $mode === 'leger' ? 'btn-primary shadow-sm' : 'btn-outline border-0 bg-transparent text-slate-600 hover:bg-white' }}">
        <i class="fa-solid fa-table-list"></i> Lihat Leger
      </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('grades.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 flex-1">
      <input type="hidden" name="mode" value="{{ $mode }}">
      <div>
        <label class="form-label">Kelas</label>
        <select name="kelas" onchange="this.form.submit()" class="form-input py-2">
          <option value="" disabled {{ !$selectedClass ? 'selected' : '' }}>-- Pilih Kelas --</option>
          @foreach($classes as $c)
            <option value="{{ $c }}" {{ $selectedClass == $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Semester</label>
        <select name="semester" onchange="this.form.submit()" class="form-input py-2">
          @foreach($semesters as $s)
            <option value="{{ $s }}" {{ $selectedSemester == $s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label">Mata Pelajaran</label>
        <select name="mata_pelajaran" onchange="this.form.submit()" class="form-input py-2">
          @foreach($subjects as $sbj)
            <option value="{{ $sbj }}" {{ ($selectedSubject ?? '') == $sbj ? 'selected' : '' }}>{{ $sbj }}</option>
          @endforeach
        </select>
      </div>
      @if($mode === 'input')
        <div>
          <label class="form-label">Jenis Penilaian</label>
          <select name="jenis" onchange="this.form.submit()" class="form-input py-2">
            @foreach($gradeTypes as $key => $label)
              <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      @endif
    </form>

    <button onclick="window.print()"
            class="btn btn-outline shrink-0 text-xs py-2.5 w-full sm:w-auto justify-center">
      <i class="fa-solid fa-print text-indigo-500"></i> Cetak Leger
    </button>
  </div>
</div>

@if($selectedClass && !empty($students))

  {{-- ── MODE INPUT NILAI ──────────────────────────────────────────────── --}}
  @if($mode === 'input')
  <form action="{{ route('grades.store') }}" method="POST">
    @csrf
    <input type="hidden" name="kelas"          value="{{ $selectedClass }}">
    <input type="hidden" name="semester"       value="{{ $selectedSemester }}">
    <input type="hidden" name="mata_pelajaran" value="{{ $selectedSubject }}">
    <input type="hidden" name="jenis"          value="{{ $selectedType }}">

    <div class="card overflow-hidden">
      <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-bold text-slate-700">
            Input {{ $gradeTypes[$selectedType] ?? $selectedType }}
          </h3>
          <span class="badge bg-indigo-50 text-indigo-700">Kelas {{ $selectedClass }} &mdash; {{ $selectedSubject }}</span>
        </div>
      </div>
      <div class="overflow-x-auto -mx-2 sm:mx-0">
        <table class="data-table min-w-[500px]">
          <thead>
            <tr>
              <th class="w-12 text-center">No</th>
              <th class="w-28">NIS</th>
              <th>Nama Siswa</th>
              <th class="w-44 text-center">{{ $gradeTypes[$selectedType] ?? $selectedType }} (0–100)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($students as $idx => $student)
              @php
                $nis   = $student['NIS'];
                $score = $grades[$nis][$selectedType] ?? '';
              @endphp
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="text-slate-400 text-xs text-center font-medium">{{ $idx + 1 }}</td>
                <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
                <td class="font-bold text-slate-900 text-xs sm:text-sm">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</td>
                <td class="text-center">
                  <input type="number" name="scores[{{ $nis }}]" value="{{ $score }}"
                         min="0" max="100" step="0.5"
                         placeholder="—"
                         class="form-input text-center w-28 mx-auto font-mono text-base font-extrabold text-indigo-700 py-2">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-6 py-4 border-t border-slate-100 flex justify-end no-print bg-slate-50/50">
        <button type="submit" class="btn btn-primary w-full sm:w-auto py-3 px-6 text-sm">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Nilai
        </button>
      </div>
    </div>
  </form>
  @endif

  {{-- ── MODE LEGER LENGKAP ────────────────────────────────────────────── --}}
  @if($mode === 'leger')
  <div class="card overflow-hidden">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700">
        Leger Nilai — Kelas {{ $selectedClass }}, {{ $selectedSubject }} (Semester {{ $selectedSemester }})
      </h3>
    </div>
    <div class="overflow-x-auto -mx-2 sm:mx-0">
      <table class="data-table min-w-[700px]">
        <thead>
          <tr>
            <th class="w-12 text-center">No</th>
            <th class="w-24">NIS</th>
            <th>Nama Siswa</th>
            <th class="text-center w-20">Tugas 1</th>
            <th class="text-center w-20">Tugas 2</th>
            <th class="text-center w-20">Tugas 3</th>
            <th class="text-center w-20">PTS</th>
            <th class="text-center w-20">PAS</th>
            <th class="text-center w-20">Praktik</th>
            <th class="text-center w-24 text-indigo-700 font-extrabold">Rata-rata</th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $idx => $student)
            @php
              $nis = $student['NIS'];
              $g   = $grades[$nis] ?? [];
              $t1  = $g['Tugas_1'] ?? null;
              $t2  = $g['Tugas_2'] ?? null;
              $t3  = $g['Tugas_3'] ?? null;
              $pts = $g['PTS'] ?? null;
              $pas = $g['PAS'] ?? null;
              $prk = $g['Praktik'] ?? null;

              $validScores = array_filter([$t1, $t2, $t3, $pts, $pas, $prk], fn($v) => $v !== null && $v !== '');
              $avg = !empty($validScores) ? array_sum($validScores) / count($validScores) : null;
            @endphp
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="text-slate-400 text-xs text-center font-medium">{{ $idx + 1 }}</td>
              <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
              <td class="font-bold text-slate-900 text-xs sm:text-sm">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $t1 !== null ? number_format($t1, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $t2 !== null ? number_format($t2, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $t3 !== null ? number_format($t3, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $pts !== null ? number_format($pts, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $pas !== null ? number_format($pas, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $prk !== null ? number_format($prk, 1) : '-' }}</td>
              <td class="text-center font-mono font-extrabold text-indigo-600 bg-indigo-50/40">
                {{ $avg !== null ? number_format($avg, 1) : '-' }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

@else
  <div class="card">
    <div class="card-body text-center py-16 text-slate-400 italic">
      Silakan pilih kelas, semester, dan mata pelajaran untuk menampilkan leger/input nilai.
    </div>
  </div>
@endif

@endsection
