<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kalender', 'id_lahan')) {
            Schema::table('kalender', function (Blueprint $table) {
                $table->foreignId('id_lahan')
                    ->nullable()
                    ->after('id_komoditas')
                    ->constrained('lahan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('kalender', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_lahan');
        });
    }
};
