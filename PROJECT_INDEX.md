# Peta Proyek Merit System Personel Polri

Indeks kode dan konteks untuk AI lain. Dipetakan pada 3 Oktober 2026, berdasarkan repository pada commit `677eb2a`. Dokumen ini tidak mengubah aplikasi dan bukan bukti pengujian ulang.

## Cara menggunakan sebagai prompt

Salin seluruh dokumen ini ke AI lain, atau lampirkan file ini bersama repository. Setelah itu ajukan pertanyaan, misalnya: “Di mana kode sorting personel?” atau “Jelaskan proses mutasi.”

Instruksi untuk AI penerima:

- Gunakan peta ini untuk menemukan kode, kemudian baca file terkait sebelum menyimpulkan atau mengubahnya.
- Jawab dengan lokasi file dan nama fungsi/kelas yang relevan. Jangan mengarang nomor baris; nomor baris dapat berubah.
- Bedakan fitur yang sudah ada, simulasi/prototype, dan pekerjaan tertunda. Backlog di akhir dokumen bukan izin otomatis untuk mengerjakannya.
- Jika hanya menerima indeks tanpa source code, jelaskan keterbatasan tersebut. Indeks tidak menggantikan seluruh kode.
- Ikuti instruksi repository yang berlaku, termasuk `backend/AGENTS.md` ketika bekerja di backend. Jangan menghapus perubahan milik pengguna.
- Jangan membocorkan `.env`, token, data personel nyata, atau dokumen privat. Akun demo bukan akun produksi.
- Jika dokumen dan kode berbeda, kode terkini adalah acuan implementasi; konfirmasi perubahan kebutuhan kepada pengguna.

Semua path di dokumen ini relatif terhadap root repository, sehingga tetap berlaku setelah project dipindahkan atau di-clone di komputer lain.

## 1. Tujuan dan batas aplikasi

Prototype ujian CRUD SI-SDM Polri untuk menyediakan identitas personel, kualifikasi, dan riwayat jabatan yang terintegrasi, mudah dicari, tervalidasi, serta dibatasi hak akses. REST API memungkinkan pengembangan integrasi dengan sistem lain.

Tidak ada skor merit otomatis. Jumlah kegiatan, pengalaman, operasi, dan riwayat lainnya adalah informasi faktual untuk membantu pimpinan menilai, bukan penetapan nilai atau rekomendasi promosi otomatis.

Tambahan prototype: operasi, prestasi, penghargaan, informasi disiplin simulasi, dan integrasi personel simulasi. Belum terhubung ke API SIPP/Propam atau sistem Polri sungguhan.

## 2. Struktur dan teknologi

| Lokasi | Isi |
| --- | --- |
| `backend/` | Laravel 13, PHP 8.3+, Sanctum, REST API dan database |
| `frontend/` | React 19, TypeScript 6, Vite 8; SPA dengan React Router |
| `docs/api/openapi.yaml` | Kontrak API; cocokkan dengan route dan validasi terkini |
| `docs/postman/Merit-System-Polri.postman_collection.json` | Contoh request untuk pengujian manual |
| `docs/postman/Local.postman_environment.json` | Environment Postman lokal |
| `docs/architecture.md` | Diagram arsitektur dan relasi |
| `README.md` | Instalasi, konfigurasi, akun demo, penggunaan dan testing |
| `implementation_plan.md` | Rencana dan keputusan pengembangan |
| `task.md` | Checklist dan catatan pengerjaan/pengujian sebelumnya |
| `compose.yaml` | PostgreSQL lokal saja; bukan container seluruh aplikasi |

Database target PostgreSQL 17. SQLite digunakan untuk pengujian/fallback lokal, bukan bukti bahwa performanya sama dengan PostgreSQL. PostgreSQL menggunakan indeks pencarian termasuk `pg_trgm`.

Alamat lokal: frontend `http://127.0.0.1:5173`, backend `http://127.0.0.1:8000`, API `/api/v1`. Docker database memakai host `127.0.0.1:5433`, diteruskan ke port container 5432.

