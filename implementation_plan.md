# Implementation Plan

## 1. Identitas Proyek

- **Nama sementara:** Merit System Personel Polri
- **Jenis:** Prototype aplikasi web berbasis REST API
- **Tahap seleksi:** Uji Pemrograman Tahap 1 - CRUD
- **Periode:** 1-7 Oktober 2026
- **Metode:** Take-Home Test
- **Dokumen acuan utama:**
  - `Paparan Uji Pemrograman.pdf`
  - `Soal CRUD.pdf`

Dokumen ini menjadi pedoman implementasi agar requirement, keputusan bisnis, output seleksi, dan alasan teknis tidak berubah atau terlupakan selama pengerjaan. Jika terdapat perbedaan antara dokumen ini dan soal resmi, soal serta paparan resmi menjadi sumber kebenaran utama.

## 2. Tujuan

Membangun prototype aplikasi Merit System Personel Polri yang:

1. Mengelola data personel, kualifikasi, dan riwayat jabatan secara terintegrasi.
2. Menyediakan fungsi Create, Read, Update, dan Delete melalui REST API.
3. Memvalidasi tipe, format, dan ketentuan data sebelum disimpan.
4. Menerapkan authentication dan authorization berdasarkan role, unit organisasi, dan cakupan kewenangan.
5. Menampilkan identitas, jabatan saat ini, kualifikasi, dan perjalanan karier personel secara kronologis.
6. Menyediakan pencarian dan penyaringan yang membantu pimpinan melihat personel berdasarkan kualifikasi serta pengalaman, tanpa membuat skor kelayakan otomatis.
7. Dapat dijalankan secara lokal dan disertai repository Git, README, dokumentasi API, serta video presentasi dengan kamera peserta aktif.

## 3. Requirement Resmi yang Wajib Dipenuhi

### 3.1 Kompetensi Tahap CRUD

Sesuai paparan, implementasi harus memperlihatkan:

- REST API.
- Penggunaan HTTP method yang benar.
- Routing.
- CRUD.
- Database.
- Authentication.
- Authorization/permission.
- Validation.
- Error handling.
- Pemilihan teknologi/framework.
- Version control menggunakan Git.
- Dokumentasi API.

### 3.2 Output Wajib

1. **Aplikasi lokal** - aplikasi harus dapat dijalankan pada komputer peserta.
2. **Repository Git** - source code tersedia pada GitHub, GitLab, atau platform sejenis.
3. **README lengkap**, sekurang-kurangnya berisi:
   - Penjelasan aplikasi.
   - Technology stack.
   - Petunjuk instalasi dan menjalankan aplikasi lokal.
   - Struktur database.
4. **Dokumentasi API** menggunakan Swagger/OpenAPI dan/atau Postman Collection.
5. **Video presentasi dengan live camera**, minimal menjelaskan:
   - Perkenalan peserta.
   - Technology stack.
   - Struktur aplikasi.
   - Struktur database.
   - REST API.
   - Authentication.
   - Permission/authorization.
   - Source code utama.
   - Demo aplikasi.
   - Kendala yang ditemukan.
6. Link repository dan video dikumpulkan sebelum waktu tahap berakhir melalui formulir yang tercantum pada paparan.

## 4. Ruang Lingkup Fungsional

### 4.1 Authentication

- Login dan logout.
- Informasi pengguna yang sedang login.
- Token API menggunakan Laravel Sanctum.
- Token dapat dicabut ketika akun dinonaktifkan, password diubah, atau pengguna berpindah tugas.
- Endpoint bisnis tidak dapat diakses tanpa authentication.

### 4.2 Pengelolaan Personel

- Daftar personel dengan pagination.
- Detail/profil personel.
- Tambah, ubah, dan soft delete personel sesuai kewenangan.
- Pencarian berdasarkan nama atau NRP/NIP.
- Filter berdasarkan unit organisasi, pangkat, bidang/fungsi, jenis kualifikasi, dan status.
- Sorting berdasarkan nama, pangkat, jumlah kualifikasi relevan, durasi pengalaman, atau data terbaru.

Data minimum personel:

- Jenis personel: POLRI atau PNS.
- Nomor identitas: NRP atau NIP.
- Nama lengkap.
- Pangkat.
- Tempat lahir.
- Tanggal lahir.
- Unit organisasi/Satker saat ini.
- Status personel.

