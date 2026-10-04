# Task Checklist - Merit System Personel Polri

Checklist ini harus diperbarui selama pengerjaan. Centang `[x]` hanya setelah implementasi atau verifikasi benar-benar selesai.

Checkpoint Point 1: CRUD pengguna, role, dan scope telah diimplementasikan pada API dan UI. Pengujian mencakup pembatasan role, larangan perubahan akun sendiri, validasi scope, deaktivasi/pencabutan token, dan aktivasi ulang. QA browser memeriksa daftar/form System Admin serta penolakan URL administrasi untuk Operator; checklist QA lintas-modul tetap menunggu tahap QA keseluruhan.

Checkpoint Point 2: CRUD lima kategori referensi selesai pada API/UI, dengan validasi induk dan siklus hierarki, pembatasan Operator menjadi read-only, serta penolakan penghapusan referensi yang masih dipakai (409). Sebanyak 82 test / 358 assertion lulus di SQLite dan PostgreSQL. Lint/build frontend serta validasi dokumen OpenAPI/Postman lulus. Browser memverifikasi tambah/edit referensi demo nonaktif (DEMO-POINT2), pilihan induk unit, serta Operator Polres hanya melihat empat unit dalam scope tanpa tombol perubahan. QA keseluruhan proyek tetap dikerjakan pada tahap terpisah.

## A. Requirement dan Perencanaan

Checkpoint Point 3: dashboard faktual berbasis role/scope, navigasi ruang kerja/administrasi, redirect login ke dashboard, dan monitoring teknis read-only khusus System Admin selesai. Operator tanpa scope aktif mendapatkan ringkasan kosong dan tidak mendapat tombol tambah personel. Seluruh 88 test / 404 assertion lulus di SQLite dan PostgreSQL 17; lint/build frontend lulus. Browser memverifikasi dashboard System Admin, Admin SSDM, Operator Intelkam, serta penolakan URL teknis untuk Admin SSDM.

Checkpoint Point 4: UI CRUD kualifikasi dan riwayat jabatan selesai, termasuk pagination, metadata lengkap, PDF opsional (tambah/ganti/hapus lampiran), download berizin, konfirmasi soft delete, serta perlindungan jabatan utama aktif melalui proses ganti jabatan/mutasi. Profil menampilkan penugasan tambahan aktif dan menyediakan arsip personel; restore belum tersedia. Seluruh 106 test / 495 assertion lulus di SQLite dan PostgreSQL 17; 11 test frontend, lint, build, serta validasi OpenAPI/Postman lulus. Browser memverifikasi tambah tanpa PDF, edit dan pengosongan metadata, upload PDF kualifikasi/SK, penyelesaian penugasan tambahan, pembatalan dan konfirmasi hapus. Klik download tidak menampilkan error, tetapi event unduhan blob tidak tersedia pada browser pengujian; kontrak download dan authorization diverifikasi lewat test. Kendala upload lokal diperbaiki melalui direktori sementara PHP yang writable dan didokumentasikan di README. Data QA fiktif diarsipkan tanpa mengubah personel demo awal.

Checkpoint Point 5: penugasan operasi, prestasi, dan penghargaan resmi terpisah telah tersedia di API dan profil. Setiap modul mendukung CRUD dalam scope, PDF privat opsional, verifikasi Admin SSDM/System Admin, pembatalan verifikasi ketika fakta berubah, dan soft delete. Daftar personel mendukung filter operasi dan urut jumlah/durasi kumulatif tanpa skor merit. Seluruh 117 test / 625 assertion backend lulus di SQLite dan PostgreSQL 17; 11 test frontend, lint, build, parsing OpenAPI/Postman, dan format Pint lulus. Browser memverifikasi tambah operasi tanpa PDF, verifikasi, edit dengan PDF yang membatalkan verifikasi, filter wilayah Papua (1 operasi, 90 hari), lalu mengarsipkan record QA tanpa mengubah data demo awal. QA menyeluruh proyek dan video presentasi tetap pada tahap berikutnya.

Checkpoint Point 6: kontrak respons API sukses/error dirapikan, termasuk 401 tanpa header JSON, 403, 404, 405, 422, 429, dan 500 tanpa bocoran detail internal. CORS kini terbatas pada origin frontend lokal yang dapat dikonfigurasi. Smoke QA browser mencakup System Admin, Admin SSDM, Operator Polda, Operator Polres, akses di luar scope, dan hasil pencarian kosong; tidak ada error konsol browser. Seluruh 126 test / 668 assertion backend lulus di SQLite dan PostgreSQL 17; 11 test frontend, lint, build, Pint, serta 249 referensi lokal OpenAPI lulus. Database lokal memiliki satu personel tambahan non-seed yang dibiarkan utuh. Uji setup/clone bersih, seluruh request Postman, Git publik, dan video tetap tahap berikutnya.