PDF soal/paparan dan folder alat lokal yang di-ignore tidak harus tersedia pada hasil clone. Instalasi penguji mengacu README, bukan lokasi alat di komputer pengembang.

## 3. Peta fitur frontend

Entry rendering: `frontend/src/main.tsx`. Routing: `frontend/src/App.tsx`. Tipe data: `frontend/src/types.ts`. Layout/navigasi: `frontend/src/components/AppShell.tsx`. Tampilan utama: `frontend/src/App.css` dan `frontend/src/index.css`.

| Fitur / halaman | File utama | Pendukung penting |
| --- | --- | --- |
| Login `/login` | `frontend/src/pages/LoginPage.tsx` | `frontend/src/auth/AuthContext.tsx`, `frontend/src/lib/api.ts` |
| Proteksi halaman dan role | `frontend/src/components/ProtectedRoute.tsx` | `frontend/src/App.tsx`, `frontend/src/auth/useAuth.ts` |
| Dashboard `/dashboard` | `frontend/src/pages/DashboardPage.tsx` | API `/dashboard` |
| Daftar/filter/sorting `/personel` | `frontend/src/pages/PersonnelListPage.tsx` | `frontend/src/lib/personnelSorting.ts` |
| Tambah/edit personel | `frontend/src/pages/PersonnelFormPage.tsx` | `frontend/src/components/RegistrationHistories.tsx`, `frontend/src/lib/registrationDrafts.ts` |
| Profil `/personel/:id` | `frontend/src/pages/PersonnelDetailPage.tsx` | Komponen histori, merit, dan disiplin di bawah |
| Daftar kualifikasi dan timeline jabatan | `frontend/src/components/PersonnelHistory.tsx` | `frontend/src/lib/useHistory.ts` |
| Form kualifikasi | `frontend/src/pages/QualificationFormPage.tsx` | `frontend/src/components/HistoryFields.tsx`, `frontend/src/lib/historyForm.ts` |
| Form riwayat jabatan | `frontend/src/pages/PositionFormPage.tsx` | `frontend/src/components/HistoryFields.tsx`, `frontend/src/lib/historyForm.ts` |
| Ganti jabatan/mutasi | `frontend/src/pages/MutationFormPage.tsx` | API `/ganti-jabatan` dan `/mutasi` |
| Operasi/prestasi/penghargaan | `frontend/src/components/MeritSection.tsx` | `frontend/src/pages/MeritFormPage.tsx`, `frontend/src/lib/meritProfile.ts` |
| Informasi disiplin prototype | `frontend/src/components/DisciplinePrototypeSection.tsx` | API `/disiplin-prototype`; bukan form putusan Propam |
| Pengguna `/pengguna` | `frontend/src/pages/UserListPage.tsx` | `frontend/src/pages/UserFormPage.tsx` |
| Referensi `/referensi` | `frontend/src/pages/ReferencePage.tsx` | API `/references/{type}` |
| Status teknis `/sistem` | `frontend/src/pages/SystemStatusPage.tsx` | Hanya System Admin |
| Integrasi `/integrasi-personel` | `frontend/src/pages/PersonnelIntegrationPage.tsx` | Simulasi preview/apply, bukan API sumber nyata |
| Dropdown pencarian unit/personel | `frontend/src/components/UnitSearchSelect.tsx` | Minimum 3 karakter, debounce, hasil terbatas |

Fungsi yang sering dicari:

- `apiRequest`, `peekApiCache`, `getToken`, `setToken`, `downloadDocument`, `toQueryString`: `frontend/src/lib/api.ts`.
- `personnelSortParams`, `personnelColumns`: `frontend/src/lib/personnelSorting.ts`; mengubah urutan di URL, mempertahankan filter dan kembali ke halaman pertama.
- `useHistory`: `frontend/src/lib/useHistory.ts`; pagination/cache/arah urutan histori.
- `appendRegistrationData`, `initialRegistrationHistories`: `frontend/src/lib/registrationDrafts.ts`; mengemas histori awal dan dokumen ke FormData.
- `historyBody`, `pdfError`, `isActivePrimary`: `frontend/src/lib/historyForm.ts`; payload perubahan, validasi file UI, dan perlindungan jabatan utama.