Keputusan desain:

- NRP/NIP dimodelkan sebagai `jenis_personel` dan `nomor_identitas`, bukan dua kolom yang sama-sama dapat kosong.
- Nomor identitas wajib unik.
- Setiap personel aktif memiliki satu jabatan utama aktif.
- Tidak digunakan status `TANPA_JABATAN`; personel yang tidak menduduki jabatan bertunjangan tetap memiliki jabatan/penugasan organisasi, misalnya Banit pada suatu Satker.
- Sistem tidak membuat klasifikasi khusus "memiliki Tunjab/tidak memiliki Tunjab" karena tidak diminta oleh soal.

### 4.3 Pengelolaan Kualifikasi

Kualifikasi adalah bukti formal atau riwayat pengembangan kemampuan personel, antara lain:

- Pendidikan Kepolisian.
- Pendidikan Umum.
- Pendidikan Kejuruan.
- Pelatihan.
- Sertifikasi.
- Kompetensi/keahlian lain yang relevan.

Setiap kegiatan disimpan sebagai satu riwayat tersendiri. Jika personel mengikuti kejuruan Intelijen dua kali, terdapat dua record agar tanggal, penyelenggara, dan dokumennya tetap dapat dibedakan.

Data kualifikasi:

- Personel.
- Jenis kualifikasi.
- Nama kualifikasi.
- Bidang/fungsi terkait, opsional.
- Jenjang, opsional.
- Bidang studi, opsional.
- Institusi/penyelenggara.
- Tanggal mulai dan selesai, opsional sesuai jenis kegiatan.
- Tahun.
- Nomor ijazah/sertifikat, opsional.
- Keterangan, opsional.
- Dokumen pendukung PDF, opsional.

Kualifikasi tidak diberi nilai atau skor otomatis. Sistem hanya menyajikan fakta dan menyediakan filter/sorting transparan agar pimpinan dapat menilai sendiri.

### 4.4 Pengelolaan Riwayat Jabatan

Data riwayat jabatan:

- Personel.
- Nama jabatan.
- Unit organisasi/Satker.
- Bidang/fungsi.
- Tanggal mulai.
- Tanggal selesai, nullable untuk penugasan aktif.
- Nivelering, nullable.
- Jenis penugasan.
- Penanda jabatan utama.
- Keterangan, opsional.
- Dokumen SK PDF, opsional.

Referensi awal jenis penugasan:

- DEFINITIF.
- PS - Pemangku Sementara.
- PLT - Pelaksana Tugas.
- PLH - Pelaksana Harian.

Aturan bisnis jabatan:

1. Setiap personel aktif memiliki tepat satu jabatan utama aktif.
2. Personel dapat memiliki penugasan tambahan aktif secara bersamaan, misalnya Plt. atau Plh.
3. Penugasan tambahan tidak menutup jabatan utama.
4. Riwayat ditampilkan kronologis dari yang terbaru atau terlama sesuai pilihan pengguna.
5. Jabatan utama aktif tidak dapat dihapus langsung.
6. Jabatan utama aktif hanya dapat:
   - Diedit untuk koreksi data.
   - Diganti melalui proses pergantian jabatan.
   - Diakhiri melalui proses perubahan status personel yang relevan.
7. DELETE terhadap jabatan utama aktif menghasilkan `409 Conflict` dengan pesan yang jelas.
8. Riwayat yang telah selesai dapat dihapus menggunakan soft delete sesuai kewenangan.
9. Pergantian jabatan utama menutup jabatan sebelumnya dan membuat jabatan baru dalam satu database transaction.

### 4.5 Mutasi

Mutasi dimodelkan sebagai proses bisnis, bukan sekadar perubahan kolom Satker:

1. Menutup jabatan utama lama.
2. Mengubah unit organisasi personel.
3. Membuat jabatan utama baru.
4. Menyimpan pelaku dan waktu perubahan untuk audit.
5. Menyimpan dokumen SK secara opsional pada riwayat jabatan baru.

Mutasi wajib dilakukan dalam satu transaction agar tidak meninggalkan personel tanpa jabatan utama aktif.

Aturan kewenangan mutasi:

