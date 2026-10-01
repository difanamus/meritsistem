# Merit System Personel Polri

Prototype aplikasi web untuk mengelola identitas personel, kualifikasi, perjalanan jabatan, penugasan operasi, prestasi, dan penghargaan secara sistematis. Aplikasi menyediakan pencarian berbasis fungsi seperti Intelkam atau Reskrim tanpa membuat skor tersembunyi; pimpinan tetap menilai fakta riwayat yang ditampilkan.

Project ini dibuat untuk Uji Pemrograman SI-SDM Polri tahap CRUD. Seluruh layanan dapat dijalankan secara lokal.

## Fitur utama

- REST API berversi dengan Laravel dan autentikasi token Sanctum.
- Role `SYSTEM_ADMIN`, `ADMIN_SSDM`, dan `OPERATOR`.
- CRUD pengguna berbasis permission, assignment beberapa scope unit, aktivasi/nonaktif akun, dan pencabutan seluruh token saat akun dinonaktifkan.
- Scope organisasi `OWN_UNIT` dan `UNIT_AND_DESCENDANTS` pada hierarki Mabes–Polda–Polres–Satker–Polsek.
- CRUD personel, kualifikasi, serta riwayat jabatan dengan validasi dan soft delete.
- CRUD unit organisasi, pangkat, bidang/fungsi, jenis kualifikasi, dan jenis penugasan; Operator memiliki akses baca, dengan daftar unit dibatasi scope.
- Satu jabatan utama aktif, dengan penugasan tambahan PS, PLT, atau PLH.
- Pergantian jabatan dan mutasi atomik: menutup jabatan lama, memperbarui Satker bila perlu, lalu membuat jabatan baru dalam satu transaksi database.
- Upload PDF pendukung/SK opsional maksimal 5 MB pada storage privat dan download berizin.
- Filter fungsi, jenis kualifikasi, unit, pangkat, serta sorting jumlah kualifikasi dan durasi pengalaman.
- Antarmuka React responsif dengan navigasi berbasis role serta halaman administrasi pengguna dan scope.

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
- Node.js 22.18 atau lebih baru dan npm (Node 24 direkomendasikan untuk test bawaan tanpa dependency tambahan).
- PostgreSQL 17, langsung atau melalui Docker Desktop.

## Menjalankan PostgreSQL dengan Docker

```powershell
docker compose up -d postgres
docker compose ps
```

Konfigurasi default development:

```text
host: 127.0.0.1
host port: 5433 (diteruskan ke port PostgreSQL 5432 di container)
database: merit_system
username: merit
password: merit_local_password
```

Port host `5433` dipilih agar tidak bertabrakan dengan instalasi PostgreSQL native yang biasanya memakai `5432`. Binding hanya ke `127.0.0.1`, sehingga database development tidak diekspos ke jaringan lokal.

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

Untuk upload PDF, pastikan `fileinfo` aktif, `upload_max_filesize` minimal `5M`, `post_max_size` minimal `8M`, dan folder sementara PHP writable. Jalankan `php --ini` untuk melihat konfigurasi yang dipakai. Jika Windows menampilkan `unable to create a temporary file`, buat folder `backend/storage/app/upload-tmp`, atur `upload_tmp_dir` pada `php.ini` ke path absolut folder itu, lalu restart `php artisan serve`. Folder sementara dan dokumen privat tidak perlu masuk Git. `storage:link` bukan jalur unduh PDF; semua PDF diunduh melalui API berizin.

## Instalasi frontend

Buka terminal kedua:

```powershell
cd frontend
Copy-Item .env.example .env
npm install
npm run dev -- --host 127.0.0.1
```

Antarmuka tersedia pada `http://127.0.0.1:5173`.

