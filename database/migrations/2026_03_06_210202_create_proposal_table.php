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
        Schema::create('proposal', function (Blueprint $table) {
            $table->id();
            // Siswa pembuat proposal
            $table->foreignId('id_pengguna')
                ->constrained('users')
                ->onDelete('cascade');
            // Kelompok pemilik proposal
            $table->foreignId('id_kelompok')
                ->constrained('kelompoks')
                ->onDelete('cascade');
            $table->foreignId('id_lahan')
                ->nullable()
                ->constrained('lahan')
                ->nullOnDelete();
            // Mentor yang mereview proposal
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('reviewed_rab_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // Identitas proposal
            $table->string('judul', 200);
            $table->string('nama_penyusun', 150);
            $table->enum('status', [
                'draft',
                'pending',
                'disetujui',
                'revisi',
                'ditolak',
            ])->default('draft');
            $table->enum('status_rab', [
                'draft',
                'pending',
                'disetujui',
                'revisi',
            ])->default('draft');
            // Catatan mentor saat review
            $table->text('catatan_mentor')->nullable();
            $table->text('catatan_rab_mentor')->nullable();
            // Informasi lahan
            $table->decimal('luas_lahan', 10, 2);
            $table->integer('jumlah_populasi');
            // BAB 1 — PENDAHULUAN
            $table->text('latar_belakang');
            // BAB 2 — MAKSUD DAN TUJUAN
            $table->text('maksud_tujuan');
            // BAB 3 — WAKTU DAN TEMPAT
            $table->text('waktu_tempat');
            // BAB 4 — RENCANA PENELITIAN
            $table->text('rencana_penelitian');
            // BAB 5 — ANALISIS USAHA
            $table->string('nama_tanaman', 100);
            $table->decimal('perkiraan_panen_per_pohon', 10, 2)
                ->nullable();
            $table->decimal('total_panen_kg', 10, 2);
            $table->decimal('harga_satuan', 12, 2);
            $table->string('jarak_tanam', 50)
                ->nullable();
            $table->string('masa_periode_tanam', 50)
                ->nullable();
            $table->text('kesimpulan_analisis')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal');
    }
};