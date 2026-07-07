<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diskusi extends Model
{
    protected $table = 'diskusi';

    protected $fillable = [
        'id_kelompok',
        'id_pengguna',
        'id_parent',
        'judul',
        'pesan',
    ];

    public function pengirim()
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function kelompok()
    {
        return $this->belongsTo(Kelompok::class, 'id_kelompok');
    }

    public function parent()
    {
        return $this->belongsTo(Diskusi::class, 'id_parent');
    }

    public function replies()
    {
        return $this->hasMany(Diskusi::class, 'id_parent');
    }

    public function isThread(): bool
    {
        return is_null($this->id_parent);
    }
}