Backend menerima permintaan browser dari `http://127.0.0.1:5173` dan `http://localhost:5173` secara default. Jika frontend dijalankan pada origin lain, atur daftar `FRONTEND_ORIGINS` (dipisahkan koma) di `backend/.env`, lalu jalankan `php artisan config:clear` dan restart server backend. API memakai bearer token, bukan cookie lintas-origin.

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
npm test
npm run lint
npm run build
```

Checkpoint Point 6: 126 test / 668 assertion backend lulus di SQLite dan PostgreSQL 17; 11 test frontend, lint, build, format Pint, serta validasi OpenAPI/Postman lulus. Smoke QA empat role, akses luar scope, dan kondisi tanpa hasil juga lulus. Rincian ada di [`task.md`](task.md).

Narasi singkat demo Point 5: buka profil personel dan tunjukkan tiga riwayat yang dipisah (operasi, prestasi, penghargaan); tambah operasi fiktif dengan PDF opsional; verifikasi sebagai Admin SSDM lalu ubah datanya untuk menunjukkan status kembali belum diverifikasi; buka daftar personel dan filter wilayah/fungsi operasi atau urutkan jumlah/durasi. Jelaskan bahwa angka tersebut fakta kumulatif, bukan skor merit atau keputusan karier otomatis. Video presentasi resmi belum dibuat.

## Aturan domain penting

- Semua personel aktif mempunyai jabatan organisasi; istilah “tanpa jabatan” tidak digunakan sebagai nilai jabatan.
- Hanya satu riwayat jabatan utama yang boleh aktif pada satu waktu. Penugasan tambahan dapat berjalan bersamaan.
- Jabatan utama aktif tidak dapat langsung dihapus atau diakhiri melalui CRUD biasa. Gunakan proses pergantian jabatan atau mutasi agar riwayat konsisten.
- Mutasi hanya diizinkan jika unit asal dan tujuan berada dalam scope pengguna.
- Kualifikasi disajikan sebagai fakta. Jumlah kegiatan dan durasi pengalaman hanyalah alat urut/filter, bukan nilai merit otomatis.
- Penugasan operasi, prestasi, dan penghargaan dicatat terpisah. Penugasan operasi dapat difilter menurut wilayah, tingkat, fungsi, status verifikasi, jumlah, dan total hari; durasi adalah jumlah hari tiap operasi (periode beririsan dapat dihitung dua kali), bukan lama kalender unik atau skor.
- Operator dalam scope boleh mencatat dan mengubah fakta. Admin SSDM dan System Admin dapat memverifikasi. Perubahan fakta atau PDF membatalkan verifikasi; setiap record menampilkan pemeriksa dan waktu verifikasi.
- Dokumen disimpan pada disk privat dan selalu melewati authorization saat diunduh.
- System Admin dapat mengelola seluruh akun. Admin SSDM hanya dapat mengelola akun Operator. Tidak ada pengguna yang dapat mengubah role, scope, atau status akunnya sendiri melalui modul administrasi.
- Aksi hapus pengguna merupakan deaktivasi yang dapat dipulihkan melalui edit status; data akun tidak dihapus permanen dan seluruh token login langsung dicabut.
- Referensi yang masih dipakai personel, riwayat (termasuk soft-deleted), scope pengguna, atau unit anak tidak dapat dihapus (409). Nonaktifkan referensi agar tidak tersedia untuk input baru; riwayat lama tetap dapat dibaca.
- Unit organisasi tidak dapat menunjuk diri sendiri atau keturunannya sebagai induk. Unit aktif harus berada di bawah induk aktif; nonaktifkan bawahan aktif sebelum induknya.

## Dokumentasi API

- Spesifikasi: [`docs/api/openapi.yaml`](docs/api/openapi.yaml)
- Postman collection: [`docs/postman/Merit-System-Polri.postman_collection.json`](docs/postman/Merit-System-Polri.postman_collection.json)
- Postman environment: [`docs/postman/Local.postman_environment.json`](docs/postman/Local.postman_environment.json)
- ERD dan diagram alur: [`docs/architecture.md`](docs/architecture.md)

Gunakan endpoint `POST /api/v1/auth/login`, simpan nilai `token`, lalu kirim header `Authorization: Bearer <token>` pada endpoint terproteksi.

## Batasan prototype

- Dashboard tiap role tersedia di `/dashboard`; System Admin memiliki `/sistem` untuk pemeriksaan koneksi dan versi runtime secara read-only. Admin SSDM tidak memiliki akses teknis, sementara ringkasan Operator mengikuti scope aktif.
- Profil memiliki CRUD kualifikasi dan riwayat jabatan berhalaman, penugasan tambahan aktif, serta upload/ganti/lepas dan unduh PDF privat. Edit mengirim hanya field yang berubah; field opsional dapat dikosongkan. File edit dikirim melalui POST dengan `_method=PUT` untuk kompatibilitas PHP 8.3.
- Profil juga memuat CRUD penugasan operasi, prestasi, dan penghargaan dengan PDF privat opsional, soft delete, serta status verifikasi. Data contoh dalam tiga modul ini seluruhnya fiktif.
- Hapus personel/riwayat melalui UI menggunakan konfirmasi soft delete. Penghapusan langsung jabatan utama aktif tidak tersedia; gunakan ganti jabatan/mutasi. Pemulihan arsip belum tersedia pada UI.
- Data penilaian kinerja, assessment resmi, dan disiplin final masih direncanakan sebagai pengembangan lanjutan dengan kontrol akses tambahan.
- Audit trail penuh dan mekanisme restore soft-delete direncanakan untuk pengembangan berikutnya.

Keputusan teknis lengkap dan checklist implementasi tersedia di `implementation_plan.md` dan `task.md`.
