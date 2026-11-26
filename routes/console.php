<?php

use App\Http\Controllers\Admin\ImportController;
use App\Models\Absensi;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('absensi:auto-checkout', function () {
    $closed = Absensi::prosesAutoCheckoutSesiLewat(30);
    $this->info("Auto-checkout selesai: {$closed} sesi ditutup.");
})->purpose('Menutup otomatis sesi presensi yang lupa check-out melewati batas toleransi kelas selesai');

Artisan::command('import:cleanup-previews {--minutes=60 : Batas usia pratinjau dalam menit}', function () {
    $minutes = max(5, (int) $this->option('minutes'));
    $deleted = ImportController::purgeExpiredPreviews($minutes);
    $this->info("Pembersihan pratinjau import selesai: {$deleted} berkas kedaluwarsa dihapus.");
})->purpose('Membersihkan berkas JSON pratinjau import yang sudah kedaluwarsa dan tidak sedang diproses');

Schedule::command('absensi:auto-checkout')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('import:cleanup-previews')
    ->hourly()
    ->withoutOverlapping();
