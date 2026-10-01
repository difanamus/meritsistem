# Task Checklist - Merit System Personel Polri

Checklist ini harus diperbarui selama pengerjaan. Centang `[x]` hanya setelah implementasi atau verifikasi benar-benar selesai.

## A. Requirement dan Perencanaan

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
- [ ] Mengonfigurasi koneksi PostgreSQL lokal.
- [x] Menambahkan `.env.example` backend dan frontend tanpa secret.
- [x] Menetapkan format/lint backend.
- [x] Menetapkan format/lint frontend.
- [x] Membuat commit fondasi.

## C. Database dan Data Referensi

- [ ] Membuat migration `users` dan kebutuhan Sanctum.
- [ ] Membuat migration `unit_organisasi` dengan relasi parent-child.
- [ ] Membuat migration `user_scopes`.
- [ ] Membuat migration `pangkats`.
- [ ] Membuat migration `bidang_fungsi`.
- [ ] Membuat migration `jenis_kualifikasi`.
- [ ] Membuat migration `jenis_penugasan`.
- [ ] Membuat migration `personel`.
- [ ] Membuat migration `kualifikasi_personel`.
- [ ] Membuat migration `riwayat_jabatan`.
- [ ] Menambahkan foreign key, index, unique constraint, dan soft delete.
- [ ] Menambahkan constraint satu jabatan utama aktif per personel.
- [ ] Membuat model dan relasi Eloquent.
- [ ] Membuat factory data uji.
- [ ] Membuat seeder pangkat.
- [ ] Membuat seeder bidang/fungsi.
- [ ] Membuat seeder jenis kualifikasi.
- [ ] Membuat seeder DEFINITIF, PS, PLT, dan PLH.
- [ ] Membuat seeder struktur organisasi contoh.
- [ ] Membuat akun demo System Admin, Admin SSDM, dan beberapa Operator scope.
- [ ] Membuat personel, kualifikasi, dan riwayat jabatan demo.
- [ ] Menjalankan migration dan seeder dari database kosong.

## D. Authentication dan Pengguna

- [ ] Memasang dan mengonfigurasi Laravel Sanctum.
- [ ] Membuat endpoint login.
- [ ] Membuat endpoint logout/revoke token.
- [ ] Membuat endpoint profil pengguna saat ini.
- [ ] Membatasi login akun nonaktif.
- [ ] Mencabut token saat akun dinonaktifkan.
- [ ] Membuat CRUD pengguna sesuai permission.
- [ ] Membuat assignment role, unit, dan scope pengguna.
- [ ] Mencegah pengguna menaikkan role atau mengubah scope sendiri.
- [ ] Membuat test authentication berhasil/gagal.

## E. Authorization dan Organizational Scope

- [ ] Membuat enum/konstanta role.
- [ ] Membuat `OWN_UNIT` dan `UNIT_AND_DESCENDANTS` scope.
- [ ] Membuat resolver descendant unit.
- [ ] Membuat middleware/policy untuk System Admin.
- [ ] Membuat policy akses global Admin SSDM.
- [ ] Membuat policy akses Operator berdasarkan scope.
- [ ] Menerapkan scope pada query daftar, detail, create, update, dan delete.
- [ ] Memastikan UI bukan satu-satunya lapisan authorization.
- [ ] Menguji akses Operator terhadap unit sendiri.
- [ ] Menguji penolakan akses ke unit lain.
- [ ] Menguji Operator unit induk terhadap descendant.
- [ ] Menguji IDOR dengan mengganti ID pada URL/API.

## F. CRUD Data Referensi

- [ ] CRUD unit organisasi untuk pengguna berwenang.
- [ ] Validasi parent dan pencegahan hierarchy cycle.
- [ ] CRUD pangkat.
- [ ] CRUD bidang/fungsi.
- [ ] CRUD jenis kualifikasi.
- [ ] CRUD jenis penugasan.
- [ ] Membatasi Operator biasa menjadi read-only terhadap referensi.

## G. CRUD Personel

