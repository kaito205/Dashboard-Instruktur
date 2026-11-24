<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Laporan Aktivitas Mengajar - {{ $instruktur->nama_lengkap }}</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #111827;
            margin: 24px;
            font-size: 12px;
            line-height: 1.5;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
        }
        .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }
        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 24px;
            margin: 14px 0 18px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
        }
        .muted {
            color: #4b5563;
            font-size: 11px;
        }
        @media print {
            .toolbar {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>Pratinjau Cetak / Simpan PDF</strong>
            <span class="muted">Gunakan tombol Cetak atau Ctrl+P lalu pilih "Save as PDF".</span>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('instruktur.aktivitas.index', request()->query()) }}" class="btn">Kembali</a>
            <button type="button" onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF</button>
        </div>
    </div>

    <h1>Laporan Aktivitas Mengajar Instruktur</h1>
    <p class="muted">LPK Cipta Tungga Indonesia Ciamis &middot; Dicetak {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>

    <div class="meta">
        <div><strong>Instruktur:</strong> {{ $instruktur->nama_lengkap }} ({{ $instruktur->kode_instruktur }})</div>
        <div><strong>Periode Kepatuhan:</strong> {{ \Carbon\Carbon::parse($occDari)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($occSampai)->format('d/m/Y') }}</div>
        <div><strong>Kepatuhan Pelaporan:</strong> {{ $persenKepatuhan }}% ({{ $sesiTerlaporPeriode }}/{{ $totalSesiPeriode }} sesi terlapor)</div>
        <div><strong>Total Baris Laporan:</strong> {{ $riwayatAktivitas->count() }} laporan</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kelas &amp; Materi</th>
                <th>Jam &amp; Durasi Aktual</th>
                <th>Peserta</th>
                <th>Status Kelas</th>
                <th>Konfirmasi &amp; Verifikasi</th>
                <th>Kendala &amp; Hasil</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayatAktivitas as $ra)
                <tr>
                    <td>
                        {{ $ra->tanggal ? $ra->tanggal->copy()->locale('id')->translatedFormat('l, d/m/Y') : '-' }}
                    </td>
                    <td>
                        <strong>{{ $ra->jadwal?->nama_kelas ?? '-' }}</strong><br />
                        <span class="muted">{{ $ra->materi_disampaikan }}</span>
                    </td>
                    <td>
                        {{ $ra->jam_mulai_aktual ? substr($ra->jam_mulai_aktual, 0, 5) : '-' }} - {{ $ra->jam_selesai_aktual ? substr($ra->jam_selesai_aktual, 0, 5) : '-' }} WIB<br />
                        <span class="muted">Durasi: {{ $ra->durasi_aktual_label }}</span>
                    </td>
                    <td>{{ $ra->jumlah_peserta_hadir ?? 0 }} orang</td>
                    <td>{{ $ra->status_kelas_info['label'] }}</td>
                    <td>
                        {{ $ra->status_label }}<br />
                        <span class="muted">{{ $ra->verifikasi_label }}</span>
                        @if ($ra->eskalasi_info)
                            <br /><span class="muted">{{ $ra->eskalasi_info['label'] }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($ra->kendala_kegiatan)
                            <div><strong>Kendala:</strong> {{ $ra->kendala_kegiatan }}</div>
                        @endif
                        @if ($ra->hasil_pembelajaran)
                            <div><strong>Hasil:</strong> {{ $ra->hasil_pembelajaran }}</div>
                        @endif
                        @if (! $ra->kendala_kegiatan && ! $ra->hasil_pembelajaran)
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding: 16px;">Belum ada data laporan aktivitas mengajar pada filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
