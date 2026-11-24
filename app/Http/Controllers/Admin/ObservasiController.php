<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesAdminFilters;
use App\Http\Controllers\Controller;
use App\Models\AktivitasMengajar;
use App\Models\Instruktur;
use App\Models\Jadwal;
use App\Models\ObservasiSupervisor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservasiController extends Controller
{
    use ResolvesAdminFilters;

    protected string $viewPrefix  = 'pages.admin.observasi';
    protected string $routePrefix = 'admin.observasi';

    protected function authorizeObservasi(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('admin', 'supervisor'), 403);
    }

    protected function authorizeObservasiOwnership(ObservasiSupervisor $observasi): void
    {
        $this->authorizeObservasi();

        if (! auth()->user()->hasRole('admin')) {
            abort_unless(
                (int) $observasi->supervisor_id === (int) auth()->id(),
                403,
                'Anda hanya dapat mengubah atau menghapus hasil observasi milik Anda sendiri.'
            );
        }
    }

    public function index(Request $request)
    {
        $this->authorizeObservasi();

        $instrukturId = (string) ($request->input('instruktur') ?? '');
        [$dari, $sampai] = $this->resolveDateRange($request);

        $observasis = ObservasiSupervisor::with(['instruktur', 'supervisor', 'jadwal'])
            ->when($instrukturId !== '', fn ($q) => $q->where('instruktur_id', $instrukturId))
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $instrukturs     = Instruktur::aktif()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kode_instruktur']);
        $jadwals         = Jadwal::with('instruktur')->where('status', '!=', 'dibatalkan')->orderBy('nama_kelas')->get();
        $indikatorLabels = ObservasiSupervisor::getIndikatorLabels();
        $editObservasi   = $request->filled('edit') ? ObservasiSupervisor::find($request->input('edit')) : null;
        if ($editObservasi && ! auth()->user()->hasRole('admin') && (int) $editObservasi->supervisor_id !== (int) auth()->id()) {
            $editObservasi = null;
        }

        return view($this->viewPrefix . '.index', compact(
            'observasis',
            'instrukturs',
            'jadwals',
            'indikatorLabels',
            'instrukturId',
            'dari',
            'sampai',
            'editObservasi'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeObservasi();

        $validated = $request->validate([
            'instruktur_id'     => ['required', 'integer', 'exists:instrukturs,id'],
            'jadwal_id'         => ['nullable', 'integer', 'exists:jadwals,id'],
            'tanggal'           => ['required', 'date'],
            'indikator_1'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_2'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_3'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_4'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_5'       => ['required', 'integer', 'min:1', 'max:5'],
            'catatan_observasi' => ['nullable', 'string', 'max:2000'],
            'rekomendasi'       => ['nullable', 'string', 'max:2000'],
        ], [
            'instruktur_id.required' => 'Instruktur wajib dipilih.',
            'tanggal.required'       => 'Tanggal observasi wajib diisi.',
            'indikator_1.required'   => 'Skor Indikator 1 wajib dipilih (1-5).',
            'indikator_2.required'   => 'Skor Indikator 2 wajib dipilih (1-5).',
            'indikator_3.required'   => 'Skor Indikator 3 wajib dipilih (1-5).',
            'indikator_4.required'   => 'Skor Indikator 4 wajib dipilih (1-5).',
            'indikator_5.required'   => 'Skor Indikator 5 wajib dipilih (1-5).',
        ]);

        $targetInstrukturId = array_key_exists('instruktur_id', $validated)
            ? (int) $validated['instruktur_id']
            : (int) $observasi->instruktur_id;

        if (! empty($validated['jadwal_id'])) {
            $jadwalSelected = Jadwal::find((int) $validated['jadwal_id']);
            if (! $jadwalSelected || (int) $jadwalSelected->instruktur_id !== $targetInstrukturId) {
                return back()
                    ->withInput()
                    ->withErrors(['jadwal_id' => 'Jadwal kelas yang dipilih tidak sesuai dengan instruktur yang diobservasi.']);
            }
        }

        $skorMap = [
            'indikator_1' => (int) $validated['indikator_1'],
            'indikator_2' => (int) $validated['indikator_2'],
            'indikator_3' => (int) $validated['indikator_3'],
            'indikator_4' => (int) $validated['indikator_4'],
            'indikator_5' => (int) $validated['indikator_5'],
        ];

        $rataSkala5    = round(array_sum($skorMap) / 5.0, 2);
        $skorObservasi = ObservasiSupervisor::hitungSkorSkala100($skorMap);

        DB::transaction(function () use ($validated, $skorMap, $rataSkala5, $skorObservasi) {
            ObservasiSupervisor::create([
                'instruktur_id'       => (int) $validated['instruktur_id'],
                'supervisor_id'       => auth()->id(),
                'jadwal_id'           => !empty($validated['jadwal_id']) ? (int) $validated['jadwal_id'] : null,
                'tanggal'             => $validated['tanggal'],
                'indikator_penilaian' => [
                    'skala'             => '1-5',
                    'skor'              => $skorMap,
                    'label_indikator'   => ObservasiSupervisor::getIndikatorLabels(),
                    'rata_rata_skala_5' => $rataSkala5,
                    'rumus_konversi'    => '(rata_rata_skala_5 / 5) * 100',
                ],
                'skor_observasi'      => $skorObservasi,
                'catatan_observasi'   => $validated['catatan_observasi'] ?? null,
                'rekomendasi'         => $validated['rekomendasi'] ?? null,
            ]);
        });

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', "Hasil observasi instruktur berhasil disimpan (Skor Asli: {$rataSkala5}/5.00, Skor Konversi: {$skorObservasi}/100).");
    }

    public function update(Request $request, ObservasiSupervisor $observasi)
    {
        $this->authorizeObservasiOwnership($observasi);

        $validated = $request->validate([
            'instruktur_id'     => ['sometimes', 'required', 'integer', 'exists:instrukturs,id'],
            'jadwal_id'         => ['nullable', 'integer', 'exists:jadwals,id'],
            'tanggal'           => ['required', 'date'],
            'indikator_1'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_2'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_3'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_4'       => ['required', 'integer', 'min:1', 'max:5'],
            'indikator_5'       => ['required', 'integer', 'min:1', 'max:5'],
            'catatan_observasi' => ['nullable', 'string', 'max:2000'],
            'rekomendasi'       => ['nullable', 'string', 'max:2000'],
        ], [
            'instruktur_id.required' => 'Instruktur wajib dipilih.',
            'tanggal.required'       => 'Tanggal observasi wajib diisi.',
            'indikator_1.required'   => 'Skor Indikator 1 wajib dipilih (1-5).',
            'indikator_2.required'   => 'Skor Indikator 2 wajib dipilih (1-5).',
            'indikator_3.required'   => 'Skor Indikator 3 wajib dipilih (1-5).',
            'indikator_4.required'   => 'Skor Indikator 4 wajib dipilih (1-5).',
            'indikator_5.required'   => 'Skor Indikator 5 wajib dipilih (1-5).',
        ]);

        $targetInstrukturId = array_key_exists('instruktur_id', $validated)
            ? (int) $validated['instruktur_id']
            : (int) $observasi->instruktur_id;

        if (! empty($validated['jadwal_id'])) {
            $jadwalSelected = Jadwal::find((int) $validated['jadwal_id']);
            if (! $jadwalSelected || (int) $jadwalSelected->instruktur_id !== $targetInstrukturId) {
                return back()
                    ->withInput()
                    ->withErrors(['jadwal_id' => 'Jadwal kelas yang dipilih tidak sesuai dengan instruktur yang diobservasi.']);
            }
        }

        $skorMap = [
            'indikator_1' => (int) $validated['indikator_1'],
            'indikator_2' => (int) $validated['indikator_2'],
            'indikator_3' => (int) $validated['indikator_3'],
            'indikator_4' => (int) $validated['indikator_4'],
            'indikator_5' => (int) $validated['indikator_5'],
        ];

        $rataSkala5    = round(array_sum($skorMap) / 5.0, 2);
        $skorObservasi = ObservasiSupervisor::hitungSkorSkala100($skorMap);

        DB::transaction(function () use ($observasi, $validated, $skorMap, $rataSkala5, $skorObservasi) {
            $payload = [
                'tanggal'             => $validated['tanggal'],
                'indikator_penilaian' => [
                    'skala'             => '1-5',
                    'skor'              => $skorMap,
                    'label_indikator'   => ObservasiSupervisor::getIndikatorLabels(),
                    'rata_rata_skala_5' => $rataSkala5,
                    'rumus_konversi'    => '(rata_rata_skala_5 / 5) * 100',
                ],
                'skor_observasi'      => $skorObservasi,
                'catatan_observasi'   => $validated['catatan_observasi'] ?? null,
                'rekomendasi'         => $validated['rekomendasi'] ?? null,
            ];

            if (array_key_exists('instruktur_id', $validated)) {
                $payload['instruktur_id'] = (int) $validated['instruktur_id'];
            }
            if (array_key_exists('jadwal_id', $validated)) {
                $payload['jadwal_id'] = !empty($validated['jadwal_id']) ? (int) $validated['jadwal_id'] : null;
            }

            $observasi->update($payload);
        });

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', "Perubahan hasil observasi berhasil disimpan (Skor Asli: {$rataSkala5}/5.00, Skor Konversi: {$skorObservasi}/100).");
    }

    public function destroy(ObservasiSupervisor $observasi)
    {
        $this->authorizeObservasiOwnership($observasi);

        $observasi->delete();

        return back()->with('success', 'Data observasi supervisor berhasil dihapus.');
    }

    public function verifikasiAktivitas(Request $request, AktivitasMengajar $aktivitas)
    {
        $this->authorizeObservasi();

        $validated = $request->validate([
            'status_verifikasi'  => ['required', 'in:menunggu,diverifikasi,perlu_revisi'],
            'catatan_supervisor' => ['nullable', 'string', 'max:1000'],
        ], [
            'status_verifikasi.required' => 'Status verifikasi wajib dipilih.',
            'status_verifikasi.in'       => 'Status verifikasi harus menunggu, diverifikasi, atau perlu_revisi.',
        ]);

        DB::transaction(function () use ($aktivitas, $validated) {
            $aktivitas->update([
                'status_verifikasi'  => $validated['status_verifikasi'],
                'verified_by'        => auth()->id(),
                'verified_at'        => now(),
                'catatan_supervisor' => $validated['catatan_supervisor'] ?? null,
            ]);
        });

        return back()->with('success', "Status verifikasi aktivitas mengajar berhasil diperbarui menjadi: {$aktivitas->fresh()->verifikasi_label}.");
    }
}
