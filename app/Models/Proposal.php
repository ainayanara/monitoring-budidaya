<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $table = 'proposal';

    protected $fillable = [
        'id_pengguna',
        'id_kelompok',
        'id_lahan',
        'judul',
        'nama_penyusun',
        'status',
        'status_rab',

        'luas_lahan',
        'jumlah_populasi',

        'latar_belakang',
        'maksud_tujuan',
        'waktu_tempat',
        'rencana_penelitian',

        'nama_tanaman',
        'perkiraan_panen_per_pohon',
        'total_panen_kg',
        'harga_satuan',
        'jarak_tanam',
        'masa_periode_tanam',
        'kesimpulan_analisis',

        'catatan_mentor',
        'catatan_rab_mentor',

        'nilai_proposal',
        'nilai_rab',

        'reviewed_by',
        'reviewed_rab_by',
    ];

    protected $casts = [
        'luas_lahan'                  => 'float',
        'jumlah_populasi'             => 'integer',
        'perkiraan_panen_per_pohon'   => 'float',
        'total_panen_kg'              => 'float',
        'harga_satuan'                => 'float',

        'nilai_proposal'              => 'float',
        'nilai_rab'                   => 'float',
    ];

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function kelompok()
    {
        return $this->belongsTo(Kelompok::class, 'id_kelompok');
    }

    public function lahan()
    {
        return $this->belongsTo(Lahan::class, 'id_lahan');
    }

    public function rab()
    {
        return $this->hasMany(Rab::class, 'id_proposal');
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function mentorRab()
    {
        return $this->belongsTo(User::class, 'reviewed_rab_by');
    }

    /**
     * Hitung metrik usaha otomatis dari item RAB + data panen proposal.
     */
    public function hitungKalkulasi(): array
    {
        $rabItems    = $this->rab;
        $totalBiaya  = $rabItems->sum('total');
        $totalPanen  = (float) ($this->total_panen_kg ?? 0);
        $hargaSatuan = (float) ($this->harga_satuan ?? 0);
        $pendapatan  = $totalPanen * $hargaSatuan;
        $laba        = $pendapatan - $totalBiaya;

        return [
            'total_biaya' => round($totalBiaya, 2),
            'pendapatan'  => round($pendapatan, 2),
            'laba'        => round($laba, 2),
            'bc_ratio'    => $totalBiaya > 0 ? round($pendapatan / $totalBiaya, 2) : 0,
            'bep_harga'   => $totalPanen > 0 ? round($totalBiaya / $totalPanen, 0) : 0,
            'bep_unit'    => $hargaSatuan > 0 ? round($totalBiaya / $hargaSatuan, 2) : 0,
        ];
    }

    public function canEditBy(User $user): bool
    {
        return $user->id === $this->id_pengguna
            && in_array($this->status, ['draft', 'revisi'], true);
    }
}