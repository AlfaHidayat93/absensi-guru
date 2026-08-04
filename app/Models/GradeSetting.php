<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeSetting extends Model
{
    protected $fillable = [
        'kelas',
        'semester',
        'mata_pelajaran',
        'tasks',
    ];

    protected $casts = [
        'tasks' => 'array',
    ];

    public static function defaultTasks(): array
    {
        return [
            ['id' => 'tugas_1', 'name' => 'Tugas 1'],
            ['id' => 'tugas_2', 'name' => 'Tugas 2'],
            ['id' => 'tugas_3', 'name' => 'Tugas 3'],
        ];
    }
}
