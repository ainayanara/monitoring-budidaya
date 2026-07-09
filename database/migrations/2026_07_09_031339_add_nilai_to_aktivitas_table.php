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
        Schema::table('aktivitas', function (Blueprint $table) {
            if (!Schema::hasColumn('aktivitas', 'nilai')) {
                $table->decimal('nilai', 5, 2)->nullable()->after('catatan_mentor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aktivitas', function (Blueprint $table) {
            if (Schema::hasColumn('aktivitas', 'nilai')) {
                $table->dropColumn('nilai');
            }
        });
    }
};