Checkpoint optimasi pra-finishing: profil tidak lagi mengirim seluruh kualifikasi dan riwayat jabatan sekaligus; detail awal tetap memuat jumlah kualifikasi dan jabatan aktif, sedangkan lima kategori riwayat dimuat saat tab pertama dibuka dan dipertahankan selama profil terbuka. Daftar personel hanya meminta opsi bidang fungsi, tanpa mengunduh seluruh pohon unit. Formulir tanpa pemilihan Satker memakai opsi `non_unit`; formulir yang memilih Satker kini mencari di server dengan minimal tiga karakter dan maksimum 25 hasil sesuai scope, bukan merender seluruh unit nasional. Animasi halaman dipersingkat. Pada data lokal, contoh respons profil turun dari 2.621 ke 997 byte dan opsi daftar dari 4.205 ke 616 byte. Scope Operator memakai recursive CTE di database untuk penyaringan personel, unit, dan dashboard, bukan menyalin seluruh unit aktif ke PHP; pencarian substring nama/NRP serta nama/kode unit mendapat indeks GIN `pg_trgm` parsial. Navigasi balik daftar ↔ profil kini menampilkan cache sementara secara instan dengan revalidasi di belakang layar; cache berumur pendek dan dibatalkan saat data/akun berubah atau akses ditolak. Waktu endpoint ringan `/auth/me` masih sekitar 240–285 ms pada server development, sehingga angka latensi akhir tidak boleh diklaim sebagai hasil database saja. Seluruh 132 test / 711 assertion backend lulus di SQLite dan database terisolasi PostgreSQL 17, serta 14 test frontend, lint, build, dan Pint lulus; smoke browser menunjukkan tab yang pernah dibuka serta navigasi balik daftar ↔ profil tanpa loader. Tanpa simulasi data besar, kapasitas nasional belum terukur dan bukan klaim hasil tahap ini.

- [x] Membaca `Paparan Uji Pemrograman.pdf` secara lengkap.
- [x] Membaca `Soal CRUD.pdf` secara lengkap.
- [x] Menentukan technology stack: Laravel, React + Vite, PostgreSQL, dan Sanctum.
- [x] Menetapkan interpretasi data kualifikasi.
- [x] Menetapkan role, scope, dan hierarki organisasi.
- [x] Menetapkan aturan jabatan utama, penugasan tambahan, dan mutasi.
- [x] Menetapkan dokumen PDF sebagai upload opsional.
- [x] Membuat `implementation_plan.md`.
- [x] Membuat `task.md`.
- [x] Review akhir implementation plan sebelum scaffold project.

## B. Repository dan Fondasi

- [x] Membuat struktur repository monorepo.
- [x] Menginisialisasi Git.
- [x] Membuat `.gitignore` yang sesuai.
- [x] Scaffold project Laravel pada `backend`.
- [x] Scaffold React + Vite pada `frontend`.
- [x] Mengonfigurasi koneksi PostgreSQL lokal.
- [x] Menambahkan `.env.example` backend dan frontend tanpa secret.
- [x] Menetapkan format/lint backend.
- [x] Menetapkan format/lint frontend.
- [x] Membuat commit fondasi.

## C. Database dan Data Referensi

- [x] Membuat migration `users` dan kebutuhan Sanctum.
- [x] Membuat migration `unit_organisasi` dengan relasi parent-child.
- [x] Membuat migration `user_scopes`.
- [x] Membuat migration `pangkats`.
- [x] Membuat migration `bidang_fungsi`.
- [x] Membuat migration `jenis_kualifikasi`.
- [x] Membuat migration `jenis_penugasan`.
- [x] Membuat migration `personel`.
- [x] Membuat migration `kualifikasi_personel`.
- [x] Membuat migration `riwayat_jabatan`.
- [x] Menambahkan foreign key, index, unique constraint, dan soft delete.
- [x] Menambahkan constraint satu jabatan utama aktif per personel.
- [x] Membuat model dan relasi Eloquent.
- [x] Membuat factory data uji.
- [x] Membuat seeder pangkat.
- [x] Membuat seeder bidang/fungsi.
- [x] Membuat seeder jenis kualifikasi.
- [x] Membuat seeder DEFINITIF, PS, PLT, dan PLH.
- [x] Membuat seeder struktur organisasi contoh.
- [x] Membuat akun demo System Admin, Admin SSDM, dan beberapa Operator scope.
- [x] Membuat personel, kualifikasi, dan riwayat jabatan demo.
- [x] Menjalankan migration dan seeder dari database kosong.

## D. Authentication dan Pengguna

