<?php

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

Schedule::command('absensi:auto-checkout')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
