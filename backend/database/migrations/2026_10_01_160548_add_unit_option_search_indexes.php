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
        DB::statement('CREATE INDEX unit_organisasi_nama_trgm_idx ON unit_organisasi USING gin (nama gin_trgm_ops) WHERE deleted_at IS NULL AND is_active = TRUE');
        DB::statement('CREATE INDEX unit_organisasi_kode_trgm_idx ON unit_organisasi USING gin (kode gin_trgm_ops) WHERE deleted_at IS NULL AND is_active = TRUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS unit_organisasi_nama_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS unit_organisasi_kode_trgm_idx');
    }
};
