export type UserRole = 'system_admin' | 'admin_ssdm' | 'operator'

export interface UserScope {
  id: number
  scope_type: 'own_unit' | 'unit_and_descendants'
  scope_type_label: string
  unit_organisasi: ReferenceItem
  is_active: boolean
  berlaku_mulai?: string | null
  berlaku_sampai?: string | null
}

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  role_label: string
  is_active: boolean
  scopes: UserScope[]
  permissions?: { create_personnel: boolean }
  created_at?: string
  updated_at?: string
}

export interface ReferenceItem {
  id: number
  kode?: string
  nama: string
  parent_id?: number | null
  jenis_unit?: string
  jenis_personel?: string
  urutan?: number
}

export interface Position {
  id: number
  nama_jabatan: string
  is_jabatan_utama?: boolean
  tanggal_mulai: string
  tanggal_selesai?: string | null
  unit_organisasi?: ReferenceItem | string
  bidang_fungsi?: ReferenceItem | string
  jenis_penugasan?: ReferenceItem | string
}

export interface Qualification {
  id: number
  nama_kualifikasi: string
  tahun?: number | null
  jenis_kualifikasi: string
  bidang_fungsi?: string | null
}

export interface Personnel {
  id: number
  jenis_personel: 'polri' | 'pns'
  jenis_personel_label: string
  nomor_identitas: string
  nama_lengkap: string
  pangkat: ReferenceItem
  tempat_lahir: string
  tanggal_lahir: string
  unit_organisasi: ReferenceItem
  status: 'aktif' | 'pensiun' | 'nonaktif'
  status_label: string
  jumlah_kualifikasi?: number
  ringkasan_relevan?: {
    jumlah_kualifikasi: number
    durasi_pengalaman_hari: number
    tahun_kualifikasi_terbaru: number | null
  }
  ringkasan_operasi?: { jumlah: number; total_durasi_hari: number }
  jabatan_utama_aktif?: Position | null
  penugasan_tambahan_aktif?: Position[]
  kualifikasi?: Qualification[]
  riwayat_jabatan?: Position[]
  created_at: string
  updated_at: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface ReferenceOptions {
  unit_organisasi: ReferenceItem[]
  pangkat: ReferenceItem[]
  bidang_fungsi: ReferenceItem[]
  jenis_kualifikasi: ReferenceItem[]
  jenis_penugasan: ReferenceItem[]
}

export type NonUnitReferenceOptions = Omit<ReferenceOptions, 'unit_organisasi'>

export interface PrivateDocument {
  nama_asli: string
  mime: string
  ukuran: number
  download_url: string
}

export interface QualificationRecord {
  id: number
  personel_id: number
  jenis_kualifikasi: ReferenceItem
  bidang_fungsi: ReferenceItem | null
  nama_kualifikasi: string
  jenjang: string | null
  bidang_studi: string | null
  institusi_penyelenggara: string | null
  tanggal_mulai: string | null
  tanggal_selesai: string | null
  tahun: number | null
  nomor_dokumen: string | null
  keterangan: string | null
  dokumen: PrivateDocument | null
}

export interface PositionRecord {
  id: number
  personel_id: number
  nama_jabatan: string
  unit_organisasi: ReferenceItem
  bidang_fungsi: ReferenceItem
  jenis_penugasan: ReferenceItem
  tanggal_mulai: string
  tanggal_selesai: string | null
  nivelering: string | null
  is_jabatan_utama: boolean
  keterangan: string | null
  dokumen: PrivateDocument | null
}

export type MeritKind = 'penugasan-operasi' | 'prestasi' | 'penghargaan'

export interface MeritRecord {
  id: number
  personel_id: number
  nama: string
  tingkat: string
  bidang_fungsi: ReferenceItem | null
  keterangan: string | null
  status_verifikasi: 'belum_diverifikasi' | 'terverifikasi'
  verified_by: { id: number; nama: string } | null
  verified_at: string | null
  dokumen: PrivateDocument | null
  kode_operasi?: string | null
  jenis_operasi?: string
  wilayah?: string
  peran?: string
  satgas_unit?: string
  tanggal_mulai?: string
  tanggal_selesai?: string | null
  nomor_surat_perintah?: string | null
  kategori?: string
  hasil?: string
  penyelenggara?: string
  tanggal?: string | null
  tahun?: number
  pemberi?: string
  nomor_keputusan?: string | null
  tanggal_keputusan?: string
  alasan?: string
}
