<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Services\OperationalAlertService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewName = 'pages.admin.notifikasi.index';
    protected string $roleName = 'admin';

    public function __construct(
        private readonly OperationalAlertService $alertService
    ) {}

    public function index(Request $request)
    {
        [$dari, $sampai] = $this->resolveDateRange($request);
        $tab    = $request->get('tab') === 'riwayat' ? 'riwayat' : 'aktif';
        $level  = in_array($request->get('level'), ['semua', 'critical', 'warning', 'info'], true)
            ? (string) $request->get('level')
            : 'semua';

        $alerts     = $this->alertService->getSummaryAlerts($dari, $sampai, $this->roleName, auth()->id());
        $activeList = $alerts['items_aktif'] ?? collect();

        if ($level !== 'semua') {
            $activeList = $activeList->where('level', $level)->values();
        }

        return view($this->viewName, compact('alerts', 'activeList', 'dari', 'sampai', 'tab', 'level'));
    }

    public function markReviewed(Request $request)
    {
        $validated = $request->validate([
            'alert_key' => ['required', 'string', 'max:120'],
            'dari'      => ['nullable', 'date'],
            'sampai'    => ['nullable', 'date'],
            'kategori'  => ['nullable', 'string', 'max:60'],
            'level'     => ['nullable', 'in:critical,warning,info'],
            'judul'     => ['nullable', 'string', 'max:200'],
            'ringkasan' => ['nullable', 'string', 'max:255'],
            'url'       => ['nullable', 'string', 'max:255'],
        ]);

        $userId = (int) auth()->id();
        $dari   = $validated['dari'] ?? now()->startOfMonth()->toDateString();
        $sampai = $validated['sampai'] ?? now()->endOfMonth()->toDateString();
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $alerts  = $this->alertService->getSummaryAlerts($dari, $sampai, $this->roleName, $userId);
        $matched = ($alerts['items_aktif'] ?? collect())->firstWhere('alert_key', $validated['alert_key']);

        $rawUrl  = $validated['url'] ?? null;
        $safeUrl = ($rawUrl && (str_starts_with($rawUrl, '/') || str_starts_with($rawUrl, url('/'))))
            ? $rawUrl
            : null;

        $payload = $matched ?? [
            'alert_key' => $validated['alert_key'],
            'kategori'  => $validated['kategori'] ?? 'Notifikasi',
            'level'     => $validated['level'] ?? 'warning',
            'judul'     => $validated['judul'] ?? 'Notifikasi operasional',
            'ringkasan' => $validated['ringkasan'] ?? null,
            'url'       => $safeUrl,
        ];

        $this->alertService->markReviewed($userId, $payload);
        $updated = $this->alertService->getSummaryAlerts($dari, $sampai, $this->roleName, $userId);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'               => true,
                'alert_key'        => $validated['alert_key'],
                'total_peringatan' => $updated['total_peringatan'] ?? 0,
                'total_info'       => $updated['total_info'] ?? 0,
                'total_aktif'      => $updated['total_semua_aktif'] ?? $updated['total_aktif'] ?? $updated['total_peringatan'] ?? 0,
                'total_riwayat'    => $updated['total_riwayat'] ?? 0,
                'message'          => 'Notifikasi ditandai sudah ditinjau.',
            ]);
        }

        return back()->with('success', 'Notifikasi ditandai sudah ditinjau.');
    }

    public function markAllReviewed(Request $request)
    {
        $validated = $request->validate([
            'dari'   => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $userId = (int) auth()->id();
        $dari   = $validated['dari'] ?? now()->startOfMonth()->toDateString();
        $sampai = $validated['sampai'] ?? now()->endOfMonth()->toDateString();
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $count   = $this->alertService->markAllReviewed($userId, $dari, $sampai, $this->roleName);
        $updated = $this->alertService->getSummaryAlerts($dari, $sampai, $this->roleName, $userId);

        $message = $count > 0
            ? "{$count} notifikasi ditandai sudah ditinjau."
            : 'Tidak ada notifikasi aktif saat ini.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok'               => true,
                'marked_count'     => $count,
                'total_peringatan' => $updated['total_peringatan'] ?? 0,
                'total_info'       => $updated['total_info'] ?? 0,
                'total_aktif'      => $updated['total_semua_aktif'] ?? $updated['total_aktif'] ?? $updated['total_peringatan'] ?? 0,
                'total_riwayat'    => $updated['total_riwayat'] ?? 0,
                'message'          => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'alert_key' => ['required', 'string', 'max:120'],
            'dari'      => ['nullable', 'date'],
            'sampai'    => ['nullable', 'date'],
        ]);

        $userId = (int) auth()->id();
        $dari   = $validated['dari'] ?? now()->startOfMonth()->toDateString();
        $sampai = $validated['sampai'] ?? now()->endOfMonth()->toDateString();
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $this->alertService->restoreReviewed($userId, $validated['alert_key']);
        $updated = $this->alertService->getSummaryAlerts($dari, $sampai, $this->roleName, $userId);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'               => true,
                'alert_key'        => $validated['alert_key'],
                'total_peringatan' => $updated['total_peringatan'] ?? 0,
                'total_info'       => $updated['total_info'] ?? 0,
                'total_aktif'      => $updated['total_semua_aktif'] ?? $updated['total_aktif'] ?? $updated['total_peringatan'] ?? 0,
                'total_riwayat'    => $updated['total_riwayat'] ?? 0,
                'message'          => 'Notifikasi dikembalikan ke daftar aktif.',
            ]);
        }

        return back()->with('success', 'Notifikasi dikembalikan ke daftar aktif.');
    }
}
