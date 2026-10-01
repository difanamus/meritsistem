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
        Schema::create('personel', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_personel', 10)->index();
            $table->string('nomor_identitas', 30)->unique();
            $table->string('nama_lengkap');
            $table->foreignId('pangkat_id')->constrained('pangkat')->restrictOnDelete();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->foreignId('unit_organisasi_id')->constrained('unit_organisasi')->restrictOnDelete();
            $table->string('status', 20)->default('aktif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_organisasi_id', 'status']);
            $table->index(['nama_lengkap', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personel');
    }
};