## 4. Peta backend: controller, service, validasi

Controller API berada di `backend/app/Http/Controllers/Api/V1/`. Berikut pasangan fitur dan logika utamanya; baca service/request juga, bukan controller saja.

| Fitur | Controller | Service / validasi terkait |
| --- | --- | --- |
| Login/me/logout | `backend/app/Http/Controllers/Api/V1/AuthController.php` | `backend/app/Http/Requests/Auth/LoginRequest.php` |
| CRUD/pencarian/arsip personel | `backend/app/Http/Controllers/Api/V1/PersonelController.php` | `backend/app/Http/Requests/Personel/IndexPersonelRequest.php`, `backend/app/Http/Requests/Personel/StorePersonelRequest.php`, `backend/app/Http/Requests/Personel/UpdatePersonelRequest.php` |
| Pendaftaran beserta histori awal | `PersonelController::store` | `backend/app/Services/PersonnelRegistrationService.php`: `create`, transaksi dan pembersihan file jika gagal |
| Cakupan organisasi | Controller yang membaca/mengubah data | `backend/app/Services/OrganizationalScopeService.php`: `accessibleUnitIds`, `canAccessUnit`, `scopePersonelQuery`, `scopeUnitQuery` |
| Kualifikasi | `backend/app/Http/Controllers/Api/V1/KualifikasiPersonelController.php` | `backend/app/Http/Requests/Kualifikasi/StoreKualifikasiRequest.php`, `backend/app/Http/Requests/Kualifikasi/UpdateKualifikasiRequest.php` |
| Riwayat jabatan | `backend/app/Http/Controllers/Api/V1/RiwayatJabatanController.php` | `backend/app/Http/Requests/Jabatan/StoreRiwayatJabatanRequest.php`, `backend/app/Http/Requests/Jabatan/UpdateRiwayatJabatanRequest.php` |
| Ganti jabatan/mutasi | `backend/app/Http/Controllers/Api/V1/MutasiController.php`: `changePosition`, `mutate` | `backend/app/Services/PositionService.php`: `replacePrimary`, `hasPrimaryOverlap`; `backend/app/Http/Requests/Jabatan/GantiJabatanRequest.php`, `backend/app/Http/Requests/Jabatan/MutasiRequest.php` |
| Operasi/prestasi/penghargaan/verifikasi | `backend/app/Http/Controllers/Api/V1/MeritRecordController.php` | `backend/app/Services/MeritProfileService.php`, `backend/app/Http/Requests/Merit/SaveMeritRecordRequest.php` |
| Pengguna dan scope | `backend/app/Http/Controllers/Api/V1/UserController.php` | `backend/app/Services/UserAdministrationService.php`, `backend/app/Policies/UserPolicy.php`, `backend/app/Http/Requests/User/StoreUserRequest.php`, `backend/app/Http/Requests/User/UpdateUserRequest.php` |
| Referensi | `backend/app/Http/Controllers/Api/V1/ReferenceController.php` | `backend/app/Services/ReferenceAdministrationService.php`, `backend/app/Http/Requests/Reference/SaveReferenceRequest.php` |
| Opsi dropdown | `backend/app/Http/Controllers/Api/V1/ReferenceOptionController.php` | Opsi personel: `PersonelController::options` |
| Dashboard | `backend/app/Http/Controllers/Api/V1/DashboardController.php` | Ringkasan sesuai scope |
| Status teknis | `backend/app/Http/Controllers/Api/V1/SystemStatusController.php` | Gate System Admin |
| Disiplin simulasi | `backend/app/Http/Controllers/Api/V1/DisciplineSnapshotController.php` | `backend/app/Models/DisciplineSnapshot.php` |
| Integrasi simulasi | `backend/app/Http/Controllers/Api/V1/PersonnelIntegrationController.php` | `backend/app/Services/PersonnelImportService.php`, `backend/app/Services/PersonnelSource.php`, `backend/app/Services/SimulatedPersonnelSource.php` |

