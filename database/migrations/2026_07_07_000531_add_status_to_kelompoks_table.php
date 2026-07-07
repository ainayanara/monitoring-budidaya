<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Isi migration dengan:
    public function up()
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            if (!Schema::hasColumn('kelompoks', 'status')) {
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompoks', function (Blueprint $table) {
            //
        });
    }
};
