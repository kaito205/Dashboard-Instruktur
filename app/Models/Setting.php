<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public $timestamps = true;

    /**
     * Penyimpanan memo di memori per-request agar tidak memicu query berulang ke tabel cache/database.
     */
    protected static ?array $memoizedSettings = null;
    protected static ?string $memoContextId = null;

    protected static function ensureFreshRequestContext(): void
    {
        $appObj = app();
        $reqId  = $appObj->bound('request') ? (string) spl_object_id($appObj->make('request')) : 'cli';
        $ctxId  = spl_object_id($appObj) . ':' . $reqId;

        if (static::$memoContextId !== $ctxId) {
            static::$memoContextId    = $ctxId;
            static::$memoizedSettings = null;
        }
    }

    public static function get(string $key, $default = null): mixed
    {
        static::ensureFreshRequestContext();

        if (static::$memoizedSettings === null) {
            try {
                static::$memoizedSettings = static::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                static::$memoizedSettings = [];
            }
        }

        if (array_key_exists($key, static::$memoizedSettings)) {
            return static::$memoizedSettings[$key];
        }

        return $default;
    }

    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        if (static::$memoizedSettings !== null) {
            static::$memoizedSettings[$key] = $value;
        }

        Cache::forget("setting_{$key}");
    }

    public static function clearMemo(): void
    {
        static::$memoizedSettings = null;
    }

    /**
     * Mengambil identitas aplikasi & lembaga dari tabel settings (termemoisasi per request).
     */
    public static function getAppIdentity(): array
    {
        $appName = trim((string) static::get('app_name', ''));
        $rawDesc = static::get('app_description');
        $lpkName = trim((string) static::get('lpk_name', ''));
        $rawAlamat = static::get('lpk_alamat');
        $defaultPeriode = trim((string) static::get('default_periode', 'bulan_ini'));

        if (!in_array($defaultPeriode, ['hari_ini', 'minggu_ini', 'bulan_ini', 'tahun_ini'], true)) {
            $defaultPeriode = 'bulan_ini';
        }

        return [
            'app_name'        => $appName !== '' ? $appName : 'Dashboard Instruktur LPK CTI',
            'app_description' => $rawDesc !== null
                ? trim((string) $rawDesc)
                : 'Sistem Monitoring dan Evaluasi Kinerja Instruktur',
            'lpk_name'        => $lpkName !== '' ? $lpkName : 'LPK Cipta Tungga Indonesia Ciamis',
            'lpk_alamat'      => $rawAlamat !== null
                ? trim((string) $rawAlamat)
                : 'Ciamis, Jawa Barat',
            'default_periode' => $defaultPeriode,
        ];
    }
}