Output JSON diformat oleh resource di `backend/app/Http/Resources/`, termasuk `PersonelResource.php`, `UserResource.php`, `RiwayatJabatanResource.php`, dan `KualifikasiPersonelResource.php`.

## 5. Authentication, authorization, dan aturan bisnis

- Nilai role aktual: `system_admin`, `admin_ssdm`, `operator`, didefinisikan di `backend/app/Enums/UserRole.php`. Operator Polda/Polres/Polsek bukan tiga role berbeda; perbedaannya adalah assignment scope.
- Scope `own_unit` atau `unit_and_descendants` di `backend/app/Enums/ScopeType.php`. Hierarki organisasi dan scope aktif diperiksa service, bukan sekadar menu yang disembunyikan.
- `backend/app/Policies/PersonelPolicy.php`, `backend/app/Policies/UserPolicy.php`, `backend/app/Policies/RiwayatJabatanPolicy.php`, dan `backend/app/Policies/KualifikasiPersonelPolicy.php` membatasi aksi terhadap data.
- Gate, binding adapter integrasi, dan rate limiter: `backend/app/Providers/AppServiceProvider.php`. Pemeriksaan akun aktif: `backend/app/Http/Middleware/EnsureUserIsActive.php`.
- Admin SSDM mengelola akun Operator; System Admin memiliki kewenangan teknis lebih luas. Operator saat ini tidak dapat mengelola pengguna. Batas target akun dan larangan perubahan terhadap akun sendiri juga perlu dilihat di `UserPolicy`.
- Akun pengguna ditautkan ke personel yang sudah ada; nama akun bukan pengganti data identitas personel. Penonaktifan akun mencabut token dan menonaktifkan scope.
- NRP/NIP adalah string angka, agar nol di depan tidak hilang. Backend memvalidasi 6–30 digit dan keunikan; pembatasan ketikan frontend masih backlog.
- Pendidikan umum minimal satu saat pendaftaran; pendidikan Polri minimal satu untuk personel POLRI, opsional bagi PNS. Keduanya dapat ditambahkan berulang (maksimal 20 per jenis) melalui tombol Tambah pendidikan. Jenjang SD–S3 dan jurusan opsional hanya ditampilkan pada pendidikan umum. Histori tambahan dapat diisi awal atau kemudian.
- Status personel (`aktif/nonaktif/pensiun`) berbeda dari status/jenis penugasan jabatan. Badge “AKTIF” bukan nama jabatan dan bukan dasar sorting kolom jabatan.
- Satu jabatan utama aktif, dengan pencegahan periode tumpang tindih. Jabatan utama aktif tidak langsung dihapus/ditutup melalui CRUD histori biasa; gunakan pergantian jabatan/mutasi yang sesuai.
- Mutasi/pergantian menutup jabatan lama, membuat jabatan baru, dan memperbarui unit bila perlu dalam transaksi dengan penguncian data. Histori lama dipertahankan.
- Penghapusan personel adalah arsip/soft delete berizin; pemulihan melalui endpoint restore. Memulihkan personel tidak otomatis mengaktifkan kembali akun yang sebelumnya dinonaktifkan.
- PDF/SK pendukung opsional, maksimum 5 MB, storage privat. Download melalui endpoint berizin, bukan URL publik langsung. Konfigurasi storage: `backend/config/filesystems.php`.
- Pangkat adalah referensi baku, bukan data yang bebas dihapus admin operasional. Pemeliharaan pangkat dibatasi System Admin dan referensi terpakai dilindungi.
- Upload foto belum menjadi fitur aktif. Data disiplin hanya prototype, tidak menghasilkan putusan/sanksi atau skor negatif otomatis.

