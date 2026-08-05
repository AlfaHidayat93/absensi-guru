@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Analisis Kelas')

@section('content')

{{-- ── STATS CARDS ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
  @php
    $cards = [
      ['icon'=>'fa-school',        'color'=>'blue',    'label'=>'Total Kelas',      'value'=> $stats['totalKelas'] ?? 0],
      ['icon'=>'fa-users',         'color'=>'indigo',  'label'=>'Total Siswa',      'value'=> $stats['totalSiswa'] ?? 0],
      ['icon'=>'fa-clipboard-user','color'=>'emerald', 'label'=>'Kehadiran Global', 'value'=> ($stats['globalAttendanceRate'] ?? 0).'%'],
      ['icon'=>'fa-award',         'color'=>'amber',   'label'=>'Rata-rata Nilai',  'value'=> $stats['globalGradesAvg'] ?? '0.0'],
    ];
    $colorMap = [
      'blue'   => ['bg'=>'bg-blue-50',   'text'=>'text-blue-600'],
      'indigo' => ['bg'=>'bg-indigo-50', 'text'=>'text-indigo-600'],
      'emerald'=> ['bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
      'amber'  => ['bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
    ];
  @endphp

  @foreach($cards as $card)
    @php $c = $colorMap[$card['color']]; @endphp
    <div class="stat-card flex items-center gap-4">
      <div class="p-3.5 {{ $c['bg'] }} {{ $c['text'] }} rounded-xl shrink-0">
        <i class="fa-solid {{ $card['icon'] }} text-2xl"></i>
      </div>
      <div class="min-w-0">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-tight">{{ $card['label'] }}</p>
        <p class="text-2xl sm:text-3xl font-extrabold text-slate-800 mt-0.5">{{ $card['value'] }}</p>
      </div>
    </div>
  @endforeach
</div>

{{-- ── CHART + RANKING ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- Chart Kehadiran --}}
  <div class="card lg:col-span-2">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700">Persentase Kehadiran per Kelas</h3>
      <span class="badge bg-indigo-50 text-indigo-700">Semua Semester</span>
    </div>
    <div class="card-body">
      <div class="relative h-56">
        @if(!empty($stats['kehadiranKelas']))
          <canvas id="attendanceChart"></canvas>
        @else
          <div class="absolute inset-0 flex items-center justify-center text-slate-400 italic text-sm">
            Belum ada data absensi kelas.
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Ranking Kehadiran --}}
  <div class="card flex flex-col">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700">Urutan Kehadiran Kelas</h3>
    </div>
    <div class="card-body flex-1 space-y-2.5 overflow-y-auto">
      @forelse($stats['kehadiranKelas'] ?? [] as $idx => $item)
        @php
          $isTop    = $idx === 0;
          $isBottom = $idx === count($stats['kehadiranKelas']) - 1 && count($stats['kehadiranKelas']) > 1;
          $cls = $isTop    ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
               : ($isBottom ? 'bg-rose-50 border border-rose-200 text-rose-700'
               : 'bg-slate-50 border border-slate-200 text-slate-700');
        @endphp
        <div class="flex items-center justify-between p-3 rounded-xl {{ $cls }}">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-extrabold opacity-50">#{{ $idx+1 }}</span>
            <span class="text-xs font-bold">Kelas {{ $item['kelas'] }}</span>
          </div>
          <span class="text-xs font-extrabold">{{ $item['rate'] }}%</span>
        </div>
      @empty
        <p class="text-xs text-slate-400 italic text-center py-8">Data kehadiran belum tersedia.</p>
      @endforelse
    </div>
    <div class="px-6 py-4 border-t border-slate-100 text-center">
      <a href="{{ route('attendance.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1.5">
        Buka Lembar Absensi <i class="fa-solid fa-arrow-right text-[10px]"></i>
      </a>
    </div>
  </div>
</div>

{{-- ── LEGER RATA-RATA NILAI ─────────────────────────────────────────────── --}}
<div class="card overflow-hidden">
  <div class="card-header">
    <h3 class="text-sm font-bold text-slate-700">Ringkasan Nilai Rata-rata per Kelas</h3>
    <a href="{{ route('grades.index', ['mode'=>'leger']) }}" class="btn btn-outline text-xs px-3 py-1.5">
      <i class="fa-solid fa-table-list text-indigo-500"></i> Lihat Leger
    </a>
  </div>
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Peringkat</th>
          <th>Nama Kelas</th>
          <th>Rata-rata Gabungan</th>
          <th>Status Capaian</th>
        </tr>
      </thead>
      <tbody>
        @forelse($stats['nilaiKelas'] ?? [] as $idx => $item)
          @php
            $avg = (float) $item['avg'];
            [$status, $badgeCls] = match(true) {
              $avg >= 80 => ['Sangat Baik',    'bg-emerald-50 text-emerald-700 border border-emerald-200'],
              $avg >= 70 => ['Baik',           'bg-indigo-50 text-indigo-700 border border-indigo-200'],
              $avg >= 60 => ['Cukup',          'bg-amber-50 text-amber-700 border border-amber-200'],
              $avg > 0   => ['Perlu Bimbingan','bg-rose-50 text-rose-700 border border-rose-200'],
              default    => ['Belum Ada Nilai','bg-slate-100 text-slate-500 border border-slate-200'],
            };
          @endphp
          <tr>
            <td class="font-mono text-xs text-slate-400 font-bold">#{{ str_pad($idx+1, 2, '0', STR_PAD_LEFT) }}</td>
            <td class="font-bold text-slate-800">Kelas {{ $item['kelas'] }}</td>
            <td class="font-extrabold text-indigo-600 text-base">{{ number_format($avg,1) }}</td>
            <td><span class="badge {{ $badgeCls }} uppercase tracking-wide text-[10px]">{{ $status }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center py-10 text-slate-400 italic">Data nilai belum tersedia.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ── DASHBOARD KHUSUS WALI KELAS ────────────────────────────────────────── --}}
@if(isset($waliKelasRekap) && $waliKelasRekap)
@php
  $rekap = $waliKelasRekap;
@endphp

<div class="mt-6 space-y-6">

  {{-- Header Wali Kelas --}}
  <div class="card bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 text-white border-0 shadow-lg">
    <div class="card-body flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="bg-white/20 rounded-2xl p-3">
          <i class="fa-solid fa-chalkboard-teacher text-2xl"></i>
        </div>
        <div>
          <p class="text-xs font-semibold text-indigo-200 uppercase tracking-widest">Dashboard Wali Kelas</p>
          <h2 class="text-lg font-extrabold">Kelas {{ $rekap['kelas'] }}</h2>
          <p class="text-xs text-indigo-200 mt-0.5">{{ count($rekap['siswa']) }} Siswa Terdaftar &bull; Rekapitulasi Kehadiran, Keaktifan & Nilai per Mata Pelajaran</p>
        </div>
      </div>
      <a href="{{ route('attendance.index', ['kelas' => $rekap['kelas']]) }}"
         class="shrink-0 px-4 py-2.5 bg-white/20 hover:bg-white/30 border border-white/30 rounded-xl text-xs font-bold text-white transition-all flex items-center gap-2">
        <i class="fa-solid fa-calendar-check"></i> Buka Lembar Presensi
      </a>
    </div>
  </div>

  {{-- Rekap Kehadiran Keseluruhan Per Siswa --}}
  <div class="card overflow-hidden">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-clipboard-user text-emerald-600"></i> Rekapitulasi Kehadiran Siswa (Semua Mapel)
      </h3>
    </div>
    <div class="overflow-x-auto -mx-2 sm:mx-0">
      <table class="data-table min-w-[700px]">
        <thead>
          <tr>
            <th class="w-10 text-center">No</th>
            <th>Nama Siswa</th>
            <th class="text-center w-16 text-emerald-700">Hadir</th>
            <th class="text-center w-16 text-amber-700">Sakit</th>
            <th class="text-center w-16 text-blue-700">Izin</th>
            <th class="text-center w-16 text-rose-700">Alpa</th>
            <th class="text-center w-20">% Hadir</th>
            <th class="text-center w-20 text-amber-700">⭐ Aktif</th>
            <th class="text-center w-20 text-rose-700">⚠️ Pasif</th>
            <th class="text-center w-24 text-indigo-700">Poin Sikap</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rekap['siswa'] as $idx => $siswa)
            @php
              $nis  = (string)$siswa->nis;
              $abs  = $rekap['absensi'][$nis] ?? ['hadir'=>0,'sakit'=>0,'izin'=>0,'alpa'=>0,'total'=>0,'bintang'=>0,'peringatan'=>0];
              $pct  = $abs['total'] > 0 ? round(($abs['hadir'] / $abs['total']) * 100) : 0;
              $poinSikap = ($abs['bintang'] ?? 0) * 5;
              $pctCls = $pct >= 85 ? 'text-emerald-600 font-extrabold' : ($pct >= 75 ? 'text-amber-600 font-extrabold' : 'text-rose-600 font-extrabold');
            @endphp
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="text-center text-slate-400 text-xs font-medium">{{ $idx + 1 }}</td>
              <td>
                <div class="font-bold text-slate-900 text-sm">{{ $siswa->nama }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ $siswa->nis }}</div>
              </td>
              <td class="text-center font-bold text-emerald-700">{{ $abs['hadir'] }}</td>
              <td class="text-center font-bold text-amber-700">{{ $abs['sakit'] }}</td>
              <td class="text-center font-bold text-blue-700">{{ $abs['izin'] }}</td>
              <td class="text-center font-bold text-rose-700">{{ $abs['alpa'] }}</td>
              <td class="text-center {{ $pctCls }}">{{ $pct }}%</td>
              <td class="text-center">
                @if(($abs['bintang'] ?? 0) > 0)
                  <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-lg font-extrabold text-xs">⭐{{ $abs['bintang'] }}</span>
                @else
                  <span class="text-slate-300 text-xs">—</span>
                @endif
              </td>
              <td class="text-center">
                @if(($abs['peringatan'] ?? 0) > 0)
                  <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded-lg font-extrabold text-xs">⚠️{{ $abs['peringatan'] }}</span>
                @else
                  <span class="text-slate-300 text-xs">—</span>
                @endif
              </td>
              <td class="text-center">
                @if($poinSikap > 0)
                  <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded-lg font-extrabold text-xs">+{{ $poinSikap }}</span>
                @else
                  <span class="text-slate-300 text-xs">0</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center py-10 text-slate-400 italic">Belum ada siswa terdaftar di kelas ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Rekap Nilai Per Mapel Per Siswa --}}
  @if(!empty($rekap['nilai']))
  <div class="card overflow-hidden">
    <div class="card-header">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <i class="fa-solid fa-star-half-stroke text-indigo-600"></i> Rekapitulasi Nilai Siswa per Mata Pelajaran
      </h3>
      <a href="{{ route('grades.index', ['kelas' => $rekap['kelas'], 'mode' => 'leger']) }}"
         class="btn btn-outline text-xs px-3 py-1.5">
        <i class="fa-solid fa-table-list text-indigo-500"></i> Buka Leger Lengkap
      </a>
    </div>
    <div class="overflow-x-auto -mx-2 sm:mx-0">
      <table class="data-table min-w-[700px]">
        <thead>
          <tr>
            <th class="w-10 text-center">No</th>
            <th>Nama Siswa</th>
            @foreach(collect($rekap['nilai'])->flatMap(fn($v) => array_keys($v))->unique()->sort() as $mapelCol)
              <th class="text-center text-xs w-28" title="{{ $mapelCol }}">
                {{ Str::limit($mapelCol, 12) }}
              </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @php
            $mapelCols = collect($rekap['nilai'])->flatMap(fn($v) => array_keys($v))->unique()->sort()->values()->all();
          @endphp
          @forelse($rekap['siswa'] as $idx => $siswa)
            @php $nis = (string)$siswa->nis; @endphp
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="text-center text-slate-400 text-xs font-medium">{{ $idx + 1 }}</td>
              <td>
                <div class="font-bold text-slate-900 text-sm">{{ $siswa->nama }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ $siswa->nis }}</div>
              </td>
              @foreach($mapelCols as $mc)
                @php
                  $nv = $rekap['nilai'][$nis][$mc] ?? null;
                  $finalScore = $nv['final'] ?? null;
                  $scoreCls = $finalScore !== null
                    ? ($finalScore >= 80 ? 'text-emerald-700 font-extrabold' : ($finalScore >= 70 ? 'text-indigo-700 font-bold' : ($finalScore >= 60 ? 'text-amber-700 font-bold' : 'text-rose-700 font-bold')))
                    : 'text-slate-300';
                @endphp
                <td class="text-center {{ $scoreCls }}">
                  {{ $finalScore !== null ? number_format($finalScore, 1) : '—' }}
                </td>
              @endforeach
            </tr>
          @empty
            <tr><td colspan="20" class="text-center py-10 text-slate-400 italic">Belum ada data nilai.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="px-4 py-2 bg-slate-50/80 border-t border-slate-100">
      <p class="text-[10px] text-slate-400">
        ✅ Nilai sudah termasuk bonus Poin Sikap (+5 per Bintang Keaktifan). Warna: 
        <span class="text-emerald-700 font-bold">≥80 Sangat Baik</span> · 
        <span class="text-indigo-700 font-bold">≥70 Baik</span> · 
        <span class="text-amber-700 font-bold">≥60 Cukup</span> · 
        <span class="text-rose-700 font-bold">&lt;60 Perlu Bimbingan</span>
      </p>
    </div>
  </div>
  @endif

</div>
@endif

@endsection

@push('scripts')
@if(!empty($stats['kehadiranKelas']))
<script>
document.addEventListener('DOMContentLoaded', () => {
  const labels = {!! json_encode(collect($stats['kehadiranKelas'])->map(fn($i) => 'Kelas '.$i['kelas'])->all()) !!};
  const values = {!! json_encode(collect($stats['kehadiranKelas'])->pluck('rate')->all()) !!};

  new Chart(document.getElementById('attendanceChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: 'rgba(79,70,229,0.8)',
        borderColor: 'rgb(79,70,229)',
        borderWidth: 1.5,
        borderRadius: 8,
        maxBarThickness: 42,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true, max: 100,
          grid: { color: '#f1f5f9' },
          ticks: { callback: v => v + '%' }
        },
        x: { grid: { display: false } }
      }
    }
  });
});
</script>
@endif
@endpush
