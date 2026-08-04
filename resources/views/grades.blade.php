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

{{-- Alert Notifikasi --}}
@if(session('success'))
  <div class="no-print mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-2">
      <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
      <span>{{ session('success') }}</span>
    </div>
    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
@endif

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

    <div class="flex items-center gap-2 w-full lg:w-auto shrink-0">
      @if($selectedClass)
        <button type="button" onclick="openTaskModal()"
                class="btn btn-outline text-xs py-2.5 px-3 flex-1 lg:flex-initial justify-center bg-indigo-50/50 text-indigo-700 border-indigo-200 hover:bg-indigo-100">
          <i class="fa-solid fa-sliders text-indigo-600"></i> Kelola Tugas
        </button>
      @endif
      <button onclick="window.print()"
              class="btn btn-outline text-xs py-2.5 px-3 flex-1 lg:flex-initial justify-center">
        <i class="fa-solid fa-print text-indigo-500"></i> Cetak Leger
      </button>
    </div>
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
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="text-sm font-bold text-slate-700">
            Input {{ $gradeTypes[$selectedType] ?? $selectedType }}
          </h3>
          <span class="badge bg-indigo-50 text-indigo-700">Kelas {{ $selectedClass }} &mdash; {{ $selectedSubject }}</span>
          @if($selectedType === 'Poin_Sikap')
            <span class="badge bg-amber-50 text-amber-800 border border-amber-200">
              <i class="fa-solid fa-star text-amber-500 mr-1"></i> Auto-Termuat dari Bintang Absensi (1 Bintang = +5 Poin)
            </span>
          @endif
        </div>
      </div>
      <div class="overflow-x-auto -mx-2 sm:mx-0">
        <table class="data-table min-w-[500px]">
          <thead>
            <tr>
              <th class="w-12 text-center">No</th>
              <th class="w-28">NIS</th>
              <th>Nama Siswa</th>
              <th class="w-44 text-center">
                {{ $gradeTypes[$selectedType] ?? $selectedType }} (0–100)
              </th>
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
    <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <h3 class="text-sm font-bold text-slate-700">
        Leger Nilai — Kelas {{ $selectedClass }}, {{ $selectedSubject }} (Semester {{ $selectedSemester }})
      </h3>
      <span class="text-xs text-slate-500">
        Total Siswa: <b>{{ count($students) }}</b> | Jumlah Tugas: <b>{{ count($taskColumns) }}</b>
      </span>
    </div>
    <div class="overflow-x-auto -mx-2 sm:mx-0">
      <table class="data-table min-w-[850px]">
        <thead>
          <tr>
            <th class="w-12 text-center">No</th>
            <th class="w-24">NIS</th>
            <th>Nama Siswa</th>
            @foreach($taskColumns as $tc)
              <th class="text-center w-24" title="{{ $tc['name'] }}">{{ $tc['name'] }}</th>
            @endforeach
            <th class="text-center w-20">PTS</th>
            <th class="text-center w-20">PAS</th>
            <th class="text-center w-20">Praktik</th>
            <th class="text-center w-24 text-amber-700 bg-amber-50/70" title="Bonus Sikap dari Absensi (1 Bintang = +5 Poin)">⭐ Poin Sikap</th>
            <th class="text-center w-24 text-indigo-700 font-extrabold bg-indigo-50/70">Rata-rata</th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $idx => $student)
            @php
              $nis = $student['NIS'];
              $g   = $grades[$nis] ?? [];
              
              $taskVals = [];
              foreach($taskColumns as $tc) {
                $taskVals[] = $g[$tc['id']] ?? null;
              }

              $pts = $g['PTS'] ?? null;
              $pas = $g['PAS'] ?? null;
              $prk = $g['Praktik'] ?? null;
              $poinSikap = $g['Poin_Sikap'] ?? 0;

              $allAkademik = array_merge($taskVals, [$pts, $pas, $prk]);
              $validScores = array_filter($allAkademik, fn($v) => $v !== null && $v !== '');
              $avgAkademik = !empty($validScores) ? array_sum($validScores) / count($validScores) : null;
              
              // Total Rata-rata akhir ditambah poin sikap bonus
              $finalScore = $avgAkademik !== null ? min(100, $avgAkademik + $poinSikap) : null;
            @endphp
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="text-slate-400 text-xs text-center font-medium">{{ $idx + 1 }}</td>
              <td class="font-mono text-xs text-slate-500">{{ $nis }}</td>
              <td class="font-bold text-slate-900 text-xs sm:text-sm">{{ $student['Nama Siswa'] ?? $student['Nama'] ?? '-' }}</td>
              @foreach($taskColumns as $idxTc => $tc)
                @php $valTc = $taskVals[$idxTc]; @endphp
                <td class="text-center font-mono font-medium">{{ $valTc !== null ? number_format($valTc, 1) : '-' }}</td>
              @endforeach
              <td class="text-center font-mono font-medium">{{ $pts !== null ? number_format($pts, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $pas !== null ? number_format($pas, 1) : '-' }}</td>
              <td class="text-center font-mono font-medium">{{ $prk !== null ? number_format($prk, 1) : '-' }}</td>
              <td class="text-center font-mono font-bold text-amber-700 bg-amber-50/50">
                +{{ number_format($poinSikap, 0) }}
              </td>
              <td class="text-center font-mono font-extrabold text-indigo-700 bg-indigo-50/50">
                {{ $finalScore !== null ? number_format($finalScore, 1) : '-' }}
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


{{-- ── MODAL KELOLA DAFTAR TUGAS ─────────────────────────────────────────── --}}
@if($selectedClass)
<div id="taskModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-5 text-white flex items-center justify-between">
      <div>
        <h3 class="font-bold text-base flex items-center gap-2">
          <i class="fa-solid fa-list-check"></i> Kelola & Rename Daftar Tugas
        </h3>
        <p class="text-xs text-indigo-200 mt-0.5">
          Kelas {{ $selectedClass }} &mdash; {{ $selectedSubject }} (Semester {{ $selectedSemester }})
        </p>
      </div>
      <button onclick="closeTaskModal()" class="text-indigo-200 hover:text-white text-lg">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="{{ route('grades.update_tasks', ['mode' => $mode]) }}" method="POST" class="p-5 space-y-4">
      @csrf
      <input type="hidden" name="kelas"          value="{{ $selectedClass }}">
      <input type="hidden" name="semester"       value="{{ $selectedSemester }}">
      <input type="hidden" name="mata_pelajaran" value="{{ $selectedSubject }}">

      <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200">
        💡 Anda dapat **mengubah nama tugas**, **menambah tugas baru**, atau **menghapus tugas** yang tidak dipakai dalam satu semester.
      </div>

      <div id="taskListContainer" class="space-y-3.5 max-h-[300px] overflow-y-auto pr-1">
        @foreach($taskColumns as $idx => $t)
          <div class="task-row flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-xs font-bold text-slate-400 w-6 text-center shrink-0">{{ $idx + 1 }}</span>
            <input type="hidden" name="tasks[{{ $idx }}][id]" value="{{ $t['id'] }}">
            <input type="text" name="tasks[{{ $idx }}][name]" value="{{ $t['name'] }}" required
                   placeholder="Nama Tugas (mis: Tugas 1 / Kuis Bab 1)"
                   class="form-input text-xs py-2 flex-1">
            <button type="button" onclick="removeTaskRow(this)"
                    class="btn text-rose-500 hover:bg-rose-50 p-2 rounded-lg shrink-0" title="Hapus Tugas">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </div>
        @endforeach
      </div>

      <button type="button" onclick="addNewTaskRow()"
              class="btn btn-outline text-xs w-full py-2.5 border-dashed border-indigo-300 text-indigo-700 hover:bg-indigo-50 justify-center">
        <i class="fa-solid fa-plus text-indigo-600"></i> Tambah Tugas Baru
      </button>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
        <button type="button" onclick="closeTaskModal()" class="btn btn-outline text-xs py-2 px-4">
          Batal
        </button>
        <button type="submit" class="btn btn-primary text-xs py-2 px-5">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Daftar Tugas
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
  }

  function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
  }

  function removeTaskRow(btn) {
    const container = document.getElementById('taskListContainer');
    const rows = container.getElementsByClassName('task-row');
    if (rows.length <= 1) {
      alert('Minimal harus ada 1 tugas dalam semester.');
      return;
    }
    btn.closest('.task-row').remove();
    renumberTaskRows();
  }

  function renumberTaskRows() {
    const rows = document.querySelectorAll('#taskListContainer .task-row');
    rows.forEach((row, idx) => {
      const numSpan = row.querySelector('span');
      if (numSpan) numSpan.textContent = idx + 1;
    });
  }

  function addNewTaskRow() {
    const container = document.getElementById('taskListContainer');
    const rows = container.getElementsByClassName('task-row');
    const nextIdx = rows.length;
    const newId = 'tugas_' + (nextIdx + 1) + '_' + Date.now().toString().slice(-4);
    const newName = 'Tugas ' + (nextIdx + 1);

    const div = document.createElement('div');
    div.className = 'task-row flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200 animate-in fade-in duration-200';
    div.innerHTML = `
      <span class="text-xs font-bold text-slate-400 w-6 text-center shrink-0">${nextIdx + 1}</span>
      <input type="hidden" name="tasks[${nextIdx}][id]" value="${newId}">
      <input type="text" name="tasks[${nextIdx}][name]" value="${newName}" required
             placeholder="Nama Tugas"
             class="form-input text-xs py-2 flex-1">
      <button type="button" onclick="removeTaskRow(this)"
              class="btn text-rose-500 hover:bg-rose-50 p-2 rounded-lg shrink-0" title="Hapus Tugas">
        <i class="fa-solid fa-trash-can"></i>
      </button>
    `;
    container.appendChild(div);
  }
</script>
@endif

@endsection
