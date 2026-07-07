<?php

namespace Tests\Feature;

use App\Models\Aktivitas;
use App\Models\Kalender;
use App\Models\Komoditas;
use App\Models\Kelompok;
use App\Models\Lahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AktivitasStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_dapat_menambah_aktivitas_manual(): void
    {
        $mentor = User::factory()->create(['peran' => 'mentor']);
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $komoditas = Komoditas::create(['nama_komoditas' => 'Timun Kyuri']);

        $kelompok = Kelompok::create([
            'nama_kelompok' => 'Kelompok A',
            'tipe_lahan'    => 'open_field',
            'id_mentor'     => $mentor->id,
            'id_komoditas'  => $komoditas->id,
        ]);
        $kelompok->anggota()->attach($siswa->id, ['peran' => 'ketua']);

        $lahan = Lahan::create([
            'id_pengguna'   => $siswa->id,
            'id_kelompok'   => $kelompok->id,
            'id_komoditas'  => $komoditas->id,
            'id_mentor'     => $mentor->id,
            'tipe_lahan'    => 'open_field',
            'jenis_lahan'   => 'individu',
            'tanggal_mulai' => now()->toDateString(),
            'luas'          => 10,
            'longitude'     => 106.8,
            'latitude'      => -6.2,
        ]);

        Sanctum::actingAs($siswa);

        $response = $this->postJson('/api/aktivitas', [
            'id_lahan'       => $lahan->id,
            'jenis_kegiatan' => 'Pemupukan',
            'tanggal'        => now()->toDateString(),
            'catatan'        => 'Pupuk NPK dosis rendah',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.jenis_kegiatan', 'Pemupukan');

        $this->assertDatabaseHas('aktivitas', [
            'catatan' => 'Pupuk NPK dosis rendah',
        ]);

        $this->assertDatabaseHas('kalender', [
            'id_pengguna'   => $siswa->id,
            'id_lahan'      => $lahan->id,
            'nama_kegiatan' => 'Pemupukan',
        ]);
    }

    public function test_form_data_mengembalikan_daftar_lahan(): void
    {
        $mentor = User::factory()->create(['peran' => 'mentor']);
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $komoditas = Komoditas::create(['nama_komoditas' => 'Tomat']);

        $kelompok = Kelompok::create([
            'nama_kelompok' => 'Kelompok B',
            'tipe_lahan'    => 'greenhouse',
            'id_mentor'     => $mentor->id,
            'id_komoditas'  => $komoditas->id,
        ]);
        $kelompok->anggota()->attach($siswa->id, ['peran' => 'anggota']);

        Lahan::create([
            'id_pengguna'   => $siswa->id,
            'id_kelompok'   => $kelompok->id,
            'id_komoditas'  => $komoditas->id,
            'id_mentor'     => $mentor->id,
            'tipe_lahan'    => 'greenhouse',
            'jenis_lahan'   => 'kelompok',
            'tanggal_mulai' => now()->toDateString(),
            'luas'          => 20,
            'longitude'     => 106.8,
            'latitude'      => -6.2,
        ]);

        Sanctum::actingAs($siswa);

        $this->getJson('/api/aktivitas/form-data')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.lahan');
    }
}
