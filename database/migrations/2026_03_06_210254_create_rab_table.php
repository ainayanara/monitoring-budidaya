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
        Schema::create('rab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_proposal')->constrained('proposal')->onDelete('cascade');
            
            // Detail biaya
            $table->enum('jenis_biaya', ['tetap', 'tidak_tetap']);
            $table->string('nama_item', 150);
            $table->string('satuan', 50);
            $table->integer('volume');
            $table->decimal('harga', 12, 2);
            $table->decimal('total', 12, 2); // Hasil volume * harga
            
            // Hasil perhitungan mandiri 
            $table->decimal('laba', 12, 2)->nullable();
            $table->decimal('bep_unit', 12, 2)->nullable();
            $table->decimal('bep_harga', 12, 2)->nullable();
            $table->decimal('b_c_ratio', 8, 2)->nullable(); 
            $table->decimal('r_c_ratio', 8, 2)->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rab');
    }
};
