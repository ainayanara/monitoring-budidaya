<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Lahan extends Model
{
    protected $table = 'lahan';

    protected $fillable = [
        'id_pengguna',
        'id_komoditas',
        'tipe_lahan',
        'jenis_lahan',
        'tanggal_mulai',
        'luas',
        'longitude',
        'latitude',
        'dokumentasi',
        'catatan_awal',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'luas' => 'float',
        'longitude' => 'float',
        'latitude' => 'float',
    ];

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function komoditas()
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }

    public function proposal()
    {
        return $this->hasMany(Proposal::class, 'id_lahan');
    }

    public function kalender()
    {
        return $this->hasMany(Kalender::class, 'id_lahan');
    }

    public function getEstimasiSelesaiAttribute()
    {
        if (!$this->tanggal_mulai) {
            return null;
        }

        $totalHari = (int) ($this->komoditas?->sop()->sum('estimasi_hari') ?? 0);

        if ($totalHari === 0) {
            return null;
        }

        return Carbon::parse($this->tanggal_mulai)
            ->addDays($totalHari)
            ->toDateString();
    }

    public function getUmurTanamAttribute()
    {
        if (!$this->tanggal_mulai) {
            return 0;
        }

        return Carbon::parse($this->tanggal_mulai)
            ->diffInDays(Carbon::today());
    }
}