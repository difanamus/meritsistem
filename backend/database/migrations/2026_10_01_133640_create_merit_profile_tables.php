<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['penugasan_operasi', 'prestasi_personel', 'penghargaan_personel'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->foreignId('personel_id')->constrained('personel')->restrictOnDelete();
                $table->foreignId('bidang_fungsi_id')->nullable()->index()->constrained('bidang_fungsi')->restrictOnDelete();
                $table->string('nama');
                $table->string('tingkat', 50);
                if ($name === 'penugasan_operasi') {
                    $table->string('kode_operasi', 100)->nullable();
                    $table->string('jenis_operasi', 100);
                    $table->string('wilayah');
                    $table->string('peran');
                    $table->string('satgas_unit');
                    $table->date('tanggal_mulai');
                    $table->date('tanggal_selesai')->nullable();
                    $table->string('nomor_surat_perintah', 100)->nullable();
                    $table->index(['personel_id', 'deleted_at', 'tanggal_mulai'], 'operasi_personel_period_idx');
                } elseif ($name === 'prestasi_personel') {
                    $table->string('kategori', 50);
                    $table->string('hasil');
                    $table->string('penyelenggara');
                    $table->date('tanggal')->nullable();
                    $table->unsignedSmallInteger('tahun');
                    $table->string('peran', 50);
                    $table->index(['personel_id', 'deleted_at', 'tahun'], 'prestasi_personel_year_idx');
                } else {
                    $table->string('pemberi');
                    $table->string('nomor_keputusan', 100)->nullable();
                    $table->date('tanggal_keputusan');
                    $table->text('alasan');
                    $table->index(['personel_id', 'deleted_at', 'tanggal_keputusan'], 'penghargaan_personel_date_idx');
                }
                $table->text('keterangan')->nullable();
                $table->string('status_verifikasi', 30)->default('belum_diverifikasi');
                $table->foreignId('verified_by')->nullable()->index()->constrained('users')->restrictOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->string('dokumen_path')->nullable();
                $table->string('dokumen_nama_asli')->nullable();
                $table->string('dokumen_mime', 100)->nullable();
                $table->unsignedBigInteger('dokumen_ukuran')->nullable();
                $table->foreignId('created_by')->nullable()->index()->constrained('users')->restrictOnDelete();
                $table->foreignId('updated_by')->nullable()->index()->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penghargaan_personel');
        Schema::dropIfExists('prestasi_personel');
        Schema::dropIfExists('penugasan_operasi');
    }
};
