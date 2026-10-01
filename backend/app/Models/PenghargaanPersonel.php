<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('penghargaan_personel')]
#[Fillable(['personel_id', 'bidang_fungsi_id', 'nama', 'tingkat', 'pemberi', 'nomor_keputusan', 'tanggal_keputusan', 'alasan', 'keterangan', 'status_verifikasi', 'verified_by', 'verified_at', 'dokumen_path', 'dokumen_nama_asli', 'dokumen_mime', 'dokumen_ukuran', 'created_by', 'updated_by'])]
class PenghargaanPersonel extends MeritRecord {}
