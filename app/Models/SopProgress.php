<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopProgress extends Model
{
    protected $table = 'sop_progress';

    protected $fillable = ['id_pengguna', 'id_sop_langkah', 'selesai'];

    protected $casts = [
        'selesai' => 'boolean',
    ];

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function langkah()
    {
        return $this->belongsTo(SopLangkah::class, 'id_sop_langkah');
    }
}