- Pengguna hanya dapat memindahkan personel apabila unit asal dan tujuan berada dalam cakupan kewenangannya.
- Antar-Polda dilakukan oleh Admin SSDM.
- Antar-Polres dalam Polda yang sama dapat dilakukan Operator bercakupan Polda atau Admin SSDM.
- Perpindahan dalam subtree Polres dapat dilakukan Operator bercakupan Polres atau pengguna dengan cakupan lebih tinggi.
- Operator unit biasa tidak dapat memindahkan personel ke luar cakupannya.
- Setelah mutasi, operator unit lama kehilangan akses pengelolaan dan operator unit baru otomatis mendapat akses berdasarkan scope.

### 4.6 Struktur Organisasi

Struktur organisasi menggunakan model hierarkis agar tidak membutuhkan role berbeda untuk setiap tingkat:

```text
POLRI
|- MABES POLRI
|  |- BARESKRIM
|  `- BIK
`- POLDA
   |- SATKER POLDA
   `- POLRES
      |- SATKER POLRES
      `- POLSEK
```

Tabel `unit_organisasi` menggunakan `parent_id` untuk membentuk hierarki. Jenis unit dapat mencakup:

- ROOT/POLRI.
- MABES.
- SATKER_MABES.
- POLDA.
- SATKER_POLDA.
- POLRES.
- SATKER_POLRES.
- POLSEK.

### 4.7 Role dan Cakupan Akses

Role utama:

1. **SYSTEM_ADMIN**
   - Mengelola konfigurasi teknis dan akun darurat.
   - Untuk prototype dapat mengakses seluruh fungsi.
   - Penggunaannya harus diaudit; dalam production akses programmer terhadap data bisnis harus dibatasi berdasarkan prinsip least privilege.
2. **ADMIN_SSDM**
   - Akses bisnis nasional.
   - Mengelola pengguna, struktur organisasi, referensi, personel, kualifikasi, jabatan, dan mutasi.
   - Tidak mengelola fungsi teknis aplikasi yang khusus System Admin.
3. **OPERATOR**
   - Hak akses ditentukan oleh unit organisasi dan cakupan assignment.

Cakupan Operator:

- `OWN_UNIT`: hanya unit yang ditugaskan.
- `UNIT_AND_DESCENDANTS`: unit yang ditugaskan beserta seluruh unit bawahannya.

Contoh hasil kombinasi role dan scope:

- Operator Satker Mabes: unit Satker Mabes + `OWN_UNIT`.
- Operator SDM Polda: unit Polda + `UNIT_AND_DESCENDANTS`.
- Operator Satker Polda: unit Satker Polda + `OWN_UNIT`.
- Operator Polres: unit Polres + `UNIT_AND_DESCENDANTS`.
- Operator Satker Polres: unit Satker Polres + `OWN_UNIT`.
- Operator Polsek: unit Polsek + `OWN_UNIT`.

Keputusan keamanan akun:

- Kewenangan Operator melekat pada Satker/scope, tetapi akun tetap melekat pada individu.
- Satu unit dapat memiliki beberapa Operator.
- Akun tidak diserahkan atau dipakai bersama ketika personel operator mutasi.
- Operator lama dinonaktifkan atau assignment-nya diakhiri; operator pengganti menggunakan akun sendiri.
- `created_by` dan `updated_by` dipakai untuk audit, bukan untuk menentukan kepemilikan data.
- Operator dapat mengelola semua data dalam scope-nya, bukan hanya data yang dibuatnya sendiri.

## 5. Model Data Awal

Tabel utama yang direncanakan:

1. `users`
2. `unit_organisasi`
3. `user_scopes`
4. `pangkats`
5. `bidang_fungsi`
6. `jenis_kualifikasi`
7. `jenis_penugasan`
8. `personel`
9. `kualifikasi_personel`
10. `riwayat_jabatan`
11. Tabel token Laravel Sanctum.
12. Tabel audit log apabila implementasi inti telah stabil.

### 5.1 Relasi Utama

```text
unit_organisasi 1 --- n unit_organisasi (parent-child)
users            1 --- n user_scopes
unit_organisasi  1 --- n user_scopes
unit_organisasi  1 --- n personel
personel          1 --- n kualifikasi_personel
personel          1 --- n riwayat_jabatan
bidang_fungsi     1 --- n kualifikasi_personel
bidang_fungsi     1 --- n riwayat_jabatan
```

