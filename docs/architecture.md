# Arsitektur dan Alur Utama

Dokumen ini merangkum struktur data serta alur keamanan dan mutasi pada Merit System Personel Polri. Diagram menggunakan Mermaid dan dapat dirender langsung oleh GitHub/GitLab.

## ERD domain utama

```mermaid
erDiagram
    USERS ||--o{ USER_SCOPES : memiliki
    PERSONEL o|--o| USERS : pemilik_akun_individu
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : login_dengan
    UNIT_ORGANISASI ||--o{ UNIT_ORGANISASI : parent_dari
    UNIT_ORGANISASI ||--o{ USER_SCOPES : menjadi_scope
    UNIT_ORGANISASI ||--o{ PERSONEL : menaungi
    PANGKAT ||--o{ PERSONEL : dimiliki
    PERSONEL ||--o{ KUALIFIKASI_PERSONEL : memiliki
    PERSONEL ||--o{ RIWAYAT_JABATAN : menjalani
    PERSONEL ||--o{ PENUGASAN_OPERASI : menjalani
    PERSONEL ||--o{ PRESTASI_PERSONEL : mencapai
    PERSONEL ||--o{ PENGHARGAAN_PERSONEL : menerima
    PERSONEL ||--o{ DISCIPLINE_SNAPSHOTS : prototype_read_only
    JENIS_KUALIFIKASI ||--o{ KUALIFIKASI_PERSONEL : mengelompokkan
    BIDANG_FUNGSI o|--o{ KUALIFIKASI_PERSONEL : relevan_untuk
    BIDANG_FUNGSI ||--o{ RIWAYAT_JABATAN : bidang
    BIDANG_FUNGSI o|--o{ PENUGASAN_OPERASI : bidang
    BIDANG_FUNGSI o|--o{ PRESTASI_PERSONEL : bidang
    JENIS_PENUGASAN ||--o{ RIWAYAT_JABATAN : berstatus
    UNIT_ORGANISASI ||--o{ RIWAYAT_JABATAN : lokasi

    USERS {
        bigint id PK
        bigint personel_id FK,UK
        string name
        string email UK
        string password
        string role
        boolean is_active
    }
    USER_SCOPES {
        bigint id PK
        bigint user_id FK
        bigint unit_organisasi_id FK
        string scope_type
        boolean is_active
        date berlaku_mulai
        date berlaku_sampai
    }
    UNIT_ORGANISASI {
        bigint id PK
        bigint parent_id FK
        string kode UK
        string nama
        string jenis_unit
        boolean is_active
    }
    PANGKAT {
        bigint id PK
        string kode UK
        string nama
        string jenis_personel
        integer urutan
        boolean is_active
    }
    PERSONEL {
        bigint id PK
        string jenis_personel
        string nomor_identitas UK
        string nama_lengkap
        bigint pangkat_id FK
        string tempat_lahir
        date tanggal_lahir
        bigint unit_organisasi_id FK
        string status
        timestamp deleted_at
        string alasan_arsip
        bigint archived_by FK
    }
    KUALIFIKASI_PERSONEL {
        bigint id PK
        bigint personel_id FK
        bigint jenis_kualifikasi_id FK
        bigint bidang_fungsi_id FK
        string nama_kualifikasi
        date tanggal_mulai
        date tanggal_selesai
        integer tahun
        string dokumen_pendukung_path
        timestamp deleted_at
    }
    RIWAYAT_JABATAN {
        bigint id PK
        bigint personel_id FK
        string nama_jabatan
        bigint unit_organisasi_id FK
        bigint bidang_fungsi_id FK
        bigint jenis_penugasan_id FK
        date tanggal_mulai
        date tanggal_selesai
        boolean is_jabatan_utama
        string dokumen_sk_path
        timestamp deleted_at
    }
    PENUGASAN_OPERASI {
        bigint id PK
        bigint personel_id FK
        bigint bidang_fungsi_id FK
        string nama
        string jenis_operasi
        string tingkat
        string wilayah
        string peran
        string satgas_unit
        date tanggal_mulai
        date tanggal_selesai
        string status_verifikasi
        bigint verified_by FK
        string dokumen_path
        timestamp deleted_at
    }
    PRESTASI_PERSONEL {
        bigint id PK
        bigint personel_id FK
        bigint bidang_fungsi_id FK
        string nama
        string kategori
        string tingkat
        string hasil
        string penyelenggara
        integer tahun
        string peran
        string status_verifikasi
        bigint verified_by FK
        string dokumen_path
        timestamp deleted_at
    }
    PENGHARGAAN_PERSONEL {
        bigint id PK
        bigint personel_id FK
        string nama
        string pemberi
        string tingkat
        string nomor_keputusan
        date tanggal_keputusan
        string alasan
        string status_verifikasi
        bigint verified_by FK
        string dokumen_path
        timestamp deleted_at
    }
    BIDANG_FUNGSI {
        bigint id PK
        string kode UK
        string nama
        boolean is_active
    }
    JENIS_KUALIFIKASI {
        bigint id PK
        string kode UK
        string nama
        boolean is_active
    }
    JENIS_PENUGASAN {
        bigint id PK
        string kode UK
        string nama
        boolean is_active
    }
```