## 6. Route API

Sumber route: `backend/routes/api.php`. Prefix seluruh route berikut adalah `/api/v1`.

Login `POST /auth/login` tidak memerlukan token dan dibatasi rate. Route lainnya memakai `auth:sanctum`, `active.user`, dan `throttle:api`, lalu policy/gate/scope sesuai aksi. Token dikirim sebagai `Authorization: Bearer ...`.

| Kelompok | Endpoint penting |
| --- | --- |
| Auth | `GET /auth/me`, `POST /auth/logout` |
| Dashboard/teknis | `GET /dashboard`, `GET /system-status` |
| Personel | `GET/POST /personel`, `GET/PUT/DELETE /personel/{id}`, `POST /personel/{id}/restore`, `GET /personel-options` |
| Kualifikasi | `GET/POST /personel/{personel}/kualifikasi`; `GET/PUT/DELETE /kualifikasi/{id}`; `GET /kualifikasi/{id}/dokumen` |
| Jabatan | `GET/POST /personel/{personel}/riwayat-jabatan`; `GET/PUT/DELETE /riwayat-jabatan/{id}`; `GET /riwayat-jabatan/{id}/dokumen` |
| Pergantian/mutasi | `POST /personel/{personel}/ganti-jabatan`, `POST /personel/{personel}/mutasi` |
| Pengguna | `GET/POST /users`, `GET/PUT/DELETE /users/{id}` |
| Referensi | `GET /reference-options`; `GET/POST /references/{type}`; `GET/PUT/DELETE /references/{type}/{id}` |
| Merit tambahan | `GET/POST /personel/{personel}/merit/{type}`; `GET/PUT/DELETE /merit/{type}/{id}`; `POST /merit/{type}/{id}/verifikasi`; `GET /merit/{type}/{id}/dokumen` |
| Disiplin prototype | `GET /personel/{personel}/disiplin-prototype` |
| Integrasi prototype | `GET /personnel-integration`, `POST /personnel-integration/preview`, `GET /personnel-integration/{run}`, `POST /personnel-integration/{run}/apply` |

Jenis referensi: `unit-organisasi`, `pangkat`, `bidang-fungsi`, `jenis-kualifikasi`, `jenis-penugasan`. Jenis merit: `penugasan-operasi`, `prestasi`, `penghargaan`.

Validasi/error JSON dan exception handling: `backend/bootstrap/app.php`. Konfigurasi CORS: `backend/config/cors.php` dan `FRONTEND_ORIGINS` pada environment backend. Sanctum: `backend/config/sanctum.php`.

## 7. Alur data, loading, dan integrasi

### Menampilkan daftar personel

1. `PersonnelListPage` membaca filter/sort/page dari URL dan meminta API melalui `apiRequest`.
2. Token disertakan. Middleware memeriksa authentication dan akun aktif.
3. `IndexPersonelRequest` memvalidasi parameter. `PersonelController::index` membatasi scope, memfilter, mengurutkan, lalu melakukan pagination di database.
4. Resource mengirim data halaman beserta metadata pagination; frontend merender tabel, bukan mengunduh seluruh personel Indonesia.

Frontend saat ini meminta 10 personel per halaman. Default backend jika parameter tidak dikirim masih 15, maksimum parameter 100. Itu tidak berarti ada lima personel hilang dari halaman UI: request UI memang meminta 10.

Sorting berlaku pada seluruh hasil di server, bukan hanya sepuluh baris yang terlihat. Nama memakai perbandingan tanpa membedakan huruf besar/kecil. Sorting jabatan memakai nama jabatan, bukan badge status. Timeline jabatan mempunyai arah tanggal sendiri, default terbaru dahulu.

Cache `api.ts` maksimum 50 entri, masa segar 60 detik. `peekApiCache` memungkinkan data tersimpan langsung ditampilkan sambil request baru berjalan. Ini bukan jaminan tidak ada request/network atau loading pada semua keadaan. Mutasi, pergantian token, dan error akses menginvalidasi cache agar data/hak akses lama tidak bocor. Cache epoch mencegah response lama mengisi ulang cache setelah invalidasi.

