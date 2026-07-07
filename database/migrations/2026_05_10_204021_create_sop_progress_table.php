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
        Schema::create('sop_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')
                ->constrained('users')
                ->onDelete('cascade'); // kalau user dihapus, progressnya ikut terhapus
            $table->foreignId('id_sop_langkah')
                ->constrained('sop_langkah')
                ->onDelete('cascade'); // kalau langkah dihapus, progressnya ikut terhapus
            $table->boolean('selesai')->default(false); // false = belum, true = sudah diceklis
            $table->unique(['id_pengguna', 'id_sop_langkah']); // satu siswa hanya punya satu progress per langkah
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_progress');
    }
};
