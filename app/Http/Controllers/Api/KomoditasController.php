<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Komoditas;
use Illuminate\Http\Request;

class KomoditasController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Daftar komoditas berhasil diambil',
            'data' => Komoditas::all(),
        ]);
    }
}
