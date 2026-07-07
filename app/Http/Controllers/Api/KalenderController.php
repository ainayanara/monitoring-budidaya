<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kalender;
use App\Models\Lahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KalenderController extends Controller
{
    public function index(Request $request)
    {
        $query = Kalender::with(['komoditas', 'lahan.komoditas'])
            ->where('id_pengguna', $request->user()->id);

        if ($request->filled('id_lahan')) {
            $query->where('id_lahan', $request->id_lahan);
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal_mulai', $request->tahun)
                ->whereMonth('tanggal_mulai', $request->bulan);
        }

        $kalender = $query->orderBy('tanggal_mulai')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $kalender->map(fn ($k) => $this->formatKalender($k)),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_lahan' => ['required',
            Rule::exists('lahan', 'id')->where(function ($query) use ($request) {
            $query->where('id_pengguna', $request->user()->id);}),],
            'nama_kegiatan'  => 'required|string|max:150',
            'nama_tahapan'   => 'required|string|max:100',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai'=> 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $lahan = Lahan::findOrFail($request->id_lahan);

        if ($lahan->id_pengguna != $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lahan bukan milik Anda.'
            ], 403);
        }

        $kalender = Kalender::create([
            'id_pengguna'     => $request->user()->id,
            'id_komoditas'    => $lahan->id_komoditas,
            'id_lahan'        => $lahan->id,
            'nama_tahapan'    => $request->nama_tahapan,
            'nama_kegiatan'   => $request->nama_kegiatan,
            'tipe_lahan'      => $lahan->tipe_lahan,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai ?? $request->tanggal_mulai,
        ]);
        
        $kalender->load(['komoditas', 'lahan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal kalender ditambahkan',
            'data'    => $this->formatKalender($kalender),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $kalender = Kalender::with(['komoditas', 'lahan.komoditas', 'aktivitas'])
            ->where('id_pengguna', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatKalender($kalender, true),
        ]);
    }

    public function update(Request $request, $id)
    {
        $kalender = Kalender::where('id_pengguna', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama_kegiatan'   => 'sometimes|required|string|max:150',
            'nama_tahapan'    => 'sometimes|required|string|max:100',
            'tanggal_mulai'   => 'sometimes|required|date',
            'tanggal_selesai' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->has('nama_kegiatan')) {
            $kalender->nama_kegiatan = $request->nama_kegiatan;
        }

        if ($request->has('nama_tahapan')) {
            $kalender->nama_tahapan = $request->nama_tahapan;
        }

        if ($request->has('tanggal_mulai')) {
            $kalender->tanggal_mulai = $request->tanggal_mulai;
        }

        if ($request->has('tanggal_selesai')) {
            $kalender->tanggal_selesai = $request->tanggal_selesai;
        }

        $kalender->save();
        $kalender->refresh();
        $kalender->load(['komoditas', 'lahan']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal kalender diperbarui',
            'data'    => $this->formatKalender($kalender),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $kalender = Kalender::where('id_pengguna', $request->user()->id)->findOrFail($id);
        $kalender->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Jadwal kalender dihapus',
        ]);
    }

    private function formatKalender(Kalender $kalender, bool $withAktivitas = false): array
    {
        $data = [
            'id'              => $kalender->id,
            'id_lahan'        => $kalender->id_lahan,
            'nama_kegiatan'   => $kalender->nama_kegiatan,
            'nama_tahapan'    => $kalender->nama_tahapan,
            'fase_budidaya'   => $kalender->nama_tahapan,
            'komoditas'       => $kalender->komoditas?->nama_komoditas,
            'id_komoditas'    => $kalender->id_komoditas,
            'tipe_lahan'      => $kalender->tipe_lahan,
            'jenis_lahan'     => $kalender->lahan?->jenis_lahan ?? null,
            'nama_lahan'      => $kalender->lahan
                ? ($kalender->lahan->komoditas?->nama_komoditas.' ('.$kalender->lahan->jenis_lahan.')'): null,
            'tanggal_mulai'   => $kalender->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $kalender->tanggal_selesai?->toDateString(),
        ];

        if ($withAktivitas) {
            $data['aktivitas'] = $kalender->aktivitas;
        }

        return $data;
    }
}
