<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SopLangkah extends Model
{
    protected $table = 'sop_langkah';
    protected $fillable = ['id_sop','urutan','judul_langkah','deskripsi','hasil_diharapkan'];

    public function sop() { return $this->belongsTo(Sop::class, 'id_sop'); }
    public function progress() { return $this->hasMany(SopProgress::class, 'id_sop_langkah'); }
}