Catatan constraint penting:

- `nomor_identitas` unik dan tetap bertipe string agar nol di depan tidak hilang.
- Partial unique index menjamin hanya satu jabatan utama aktif per personel.
- Soft delete dipakai pada data domain agar penghapusan operasional tidak langsung menghilangkan histori.
- Path PDF tersimpan di database, sedangkan berkas berada pada storage privat.
- Penugasan operasi, prestasi, dan penghargaan masing-masing memiliki satu PDF opsional privat, status verifikasi, aktor, dan waktu verifikasi. Perubahan fakta membatalkan verifikasi sebelumnya.

## Alur authentication

```mermaid
sequenceDiagram
    actor U as Pengguna
    participant F as React SPA
    participant A as Laravel API
    participant D as Database

    U->>F: Isi email dan password
    F->>A: POST /api/v1/auth/login
    A->>D: Cari user aktif dan verifikasi hash
    alt kredensial valid dan akun aktif
        A->>D: Buat Sanctum token (kedaluwarsa 8 jam)
        A-->>F: token + profil + scope
        F->>F: Simpan sesi
        F-->>U: Buka daftar personel
    else tidak valid/nonaktif
        A-->>F: 422 tanpa membocorkan detail sensitif
        F-->>U: Tampilkan pesan login gagal
    end
```

Semua endpoint selain login wajib menggunakan `Authorization: Bearer <token>`. Middleware juga memastikan akun pemilik token masih aktif.

## Alur authorization dan scope organisasi

```mermaid
flowchart TD
    A[Request terautentikasi] --> B{Role global?}
    B -- SYSTEM_ADMIN / ADMIN_SSDM --> G[Izinkan seluruh unit]
    B -- OPERATOR --> C[Ambil user_scopes aktif dan masih berlaku]
    C --> D{scope_type}
    D -- OWN_UNIT --> E[Unit tepat sama]
    D -- UNIT_AND_DESCENDANTS --> F[Unit scope + semua descendant]
    E --> H{Resource berada dalam allowed unit?}
    F --> H
    H -- Ya --> I[Policy mengizinkan dan query tetap di-scope]
    H -- Tidak --> J[HTTP 403]
```

Scope diterapkan dua lapis: query hanya mengambil data yang boleh terlihat dan policy kembali memeriksa resource individual. Dengan demikian, mengganti ID pada URL tidak melewati authorization.

## Alur pergantian jabatan dan mutasi

```mermaid
flowchart TD
    A[Request ganti jabatan / mutasi] --> B[Validasi personel aktif]
    B --> C[Validasi jabatan utama aktif]
    C --> D[Validasi tanggal jabatan baru]
    D --> E{Mutasi lintas unit?}
    E -- Ya --> F[Validasi unit asal dan tujuan berada dalam scope]
    E -- Tidak --> G[Gunakan unit personel saat ini]
    F --> H[Mulai transaction + lock row]
    G --> H
    H --> I[Tutup jabatan utama lama pada H-1]
    I --> J{Mutasi?}
    J -- Ya --> K[Perbarui unit personel]
    J -- Tidak --> L[Buat jabatan utama baru]
    K --> L
    L --> M[Simpan actor dan dokumen SK opsional]
    M --> N{Semua langkah berhasil?}
    N -- Ya --> O[Commit]
    N -- Tidak --> P[Rollback seluruh perubahan]
```

Proses ini mencegah kondisi personel berpindah unit tetapi tidak mempunyai jabatan baru, atau mempunyai dua jabatan utama aktif akibat kegagalan di tengah proses.

## Alur pencarian kualifikasi

```mermaid
flowchart LR
    A[Pilih fungsi: contoh Intelkam] --> B[Filter kualifikasi terkait fungsi]
    A --> C[Filter riwayat jabatan terkait fungsi]
    B --> D[Hitung jumlah kegiatan relevan]
    C --> E[Hitung durasi pengalaman relevan]
    D --> F[Daftar personel dalam scope]
    E --> F
    F --> G[User memilih urutan]
```

Jumlah kegiatan dan durasi pengalaman adalah ringkasan faktual, bukan skor otomatis. Keputusan merit tetap dilakukan pimpinan dengan membaca riwayat personel.

## Registrasi dan arsip

