# Panduan Evaluasi & Skenario Integrasi Database
## Sistem Monitoring & Evaluasi Kinerja Instruktur LPK CTI

Dokumen ini menyediakan panduan teknis untuk dua skenario penerapan database tanpa mengasumsikan arsitektur target terlebih dahulu:
- **Skenario A:** Database Terpisah (*Dedicated Database*)
- **Skenario B:** Database Bersama (*Shared Database* dengan sistem informasi LPK lainnya)

> **Catatan Penting:** Jangan mengubah file migration historis sebelum arsitektur target (Skenario A atau Skenario B) diputuskan secara resmi oleh tim pengelola sistem.

---

## Skenario A: Database Terpisah (Dedicated Database)

Pada skenario ini, Sistem Monitoring Instruktur memiliki skema database MySQL/MariaDB sendiri yang terpisah dari database aplikasi lain (walaupun berada di server database yang sama).

### 1. Prosedur Instalasi Database Baru
1. Buat database kosong dengan karakter set `utf8mb4` dan buat akun database khusus aplikasi (hindari penggunaan akun `root` di produksi):
   ```sql
   CREATE DATABASE dashboard_instruktur
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;

   CREATE USER 'cti_mon_user'@'localhost' IDENTIFIED BY 'GantiDenganPasswordKuat!';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
     ON dashboard_instruktur.* TO 'cti_mon_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
2. Salin `.env.example` menjadi `.env`, lalu jalankan `php artisan key:generate`.

### 2. Konfigurasi Koneksi
Atur variabel koneksi pada berkas `.env`:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dashboard_instruktur
DB_USERNAME=cti_mon_user
DB_PASSWORD="GantiDenganPasswordKuat!"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```
*Catatan integrasi antar-aplikasi:* Apabila di kemudian hari aplikasi utama LPK perlu membaca rekap skor kinerja dari sistem ini, sistem utama cukup menambahkan koneksi kedua (misalnya `'monitoring_cti'`) pada `config/database.php` miliknya dengan hak akses *read-only* (`SELECT`) tanpa perlu menggabungkan tabel.

### 3. Urutan Migration
Seluruh **28 file migration** dirancang berurutan dan dijalankan otomatis dengan satu perintah:
```bash
php artisan migrate --force
```
Urutan eksekusi skema yang terbentuk:
1. `0001_01_01_000000` s.d. `0001_01_01_000002`: Tabel dasar framework (`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`).
2. `2026_11_05_000001` s.d. `2026_11_05_000009`: Kolom peran `users` (`role`, `is_active`, `last_login_at`) serta tabel inti domain (`instrukturs`, `jadwals`, `absensis`, `kode_evaluasis`, `evaluasis`, `import_histories`, `settings`, `aktivitas_mengajars`).
3. `2026_11_08_000010` s.d. `2026_11_21_000019`: Perluasan fitur LPK CTI (`observasi_supervisors`, `instruktur_audit_logs`, `foto_profil` pada `users`, `riwayat_pengalihan` pada `jadwals`, `notifikasi_tinjauans`, penyesuaian FK `instrukturs.user_id` menjadi `ON DELETE SET NULL`, `instruktur_sertifikasis`, dan master `kelas`).
4. `2026_11_22_000020` s.d. `2026_11_25_000023`: Normalisasi konsistensi data historis dan penambahan 5 indeks performa operasional.
5. `2026_11_26_160000` s.d. `2026_11_26_160100`: Tabel `instruktur_cutis` dan kolom `batch_import` pada `absensis`.

### 4. Prosedur Seeding
1. Isi password awal pada `.env` (opsional; jika dikosongkan, seeder membuat password acak 16 karakter satu kali di terminal):
   ```dotenv
   ADMIN_INITIAL_PASSWORD="PasswordUnikAdmin#2026"
   SUPERVISOR_INITIAL_PASSWORD="PasswordUnikSpv#2026"
   ```
2. Jalankan seeder produksi:
   ```bash
   php artisan db:seed --force
   ```
3. Perilaku keamanan `AdminSeeder`:
   - Hanya membuat akun `admin@lpkcti.id` dan `supervisor@lpkcti.id` jika email tersebut belum terdaftar.
   - Tidak menimpa `password`, `name`, `role`, maupun `is_active` pada akun yang sudah ada.
   - Menggunakan `Setting::setIfMissing()` sehingga parameter yang sudah dikustomisasi Admin (seperti bobot KPI dan radius presensi) tidak berubah jika `db:seed` dijalankan ulang.
   - Tidak memuat data contoh (`LpkCtiJadwalMingguanSeeder` terpisah dan hanya dijalankan manual di lingkungan pengembangan).

---

## Skenario B: Database Bersama (Shared Database)

Pada skenario ini, tabel Sistem Monitoring Instruktur ditempatkan di dalam satu skema database yang sudah berisi tabel milik aplikasi lain (misalnya SIAKAD LPK, portal pendaftaran peserta, atau sistem kepegawaian).

