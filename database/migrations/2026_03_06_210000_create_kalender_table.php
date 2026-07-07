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
        Schema::create('kalender', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->constrained('users');
            $table->foreignId('id_komoditas')->constrained('komoditas');
            $table->foreignId('id_lahan')
            ->constrained('lahan')
            ->cascadeOnDelete();
            $table->string('nama_tahapan', 100);
            $table->string('nama_kegiatan', 150);
            $table->enum('tipe_lahan', ['greenhouse', 'open_field']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kalender');
    }
};
