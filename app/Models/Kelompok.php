<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelompok extends Model
{
    protected $table = 'kelompoks';

    protected $fillable = [
        'nama_kelompok',
        'tipe_lahan',
        'id_mentor',
        'id_komoditas',
        'deskripsi',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User (Mentor)
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_mentor');
    }

    /**
     * Relasi ke User (Anggota Kelompok) via tabel pivot kelompok_user
     */
    public function anggota(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'kelompok_user',
            'kelompok_id',
            'user_id'
        )->withPivot('peran')->withTimestamps();
    }

    /**
     * Relasi ke Komoditas
     */
    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }

    /**
     * Relasi ke Proposal
     */
    public function proposal(): HasMany
    {
        return $this->hasMany(Proposal::class, 'id_kelompok');
    }

    /**
     * Relasi ke Diskusi
     */
    public function diskusi(): HasMany
    {
        return $this->hasMany(Diskusi::class, 'id_kelompok');
    }

    /**
     * Relasi ke Lahan
     */
    public function lahan(): HasMany
    {
        return $this->hasMany(Lahan::class, 'id_kelompok');
    }

    /**
     * Relasi ke Aktivitas
     */
    public function aktivitas(): HasMany
    {
        return $this->hasMany(Aktivitas::class, 'id_kelompok');
    }

    /**
     * Helper: Cek apakah user adalah anggota
     */
    public function isAnggota(int $userId): bool
    {
        return $this->anggota()->where('user_id', $userId)->exists();
    }

    /**
     * Helper: Cek apakah user adalah mentor
     */
    public function isMentor(int $userId): bool
    {
        return $this->id_mentor === $userId;
    }

    /**
     * Helper: Cek apakah user memiliki akses ke kelompok
     */
    public function hasAccess(int $userId): bool
    {
        return $this->isMentor($userId) || $this->isAnggota($userId);
    }

    /**
     * Scope: Kelompok yang aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope: Kelompok berdasarkan mentor
     */
    public function scopeByMentor($query, int $mentorId)
    {
        return $query->where('id_mentor', $mentorId);
    }

    /**
     * Scope: Kelompok berdasarkan anggota
     */
    public function scopeByAnggota($query, int $userId)
    {
        return $query->whereHas('anggota', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}