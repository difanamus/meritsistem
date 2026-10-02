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
        Schema::create('discipline_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personel_id')->constrained('personel')->restrictOnDelete();
            $table->string('source_system')->default('prototype_demo');
            $table->string('source_record_id');
            $table->enum('jenis', ['disiplin', 'kode_etik']);
            $table->text('ringkasan');
            $table->string('nomor_keputusan');
            $table->date('tanggal_keputusan');
            $table->text('sanksi');
            $table->string('instansi_penerbit');
            $table->enum('status_keputusan', ['final', 'dibatalkan']);
            $table->text('keterangan_pembatalan')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['source_system', 'source_record_id']);
            $table->index(['personel_id', 'tanggal_keputusan', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_snapshots');
    }
};
