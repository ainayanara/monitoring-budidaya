<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /api/users/mentor
     * List semua user berperan mentor.
     * Dipakai untuk dropdown "Pilih Mentor" di form Tambah Kelompok.
     */
    public function listMentor()
    {
        $mentor = User::where('peran', 'mentor')
            ->orderBy('nama_depan')
            ->get(['id', 'nama_depan', 'nama_belakang', 'username']);

        return response()->json([
            'status' => 'success',
            'data'   => $mentor,
        ]);
    }

    /**
     * GET /api/users/siswa-tersedia
     * List siswa yang BELUM tergabung di kelompok manapun.
     * Dipakai untuk dropdown "Pilih Anggota" di form Tambah Kelompok
     * (ketua hanya boleh memilih dari siswa yang belum punya kelompok).
     *
     * Catatan: siswa yang sedang login (request user) ikut tampil di sini
     * jika dia juga belum punya kelompok — frontend bisa exclude dia sendiri
     * karena dia otomatis jadi ketua.
     */
    public function listSiswaTersedia(Request $request)
    {
        $idSudahBerkelompok = Kelompok::with('anggota:id')
            ->get()
            ->flatMap(fn ($k) => $k->anggota->pluck('id'))
            ->unique()
            ->values();

        $siswa = User::where('peran', 'siswa')
            ->whereNotIn('id', $idSudahBerkelompok)
            ->orderBy('nama_depan')
            ->get(['id', 'nama_depan', 'nama_belakang', 'username']);

        return response()->json([
            'status' => 'success',
            'data'   => $siswa,
        ]);
    }
}