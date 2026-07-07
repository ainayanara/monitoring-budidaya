<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kalender extends Model
{
    protected $table = 'kalender';

    protected $fillable = [
        'id_pengguna',
        'id_komoditas',
        'id_lahan',
        'nama_tahapan',
        'nama_kegiatan',
        'tipe_lahan',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function komoditas()
    {
        return $this->belongsTo(Komoditas::class, 'id_komoditas');
    }

    public function lahan()
    {
        return $this->belongsTo(Lahan::class, 'id_lahan');
    }

    public function aktivitas()
    {
        return $this->hasMany(Aktivitas::class, 'id_kalender');
    }
}
