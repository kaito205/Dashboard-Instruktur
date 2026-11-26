# Sistem Monitoring & Evaluasi Kinerja Instruktur LPK CTI

Aplikasi web berbasis Laravel 12 untuk pemantauan jadwal mengajar, presensi berbasis GPS, laporan aktivitas mengajar, evaluasi peserta pelatihan, observasi pengajaran, dan perhitungan skor kinerja instruktur pada **LPK Cipta Tungga Indonesia Ciamis**.

---

## Peran Pengguna

1. **Administrator (`admin`):** Mengelola akun pengguna, data instruktur beserta sertifikasi dan riwayat cuti/izin, master kelas, jadwal mengajar (spesifik maupun pola mingguan), pengalihan jadwal, kehadiran manual, import data CSV/Excel, kode evaluasi, parameter bobot kinerja, dan laporan.
2. **Supervisor (`supervisor`):** Memantau jadwal dan kehadiran instruktur, memverifikasi jurnal aktivitas mengajar beserta bukti dokumentasi, mengisi penilaian observasi pengajaran, meninjau hasil evaluasi peserta, serta mencetak/mengekspor laporan kinerja.
3. **Instruktur (`instruktur`):** Melihat jadwal pribadi, melakukan check-in dan check-out kehadiran berbasis lokasi, melaporkan realisasi aktivitas mengajar, melihat umpan balik peserta, serta memantau rapor kinerja pribadi.
4. **Peserta Pelatihan (Publik):** Mengisi kuesioner evaluasi pengajaran melalui halaman `/evaluasi` menggunakan kode akses kelas aktif tanpa perlu akun login.

---

## Persyaratan Sistem

- **PHP:** `>= 8.2`
- **Ekstensi PHP Wajib:** `bcmath`, `ctype`, `dom`, `fileinfo`, `gd`, `iconv`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql` (atau driver DB yang dipilih), `simplexml`, `tokenizer`, `xml`, `zip`
- **Framework:** Laravel `^12.0`
- **Composer:** `2.x`
- **Node.js & npm:** Node.js `>= 18` dan npm `>= 9` (untuk kompilasi aset Vite + Tailwind CSS v4)
- **Database:** MySQL `8.0+` / MariaDB `10.6+` (direkomendasikan untuk produksi) atau PostgreSQL / SQLite
- **Konfigurasi Upload PHP (`php.ini`):** Disarankan `upload_max_filesize = 16M` dan `post_max_size = 20M` (batas unggah import spreadsheet `10 MB`, dokumen sertifikat & dokumentasi mengajar `5 MB`)

---

## Instalasi Lokal

1. **Pasang dependensi PHP dan Node.js:**
   ```bash
   composer install
   npm install
   ```

2. **Salin berkas konfigurasi lingkungan dan buat kunci enkripsi aplikasi:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Sesuaikan konfigurasi database di `.env`:**
   ```dotenv
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_database_anda
   DB_USERNAME=pengguna_db
   DB_PASSWORD=rahasia_db
   ```

4. **Jalankan migrasi database:**
   ```bash
   php artisan migrate
   ```

5. **Siapkan tautan penyimpanan publik & kompilasi aset antarmuka:**
   ```bash
   php artisan storage:link
   npm run build
   ```

---

## Inisialisasi Akun Awal & Seeder

Sistem memisahkan seeder pengaturan produksi dari data contoh agar data sampel tidak masuk ke server produksi secara tidak sengaja.

### 1. Seeder Produksi (`AdminSeeder`)
Membuat parameter pengaturan awal LPK serta akun awal `admin@lpkcti.id` dan `supervisor@lpkcti.id` **hanya jika belum tersedia** di database.

- Atur password awal terlebih dahulu pada `.env` sebelum menjalankan seeder (jangan gunakan password umum):
  ```dotenv
  ADMIN_INITIAL_PASSWORD="PasswordKuatUnikAdmin1!"
  SUPERVISOR_INITIAL_PASSWORD="PasswordKuatUnikSpv1!"
  ```
- Apabila variabel tersebut dikosongkan, `AdminSeeder` **tidak** memakai password statis publik maupun password kosong, melainkan menghasilkan password acak satu kali dan menampilkannya di terminal saat eksekusi pertama.
- Aman dijalankan ulang (*idempotent*): jika akun sudah ada, `AdminSeeder` tidak mengubah `password`, `name`, `role`, maupun status `is_active`. Pengaturan disimpan melalui `Setting::setIfMissing()` sehingga bobot KPI atau konfigurasi LPK yang sudah diubah Admin tidak tertimpa saat re-seed.
- Jalankan perintah:
  ```bash
  php artisan db:seed
  ```
- Segera ganti password melalui menu **Pengaturan / Profil & Keamanan** setelah login pertama, lalu hapus nilai `*_INITIAL_PASSWORD` dari `.env`.

### 2. Seeder Data Contoh (Opsional, Khusus Lingkungan Pengembangan)
Untuk memuat data contoh instruktur, jadwal pola mingguan, dan kode evaluasi kelas di lingkungan lokal/pengujian:
```bash
php artisan db:seed --class=LpkCtiJadwalMingguanSeeder
```

---

## Penyimpanan Berkas (Storage)

- **Disk `public` (`storage/app/public`):** Menyimpan foto profil pengguna dan instruktur. Dilayani melalui symbolic link `public/storage` serta dilengkapi rute fallback `/storage/{path}` apabila fungsi `symlink` dibatasi pada shared hosting.
- **Disk `local` (`storage/app/private`):** Menyimpan dokumen internal sensitif (`instruktur/sertifikasi`, `aktivitas/dokumentasi`, dan berkas sementara `import_previews`). Berkas ini diblokir dari akses URL publik langsung (`403`) dan hanya dapat diakses melalui rute terautentikasi sesuai hak akses role.

Pastikan direktori `storage/` dan `bootstrap/cache/` memiliki izin tulis pada server Linux:
```bash
chmod -R 775 storage bootstrap/cache
```

---

## Penjadwalan Tugas (Cron / Scheduler)

Aplikasi memiliki dua tugas terjadwal di `routes/console.php`:
1. `absensi:auto-checkout` (setiap 15 menit): Menutup otomatis sesi presensi yang belum check-out melewati batas toleransi setelah jam selesai kelas.
2. `import:cleanup-previews` (setiap jam): Menghapus berkas JSON pratinjau import yang telah kedaluwarsa (`> 60 menit`) selama tidak sedang dikunci oleh proses import aktif.

Tambahkan entri Cron berikut pada server hosting:
```cron
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

