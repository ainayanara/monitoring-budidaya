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
        $user  = $request->user();
        $query = Proposal::with(['pengguna', 'kelompok', 'lahan.komoditas', 'rab']);

        if ($user->peran === 'mentor') {
            $kelompokIds = $user->kelompokDibimbing()->pluck('id');
            $query->whereIn('id_kelompok', $kelompokIds)
                  // ✅ FIX: mentor tidak bisa lihat draft siswa
                  // Mentor hanya lihat proposal yang sudah di-submit (pending/disetujui/revisi/ditolak)
                  ->whereNotIn('status', ['draft']);
        } else {
            $query->where('id_pengguna', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $proposals = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $proposals->map(fn ($p) => $this->formatProposal($p)),
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
            'id_pengguna'              => $user->id,
            'id_kelompok'              => $request->id_kelompok,
            'id_lahan'                 => $request->id_lahan,
            'judul'                    => $request->judul,
            'nama_penyusun'            => trim($user->nama_depan . ' ' . $user->nama_belakang),
            'status'                   => 'draft',
            'status_rab'               => 'draft',
            'luas_lahan'               => $request->luas_lahan,
            'jumlah_populasi'          => $request->jumlah_populasi,
            'latar_belakang'           => $request->latar_belakang,
            'maksud_tujuan'            => $request->maksud_tujuan,
            'waktu_tempat'             => $request->waktu_tempat,
            'rencana_penelitian'       => $request->rencana_penelitian,
            'nama_tanaman'             => $request->nama_tanaman,
            'perkiraan_panen_per_pohon'=> $request->perkiraan_panen_per_pohon,
            'total_panen_kg'           => $request->total_panen_kg,
            'harga_satuan'             => $request->harga_satuan,
            'jarak_tanam'              => $request->jarak_tanam,
            'masa_periode_tanam'       => $request->masa_periode_tanam,
            'kesimpulan_analisis'      => $request->kesimpulan_analisis,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal disimpan sebagai draft',
            'data'    => $this->formatProposal($proposal->fresh(['pengguna', 'kelompok'])),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $proposal = Proposal::with(['pengguna', 'kelompok', 'lahan', 'rab'])->findOrFail($id);

        if ($response = $this->authorizeAksesProposal($request, $proposal)) {
            return $response;
        }

        // ✅ FIX: mentor tidak bisa lihat detail draft
        if ($request->user()->peran === 'mentor' && $proposal->status === 'draft') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Proposal masih dalam draft, belum dikirim ke mentor.',
            ], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail proposal berhasil diambil',
            'data'    => $this->formatProposal($proposal),
            'rab'     => $proposal->rab,
            'kalkulasi' => $proposal->hitungKalkulasi(),
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
            'id_kelompok', 'id_lahan', 'judul',
            'luas_lahan', 'jumlah_populasi',
            'latar_belakang', 'maksud_tujuan',
            'waktu_tempat', 'rencana_penelitian',
            'nama_tanaman', 'perkiraan_panen_per_pohon',
            'total_panen_kg', 'harga_satuan',
            'jarak_tanam', 'masa_periode_tanam',
            'kesimpulan_analisis',
        ]));
        $proposal->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal berhasil diperbarui',
            'data'    => $this->formatProposal(
                $proposal->fresh(['pengguna', 'kelompok', 'rab', 'lahan'])
            ),
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

    public function submit(Request $request, $id)
    {
        $proposal = Proposal::with('rab')->findOrFail($id);

        if ($proposal->id_pengguna != $request->user()->id) {
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
            'data'    => $this->formatProposal($proposal->fresh(['pengguna', 'kelompok'])),
        ]);
    }

    public function review(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:disetujui,revisi,ditolak',
            'catatan_mentor' => 'nullable|string',
            'nilai_proposal' => 'nullable|numeric|min:0|max:100',
        ]);

        $proposal = Proposal::findOrFail($id);

        if ($response = $this->authorizeAksesProposal($request, $proposal)) {
            return $response;
        }

        $proposal->update([
            'status'         => $request->status,
            'catatan_mentor' => $request->catatan_mentor,
            'nilai_proposal' => $request->filled('nilai_proposal')
                ? $request->nilai_proposal
                : $proposal->nilai_proposal,
            'reviewed_by'    => $request->user()->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Proposal berhasil direview',
            'data'    => $this->formatProposal($proposal->fresh(['pengguna', 'kelompok'])),
        ]);
    }

    public function reviewRab(Request $request, $id)
    {
        $request->validate([
            'status_rab'        => 'required|in:disetujui,revisi',
            'catatan_rab_mentor'=> 'nullable|string',
            'nilai_rab'         => 'nullable|numeric|min:0|max:100',
        ]);

        $proposal = Proposal::findOrFail($id);

        if ($response = $this->authorizeAksesProposal($request, $proposal)) {
            return $response;
        }

        $proposal->update([
            'status_rab'          => $request->status_rab,
            'catatan_rab_mentor'  => $request->catatan_rab_mentor,
            'nilai_rab'           => $request->filled('nilai_rab')
                ? $request->nilai_rab
                : $proposal->nilai_rab,
            'reviewed_rab_by'     => $request->user()->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'RAB proposal berhasil direview',
            'data'    => $this->formatProposal(
                $proposal->fresh(['pengguna', 'kelompok', 'rab'])
            ),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * ✅ FIX: Format proposal untuk response API.
     * Tambahkan nama_penyusun, nama_kelompok, status_rab, nilai, catatan
     * di level atas agar Flutter tidak perlu nested parsing.
     */
    private function formatProposal(Proposal $p): array
    {
        $pengguna = $p->pengguna;
        $kelompok = $p->kelompok;

        return array_merge($p->toArray(), [
            // ✅ Nama siswa penyusun — mudah diakses Flutter di level atas
            'nama_penyusun'  => $p->nama_penyusun
                ?? ($pengguna ? trim($pengguna->nama_depan . ' ' . $pengguna->nama_belakang) : '-'),
            // ✅ Nama kelompok — mudah diakses Flutter di level atas
            'nama_kelompok'  => $kelompok?->nama_kelompok,
            // Field-field yang perlu eksplisit agar tidak null
            'status'         => $p->status        ?? 'draft',
            'status_rab'     => $p->status_rab    ?? 'draft',
            'catatan_mentor' => $p->catatan_mentor,
            'nilai_proposal' => $p->nilai_proposal,
            'catatan_rab_mentor' => $p->catatan_rab_mentor,
            'nilai_rab'      => $p->nilai_rab,
        ]);
    }

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
        $req = $isUpdate ? 'sometimes' : 'required';
        return [
            'id_kelompok'               => "$req|exists:kelompoks,id",
            'judul'                     => "$req|string|max:255",
            'luas_lahan'                => "$req|numeric|min:0",
            'jumlah_populasi'           => "$req|integer|min:0",
            'latar_belakang'            => "$req|string",
            'maksud_tujuan'             => "$req|string",
            'waktu_tempat'              => "$req|string",
            'rencana_penelitian'        => "$req|string",
            'nama_tanaman'              => "$req|string|max:100",
            'total_panen_kg'            => "$req|numeric|min:0",
            'harga_satuan'              => "$req|numeric|min:0",
            'perkiraan_panen_per_pohon' => 'nullable|numeric|min:0',
            'jarak_tanam'               => 'nullable|string|max:50',
            'masa_periode_tanam'        => 'nullable|string|max:50',
            'kesimpulan_analisis'       => 'nullable|string',
            'id_lahan'                  => 'nullable|exists:lahans,id',
        ];
    }
}