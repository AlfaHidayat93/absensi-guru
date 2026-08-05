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
    /**
     * Daftar kelas yang boleh diakses user ini.
     * Super Admin: semua kelas. Wali Kelas: kelas binaan + assigned. Guru: assigned saja.
     */
    public function getAccessibleClasses(array $allClasses = []): array
    {
        if ($this->isSuperAdmin()) {
            return $allClasses;
        }

        $rawList = $this->assigned_classes ?? [];
        if ($this->isWaliKelas() && $this->homeroom_class) {
            $rawList[] = $this->homeroom_class;
            $cleanHomeroom = trim(preg_replace('/^Wali\s+/i', '', $this->homeroom_class));
            $rawList[] = $cleanHomeroom;
        }

        $rawList = array_values(array_unique(array_filter(array_map('trim', $rawList))));

        if (empty($allClasses)) {
            return $rawList;
        }

        // Matching cerdas dengan $allClasses (fleksibel terhadap tanda hubung/spasi/kasus huruf)
        $matched = [];
        foreach ($allClasses as $realClass) {
            $normReal = strtolower(str_replace(['-', ' ', '_'], '', $realClass));
            foreach ($rawList as $userCls) {
                $normUser = strtolower(str_replace(['-', ' ', '_'], '', preg_replace('/^Wali\s+/i', '', $userCls)));
                if ($normReal === $normUser || strtolower(trim($realClass)) === strtolower(trim($userCls))) {
                    $matched[] = $realClass;
                    break;
                }
            }
        }

        // Fallback ke rawList jika belum ada siswa terdaftar di DB
        if (empty($matched)) {
            $matched = $rawList;
        }

        sort($matched);
        return array_values(array_unique($matched));
    }

    /**
     * Daftar mata pelajaran yang boleh diakses user ini.
     * Super Admin & Wali Kelas: SEMUA mapel (Wali Kelas memantau seluruh mapel kelas binaannya).
     * Guru: assigned saja.
     */
    public function getAccessibleSubjects(array $allSubjects = []): array
    {
        if ($this->isSuperAdmin() || $this->isWaliKelas()) {
            return $allSubjects;
        }

        $subjects = $this->assigned_subjects ?? [];
        if (!empty($allSubjects)) {
            $matched = [];
            foreach ($allSubjects as $realSubject) {
                $normReal = strtolower(trim($realSubject));
                foreach ($subjects as $us) {
                    if (strtolower(trim($us)) === $normReal) {
                        $matched[] = $realSubject;
                        break;
                    }
                }
            }
            $subjects = !empty($matched) ? $matched : $subjects;
        }

        sort($subjects);
        return array_values(array_unique($subjects));
    }

    /**
     * Cek apakah user boleh mengakses kelas tertentu.
     */
    public function canAccessClass(string $class): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $normTarget = strtolower(str_replace(['-', ' ', '_'], '', $class));

        if ($this->isWaliKelas() && $this->homeroom_class) {
            $normHome = strtolower(str_replace(['-', ' ', '_'], '', preg_replace('/^Wali\s+/i', '', $this->homeroom_class)));
            if ($normTarget === $normHome) {
                return true;
            }
        }

        foreach ($this->assigned_classes ?? [] as $ac) {
            $normAc = strtolower(str_replace(['-', ' ', '_'], '', preg_replace('/^Wali\s+/i', '', $ac)));
            if ($normTarget === $normAc) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah user boleh mengakses mata pelajaran tertentu.
     */
    public function canAccessSubject(string $subject): bool
    {
        if ($this->isSuperAdmin() || $this->isWaliKelas()) {
            return true;
        }

        $normTarget = strtolower(trim($subject));
        foreach ($this->assigned_subjects ?? [] as $as) {
            if (strtolower(trim($as)) === $normTarget) {
                return true;
            }
        }

        return false;
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