- [ ] Membuat endpoint daftar personel.
- [ ] Membuat endpoint detail personel.
- [ ] Membuat endpoint tambah personel.
- [ ] Membuat endpoint ubah personel.
- [ ] Membuat endpoint soft delete personel.
- [ ] Membuat endpoint restore sesuai permission.
- [ ] Memvalidasi field wajib.
- [ ] Memvalidasi nomor identitas sebagai string dan unik.
- [ ] Memvalidasi tanggal lahir.
- [ ] Memvalidasi pangkat dan unit aktif.
- [ ] Memastikan Operator tidak dapat memilih unit di luar scope.
- [ ] Menambahkan pagination.
- [ ] Menambahkan pencarian nama dan NRP/NIP.
- [ ] Menambahkan filter unit, pangkat, fungsi, jenis kualifikasi, dan status.
- [ ] Menambahkan sorting yang disepakati.
- [ ] Membuat test CRUD dan validation personel.

## H. CRUD Kualifikasi

- [ ] Membuat endpoint daftar kualifikasi personel.
- [ ] Membuat endpoint tambah kualifikasi.
- [ ] Membuat endpoint ubah kualifikasi.
- [ ] Membuat endpoint soft delete kualifikasi.
- [ ] Memvalidasi jenis dan nama kualifikasi.
- [ ] Memvalidasi tanggal mulai/selesai.
- [ ] Mendukung bidang/fungsi nullable untuk kualifikasi umum.
- [ ] Mendukung dua kegiatan dengan nama sama pada waktu berbeda.
- [ ] Menambahkan upload PDF opsional maksimal 5 MB.
- [ ] Memvalidasi MIME dan ekstensi PDF.
- [ ] Menyimpan file pada storage privat.
- [ ] Membuat endpoint download berizin.
- [ ] Menghapus/membersihkan file sesuai kebijakan soft delete.
- [ ] Membuat test CRUD, upload, dan authorization kualifikasi.

## I. CRUD Riwayat Jabatan

- [ ] Membuat endpoint daftar riwayat jabatan personel.
- [ ] Membuat endpoint tambah riwayat jabatan.
- [ ] Membuat endpoint ubah riwayat jabatan.
- [ ] Membuat endpoint soft delete riwayat selesai.
- [ ] Memvalidasi tanggal mulai/selesai.
- [ ] Memvalidasi satu jabatan utama aktif.
- [ ] Mencegah overlap riwayat jabatan utama.
- [ ] Mengizinkan overlap penugasan tambahan.
- [ ] Mendukung DEFINITIF, PS, PLT, dan PLH.
- [ ] Mengembalikan 409 saat menghapus jabatan utama aktif.
- [ ] Membuat proses mengganti jabatan utama secara atomic.
- [ ] Membuat proses mengakhiri jabatan sesuai status personel yang relevan.
- [ ] Menambahkan upload SK PDF opsional maksimal 5 MB.
- [ ] Membuat endpoint download SK berizin.
- [ ] Menampilkan riwayat secara kronologis.
- [ ] Membuat test jabatan utama dan penugasan tambahan.

## J. Mutasi

- [ ] Membuat endpoint/proses mutasi.
- [ ] Memvalidasi unit asal berada dalam scope pengguna.
- [ ] Memvalidasi unit tujuan berada dalam scope pengguna.
- [ ] Menutup jabatan utama lama.
- [ ] Membuat jabatan utama baru.
- [ ] Memperbarui unit personel.
- [ ] Menjalankan seluruh perubahan dalam satu transaction.
- [ ] Menyimpan actor dan waktu perubahan.
- [ ] Memastikan operator lama kehilangan akses setelah mutasi.
- [ ] Memastikan operator unit baru mendapat akses berdasarkan scope.
- [ ] Menguji mutasi dalam unit, antar-Polres, dan penolakan antar-scope.
- [ ] Menguji rollback ketika mutasi gagal di tengah proses.

## K. Filter Kualifikasi dan Pengalaman

- [ ] Membuat filter personel berdasarkan bidang/fungsi.
- [ ] Menghitung jumlah kualifikasi relevan per personel.
- [ ] Menghitung durasi pengalaman relevan dari riwayat jabatan.
- [ ] Menambahkan sorting jumlah kualifikasi.
- [ ] Menambahkan sorting durasi pengalaman.
- [ ] Menambahkan sorting kualifikasi terbaru.
- [ ] Memastikan hasil hanya menampilkan data dalam scope pengguna.
- [ ] Memastikan tidak ada skor/ranking tersembunyi.
- [ ] Membuat test query filter dan sorting.

## L. Error Handling dan API Quality

