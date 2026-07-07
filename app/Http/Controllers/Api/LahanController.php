<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LahanController extends Controller
{
    /**
     * GET /api/lahan
     * List lahan milik user yang login (tidak bergantung kelompok/mentor)
     */
    public function index(Request $request)
    {
        $query = Lahan::with('komoditas')
            ->where('id_pengguna', $request->user()->id);

        if ($request->filled('jenis_lahan')) {
            $query->where('jenis_lahan', $request->jenis_lahan);
        }

        $lahan = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $lahan->map(fn ($l) => $this->formatLahan($l)),
        ]);
    }

    /**
     * POST /api/lahan
     * Tambah lahan baru
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_komoditas'  => 'required|exists:komoditas,id',
                'tipe_lahan'    => 'required|in:greenhouse,open_field',
                'jenis_lahan'   => 'required|in:kelompok,individu',
                'tanggal_mulai' => 'required|date',
                'luas'          => 'required|numeric|min:0.01',
                'longitude'     => 'required|numeric',
                'latitude'      => 'required|numeric',
                'dokumentasi'   => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $lahan = Lahan::create([
                'id_pengguna'   => $request->user()->id,
                'id_komoditas'  => $request->id_komoditas,
                'tipe_lahan'    => $request->tipe_lahan,
                'jenis_lahan'   => $request->jenis_lahan,
                'tanggal_mulai' => $request->tanggal_mulai,
                'luas'          => $request->luas,
                'longitude'     => $request->longitude,
                'latitude'      => $request->latitude,
                'catatan_awal'  => $request->catatan_awal,
            ]);

            if ($request->hasFile('dokumentasi')) {
                $lahan->dokumentasi = $request->file('dokumentasi')
                    ->store('lahan/dokumentasi', 'public');
                $lahan->save();
            }

            $lahan->load('komoditas');

            return response()->json([
                'status' => 'success',
                'data'   => $this->formatLahan($lahan),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }

    /**
     * GET /api/lahan/{id}
     * Detail lahan
     */
    public function show(Request $request, $id)
    {
        $lahan = Lahan::with('komoditas')
            ->where('id_pengguna', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatLahan($lahan),
        ]);
    }

    /**
     * PUT /api/lahan/{id}
     * Update lahan
     */
    public function update(Request $request, $id)
    {
        $lahan = Lahan::where('id_pengguna', $request->user()->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'id_komoditas'  => 'sometimes|exists:komoditas,id',
            'tipe_lahan'    => 'sometimes|in:greenhouse,open_field',
            'tanggal_mulai' => 'sometimes|date',
            'luas'          => 'sometimes|numeric|min:0.01',
            'catatan_awal'  => 'nullable|string',
            'dokumentasi'   => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->hasFile('dokumentasi')) {
            if ($lahan->dokumentasi) {
                Storage::disk('public')->delete($lahan->dokumentasi);
            }
            $lahan->dokumentasi = $request->file('dokumentasi')
                ->store('lahan/dokumentasi', 'public');
        }

        $lahan->fill($request->only([
            'id_komoditas', 'tipe_lahan', 'tanggal_mulai', 'luas', 'catatan_awal',
        ]));
        $lahan->save();
        $lahan->load('komoditas');

        return response()->json([
            'status'  => 'success',
            'message' => 'Lahan berhasil diperbarui.',
            'data'    => $this->formatLahan($lahan),
        ]);
    }

    /**
     * DELETE /api/lahan/{id}
     */
    public function destroy(Request $request, $id)
    {
        $lahan = Lahan::where('id_pengguna', $request->user()->id)
            ->findOrFail($id);

        if ($lahan->dokumentasi) {
            Storage::disk('public')->delete($lahan->dokumentasi);
        }

        $lahan->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Lahan berhasil dihapus.',
        ]);
    }

    /**
     * Helper: Format data lahan untuk response
     */
    private function formatLahan(Lahan $lahan): array
    {
        return [
            'id'               => $lahan->id,
            'id_komoditas'     => $lahan->id_komoditas,
            'komoditas'        => $lahan->komoditas?->nama_komoditas ?? '-',
            'tipe_lahan'       => $lahan->tipe_lahan,
            'jenis_lahan'      => $lahan->jenis_lahan,
            'tanggal_mulai'    => $lahan->tanggal_mulai?->toDateString(),
            'luas'             => (float) $lahan->luas,
            'latitude'         => (float) $lahan->latitude,
            'longitude'        => (float) $lahan->longitude,
            'umur_tanam'       => $lahan->umur_tanam,
            'estimasi_selesai' => $lahan->estimasi_selesai,
            'catatan_awal'     => $lahan->catatan_awal,
            // FIX: syntax error — was using ? (nullsafe) instead of ternary
            'dokumentasi'      => $lahan->dokumentasi
                ? asset('storage/' . $lahan->dokumentasi)
                : null,
            'created_at'       => $lahan->created_at?->toDateTimeString(),
        ];
    }
}
