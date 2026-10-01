<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->string('nama_jabatan');
            $table->foreignId('unit_organisasi_id')->constrained('unit_organisasi')->restrictOnDelete();
            $table->foreignId('bidang_fungsi_id')->constrained('bidang_fungsi')->restrictOnDelete();
            $table->foreignId('jenis_penugasan_id')->constrained('jenis_penugasan')->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('nivelering', 100)->nullable();
            $table->boolean('is_jabatan_utama')->default(true);
            $table->text('keterangan')->nullable();
            $table->string('dokumen_sk_path')->nullable();
            $table->string('dokumen_sk_nama_asli')->nullable();
            $table->string('dokumen_sk_mime', 100)->nullable();
            $table->unsignedBigInteger('dokumen_sk_ukuran')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['personel_id', 'tanggal_mulai']);
            $table->index(['bidang_fungsi_id', 'tanggal_mulai', 'tanggal_selesai'], 'riwayat_jabatan_bidang_tanggal_index');
        });

        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX riwayat_jabatan_satu_utama_aktif_unique '
                .'ON riwayat_jabatan (personel_id) '
                .'WHERE is_jabatan_utama = true AND tanggal_selesai IS NULL AND deleted_at IS NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_jabatan');
    }
};