- [x] Memasang dan mengonfigurasi Laravel Sanctum.
- [x] Membuat endpoint login.
- [x] Membuat endpoint logout/revoke token.
- [x] Membuat endpoint profil pengguna saat ini.
- [x] Membatasi login akun nonaktif.
- [x] Mencabut token saat akun dinonaktifkan.
- [x] Membuat CRUD pengguna sesuai permission.
- [x] Membuat assignment role, unit, dan scope pengguna.
- [x] Mencegah pengguna menaikkan role atau mengubah scope sendiri.
- [x] Membuat test authentication berhasil/gagal.

## E. Authorization dan Organizational Scope

- [x] Membuat enum/konstanta role.
- [x] Membuat `OWN_UNIT` dan `UNIT_AND_DESCENDANTS` scope.
- [x] Membuat resolver descendant unit.
- [x] Membuat middleware/policy untuk System Admin.
- [x] Membuat policy akses global Admin SSDM.
- [x] Membuat policy akses Operator berdasarkan scope.
- [x] Menerapkan scope pada query daftar, detail, create, update, dan delete.
- [x] Memastikan UI bukan satu-satunya lapisan authorization.
- [x] Menguji akses Operator terhadap unit sendiri.
- [x] Menguji penolakan akses ke unit lain.
- [x] Menguji Operator unit induk terhadap descendant.
- [x] Menguji IDOR dengan mengganti ID pada URL/API.

## F. CRUD Data Referensi

- [x] CRUD unit organisasi untuk pengguna berwenang.
- [x] Validasi parent dan pencegahan hierarchy cycle.
- [x] CRUD pangkat.
- [x] CRUD bidang/fungsi.
- [x] CRUD jenis kualifikasi.
- [x] CRUD jenis penugasan.
- [x] Membatasi Operator biasa menjadi read-only terhadap referensi.

## G. CRUD Personel

- [x] Membuat endpoint daftar personel.
- [x] Membuat endpoint detail personel.
- [x] Membuat endpoint tambah personel.
- [x] Membuat endpoint ubah personel.
- [x] Membuat endpoint soft delete personel.
- [x] Membuat endpoint restore sesuai permission.
- [x] Memvalidasi field wajib.
- [x] Memvalidasi nomor identitas sebagai string dan unik.
- [x] Memvalidasi tanggal lahir.
- [x] Memvalidasi pangkat dan unit aktif.
- [x] Memastikan Operator tidak dapat memilih unit di luar scope.
- [x] Menambahkan pagination.
- [x] Menambahkan pencarian nama dan NRP/NIP.
- [x] Menambahkan filter unit, pangkat, fungsi, jenis kualifikasi, dan status.
- [x] Menambahkan sorting yang disepakati.
- [x] Membuat test CRUD dan validation personel.

## H. CRUD Kualifikasi

- [x] Membuat endpoint daftar kualifikasi personel.
- [x] Membuat endpoint tambah kualifikasi.
- [x] Membuat endpoint ubah kualifikasi.
- [x] Membuat endpoint soft delete kualifikasi.
- [x] Memvalidasi jenis dan nama kualifikasi.
- [x] Memvalidasi tanggal mulai/selesai.
- [x] Mendukung bidang/fungsi nullable untuk kualifikasi umum.
- [x] Mendukung dua kegiatan dengan nama sama pada waktu berbeda.
- [x] Menambahkan upload PDF opsional maksimal 5 MB.
- [x] Memvalidasi MIME dan ekstensi PDF.
- [x] Menyimpan file pada storage privat.
- [x] Membuat endpoint download berizin.
- [x] Menghapus/membersihkan file sesuai kebijakan soft delete.
- [x] Membuat test CRUD, upload, dan authorization kualifikasi.

## I. CRUD Riwayat Jabatan

- [x] Membuat endpoint daftar riwayat jabatan personel.
- [x] Membuat endpoint tambah riwayat jabatan.
- [x] Membuat endpoint ubah riwayat jabatan.
- [x] Membuat endpoint soft delete riwayat selesai.
- [x] Memvalidasi tanggal mulai/selesai.
- [x] Memvalidasi satu jabatan utama aktif.
- [x] Mencegah overlap riwayat jabatan utama.
- [x] Mengizinkan overlap penugasan tambahan.
- [x] Mendukung DEFINITIF, PS, PLT, dan PLH.
- [x] Mengembalikan 409 saat menghapus jabatan utama aktif.
- [x] Membuat proses mengganti jabatan utama secara atomic.
- [x] Membuat proses mengakhiri jabatan sesuai status personel yang relevan.
- [x] Menambahkan upload SK PDF opsional maksimal 5 MB.
- [x] Membuat endpoint download SK berizin.
- [x] Menampilkan riwayat secara kronologis.
- [x] Tombol Tanggal mulai ↑/↓ pada riwayat jabatan, default terbaru; sorting server sebelum pagination, kembali ke halaman pertama dan cache terpisah per arah.
- [x] Membuat test jabatan utama dan penugasan tambahan.

## J. Mutasi

