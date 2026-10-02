# Merit System Personel Polri

Prototype aplikasi web untuk mengelola identitas personel, kualifikasi, perjalanan jabatan, penugasan operasi, prestasi, dan penghargaan secara sistematis. Aplikasi menyediakan pencarian berbasis fungsi seperti Intelkam atau Reskrim tanpa membuat skor tersembunyi; pimpinan tetap menilai fakta riwayat yang ditampilkan.

Project ini dibuat untuk Uji Pemrograman SI-SDM Polri tahap CRUD. Seluruh layanan dapat dijalankan secara lokal.

## Fitur utama

- REST API berversi dengan Laravel dan autentikasi token Sanctum.
- Role `SYSTEM_ADMIN`, `ADMIN_SSDM`, dan `OPERATOR`.
- CRUD pengguna berbasis permission, assignment beberapa scope unit, aktivasi/nonaktif akun, dan pencabutan seluruh token saat akun dinonaktifkan.
- Scope organisasi `OWN_UNIT` dan `UNIT_AND_DESCENDANTS` pada hierarki Mabes–Polda–Polres–Satker–Polsek.
- CRUD personel, kualifikasi, serta riwayat jabatan dengan validasi dan soft delete.
- Riwayat jabatan dapat diurutkan lewat tombol Tanggal mulai ↑/↓ di atas timeline; default terbaru dahulu. Pergantian arah mengurutkan seluruh riwayat di server dan kembali ke halaman pertama.
- CRUD unit organisasi, bidang/fungsi, jenis kualifikasi, dan jenis penugasan oleh System Admin/Admin SSDM; daftar pangkat baku (22 POLRI termasuk enam Tamtama, 17 PNS) hanya dipelihara System Admin. Admin SSDM dan Operator dapat membaca/memilih pangkat, dengan daftar unit dibatasi scope Operator.
- Satu jabatan utama aktif, dengan penugasan tambahan PS, PLT, atau PLH.
- Pergantian jabatan dan mutasi atomik: menutup jabatan lama, memperbarui Satker bila perlu, lalu membuat jabatan baru dalam satu transaksi database.
- Upload PDF pendukung/SK opsional maksimal 5 MB pada storage privat dan download berizin.
- Filter fungsi, jenis kualifikasi, unit, pangkat, serta sorting jumlah kualifikasi dan durasi pengalaman. Header tabel Personel/Jabatan/Satker/Kualifikasi/Pengalaman/Operasi dapat diklik untuk mengurutkan seluruh hasil server; klik ulang membalik arah, mempertahankan filter dan kembali ke halaman pertama. Panah dan dropdown menunjukkan urutan yang sama.
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
- PostgreSQL 17, langsung atau melalui Docker Desktop. Migrasi pencarian memakai ekstensi bawaan `pg_trgm`; pengguna database perlu izin membuat ekstensi, atau administrator database perlu mengaktifkannya lebih dulu.

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
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

API tersedia pada `http://127.0.0.1:8000/api/v1`.

Perintah di atas untuk instalasi awal pada database baru. Untuk memperbarui instalasi yang sudah berisi data, gunakan `php artisan migrate` tanpa seed ulang, karena seed dasar dapat mengatur ulang akun demo. **Jangan gunakan `migrate:fresh` pada database yang ingin dipertahankan:** perintah tersebut menghapus seluruh tabel beserta datanya.

### Data demo tambahan (opsional)

Setelah migrasi dan seed dasar, dari folder `backend` jalankan:

```powershell
php artisan db:seed --class=DemoPersonnelSeeder
```

Seeder terpisah ini menambahkan 100 identitas sintetis: 80 POLRI (Tamtama sampai Perwira) dan 20 PNS, dengan 85 aktif, 5 nonaktif, 10 pensiun (5 di antaranya diarsipkan). Pendidikan wajib, variasi kualifikasi/riwayat jabatan, operasi Papua/Aceh/Jakarta/Bengkulu, prestasi, dan penghargaan tersedia untuk demonstrasi filter, pagination, serta scope. Dua cabang Polda/Polres/Polsek fiktif bertanda DEMO juga ditambahkan, selain penempatan di Satker contoh yang sudah ada. Semua nama bertanda `(DEMO nnn)`; nomor POLRI `99990001`–`99990080` dan nomor PNS `999900000000000081`–`999900000000000100` sengaja sintetis, bukan identitas kedinasan nyata. Nama jabatan, lembaga, dan keputusan juga hanya contoh.

