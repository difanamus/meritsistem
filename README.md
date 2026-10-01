# Merit System Personel Polri

Prototype aplikasi web untuk mengelola identitas personel, kualifikasi, dan perjalanan jabatan secara sistematis. Aplikasi menyediakan pencarian berbasis fungsi seperti Intelkam atau Reskrim tanpa membuat skor tersembunyi; pimpinan tetap menilai fakta riwayat yang ditampilkan.

Project ini dibuat untuk Uji Pemrograman SI-SDM Polri tahap CRUD. Seluruh layanan dapat dijalankan secara lokal.

## Fitur utama

- REST API berversi dengan Laravel dan autentikasi token Sanctum.
- Role `SYSTEM_ADMIN`, `ADMIN_SSDM`, dan `OPERATOR`.
- Scope organisasi `OWN_UNIT` dan `UNIT_AND_DESCENDANTS` pada hierarki Mabes–Polda–Polres–Satker–Polsek.
- CRUD personel, kualifikasi, serta riwayat jabatan dengan validasi dan soft delete.
- Satu jabatan utama aktif, dengan penugasan tambahan PS, PLT, atau PLH.
- Pergantian jabatan dan mutasi atomik: menutup jabatan lama, memperbarui Satker bila perlu, lalu membuat jabatan baru dalam satu transaksi database.
- Upload PDF pendukung/SK opsional maksimal 5 MB pada storage privat dan download berizin.
- Filter fungsi, jenis kualifikasi, unit, pangkat, serta sorting jumlah kualifikasi dan durasi pengalaman.
- Antarmuka React yang responsif dan siap didemonstrasikan.

## Technology stack

| Bagian | Teknologi | Alasan |
| --- | --- | --- |
| Backend | PHP 8.3+, Laravel 13 | Struktur MVC matang, validasi, policy, transaction, dan testing terintegrasi. |
| Authentication | Laravel Sanctum | Token API sederhana, resmi, dan sesuai kebutuhan aplikasi internal. |
| Database | PostgreSQL 17 | Relasi dan constraint kuat, transaction andal, serta cocok untuk query hierarki/riwayat. |
| Frontend | React 19, TypeScript, Vite 8 | UI modular, type-safe, cepat saat pengembangan dan build. |
| Testing | PHPUnit/Laravel Test | Menguji authentication, authorization, scope, CRUD, file, filter, dan mutasi. |

## Struktur repository

```text
.
├── backend/                 Laravel REST API
├── frontend/                React + Vite SPA
├── docs/api/openapi.yaml    Spesifikasi OpenAPI
├── docs/postman/            Collection dan environment Postman
├── compose.yaml             PostgreSQL lokal
├── implementation_plan.md   Keputusan dan rencana implementasi
└── task.md                  Checklist pengerjaan
```

## Prasyarat

- PHP 8.3 atau lebih baru dengan ekstensi umum Laravel dan `pdo_pgsql`.
- Composer 2.
- Node.js 22 atau lebih baru dan npm.
- PostgreSQL 17, langsung atau melalui Docker Desktop.

## Menjalankan PostgreSQL dengan Docker

```powershell
docker compose up -d postgres
docker compose ps
```

Konfigurasi default development:

```text
host: 127.0.0.1
port: 5432
database: merit_system
username: merit
password: merit_local_password
```

Jika Docker Desktop belum dapat berjalan, backend dapat dikembangkan sementara dengan SQLite. Ubah `DB_CONNECTION=sqlite`, kosongkan variabel `DB_*` lainnya, lalu buat file `backend/database/database.sqlite`. PostgreSQL tetap menjadi target database aplikasi.

## Instalasi backend

```powershell
cd backend
Copy-Item .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

API tersedia pada `http://127.0.0.1:8000/api/v1`.

## Instalasi frontend

Buka terminal kedua:

```powershell
cd frontend
Copy-Item .env.example .env
npm install
npm run dev -- --host 127.0.0.1
```

Antarmuka tersedia pada `http://127.0.0.1:5173`.

## Akun demo

Semua akun seed menggunakan password `Password123!`.

| Akun | Role/cakupan |
| --- | --- |
| `system.admin@example.test` | System Admin, global |
| `admin.ssdm@example.test` | Admin SSDM, global |
| `operator.polda@example.test` | Polda Bengkulu dan seluruh descendant |
| `operator.polres@example.test` | Polres Bengkulu Tengah dan seluruh descendant |
| `operator.intelkam@example.test` | Sat Intelkam Polres Bengkulu Tengah saja |

## Menjalankan test dan quality check

```powershell
cd backend
php artisan test
vendor\bin\pint --test

cd ..\frontend
npm run lint
npm run build
```

Checkpoint terakhir: 43 test backend dengan 156 assertion lulus, serta lint dan production build frontend lulus.

## Aturan domain penting

- Semua personel aktif mempunyai jabatan organisasi; istilah “tanpa jabatan” tidak digunakan sebagai nilai jabatan.
- Hanya satu riwayat jabatan utama yang boleh aktif pada satu waktu. Penugasan tambahan dapat berjalan bersamaan.
- Jabatan utama aktif tidak dapat langsung dihapus atau diakhiri melalui CRUD biasa. Gunakan proses pergantian jabatan atau mutasi agar riwayat konsisten.
- Mutasi hanya diizinkan jika unit asal dan tujuan berada dalam scope pengguna.
- Kualifikasi disajikan sebagai fakta. Jumlah kegiatan dan durasi pengalaman hanyalah alat urut/filter, bukan nilai merit otomatis.
- Dokumen disimpan pada disk privat dan selalu melewati authorization saat diunduh.

## Dokumentasi API

- Spesifikasi: [`docs/api/openapi.yaml`](docs/api/openapi.yaml)
- Postman collection: [`docs/postman/Merit-System-Polri.postman_collection.json`](docs/postman/Merit-System-Polri.postman_collection.json)
- Postman environment: [`docs/postman/Local.postman_environment.json`](docs/postman/Local.postman_environment.json)

Gunakan endpoint `POST /api/v1/auth/login`, simpan nilai `token`, lalu kirim header `Authorization: Bearer <token>` pada endpoint terproteksi.

## Batasan prototype

- CRUD pengguna dan master data administratif belum tersedia di UI.
- PostgreSQL perlu diverifikasi pada mesin yang Docker Desktop atau service PostgreSQL-nya aktif.
- Audit trail penuh dan mekanisme restore soft-delete direncanakan untuk pengembangan berikutnya.

Keputusan teknis lengkap dan checklist implementasi tersedia di `implementation_plan.md` dan `task.md`.
