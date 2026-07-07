<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sop extends Model
{
    use HasFactory;

    protected $table = 'sop';  // ← tambahkan ini
    
    protected $fillable = [
        'id_komoditas',
        'nama_tahapan', 
        'estimasi_hari',
        'deskripsi',
    ];

    public function langkah()
    {
        return $this->hasMany(SopLangkah::class, 'id_sop');
    }
}