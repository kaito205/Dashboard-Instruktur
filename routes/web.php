<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvaluasiPublikController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\InstrukturController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KehadiranController as AdminKehadiranController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\EvaluasiController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\ObservasiController as AdminObservasiController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\NotifikasiController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\InstrukturController as SupervisorInstrukturController;
use App\Http\Controllers\Supervisor\JadwalController as SupervisorJadwalController;
use App\Http\Controllers\Supervisor\KehadiranController as SupervisorKehadiranController;
use App\Http\Controllers\Supervisor\EvaluasiController as SupervisorEvaluasiController;
use App\Http\Controllers\Supervisor\MonitoringController as SupervisorMonitoringController;
use App\Http\Controllers\Supervisor\ObservasiController as SupervisorObservasiController;
use App\Http\Controllers\Supervisor\LaporanController as SupervisorLaporanController;
use App\Http\Controllers\Supervisor\ProfilController as SupervisorProfilController;
use App\Http\Controllers\Supervisor\NotifikasiController as SupervisorNotifikasiController;
use App\Http\Controllers\Instruktur\DashboardController as InstrukturDashboardController;
use App\Http\Controllers\Instruktur\JadwalController as InstrukturJadwalController;
use App\Http\Controllers\Instruktur\AktivitasController as InstrukturAktivitasController;
use App\Http\Controllers\Instruktur\EvaluasiController as InstrukturEvaluasiController;
use App\Http\Controllers\Instruktur\RaporController as InstrukturRaporController;
use App\Http\Controllers\Instruktur\ProfilController as InstrukturProfilController;
use App\Http\Controllers\Instruktur\NotifikasiController as InstrukturNotifikasiController;

// Melayani berkas dari disk public (foto profil) secara langsung pada Windows apabila symlink belum aktif.
Route::get('/storage/{path}', [AuthController::class, 'servePublicStorage'])
    ->where('path', '.*')
    ->name('storage.public');

Route::get('/', [AuthController::class, 'home'])->name('home');