Pencarian dropdown unit/personel menggunakan debounce dan hasil terbatas. Jangan menganggap endpoint opsi default selalu bebas dari pengambilan semua opsi: `ReferenceOptionController` mempunyai beberapa mode `only` dan jalur fallback.

Indeks pencarian/urutan ada di `backend/database/migrations/2026_10_01_155257_add_personel_search_indexes.php`, `backend/database/migrations/2026_10_01_160548_add_unit_option_search_indexes.php`, dan `backend/database/migrations/2026_10_02_040752_add_case_insensitive_personnel_name_sort_index.php`. Tidak ada klaim benchmark nasional; performa skala besar tetap perlu diukur pada deployment nyata.

### Integrasi personel simulasi dan delta sync

`PersonnelSource` adalah interface sumber; `SimulatedPersonnelSource` menyediakan data uji. Binding di `AppServiceProvider` dapat diganti adapter API nyata setelah kontrak API, authentication, mapping, dan aturan konflik disepakati.

`PersonnelImportService` menyediakan preview/staging, apply bertahap, tautan ID sumber ke personel lokal, fingerprint, deteksi konflik, dan checkpoint versi. Simulasi mempunyai versi data dan mode delta; ini bukan sinkronisasi otomatis terjadwal dengan server Polri.

Field sumber yang dikelola saat ini terbatas pada nama lengkap, tempat/tanggal lahir, dan pangkat. Integrasi tidak sembarangan menimpa pendidikan, penempatan, dan histori jabatan lokal. Tombstone sumber dilewati: personel lokal tidak otomatis dihapus/diarsipkan. Checkpoint tidak dimajukan jika proses masih memiliki error/konflik.

Write simulasi dibatasi environment lokal/testing oleh controller. Kesiapan pengembangan bukan berarti sudah siap digunakan sebagai integrasi produksi tanpa perubahan.

## 8. Data, model, dan migration

Model domain berada di `backend/app/Models/`:

- `Personel.php`, `RiwayatJabatan.php`, `KualifikasiPersonel.php`: identitas dan histori; relasi personel ke banyak histori.
- `User.php`, `UserScope.php`, `UnitOrganisasi.php`: akun, assignment scope, hierarki unit.
- `Pangkat.php`, `BidangFungsi.php`, `JenisKualifikasi.php`, `JenisPenugasan.php`: referensi.
- `PenugasanOperasi.php`, `PrestasiPersonel.php`, `PenghargaanPersonel.php`, `MeritRecord.php`: merit tambahan.
- `DisciplineSnapshot.php`: snapshot disiplin simulasi.

Lihat migration untuk nama tabel/constraint sebenarnya; jangan menebak dari plural bahasa Inggris. Contohnya migration `backend/database/migrations/2026_10_01_032115_create_riwayat_jabatans_table.php` membuat tabel `riwayat_jabatan`, termasuk unique index parsial untuk jabatan utama aktif.

Migration tambahan penting:

- `backend/database/migrations/2026_10_01_133640_create_merit_profile_tables.php`: operasi, prestasi, penghargaan.
- `backend/database/migrations/2026_10_02_023619_add_personnel_account_and_archive_metadata.php`: tautan akun/personel dan metadata arsip.
- `backend/database/migrations/2026_10_02_071651_create_discipline_snapshots_table.php`: disiplin prototype.
- `backend/database/migrations/2026_10_02_074018_create_personnel_integration_tables.php`: `personnel_sync_checkpoints`, `personnel_import_runs`, `personnel_import_items`, `personnel_source_links`.

Integrasi memakai query database langsung untuk tabel staging tersebut; jangan mencari model `PersonnelImportRun` yang belum dibuat.

## 9. Seed, instalasi, dan pengujian

