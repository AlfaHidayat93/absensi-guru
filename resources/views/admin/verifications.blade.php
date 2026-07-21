@extends('layouts.app')

@section('title', 'Verifikasi Guru')
@section('page-title', 'Verifikasi Pendaftaran Guru')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
  <div class="p-6 border-b border-slate-100 flex items-center justify-between">
    <div>
      <h3 class="font-bold text-slate-800 text-lg">Daftar Menunggu Verifikasi</h3>
      <p class="text-sm text-slate-500 mt-1">Guru yang mendaftar akan tampil di sini sebelum bisa login ke aplikasi.</p>
    </div>
    <div class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-sm">
      Total: {{ $pendingUsers->count() }}
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm text-slate-600">
      <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
        <tr>
          <th class="px-6 py-4">No</th>
          <th class="px-6 py-4">Nama Lengkap & Gelar</th>
          <th class="px-6 py-4">Email</th>
          <th class="px-6 py-4">NIP</th>
          <th class="px-6 py-4">Mata Pelajaran</th>
          <th class="px-6 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($pendingUsers as $index => $user)
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-medium text-slate-900">{{ $index + 1 }}</td>
            <td class="px-6 py-4 font-bold text-slate-800">{{ $user->name }}</td>
            <td class="px-6 py-4">
              <span class="px-2.5 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold">
                {{ $user->email }}
              </span>
            </td>
            <td class="px-6 py-4">{{ $user->nip ?: '-' }}</td>
            <td class="px-6 py-4">{{ $user->subjects }}</td>
            <td class="px-6 py-4 text-center">
              <form action="{{ route('admin.verifications.verify', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memverifikasi guru ini?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 text-white text-xs font-bold rounded-lg hover:bg-emerald-600 transition-colors shadow-sm shadow-emerald-500/20">
                  <i class="fa-solid fa-check"></i>
                  Verifikasi
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
                <i class="fa-solid fa-check-double text-xl"></i>
              </div>
              <p class="font-semibold">Tidak ada pendaftar baru</p>
              <p class="text-xs mt-1">Semua guru telah diverifikasi.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
