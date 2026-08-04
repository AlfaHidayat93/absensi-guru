<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'id_absen', 'kelas', 'semester', 'tanggal',
        'jam_mulai', 'jam_selesai', 'mata_pelajaran', 'guru', 'guru_id',
        'materi_pembelajaran', 'catatan_kelas', 'detail_kehadiran',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'           => 'date',
            'detail_kehadiran'  => 'array',
        ];
    }
}
