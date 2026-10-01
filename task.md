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
- [ ] Mencabut token saat akun dinonaktifkan.
- [ ] Membuat CRUD pengguna sesuai permission.
- [ ] Membuat assignment role, unit, dan scope pengguna.
- [ ] Mencegah pengguna menaikkan role atau mengubah scope sendiri.
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

- [ ] CRUD unit organisasi untuk pengguna berwenang.
- [ ] Validasi parent dan pencegahan hierarchy cycle.
- [ ] CRUD pangkat.
- [ ] CRUD bidang/fungsi.
- [ ] CRUD jenis kualifikasi.
- [ ] CRUD jenis penugasan.
- [ ] Membatasi Operator biasa menjadi read-only terhadap referensi.

## G. CRUD Personel

- [x] Membuat endpoint daftar personel.
- [x] Membuat endpoint detail personel.
- [x] Membuat endpoint tambah personel.
- [x] Membuat endpoint ubah personel.
- [x] Membuat endpoint soft delete personel.
- [ ] Membuat endpoint restore sesuai permission.
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

## L. Error Handling dan API Quality

- [ ] Menetapkan format respons sukses yang konsisten.
- [ ] Menetapkan format validation error yang konsisten.
- [x] Menangani 401.
- [x] Menangani 403.
- [ ] Menangani 404.
- [x] Menangani 409.
- [ ] Menangani 422.
- [ ] Menangani 500 tanpa membocorkan stack trace.
- [x] Menambahkan API version prefix `/api/v1`.
- [x] Menambahkan rate limiting yang sesuai.
- [ ] Mengonfigurasi CORS untuk frontend lokal.

## M. Frontend

- [x] Menyiapkan API client dan environment URL.
- [x] Membuat state/session authentication.
- [x] Membuat halaman login.
- [x] Membuat protected route.
- [x] Membuat layout dan navigasi berdasarkan permission.
- [x] Membuat dashboard ringkas.
- [x] Membuat halaman daftar personel.
- [x] Membuat search, filter, sorting, dan pagination UI.
- [x] Membuat form tambah/edit personel.
- [x] Membuat halaman profil personel.
- [x] Menampilkan jabatan utama aktif.
- [x] Menampilkan penugasan tambahan aktif.
- [x] Menampilkan kualifikasi.
- [x] Menampilkan riwayat jabatan kronologis.
- [ ] Membuat form CRUD kualifikasi.
- [ ] Membuat upload/download dokumen kualifikasi.
- [ ] Membuat form CRUD riwayat jabatan.
- [ ] Membuat upload/download SK.
- [x] Membuat UI pergantian jabatan dan mutasi.
- [ ] Membuat halaman pengguna dan scope untuk Admin.
- [ ] Membuat halaman referensi minimum.
- [x] Menampilkan validation error dari API secara jelas.
- [ ] Membuat halaman/state 401, 403, dan 404.
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
