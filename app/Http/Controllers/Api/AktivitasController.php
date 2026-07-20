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
use App\Models\Kelompok;

class AktivitasController extends Controller
{
    public function formData(Request $request)
    {
        $user = $request->user();

        $lahan = Lahan::where('id_pengguna', $user->id)
            ->with('komoditas')
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id'           => $l->id,
                'id_komoditas' => $l->id_komoditas,
                'label'        => ($l->komoditas?->nama_komoditas ?? 'Lahan')
                    . ' (' . $l->jenis_lahan . ')',
                'komoditas'    => $l->komoditas?->nama_komoditas,
                'jenis_lahan'  => $l->jenis_lahan,
                'tipe_lahan'   => $l->tipe_lahan,
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => $lahan->isEmpty()
                ? 'Belum ada lahan. Isi fitur Lahan terlebih dahulu.'
                : 'Data form aktivitas',
            'data'    => ['lahan' => $lahan],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->peran === 'mentor') {
            // Ambil semua kelompok yang dibimbing mentor ini
            $kelompokIds = $user->kelompokDibimbing()->pluck('id');

            // Ambil semua siswa di kelompok tersebut
            $siswaIds = Kelompok::whereIn('id', $kelompokIds)
                ->with('anggota')
                ->get()
                ->flatMap(fn($k) => $k->anggota->pluck('id'))
                ->unique()
                ->values();

            // ✅ FIX: eager load 'pengguna' agar nama siswa bisa diambil
            $query = Aktivitas::with([
                'kalender.komoditas',
                'kalender.lahan.komoditas',
                'kalender.pengguna', // ← TAMBAHAN PENTING
            ])->whereHas('kalender', function ($q) use ($siswaIds) {
                $q->whereIn('id_pengguna', $siswaIds);
            });
        } else {
            Aktivitas::syncForUser($user->id);
            $query = Aktivitas::with([
                'kalender.komoditas',
                'kalender.lahan.komoditas',
                'kalender.pengguna',
            ])->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $user->id));
        }

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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_lahan'       => 'required_without:id_kalender|exists:lahan,id',
            'id_kalender'    => 'nullable|exists:kalender,id',
            'jenis_kegiatan' => 'required_without:nama_kegiatan|string|max:150',
            'nama_kegiatan'  => 'required_without:jenis_kegiatan|string|max:150',
            'nama_tahapan'   => 'nullable|string|max:100',
            'tanggal'        => 'nullable|date',
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

        $user         = $request->user();
        $namaKegiatan = $request->input('jenis_kegiatan', $request->input('nama_kegiatan'));
        $tanggal      = Carbon::parse($request->input('tanggal', now()->toDateString()));
        $dokumentasiPath = $this->uploadDokumentasi($request);

        if ($request->filled('id_kalender')) {
            $kalender = Kalender::where('id_pengguna', $user->id)
                ->findOrFail($request->id_kalender);

            if ($request->filled('id_lahan') &&
                (int) $kalender->id_lahan !== (int) $request->id_lahan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Lahan tidak sesuai dengan jadwal kalender',
                ], 422);
            }

            $payload = [
                'tanggal_aktivitas' => $tanggal->toDateString(),
                'status'            => Aktivitas::resolveStatus($tanggal),
            ];

            if ($request->filled('catatan')) $payload['catatan'] = $request->catatan;
            if ($dokumentasiPath)            $payload['dokumentasi'] = $dokumentasiPath;

            $aktivitas = Aktivitas::updateOrCreate(
                ['id_kalender' => $kalender->id],
                $payload
            );
        } else {
            $lahan = Lahan::with('komoditas')->findOrFail($request->id_lahan);

            if (!$this->canAccessLahan($user, $lahan)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Lahan tidak ditemukan atau bukan milik kelompok Anda.',
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

        $aktivitas->load(['kalender.komoditas', 'kalender.lahan', 'kalender.pengguna']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Aktivitas berhasil ditambahkan',
            'data'    => $this->formatAktivitas($aktivitas),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user      = $request->user();
        $isMentor  = $user->peran === 'mentor';

        if ($isMentor) {
            // Mentor bisa lihat aktivitas siswa yang dibimbingnya
            $aktivitas = Aktivitas::with([
                'kalender', 'kalender.komoditas',
                'kalender.lahan', 'kalender.lahan.komoditas',
                'kalender.pengguna',
            ])->findOrFail($id);
        } else {
            $aktivitas = Aktivitas::with([
                'kalender', 'kalender.komoditas',
                'kalender.lahan', 'kalender.lahan.komoditas',
                'kalender.pengguna',
            ])
                ->whereHas('kalender', fn ($q) => $q->where('id_pengguna', $user->id))
                ->findOrFail($id);
        }

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

        if ($request->filled('catatan')) $aktivitas->catatan = $request->catatan;

        $aktivitas->save();
        $aktivitas->load(['kalender.komoditas', 'kalender.lahan', 'kalender.pengguna']);

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

        $aktivitas->catatan           = $request->input('catatan', $aktivitas->catatan);
        $aktivitas->status            = 'selesai';
        $aktivitas->status_verifikasi = 'pending';
        $aktivitas->save();
        $aktivitas->load(['kalender.komoditas', 'kalender.lahan', 'kalender.pengguna']);

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
            // ✅ tidak ada 'nilai' — sudah dihapus
        ]);

        $aktivitas = Aktivitas::with([
            'kalender',
            'kalender.pengguna',
            'kalender.komoditas',
            'kalender.lahan',
        ])->findOrFail($id);

        $user    = $request->user();
        $siswaId = $aktivitas->kalender?->id_pengguna;

        $kelompok = null;
        if ($siswaId) {
            $kelompok = Kelompok::whereHas('anggota', function ($q) use ($siswaId) {
                $q->where('user_id', $siswaId);
            })->first();
        }

        if (!$kelompok || (int) $kelompok->id_mentor !== (int) $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda bukan mentor pembimbing kelompok siswa ini.',
            ], 403);
        }

        $aktivitas->update([
            'status_verifikasi' => $request->status_verifikasi,
            'catatan_mentor'    => $request->catatan_mentor,
            'verified_by'       => $user->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Aktivitas berhasil diverifikasi',
            'data'    => $this->formatAktivitas($aktivitas->fresh(['kalender.pengguna'])),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function canAccessLahan(User $user, Lahan $lahan): bool
    {
        return $lahan->id_pengguna == $user->id;
    }

    private function uploadDokumentasi(Request $request): ?string
    {
        if (!$request->hasFile('dokumentasi')) return null;
        return $request->file('dokumentasi')->store('aktivitas/dokumentasi', 'public');
    }

    private function formatAktivitas(Aktivitas $aktivitas): array
    {
        $kalender = $aktivitas->kalender;
        $lahan    = $kalender?->lahan;
        $pengguna = $kalender?->pengguna; // siswa pemilik aktivitas

        // ✅ FIX: ambil kelompok siswa untuk tampil di sisi mentor
        $namaKelompok = null;
        if ($pengguna) {
            $kelompok = Kelompok::whereHas('anggota', function ($q) use ($pengguna) {
                $q->where('user_id', $pengguna->id);
            })->first();
            $namaKelompok = $kelompok?->nama_kelompok;
        }

        // ✅ FIX FOTO: gunakan Storage::url() bukan asset() untuk URL yang benar
        // di semua environment (termasuk Android emulator & device)
        $dokumentasiUrl = null;
        if ($aktivitas->dokumentasi) {
            $dokumentasiUrl = Storage::disk('public')->url($aktivitas->dokumentasi);
        }

        return [
            'id'                => $aktivitas->id,
            'id_kalender'       => $aktivitas->id_kalender,
            'jenis_kegiatan'    => $kalender?->nama_kegiatan,
            'nama_kegiatan'     => $kalender?->nama_kegiatan,
            'nama_tahapan'      => $kalender?->nama_tahapan,
            'fase_budidaya'     => $kalender?->nama_tahapan,
            'komoditas'         => $kalender?->komoditas?->nama_komoditas,
            'id_lahan'          => $kalender?->id_lahan,
            'nama_lahan'        => $lahan
                ? trim(($lahan->komoditas?->nama_komoditas ?? 'Lahan')
                    . ' (' . ($lahan->jenis_lahan ?? '-') . ')')
                : null,
            'tipe_lahan'        => $kalender?->tipe_lahan,
            'tanggal'           => $aktivitas->tanggal_aktivitas?->toDateString(),
            'status'            => $aktivitas->status,
            'status_label'      => $aktivitas->status === 'terjadwal'
                ? 'terjadwal / mendatang' : $aktivitas->status,
            'catatan'           => $aktivitas->catatan,
            // ✅ URL foto benar di semua device
            'dokumentasi'       => $dokumentasiUrl,
            'status_verifikasi' => $aktivitas->status_verifikasi,
            'catatan_mentor'    => $aktivitas->catatan_mentor,
            'nilai'             => $aktivitas->nilai,
            'verified_by'       => $aktivitas->verified_by,
            // ✅ TAMBAHAN: info siswa & kelompok untuk tampilan mentor
            'id_siswa'          => $pengguna?->id,
            'nama_siswa'        => $pengguna
                ? trim($pengguna->nama_depan . ' ' . $pengguna->nama_belakang)
                : null,
            'nama_kelompok'     => $namaKelompok,
        ];
    }
}