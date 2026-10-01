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
        Schema::create('unit_organisasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('unit_organisasi')->restrictOnDelete();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->string('jenis_unit', 30)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_organisasi');
    }
};
