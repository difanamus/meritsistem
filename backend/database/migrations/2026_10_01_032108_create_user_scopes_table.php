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
        Schema::create('user_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_organisasi_id')->constrained('unit_organisasi')->restrictOnDelete();
            $table->string('scope_type', 30);
            $table->boolean('is_active')->default(true);
            $table->date('berlaku_mulai')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'unit_organisasi_id']);
            $table->index(['unit_organisasi_id', 'scope_type', 'is_active'], 'user_scopes_unit_scope_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
    }
};