- Registrasi menerima pendidikan umum wajib, pendidikan Polri wajib untuk POLRI (opsional bagi PNS), serta riwayat awal opsional. Pendidikan tetap berupa record `kualifikasi_personel`, bukan kolom tunggal. Jenis pendidikan ditentukan server dari kode referensi.
- Identitas, jabatan awal, pendidikan, dan seluruh riwayat yang diisi disimpan dalam satu transaksi. Validasi menggunakan aturan domain yang sama dengan form profil. PDF tersimpan privat; kegagalan transaksi membersihkan file yang baru dibuat. Maksimal 20 record per jenis riwayat pada satu registrasi, PDF maksimal 5 MB per file.
- Riwayat jabatan saat registrasi harus sudah selesai; periode jabatan utama tidak boleh bertumpang tindih. Penempatan aktif diisi melalui jabatan utama awal.
- `users.personel_id` nullable dan unik. Akun Admin SSDM/Operator baru wajib terhubung ke personel aktif, nama akun berasal dari personel; programmer eksternal boleh tanpa personel. Pemilik akun yang telah terhubung tidak dapat dialihkan ke individu lain. Scope tetap merupakan assignment terpisah; mutasi tidak otomatis memindahkan kewenangan akun.
- Data lama termasuk akun demo dipertahankan tanpa menebak identitas pemilik atau pendidikan. Akun staff lama yang belum terhubung wajib dipilihkan personel pada pembaruan berikutnya.
- Pengarsipan hanya untuk System Admin/Admin SSDM, alasan wajib. Soft delete menyembunyikan profil tanpa mengubah tanggal jabatan; akun terkait dinonaktifkan, scope dimatikan, token dicabut. Kewenangan akun tidak dapat dilewati melalui pengarsipan personel (termasuk akun sendiri).
- Daftar arsip admin memakai pagination; pemulihan mengembalikan profil dan riwayat asli, tidak mengaktifkan kembali akun/scope. Pemulihan semua jenis riwayat individual serta audit trail penuh belum termasuk versi ini.
- Nomor identitas tetap unik termasuk arsip. Untuk koreksi duplikat, periksa profil yang benar sebelum mengarsipkan; tidak ada penggabungan otomatis.

## Rancangan rekam disiplin (belum diimplementasikan)

Prototype read-only terpisah kini tersedia pada tab disiplin; rancangan di bawah menjelaskan governance modul produksi, bukan koneksi Propam aktif.

Rekam pelanggaran hanya boleh berisi putusan/sanksi final, bukan dugaan atau laporan mentah. Rencana data: jenis disiplin/etik, nomor dan tanggal putusan, instansi penetap, uraian faktual terbatas, sanksi, masa berlaku, dokumen privat, status berlaku/dibatalkan, serta versi perubahan keputusan. Pembatalan tidak menghapus rekam sebelumnya. Akses baca/tulis/unduh dibatasi petugas berwenang dan dicatat pada audit akses; tidak diberikan otomatis kepada semua Operator. Tidak ada pengurangan skor otomatis. Modul pengelolaan perkara produksi ditunda sampai governance dan audit khusus siap; prototype hanya menyediakan endpoint baca data sintetis, tanpa form input atau koneksi Propam.

## Prototype sinkronisasi personel

`PersonnelSource` → validasi canonical + pemetaan kode → `personnel_import_runs/items` (pratinjau) → konfirmasi → batch 25 dalam transaksi → `PersonnelRegistrationService` atau pembaruan identitas dasar → `personnel_source_links` → laporan dan `personnel_sync_checkpoints`. Keempat tabel bookkeeping terpisah dari riwayat domain. Indeks unik sumber/ID dan sumber/personel mencegah pemetaan ganda; indeks run/status/id membatasi query batch.

Checkpoint dan run dikunci saat apply; setiap item memiliki savepoint untuk rollback personel/riwayat/pemetaan secara atomik bila gagal. Fingerprint pratinjau dan hash lokal mencegah overwrite data yang berubah. Laporan hanya menampilkan identitas/item/status/alasan, bukan payload penuh. Run yang selesai dapat dibaca/diapply ulang tanpa efek tambahan. Checkpoint tidak maju bila ada konflik/gagal. Source tombstone dilewati, NRP tanpa link perlu pemeriksaan manual, arsip tidak dipulihkan.

Adapter simulasi menyediakan versi 1/2, halaman 25, maksimum manifest 100 dan empat halaman. Sumber nyata memerlukan adapter resmi dan staging/queue streaming, bukan menaikkan batas simulasi lalu mengklaim skala nasional. Delta saat ini memakai nomor versi simulasi, bukan cursor SIPP. Tidak ada request eksternal/kredensial atau scheduler. Lihat README dan OpenAPI untuk demo/kontrak.
