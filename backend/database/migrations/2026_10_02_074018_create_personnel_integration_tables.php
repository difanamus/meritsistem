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
        Schema::create('personnel_sync_checkpoints', function (Blueprint $table) {
            $table->string('source')->primary();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
        Schema::create('personnel_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('mode');
            $table->unsignedInteger('version');
            $table->unsignedInteger('baseline_checkpoint');
            $table->string('status')->default('preview');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('personnel_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('personnel_import_runs')->cascadeOnDelete();
            $table->string('source_record_id');
            $table->json('payload');
            $table->string('action');
            $table->string('status');
            $table->string('reason', 1000)->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->unique(['run_id', 'source_record_id']);
            $table->index(['run_id', 'status', 'id']);
        });
        Schema::create('personnel_source_links', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('source_record_id');
            $table->foreignId('personel_id')->constrained('personel')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->json('last_source_payload');
            $table->string('local_hash', 64);
            $table->unique(['source', 'source_record_id']);
            $table->unique(['source', 'personel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_source_links');
        Schema::dropIfExists('personnel_import_items');
        Schema::dropIfExists('personnel_import_runs');
        Schema::dropIfExists('personnel_sync_checkpoints');
    }
};