- [x] Membuat endpoint/proses mutasi.
- [x] Memvalidasi unit asal berada dalam scope pengguna.
- [x] Memvalidasi unit tujuan berada dalam scope pengguna.
- [x] Menutup jabatan utama lama.
- [x] Membuat jabatan utama baru.
- [x] Memperbarui unit personel.
- [x] Menjalankan seluruh perubahan dalam satu transaction.
- [x] Menyimpan actor dan waktu perubahan.
- [x] Memastikan operator lama kehilangan akses setelah mutasi.
- [x] Memastikan operator unit baru mendapat akses berdasarkan scope.
- [ ] Menguji mutasi dalam unit, antar-Polres, dan penolakan antar-scope.
- [x] Menguji rollback ketika mutasi gagal di tengah proses.

## K. Filter Kualifikasi dan Pengalaman

- [x] Membuat filter personel berdasarkan bidang/fungsi.
- [x] Menghitung jumlah kualifikasi relevan per personel.
- [x] Menghitung durasi pengalaman relevan dari riwayat jabatan.
- [x] Menambahkan sorting jumlah kualifikasi.
- [x] Menambahkan sorting durasi pengalaman.
- [x] Menambahkan sorting kualifikasi terbaru.
- [x] Memastikan hasil hanya menampilkan data dalam scope pengguna.
- [x] Memastikan tidak ada skor/ranking tersembunyi.
- [x] Membuat test query filter dan sorting.

## K.1 Perluasan Profil Merit

- [x] Prototype integrasi disiplin/kode etik read-only, Admin SSDM/System Admin saja, tanpa role Propam atau CRUD perkara.
- [x] Snapshot keputusan final/dibatalkan, ID sumber unik dan timestamp, pagination serta label DEMO/belum tersinkron.
- [x] QA browser Admin SSDM: tab prototype, label belum terhubung, kedua catatan final/dibatalkan tampil tanpa tombol perubahan.
- [x] Seeder opsional disiplin untuk DEMO 001 saja, local/testing, tidak menimpa data lama/personel manual.
- [x] Tes prototype: autentikasi, role/gate, operator ditolak, tidak bocor di profil biasa, GET-only, pagination, sumber demo saja, empty/arsip/404, seed idempotent dan guard production. Seluruh 193 test / 1081 assertion backend lulus pada SQLite dan PostgreSQL terisolasi; 20 test frontend, lint/build dan Pint lulus. Migrasi tambahan/seed demo diterapkan lokal tanpa reset.
- [ ] Integrasi nyata ke API Propam, validasi/pencocokan identitas, audit dan delta sync (pengembangan lanjutan, bukan koneksi aktif prototype).

- [x] Menetapkan konsep riwayat penugasan operasi sebagai data faktual terpisah.
- [x] Menetapkan konsep prestasi personel sebagai data faktual terpisah.
- [x] Membedakan prestasi dari penghargaan/tanda kehormatan resmi.
- [x] Menetapkan penilaian kinerja, assessment resmi, dan disiplin final sebagai pengembangan lanjutan.
- [x] Membuat migration, model, factory, dan seeder penugasan operasi.
- [x] Membuat CRUD, authorization scope, dokumen privat, filter, dan test penugasan operasi.
- [x] Membuat UI riwayat penugasan operasi pada profil personel.
- [x] Membuat migration, model, factory, dan seeder prestasi personel.
- [x] Membuat CRUD, authorization scope, dokumen privat, filter, dan test prestasi personel.
- [x] Membuat UI prestasi pada profil personel.
- [x] Membuat migration, model, factory, dan seeder penghargaan personel.
- [x] Membuat CRUD, authorization scope, dokumen privat, dan test penghargaan personel.
- [x] Membuat UI penghargaan pada profil personel.
- [x] Memperbarui OpenAPI, Postman, ERD, README, dan narasi demo modul tambahan (video tetap tahap berikutnya).

## L. Error Handling dan API Quality

- [x] Menetapkan format respons sukses yang konsisten.
- [x] Menetapkan format validation error yang konsisten.
- [x] Menangani 401.
- [x] Menangani 403.
- [x] Menangani 404.
- [x] Menangani 409.
- [x] Menangani 422.
- [x] Menangani 500 tanpa membocorkan stack trace.
- [x] Menambahkan API version prefix `/api/v1`.
- [x] Menambahkan rate limiting yang sesuai.
- [x] Mengonfigurasi CORS untuk frontend lokal.

## M. Frontend

