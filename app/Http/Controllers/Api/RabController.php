<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Rab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RabController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'id_proposal' => 'required|exists:proposal,id',
        ]);

        $proposal = Proposal::with('rab')->findOrFail($request->id_proposal);

        if ($response = $this->authorizeAksesRab($request, $proposal)) {
            return $response;
        }

        $rab = $proposal->rab;

        return response()->json([
            'status' => 'success',
            'data' => $rab,
            'total_rab' => round($rab->sum('total'), 2),
            'kalkulasi' => $proposal->hitungKalkulasi(),
            'status_rab' => $proposal->status_rab,
            'nilai_rab' => $proposal->nilai_rab,
            'catatan_rab_mentor' => $proposal->catatan_rab_mentor,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_proposal' => 'required|exists:proposal,id',
            'jenis_biaya' => 'required|in:tetap,tidak_tetap',
            'nama_item' => 'required|string|max:150',
            'satuan' => 'required|string|max:50',
            'volume' => 'required|numeric|min:0.01',
            'harga' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $proposal = Proposal::findOrFail($request->id_proposal);

        if ($proposal->id_pengguna !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan pemilik proposal ini.',
            ], 403);
        }

        if (!in_array($proposal->status, ['draft', 'revisi'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => "Proposal tidak dapat diubah karena status saat ini adalah '{$proposal->status}'.",
            ], 403);
        }

        $total = Rab::hitungTotal((float) $request->volume, (float) $request->harga);

        $rab = Rab::create([
            'id_proposal' => $request->id_proposal,
            'jenis_biaya' => $request->jenis_biaya,
            'nama_item' => $request->nama_item,
            'satuan' => $request->satuan,
            'volume' => $request->volume,
            'harga' => $request->harga,
            'total' => $total,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Item RAB tersimpan',

            'data' => [
                'rab' => $rab,
                'total_rab' => round(
                    $proposal->fresh('rab')->rab->sum('total'),
                    2
                ),
            ]
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $rab = Rab::with('proposal')->findOrFail($id);

        if ($response = $this->authorizeAksesRab($request, $rab->proposal)) {
            return $response;
        }

        return response()->json([
            'status' => 'success',
            'data' => $rab,
        ]);
    }

    public function update(Request $request, $id)
    {
        $rab = Rab::with('proposal')->findOrFail($id);

        if ($response = $this->authorizeAksesRab($request, $rab->proposal)) {
            return $response;
        }

        if (!$rab->proposal->canEditBy($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'RAB hanya bisa diubah saat proposal masih draft atau revisi',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'jenis_biaya' => 'sometimes|in:tetap,tidak_tetap',
            'nama_item' => 'sometimes|string|max:150',
            'satuan' => 'sometimes|string|max:50',
            'volume' => 'sometimes|numeric|min:0.01',
            'harga' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rab->fill($request->only(['jenis_biaya', 'nama_item', 'satuan', 'volume', 'harga']));
        $rab->total = Rab::hitungTotal(
            (float) ($rab->volume ?? 0),
            (float) ($rab->harga ?? 0)
        );
        $rab->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Item RAB diperbarui',
            'data' => $rab,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $rab = Rab::with('proposal')->findOrFail($id);

        if ($response = $this->authorizeAksesRab($request, $rab->proposal)) {
            return $response;
        }

        if (!$rab->proposal->canEditBy($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'RAB hanya bisa dihapus saat proposal masih draft atau revisi',
            ], 403);
        }

        $rab->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Item RAB dihapus',
        ]);
    }

    /**
     * Pastikan akses ke RAB (via proposal induknya) sah:
     * - Mentor hanya boleh akses RAB milik kelompok yang ia bimbing.
     * - Siswa hanya boleh akses RAB dari proposal miliknya sendiri.
     */
    private function authorizeAksesRab(Request $request, Proposal $proposal)
    {
        $user = $request->user();

        if ($user->peran === 'mentor') {
            $kelompok = $proposal->kelompok;

            if (!$kelompok || (int) $kelompok->id_mentor !== (int) $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda bukan mentor pembimbing kelompok pemilik RAB ini.',
                ], 403);
            }

            return null;
        }

        if ((int) $proposal->id_pengguna !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak berhak mengakses RAB ini.',
            ], 403);
        }

        return null;
    }
}
