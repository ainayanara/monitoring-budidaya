    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::table('proposal', function (Blueprint $table) {
                if (!Schema::hasColumn('proposal', 'id_lahan')) {
                    $table->foreignId('id_lahan')
                        ->nullable()
                        ->after('id_kelompok')
                        ->constrained('lahan')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('proposal', 'status_rab')) {
                    $table->enum('status_rab', ['draft', 'pending', 'disetujui', 'revisi'])
                        ->default('draft')
                        ->after('status');
                }

                if (!Schema::hasColumn('proposal', 'catatan_rab_mentor')) {
                    $table->text('catatan_rab_mentor')->nullable()->after('catatan_mentor');
                }

                if (!Schema::hasColumn('proposal', 'reviewed_rab_by')) {
                    $table->foreignId('reviewed_rab_by')
                        ->nullable()
                        ->after('reviewed_by')
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });

            // Perluas enum status proposal agar mendukung draft
            if (DB::getDriverName() === 'mysql') {
                DB::statement(
                    "ALTER TABLE proposal MODIFY COLUMN status ENUM('draft','pending','disetujui','revisi','ditolak') NOT NULL DEFAULT 'draft'"
                );
            } else {
                // SQLite / testing: cukup update default via schema builder tidak bisa alter enum,
                // nilai draft tetap valid karena disimpan sebagai string.
            }
        }

        public function down(): void
        {
            Schema::table('proposal', function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_lahan');
                $table->dropConstrainedForeignId('reviewed_rab_by');
                $table->dropColumn(['status_rab', 'catatan_rab_mentor']);
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement(
                    "ALTER TABLE proposal MODIFY COLUMN status ENUM('pending','disetujui','revisi','ditolak') NOT NULL DEFAULT 'pending'"
                );
            }
        }
    };