Seed dasar: `backend/database/seeders/DatabaseSeeder.php`. Referensi pangkat: `backend/database/seeders/PangkatSeeder.php` (termasuk Tamtama). Akun/scope: `backend/database/seeders/UserScopeSeeder.php`.

Tambahan data demo: `backend/database/seeders/DemoPersonnelSeeder.php` (100 personel sintetis), `backend/database/seeders/PolsekDemoSeeder.php`, dan `backend/database/seeders/DisciplinePrototypeSeeder.php`. Baca batas environment dan perlindungan data sebelum menjalankan seeder; jangan menganggap setiap seeder tambahan dijalankan otomatis oleh seed dasar.

Akun demo mencakup System Admin, Admin SSDM, Operator Polda, Polres, Intelkam, dan Polsek. Kredensial serta cakupan tepat lihat README/seeder, bukan gunakan akun demo untuk produksi.

Instalasi lengkap ada di README. Ringkasnya: PostgreSQL aktif; backend `composer install`, salin `.env.example` ke `.env`, konfigurasi environment, generate key, migrasi/seed instalasi baru, jalankan Laravel; frontend `npm ci`, salin environment, jalankan Vite. Environment contoh: `backend/.env.example` dan `frontend/.env.example`.

Jangan menjalankan `migrate:fresh` pada database berisi data pengguna. Upgrade normal memakai migration yang belum dijalankan; clean install/test harus memakai database terpisah. `compose.yaml` tidak wajib jika PostgreSQL sudah tersedia tanpa Docker.

Perintah pengujian (dari folder masing-masing, sesuaikan runtime lokal):

```powershell
# Dalam backend
php artisan test
php artisan route:list --path=api/v1

# Dalam frontend
npm test
npm run lint
npm run build
```

`backend/phpunit.xml` mengatur database test SQLite in-memory. Pengujian PostgreSQL harus sengaja dikonfigurasi ke database test terpisah, bukan database pengembangan. Dokumen ini tidak menyatakan test di atas baru saja dijalankan.

| Area yang ingin dicek | Test rujukan |
| --- | --- |
| Auth dan scope | `backend/tests/Feature/AuthApiTest.php`, `backend/tests/Feature/AuthorizationScopeTest.php` |
| Akun/policy | `backend/tests/Feature/UserApiTest.php`, `backend/tests/Unit/Policies/UserPolicyTest.php` |
| Personel/pendaftaran/arsip | `backend/tests/Feature/PersonelApiTest.php`, `backend/tests/Feature/RegistrationAndArchiveApiTest.php` |
| Konsistensi/mutasi/jabatan | `backend/tests/Feature/DomainDataIntegrityTest.php`, `backend/tests/Feature/MutasiApiTest.php`, `backend/tests/Feature/RiwayatJabatanApiTest.php` |
| Kualifikasi/merit | `backend/tests/Feature/KualifikasiPersonelApiTest.php`, `backend/tests/Feature/MeritProfileApiTest.php` |
| Sorting/referensi | `backend/tests/Feature/PersonnelSortingTest.php`, `backend/tests/Feature/ReferenceAdministrationApiTest.php`, `backend/tests/Feature/RankReferenceTest.php` |
| Integrasi/disiplin | `backend/tests/Feature/PersonnelIntegrationTest.php`, `backend/tests/Feature/DisciplinePrototypeTest.php` |
| Cache dan payload UI | `frontend/src/lib/api.test.mjs`, `frontend/src/lib/historyForm.test.mjs`, `frontend/src/lib/registrationDrafts.test.mjs` |
| Sorting UI | `frontend/src/lib/personnelSorting.test.mjs` |

## 10. Empat perbaikan berikutnya dan pembaruan pendidikan

Daftar ini merupakan kebutuhan berikutnya, bukan fitur yang sudah selesai dan bukan instruksi untuk langsung mengubah aplikasi.