Hanya diizinkan pada `APP_ENV=local` atau `testing`; tidak otomatis dijalankan oleh seed standar. Tidak membuat akun, memperluas scope, mengubah password, atau membuat foto/PDF palsu. Semua catatan merit belum diverifikasi. Jalankan ulang dengan perintah yang sama tanpa `migrate:fresh`: identitas yang sudah ada (termasuk arsip) dilewati beserta seluruh riwayatnya, sehingga perubahan testing tidak tertimpa atau dipulihkan paksa. Referensi dasar harus tersedia/aktif; konflik unit membatalkan transaksi tanpa menimpa data. Ini dataset demo, bukan pengujian beban nasional.

Untuk upload PDF, pastikan `fileinfo` aktif, `upload_max_filesize` minimal `5M`, `post_max_size` minimal `8M`, dan folder sementara PHP writable. Jalankan `php --ini` untuk melihat konfigurasi yang dipakai. Jika Windows menampilkan `unable to create a temporary file`, buat folder `backend/storage/app/upload-tmp`, atur `upload_tmp_dir` pada `php.ini` ke path absolut folder itu, lalu restart `php artisan serve`. Folder sementara dan dokumen privat tidak perlu masuk Git. `storage:link` bukan jalur unduh PDF; semua PDF diunduh melalui API berizin.

## Instalasi frontend

Buka terminal kedua:

```powershell
cd frontend
Copy-Item .env.example .env
npm ci
npm run dev -- --host 127.0.0.1 --port 5173 --strictPort
```

Antarmuka tersedia pada `http://127.0.0.1:5173`.

Backend menerima permintaan browser dari `http://127.0.0.1:5173` dan `http://localhost:5173` secara default. Jika frontend dijalankan pada origin lain, atur daftar `FRONTEND_ORIGINS` (dipisahkan koma) di `backend/.env`, lalu jalankan `php artisan config:clear` dan restart server backend. API memakai bearer token, bukan cookie lintas-origin.

`--strictPort` menghentikan Vite jika port sudah dipakai, sehingga tidak diam-diam berpindah port dan menyebabkan kesalahan CORS. Hentikan aplikasi yang memakai port tersebut atau pilih port lain secara eksplisit. Origin harus cocok lengkap: protokol, host, dan port; `localhost` berbeda dari `127.0.0.1`.

### Jika port default sudah dipakai

Contoh menjalankan backend pada `8001` dan frontend pada `5174`:

1. Atur `backend/.env`: `APP_URL=http://127.0.0.1:8001` dan `FRONTEND_ORIGINS=http://127.0.0.1:5174,http://localhost:5174`.
2. Atur `frontend/.env`: `VITE_API_URL=http://127.0.0.1:8001/api/v1`.
3. Dari folder `backend`, jalankan `php artisan config:clear`, kemudian `php artisan serve --host=127.0.0.1 --port=8001`.
4. Dari folder `frontend`, jalankan `npm run dev -- --host 127.0.0.1 --port 5174 --strictPort`.

Buka `http://127.0.0.1:5174`. Setelah mengubah `.env`, restart layanan terkait agar konfigurasi baru terbaca. `FRONTEND_ORIGINS` adalah alamat halaman frontend yang diizinkan mengakses API, sedangkan `VITE_API_URL` adalah alamat API backend. Jangan mengganti pembatasan origin dengan wildcard `*` untuk mengatasi salah port.

## Akun demo

Semua akun seed menggunakan password `Password123!`.

| Akun | Role/cakupan |
| --- | --- |
| `system.admin@example.test` | System Admin, global |
| `admin.ssdm@example.test` | Admin SSDM, global |
| `operator.polda@example.test` | Polda Bengkulu dan seluruh descendant |
| `operator.polres@example.test` | Polres Bengkulu Tengah dan seluruh descendant |
| `operator.intelkam@example.test` | Sat Intelkam Polres Bengkulu Tengah saja |
| `operator.polsek@example.test` | Polsek Talang Empat saja |

Untuk instalasi lama, tambahkan akun Polsek dengan `php artisan db:seed --class=PolsekDemoSeeder` dari folder backend. Seeder ini tidak mereset akun/password atau scope yang sudah ada. Password awal akun demo baru: `Password123!`.

Sorting teks Personel, Jabatan, dan Satker mengabaikan huruf besar/kecil; penulisan asli tetap dipertahankan. Indeks ekspresi nama membantu pengurutan server sebelum pagination.

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
- Status jabatan ditampilkan sebagai jenis penugasan (Definitif/PS/PLT/PLH), berbeda dari status personel (aktif/nonaktif/pensiun). Periode jabatan terlihat dari tanggal mulai dan selesai; tanggal selesai kosong berarti belum dicatat berakhir. Nivelering dan keterangan jabatan utama dapat diisi secara opsional sejak registrasi awal.
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

## Catatan kapasitas dan loading

