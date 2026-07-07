<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Aktivitas extends Model
{
    protected $table = 'aktivitas';

    protected $fillable = [
        'id_kalender',
        'tanggal_aktivitas',
        'status',
        'catatan',
        'dokumentasi',
        'status_verifikasi',
        'catatan_mentor',
        'verified_by',
    ];

    protected $casts = [
        'tanggal_aktivitas' => 'date',
    ];

    /**
     * Tentukan status berdasarkan tanggal jadwal.
     * "mendatang" di UI diperlakukan sama dengan "terjadwal".
     */
    public static function resolveStatus(Carbon $date, string $currentStatus = 'terjadwal'): string
    {
        if ($currentStatus === 'selesai') {
            return 'selesai';
        }

        if ($date->isToday()) {
            return 'hari_ini';
        }

        if ($date->isPast()) {
            return 'terlewat';
        }

        return 'terjadwal';
    }

    /**
     * Sinkronkan aktivitas dari kalender milik user.
     */
    public static function syncForUser(int $userId): void
    {
        $jadwal = Kalender::where('id_pengguna', $userId)->get();

        foreach ($jadwal as $j) {
            $date = Carbon::parse($j->tanggal_mulai);

            $aktivitas = self::firstOrCreate(
                ['id_kalender' => $j->id],
                [
                    'tanggal_aktivitas' => $j->tanggal_mulai,
                    'status'            => 'terjadwal',
                ]
            );

            if ($aktivitas->status !== 'selesai') {
                $aktivitas->update([
                    'tanggal_aktivitas' => $j->tanggal_mulai,
                    'status'            => self::resolveStatus($date, $aktivitas->status),
                ]);
            }
        }
    }

    public function kalender()
    {
        return $this->belongsTo(Kalender::class, 'id_kalender');
    }

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
