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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('personel_id')->nullable()->unique()->constrained('personel')->restrictOnDelete();
        });
        Schema::table('personel', function (Blueprint $table): void {
            $table->text('alasan_arsip')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personel', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn('alasan_arsip');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('personel_id');
        });
    }
};
