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
        Schema::table('lahan', function (Blueprint $table) {
            $table->dropForeign(['id_kelompok']);
            $table->dropForeign(['id_mentor']);
        });

        Schema::table('lahan', function (Blueprint $table) {
            $table->foreignId('id_kelompok')->nullable()->change();
            $table->foreignId('id_mentor')->nullable()->change();
        });

        Schema::table('lahan', function (Blueprint $table) {
            $table->foreign('id_kelompok')->references('id')->on('kelompoks')->onDelete('cascade');
            $table->foreign('id_mentor')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lahan', function (Blueprint $table) {
            $table->dropForeign(['id_kelompok']);
            $table->dropForeign(['id_mentor']);
        });

        Schema::table('lahan', function (Blueprint $table) {
            $table->foreignId('id_kelompok')->nullable(false)->change();
            $table->foreignId('id_mentor')->nullable(false)->change();
        });

        Schema::table('lahan', function (Blueprint $table) {
            $table->foreign('id_kelompok')->references('id')->on('kelompoks')->onDelete('cascade');
            $table->foreign('id_mentor')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