---

## Panduan Deployment Produksi

1. Pastikan `.env` produksi menggunakan konfigurasi aman:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-resmi-anda.id
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   SESSION_SECURE_COOKIE=true
   ```
   Jika aplikasi berada di balik reverse proxy (seperti Nginx atau Cloudflare), isi `TRUSTED_PROXIES` dengan daftar IP/CIDR proxy tepercaya agar deteksi skema HTTPS dan batas percobaan login (`RateLimiter`) membaca IP klien yang sebenarnya.

2. Kompilasi aset produksi dan pasang dependensi PHP tanpa paket dev:
   ```bash
   npm ci && npm run build
   composer install --no-dev --optimize-autoloader
   ```

3. Jalankan migrasi secara aman (tanpa menghapus data) dan aktifkan cache optimasi Laravel:
   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

> **Peringatan Keamanan Data:** Jangan pernah menjalankan `php artisan migrate:fresh` atau `php artisan db:wipe` pada lingkungan staging maupun produksi.

---

## Skenario Integrasi Database & Strategi Pemaketan Rilis

### 1. Evaluasi Integrasi Database (Terpisah vs Bersama)
Panduan lengkap instalasi database baru (Skenario A: Database Terpisah) maupun penggabungan ke database sistem eksisting beserta analisis benturan tabel, dampak migration historis, strategi prefix/isolasi, dan prosedur backup-restore (Skenario B: Database Bersama) didokumentasikan secara terpisah pada berkas `DOKUMENTASI_INTEGRASI_DATABASE.md`.

### 2. Strategi Pemaketan Distribusi Final
Karena direktori `/public/build` dikecualikan dari pelacakan Git (`.gitignore`), perintah `git archive` saja tidak menyertakan hasil kompilasi aset antarmuka. Saat membuat paket distribusi `.zip` final setelah seluruh perubahan di-commit:
1. Jalankan `npm run build` dan pastikan `public/build/manifest.json` beserta `public/build/assets/*` mutakhir.
2. Untuk paket dengan dependensi produksi lengkap (shared hosting tanpa Composer), jalankan `composer install --no-dev --optimize-autoloader` terlebih dahulu.
3. Rakit paket dari working tree bersih (atau hasil `git archive` setelah commit terbaru **ditambah** penyalinan folder `public/build/`) dengan mengecualikan `.git/`, `.agents/`, `.claude/`, `node_modules/`, `tests/`, `.env*` (kecuali `.env.example`), `AGENTS.md`, `CLAUDE.md`, `AUDIT_PROGRESS.md`, `phpunit.xml`, `database/*.sqlite`, serta isi berkas lokal di `storage/logs/*`, `storage/framework/{cache,sessions,testing,views}/*`, `storage/app/private/*`, dan `storage/app/public/*`.

---

## Pengujian Otomatis

Seluruh pengujian menggunakan database SQLite `:memory:` terisolasi (dikonfigurasi pada `phpunit.xml`) sehingga aman dijalankan kapan saja tanpa mengubah database lokal:

```bash
php artisan test
```
