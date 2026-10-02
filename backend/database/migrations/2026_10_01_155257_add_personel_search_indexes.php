<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX personel_nama_lengkap_trgm_idx ON personel USING gin (nama_lengkap gin_trgm_ops) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX personel_nomor_identitas_trgm_idx ON personel USING gin (nomor_identitas gin_trgm_ops) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS personel_nama_lengkap_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS personel_nomor_identitas_trgm_idx');
    }
};