| Permintaan | Titik kode awal | Catatan rancangan |
| --- | --- | --- |
| Delegasi pendaftaran/pengelolaan Operator daerah | `UserPolicy.php`, `UserAdministrationService.php`, request pengguna, `UserFormPage.tsx`, route guard | Tentukan permission bagi petugas SDM yang berwenang; hanya unit bawahan sesuai scope. Jangan otomatis memberi semua Operator hak membuat akun atau meningkatkan role. |
| Samakan default API 15 menjadi 10 | `PersonelController::index`, `IndexPersonelRequest.php`, dokumentasi API | UI sudah meminta 10. Selaraskan default, kontrak/contoh, dan test tanpa menghilangkan pagination. |
| NRP/NIP angka saja saat mengetik/paste | `PersonnelFormPage.tsx` | Pertahankan string/nol depan dan validasi backend. Jangan hanya mengandalkan `type=number`. |
| Filter Admin SSDM berdasarkan Polda/Polres/Polsek | `PersonnelListPage.tsx`, `IndexPersonelRequest.php`, `PersonelController::index`, `OrganizationalScopeService.php` | API saat ini memiliki `unit_organisasi_id` untuk unit persis, belum pilihan Polda beserta turunannya. Gabungkan filter baru dengan scope keamanan, pencarian, sorting, dan pagination. |

Path pendek pada tabel backlog mengacu pada lokasi lengkap di bagian 3–5. Sebelum implementasi, baca kode terkini dan sepakati aturan delegasi/scope yang belum diputuskan.

Pembaruan pendidikan sudah dikerjakan sesuai permintaan terbaru: judul “Pendidikan Umum” dan “Pendidikan Polri”, tanpa kata “wajib”, dengan beberapa catatan saat pendaftaran. Jalur kode: `RegistrationHistories.tsx` → `registrationDrafts.ts` → `PersonnelFormPage.tsx` → `StorePersonelRequest.php` → `PersonnelRegistrationService.php`. Payload pendidikan berbentuk array; objek tunggal API/integrasi lama tetap diterima. Skema database tidak berubah. Tes: `registrationDrafts.test.mjs` dan `RegistrationAndArchiveApiTest.php`.

## 11. Contoh pertanyaan dan jalur membaca

- “Kenapa halaman sebelumnya masih loading?” → `api.ts`, pemakaian `peekApiCache` pada halaman terkait, `useHistory.ts`, dan auth/token. Periksa cache hit, invalidasi, dan request nyata sebelum menyalahkan server.
- “Di mana urutan abjad atau tombol panah?” → `PersonnelListPage.tsx` → `personnelSorting.ts` → `IndexPersonelRequest.php` → `PersonelController::index` → test sorting.
- “Siapa boleh melihat/mengubah personel?” → `PersonelPolicy.php` → `OrganizationalScopeService.php` → assignment `UserScope` → controller terkait.
- “Kenapa Operator Polda belum bisa buat Operator Polres?” → `UserPolicy.php`, gate/permission, route guard pengguna; lihat backlog delegasi.
- “Bagaimana mutasi lintas Polres?” → `MutationFormPage.tsx` → `MutasiRequest.php` → `MutasiController::mutate` → `PositionService::replacePrimary`; periksa kewenangan asal dan tujuan.
- “Bagaimana menambah kolom profil?” → model/migration, request, service bila ada, resource, tipe frontend, form/detail, kontrak API, dan test terkait. Tidak cukup hanya menambah input UI.
- “Integrasi hanya tampilan?” → `PersonnelIntegrationController.php`, `PersonnelImportService.php`, interface/adapter sumber dan migration staging; fondasi backend ada, sumber nyata belum terhubung.
- “Apakah disiplin sudah dari Propam?” → `DisciplineSnapshotController.php`, `DisciplineSnapshot.php`, seeder prototype; jawab belum, data simulasi.

Untuk pencarian kode gunakan `rg` dari root, misalnya `rg -n 'replacePrimary|scopePersonelQuery' backend/app` atau `rg -n 'peekApiCache|per_page' frontend/src`. Sesuaikan indeks ini setiap kali struktur, fitur, atau backlog berubah.