- [x] Menyiapkan API client dan environment URL.
- [x] Membuat state/session authentication.
- [x] Membuat halaman login.
- [x] Membuat protected route.
- [x] Membuat layout dan navigasi berdasarkan permission untuk System Admin, Admin SSDM, dan Operator.
- [x] Membuat dashboard ringkas berbasis role dan cakupan akses.
- [x] Membuat halaman daftar personel.
- [x] Membuat search, filter, sorting, dan pagination UI.
- [x] Membuat form tambah/edit personel.
- [x] Membuat halaman profil personel.
- [x] Menampilkan jabatan utama aktif.
- [x] Menampilkan penugasan tambahan aktif.
- [x] Menampilkan kualifikasi.
- [x] Menampilkan riwayat jabatan kronologis.
- [x] Membuat form CRUD kualifikasi.
- [x] Membuat upload/download dokumen kualifikasi.
- [x] Membuat form CRUD riwayat jabatan.
- [x] Membuat upload/download SK.
- [x] Membuat konfirmasi soft delete personel, kualifikasi, dan riwayat jabatan pada UI.
- [x] Membuat UI pergantian jabatan dan mutasi.
- [x] Membuat halaman pengguna dan scope untuk Admin.
- [x] Membuat halaman referensi minimum.
- [x] Membuat menu dan ruang kerja System Admin untuk pengguna, scope, referensi, dan monitoring sistem.
- [x] Membuat menu dan ruang kerja Admin SSDM untuk pengelolaan bisnis nasional.
- [x] Membuat menu Operator yang hanya menampilkan fitur dan aksi dalam kewenangan scope-nya.
- [x] Menyembunyikan tombol/aksi yang tidak diizinkan berdasarkan role, tanpa menggantikan policy backend.
- [x] Menampilkan validation error dari API secara jelas.
- [x] Membuat halaman/state 401, 403, dan 404.
- [x] Memastikan UI responsif dan layak untuk demo.
- [x] Menjalankan lint frontend.
- [x] Menjalankan production build frontend.

## N. Automated Test dan QA

- [x] Menjalankan seluruh backend test.
- [x] Memastikan test authentication lulus.
- [x] Memastikan test authorization/scope lulus.
- [x] Memastikan test CRUD lulus.
- [x] Memastikan test validation lulus.
- [x] Memastikan test file security lulus.
- [x] Memastikan test jabatan/mutasi transaction lulus.
- [x] Memastikan test filter/sorting lulus.
- [x] Melakukan smoke QA sebagai System Admin.
- [x] Melakukan smoke QA sebagai Admin SSDM.
- [x] Melakukan smoke QA sebagai Operator Polda.
- [x] Melakukan smoke QA sebagai Operator Polres/Satker.
- [x] Menguji forbidden access dengan URL langsung.
- [x] Menguji aplikasi dengan data kosong dan pencarian tanpa hasil.
- [x] Menguji aplikasi dengan seed data.
- [ ] Memperbaiki seluruh error blocker dan high-priority.

## N.1 Optimasi Loading Pra-Finishing

- [x] Mengukur baseline endpoint lokal tanpa mengubah data demo.
- [x] Menghilangkan riwayat tidak terbatas dan duplikat dari respons profil.
- [x] Memuat riwayat saat tab dibuka dan mempertahankan data tab yang sudah dikunjungi.
- [x] Menghindari pengambilan seluruh hierarki unit untuk filter daftar personel.
- [x] Mempercepat animasi halaman yang menunda tampilan konten.
- [x] Menguji kontrak respons, build, lint, dan perilaku tab di browser.
- [x] Memindahkan resolusi scope turunan Satker ke recursive CTE database dan menghindari `WHERE IN` berisi seluruh unit nasional di query daftar/dashboard.
- [x] Menambahkan indeks PostgreSQL `pg_trgm` untuk pola pencarian substring nama dan NRP/NIP yang benar-benar dipakai API.
- [x] Mengganti dropdown Satker nasional pada formulir dengan pencarian server yang dibatasi 25 hasil dan scope; menambahkan indeks pencarian unit.
- [x] Menghitung batas transfer/DOM untuk contoh 100.000 personel dan 10.000 unit, serta mendokumentasikan kerja database yang masih tumbuh.
- [x] Menyimpan respons GET berumur pendek di memori untuk menampilkan daftar, profil, dashboard, dan riwayat yang pernah dibuka tanpa flash loading saat navigasi balik; tetap revalidasi di belakang layar dan batalkan cache saat mutasi/akun berubah/akses ditolak.
- [x] Menjalankan seluruh test di SQLite dan PostgreSQL 17 serta menerapkan migrasi indeks pada database lokal tanpa reset data.
- [ ] Mengevaluasi exact-count pagination dan pengurutan agregat jika pengukuran pemakaian riil kelak menunjukkan keduanya mahal; bukan blocker demo lokal.

## O. Dokumentasi API

- [x] Membuat spesifikasi OpenAPI/Swagger.
- [x] Mendokumentasikan authentication/token.
- [x] Mendokumentasikan request, response, validation, dan error.
- [x] Mendokumentasikan filter, sorting, dan pagination.
- [x] Mendokumentasikan upload/download PDF.
- [x] Membuat Postman Collection.
- [x] Membuat Postman environment lokal.
- [ ] Menguji seluruh request utama dari dokumentasi.
- [x] Memastikan dokumentasi sesuai endpoint aktual.

