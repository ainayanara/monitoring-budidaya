<?php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Komoditas extends Model
{
    protected $table = 'komoditas';
 
    protected $fillable = ['nama_komoditas'];
 
    // PERBAIKAN: SOP::class → Sop::class (harus konsisten dengan nama file)
    public function sop()
    {
        return $this->hasMany(Sop::class, 'id_komoditas');
    }
 
    public function lahan()
    {
        return $this->hasMany(Lahan::class, 'id_komoditas');
    }
}