- [ ] Menetapkan format respons sukses yang konsisten.
- [ ] Menetapkan format validation error yang konsisten.
- [ ] Menangani 401.
- [ ] Menangani 403.
- [ ] Menangani 404.
- [ ] Menangani 409.
- [ ] Menangani 422.
- [ ] Menangani 500 tanpa membocorkan stack trace.
- [ ] Menambahkan API version prefix `/api/v1`.
- [ ] Menambahkan rate limiting yang sesuai.
- [ ] Mengonfigurasi CORS untuk frontend lokal.

## M. Frontend

- [ ] Menyiapkan API client dan environment URL.
- [ ] Membuat state/session authentication.
- [ ] Membuat halaman login.
- [ ] Membuat protected route.
- [ ] Membuat layout dan navigasi berdasarkan permission.
- [ ] Membuat dashboard ringkas.
- [ ] Membuat halaman daftar personel.
- [ ] Membuat search, filter, sorting, dan pagination UI.
- [ ] Membuat form tambah/edit personel.
- [ ] Membuat halaman profil personel.
- [ ] Menampilkan jabatan utama aktif.
- [ ] Menampilkan penugasan tambahan aktif.
- [ ] Menampilkan kualifikasi.
- [ ] Menampilkan riwayat jabatan kronologis.
- [ ] Membuat form CRUD kualifikasi.
- [ ] Membuat upload/download dokumen kualifikasi.
- [ ] Membuat form CRUD riwayat jabatan.
- [ ] Membuat upload/download SK.
- [ ] Membuat UI pergantian jabatan dan mutasi.
- [ ] Membuat halaman pengguna dan scope untuk Admin.
- [ ] Membuat halaman referensi minimum.
- [ ] Menampilkan validation error dari API secara jelas.
- [ ] Membuat halaman/state 401, 403, dan 404.
- [ ] Memastikan UI responsif dan layak untuk demo.
- [ ] Menjalankan lint frontend.
- [ ] Menjalankan production build frontend.

## N. Automated Test dan QA

- [ ] Menjalankan seluruh backend test.
- [ ] Memastikan test authentication lulus.
- [ ] Memastikan test authorization/scope lulus.
- [ ] Memastikan test CRUD lulus.
- [ ] Memastikan test validation lulus.
- [ ] Memastikan test file security lulus.
- [ ] Memastikan test jabatan/mutasi transaction lulus.
- [ ] Memastikan test filter/sorting lulus.
- [ ] Melakukan manual QA sebagai System Admin.
- [ ] Melakukan manual QA sebagai Admin SSDM.
- [ ] Melakukan manual QA sebagai Operator Polda.
- [ ] Melakukan manual QA sebagai Operator Polres/Satker.
- [ ] Menguji forbidden access dengan URL langsung.
- [ ] Menguji aplikasi dengan data kosong.
- [ ] Menguji aplikasi dengan seed data.
- [ ] Memperbaiki seluruh error blocker dan high-priority.

## O. Dokumentasi API

- [ ] Membuat spesifikasi OpenAPI/Swagger.
- [ ] Mendokumentasikan authentication/token.
- [ ] Mendokumentasikan request, response, validation, dan error.
- [ ] Mendokumentasikan filter, sorting, dan pagination.
- [ ] Mendokumentasikan upload/download PDF.
- [ ] Membuat Postman Collection.
- [ ] Membuat Postman environment lokal.
- [ ] Menguji seluruh request utama dari dokumentasi.
- [ ] Memastikan dokumentasi sesuai endpoint aktual.

## P. README dan Dokumen Teknis

- [ ] Menulis penjelasan aplikasi.
- [ ] Menulis technology stack dan alasan pemilihannya.
- [ ] Menulis requirement runtime.
- [ ] Menulis instalasi backend.
- [ ] Menulis instalasi frontend.
- [ ] Menulis konfigurasi PostgreSQL.
- [ ] Menulis migration dan seeding.
- [ ] Menulis cara menjalankan aplikasi lokal.
- [ ] Menulis cara menjalankan test.
- [ ] Menulis akun demo tiap role/scope.
- [ ] Menulis struktur database.
- [ ] Menulis daftar endpoint/dokumentasi API.
- [ ] Menulis keputusan authorization dan mutasi.
- [ ] Menulis keterbatasan dan pengembangan lanjutan.
- [ ] Membuat ERD.
- [ ] Membuat diagram flow authentication.
- [ ] Membuat diagram flow authorization/scope.
- [ ] Membuat diagram flow mutasi.
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
