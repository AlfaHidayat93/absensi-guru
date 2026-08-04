<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'nip', 'subjects', 'status', 'homeroom_class', 'assigned_classes', 'assigned_subjects'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'assigned_classes'   => 'array',
            'assigned_subjects'  => 'array',
        ];
    }

    /**
     * Cek apakah user adalah Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Cek apakah user adalah Wali Kelas.
     */
    public function isWaliKelas(): bool
    {
        return $this->role === 'wali_kelas';
    }

    /**
     * Cek apakah user adalah Guru.
     */
    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    /**
     * Kelas binaan (hanya untuk Wali Kelas).
     */
    public function getHomeroomClass(): ?string
    {
        return $this->homeroom_class;
    }

    /**
     * Daftar kelas yang boleh diakses user ini.
     * Super Admin: semua kelas. Wali Kelas: kelas binaan + assigned. Guru: assigned saja.
     */
    public function getAccessibleClasses(array $allClasses = []): array
    {
        if ($this->isSuperAdmin()) {
            return $allClasses;
        }

        $classes = $this->assigned_classes ?? [];

        // Wali Kelas mendapat akses otomatis ke kelas binaannya
        if ($this->isWaliKelas() && $this->homeroom_class) {
            $classes[] = $this->homeroom_class;
        }

        $classes = array_values(array_unique($classes));

        // Filter hanya kelas yang benar-benar ada
        if (!empty($allClasses)) {
            $classes = array_values(array_intersect($classes, $allClasses));
        }

        sort($classes);
        return $classes;
    }

    /**
     * Daftar mata pelajaran yang boleh diakses user ini.
     * Super Admin: semua mapel. Guru/Wali Kelas: assigned saja.
     */
    public function getAccessibleSubjects(array $allSubjects = []): array
    {
        if ($this->isSuperAdmin()) {
            return $allSubjects;
        }

        $subjects = $this->assigned_subjects ?? [];

        // Filter hanya mapel yang benar-benar ada
        if (!empty($allSubjects)) {
            $subjects = array_values(array_intersect($subjects, $allSubjects));
        }

        sort($subjects);
        return $subjects;
    }

    /**
     * Cek apakah user boleh mengakses kelas tertentu.
     */
    public function canAccessClass(string $class): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isWaliKelas() && $this->homeroom_class === $class) {
            return true;
        }

        return in_array($class, $this->assigned_classes ?? []);
    }

    /**
     * Cek apakah user boleh mengakses mata pelajaran tertentu.
     */
    public function canAccessSubject(string $subject): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($subject, $this->assigned_subjects ?? []);
    }

    /**
     * Label role yang ramah pengguna.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'wali_kelas'  => 'Wali Kelas',
            'guru'        => 'Guru',
            default       => ucfirst($this->role),
        };
    }
}