### 1. Daftar Potensi Benturan Tabel dan Kolom
Sistem ini menggunakan **19 tabel aplikasi** ditambah **1 tabel pelacak `migrations`**:

| Kategori | Nama Tabel | Tingkat Risiko Benturan | Detail Potensi Benturan |
|---|---|---|---|
| Autentikasi & Umum | `users` | **Tinggi** | Migration `000000` menggunakan `Schema::create('users')` tanpa `hasTable` (akan gagal jika `users` sudah ada). Migration `000001` menambahkan `ENUM('admin','supervisor','instruktur')`, `is_active`, dan `last_login_at`. Jika sistem lain memakai nilai `role` lain (mis. `peserta`, `staf`, `pimpinan`) atau relasi tabel role terpisah, tipe `ENUM` 3 nilai akan menolak data role sistem lain. |
| Konfigurasi | `settings` | **Tinggi** | Struktur `(id, key UNIQUE, value, group, timestamps)` sering bentrok dengan tabel `settings` milik CMS/aplikasi lain yang memakai nama kolom berbeda (mis. `name`/`val` atau tanpa kolom `group`). |
| Akademik / Pelatihan | `kelas`, `jadwals`, `absensis` | **Tinggi** | Sistem akademik/pelatihan LPK umumnya sudah memiliki tabel `kelas`, `jadwals`, atau `absensis` (untuk peserta pelatihan) dengan struktur kolom dan foreign key yang berbeda. |
| Infrastruktur Laravel | `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | **Sedang - Tinggi** | Berbagi tabel `migrations` antar dua repositori Laravel membuat nomor `batch` tercampur sehingga `migrate:rollback` di satu aplikasi dapat membatalkan migration aplikasi lain. Berbagi tabel `cache` tanpa `CACHE_PREFIX` unik dapat memicu tabrakan kunci cache/lock. |
| Spesifik Monitoring Instruktur | `instrukturs`, `instruktur_sertifikasis`, `instruktur_cutis`, `instruktur_audit_logs`, `aktivitas_mengajars`, `kode_evaluasis`, `evaluasis`, `observasi_supervisors`, `import_histories`, `notifikasi_tinjauans` | **Rendah - Sedang** | Nama tabel spesifik untuk modul monitoring instruktur, namun tetap perlu diperiksa terhadap daftar tabel eksisting di database target sebelum migrasi dijalankan. |

### 2. Dampak Migration `000013` dan `000020` (Serta Migration Pembersih Lainnya)
Apabila migration dijalankan langsung tanpa isolasi di atas database bersama yang sudah berisi data operasional:
1. **`2026_11_08_000013_sync_users_and_instrukturs_cascade.php`:**
   - Menghapus permanen baris pada `instrukturs` (beserta data terkait di `jadwals`, `absensis`, `evaluasis`, `kode_evaluasis`, `aktivitas_mengajars`, `instruktur_audit_logs`) apabila memiliki `kode_instruktur IN ('INS-0001', 'INS-0002')`, `email IN ('ahmad.fauzi@lpkcti.id', 'siti.nurhaliza@lpkcti.id')`, **`user_id IS NULL`**, atau `user_id` yang tidak ditemukan di `users`.
   - Menghapus baris `users` dengan email `ahmad.fauzi@lpkcti.id`, `siti.nurhaliza@lpkcti.id`, dan `test@example.com`.
   - Menjalankan `$table->dropForeign(['user_id'])` yang mengasumsikan nama constraint MySQL adalah `instrukturs_user_id_foreign` (akan gagal dengan error `1091` bila tabel `instrukturs` eksisting memakai nama constraint berbeda), kemudian disusul `2026_11_20_000017` yang mengubahnya kembali ke `ON DELETE SET NULL`.
2. **`2026_11_22_000020_cleanup_aktivitas_mengajars_duplicates_and_test_data.php`:**
   - Mengelompokkan isi `aktivitas_mengajars` berdasarkan `(instruktur_id, jadwal_id, tanggal)` dan menghapus semua baris selain `MAX(id)`. Jika sudah ada data aktivitas non-jadwal (`jadwal_id IS NULL`) lebih dari satu sesi pada hari yang sama, baris lama akan terhapus.
   - Menimpa kolom `kendala_kegiatan` yang bernilai `'tes'`, `'test'`, `'testing'`, `'tes data'`, `'test data'`.
3. **Catatan tambahan `000021` & `000022`:** Menghapus baris `absensis` dan `aktivitas_mengajars` (`jadwal_id IS NOT NULL`) apabila tanggal pencatatannya tidak sesuai dengan hari/tanggal `jadwals` terkait.

### 3. Strategi Isolasi Tabel dan Identitas Pengguna
Terdapat dua arsitektur aman apabila database harus digunakan bersama:

#### Opsi B1: Isolasi Penuh via Prefix Tabel (`DB_PREFIX`) (Paling Aman Tanpa Mengubah Migration)
Tambahkan prefix khusus modul (misalnya `mon_`) pada konfigurasi koneksi MySQL di `config/database.php` (`'prefix' => env('DB_PREFIX', '')`) serta pisahkan prefix cache dan cookie sesi di `.env`:
```dotenv
DB_PREFIX=mon_
CACHE_PREFIX=mon_cti_
SESSION_COOKIE=lpkcti_monitoring_session
```
Dengan `DB_PREFIX=mon_`, seluruh tabel otomatis terisolasi menjadi `mon_migrations`, `mon_users`, `mon_kelas`, `mon_jadwals`, `mon_absensis`, `mon_settings`, dan seterusnya sehingga **nol benturan** dengan tabel sistem lain di dalam skema database yang sama.

#### Opsi B2: Berbagi Tabel `users` (Single Identity), Prefix untuk Tabel Domain
Jika manajemen akun login wajib disatukan pada satu tabel `users` bersama:
1. Gunakan `DB_PREFIX=mon_` untuk seluruh tabel domain monitoring, lalu tetapkan `protected $table = 'users';` tanpa prefix (atau koneksi khusus) pada model `User` setelah skema `users` diselaraskan.
2. Samakan `APP_KEY` dan algoritma hashing (`BCRYPT_ROUNDS`) hanya jika kedua aplikasi memang dirancang berbagi enkripsi/sesi; jika tidak, gunakan cookie sesi terpisah (`SESSION_COOKIE` berbeda) agar sesi kedua aplikasi tidak saling menimpa.

### 4. Kebutuhan Penyesuaian Foreign Key dan Role (Jika Memilih Opsi B2)
Sebelum menghubungkan tabel domain ke tabel `users` eksisting milik sistem lain, periksa empat hal berikut di lingkungan staging:
1. **Kesesuaian Tipe Primary Key `users.id`:** Seluruh 8 foreign key pengguna di sistem ini (`instrukturs.user_id`, `instruktur_audit_logs.actor_user_id`, `observasi_supervisors.supervisor_id`, `aktivitas_mengajars.verified_by`, `import_histories.user_id`, `notifikasi_tinjauans.user_id`, `instruktur_cutis.disetujui_oleh`, `sessions.user_id`) menggunakan `BIGINT UNSIGNED`. Jika `users.id` sistem eksisting bertipe `INT UNSIGNED` atau `CHAR(36)` (UUID), definisi kolom FK harus disamakan terlebih dahulu agar MySQL tidak menolak pembuatan constraint (`errno: 150`).
2. **Engine & Charset Tabel:** Pastikan tabel `users` eksisting memakai engine `InnoDB` dan charset/collation yang kompatibel (`utf8mb4`).
3. **Skema Kolom `role` dan `is_active`:** Ubah penambahan kolom `role` agar bertipe `VARCHAR(50)` (bukan `ENUM` terbatas 3 nilai) atau tambahkan kolom khusus `monitoring_role VARCHAR(30) NULL` supaya pengguna dari sistem lain (misalnya `peserta` atau `keuangan`) tetap valid di tabel `users`, namun hanya akun ber-role `admin`, `supervisor`, atau `instruktur` yang diizinkan masuk ke rute monitoring oleh middleware `CheckRole`.
4. **Kebijakan Penghapusan Akun (`ON DELETE`):** Pertahankan kebijakan akhir `ON DELETE SET NULL` pada `instrukturs.user_id` (sesuai migration `000017`) agar penghapusan atau penonaktifan akun di sistem induk tidak menghapus histori jadwal, presensi, dan nilai kinerja instruktur.

### 5. Prosedur Backup dan Pemulihan Wajib Sebelum Integrasi
1. **Aktifkan mode pemeliharaan** pada seluruh aplikasi yang mengakses database bersama (`php artisan down`).
2. **Buat cadangan penuh konsisten** beserta struktur, data, trigger, dan routine:
   ```bash
   mysqldump -u USER -p --single-transaction --routines --triggers --events NAMA_DB > backup_sebelum_integrasi_$(date +%Y%m%d_%H%M%S).sql
   ```
3. **Verifikasi integritas file backup:** Uji impor berkas `.sql` tersebut ke database staging lokal dan jalankan `php artisan migrate --pretend` untuk meninjau setiap query SQL sebelum menyentuh database sebenarnya.
4. **Catat jumlah baris referensi pra-migrasi** (`SELECT COUNT(*)` pada `users`, `kelas`, `jadwals`, `absensis`, `settings`) untuk diaudit sesudah migrasi selesai.
5. **Prosedur pemulihan (*restore*) darurat:** Apabila terjadi kegagalan DDL di tengah jalan pada MySQL (mengingat `ALTER TABLE` bersifat *implicit commit* dan tidak dapat dibatalkan oleh transaksi biasa), jangan memaksakan `migrate:rollback` parsial; pulihkan langsung dari cadangan penuh:
   ```bash
   mysql -u USER -p NAMA_DB < backup_sebelum_integrasi_YYYYMMDD_HHMMSS.sql
   ```