### 5.2 Dokumen PDF Opsional

Untuk scope prototype, satu dokumen opsional per record sudah cukup:

- `kualifikasi_personel.dokumen_pendukung_path`.
- `riwayat_jabatan.dokumen_sk_path`.

Ketentuan:

- Tidak wajib diunggah.
- Hanya PDF.
- Batas awal ukuran 5 MB, dapat disesuaikan melalui konfigurasi.
- Disimpan pada storage privat.
- Diunduh melalui endpoint berizin, bukan URL publik langsung.
- Nama file asli, ukuran, dan MIME type dicatat bila diperlukan.

Jika di masa depan satu record membutuhkan banyak dokumen, penyimpanan dapat dimigrasikan ke tabel lampiran tersendiri tanpa mengubah konsep bisnis.

## 6. REST API

Prefix awal: `/api/v1`.

Kelompok endpoint:

- `/auth/login`, `/auth/logout`, `/auth/me`.
- `/users` untuk pengelolaan pengguna sesuai permission.
- `/unit-organisasi`.
- `/pangkats`.
- `/bidang-fungsi`.
- `/jenis-kualifikasi`.
- `/jenis-penugasan`.
- `/personel`.
- `/personel/{id}/kualifikasi`.
- `/personel/{id}/riwayat-jabatan`.
- `/personel/{id}/ganti-jabatan`.
- `/personel/{id}/mutasi`.
- Endpoint unduh dokumen yang tetap melewati authorization.

HTTP method:

- `GET` untuk membaca.
- `POST` untuk membuat dan menjalankan proses bisnis seperti mutasi.
- `PUT/PATCH` untuk memperbarui.
- `DELETE` untuk soft delete.

Format respons API dibuat konsisten, misalnya:

```json
{
  "success": true,
  "message": "Data personel berhasil dibuat.",
  "data": {}
}
```

Untuk validation error:

```json
{
  "success": false,
  "message": "Data yang diberikan tidak valid.",
  "errors": {}
}
```

Status code minimum:

- `200 OK`.
- `201 Created`.
- `204 No Content` bila digunakan.
- `401 Unauthorized`.
- `403 Forbidden`.
- `404 Not Found`.
- `409 Conflict`.
- `422 Unprocessable Entity`.
- `500 Internal Server Error` tanpa membocorkan detail sensitif.

## 7. Validation dan Konsistensi

### 7.1 Personel

- Field minimum wajib terisi.
- Nomor identitas unik.
- Nomor identitas diperlakukan sebagai string agar angka nol di depan tidak hilang.
- Karakter nomor identitas dibatasi secara wajar tanpa mengarang panjang NRP/NIP yang tidak ditentukan soal.
- Tanggal lahir tidak boleh setelah tanggal hari ini.
- Referensi pangkat dan unit harus valid serta aktif.

### 7.2 Kualifikasi

- Jenis dan nama kualifikasi wajib.
- Tanggal selesai tidak boleh sebelum tanggal mulai.
- Bidang/fungsi boleh kosong untuk kualifikasi umum.
- PDF opsional harus lolos validasi MIME, ekstensi, dan ukuran.

### 7.3 Riwayat Jabatan

- Nama jabatan, unit, fungsi, tanggal mulai, dan jenis penugasan wajib.
- Tanggal selesai tidak boleh sebelum tanggal mulai.
- Jabatan aktif menggunakan `tanggal_selesai = null`.
- Hanya satu jabatan utama aktif per personel.
- Riwayat jabatan utama tidak boleh saling tumpang tindih.
- Penugasan tambahan boleh tumpang tindih.
- Integritas satu jabatan utama aktif dijaga di service layer dan, jika memungkinkan, dengan partial unique index PostgreSQL.

## 8. Filtering, Sorting, dan Ringkasan Kualifikasi

Contoh permintaan:

```http
GET /api/v1/personel?bidang=intelkam&sort=jumlah_kualifikasi&direction=desc
```

Filter awal:

- Nama atau NRP/NIP.
- Unit organisasi.
- Pangkat.
- Bidang/fungsi.
- Jenis kualifikasi.
- Rentang waktu pengalaman.

Sorting awal:

