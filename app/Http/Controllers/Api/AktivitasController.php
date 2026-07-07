<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Kalender;
use App\Models\Lahan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AktivitasController extends Controller
{
    /**
     * GET /api/aktivitas/form-data
     * Data dropdown untuk form tambah aktivitas (lahan wajib diisi dulu).
     */
    public function formData(Request $request)
    {
        $user = $request->user();

        $lahan = Lahan::where('id_pengguna', $user->id)
            ->with('komoditas')
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'id_komoditas' => $l->id_komoditas,
                'label' => ($l->komoditas?->nama_komoditas ?? 'Lahan')
                    . ' (' . $l->jenis_lahan . ')',
                'komoditas' => $l->komoditas?->nama_komoditas,
                'jenis_lahan' => $l->jenis_lahan,
                'tipe_lahan' => $l->tipe_lahan,
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => $lahan->isEmpty()
                ? 'Belum ada lahan. Isi fitur Lahan terlebih dahulu.'
                : 'Data form aktivitas',
            'data'    => [
                'lahan' => $lahan,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        Aktivitas::syncForUser($userId);

        $query = Aktivitas::with(['kalender.komoditas', 'kalender.lahan.komoditas'])
            ->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $userId));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $aktivitas = $query->orderBy('tanggal_aktivitas')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $aktivitas->map(fn ($a) => $this->formatAktivitas($a)),
            'ringkasan' => [
                'terlewat'  => $aktivitas->where('status', 'terlewat')->count(),
                'terjadwal' => $aktivitas->where('status', 'terjadwal')->count(),
                'hari_ini'  => $aktivitas->where('status', 'hari_ini')->count(),
                'selesai'   => $aktivitas->where('status', 'selesai')->count(),
            ],
        ]);
    }

    /**
     * POST /api/aktivitas
     * Tambah aktivitas manual oleh siswa.
     *
     * Body (multipart jika ada foto):
     * - id_lahan (wajib jika tanpa id_kalender)
     * - jenis_kegiatan / nama_kegiatan (wajib)
     * - tanggal (wajib, default hari ini)
     * - nama_tahapan (opsional)
     * - catatan (opsional)
     * - dokumentasi (opsional, file image)
     * - id_kalender (opsional, jika dari jadwal kalender yang sudah ada)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_lahan'        => 'required_without:id_kalender|exists:lahan,id',
            'id_kalender'     => 'nullable|exists:kalender,id',
            'jenis_kegiatan'  => 'required_without:nama_kegiatan|string|max:150',
            'nama_kegiatan'   => 'required_without:jenis_kegiatan|string|max:150',
            'nama_tahapan'    => 'nullable|string|max:100',
            'tanggal'         => 'nullable|date',
            'catatan'         => 'nullable|string',
            'dokumentasi'     => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $namaKegiatan = $request->input('jenis_kegiatan', $request->input('nama_kegiatan'));
        $tanggal = Carbon::parse($request->input('tanggal', now()->toDateString()));
        $dokumentasiPath = $this->uploadDokumentasi($request);

        if ($request->filled('id_kalender')) {
            $kalender = Kalender::where('id_pengguna', $user->id)
                ->findOrFail($request->id_kalender);

            if ($request->filled('id_lahan') && (int) $kalender->id_lahan !== (int) $request->id_lahan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Lahan tidak sesuai dengan jadwal kalender',
                ], 422);
            }

            $payload = [
                'tanggal_aktivitas' => $tanggal->toDateString(),
                'status'            => Aktivitas::resolveStatus($tanggal),
            ];

            if ($request->filled('catatan')) {
                $payload['catatan'] = $request->catatan;
            }

            if ($dokumentasiPath) {
                $payload['dokumentasi'] = $dokumentasiPath;
            }

            $aktivitas = Aktivitas::updateOrCreate(
                ['id_kalender' => $kalender->id],
                $payload
            );
        } else {
            $lahan = Lahan::with('komoditas')->findOrFail($request->id_lahan);

            if (!$this->canAccessLahan($user, $lahan)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Lahan tidak ditemukan atau bukan milik kelompok Anda. Isi fitur Lahan terlebih dahulu.',
                ], 403);
            }

            $kalender = Kalender::create([
                'id_pengguna'     => $user->id,
                'id_komoditas'    => $lahan->id_komoditas,
                'id_lahan'        => $lahan->id,
                'nama_tahapan'    => $request->nama_tahapan ?? 'Kegiatan Manual',
                'nama_kegiatan'   => $namaKegiatan,
                'tipe_lahan'      => $lahan->tipe_lahan,
                'tanggal_mulai'   => $tanggal->toDateString(),
                'tanggal_selesai' => $tanggal->toDateString(),
            ]);

            $aktivitas = Aktivitas::create([
                'id_kalender'       => $kalender->id,
                'tanggal_aktivitas' => $tanggal->toDateString(),
                'status'            => Aktivitas::resolveStatus($tanggal),
                'catatan'           => $request->catatan,
                'dokumentasi'       => $dokumentasiPath,
            ]);
        }

        $aktivitas->load(['kalender.komoditas', 'kalender.lahan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Aktivitas berhasil ditambahkan',
            'data'    => $this->formatAktivitas($aktivitas),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $aktivitas = Aktivitas::with([
            'kalender',
            'kalender.komoditas',
            'kalender.lahan',
            'kalender.lahan.komoditas',
        ])
            ->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $request->user()->id))
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatAktivitas($aktivitas),
        ]);
    }

    public function update(Request $request, $id)
    {
        $aktivitas = Aktivitas::with('kalender')
            ->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $request->user()->id))
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'jenis_kegiatan' => 'nullable|string|max:150',
            'nama_kegiatan'  => 'nullable|string|max:150',
            'catatan'        => 'nullable|string',
            'dokumentasi'    => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $namaKegiatan = $request->input('jenis_kegiatan', $request->input('nama_kegiatan'));
        if ($namaKegiatan && $aktivitas->kalender) {
            $aktivitas->kalender->update(['nama_kegiatan' => $namaKegiatan]);
        }

        if ($request->hasFile('dokumentasi')) {
            if ($aktivitas->dokumentasi) {
                Storage::disk('public')->delete($aktivitas->dokumentasi);
            }
            $aktivitas->dokumentasi = $request->file('dokumentasi')
                ->store('aktivitas/dokumentasi', 'public');
        }

        if ($request->filled('catatan')) {
            $aktivitas->catatan = $request->catatan;
        }

        $aktivitas->save();
        $aktivitas->load(['kalender.komoditas', 'kalender.lahan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Aktivitas berhasil diperbarui',
            'data'    => $this->formatAktivitas($aktivitas),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $aktivitas = Aktivitas::with('kalender')
            ->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $request->user()->id))
            ->findOrFail($id);

        if ($aktivitas->dokumentasi) {
            Storage::disk('public')->delete($aktivitas->dokumentasi);
        }

        $kalender = $aktivitas->kalender;
        $aktivitas->delete();

        if ($kalender && $kalender->nama_tahapan === 'Kegiatan Manual') {
            $kalender->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Aktivitas berhasil dihapus',
        ]);
    }

    public function updateProgress(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'catatan'     => 'nullable|string',
            'dokumentasi' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $aktivitas = Aktivitas::with('kalender')
            ->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $request->user()->id))
            ->findOrFail($id);

        if ($request->hasFile('dokumentasi')) {
            if ($aktivitas->dokumentasi) {
                Storage::disk('public')->delete($aktivitas->dokumentasi);
            }
            $aktivitas->dokumentasi = $request->file('dokumentasi')
                ->store('aktivitas/dokumentasi', 'public');
        }

        $aktivitas->catatan = $request->input('catatan', $aktivitas->catatan);
        $aktivitas->status = 'selesai';
        $aktivitas->status_verifikasi = 'pending';
        $aktivitas->save();
        $aktivitas->load(['kalender.komoditas', 'kalender.lahan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Laporan aktivitas terkirim, menunggu verifikasi mentor',
            'data'    => $this->formatAktivitas($aktivitas),
        ]);
    }

    public function verifikasi(Request $request, $id)
    {
        $request->validate([
            'status_verifikasi' => 'required|in:disetujui,revisi',
            'catatan_mentor'    => 'nullable|string',
        ]);

        $aktivitas = Aktivitas::with(['kalender.komoditas', 'kalender.lahan'])->findOrFail($id);

        if ($response = $this->authorizeVerifikasiAktivitas($request, $aktivitas)) {
            return $response;
        }

        $aktivitas->update([
            'status_verifikasi' => $request->status_verifikasi,
            'catatan_mentor'    => $request->catatan_mentor,
            'verified_by'       => $request->user()->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Aktivitas berhasil diverifikasi',
            'data'    => $this->formatAktivitas($aktivitas),
        ]);
    }

    /**
     * Pastikan mentor yang login adalah mentor pembimbing kelompok
     * tempat siswa pemilik aktivitas ini bernaung. Selain itu, ditolak (403).
     */
    private function authorizeVerifikasiAktivitas(Request $request, Aktivitas $aktivitas)
    {
        $siswaId = $aktivitas->kalender?->id_pengguna;

        $kelompok = $siswaId
            ? Kelompok::whereHas('anggota', fn ($q) => $q->where('user_id', $siswaId))->first()
            : null;

        if (!$kelompok || (int) $kelompok->id_mentor !== (int) $request->user()->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda bukan mentor pembimbing kelompok siswa pemilik aktivitas ini.',
            ], 403);
        }

        return null;
    }

    private function canAccessLahan(User $user, Lahan $lahan): bool
    {
        return $lahan->id_pengguna == $user->id;
    }

    private function uploadDokumentasi(Request $request): ?string
    {
        if (!$request->hasFile('dokumentasi')) {
            return null;
        }

        return $request->file('dokumentasi')->store('aktivitas/dokumentasi', 'public');
    }

    private function formatAktivitas(Aktivitas $aktivitas): array
    {
        $kalender = $aktivitas->kalender;
        $lahan    = $kalender?->lahan;

        return [
            'id'                  => $aktivitas->id,
            'id_kalender'         => $aktivitas->id_kalender,
            'jenis_kegiatan'      => $kalender?->nama_kegiatan,
            'nama_kegiatan'       => $kalender?->nama_kegiatan,
            'nama_tahapan'        => $kalender?->nama_tahapan,
            'fase_budidaya'       => $kalender?->nama_tahapan,
            'komoditas'           => $kalender?->komoditas?->nama_komoditas,
            'id_lahan'            => $kalender?->id_lahan,
            'nama_lahan'          => $lahan
                ? trim(($lahan->komoditas?->nama_komoditas ?? 'Lahan') . ' (' . ($lahan->jenis_lahan ?? '-') . ')')
                : null,
            'tipe_lahan'          => $kalender?->tipe_lahan,
            'tanggal'             => $aktivitas->tanggal_aktivitas?->toDateString(),
            'status'              => $aktivitas->status,
            'status_label'        => $aktivitas->status === 'terjadwal' ? 'terjadwal / mendatang' : $aktivitas->status,
            'catatan'             => $aktivitas->catatan,
            'dokumentasi'         => $aktivitas->dokumentasi
                ? asset('storage/' . $aktivitas->dokumentasi)
                : null,
            'status_verifikasi'   => $aktivitas->status_verifikasi,
            'catatan_mentor'      => $aktivitas->catatan_mentor,
            'verified_by'         => $aktivitas->verified_by,
        ];
    }
}
