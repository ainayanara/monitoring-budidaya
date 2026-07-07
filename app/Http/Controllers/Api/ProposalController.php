<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Rab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Proposal::with(['pengguna', 'kelompok', 'lahan.komoditas', 'rab']);

        if ($user->peran === 'mentor') {
            $kelompokIds = $user->kelompokDibimbing()->pluck('id');
            $query->whereIn('id_kelompok', $kelompokIds);
        } else {
            $query->where('id_pengguna', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $proposal = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $proposal,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->proposalRules());

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $proposal = Proposal::create([
            'id_pengguna'               => $user->id,
            'id_kelompok'               => $request->id_kelompok,
            'id_lahan'                  => $request->id_lahan,
            'judul'                     => $request->judul,
            'nama_penyusun'             => $user->nama_depan . ' ' . $user->nama_belakang,
            'status'                    => 'draft',
            'status_rab'                => 'draft',
            'luas_lahan'                => $request->luas_lahan,
            'jumlah_populasi'           => $request->jumlah_populasi,
            'latar_belakang'            => $request->latar_belakang,
            'maksud_tujuan'             => $request->maksud_tujuan,
            'waktu_tempat'              => $request->waktu_tempat,
            'rencana_penelitian'        => $request->rencana_penelitian,
            'nama_tanaman'              => $request->nama_tanaman,
            'perkiraan_panen_per_pohon' => $request->perkiraan_panen_per_pohon,
            'total_panen_kg'            => $request->total_panen_kg,
            'harga_satuan'              => $request->harga_satuan,
            'jarak_tanam'               => $request->jarak_tanam,
            'masa_periode_tanam'        => $request->masa_periode_tanam,
            'kesimpulan_analisis'       => $request->kesimpulan_analisis,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal disimpan sebagai draft',
            'data'    => $proposal,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $proposal = Proposal::with(['pengguna', 'kelompok', 'lahan', 'rab'])->findOrFail($id);

        if ($response = $this->authorizeAksesProposal($request, $proposal)) {
            return $response;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail proposal berhasil diambil',

            'data' => [
                'proposal' => $proposal,
                'rab' => $proposal->rab,
                'kalkulasi' => $proposal->hitungKalkulasi(),
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        if (!$proposal->canEditBy($request->user())) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Proposal hanya bisa diedit saat status draft atau revisi',
            ], 403);
        }

        $validator = Validator::make($request->all(), $this->proposalRules(isUpdate: true));

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $proposal->fill($request->only([
            'id_kelompok',
            'id_lahan',
            'judul',
            'luas_lahan',
            'jumlah_populasi',
            'latar_belakang',
            'maksud_tujuan',
            'waktu_tempat',
            'rencana_penelitian',
            'nama_tanaman',
            'perkiraan_panen_per_pohon',
            'total_panen_kg',
            'harga_satuan',
            'jarak_tanam',
            'masa_periode_tanam',
            'kesimpulan_analisis',
        ]));
        $proposal->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal berhasil diperbarui',
            'data'    => $proposal->fresh(['rab', 'lahan']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        if ($proposal->id_pengguna !== $request->user()->id || $proposal->status !== 'draft') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya proposal draft milik sendiri yang bisa dihapus',
            ], 403);
        }

        $proposal->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal draft berhasil dihapus',
        ]);
    }

    /**
     * POST /api/proposal/{id}/submit — kirim proposal ke mentor
     */
    public function submit(Request $request, $id)
    {
        $proposal = Proposal::with('rab')->findOrFail($id);

        if ($proposal->id_pengguna !== $request->user()->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Proposal bukan milik Anda',
            ], 403);
        }

        if (!in_array($proposal->status, ['draft', 'revisi'], true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Proposal sudah dikirim ke mentor',
            ], 422);
        }

        $proposal->update([
            'status'     => 'pending',
            'status_rab' => $proposal->rab->isNotEmpty() ? 'pending' : 'draft',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal berhasil dikirim ke mentor',
            'data'    => $proposal,
        ]);
    }

    public function review(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:disetujui,revisi,ditolak',
            'catatan_mentor' => 'nullable|string',
        ]);

        $proposal = Proposal::findOrFail($id);

        if ($response = $this->authorizeAksesProposal($request, $proposal)) {
            return $response;
        }

        $proposal->update([
            'status'         => $request->status,
            'catatan_mentor' => $request->catatan_mentor,
            'reviewed_by'    => $request->user()->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal berhasil direview',
            'data'    => $proposal,
        ]);
    }

    /**
     * POST /api/proposal/{id}/review-rab — mentor review RAB proposal
     */
    public function reviewRab(Request $request, $id)
    {
        $request->validate([
            'status_rab'         => 'required|in:disetujui,revisi',
            'catatan_rab_mentor' => 'nullable|string',
        ]);

        $proposal = Proposal::findOrFail($id);

        if ($response = $this->authorizeAksesProposal($request, $proposal)) {
            return $response;
        }

        $proposal->update([
            'status_rab'         => $request->status_rab,
            'catatan_rab_mentor' => $request->catatan_rab_mentor,
            'reviewed_rab_by'    => $request->user()->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'RAB proposal berhasil direview',
            'data'    => $proposal->fresh('rab'),
        ]);
    }

    /**
     * Pastikan akses ke proposal ini sah:
     * - Mentor hanya boleh akses proposal milik kelompok yang ia bimbing.
     * - Siswa hanya boleh akses proposal miliknya sendiri.
     */
    private function authorizeAksesProposal(Request $request, Proposal $proposal)
    {
        $user = $request->user();

        if ($user->peran === 'mentor') {
            $kelompok = $proposal->kelompok;

            if (!$kelompok || (int) $kelompok->id_mentor !== (int) $user->id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Anda bukan mentor pembimbing kelompok pemilik proposal ini.',
                ], 403);
            }

            return null;
        }

        if ((int) $proposal->id_pengguna !== (int) $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak berhak mengakses proposal ini.',
            ], 403);
        }

        return null;
    }

    private function proposalRules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'id_kelompok'               => $required . '|exists:kelompoks,id',
            'id_lahan'                  => 'nullable|exists:lahan,id',
            'judul'                     => $required . '|string|max:200',
            'luas_lahan'                => $required . '|numeric|min:0',
            'jumlah_populasi'           => $required . '|integer|min:1',
            'latar_belakang'            => $required . '|string',
            'maksud_tujuan'             => $required . '|string',
            'waktu_tempat'              => $required . '|string',
            'rencana_penelitian'        => $required . '|string',
            'nama_tanaman'              => $required . '|string|max:100',
            'perkiraan_panen_per_pohon' => 'nullable|numeric|min:0',
            'total_panen_kg'            => $required . '|numeric|min:0',
            'harga_satuan'              => $required . '|numeric|min:0',
            'jarak_tanam'               => 'nullable|string|max:50',
            'masa_periode_tanam'        => 'nullable|string|max:50',
            'kesimpulan_analisis'       => 'nullable|string',
        ];
    }
}
