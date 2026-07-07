<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('aktivitas')) {  // ← tambah pengecekan ini
            Schema::create('aktivitas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_kalender')
                ->constrained('kalender')
                ->onDelete('cascade');

            $table->date('tanggal_aktivitas');

            // status aktivitas siswa
            $table->enum('status', [
                'terjadwal',
                'hari_ini',
                'terlewat',
                'selesai'
            ])->default('terjadwal');

            // laporan siswa
            $table->text('catatan')->nullable();
            $table->string('dokumentasi')->nullable();

            // verifikasi mentor
            $table->enum('status_verifikasi', [
                'pending',
                'disetujui',
                'revisi'
            ])->default('pending');

            $table->text('catatan_mentor')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aktivitas');
    }
};
