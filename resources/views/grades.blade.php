@extends('layouts.app')

@section('title', 'Penilaian & Leger')
@section('page-title', 'Input Nilai & Leger Kelas')

@section('content')

{{-- Print Header --}}
<div class="print-header text-center border-b-2 border-slate-800 pb-4 mb-6">
  <h1 class="text-xl font-bold uppercase tracking-widest">Leger Nilai Siswa</h1>
  <p class="text-sm font-semibold text-slate-600 mt-1">
    Kelas: {{ $selectedClass ?? '-' }} &nbsp;|&nbsp; Semester: {{ $selectedSemester }}
  </p>
</div>

{{-- ── MODE TABS + FILTER ─────────────────────────────────────────────────── --}}
<div class="card no-print">
  <div class="card-body flex flex-col sm:flex-row sm:items-end gap-4">
    {{-- Tabs --}}
    <div class="flex bg-slate-100 rounded-xl p-1 gap-1 shrink-0">
      <a href="{{ route('grades.index', array_merge(request()->query(), ['mode'=>'input'])) }}"
         class="btn text-xs py-1.5 px-4 {{ $mode === 'input' ? 'btn-primary shadow' : 'btn-outline border-0 bg-transparent text-slate-500 hover:bg-white' }}">
        <i class="fa-solid fa-pencil"></i> Input Nilai
      </a>
      <a href="{{ route('grades.index', array_merge(request()->query(), ['mode'=>'leger'])) }}"
         class="btn text-xs py-1.5 px-4 {{ $mode === 'leger' ? 'btn-primary shadow' : 'btn-outline border-0 bg-transparent text-slate-500 hover:bg-white' }}">
        <i class="fa-solid fa-table-list"></i> Lihat Leger
      </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('grades.index') }}" class="flex flex-wrap gap-3 flex-1">
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
            class="btn btn-outline shrink-0 text-xs py-2 self-end sm:self-auto">
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
      <div class="card-header">
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-bold text-slate-700">
            Input {{ $gradeTypes[$selectedType] ?? $selectedType }}
          </h3>
          <span class="badge bg-indigo-50 text-indigo-700">Kelas {{ $selectedClass }} &mdash; {{ $selectedSemester }}</span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th class="w-14">No.</th>
              <th class="w-32">NIS</th>
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
              <tr>
                <td class="text-slate-400 text-xs">{{ $idx + 1 }}</td>
                <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
                <td class="font-semibold">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</td>
                <td class="text-center">
                  <input type="number" name="scores[{{ $nis }}]" value="{{ $score }}"
                         min="0" max="100" step="0.5"
                         placeholder="—"
                         class="form-input text-center w-24 mx-auto font-mono text-base font-bold">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-6 py-4 border-t border-slate-100 flex justify-end">
        <button type="submit" class="btn btn-primary">
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
        Leger Nilai — Kelas {{ $selectedClass }}, Semester {{ $selectedSemester }}
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th class="w-14">No.</th>
            <th class="w-32">NIS</th>
            <th>Nama Siswa</th>
            @foreach($gradeTypes as $key => $label)
              <th class="text-center w-24">{{ $key }}</th>
            @endforeach
            <th class="text-center">Rata-rata</th>
            <th class="text-center">Predikat</th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $idx => $student)
            @php
              $nis     = $student['NIS'];
              $row     = $grades[$nis] ?? [];
              $vals    = collect(array_keys($gradeTypes))->map(fn($k) => $row[$k] !== '' && $row[$k] !== null ? (float)$row[$k] : null)->filter()->values()->all();
              $avg     = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
              [$pred, $pCls] = match(true) {
                $avg === null          => ['-',  'bg-slate-100 text-slate-400'],
                $avg >= 90             => ['A',  'bg-emerald-50 text-emerald-700 border border-emerald-200'],
                $avg >= 80             => ['B',  'bg-indigo-50 text-indigo-700 border border-indigo-200'],
                $avg >= 70             => ['C',  'bg-amber-50 text-amber-700 border border-amber-200'],
                $avg >= 60             => ['D',  'bg-orange-50 text-orange-700 border border-orange-200'],
                default                => ['E',  'bg-rose-50 text-rose-700 border border-rose-200'],
              };
            @endphp
            <tr>
              <td class="text-slate-400 text-xs">{{ $idx + 1 }}</td>
              <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
              <td class="font-semibold">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</td>
              @foreach(array_keys($gradeTypes) as $key)
                <td class="text-center font-mono">
                  {{ $row[$key] !== '' && $row[$key] !== null ? $row[$key] : '—' }}
                </td>
              @endforeach
              <td class="text-center font-extrabold text-indigo-700 text-base">
                {{ $avg !== null ? number_format($avg, 1) : '—' }}
              </td>
              <td class="text-center">
                <span class="badge {{ $pCls }} text-xs font-extrabold">{{ $pred }}</span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

@elseif($selectedClass)
  <div class="card">
    <div class="card-body text-center py-12 text-slate-400 italic">
      Tidak ada siswa terdaftar di Kelas {{ $selectedClass }}.
    </div>
  </div>
@else
  <div class="card">
    <div class="card-body text-center py-16 text-slate-400 italic">
      Silakan pilih kelas dan semester untuk menampilkan data nilai.
    </div>
  </div>
@endif

@endsection
