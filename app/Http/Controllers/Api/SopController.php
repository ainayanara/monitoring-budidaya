<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Komoditas;
use App\Models\Sop;
use App\Models\SopLangkah;
use App\Models\SopProgress;

class SopController extends Controller
{
    // GET /api/komoditas — list semua komoditas (untuk halaman pilih komoditas)
    public function indexKomoditas()
    {
        return response()->json(Komoditas::all());
    }

    // GET /api/sop/{id_komoditas} — list tahapan SOP per komoditas
    public function showByKomoditas(Request $request, $id_komoditas)
    {
        $user = $request->user();

        $sop = Sop::where('id_komoditas', $id_komoditas)
            ->orderBy('id')
            ->with(['langkah' => function ($q) use ($user) {
                $q->orderBy('urutan')->with(['progress' => function ($q2) use ($user) {
                    $q2->where('id_pengguna', $user->id);
                }]);
            }])
            ->get()
            ->map(function ($s) {
                $totalLangkah   = $s->langkah->count();
                $selesaiLangkah = $s->langkah->filter(fn($l) =>
                    $l->progress->isNotEmpty() && $l->progress->first()->selesai
                )->count();

                return [
                    'id'              => $s->id,
                    // PERBAIKAN: field ini sebelumnya tidak dikirim,
                    // padahal dibutuhkan oleh model Sop di Flutter.
                    'id_komoditas'    => $s->id_komoditas,
                    'nama_tahapan'    => $s->nama_tahapan,
                    'estimasi_hari'   => $s->estimasi_hari,
                    'deskripsi'       => $s->deskripsi,
                    // PERBAIKAN: cast ke (int) supaya tidak dikirim sebagai
                    // float ("42.0") yang gagal di-parse oleh int.tryParse()
                    // di sisi Flutter (JsonUtils.asInt).
                    'progress_persen' => $totalLangkah > 0
                        ? (int) round(($selesaiLangkah / $totalLangkah) * 100)
                        : 0,
                    'langkah' => $s->langkah->map(fn($l) => [
                        'id'               => $l->id,
                        'id_sop'           => $l->id_sop,
                        'urutan'           => $l->urutan,
                        'judul_langkah'    => $l->judul_langkah,
                        'deskripsi'        => $l->deskripsi,
                        'hasil_diharapkan' => $l->hasil_diharapkan,
                        'selesai'          => $l->progress->isNotEmpty()
                            ? (bool) $l->progress->first()->selesai
                            : false,
                    ]),
                ];
            });

        return response()->json([
            'status' => 'success',
            'message' => 'Data SOP berhasil diambil',
            'data' => $sop,
        ]);
    }

    // POST /api/sop/progress — siswa ceklis satu langkah
    public function toggleProgress(Request $request)
    {
        $request->validate(['id_sop_langkah' => 'required|exists:sop_langkah,id']);

        $progress = SopProgress::firstOrCreate([
            'id_pengguna'    => $request->user()->id,
            'id_sop_langkah' => $request->id_sop_langkah,
        ]);

        // Toggle: kalau sudah selesai jadi belum, kalau belum jadi selesai
        $progress->update(['selesai' => !$progress->selesai]);

        return response()->json([
            'status' => 'success',
            'message' => $progress->selesai
                ? 'Langkah ditandai selesai'
                : 'Langkah dibatalkan',

            'data' => [
                'selesai' => $progress->selesai,
                'id_sop_langkah' => (int) $request->id_sop_langkah,
            ]
        ]);
    }
}