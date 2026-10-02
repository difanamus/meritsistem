<?php

namespace App\Models;

use Database\Factories\DisciplineSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['personel_id', 'source_system', 'source_record_id', 'jenis', 'ringkasan', 'nomor_keputusan', 'tanggal_keputusan', 'sanksi', 'instansi_penerbit', 'status_keputusan', 'keterangan_pembatalan', 'source_updated_at', 'synced_at'])]
class DisciplineSnapshot extends Model
{
    /** @use HasFactory<DisciplineSnapshotFactory> */
    use HasFactory;

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class);
    }

    protected function casts(): array
    {
        return ['tanggal_keputusan' => 'date', 'source_updated_at' => 'datetime', 'synced_at' => 'datetime'];
    }
}
