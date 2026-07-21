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
