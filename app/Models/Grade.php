<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'nis', 'kelas', 'semester', 'mata_pelajaran',
        'tugas_1', 'tugas_2', 'tugas_3', 'pts', 'pas', 'praktik',
    ];
}
