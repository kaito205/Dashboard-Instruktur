<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluasi;
use App\Models\ObservasiSupervisor;
use App\Models\Setting;
use App\Services\KinerjaCalculationService;
use App\Services\OperationalAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PengaturanController extends Controller
{
    protected function authorizeAdminOnly(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);
    }

    public function index()
    {
        $this->authorizeAdminOnly();

        $settings            = Setting::all()->keyBy('key');
        $indikatorEvaluasi   = Evaluasi::getIndikatorLabels();
        $indikatorObservasi  = ObservasiSupervisor::getIndikatorLabels();

        return view('pages.admin.pengaturan.index', compact(
            'settings',
            'indikatorEvaluasi',
            'indikatorObservasi'
        ));
    }

    public function update(Request $request)
    {
        $this->authorizeAdminOnly();

        $validated = $request->validate([
            'app_name'        => ['required', 'string', 'max:100'],
            'app_description' => ['nullable', 'string', 'max:255'],
            'lpk_name'        => ['required', 'string', 'max:150'],
            'lpk_alamat'      => ['nullable', 'string', 'max:500'],
            'default_periode' => ['required', 'in:hari_ini,minggu_ini,bulan_ini,tahun_ini'],
        ], [
            'app_name.required'        => 'Nama aplikasi wajib diisi.',
            'app_name.max'             => 'Nama aplikasi maksimal 100 karakter.',
            'app_description.max'      => 'Deskripsi aplikasi maksimal 255 karakter.',
            'lpk_name.required'        => 'Nama lembaga (LPK) wajib diisi.',
            'lpk_name.max'             => 'Nama lembaga maksimal 150 karakter.',
            'lpk_alamat.max'           => 'Alamat lembaga maksimal 500 karakter.',
            'default_periode.required' => 'Filter periode default wajib dipilih.',
            'default_periode.in'       => 'Pilihan filter periode default tidak valid.',
        ]);

        Setting::set('app_name', trim((string) $validated['app_name']), 'general');
        Setting::set('app_description', trim((string) ($validated['app_description'] ?? '')), 'general');
        Setting::set('lpk_name', trim((string) $validated['lpk_name']), 'general');
        Setting::set('lpk_alamat', trim((string) ($validated['lpk_alamat'] ?? '')), 'general');
        Setting::set('default_periode', $validated['default_periode'], 'general');

        return back()->with('success', 'Pengaturan identitas dan periode aplikasi berhasil disimpan.');
    }

    public function updateProfil(Request $request)
    {
        $this->authorizeAdminOnly();

        $user = auth()->user();

        $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'foto_profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.unique'      => 'Email sudah digunakan oleh akun lain.',
            'foto_profil.image' => 'Berkas foto harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, PNG, atau WebP.',
            'foto_profil.max'   => 'Ukuran foto maksimal 2MB.',
        ]);

        $payload = [
            'name'  => $request->name,
            'email' => $request->email,
        ];

        $oldPhoto = $user->foto_profil;
        $newPhoto = null;

        if ($request->hasFile('foto_profil')) {
            $newPhoto               = $request->file('foto_profil')->store('users/foto', 'public');
            $payload['foto_profil'] = $newPhoto;
        }

        $user->update($payload);

        if ($newPhoto && $oldPhoto && $oldPhoto !== $newPhoto && Storage::disk('public')->exists($oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return back()->with('success', 'Profil administrator berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $this->authorizeAdminOnly();

        $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'password_lama.required'  => 'Password saat ini wajib diisi.',
            'password_baru.required'  => 'Password baru wajib diisi.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors(['password_lama' => 'Password saat ini tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($request->password_baru)]);

        return back()->with('success', 'Password keamanan akun berhasil diperbarui.');
    }

    public function updateKinerja(Request $request)
    {
        $this->authorizeAdminOnly();

        // Kompatibilitas mundur apabila form mengirimkan bobot_absensi
        if (!$request->filled('bobot_kehadiran') && $request->filled('bobot_absensi')) {
            $request->merge(['bobot_kehadiran' => $request->input('bobot_absensi')]);
        }

        $validator = Validator::make($request->all(), [
            'kinerja_rule_enabled'       => ['nullable', 'boolean'],
            'bobot_kehadiran'            => ['required', 'numeric', 'min:0', 'max:100'],
            'bobot_aktivitas'            => ['required', 'numeric', 'min:0', 'max:100'],
            'bobot_evaluasi'             => ['required', 'numeric', 'min:0', 'max:100'],
            'bobot_observasi'            => ['required', 'numeric', 'min:0', 'max:100'],
            'batas_sangat_baik'          => ['required', 'numeric', 'min:1', 'max:100'],
            'batas_baik'                 => ['required', 'numeric', 'min:1', 'lt:batas_sangat_baik'],
            'batas_cukup'                => ['required', 'numeric', 'min:1', 'lt:batas_baik'],
            'toleransi_terlambat_menit'  => ['required', 'integer', 'min:0', 'max:180'],
            'checkin_buka_sebelum_menit' => ['required', 'integer', 'min:0', 'max:240'],
            'lpk_latitude'               => ['required', 'numeric', 'between:-90,90'],
            'lpk_longitude'              => ['required', 'numeric', 'between:-180,180'],
            'radius_lokasi_meter'        => ['required', 'integer', 'min:10', 'max:10000'],
            'eval_indikator_1'           => ['nullable', 'string', 'max:200'],
            'eval_indikator_2'           => ['nullable', 'string', 'max:200'],
            'eval_indikator_3'           => ['nullable', 'string', 'max:200'],
            'eval_indikator_4'           => ['nullable', 'string', 'max:200'],
            'eval_indikator_5'           => ['nullable', 'string', 'max:200'],
            'obs_indikator_1'            => ['nullable', 'string', 'max:200'],
            'obs_indikator_2'            => ['nullable', 'string', 'max:200'],
            'obs_indikator_3'            => ['nullable', 'string', 'max:200'],
            'obs_indikator_4'            => ['nullable', 'string', 'max:200'],
            'obs_indikator_5'            => ['nullable', 'string', 'max:200'],
        ], [
            'bobot_kehadiran.required'            => 'Bobot Kehadiran wajib diisi.',
            'bobot_kehadiran.numeric'             => 'Bobot Kehadiran harus berupa angka.',
            'bobot_kehadiran.min'                 => 'Bobot Kehadiran minimal 0%.',
            'bobot_kehadiran.max'                 => 'Bobot Kehadiran maksimal 100%.',
            'bobot_aktivitas.required'            => 'Bobot Kepatuhan Aktivitas wajib diisi.',
            'bobot_aktivitas.numeric'             => 'Bobot Kepatuhan Aktivitas harus berupa angka.',
            'bobot_aktivitas.min'                 => 'Bobot Kepatuhan Aktivitas minimal 0%.',
            'bobot_aktivitas.max'                 => 'Bobot Kepatuhan Aktivitas maksimal 100%.',
            'bobot_evaluasi.required'             => 'Bobot Evaluasi Peserta wajib diisi.',
            'bobot_evaluasi.numeric'              => 'Bobot Evaluasi Peserta harus berupa angka.',
            'bobot_evaluasi.min'                  => 'Bobot Evaluasi Peserta minimal 0%.',
            'bobot_evaluasi.max'                  => 'Bobot Evaluasi Peserta maksimal 100%.',
            'bobot_observasi.required'            => 'Bobot Observasi Supervisor wajib diisi.',
            'bobot_observasi.numeric'             => 'Bobot Observasi Supervisor harus berupa angka.',
            'bobot_observasi.min'                 => 'Bobot Observasi Supervisor minimal 0%.',
            'bobot_observasi.max'                 => 'Bobot Observasi Supervisor maksimal 100%.',
            'batas_sangat_baik.required'          => 'Batas kategori Sangat Baik wajib diisi.',
            'batas_sangat_baik.numeric'           => 'Batas kategori Sangat Baik harus berupa angka.',
            'batas_sangat_baik.min'               => 'Batas kategori Sangat Baik minimal 1.',
            'batas_sangat_baik.max'               => 'Batas kategori Sangat Baik maksimal 100.',
            'batas_baik.required'                 => 'Batas kategori Baik wajib diisi.',
            'batas_baik.numeric'                  => 'Batas kategori Baik harus berupa angka.',
            'batas_baik.min'                      => 'Batas kategori Baik minimal 1.',
            'batas_baik.lt'                       => 'Batas kategori Baik harus lebih rendah dari batas Sangat Baik.',
            'batas_cukup.required'                => 'Batas kategori Cukup wajib diisi.',
            'batas_cukup.numeric'                 => 'Batas kategori Cukup harus berupa angka.',
            'batas_cukup.min'                     => 'Batas kategori Cukup minimal 1.',
            'batas_cukup.lt'                      => 'Batas kategori Cukup harus lebih rendah dari batas Baik.',
            'toleransi_terlambat_menit.required'  => 'Toleransi terlambat wajib diisi.',
            'toleransi_terlambat_menit.integer'   => 'Toleransi terlambat harus berupa bilangan bulat (menit).',
            'toleransi_terlambat_menit.min'       => 'Toleransi terlambat minimal 0 menit.',
            'toleransi_terlambat_menit.max'       => 'Toleransi terlambat maksimal 180 menit.',
            'checkin_buka_sebelum_menit.required' => 'Waktu buka check-in wajib diisi.',
            'checkin_buka_sebelum_menit.integer'  => 'Waktu buka check-in harus berupa bilangan bulat (menit).',
            'checkin_buka_sebelum_menit.min'      => 'Waktu buka check-in minimal 0 menit.',
            'checkin_buka_sebelum_menit.max'      => 'Waktu buka check-in maksimal 240 menit.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $bobotKeys = ['bobot_kehadiran', 'bobot_aktivitas', 'bobot_evaluasi', 'bobot_observasi'];
            foreach ($bobotKeys as $key) {
                if ($validator->errors()->has($key) || !is_numeric($request->input($key))) {
                    return;
                }
            }

            $total = round(
                (float) $request->input('bobot_kehadiran')
                + (float) $request->input('bobot_aktivitas')
                + (float) $request->input('bobot_evaluasi')
                + (float) $request->input('bobot_observasi'),
                2
            );

            if (abs($total - 100.0) > 0.001) {
                $selisih    = round(abs(100.0 - $total), 2);
                $totalStr   = KinerjaCalculationService::formatAngka($total);
                $selisihStr = KinerjaCalculationService::formatAngka($selisih);
                $arah       = $total < 100.0 ? "kurang {$selisihStr}%" : "lebih {$selisihStr}%";

                $validator->errors()->add(
                    'bobot_total',
                    "Total bobot 4 komponen kinerja saat ini {$totalStr}% ({$arah}). Total bobot wajib tepat 100%."
                );
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->to(route('admin.pengaturan.index') . '#pengaturan-kinerja')
                ->withErrors($validator)
                ->withInput();
        }

        $normalizeDecimal = static function (mixed $value): string {
            $formatted = number_format((float) $value, 2, '.', '');
            $trimmed   = rtrim(rtrim($formatted, '0'), '.');

            return $trimmed === '' ? '0' : $trimmed;
        };

        $bobotKehadiran = $normalizeDecimal($request->input('bobot_kehadiran'));
        $bobotAktivitas = $normalizeDecimal($request->input('bobot_aktivitas'));
        $bobotEvaluasi  = $normalizeDecimal($request->input('bobot_evaluasi'));
        $bobotObservasi = $normalizeDecimal($request->input('bobot_observasi'));

        Setting::set('kinerja_rule_enabled', $request->boolean('kinerja_rule_enabled') ? '1' : '0', 'kinerja');
        Setting::set('bobot_kehadiran', $bobotKehadiran, 'kinerja');
        Setting::set('bobot_absensi', $bobotKehadiran, 'kinerja');
        Setting::set('bobot_aktivitas', $bobotAktivitas, 'kinerja');
        Setting::set('bobot_evaluasi', $bobotEvaluasi, 'kinerja');
        Setting::set('bobot_observasi', $bobotObservasi, 'kinerja');
        Setting::set('batas_sangat_baik', $normalizeDecimal($request->input('batas_sangat_baik')), 'kinerja');
        Setting::set('batas_baik', $normalizeDecimal($request->input('batas_baik')), 'kinerja');
        Setting::set('batas_cukup', $normalizeDecimal($request->input('batas_cukup')), 'kinerja');
        Setting::set('toleransi_terlambat_menit', (string) ((int) $request->input('toleransi_terlambat_menit')), 'absensi');
        Setting::set('checkin_buka_sebelum_menit', (string) ((int) $request->input('checkin_buka_sebelum_menit')), 'absensi');
        Setting::set('lpk_latitude', trim((string) $request->input('lpk_latitude')), 'absensi');
        Setting::set('lpk_longitude', trim((string) $request->input('lpk_longitude')), 'absensi');
        Setting::set('radius_lokasi_meter', (string) max(10, (int) $request->input('radius_lokasi_meter')), 'absensi');

        $hasEvalInput = collect(range(1, 5))->contains(fn (int $i) => $request->filled("eval_indikator_{$i}"));
        if ($hasEvalInput) {
            $evalCurrent = Evaluasi::getIndikatorLabels();
            for ($i = 1; $i <= 5; $i++) {
                $val = trim((string) $request->input("eval_indikator_{$i}", ''));
                if ($val !== '') {
                    $evalCurrent["indikator_{$i}"] = $val;
                }
            }
            Setting::set('indikator_evaluasi_peserta', json_encode($evalCurrent, JSON_UNESCAPED_UNICODE), 'evaluasi');
        }

        $hasObsInput = collect(range(1, 5))->contains(fn (int $i) => $request->filled("obs_indikator_{$i}"));
        if ($hasObsInput) {
            $obsCurrent = ObservasiSupervisor::getIndikatorLabels();
            for ($i = 1; $i <= 5; $i++) {
                $val = trim((string) $request->input("obs_indikator_{$i}", ''));
                if ($val !== '') {
                    $obsCurrent["indikator_{$i}"] = $val;
                }
            }
            Setting::set('indikator_observasi_supervisor', json_encode($obsCurrent, JSON_UNESCAPED_UNICODE), 'observasi');
        }

        KinerjaCalculationService::clearMemo();
        OperationalAlertService::clearMemo();

        return redirect()
            ->to(route('admin.pengaturan.index') . '#pengaturan-kinerja')
            ->with('success', 'Konfigurasi parameter bobot kinerja, ambang batas kategori, dan presensi berhasil disimpan.');
    }
}
