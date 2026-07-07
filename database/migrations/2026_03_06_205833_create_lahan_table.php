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
        Schema::create('lahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->constrained('users')->onDelete('cascade');
            $table->foreignId('id_kelompok')->constrained('kelompoks')->onDelete('cascade');
            $table->foreignId('id_komoditas')->constrained('komoditas')->onDelete('cascade');
            $table->foreignId('id_mentor')->constrained('users')->onDelete('cascade');
            $table->enum('tipe_lahan', ['greenhouse','open_field']);
            $table->enum('jenis_lahan', ['kelompok','individu']);
            $table->date('tanggal_mulai');
            $table->decimal('luas', 10, 2);
            $table->decimal('longitude', 11, 8);
            $table->decimal('latitude', 10, 8);
            $table->string('dokumentasi')->nullable();
            $table->text('catatan_awal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lahan');
    }
};