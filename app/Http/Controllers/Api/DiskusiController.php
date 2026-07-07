<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diskusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiskusiController extends Controller
{
    public function index(Request $request)
    {
        $query = Diskusi::whereNull('id_parent')
            ->with(['pengirim', 'replies.pengirim', 'kelompok']);

        if ($request->filled('id_kelompok')) {
            $query->where('id_kelompok', $request->id_kelompok);
        }

        $diskusi = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $diskusi->map(fn ($d) => $this->formatThread($d)),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_kelompok' => 'nullable|exists:kelompoks,id',
            // judul dibuat opsional: form pertanyaan di Flutter hanya berisi
            // kolom "pesan", jadi judul akan di-generate otomatis dari pesan
            // bila tidak dikirim, supaya tidak terjadi error validasi 422.
            'judul'       => 'nullable|string|max:255',
            'pesan'       => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $judul = $request->judul;
        if (empty($judul)) {
            $judul = strlen($request->pesan) > 50
                ? substr($request->pesan, 0, 50) . '...'
                : $request->pesan;
        }

        $diskusi = Diskusi::create([
            'id_pengguna' => $request->user()->id,
            'id_kelompok' => $request->id_kelompok,
            'judul'       => $judul,
            'pesan'       => $request->pesan,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pertanyaan dikirim',
            'data'    => $this->formatThread($diskusi->load('pengirim')),
        ], 201);
    }

    public function show($id)
    {
        $diskusi = Diskusi::with(['pengirim', 'replies.pengirim', 'kelompok'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                ...$this->formatThread($diskusi),
                'replies' => $diskusi->replies->map(fn ($r) => $this->formatReply($r)),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $diskusi = Diskusi::findOrFail($id);

        if ($diskusi->id_pengguna !== $request->user()->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak berhak mengubah diskusi ini',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|string|max:255',
            'pesan' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $diskusi->fill($request->only(['judul', 'pesan']));
        $diskusi->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Diskusi diperbarui',
            'data'    => $this->formatThread($diskusi->load('pengirim')),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $diskusi = Diskusi::findOrFail($id);

        if ($diskusi->id_pengguna !== $request->user()->id && $request->user()->peran !== 'mentor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak berhak menghapus diskusi ini',
            ], 403);
        }

        $diskusi->replies()->delete();
        $diskusi->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Diskusi dihapus',
        ]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['pesan' => 'required|string']);

        $parent = Diskusi::findOrFail($id);

        $reply = Diskusi::create([
            'id_pengguna' => $request->user()->id,
            'id_kelompok' => $parent->id_kelompok,
            'id_parent'   => $id,
            'pesan'       => $request->pesan,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Balasan dikirim',
            'data'    => $this->formatReply($reply->load('pengirim')),
        ], 201);
    }

    private function formatThread(Diskusi $diskusi): array
    {
        return [
            'id'             => $diskusi->id,
            'id_kelompok'    => $diskusi->id_kelompok,
            'nama_kelompok'  => $diskusi->kelompok?->nama_kelompok,
            'judul'          => $diskusi->judul,
            'pesan'          => $diskusi->pesan,
            'id_pengguna'    => $diskusi->id_pengguna,
            'nama_pengirim'  => $diskusi->pengirim
                ? $diskusi->pengirim->nama_depan . ' ' . $diskusi->pengirim->nama_belakang
                : '-',
            'peran'          => $diskusi->pengirim?->peran,
            'created_at'     => $diskusi->created_at?->toDateTimeString(),
            'waktu'          => $diskusi->created_at?->diffForHumans(),
            'jumlah_reply'   => $diskusi->relationLoaded('replies')
                ? $diskusi->replies->count()
                : $diskusi->replies()->count(),
            'replies'        => $diskusi->relationLoaded('replies')
                ? $diskusi->replies->map(fn ($r) => $this->formatReply($r))
                : [],
        ];
    }

    private function formatReply(Diskusi $reply): array
    {
        return [
            'id'            => $reply->id,
            'id_parent'     => $reply->id_parent,
            'pesan'         => $reply->pesan,
            'id_pengguna'   => $reply->id_pengguna,
            'nama_pengirim' => $reply->pengirim
                ? $reply->pengirim->nama_depan . ' ' . $reply->pengirim->nama_belakang
                : '-',
            'peran'         => $reply->pengirim?->peran,
            'created_at'    => $reply->created_at?->toDateTimeString(),
            'waktu'         => $reply->created_at?->diffForHumans(),
        ];
    }
}