- Daftar personel berhalaman (default 15, maksimum 100 record per respons). Misal ada 100.000 personel, halaman pertama tetap mengirim paling banyak 15 record, bukan 100.000; jumlah halaman dan hasil filter dihitung di database. Lima jenis riwayat profil juga diminta saat tab pertama dibuka, bukan semuanya saat profil masuk.
- Daftar, profil, dashboard, dan halaman riwayat yang baru dikunjungi memakai cache memori browser singkat (maksimum 50 respons, kedaluwarsa setelah 60 detik). Saat kembali, data tersimpan langsung ditampilkan sementara permintaan baru menyegarkannya di belakang layar. Cache dibersihkan setelah perubahan data, pergantian akun/logout, atau respons akses ditolak. Muat ulang browser tetap melakukan pemeriksaan autentikasi dan permintaan pertama halaman baru tetap membutuhkan loading.
- Pencarian Satker di formulir menunggu minimal 3 karakter, lalu mengambil maksimum 25 unit yang sesuai scope pengguna. Misal ada 10.000 unit, satu pencarian merender maksimum 25 opsi—batas 400 kali lebih kecil dibanding daftar semua unit. Ini batas jumlah data respons/DOM, **bukan** klaim waktu eksekusi 400 kali lebih cepat.
- Scope Operator dihitung dengan recursive CTE di PostgreSQL/SQLite; aplikasi tidak membangun daftar seluruh unit turunan di PHP untuk setiap daftar/dashboard. PostgreSQL memakai indeks GIN `pg_trgm` untuk pencarian substring nama/NRP dan nama/kode unit. Untuk kebutuhan pencarian spesifik, gunakan kata kunci yang cukup panjang agar indeks efektif.
- Batas respons tidak otomatis membatasi seluruh kerja database: `paginate()` tetap melakukan exact count, halaman offset jauh dapat lebih mahal, dan urut menurut agregat kualifikasi/durasi/operasi dapat menghitung banyak kandidat. Pada data nasional, ukur `EXPLAIN (ANALYZE, BUFFERS)` dengan data yang representatif sebelum menentukan kebutuhan cursor pagination, ringkasan terindeks, atau cache. Belum ada klaim kapasitas/latensi nasional tanpa pengukuran tersebut.

## Batasan prototype

### Prototype integrasi data personel

Menu **Integrasi Personel** tersedia bagi Admin SSDM/System Admin. Ini sumber simulasi lokal, **bukan koneksi SIPP**. Pratinjau hanya menyimpan staging; konfirmasi memproses maksimum 25 item per request dan laporan tersimpan dapat dibuka kembali. Tidak perlu seed ulang atau reset data.

Demo: pilih Impor awal / versi 1 → Buat pratinjau → periksa tiga personel `(IMPORT DEMO)` → centang konfirmasi → proses batch. Setelah selesai pilih Delta / versi 2: satu identitas diperbarui, satu personel baru ditambahkan, satu tombstone dilewati. Delta berikutnya di versi yang sama kosong. Tombstone tidak menghapus personel lokal. Mengulang laporan atau impor yang sama tidak menggandakan data.

Pemetaan memakai `(source, source_record_id)` dan NRP/NIP unik; NRP yang sudah ada tanpa pemetaan menjadi konflik, bukan ditimpa/diadopsi otomatis. Arsip tidak dipulihkan, perubahan lokal dilindungi, pendidikan/jabatan/riwayat lokal tidak disinkron ulang secara destruktif. Perubahan penempatan/status/jabatan/pendidikan sumber memerlukan pemeriksaan dan proses domain. Pembaruan otomatis hanya nama, tempat/tanggal lahir, dan pangkat yang tervalidasi. Checkpoint maju hanya jika semua item selesai tanpa konflik/gagal; item berhasil tetap tersimpan saat retry.

Kontrak adapter `App\Services\PersonnelSource::fetch(version, afterVersion, page, limit)` mengembalikan `{records, next_page}`. Record canonical: `{source_id, revision, deleted, personnel}`; `personnel` memuat jenis, NRP/NIP, nama, kode pangkat/unit, tempat/tanggal lahir, status, jabatan awal (kode fungsi/jenis penugasan), pendidikan umum dan Polri. Kode referensi harus aktif dan dikenali. Field registrasi wajib dan aturan domain sama dengan input manual. Manifest simulasi dibatasi 100 record / empat halaman, laporan 25 item per halaman; ini demonstrasi alur, bukan uji kapasitas nasional.

Adapter nyata dapat menggantikan simulasi tanpa menulis ulang pendaftaran, staging, laporan, dan proteksi duplikasi. Tetap diperlukan kontrak sumber resmi, pemetaan kode/otoritas field, kredensial server-side, cursor delta resmi, queue/job streaming skala besar, retry/backoff, monitoring dan audit lengkap sebelum produksi. Endpoint simulasi write dinonaktifkan di luar local/testing. Jangan menganggap integrasi nyata tinggal mengisi URL.