## P. README dan Dokumen Teknis

- [x] Menulis penjelasan aplikasi.
- [x] Menulis technology stack dan alasan pemilihannya.
- [x] Menulis requirement runtime.
- [x] Menulis instalasi backend.
- [x] Menulis instalasi frontend.
- [x] Menulis konfigurasi PostgreSQL.
- [x] Menulis migration dan seeding.
- [x] Menulis cara menjalankan aplikasi lokal.
- [x] Menulis cara menjalankan test.
- [x] Menulis akun demo tiap role/scope.
- [x] Menulis struktur database.
- [x] Menulis daftar endpoint/dokumentasi API.
- [x] Menulis keputusan authorization dan mutasi.
- [x] Menulis keterbatasan dan pengembangan lanjutan.
- [x] Membuat ERD.
- [x] Membuat diagram flow authentication.
- [x] Membuat diagram flow authorization/scope.
- [x] Membuat diagram flow mutasi.
- [ ] Menguji README dari kondisi setup bersih.

## Q. Git dan Repository

- [ ] Memastikan tidak ada secret di Git.
- [ ] Memastikan commit tersusun dan pesannya jelas.
- [ ] Memastikan branch/repository siap dipublikasikan.
- [ ] Membuat repository remote GitHub/GitLab.
- [ ] Push source code lengkap.
- [ ] Memastikan repository dapat di-clone.
- [ ] Menjalankan setup dari hasil clone bersih.
- [ ] Memastikan README tampil dengan benar pada repository.

## R. Persiapan Video Presentasi

- [ ] Menyusun naskah perkenalan peserta.
- [ ] Menjelaskan technology stack.
- [ ] Menjelaskan struktur aplikasi.
- [ ] Menjelaskan struktur database/ERD.
- [ ] Menjelaskan REST API dan HTTP method.
- [ ] Menjelaskan authentication.
- [ ] Menjelaskan authorization, role, hierarchy, dan scope.
- [ ] Menjelaskan source code utama.
- [ ] Mendemonstrasikan login tiap role penting.
- [ ] Mendemonstrasikan CRUD personel.
- [ ] Mendemonstrasikan kualifikasi dan filter bidang.
- [ ] Mendemonstrasikan riwayat jabatan dan mutasi.
- [ ] Mendemonstrasikan upload/download PDF opsional.
- [ ] Mendemonstrasikan penolakan akses lintas-unit.
- [ ] Menjelaskan kendala yang ditemukan.
- [ ] Menjelaskan keterbatasan dan pengembangan lanjutan.
- [ ] Memastikan kamera peserta terlihat sesuai ketentuan.
- [ ] Merekam video final.
- [ ] Memeriksa audio, kamera, dan keterbacaan layar.
- [ ] Mengunggah video ke YouTube/Google Drive/platform yang digunakan.
- [ ] Memastikan link video dapat diakses penguji.

## S. Final Submission

### Revisi pendaftaran dan administrasi (2 Oktober 2026)

- [x] Satker menjadi satu combobox pencarian otomatis yang terbatas dan sesuai scope.
- [x] Pendidikan umum wajib; pendidikan Polri wajib untuk POLRI, opsional bagi PNS; tanpa backfill data rekaan.
- [x] Riwayat kualifikasi tambahan, jabatan terdahulu, operasi, prestasi, penghargaan opsional saat registrasi dan dapat dilengkapi dari profil.
- [x] Registrasi atomik dengan PDF privat opsional serta rollback data/file.
- [x] Placeholder foto belum tersedia (disabled, bukan upload aktif).
- [x] Akun staff memakai personel database; nama server-derived, pemilik individu unik, scope independen; programmer eksternal boleh tanpa personel.
- [x] Arsip personel admin-only dengan alasan, tidak mengubah tanggal karier; akun terkait dinonaktifkan/token dicabut.
- [x] Daftar arsip berhalaman dan pemulihan admin; akun tidak otomatis aktif.
- [x] Label urutan pangkat dan pengelompokan POLRI/PNS, bukan nilai merit.
- [x] Daftar baku pangkat lengkap termasuk enam Tamtama dan 17 PNS; pemeliharaan dibatasi System Admin pada API/UI, seed ulang menjaga ID/status lama.
- [x] Seeder demo opsional 100 personel sintetis, variasi pendidikan/karier/merit dan cabang organisasi; idempotensi, perlindungan data lama, rollback, serta larangan production diuji.
- [x] Sorting klik header Personel/Jabatan/Satker/Kualifikasi/Pengalaman/Operasi; indikator arah, dropdown sinkron, filter/scope dipertahankan, urutan server sebelum pagination dan kembali ke halaman pertama.
- [x] Rancangan disiplin final dengan pembatasan akses/audit/versioning dicatat; belum modul input umum.
- [x] Uji transaksi, validasi pendidikan/riwayat, linkage akun, role, arsip/restore; full suite SQLite dan PostgreSQL lulus.
- [x] Verifikasi antarmuka baru di browser dan sinkronisasi kontrak OpenAPI/Postman.