- Nama.
- Pangkat.
- Jumlah kualifikasi yang terkait bidang terpilih.
- Durasi pengalaman pada bidang terpilih.
- Kualifikasi terbaru.
- Data terbaru diperbarui.

Hasil dapat menampilkan ringkasan seperti:

```text
Nama       Kualifikasi Intelkam    Pengalaman Intelkam
Personel A 3 kegiatan              10 tahun
Personel B 2 kegiatan               6 tahun
```

Sistem tidak menyatakan siapa yang paling layak dan tidak menjumlahkan kualifikasi serta pengalaman menjadi skor tersembunyi.

## 9. Frontend

Halaman minimum:

1. Login.
2. Dashboard ringkas.
3. Daftar personel dengan pencarian, filter, sorting, dan pagination.
4. Form tambah/edit personel.
5. Profil personel:
   - Identitas.
   - Jabatan utama aktif.
   - Penugasan tambahan aktif.
   - Kualifikasi.
   - Riwayat jabatan kronologis.
6. Form kualifikasi dan upload dokumen opsional.
7. Form riwayat jabatan dan upload SK opsional.
8. Proses pergantian jabatan/mutasi.
9. Pengelolaan pengguna dan scope untuk Admin SSDM/System Admin.
10. Pengelolaan referensi minimum.
11. Halaman 401/403/404 dan pesan validation yang jelas.

Frontend harus sederhana, rapi, responsif, dan mudah didemonstrasikan. Prioritasnya adalah fitur lengkap dan benar, bukan animasi atau desain yang berlebihan.

## 10. Arsitektur dan Technology Stack

```text
React + Vite
      |
      | HTTPS/JSON REST API
      v
Laravel API + Sanctum
      |
      v
PostgreSQL
```

- **Backend:** Laravel.
- **Frontend:** React + Vite.
- **Database:** PostgreSQL.
- **Authentication:** Laravel Sanctum.
- **API documentation:** Swagger/OpenAPI dan Postman Collection.
- **Version control:** Git.
- **Repository:** monorepo dengan folder `backend`, `frontend`, dan `docs`.

Struktur awal:

```text
merit-system-polri/
|- backend/
|- frontend/
|- docs/
|- README.md
|- implementation_plan.md
`- task.md
```

## 11. Menjalankan Aplikasi Secara Lokal

Local setup merupakan acceptance criterion wajib, bukan fitur tambahan.

Repository harus menyediakan:

- `.env.example` tanpa secret.
- Daftar dependency dan versi runtime.
- Perintah instalasi backend.
- Perintah instalasi frontend.
- Perintah membuat database PostgreSQL.
- Migration dan seeder.
- Akun demo untuk setiap role.
- Perintah menjalankan backend dan frontend.
- Cara menjalankan test.
- Cara membuka dokumentasi API.

Docker Compose dapat ditambahkan apabila membantu reproducibility, tetapi instalasi lokal standar tetap harus dijelaskan dan diuji. Jangan menjadikan Docker satu-satunya cara jika belum dipastikan tersedia pada komputer penguji.

## 12. Security

- Password di-hash menggunakan mekanisme Laravel.
- Semua input divalidasi di backend.
- Authorization diperiksa pada setiap resource dan endpoint file.
- Scope tidak hanya diterapkan pada UI, tetapi juga pada query backend.
- Operator tidak dapat mengirim unit bebas di luar cakupannya.
- Token akun nonaktif dicabut.
- File disimpan privat dan nama/path internal tidak diekspos langsung.
- Error 500 tidak membocorkan stack trace pada mode production.
- Rate limiting diterapkan pada login dan endpoint API yang sesuai.
- CORS dibatasi pada origin frontend lokal yang dikonfigurasi.
- Soft delete dipakai untuk data bisnis yang memerlukan pemulihan.
- Audit minimal menyimpan pembuat dan pengubah; audit log lengkap dikerjakan setelah fitur inti stabil.

## 13. Testing

Prioritas automated test backend:

1. Login berhasil dan gagal.
2. Endpoint terlindungi dari pengguna anonim.
3. Admin SSDM dapat mengakses seluruh unit.
4. Operator hanya dapat mengakses scope sendiri.
5. Percobaan akses ID milik unit lain menghasilkan 403/404 sesuai kebijakan.
6. CRUD personel.
7. Validasi NRP/NIP unik.
8. CRUD kualifikasi dan validasi PDF.
9. CRUD riwayat jabatan.
10. Hanya satu jabatan utama aktif.
11. Penugasan tambahan tidak menutup jabatan utama.
12. Mutasi berhasil secara atomic.
13. Rollback terjadi jika sebagian proses mutasi gagal.
14. Filter, sorting, dan pagination.
15. Soft delete dan pemulihan yang diizinkan.

Frontend diverifikasi melalui lint/build dan pengujian alur utama secara manual. Automated frontend test ditambahkan bila waktu memungkinkan setelah requirement wajib stabil.

## 14. Git dan Dokumentasi

- Gunakan commit kecil dengan pesan yang menjelaskan perubahan.
- Jangan memasukkan `.env`, token, password, atau file sensitif ke repository.
- README diperbarui bersamaan dengan implementasi, bukan hanya di akhir.
- ERD, flow authentication, flow authorization, dan flow mutasi ditempatkan di `docs`.
- OpenAPI harus sesuai dengan endpoint aktual.
- Postman Collection harus dapat digunakan dengan environment lokal.
- Semua akun demo dan contoh request menggunakan data fiktif.

## 15. Tahapan Implementasi

### Fase 1 - Fondasi

- Scaffold repository, Laravel, React, dan konfigurasi PostgreSQL.
- Menyusun migration, model, factory, dan seeder.
- Membuat referensi serta struktur organisasi contoh.

### Fase 2 - Keamanan dan Hak Akses

- Authentication Sanctum.
- Role, permission, user scope, policy, dan query scoping.
- Test lintas-unit dan escalation attempt.

### Fase 3 - CRUD Inti

- Personel.
- Kualifikasi.
- Riwayat jabatan.
- Dokumen PDF opsional.
- Validation dan error handling.

### Fase 4 - Proses Bisnis

- Pergantian jabatan utama.
- Penugasan tambahan.
- Mutasi lintas-unit sesuai scope.
- Filter bidang/fungsi dan ringkasan pengalaman.

### Fase 5 - Frontend

- Authentication UI.
- Daftar dan profil personel.
- Form CRUD.
- Filter, sorting, pagination.
- UI berbasis permission.

### Fase 6 - Quality dan Deliverables

- Automated test dan manual QA.
- Swagger/OpenAPI dan Postman Collection.
- README dan diagram.
- Uji instalasi dari kondisi bersih.
- Seed data demo.
- Persiapan skrip dan perekaman video.
- Push repository dan pengumpulan link.

## 16. Definition of Done

Proyek dianggap selesai ketika:

1. Seluruh fungsi wajib berjalan di komputer lokal dari petunjuk README.
2. Migration dan seeder dapat dijalankan tanpa perubahan manual pada source code.
3. CRUD personel, kualifikasi, dan riwayat jabatan berfungsi melalui UI serta REST API.
4. Authentication, role, dan organizational scope terbukti bekerja.
5. Mutasi dan pergantian jabatan menjaga konsistensi data.
6. Validation dan error handling memberikan respons yang tepat.
7. Filter bidang/fungsi dan sorting transparan berfungsi tanpa skor otomatis.
8. PDF opsional dapat diunggah dan diunduh hanya oleh pengguna berwenang.
9. Test prioritas lulus.
10. Frontend production build berhasil.
11. OpenAPI/Postman sesuai dengan API aktual.
12. README, diagram, akun demo, dan langkah instalasi lengkap.
13. Video live camera mencakup seluruh materi minimum dari paparan.
14. Link repository dan video siap dikumpulkan sebelum batas waktu.

## 17. Batasan dan Pengembangan Lanjutan

Yang tidak menjadi prioritas sebelum requirement wajib selesai:

- Penilaian/ranking otomatis kelayakan personel.
- Workflow persetujuan mutasi berlapis.
- Notifikasi real-time.
- Integrasi dengan sistem Polri yang sebenarnya.
- Object storage/cloud deployment.
- Multi-file attachment untuk satu record.
- Analytics kompleks atau machine learning.

Fitur tersebut dapat disebut sebagai pengembangan lanjutan saat presentasi, bukan dipaksakan ke prototype hingga mengganggu stabilitas fungsi wajib.