// Portal Publik Evaluasi Peserta (Tanpa Login)
Route::get('/evaluasi', [EvaluasiPublikController::class, 'index'])->name('evaluasi.publik.index');
Route::post('/evaluasi', [EvaluasiPublikController::class, 'store'])->name('evaluasi.publik.store');
Route::get('/evaluasi/berhasil', [EvaluasiPublikController::class, 'berhasil'])->name('evaluasi.publik.berhasil');

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Panel Admin
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::redirect('/', '/admin/dashboard');

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('pengguna')->name('pengguna.')->group(function () {
            Route::get('/',                           [PenggunaController::class, 'index'])->name('index');
            Route::get('/create',                     [PenggunaController::class, 'create'])->name('create');
            Route::get('/tambah',                     [PenggunaController::class, 'create']);
            Route::post('/',                          [PenggunaController::class, 'store'])->name('store');
            Route::get('/{pengguna}/edit',            [PenggunaController::class, 'edit'])->name('edit');
            Route::put('/{pengguna}',                 [PenggunaController::class, 'update'])->name('update');
            Route::post('/{pengguna}/toggle-status',  [PenggunaController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{pengguna}/toggle',         [PenggunaController::class, 'toggleStatus'])->name('toggle');
            Route::post('/{pengguna}/reset-password', [PenggunaController::class, 'resetPassword'])->name('reset-password');
            Route::delete('/{pengguna}',              [PenggunaController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('instruktur')->name('instruktur.')->group(function () {
            Route::get('/',                            [InstrukturController::class, 'index'])->name('index');
            Route::get('/create',                      [InstrukturController::class, 'create'])->name('create');
            Route::get('/tambah',                      [InstrukturController::class, 'create']);
            Route::post('/',                           [InstrukturController::class, 'store'])->name('store');
            Route::post('/{id}/restore',               [InstrukturController::class, 'restore'])->name('restore');
            Route::get('/{instruktur}',                [InstrukturController::class, 'show'])->name('show');
            Route::get('/{instruktur}/edit',           [InstrukturController::class, 'edit'])->name('edit');
            Route::put('/{instruktur}',                [InstrukturController::class, 'update'])->name('update');
            Route::post('/{instruktur}/sertifikasi',                      [InstrukturController::class, 'storeSertifikasi'])->name('sertifikasi.store');
            Route::put('/{instruktur}/sertifikasi/{sertifikasi}',         [InstrukturController::class, 'updateSertifikasi'])->name('sertifikasi.update');
            Route::delete('/{instruktur}/sertifikasi/{sertifikasi}',      [InstrukturController::class, 'destroySertifikasi'])->name('sertifikasi.destroy');
            Route::get('/{instruktur}/sertifikasi/{sertifikasi}/dokumen', [InstrukturController::class, 'dokumenSertifikasi'])->name('sertifikasi.dokumen');
            Route::post('/{instruktur}/toggle-status', [InstrukturController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{instruktur}/toggle',        [InstrukturController::class, 'toggleStatus'])->name('toggle');
            Route::delete('/{instruktur}',             [InstrukturController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('kelas')->name('kelas.')->group(function () {
            Route::get('/',                   [KelasController::class, 'index'])->name('index');
            Route::get('/create',             [KelasController::class, 'create'])->name('create');
            Route::get('/tambah',             [KelasController::class, 'create']);
            Route::post('/',                  [KelasController::class, 'store'])->name('store');
            Route::post('/{id}/restore',      [KelasController::class, 'restore'])->name('restore');
            Route::get('/{kelas}',            [KelasController::class, 'show'])->withTrashed()->name('show');
            Route::get('/{kelas}/edit',       [KelasController::class, 'edit'])->name('edit');
            Route::put('/{kelas}',            [KelasController::class, 'update'])->name('update');
            Route::post('/{kelas}/toggle',    [KelasController::class, 'toggleStatus'])->name('toggle');
            Route::delete('/{kelas}',         [KelasController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('jadwal')->name('jadwal.')->group(function () {
            Route::get('/',                  [JadwalController::class, 'index'])->name('index');
            Route::get('/create',            [JadwalController::class, 'create'])->name('create');
            Route::get('/tambah',            [JadwalController::class, 'create']);
            Route::post('/',                 [JadwalController::class, 'store'])->name('store');
            Route::get('/{jadwal}/edit',     [JadwalController::class, 'edit'])->name('edit');
            Route::put('/{jadwal}',          [JadwalController::class, 'update'])->name('update');
            Route::post('/{jadwal}/alihkan', [JadwalController::class, 'alihkan'])->name('alihkan');
            Route::post('/{jadwal}/batal',   [JadwalController::class, 'batalkan'])->name('batal');
            Route::delete('/{jadwal}',       [JadwalController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('kehadiran')->name('kehadiran.')->group(function () {
            Route::get('/',                                     [AdminKehadiranController::class, 'index'])->name('index');
            Route::post('/manual',                              [AdminKehadiranController::class, 'storeManual'])->name('manual');
            Route::post('/auto-checkout',                       [AdminKehadiranController::class, 'autoCheckout'])->name('auto-checkout');
            Route::post('/aktivitas',                           [AdminKehadiranController::class, 'storeAktivitas'])->name('aktivitas.store');
            Route::get('/aktivitas/{aktivitas}/dokumentasi',    [AdminKehadiranController::class, 'dokumenAktivitas'])->name('aktivitas.dokumentasi');
            Route::post('/aktivitas/{aktivitas}/verifikasi',    [AdminKehadiranController::class, 'verifikasi'])->name('verifikasi');
        });

        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/',                  [ImportController::class, 'index'])->name('index');
            Route::get('/template/{jenis?}', [ImportController::class, 'downloadTemplate'])->name('template');
            Route::post('/preview',          [ImportController::class, 'preview'])->name('preview');
            Route::post('/proses',           [ImportController::class, 'proses'])->name('proses');
        });

        Route::prefix('evaluasi')->name('evaluasi.')->group(function () {
            Route::get('/', [EvaluasiController::class, 'index'])->name('index');
            Route::prefix('kode')->name('kode.')->group(function () {
                Route::get('/',                       [EvaluasiController::class, 'kodeIndex'])->name('index');
                Route::post('/',                      [EvaluasiController::class, 'kodeStore'])->name('store');
                Route::post('/{kodeEvaluasi}/toggle', [EvaluasiController::class, 'kodeToggle'])->name('toggle');
            });
        });

        Route::get('/kinerja', [MonitoringController::class, 'index'])->name('kinerja.index');

        Route::prefix('observasi')->name('observasi.')->group(function () {
            Route::get('/',               [AdminObservasiController::class, 'index'])->name('index');
            Route::post('/',              [AdminObservasiController::class, 'store'])->name('store');
            Route::put('/{observasi}',    [AdminObservasiController::class, 'update'])->name('update');
            Route::delete('/{observasi}', [AdminObservasiController::class, 'destroy'])->name('destroy');
        });
        Route::post('/aktivitas/{aktivitas}/verifikasi', [AdminObservasiController::class, 'verifikasiAktivitas'])->name('aktivitas.verifikasi');

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/',             [LaporanController::class, 'index'])->name('index');
            Route::get('/cetak',        [LaporanController::class, 'cetak'])->name('cetak');
            Route::get('/export',       [LaporanController::class, 'export'])->name('export');
            Route::get('/export-excel', [LaporanController::class, 'exportExcel'])->name('export-excel');
        });

        Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
            Route::get('/',         [PengaturanController::class, 'index'])->name('index');
            Route::put('/',         [PengaturanController::class, 'update'])->name('update');
            Route::put('/aplikasi', [PengaturanController::class, 'update']);
            Route::put('/profil',   [PengaturanController::class, 'updateProfil'])->name('profil');
            Route::put('/password', [PengaturanController::class, 'updatePassword'])->name('password');
            Route::put('/kinerja',  [PengaturanController::class, 'updateKinerja'])->name('kinerja');
        });

        Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
            Route::get('/',             [NotifikasiController::class, 'index'])->name('index');
            Route::post('/tinjau',      [NotifikasiController::class, 'markReviewed'])->name('tinjau');
            Route::post('/tinjau-semua',[NotifikasiController::class, 'markAllReviewed'])->name('tinjau-semua');
            Route::post('/kembalikan',  [NotifikasiController::class, 'restore'])->name('kembalikan');
        });
    });

// Panel Supervisor
Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {

        Route::redirect('/', '/supervisor/dashboard');

        Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('instruktur')->name('instruktur.')->group(function () {
            Route::get('/',                                               [SupervisorInstrukturController::class, 'index'])->name('index');
            Route::get('/{instruktur}',                                   [SupervisorInstrukturController::class, 'show'])->name('show');
            Route::get('/{instruktur}/sertifikasi/{sertifikasi}/dokumen', [SupervisorInstrukturController::class, 'dokumenSertifikasi'])->name('sertifikasi.dokumen');
        });

        Route::prefix('jadwal')->name('jadwal.')->group(function () {
            Route::get('/', [SupervisorJadwalController::class, 'index'])->name('index');
        });

        Route::prefix('kehadiran')->name('kehadiran.')->group(function () {
            Route::get('/',                                     [SupervisorKehadiranController::class, 'index'])->name('index');
            Route::get('/aktivitas/{aktivitas}/dokumentasi',    [SupervisorKehadiranController::class, 'dokumenAktivitas'])->name('aktivitas.dokumentasi');
            Route::post('/aktivitas/{aktivitas}/verifikasi',    [SupervisorKehadiranController::class, 'verifikasi'])->name('verifikasi');
        });

        Route::prefix('evaluasi')->name('evaluasi.')->group(function () {
            Route::get('/', [SupervisorEvaluasiController::class, 'index'])->name('index');
            Route::prefix('kode')->name('kode.')->group(function () {
                Route::get('/', [SupervisorEvaluasiController::class, 'kodeIndex'])->name('index');
            });
        });

        Route::get('/kinerja', [SupervisorMonitoringController::class, 'index'])->name('kinerja.index');

        Route::prefix('observasi')->name('observasi.')->group(function () {
            Route::get('/',               [SupervisorObservasiController::class, 'index'])->name('index');
            Route::post('/',              [SupervisorObservasiController::class, 'store'])->name('store');
            Route::put('/{observasi}',    [SupervisorObservasiController::class, 'update'])->name('update');
            Route::delete('/{observasi}', [SupervisorObservasiController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/',             [SupervisorLaporanController::class, 'index'])->name('index');
            Route::get('/cetak',        [SupervisorLaporanController::class, 'cetak'])->name('cetak');
            Route::get('/export',       [SupervisorLaporanController::class, 'export'])->name('export');
            Route::get('/export-excel', [SupervisorLaporanController::class, 'exportExcel'])->name('export-excel');
        });

        Route::prefix('profil')->name('profil.')->group(function () {
            Route::get('/',         [SupervisorProfilController::class, 'index'])->name('index');
            Route::put('/',         [SupervisorProfilController::class, 'update'])->name('update');
            Route::put('/password', [SupervisorProfilController::class, 'updatePassword'])->name('password');
        });

        Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
            Route::get('/',             [SupervisorNotifikasiController::class, 'index'])->name('index');
            Route::post('/tinjau',      [SupervisorNotifikasiController::class, 'markReviewed'])->name('tinjau');
            Route::post('/tinjau-semua',[SupervisorNotifikasiController::class, 'markAllReviewed'])->name('tinjau-semua');
            Route::post('/kembalikan',  [SupervisorNotifikasiController::class, 'restore'])->name('kembalikan');
        });
    });

// Panel Instruktur (Hanya mengakses data miliknya sendiri)
Route::prefix('instruktur-panel')
    ->name('instruktur.')
    ->middleware(['auth', 'role:instruktur'])
    ->group(function () {
        Route::redirect('/', '/instruktur-panel/dashboard');
        Route::get('/dashboard', [InstrukturDashboardController::class, 'index'])->name('dashboard');
        Route::get('/jadwal', [InstrukturJadwalController::class, 'index'])->name('jadwal.index');
        Route::post('/jadwal/{jadwal}/checkin', [InstrukturJadwalController::class, 'checkin'])->name('jadwal.checkin');
        Route::post('/jadwal/{jadwal}/checkout', [InstrukturJadwalController::class, 'checkout'])->name('jadwal.checkout');
        Route::get('/aktivitas', [InstrukturAktivitasController::class, 'index'])->name('aktivitas.index');
        Route::get('/aktivitas/export-excel', [InstrukturAktivitasController::class, 'exportExcel'])->name('aktivitas.export.excel');
        Route::get('/aktivitas/export-pdf', [InstrukturAktivitasController::class, 'exportPdf'])->name('aktivitas.export.pdf');
        Route::get('/aktivitas/{aktivitas}/dokumentasi', [InstrukturAktivitasController::class, 'dokumenAktivitas'])->name('aktivitas.dokumentasi');
        Route::put('/aktivitas/{jadwal}', [InstrukturAktivitasController::class, 'update'])->name('aktivitas.update');
        Route::get('/evaluasi', [InstrukturEvaluasiController::class, 'index'])->name('evaluasi.index');
        Route::get('/rapor', [InstrukturRaporController::class, 'index'])->name('rapor.index');
        Route::get('/profil', [InstrukturProfilController::class, 'index'])->name('profil.index');
        Route::put('/profil', [InstrukturProfilController::class, 'update'])->name('profil.update');
        Route::put('/profil/password', [InstrukturProfilController::class, 'updatePassword'])->name('profil.password');
        Route::get('/profil/sertifikasi/{sertifikasi}/dokumen', [InstrukturProfilController::class, 'dokumenSertifikasi'])->name('profil.sertifikasi.dokumen');

        Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
            Route::get('/',              [InstrukturNotifikasiController::class, 'index'])->name('index');
            Route::post('/tinjau',       [InstrukturNotifikasiController::class, 'markReviewed'])->name('tinjau');
            Route::post('/tinjau-semua', [InstrukturNotifikasiController::class, 'markAllReviewed'])->name('tinjau-semua');
            Route::post('/kembalikan',   [InstrukturNotifikasiController::class, 'restore'])->name('kembalikan');
        });
    });

// Fallback untuk seluruh URL yang tidak terdaftar -> tampilkan halaman 404 custom LPK CTI
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
