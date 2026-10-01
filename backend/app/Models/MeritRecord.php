<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class MeritRecord extends Model
{
    use HasFactory, SoftDeletes;

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class);
    }

    public function bidangFungsi(): BelongsTo
    {
        return $this->belongsTo(BidangFungsi::class);
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date',
            'tanggal' => 'date', 'tanggal_keputusan' => 'date', 'tahun' => 'integer',
            'dokumen_ukuran' => 'integer', 'verified_at' => 'datetime',
        ];
    }
}
