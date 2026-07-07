<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'nama_depan',
        'nama_belakang',
        'email',
        'password',
        'peran',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function kelompok()
    {
        return $this->belongsToMany(
            Kelompok::class,
            'kelompok_user',
            'user_id',
            'kelompok_id'
        )->withPivot('peran')->withTimestamps();
    }

    public function kelompokDibimbing()
    {
        return $this->hasMany(Kelompok::class, 'id_mentor');
    }

    public function kalender()
    {
        return $this->hasMany(Kalender::class, 'id_pengguna');
    }

    public function proposal()
    {
        return $this->hasMany(Proposal::class, 'id_pengguna');
    }
}