Checkpoint revisi 2 Oktober 2026: pendaftaran beserta pendidikan wajib dan riwayat opsional, pemilih Satker satu kolom, linkage akun personel, arsip/pemulihan admin, serta label pangkat selesai. Seluruh 153 test / 824 assertion backend lulus pada SQLite dan PostgreSQL terisolasi; 17 test frontend, lint, build, Pint, parsing OpenAPI/Postman lulus. Browser memverifikasi formulir registrasi, pendidikan Polri opsional untuk PNS, pencarian personel pada akun, dan daftar arsip. Foto masih placeholder; riwayat pelanggaran masih rancangan terbatas, bukan modul input. Data pengguna lama tidak diisi dengan pendidikan atau hubungan akun rekaan. Setup/clone bersih, pengujian Postman menyeluruh, publikasi repository, dan video tetap tahap finishing.

Checklist final submission berikut tetap belum selesai hanya karena revisi di atas selesai.

### Kesiapan prototype integrasi personel (2 Oktober 2026)

- [x] Pencarian nama tidak case-sensitive, termasuk personel yang dipulihkan dari arsip; teks tersimpan tidak diubah.
- [x] Card dashboard memperjelas total catatan kualifikasi, bukan jumlah personel; login memakai bahasa formal Sistem Merit Personel Polri.
- [x] Kontrak adapter sumber canonical + simulasi versi 1/2, tanpa koneksi/kredensial SIPP.
- [x] Pratinjau staging tanpa perubahan personel, konfirmasi eksplisit, batch maksimal 25, laporan tersimpan berhalaman dan resume.
- [x] ID sumber unik/NRP unik, konflik pemetaan/manual edit/arsip/mutasi, anti-duplikasi dan pengaman fingerprint.
- [x] Pendidikan wajib/jabatan awal mengikuti validasi dan transaksi domain; perubahan identitas dasar tidak menimpa riwayat lokal.
- [x] Initial import dan delta checkpoint; tombstone tidak menghapus personel, checkpoint tidak maju saat konflik/gagal, baseline stale ditolak.
- [x] Pembatasan Admin SSDM/System Admin, penolakan Operator, simulasi write hanya local/testing, laporan tidak membocorkan payload penuh.
- [x] Uji sumber/laporan berhalaman, rollback per item, retry dan perubahan pemetaan referensi sesudah pratinjau; 13 test integrasi / 133 assertion lulus. Full 206 test / 1214 assertion lulus pada SQLite dan PostgreSQL terisolasi; 20 test frontend, lint/build/Pint dan parsing OpenAPI/Postman lulus.
- [x] Browser: pratinjau → konfirmasi → impor tiga data sintetis → delta tambah/update/tombstone, checkpoint 1→2; empat personel IMPORT DEMO lokal, data manual dipertahankan.
- [x] README, rencana, arsitektur, OpenAPI dan Postman diperbarui dengan batas prototype/pengembangan produksi.
- [ ] Adapter REST sumber resmi, pemetaan/governance resmi, cursor delta resmi, queue streaming/backoff, audit dan monitoring produksi (pengembangan lanjutan, bukan requirement koneksi ujian).

Checkpoint sorting header: enam header klik dengan panah aktif dan `aria-sort`, keyboard focus, teks A–Z/Z–A, serta arah awal numerik menurun selesai. Backend menerima `jabatan` dan `satker`, mengambil satu nilai terindeks tanpa menggandakan baris, mengabaikan jabatan lama/tambahan/soft-deleted, dan memakai ID sebagai tie-breaker. Browser memverifikasi toggle Satker serta sorting dari halaman 2 kembali ke 1 sambil mempertahankan filter Aktif dan sinkronisasi dropdown. Seluruh 176 test / 971 assertion backend lulus pada SQLite dan PostgreSQL terisolasi; 20 test frontend, lint/build, dan Pint lulus. README, rencana, dan kontrak OpenAPI diperbarui.

- [x] Sorting nama/jabatan/satker mengabaikan kapitalisasi tanpa mengubah teks tersimpan; indeks ekspresi nama ditambahkan.
- [x] Akun demo Polsek Talang Empat `operator.polsek@example.test` dengan OWN_UNIT dan seeder upgrade tanpa reset akun lama.
- [x] Tes campuran Dimas/diva/Eka dua arah sebelum pagination dan scope Polsek; 184 test / 1021 assertion backend lulus pada SQLite dan PostgreSQL terisolasi, 20 test frontend serta lint/build/Pint lulus. Migrasi tambahan dan seeder Polsek diterapkan lokal tanpa reset data.

