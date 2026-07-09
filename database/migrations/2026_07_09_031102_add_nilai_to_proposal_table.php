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
        Schema::table('proposal', function (Blueprint $table) {
            if (!Schema::hasColumn('proposal', 'nilai_proposal')) {
                $table->decimal('nilai_proposal', 5, 2)->nullable()->after('catatan_mentor');
            }
            if (!Schema::hasColumn('proposal', 'nilai_rab')) {
                $table->decimal('nilai_rab', 5, 2)->nullable()->after('catatan_rab_mentor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal', function (Blueprint $table) {
            if (Schema::hasColumn('proposal', 'nilai_proposal')) {
                $table->dropColumn('nilai_proposal');
            }
            if (Schema::hasColumn('proposal', 'nilai_rab')) {
                $table->dropColumn('nilai_rab');
            }
        });
    }
};
