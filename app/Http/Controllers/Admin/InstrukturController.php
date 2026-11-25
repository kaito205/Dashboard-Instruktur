<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstrukturRequest;
use App\Models\Instruktur;
use App\Models\InstrukturAuditLog;
use App\Models\InstrukturSertifikasi;
use App\Models\User;
use App\Services\KinerjaCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InstrukturController extends Controller
{
    public function __construct(
        private readonly KinerjaCalculationService $kinerjaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Instruktur::class);

        $search       = trim((string) ($request->input('search') ?? $request->input('cari') ?? ''));
        $status       = (string) ($request->input('status') ?? '');
        $keahlian     = (string) ($request->input('keahlian') ?? '');
        $ketersediaan = (string) ($request->input('ketersediaan') ?? '');
        $arsip        = $request->boolean('arsip');

        $query = $arsip ? Instruktur::withTrashed() : Instruktur::query();

        $instrukturs = $query
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('kode_instruktur', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('bidang_keahlian', 'like', "%{$search}%")
                    ->orWhere('nama_sertifikasi', 'like', "%{$search}%")
                    ->orWhereHas('sertifikasis', fn ($sq) => $sq->where('nama_sertifikasi', 'like', "%{$search}%"));
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($keahlian !== '', fn ($q) => $q->where('bidang_keahlian', 'like', "%{$keahlian}%"))
            ->when($ketersediaan !== '', fn ($q) => $q->where('status_ketersediaan', $ketersediaan))
            ->withCount(['jadwals', 'absensis', 'evaluasis', 'sertifikasis'])
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        $keahlianList = Instruktur::distinct()->pluck('bidang_keahlian')->filter()->sort()->values();

        return view('pages.admin.instruktur.index', compact(
            'instrukturs',
            'search',
            'status',
            'keahlian',
            'ketersediaan',
            'arsip',
            'keahlianList'
        ));
    }

    public function create()
    {
        Gate::authorize('create', Instruktur::class);

        $usersAvailable = User::byRole('instruktur')
            ->whereDoesntHave('instruktur')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $nextCode = Instruktur::generateNextCode();

        return view('pages.admin.instruktur.create', compact('usersAvailable', 'nextCode'));
    }

    public function store(StoreInstrukturRequest $request)
    {
        Gate::authorize('create', Instruktur::class);

        $data         = $request->validated();
        $akunMode     = (string) ($data['akun_mode'] ?? 'auto');
        $passwordAwal = (string) ($data['password_awal'] ?? '');
        unset($data['akun_mode'], $data['password_awal']);

        $data['status_ketersediaan'] = $data['status_ketersediaan'] ?? 'tersedia';
        $createdAccountEmail         = null;

        $instruktur = DB::transaction(function () use ($data, $request, $akunMode, $passwordAwal, &$createdAccountEmail) {
            if ($request->hasFile('foto_profil')) {
                $data['foto_profil'] = $request->file('foto_profil')
                    ->store('instruktur/foto', 'public');
            }

            $fileSertifikasiName = null;
            if ($request->hasFile('file_sertifikasi')) {
                $fileSertifikasiName     = $request->file('file_sertifikasi')->getClientOriginalName();
                $data['file_sertifikasi'] = $request->file('file_sertifikasi')
                    ->store('instruktur/sertifikasi', 'local');
            }

            if ($akunMode === 'auto') {
                $emailUser = !empty($data['email'])
                    ? $data['email']
                    : strtolower($data['kode_instruktur']) . '@lpkcti.id';

                $newUser = User::create([
                    'name'        => $data['nama_lengkap'],
                    'email'       => $emailUser,
                    'foto_profil' => $data['foto_profil'] ?? null,
                    'password'    => Hash::make($passwordAwal),
                    'role'        => 'instruktur',
                    'is_active'   => ($data['status'] ?? 'aktif') === 'aktif',
                ]);
                $data['user_id']     = $newUser->id;
                $data['email']       = $emailUser;
                $createdAccountEmail = $emailUser;
            } elseif ($akunMode === 'existing') {
                // Validasi di StoreInstrukturRequest memastikan user_id ber-role instruktur dan belum tertaut.
            } else {
                $data['user_id'] = null;
            }

            $instruktur = Instruktur::create($data);

            $hasInitialCert = !empty($data['nama_sertifikasi'])
                || !empty($data['sertifikasi_berlaku_sampai'])
                || !empty($data['file_sertifikasi']);

            if ($hasInitialCert) {
                $instruktur->sertifikasis()->create([
                    'nama_sertifikasi' => !empty($data['nama_sertifikasi']) ? $data['nama_sertifikasi'] : 'Sertifikasi Kompetensi',
                    'berlaku_sampai'   => $data['sertifikasi_berlaku_sampai'] ?? null,
                    'file_path'        => $data['file_sertifikasi'] ?? null,
                    'file_name'        => $fileSertifikasiName,
                ]);
                $instruktur->syncPrimarySertifikasiSummary();
                $instruktur->refresh();
            }

            if ($instruktur->user && $akunMode === 'existing') {
                $emailSync    = $instruktur->email;
                $canSyncEmail = !empty($emailSync) && !User::where('email', $emailSync)->where('id', '!=', $instruktur->user_id)->exists();
                $instruktur->user->updateQuietly(array_filter([
                    'name'        => $instruktur->nama_lengkap,
                    'email'       => $canSyncEmail ? $emailSync : $instruktur->user->email,
                    'foto_profil' => $instruktur->foto_profil ?: $instruktur->user->foto_profil,
                    'is_active'   => $instruktur->status === 'aktif',
                ], fn ($v) => $v !== null));
            }

            InstrukturAuditLog::create([
                'instruktur_id'       => $instruktur->id,
                'user_id'             => auth()->id(),
                'aksi'                => 'dibuat',
                'ringkasan_perubahan' => "Profil instruktur {$instruktur->nama_lengkap} ({$instruktur->kode_instruktur}) ditambahkan.",
                'data_lama'           => null,
                'data_baru'           => $instruktur->only([
                    'nama_lengkap',
                    'kode_instruktur',
                    'bidang_keahlian',
                    'nama_sertifikasi',
                    'status',
                    'status_ketersediaan',
                ]),
            ]);

            return $instruktur;
        });

        $successMsg = match ($akunMode) {
            'auto'     => "Data instruktur {$instruktur->nama_lengkap} ({$instruktur->kode_instruktur}) dan akun login ({$createdAccountEmail}) berhasil dibuat.",
            'existing' => "Data instruktur {$instruktur->nama_lengkap} ({$instruktur->kode_instruktur}) berhasil ditambahkan dan ditautkan ke akun login.",
            default    => "Data instruktur {$instruktur->nama_lengkap} ({$instruktur->kode_instruktur}) berhasil ditambahkan tanpa akun login.",
        };

        return redirect()->route('admin.instruktur.index')
            ->with('success', $successMsg);
    }

    public function show(Instruktur $instruktur)
    {
        Gate::authorize('view', $instruktur);

        $instruktur->load([
            'user',
            'sertifikasis',
            'jadwals'            => fn ($q) => $q->orderByRaw('CASE WHEN is_pola_mingguan = 1 THEN 0 ELSE 1 END')->latest('tanggal')->take(10),
            'aktivitasMengajars' => fn ($q) => $q->with('jadwal.kelas')->latest('tanggal')->take(10),
            'kodeEvaluasis'      => fn ($q) => $q->withCount('evaluasis')->latest(),
            'auditLogs'          => fn ($q) => $q->with('user')->latest()->take(20),
        ]);

        $dari   = now()->subDays(30)->toDateString();
        $sampai = now()->toDateString();

        $statsAbsensi = $instruktur->absensis()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statsEvaluasi = [
            'total'     => $instruktur->evaluasis()->count(),
            'rata_rata' => round($instruktur->evaluasis()->avg('total_nilai') ?? 0, 2),
        ];

        $kinerjaSummary = $this->kinerjaService->evaluateInstruktur(
            $instruktur,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        return view('pages.admin.instruktur.show', compact(
            'instruktur',
            'statsAbsensi',
            'statsEvaluasi',
            'kinerjaSummary'
        ));
    }

    public function edit(Instruktur $instruktur)
    {
        Gate::authorize('update', $instruktur);

        $instruktur->load('sertifikasis');

        $usersAvailable = User::byRole('instruktur')
            ->where(function ($q) use ($instruktur) {
                $q->whereDoesntHave('instruktur')
                    ->orWhere('id', $instruktur->user_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('pages.admin.instruktur.edit', compact('instruktur', 'usersAvailable'));
    }

    public function update(StoreInstrukturRequest $request, Instruktur $instruktur)
    {
        Gate::authorize('update', $instruktur);

        $data = $request->validated();
        unset($data['akun_mode'], $data['password_awal']);
        $data['status_ketersediaan'] = $data['status_ketersediaan'] ?? $instruktur->status_ketersediaan ?? 'tersedia';

        $hasCertInput = $request->hasAny(['nama_sertifikasi', 'sertifikasi_berlaku_sampai'])
            || $request->hasFile('file_sertifikasi');

        if (! $hasCertInput) {
            unset($data['nama_sertifikasi'], $data['sertifikasi_berlaku_sampai'], $data['file_sertifikasi']);
        }

        $trackedFields = [
            'nama_lengkap',
            'kode_instruktur',
            'email',
            'no_telepon',
            'bidang_keahlian',
            'riwayat_keahlian',
            'nama_sertifikasi',
            'sertifikasi_berlaku_sampai',
            'status',
            'status_ketersediaan',
            'cuti_mulai',
            'cuti_selesai',
            'keterangan_cuti',
        ];
        $snapshotLama = $instruktur->only($trackedFields);

        DB::transaction(function () use ($data, $request, $instruktur, $trackedFields, $snapshotLama, $hasCertInput) {
            if ($request->hasFile('foto_profil')) {
                if ($instruktur->foto_profil) {
                    Storage::disk('public')->delete($instruktur->foto_profil);
                }
                $data['foto_profil'] = $request->file('foto_profil')
                    ->store('instruktur/foto', 'public');
            } else {
                unset($data['foto_profil']);
            }

            $uploadedCertName = null;
            $uploadedCertPath = null;
            if ($hasCertInput && $request->hasFile('file_sertifikasi')) {
                $uploadedCertName        = $request->file('file_sertifikasi')->getClientOriginalName();
                $uploadedCertPath        = $request->file('file_sertifikasi')->store('instruktur/sertifikasi', 'local');
                $data['file_sertifikasi'] = $uploadedCertPath;
            } else {
                unset($data['file_sertifikasi']);
            }

            $instruktur->update($data);

            if ($hasCertInput) {
                $latestCert = $instruktur->sertifikasis()->first();
                if ($latestCert) {
                    $oldCertPath = $latestCert->file_path;
                    $certUpdate  = [
                        'nama_sertifikasi' => !empty($data['nama_sertifikasi']) ? $data['nama_sertifikasi'] : $latestCert->nama_sertifikasi,
                        'berlaku_sampai'   => $request->has('sertifikasi_berlaku_sampai') ? ($data['sertifikasi_berlaku_sampai'] ?? null) : $latestCert->berlaku_sampai,
                    ];
                    if ($uploadedCertPath !== null) {
                        $certUpdate['file_path'] = $uploadedCertPath;
                        $certUpdate['file_name'] = $uploadedCertName;
                    }
                    $latestCert->update($certUpdate);
                    if ($uploadedCertPath !== null && $oldCertPath !== $uploadedCertPath) {
                        $this->deleteSertifikasiFileIfUnused($oldCertPath, $latestCert->id);
                    }
                } elseif (!empty($data['nama_sertifikasi']) || !empty($data['sertifikasi_berlaku_sampai']) || $uploadedCertPath !== null) {
                    $instruktur->sertifikasis()->create([
                        'nama_sertifikasi' => !empty($data['nama_sertifikasi']) ? $data['nama_sertifikasi'] : 'Sertifikasi Kompetensi',
                        'berlaku_sampai'   => $data['sertifikasi_berlaku_sampai'] ?? null,
                        'file_path'        => $uploadedCertPath,
                        'file_name'        => $uploadedCertName,
                    ]);
                }

                $instruktur->syncPrimarySertifikasiSummary();
            }

            $instrukturFresh = $instruktur->fresh();

            if ($instrukturFresh->user) {
                $emailSync = $instrukturFresh->email;
                $canSyncEmail = !empty($emailSync) && !User::where('email', $emailSync)->where('id', '!=', $instrukturFresh->user_id)->exists();
                $isActiveNow = $instrukturFresh->status === 'aktif';
                $instrukturFresh->user->updateQuietly([
                    'name'        => $instrukturFresh->nama_lengkap,
                    'email'       => $canSyncEmail ? $emailSync : $instrukturFresh->user->email,
                    'foto_profil' => $instrukturFresh->foto_profil ?: $instrukturFresh->user->foto_profil,
                    'is_active'   => $isActiveNow,
                ]);
                if (! $isActiveNow) {
                    $instrukturFresh->user->revokeActiveSessions();
                }
            }

            $snapshotBaru = $instrukturFresh->only($trackedFields);

            $changedKeys = [];
            foreach ($trackedFields as $field) {
                $oldVal = $snapshotLama[$field] instanceof \DateTimeInterface ? $snapshotLama[$field]->format('Y-m-d') : (string) ($snapshotLama[$field] ?? '');
                $newVal = $snapshotBaru[$field] instanceof \DateTimeInterface ? $snapshotBaru[$field]->format('Y-m-d') : (string) ($snapshotBaru[$field] ?? '');
                if ($oldVal !== $newVal) {
                    $changedKeys[] = $field;
                }
            }

            $aksiLog = in_array('status_ketersediaan', $changedKeys, true)
                ? 'cuti_diubah'
                : (in_array('nama_sertifikasi', $changedKeys, true) || in_array('sertifikasi_berlaku_sampai', $changedKeys, true) ? 'sertifikasi_diubah' : 'diperbarui');

            $ringkasan = !empty($changedKeys)
                ? 'Memperbarui kolom: ' . implode(', ', $changedKeys)
                : 'Memperbarui profil instruktur';

            InstrukturAuditLog::create([
                'instruktur_id'       => $instruktur->id,
                'user_id'             => auth()->id(),
                'aksi'                => $aksiLog,
                'ringkasan_perubahan' => $ringkasan,
                'data_lama'           => $snapshotLama,
                'data_baru'           => $snapshotBaru,
            ]);
        });

        $redirect = redirect()->route('admin.instruktur.index')
            ->with('success', 'Data instruktur berhasil diperbarui dan tercatat pada riwayat perubahan.');

        $freshAfterUpdate = $instruktur->fresh();
        if ($freshAfterUpdate && in_array($freshAfterUpdate->status_ketersediaan, ['cuti', 'izin'], true)) {
            $jadwalAktif = \App\Models\Jadwal::query()
                ->where('instruktur_id', $freshAfterUpdate->id)
                ->whereIn('status', ['terjadwal', 'berlangsung'])
                ->get();

            $bentrokCutiCount = $jadwalAktif->filter(function (\App\Models\Jadwal $j) use ($freshAfterUpdate): bool {
                $tgl = $j->is_pola_mingguan ? null : $j->tanggal?->toDateString();

                return $freshAfterUpdate->sedangCutiAtauIzinPada(
                    $tgl,
                    $j->hari,
                    $j->periode_mulai?->toDateString(),
                    $j->periode_selesai?->toDateString()
                );
            })->count();

            if ($bentrokCutiCount > 0) {
                $redirect->with(
                    'warning',
                    "Instruktur {$freshAfterUpdate->nama_lengkap} memiliki {$bentrokCutiCount} jadwal aktif yang beririsan dengan masa {$freshAfterUpdate->ketersediaan_label}. Silakan tinjau atau alihkan jadwal terkait pada menu Jadwal Mengajar."
                );
            }
        }

        return $redirect;
    }

    public function storeSertifikasi(Request $request, Instruktur $instruktur)
    {
        Gate::authorize('update', $instruktur);

        $validator = Validator::make(
            $request->all(),
            [
                'nama_sertifikasi' => ['required', 'string', 'max:150'],
                'berlaku_sampai'   => ['nullable', 'date'],
                'file_sertifikasi' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                'keterangan'       => ['nullable', 'string', 'max:255'],
            ],
            $this->sertifikasiValidationMessages()
        );

        if ($validator->fails()) {
            return redirect($this->sertifikasiRedirectUrl($request, $instruktur))
                ->withErrors($validator, 'sertifikasiStore')
                ->withInput($request->except('file_sertifikasi'));
        }

        $validated = $validator->validated();

        $sertifikasi = DB::transaction(function () use ($request, $instruktur, $validated) {
            $filePath = null;
            $fileName = null;
            if ($request->hasFile('file_sertifikasi')) {
                $file     = $request->file('file_sertifikasi');
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('instruktur/sertifikasi', 'local');
            }

            $sertifikasi = $instruktur->sertifikasis()->create([
                'nama_sertifikasi' => $validated['nama_sertifikasi'],
                'berlaku_sampai'   => $validated['berlaku_sampai'] ?? null,
                'file_path'        => $filePath,
                'file_name'        => $fileName,
                'keterangan'       => $validated['keterangan'] ?? null,
            ]);

            $instruktur->syncPrimarySertifikasiSummary();

            InstrukturAuditLog::create([
                'instruktur_id'       => $instruktur->id,
                'user_id'             => auth()->id(),
                'aksi'                => 'sertifikasi_diubah',
                'ringkasan_perubahan' => "Menambahkan sertifikasi: {$sertifikasi->nama_sertifikasi}.",
                'data_lama'           => null,
                'data_baru'           => [
                    'nama_sertifikasi' => $sertifikasi->nama_sertifikasi,
                    'berlaku_sampai'   => $sertifikasi->berlaku_sampai?->format('Y-m-d'),
                    'file_name'        => $sertifikasi->file_name,
                ],
            ]);

            return $sertifikasi;
        });

        return redirect($this->sertifikasiRedirectUrl($request, $instruktur))
            ->with('success', "Sertifikat {$sertifikasi->nama_sertifikasi} berhasil ditambahkan.");
    }

    public function updateSertifikasi(Request $request, Instruktur $instruktur, InstrukturSertifikasi $sertifikasi)
    {
        Gate::authorize('update', $instruktur);
        abort_unless($sertifikasi->instruktur_id === $instruktur->id, 404);

        $validator = Validator::make(
            $request->all(),
            [
                'nama_sertifikasi' => ['required', 'string', 'max:150'],
                'berlaku_sampai'   => ['nullable', 'date'],
                'file_sertifikasi' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                'keterangan'       => ['nullable', 'string', 'max:255'],
            ],
            $this->sertifikasiValidationMessages()
        );

        if ($validator->fails()) {
            return redirect($this->sertifikasiRedirectUrl($request, $instruktur))
                ->withErrors($validator, 'sertifikasiUpdate')
                ->withInput(array_merge($request->except('file_sertifikasi'), [
                    '_edit_sertifikasi_id' => $sertifikasi->id,
                ]));
        }

        $validated = $validator->validated();
        $dataLama  = [
            'nama_sertifikasi' => $sertifikasi->nama_sertifikasi,
            'berlaku_sampai'   => $sertifikasi->berlaku_sampai?->format('Y-m-d'),
            'file_name'        => $sertifikasi->file_name,
            'keterangan'       => $sertifikasi->keterangan,
        ];

        DB::transaction(function () use ($request, $instruktur, $sertifikasi, $validated, $dataLama) {
            $oldPath  = $sertifikasi->file_path;
            $filePath = $oldPath;
            $fileName = $sertifikasi->file_name;

            if ($request->hasFile('file_sertifikasi')) {
                $file     = $request->file('file_sertifikasi');
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('instruktur/sertifikasi', 'local');
            }

            $sertifikasi->update([
                'nama_sertifikasi' => $validated['nama_sertifikasi'],
                'berlaku_sampai'   => $validated['berlaku_sampai'] ?? null,
                'file_path'        => $filePath,
                'file_name'        => $fileName,
                'keterangan'       => $validated['keterangan'] ?? null,
            ]);

            if ($oldPath && $oldPath !== $filePath) {
                $this->deleteSertifikasiFileIfUnused($oldPath, $sertifikasi->id);
            }

            $instruktur->syncPrimarySertifikasiSummary();

            InstrukturAuditLog::create([
                'instruktur_id'       => $instruktur->id,
                'user_id'             => auth()->id(),
                'aksi'                => 'sertifikasi_diubah',
                'ringkasan_perubahan' => "Memperbarui sertifikasi: {$sertifikasi->nama_sertifikasi}.",
                'data_lama'           => $dataLama,
                'data_baru'           => [
                    'nama_sertifikasi' => $sertifikasi->nama_sertifikasi,
                    'berlaku_sampai'   => $sertifikasi->berlaku_sampai?->format('Y-m-d'),
                    'file_name'        => $sertifikasi->file_name,
                    'keterangan'       => $sertifikasi->keterangan,
                ],
            ]);
        });

        return redirect($this->sertifikasiRedirectUrl($request, $instruktur))
            ->with('success', "Sertifikat {$sertifikasi->nama_sertifikasi} berhasil diperbarui.");
    }

    public function destroySertifikasi(Request $request, Instruktur $instruktur, InstrukturSertifikasi $sertifikasi)
    {
        Gate::authorize('update', $instruktur);
        abort_unless($sertifikasi->instruktur_id === $instruktur->id, 404);

        $nama          = $sertifikasi->nama_sertifikasi;
        $oldPath       = $sertifikasi->file_path;
        $sertifikasiId = $sertifikasi->id;

        DB::transaction(function () use ($instruktur, $sertifikasi, $nama, $oldPath, $sertifikasiId) {
            $dataLama = [
                'nama_sertifikasi' => $sertifikasi->nama_sertifikasi,
                'berlaku_sampai'   => $sertifikasi->berlaku_sampai?->format('Y-m-d'),
                'file_name'        => $sertifikasi->file_name,
            ];

            $sertifikasi->delete();
            $this->deleteSertifikasiFileIfUnused($oldPath, $sertifikasiId);
            $instruktur->syncPrimarySertifikasiSummary();

            InstrukturAuditLog::create([
                'instruktur_id'       => $instruktur->id,
                'user_id'             => auth()->id(),
                'aksi'                => 'sertifikasi_diubah',
                'ringkasan_perubahan' => "Menghapus sertifikasi: {$nama}.",
                'data_lama'           => $dataLama,
                'data_baru'           => null,
            ]);
        });

        return redirect($this->sertifikasiRedirectUrl($request, $instruktur))
            ->with('success', "Sertifikat {$nama} berhasil dihapus.");
    }

    public function dokumenSertifikasi(Request $request, Instruktur $instruktur, InstrukturSertifikasi $sertifikasi)
    {
        Gate::authorize('view', $instruktur);
        abort_unless($sertifikasi->instruktur_id === $instruktur->id, 404);
        $disk = $sertifikasi->resolveStorageDisk();
        abort_if($disk === null, 404);

        $safeName = str_replace(['"', '\\', '/', "\r", "\n"], '_', $sertifikasi->display_file_name ?: basename($sertifikasi->file_path));

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download($sertifikasi->file_path, $safeName);
        }

        return Storage::disk($disk)->response($sertifikasi->file_path, $safeName);
    }

    public function toggleStatus(Instruktur $instruktur)
    {
        Gate::authorize('toggleStatus', $instruktur);

        $oldStatus = $instruktur->status;
        $newStatus = $oldStatus === 'aktif' ? 'nonaktif' : 'aktif';
        $instruktur->update(['status' => $newStatus]);
        $instruktur->user?->updateQuietly(['is_active' => $newStatus === 'aktif']);
        if ($newStatus !== 'aktif') {
            $instruktur->user?->revokeActiveSessions();
        }

        InstrukturAuditLog::create([
            'instruktur_id'       => $instruktur->id,
            'user_id'             => auth()->id(),
            'aksi'                => 'status_diubah',
            'ringkasan_perubahan' => "Status instruktur diubah dari {$oldStatus} menjadi {$newStatus}.",
            'data_lama'           => ['status' => $oldStatus],
            'data_baru'           => ['status' => $newStatus],
        ]);

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Instruktur {$instruktur->nama_lengkap} berhasil {$label}.");
    }

    public function destroy(Instruktur $instruktur)
    {
        Gate::authorize('delete', $instruktur);

        DB::transaction(function () use ($instruktur) {
            $instruktur->update(['status' => 'nonaktif']);
            $instruktur->user?->updateQuietly(['is_active' => false]);
            $instruktur->user?->revokeActiveSessions();

            InstrukturAuditLog::create([
                'instruktur_id'       => $instruktur->id,
                'user_id'             => auth()->id(),
                'aksi'                => 'dihapus_sementara',
                'ringkasan_perubahan' => "Instruktur {$instruktur->nama_lengkap} dinonaktifkan melalui Soft Delete agar riwayat kinerja tetap terjaga.",
                'data_lama'           => ['status' => 'aktif', 'deleted_at' => null],
                'data_baru'           => ['status' => 'nonaktif', 'deleted_at' => now()->toDateTimeString()],
            ]);

            $instruktur->delete();
        });

        return redirect()->route('admin.instruktur.index')
            ->with('success', "Data instruktur {$instruktur->nama_lengkap} berhasil diarsipkan (Soft Delete) sehingga laporan historis tetap aman.");
    }

    public function restore(int $id)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin'), 403);

        $instruktur = Instruktur::withTrashed()->findOrFail($id);
        $instruktur->restore();
        $instruktur->update(['status' => 'aktif']);
        $instruktur->user?->updateQuietly(['is_active' => true]);

        InstrukturAuditLog::create([
            'instruktur_id'       => $instruktur->id,
            'user_id'             => auth()->id(),
            'aksi'                => 'status_diubah',
            'ringkasan_perubahan' => "Instruktur {$instruktur->nama_lengkap} dipulihkan dari arsip Soft Delete menjadi aktif.",
            'data_lama'           => ['status' => 'nonaktif'],
            'data_baru'           => ['status' => 'aktif', 'deleted_at' => null],
        ]);

        return back()->with('success', "Instruktur {$instruktur->nama_lengkap} berhasil dipulihkan.");
    }

    private function sertifikasiRedirectUrl(Request $request, Instruktur $instruktur): string
    {
        $target = $request->input('redirect_to') === 'edit'
            ? route('admin.instruktur.edit', $instruktur)
            : route('admin.instruktur.show', $instruktur);

        return $target . '#sertifikasi';
    }

    private function deleteSertifikasiFileIfUnused(?string $filePath, ?int $ignoreSertifikasiId = null): void
    {
        if (empty($filePath)) {
            return;
        }

        $stillReferenced = InstrukturSertifikasi::query()
            ->where('file_path', $filePath)
            ->when($ignoreSertifikasiId !== null, fn ($q) => $q->where('id', '!=', $ignoreSertifikasiId))
            ->exists();

        if (! $stillReferenced) {
            Storage::disk('local')->delete($filePath);
            Storage::disk('public')->delete($filePath);
        }
    }

    private function sertifikasiValidationMessages(): array
    {
        return [
            'nama_sertifikasi.required' => 'Nama sertifikasi wajib diisi.',
            'nama_sertifikasi.max'      => 'Nama sertifikasi maksimal 150 karakter.',
            'berlaku_sampai.date'       => 'Format tanggal berlaku sampai tidak valid.',
            'file_sertifikasi.mimes'    => 'Dokumen sertifikasi harus berformat PDF, JPG, PNG, atau WebP.',
            'file_sertifikasi.max'      => 'Ukuran dokumen sertifikasi maksimal 5MB.',
            'keterangan.max'            => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