Verifikasi tambahan pangkat: 162 test / 865 assertion backend lulus pada SQLite dan PostgreSQL terisolasi; 17 test frontend, lint/build, dan Pint lulus. Browser Admin SSDM menampilkan pangkat Tamtama tanpa tombol tambah/edit/hapus. Seed pangkat diterapkan ke database lokal tanpa reset personel.

Checkpoint dataset demo: `DemoPersonnelSeeder` tidak masuk seed instalasi standar; 100 data bertanda DEMO diterapkan ke database lokal (95 tampil dan 5 arsip), tanpa reset, penambahan akun, atau perubahan scope. Pendidikan wajib dan variasi riwayat tersedia; merit belum diverifikasi dan tanpa PDF palsu. Delapan test tambahan mencakup komposisi data, menjalankan ulang setelah edit/arsip, benturan identitas, prasyarat, rollback konflik unit, production guard, instalasi standar tetap kecil, pagination/filter/scope API. Seluruh 170 test / 947 assertion backend lulus pada SQLite dan PostgreSQL terisolasi; Pint lulus. Ini dataset demonstrasi, bukan hasil uji beban nasional.

### Perapian field jabatan sesuai soal

- [x] Label Status personel dibedakan dari Status jabatan / jenis penugasan pada registrasi, riwayat dan mutasi; profil memberi label Status jabatan pada setiap riwayat.
- [x] Nivelering dan keterangan jabatan utama tersedia secara opsional saat registrasi; dikirim memakai field API yang sudah ada, dengan batas panjang dan pesan validasi backend.
- [x] Verifikasi: 21 tes frontend, lint dan build lulus; 38 tes backend terkait registrasi/arsip/riwayat/detail dengan 205 assertion lulus pada SQLite. Tidak ada perubahan skema, data, maupun kontrak API.

### Perbaikan panduan clean install dan tes CORS

- [x] Tes preflight mengatur origin sendiri, mencakup port 5173/5174 dan penolakan origin asing, tanpa bergantung pada `.env` developer.
- [x] README memakai `migrate --seed` untuk instalasi baru, memperingatkan risiko `migrate:fresh` dan seed ulang pada instalasi lama, serta memakai `npm ci` dan port Vite eksplisit dengan `--strictPort`.
- [x] Contoh konfigurasi port alternatif menjelaskan `FRONTEND_ORIGINS`, `VITE_API_URL`, pembersihan config dan restart layanan.
- [x] Verifikasi perbaikan: 9 tes API quality / 41 assertion lulus dengan konfigurasi standar dan origin lingkungan 5174; seluruh 207 tes backend / 1221 assertion lulus pada SQLite dengan origin lingkungan 5174; Pint lulus. Konfigurasi CORS runtime dan database aplikasi tidak diubah.

- [ ] Memastikan aplikasi berjalan lokal.
- [ ] Memastikan seluruh requirement wajib terpenuhi.
- [ ] Memastikan seluruh test penting lulus.
- [ ] Memastikan repository dapat diakses.
- [ ] Memastikan README dan dokumentasi API lengkap.
- [ ] Memastikan video dapat diakses.
- [ ] Menyiapkan link repository.
- [ ] Menyiapkan link video.
- [ ] Mengisi formulir pengumpulan dari paparan.
- [ ] Menyimpan bukti pengumpulan.
- [ ] Menyiapkan ringkasan keputusan teknis untuk wawancara pendalaman.

### Pembaruan form pendidikan saat registrasi

- [x] Judul Pendidikan Umum dan Pendidikan Polri tanpa kata wajib; tombol Tambah pendidikan dan batal catatan tambahan (maksimal 20 per jenis).
- [x] Pendidikan umum memiliki pilihan jenjang SD–S3 dan label Jurusan / bidang studi (opsional); form pendidikan Polri tidak menampilkan jenjang/jurusan, dengan contoh Diktukba/Akpol.
- [x] Minimal satu pendidikan umum; minimal satu pendidikan Polri untuk POLRI. PNS boleh tanpa pendidikan Polri atau menambahkannya jika ada.
- [x] Backend menerima array pendidikan beserta PDF per catatan, mempertahankan format objek tunggal API/integrasi lama dan transaksi atomik tanpa perubahan skema database.
- [x] Verifikasi: 219 tes backend / 1284 assertion, 22 tes frontend, build, lint dan Pint lulus. Browser memverifikasi tambah pendidikan umum/Polri, tambah/batal pendidikan PNS dan kembali ke POLRI. Tidak menyimpan personel uji ke database lokal.
- [x] Kontrak OpenAPI dan indeks proyek diperbarui.
