<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'foto_profil',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (! empty($user->email)) {
                $user->email = mb_strtolower(trim((string) $user->email));
            }
        });

        // Sinkronisasi otomatis perubahan akun instruktur ke tabel instrukturs
        static::saved(function (User $user) {
            if (! $user->is_active) {
                $user->revokeActiveSessions();
            }

            if ($user->role === 'instruktur') {
                $instruktur = Instruktur::withTrashed()
                    ->where('user_id', $user->id)
                    ->first();

                if ($instruktur) {
                    $wasTrashed = $instruktur->trashed();
                    $oldStatus  = $instruktur->status;
                    $newStatus  = $user->is_active ? 'aktif' : 'nonaktif';

                    if ($wasTrashed && $user->is_active) {
                        $instruktur->restore();
                    }

                    $payload = [
                        'user_id'      => $user->id,
                        'nama_lengkap' => $user->name,
                        'email'        => $user->email,
                        'status'       => $newStatus,
                    ];
                    if (! empty($user->foto_profil)) {
                        $payload['foto_profil'] = $user->foto_profil;
                    }
                    $instruktur->updateQuietly($payload);

                    if (($wasTrashed && $user->is_active) || $oldStatus !== $newStatus) {
                        InstrukturAuditLog::create([
                            'instruktur_id'       => $instruktur->id,
                            'user_id'             => auth()->id(),
                            'aksi'                => 'status_diubah',
                            'ringkasan_perubahan' => $wasTrashed && $user->is_active
                                ? "Profil instruktur {$instruktur->nama_lengkap} dipulihkan dari arsip karena akun login diaktifkan."
                                : "Status instruktur disinkronkan dari status akun login ({$oldStatus} menjadi {$newStatus}).",
                            'data_lama'           => ['status' => $oldStatus, 'deleted_at' => $wasTrashed ? 'trashed' : null],
                            'data_baru'           => ['status' => $newStatus, 'deleted_at' => $instruktur->trashed() ? 'trashed' : null],
                        ]);
                    }
                }
            }
        });

        // Lindungi histori operasional saat akun user instruktur dihapus
        static::deleting(function (User $user) {
            $userFoto = $user->foto_profil;
            $instruktur = Instruktur::withTrashed()->where('user_id', $user->id)->first();
            if (! $instruktur) {
                if (! empty($userFoto)) {
                    DB::afterCommit(function () use ($userFoto, $user) {
                        $stillUsed = User::where('id', '!=', $user->id)->where('foto_profil', $userFoto)->exists()
                            || Instruktur::withTrashed()->where('foto_profil', $userFoto)->exists();
                        if (! $stillUsed) {
                            Storage::disk('public')->delete($userFoto);
                        }
                    });
                }

                return;
            }

            if ($instruktur->hasOperationalHistory()) {
                $statusSebelumnya = $instruktur->status;

                $instruktur->updateQuietly([
                    'user_id' => null,
                    'status'  => 'nonaktif',
                ]);

                if (! $instruktur->trashed()) {
                    $instruktur->delete();
                }

                InstrukturAuditLog::create([
                    'instruktur_id'       => $instruktur->id,
                    'user_id'             => auth()->id(),
                    'aksi'                => 'akun_dihapus',
                    'ringkasan_perubahan' => 'Akun login terkait dihapus; profil instruktur diarsipkan untuk menjaga riwayat operasional.',
                    'data_lama'           => ['user_id' => $user->id, 'status' => $statusSebelumnya],
                    'data_baru'           => ['user_id' => null, 'status' => 'nonaktif'],
                ]);
            } else {
                $fotoInstruktur = $instruktur->foto_profil;
                $fileSertUtama  = $instruktur->file_sertifikasi;
                $certPaths      = Schema::hasTable('instruktur_sertifikasis')
                    ? $instruktur->sertifikasis()->pluck('file_path')->filter()->unique()->values()->all()
                    : [];

                if (Schema::hasTable('instruktur_sertifikasis')) {
                    $instruktur->sertifikasis()->delete();
                }
                if (Schema::hasTable('instruktur_cutis')) {
                    $instruktur->cutis()->delete();
                }
                if (Schema::hasTable('instruktur_audit_logs')) {
                    $instruktur->auditLogs()->delete();
                }

                $instruktur->forceDelete();

                DB::afterCommit(function () use ($userFoto, $fotoInstruktur, $fileSertUtama, $certPaths, $user) {
                    foreach (array_unique(array_filter([$userFoto, $fotoInstruktur])) as $fotoPath) {
                        $stillUsed = User::where('id', '!=', $user->id)->where('foto_profil', $fotoPath)->exists()
                            || Instruktur::withTrashed()->where('foto_profil', $fotoPath)->exists();
                        if (! $stillUsed) {
                            Storage::disk('public')->delete($fotoPath);
                        }
                    }

                    $allCertPaths = array_unique(array_filter(array_merge([$fileSertUtama], $certPaths)));
                    foreach ($allCertPaths as $certPath) {
                        $stillReferenced = (Schema::hasTable('instruktur_sertifikasis')
                                && InstrukturSertifikasi::where('file_path', $certPath)->exists())
                            || Instruktur::withTrashed()->where('file_sertifikasi', $certPath)->exists();

                        if (! $stillReferenced) {
                            Storage::disk('local')->delete($certPath);
                            Storage::disk('public')->delete($certPath);
                        }
                    }
                });
            }
        });
    }

    // Membuat atau menyinkronkan profil Instruktur untuk akun ber-role instruktur.
    public function syncInstrukturProfile(?string $bidangKeahlian = null): ?Instruktur
    {
        if ($this->role !== 'instruktur') {
            return null;
        }

        $instruktur = Instruktur::withTrashed()
            ->where(function ($query) {
                $query->where('user_id', $this->id);
                if (! empty($this->email)) {
                    $query->orWhere(function ($sub) {
                        $sub->whereNull('user_id')->whereRaw('LOWER(email) = ?', [mb_strtolower($this->email)]);
                    });
                }
            })
            ->first();

        if ($instruktur) {
            $wasTrashed = $instruktur->trashed();
            $oldStatus  = $instruktur->status;
            $newStatus  = $this->is_active ? 'aktif' : 'nonaktif';

            if ($wasTrashed && $this->is_active) {
                $instruktur->restore();
            }
            $payload = [
                'user_id'      => $this->id,
                'nama_lengkap' => $this->name,
                'email'        => $this->email,
                'status'       => $newStatus,
            ];
            if (! empty($this->foto_profil)) {
                $payload['foto_profil'] = $this->foto_profil;
            }
            $instruktur->updateQuietly($payload);

            if (($wasTrashed && $this->is_active) || $oldStatus !== $newStatus) {
                InstrukturAuditLog::create([
                    'instruktur_id'       => $instruktur->id,
                    'user_id'             => auth()->id(),
                    'aksi'                => 'status_diubah',
                    'ringkasan_perubahan' => $wasTrashed && $this->is_active
                        ? "Profil instruktur {$instruktur->nama_lengkap} dipulihkan dari arsip saat sinkronisasi akun login."
                        : "Status instruktur disinkronkan dari akun login ({$oldStatus} menjadi {$newStatus}).",
                    'data_lama'           => ['status' => $oldStatus, 'deleted_at' => $wasTrashed ? 'trashed' : null],
                    'data_baru'           => ['status' => $newStatus, 'deleted_at' => $instruktur->trashed() ? 'trashed' : null],
                ]);
            }

            return $instruktur;
        }

        $kode = Instruktur::generateNextCode();

        return Instruktur::create([
            'user_id'             => $this->id,
            'kode_instruktur'     => $kode,
            'nama_lengkap'        => $this->name,
            'email'               => $this->email,
            'foto_profil'         => $this->foto_profil,
            'bidang_keahlian'     => $bidangKeahlian,
            'status'              => $this->is_active ? 'aktif' : 'nonaktif',
            'status_ketersediaan' => 'tersedia',
        ]);
    }

    public function getInisialAttribute(): string
    {
        $words  = preg_split('/\s+/', trim($this->name ?? 'U'));
        $first  = mb_substr($words[0] ?? 'U', 0, 1);
        $second = isset($words[1]) ? mb_substr($words[1], 0, 1) : '';

        return mb_strtoupper($first . $second);
    }

    public function getDefaultAvatarUrlAttribute(): string
    {
        $initials = htmlspecialchars($this->inisial, ENT_QUOTES, 'UTF-8');
        $bgColor  = match ($this->role) {
            'admin'      => '#033DF6',
            'supervisor' => '#1D4ED8',
            default      => '#0F766E',
        };

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<rect width="128" height="128" rx="64" fill="' . $bgColor . '"/>'
            . '<text x="50%" y="52%" dominant-baseline="middle" text-anchor="middle" fill="#FFFFFF" font-family="sans-serif" font-size="48" font-weight="600">'
            . $initials
            . '</text></svg>';

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    public function getFotoUrlAttribute(): string
    {
        $path = $this->foto_profil;
        if (empty($path) && $this->role === 'instruktur' && $this->relationLoaded('instruktur')) {
            $path = $this->instruktur?->foto_profil;
        } elseif (empty($path) && $this->role === 'instruktur') {
            $path = $this->instruktur()->value('foto_profil');
        }

        $normalized = ltrim(preg_replace('#^storage/#i', '', str_replace('\\', '/', trim((string) $path))), '/');
        if ($normalized !== '' && !str_contains($normalized, '..') && Storage::disk('public')->exists($normalized)) {
            $version = null;
            try {
                $version = Storage::disk('public')->lastModified($normalized) ?: $this->updated_at?->timestamp;
            } catch (\Throwable) {
                $version = $this->updated_at?->timestamp;
            }

            $baseUrl = asset('storage/' . $normalized);

            return $version ? $baseUrl . '?v=' . $version : $baseUrl;
        }

        return $this->default_avatar_url;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isInstruktur(): bool
    {
        return $this->role === 'instruktur';
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'      => 'Administrator',
            'supervisor' => 'Supervisor',
            'instruktur' => 'Instruktur',
            default      => ucfirst($this->role),
        };
    }

    public function instruktur(): HasOne
    {
        return $this->hasOne(Instruktur::class)->withTrashed();
    }

    public function importHistories(): HasMany
    {
        return $this->hasMany(ImportHistory::class);
    }

    public function revokeActiveSessions(): void
    {
        if (! empty($this->remember_token)) {
            $this->forceFill(['remember_token' => null])->saveQuietly();
        }

        try {
            $table = config('session.table', 'sessions');
            if (Schema::hasTable($table)) {
                DB::table($table)->where('user_id', $this->id)->delete();
            }
        } catch (\Throwable) {
            // Abaikan jika tabel session tidak tersedia pada driver non-database
        }
    }

    public function revokeOtherSessions(?string $currentSessionId = null, ?string $plainPassword = null): void
    {
        if (! empty($this->remember_token)) {
            $this->forceFill(['remember_token' => null])->saveQuietly();
        }

        if ($plainPassword !== null && auth()->check() && (int) auth()->id() === (int) $this->id) {
            try {
                \Illuminate\Support\Facades\Auth::logoutOtherDevices($plainPassword);
            } catch (\Throwable) {
                // Abaikan bila guard/session pengujian tidak mendukung rotasi password hash
            }
        }

        try {
            $table = config('session.table', 'sessions');
            if (Schema::hasTable($table)) {
                DB::table($table)
                    ->where('user_id', $this->id)
                    ->when(
                        $currentSessionId !== null && $currentSessionId !== '',
                        fn ($q) => $q->where('id', '!=', $currentSessionId)
                    )
                    ->delete();
            }
        } catch (\Throwable) {
            // Abaikan jika tabel session tidak tersedia pada driver non-database
        }
    }
}
