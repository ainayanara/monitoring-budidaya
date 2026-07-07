<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KelompokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            Log::info('User ' . $user->id . ' (' . $user->peran . ') mengakses daftar kelompok');

            if ($user->peran === 'mentor') {
                // Mentor: lihat kelompok yang dibimbing
                $kelompoks = Kelompok::with(['mentor', 'anggota', 'komoditas'])
                    ->where('id_mentor', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // Siswa: lihat kelompok yang diikuti
                $kelompoks = Kelompok::with(['mentor', 'anggota', 'komoditas'])
                    ->whereHas('anggota', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->orWhere('id_mentor', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data kelompok berhasil diambil',
                'data' => $kelompoks
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            // Validasi
            $validator = Validator::make($request->all(), [
                'nama_kelompok' => 'required|string|max:255',
                'tipe_lahan' => 'nullable|string|max:100',
                'id_komoditas' => 'nullable|exists:komoditas,id',
                'deskripsi' => 'nullable|string',
                'id_mentor' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Jika user adalah siswa, cari mentor default atau gunakan yang dipilih
            $idMentor = $request->id_mentor;
            
            if ($user->peran === 'siswa' && !$idMentor) {
                // Cari mentor dengan kelompok paling sedikit
                $mentor = User::where('role', 'mentor')
                    ->withCount('kelompok')
                    ->orderBy('kelompok_count')
                    ->first();
                    
                if ($mentor) {
                    $idMentor = $mentor->id;
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Belum ada mentor yang tersedia'
                    ], 400);
                }
            }

            // Buat kelompok
            $kelompok = Kelompok::create([
                'nama_kelompok' => $request->nama_kelompok,
                'tipe_lahan' => $request->tipe_lahan ?? 'open_field',
                'id_mentor' => $idMentor,
                'id_komoditas' => $request->id_komoditas,
                'deskripsi' => $request->deskripsi,
                'status' => 'aktif',
            ]);

            // Tambahkan user sebagai ketua
            $kelompok->anggota()->attach($user->id, ['peran' => 'ketua']);

            // Load relasi
            $kelompok->load(['mentor', 'anggota', 'komoditas']);

            Log::info('Kelompok berhasil dibuat oleh user ' . $user->id . ' dengan ID ' . $kelompok->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Kelompok berhasil dibuat',
                'data' => $kelompok
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::store: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            
            $kelompok = Kelompok::with(['mentor', 'anggota', 'komoditas', 'lahan', 'proposal'])
                ->find($id);

            if (!$kelompok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelompok tidak ditemukan'
                ], 404);
            }

            // Cek akses
            if (!$kelompok->hasAccess($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke kelompok ini'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Detail kelompok berhasil diambil',
                'data' => $kelompok
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::show: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat detail kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            $kelompok = Kelompok::find($id);
            
            if (!$kelompok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelompok tidak ditemukan'
                ], 404);
            }

            // Hanya mentor atau ketua yang bisa update
            $isKetua = $kelompok->anggota()
                ->where('user_id', $user->id)
                ->where('peran', 'ketua')
                ->exists();
                
            if ($user->peran !== 'mentor' && !$isKetua) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk mengupdate kelompok ini'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_kelompok' => 'sometimes|string|max:255',
                'tipe_lahan' => 'nullable|string|max:100',
                'id_komoditas' => 'nullable|exists:komoditas,id',
                'deskripsi' => 'nullable|string',
                'status' => 'nullable|in:aktif,nonaktif',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $kelompok->update($request->only([
                'nama_kelompok',
                'tipe_lahan',
                'id_komoditas',
                'deskripsi',
                'status'
            ]));

            $kelompok->load(['mentor', 'anggota', 'komoditas']);

            Log::info('Kelompok ' . $id . ' diupdate oleh user ' . $user->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Kelompok berhasil diupdate',
                'data' => $kelompok
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::update: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            $kelompok = Kelompok::find($id);
            
            if (!$kelompok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelompok tidak ditemukan'
                ], 404);
            }

            // Hanya mentor yang bisa hapus
            if ($user->peran !== 'mentor') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk menghapus kelompok ini'
                ], 403);
            }

            // Hapus relasi di pivot
            $kelompok->anggota()->detach();
            
            // Hapus kelompok
            $kelompok->delete();

            Log::info('Kelompok ' . $id . ' dihapus oleh user ' . $user->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Kelompok berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::destroy: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Custom: Buat kelompok sederhana (untuk siswa)
     */
 public function storeSimple(Request $request)
{
    try {
        $user = auth()->user();

        if ($user->peran !== 'siswa') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya siswa yang bisa membuat kelompok'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_kelompok' => 'required|string|max:255',
            'id_komoditas' => 'nullable|exists:komoditas,id',
            'id_mentor' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::where('id', $value)
                        ->where('peran', 'mentor')
                        ->first();
                    if (!$user) {
                        $fail('Mentor tidak ditemukan atau bukan mentor.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $existing = Kelompok::whereHas('anggota', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->exists();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah memiliki kelompok'
            ], 400);
        }

        $kelompok = Kelompok::create([
            'nama_kelompok' => $request->nama_kelompok,
            // ✅ FIX: Ambil dari request, default 'open_field'
            'tipe_lahan' => $request->tipe_lahan ?? 'open_field',
            'id_mentor' => $request->id_mentor,
            'id_komoditas' => $request->id_komoditas,
            'status' => 'aktif',
        ]);

        $kelompok->anggota()->attach($user->id, ['peran' => 'ketua']);

        $kelompok->load(['mentor', 'anggota', 'komoditas']);

        Log::info('Kelompok simple dibuat oleh siswa ' . $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelompok berhasil dibuat',
            'data' => $kelompok
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error di KelompokController::storeSimple: ' . $e->getMessage());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal membuat kelompok: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Custom: Daftar kelompok yang tersedia untuk bergabung
     */
    public function tersedia(Request $request)
    {
        try {
            $user = auth()->user();

            // Cari kelompok yang memiliki kurang dari 5 anggota
            $kelompoks = Kelompok::with(['mentor', 'komoditas'])
                ->withCount('anggota')
                ->having('anggota_count', '<', 5)
                ->where('status', 'aktif')
                ->orderBy('created_at', 'desc')
                ->get();

            // Filter kelompok yang sudah diikuti user
            $userKelompokIds = Kelompok::whereHas('anggota', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->pluck('id')->toArray();

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar kelompok tersedia',
                'data' => $kelompoks,
                'user_kelompok_ids' => $userKelompokIds
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::tersedia: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat kelompok tersedia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Custom: Gabung ke kelompok
     */
    public function gabung(Request $request, $id)
    {
        try {
            $user = auth()->user();

            if ($user->peran !== 'siswa') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya siswa yang bisa bergabung ke kelompok'
                ], 403);
            }

            $kelompok = Kelompok::withCount('anggota')->find($id);

            if (!$kelompok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelompok tidak ditemukan'
                ], 404);
            }

            // Cek apakah sudah bergabung
            if ($kelompok->isAnggota($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah bergabung dengan kelompok ini'
                ], 400);
            }

            // Cek kuota (max 5 anggota)
            if ($kelompok->anggota_count >= 5) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelompok sudah penuh (maksimal 5 anggota)'
                ], 400);
            }

            // Cek apakah user sudah punya kelompok lain
            $existing = Kelompok::whereHas('anggota', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->exists();

            if ($existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah memiliki kelompok lain'
                ], 400);
            }

            // Bergabung sebagai anggota
            $kelompok->anggota()->attach($user->id, ['peran' => 'anggota']);

            Log::info('User ' . $user->id . ' bergabung ke kelompok ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil bergabung ke kelompok',
                'data' => $kelompok->load(['mentor', 'anggota', 'komoditas'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::gabung: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal bergabung ke kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Custom: Progress kelompok
     */
    public function progress($id)
    {
        try {
            $user = auth()->user();
            
            $kelompok = Kelompok::with(['lahan', 'proposal'])->find($id);

            if (!$kelompok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kelompok tidak ditemukan'
                ], 404);
            }

            if (!$kelompok->hasAccess($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            // Hitung progress
            $totalLangkah = 0;
            $selesaiLangkah = 0;

            // Progress dari SOP
            // ...

            return response()->json([
                'status' => 'success',
                'message' => 'Progress kelompok berhasil diambil',
                'data' => [
                    'total_langkah' => $totalLangkah,
                    'selesai_langkah' => $selesaiLangkah,
                    'persentase' => $totalLangkah > 0 ? round(($selesaiLangkah / $totalLangkah) * 100) : 0,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error di KelompokController::progress: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat progress kelompok',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}