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
        Schema::create('sop_langkah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sop')
                ->constrained('sop')
                ->onDelete('cascade'); // kalau SOP dihapus, langkahnya ikut terhapus
            $table->integer('urutan')->default(1); // urutan langkah ke berapa
            $table->string('judul_langkah'); // contoh: "Pembersihan lahan"
            $table->text('deskripsi')->nullable(); // penjelasan langkahnya
            $table->text('hasil_diharapkan')->nullable(); // target setelah langkah selesai
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_langkah');
    }
};
