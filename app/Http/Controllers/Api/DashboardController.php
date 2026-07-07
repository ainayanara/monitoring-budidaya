<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Aktivitas;
use App\Models\Lahan;
use App\Models\Kelompok;
use App\Models\Kalender;
use App\Models\Proposal;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard Siswa
     * GET /api/dashboard/siswa
     */
    public function siswa(Request $request)
    {
        $user = $request->user();

        Aktivitas::syncForUser($user->id);

        // 1. Ambil kelompok milik siswa ini
        $kelompok = Kelompok::whereHas('anggota', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['lahan.komoditas'])->first();

        // 2. Aktivitas hari ini (dari kalender milik user)
        $today = Carbon::today()->toDateString();
        $aktivitasHariIni = Aktivitas::whereHas('kalender', function ($q) use ($user) {
                $q->where('id_pengguna', $user->id);
            })
            ->where('tanggal_aktivitas', $today)
            ->with('kalender')
            ->get();

        // 3. Aktivitas terlewat (status terlewat)
        $aktivitasTerlewat = Aktivitas::whereHas('kalender', function ($q) use ($user) {
                $q->where('id_pengguna', $user->id);
            })
            ->where('status', 'terlewat')
            ->count();

        // 4. Aktivitas mendatang (7 hari ke depan)
        $aktivitasMendatang = Kalender::where('id_pengguna', $user->id)
            ->whereBetween('tanggal_mulai', [
                Carbon::tomorrow()->toDateString(),
                Carbon::today()->addDays(7)->toDateString()
            ])
            ->count();

        // 5. Lahan milik kelompok siswa
        $lahan = $kelompok ? Lahan::where('id_kelompok', $kelompok->id)
            ->with(['komoditas', 'kelompok'])
            ->get() : collect();

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'nama_depan'  => $user->nama_depan,
                'nama_belakang' => $user->nama_belakang,
                'username'    => $user->username,
                'peran'       => $user->peran,
            ],
            'kelompok' => $kelompok ? [
                'id'           => $kelompok->id,
                'nama_kelompok' => $kelompok->nama_kelompok,
                'tipe_lahan'   => $kelompok->tipe_lahan,
            ] : null,
            'ringkasan_aktivitas' => [
                'hari_ini'   => $aktivitasHariIni->count(),
                'terlewat'   => $aktivitasTerlewat,
                'mendatang'  => $aktivitasMendatang,
            ],
            'aktivitas_hari_ini' => $aktivitasHariIni->map(fn($a) => [
                'id'             => $a->id,
                'nama_kegiatan'  => $a->kalender->nama_kegiatan ?? '-',
                'nama_tahapan'   => $a->kalender->nama_tahapan ?? '-',
                'tipe_lahan'     => $a->kalender->tipe_lahan ?? '-',
                'status'         => $a->status,
                'tanggal'        => $a->tanggal_aktivitas,
            ]),
            'lahan' => $lahan->map(fn($l) => [
                'id'           => $l->id,
                'komoditas'    => $l->komoditas->nama_komoditas ?? '-',
                'tipe_lahan'   => $l->tipe_lahan,
                'tanggal_mulai' => $l->tanggal_mulai,
                'luas'         => $l->luas,
            ]),
        ]);
    }

    /**
     * Dashboard Mentor
     * GET /api/dashboard/mentor
     */
    public function mentor(Request $request)
    {
        $user = $request->user();
        $kelompoks = Kelompok::where('id_mentor', $user->id)
            ->with(['lahan.komoditas', 'anggota'])
            ->get();

        $kelompokIds = $kelompoks->pluck('id');

        $proposalPending = Proposal::whereIn('id_kelompok', $kelompokIds)
            ->where('status', 'pending')
            ->count();
        $proposalDisetujui = Proposal::whereIn('id_kelompok', $kelompokIds)
            ->where('status', 'disetujui')
            ->count();
        $proposalDitolak = Proposal::whereIn('id_kelompok', $kelompokIds)
            ->where('status', 'ditolak')
            ->count();
        $rabPending = Proposal::whereIn('id_kelompok', $kelompokIds)
            ->where('status_rab', 'pending')
            ->count();

        $userIdsSiswa = $kelompoks
            ->flatMap(fn($k) => $k->anggota->pluck('id'))
            ->unique();
        $aktivitasTerlewat = Aktivitas::whereHas('kalender', function ($q) use ($userIdsSiswa) {
                $q->whereIn('id_pengguna', $userIdsSiswa);
            })
            ->where('status', 'terlewat')
            ->count();
        $aktivitasHariIni = Aktivitas::whereHas('kalender', function ($q) use ($userIdsSiswa) {
                $q->whereIn('id_pengguna', $userIdsSiswa);
            })
            ->where('status', 'hari_ini')
            ->with('kalender')
            ->latest()
            ->take(5)
            ->get();
        $rekapKelompok = $kelompoks->map(function ($k) {

            $anggotaIds = $k->anggota->pluck('id');

            $totalAktivitas = Aktivitas::whereHas('kalender', fn($q) =>
                $q->whereIn('id_pengguna', $anggotaIds)
            )->count();

            $aktivitasSelesai = Aktivitas::whereHas('kalender', fn($q) =>
                $q->whereIn('id_pengguna', $anggotaIds)
            )->where('status', 'hari_ini')
            ->count();

            return [
                'id' => $k->id,
                'nama_kelompok' => $k->nama_kelompok,
                'jumlah_anggota' => $k->anggota->count(),
                'jumlah_lahan' => $k->lahan->count(),

                'komoditas' => $k->lahan
                    ->map(fn($l) => $l->komoditas->nama_komoditas ?? '-')
                    ->unique()
                    ->values(),

                'total_aktivitas' => $totalAktivitas,

                'aktivitas_selesai' => $aktivitasSelesai,

                'progress_persen' => $totalAktivitas > 0
                    ? round(($aktivitasSelesai / $totalAktivitas) * 100)
                    : 0,
            ];
        });

return response()->json([
    'status' => 'success',
    'data' => [
        'mentor' => [
            'id' => $user->id,
            'nama' => $user->nama_depan . ' ' . $user->nama_belakang,
            'username' => $user->username,
        ],

        'ringkasan' => [
            'total_kelompok' => $kelompoks->count(),
            'total_siswa' => $userIdsSiswa->count(),

            'proposal_pending' => $proposalPending,
            'proposal_disetujui' => $proposalDisetujui,
            'proposal_ditolak' => $proposalDitolak,
            'rab_pending' => $rabPending,

            'aktivitas_terlewat' => $aktivitasTerlewat,
        ],

        'aktivitas_hari_ini' => $aktivitasHariIni->map(fn($a) => [
            'id' => $a->id,
            'nama_kegiatan' => $a->kalender->nama_kegiatan ?? '-',
            'nama_tahapan' => $a->kalender->nama_tahapan ?? '-',
            'status' => $a->status,
            'tanggal' => $a->tanggal_aktivitas,
        ]),

        'kelompok' => $rekapKelompok,
    ]
]);
    }
}