<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rab extends Model
{
    protected $table = 'rab';

    protected $fillable = [
        'id_proposal',
        'jenis_biaya',
        'nama_item',
        'satuan',
        'volume',
        'harga',
        'total',
    ];

    protected $casts = [
        'volume' => 'float',
        'harga'  => 'float',
        'total'  => 'float',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'id_proposal');
    }

    /**
     * Total per item dihitung otomatis: volume × harga.
     */
    public static function hitungTotal(float $volume, float $harga): float
    {
        return round($volume * $harga, 2);
    }
}
