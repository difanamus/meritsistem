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
        Schema::create('kualifikasi_personel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->foreignId('jenis_kualifikasi_id')->constrained('jenis_kualifikasi')->restrictOnDelete();
            $table->foreignId('bidang_fungsi_id')->nullable()->constrained('bidang_fungsi')->nullOnDelete();
            $table->string('nama_kualifikasi');
            $table->string('jenjang', 100)->nullable();
            $table->string('bidang_studi')->nullable();
            $table->string('institusi_penyelenggara')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('nomor_dokumen')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen_pendukung_path')->nullable();
            $table->string('dokumen_pendukung_nama_asli')->nullable();
            $table->string('dokumen_pendukung_mime', 100)->nullable();
            $table->unsignedBigInteger('dokumen_pendukung_ukuran')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['personel_id', 'jenis_kualifikasi_id']);
            $table->index(['bidang_fungsi_id', 'tanggal_selesai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kualifikasi_personel');
    }
};
