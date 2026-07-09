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
        'laba',
        'bep_unit',
        'bep_harga',
        'b_c_ratio',
        'r_c_ratio',
    ];

    protected $casts = [
        'volume'    => 'float',
        'harga'     => 'float',
        'total'     => 'float',
        'laba'      => 'float',
        'bep_unit'  => 'float',
        'bep_harga' => 'float',
        'b_c_ratio' => 'float',
        'r_c_ratio' => 'float',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'id_proposal');
    }

    /**
     * Hitung total biaya per item.
     */
    public static function hitungTotal(float $volume, float $harga): float
    {
        return round($volume * $harga, 2);
    }
}