<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('penugasan_operasi')]
#[Fillable(['personel_id', 'bidang_fungsi_id', 'nama', 'tingkat', 'kode_operasi', 'jenis_operasi', 'wilayah', 'peran', 'satgas_unit', 'tanggal_mulai', 'tanggal_selesai', 'nomor_surat_perintah', 'keterangan', 'status_verifikasi', 'verified_by', 'verified_at', 'dokumen_path', 'dokumen_nama_asli', 'dokumen_mime', 'dokumen_ukuran', 'created_by', 'updated_by'])]
class PenugasanOperasi extends MeritRecord {}