REST: `GET /personnel-integration`, `POST /personnel-integration/preview` (`mode: initial|delta`, `version: 1|2`), `GET /personnel-integration/{run}?page=1`, `POST /personnel-integration/{run}/apply` (`confirmed: true`, `batch_size: 1..25`). Seluruhnya di bawah `/api/v1`, wajib token dan role admin. Checkpoint berubah setelah pratinjau menghasilkan 409; buat pratinjau baru.

### Prototype integrasi disiplin dan kode etik

Tab read-only profil khusus Admin SSDM/System Admin menampilkan label **DEMO — belum terhubung ke sistem Propam**. Operator tidak melihat tab dan API menolak aksesnya. Tidak ada role Propam, form input/edit/hapus, skor negatif, file keputusan palsu, atau koneksi eksternal. Catatan berstatus `dibatalkan` tetap terlihat tetapi bukan sanksi aktif. Data kosong berarti belum ada catatan dalam sistem, bukan bukti tidak pernah melanggar.

Contoh opsional: setelah `DemoPersonnelSeeder`, jalankan `php artisan db:seed --class=DisciplinePrototypeSeeder` dari backend. Hanya local/testing, hanya personel **Aditya Pratama (DEMO 001)** / `99990001`; tidak menempelkan pelanggaran ke personel manual. Dua keputusan sintetis (final dan dibatalkan) dibuat idempotent tanpa menimpa catatan lama. Instalasi standar tidak memuat contoh pelanggaran.

Endpoint baca: `GET /api/v1/personel/{personel}/disiplin-prototype?page=1`, maksimal 15 per halaman. Tabel snapshot memisahkan `source_system`, `source_record_id`, waktu pembaruan sumber dan waktu sinkronisasi; ID sumber unik per sistem. Saat ini hanya sumber `prototype_demo` yang ditampilkan dan waktu sinkronisasi kosong. Adapter REST API Propam, pencocokan identitas, validasi payload, audit akses, revisi keputusan, kredensial server-side, dan delta sync merupakan pekerjaan integrasi mendatang, belum diimplementasikan. Kontrak resmi sumber tidak diasumsikan.

- Dashboard tiap role tersedia di `/dashboard`; System Admin memiliki `/sistem` untuk pemeriksaan koneksi dan versi runtime secara read-only. Admin SSDM tidak memiliki akses teknis, sementara ringkasan Operator mengikuti scope aktif.
- Profil memiliki CRUD kualifikasi dan riwayat jabatan berhalaman, penugasan tambahan aktif, serta upload/ganti/lepas dan unduh PDF privat. Edit mengirim hanya field yang berubah; field opsional dapat dikosongkan. File edit dikirim melalui POST dengan `_method=PUT` untuk kompatibilitas PHP 8.3.
- Profil juga memuat CRUD penugasan operasi, prestasi, dan penghargaan dengan PDF privat opsional, soft delete, serta status verifikasi. Data contoh dalam tiga modul ini seluruhnya fiktif.
- Registrasi menyediakan pendidikan umum wajib dan pendidikan Polri wajib untuk POLRI (opsional untuk PNS). Kualifikasi tambahan, jabatan terdahulu, operasi, prestasi, dan penghargaan dapat diisi langsung atau ditambahkan nanti dari profil. Penyimpanan atomik termasuk pembersihan PDF jika transaksi gagal; foto masih placeholder nonaktif.
- Akun staff baru dipilih dari personel aktif yang sudah ada; satu personel satu akun. Nama diambil dari database, scope terpisah dari penempatan. Programmer eksternal boleh tidak terhubung ke personel. Akun demo/lama tetap dipertahankan tanpa menebak pemilik; saat mengedit akun staff lama, pilih personel pemilik terlebih dahulu.
- Admin dapat mengarsipkan personel dengan alasan dan memulihkannya dari tab Arsip. Arsip tidak menutup jabatan/mengubah tanggal karier; akun terkait dinonaktifkan dan token dicabut. Pemulihan tidak otomatis mengaktifkan akun. Status pensiun/nonaktif tetap melalui perubahan status, mutasi melalui proses mutasi.
- Penghapusan langsung jabatan utama aktif tidak tersedia; gunakan ganti jabatan/mutasi. Riwayat individual menggunakan soft delete; restore riwayat individual belum tersedia.
- Penilaian kinerja dan assessment resmi masih pengembangan lanjutan. Disiplin/kode etik tersedia sebagai prototype read-only sintetis; integrasi data nyata belum tersedia.
- Audit trail penuh, audit akses disiplin, dan pemulihan riwayat individual direncanakan untuk pengembangan berikutnya. Urutan pangkat hanya pengurutan per jenis personel, bukan skor merit.

Keputusan teknis lengkap dan checklist implementasi tersedia di `implementation_plan.md` dan `task.md`.
