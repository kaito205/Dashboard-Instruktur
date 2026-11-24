@php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan {{ $jenisLabel }} - {{ $appIdentity['lpk_name'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logocti.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #101828;
            background-color: #F2F4F7;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.4;
        }
        .actions {
            max-width: 1120px;
            margin: 0 auto 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #033DF6;
            background: #033DF6;
            color: #FFFFFF;
            text-decoration: none;
            font-size: 12px;
        }
        .btn:hover {
            background: #022EC2;
        }
        .btn:focus-visible {
            outline: 2px solid #033DF6;
            outline-offset: 2px;
        }
        .btn-outline {
            background: #FFFFFF;
            color: #344054;
            border-color: #D0D5DD;
        }
        .btn-outline:hover {
            background: #F9FAFB;
        }
        .sheet {
            max-width: 1120px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #E4E7EC;
            border-radius: 10px;
            padding: 20px 24px;
        }
        .header {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #033DF6;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #033DF6;
            letter-spacing: 0.01em;
        }
        .header p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #475467;
        }
        .print-info {
            text-align: right;
            font-size: 11px;
            color: #344054;
        }
        .print-info p {
            margin: 2px 0;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 8px 16px;
            background-color: #F9FAFB;
            border: 1px solid #E4E7EC;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .meta-item strong {
            display: block;
            font-size: 10px;
            color: #475467;
            margin-bottom: 2px;
        }
        .meta-item span {
            color: #101828;
            font-weight: 600;
        }
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4px;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #D0D5DD;
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.38;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        th {
            background-color: #F9FAFB;
            font-weight: 600;
            color: #344054;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .num {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .sub {
            display: block;
            margin-top: 2px;
            font-size: 9.5px;
            color: #475467;
            font-weight: 400;
        }
        .badge {
            display: inline-block;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .badge-emerald { background: #ECFDF3; color: #027A48; }
        .badge-blue    { background: #EFF8FF; color: #175CD3; }
        .badge-amber   { background: #FFFAEB; color: #B54708; }
        .badge-red     { background: #FEF3F2; color: #B42318; }
        .badge-gray    { background: #F2F4F6; color: #344054; }
        .footnote {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #E4E7EC;
            font-size: 10px;
            color: #475467;
        }
        .empty-row {
            text-align: center;
            color: #475467;
            padding: 18px 8px;
        }
        @media print {
            body {
                background: #FFFFFF;
                padding: 0;
                margin: 0;
            }
            .actions {
                display: none !important;
            }
            .sheet {
                max-width: none;
                width: 100%;
                border: none;
                border-radius: 0;
                padding: 0;
            }
            .table-wrap {
                overflow: visible;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route($routePrefix . '.laporan.export', $activeFilterParams) }}" class="btn btn-outline">Unduh CSV</a>
        <a href="{{ route($routePrefix . '.laporan.index', $activeFilterParams) }}" class="btn btn-outline">Kembali ke Laporan</a>
    </div>

    <div class="sheet">
        <div class="header">
            <div class="brand">
                <img src="{{ asset('images/logo/logocti.png') }}" alt="Logo {{ $appIdentity['lpk_name'] }}" class="logo">
                <div>
                    <h1>{{ strtoupper($appIdentity['lpk_name']) }}</h1>
                    <p>{{ $appIdentity['app_description'] !== '' ? $appIdentity['app_description'] : 'Laporan Monitoring & Evaluasi Kinerja Instruktur' }}@if($appIdentity['lpk_alamat'] !== '') &middot; {{ $appIdentity['lpk_alamat'] }}@endif</p>
                </div>
            </div>
            <div class="print-info">
                <p><strong>Tanggal Cetak:</strong> {{ now()->format('d/m/Y H:i') }} WIB</p>
                <p><strong>Dicetak Oleh:</strong> {{ auth()->user()?->name ?? 'Administrator' }}</p>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-item">
                <strong>Jenis Laporan</strong>
                <span>Laporan {{ $jenisLabel }}</span>
            </div>
            <div class="meta-item">
                <strong>Periode Data</strong>
                <span>{{ $periodeLabel }}</span>
            </div>
            <div class="meta-item">
                <strong>Cakupan Instruktur</strong>
                <span>{{ $selectedInstruktur ? $selectedInstruktur->nama_lengkap . ' (' . $selectedInstruktur->kode_instruktur . ')' : 'Seluruh Instruktur' }}</span>
            </div>
            @if(!empty($selectedKelas))
            <div class="meta-item">
                <strong>Cakupan Kelas</strong>
                <span>{{ $selectedKelas->display_label }}</span>
            </div>
            @endif
            @if(!empty($statusOptions))
            <div class="meta-item">
                <strong>Filter Status / Kategori</strong>
                <span>{{ $statusFilterLabel !== '' ? $statusFilterLabel : 'Semua Status / Kategori' }}</span>
            </div>
            @endif
            <div class="meta-item">
                <strong>Total Data</strong>
                <span>{{ $records->count() }} baris</span>
            </div>
        </div>

        <div class="table-wrap">
            @if($jenis === 'kinerja')
                <table>
                    <colgroup>
                        <col style="width: 4%">
                        <col style="width: 16%">
                        <col style="width: 11%">
                        <col style="width: 10%">
                        <col style="width: 12%">
                        <col style="width: 12%">
                        <col style="width: 12%">
                        <col style="width: 12%">
                        <col style="width: 11%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Instruktur</th>
                            <th>Bidang Keahlian</th>
                            <th>Jadwal &amp; Jam</th>
                            <th>Kehadiran</th>
                            <th>Aktivitas</th>
                            <th>Evaluasi</th>
                            <th>Observasi</th>
                            <th>Skor Akhir &amp; Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $item)
                            @php
                                $badgeClass = match ($item['kategori'] ?? '') {
                                    'sangat_baik' => 'badge-emerald',
                                    'baik'        => 'badge-blue',
                                    'cukup'       => 'badge-amber',
                                    'kurang'      => 'badge-red',
                                    default       => 'badge-gray',
                                };
                            @endphp
                            <tr>
                                <td class="num">{{ $idx + 1 }}</td>
                                <td>
                                    <strong>{{ $item['instruktur']->nama_lengkap }}</strong>
                                    <span class="sub">{{ $item['instruktur']->kode_instruktur }}</span>
                                </td>
                                <td>{{ $item['instruktur']->bidang_keahlian ?: '-' }}</td>
                                <td class="num">
                                    <strong>{{ (int) $item['total_jadwal'] }} sesi</strong>
                                    <span class="sub">{{ number_format((float) $item['jam_mengajar'], 1, '.', '') }} jam</span>
                                </td>
                                <td class="num">
                                    <strong>{{ $item['skor_kehadiran'] !== null ? number_format((float) $item['skor_kehadiran'], 2) . '%' : '-' }}</strong>
                                    <span class="sub">{{ (int) $item['total_absensi'] > 0 ? $item['total_hadir'] . ' dari ' . $item['total_absensi'] . ' sesi' : 'Belum ada presensi' }}</span>
                                </td>
                                <td class="num">
                                    <strong>{{ $item['skor_aktivitas'] !== null ? number_format((float) $item['skor_aktivitas'], 2) . '%' : '-' }}</strong>
                                    <span class="sub">{{ (int) $item['basis_sesi_aktivitas'] > 0 ? $item['total_aktivitas'] . ' dari ' . $item['basis_sesi_aktivitas'] . ' sesi' : 'Belum ada sesi' }}</span>
                                </td>
                                <td class="num">
                                    <strong>{{ $item['skor_evaluasi'] !== null ? number_format((float) $item['skor_evaluasi'], 2) : '-' }}</strong>
                                    <span class="sub">{{ (int) $item['total_evaluasi'] > 0 && $item['rata_rata_evaluasi_skala5'] !== null ? number_format((float) $item['rata_rata_evaluasi_skala5'], 2) . '/5.00 (' . $item['total_evaluasi'] . ' resp.)' : 'Belum ada evaluasi' }}</span>
                                </td>
                                <td class="num">
                                    <strong>{{ $item['skor_observasi'] !== null ? number_format((float) $item['skor_observasi'], 2) : '-' }}</strong>
                                    <span class="sub">{{ (int) $item['total_observasi'] > 0 && $item['rata_rata_observasi_skala5'] !== null ? number_format((float) $item['rata_rata_observasi_skala5'], 2) . '/5.00 (' . $item['total_observasi'] . ' obs.)' : 'Belum ada observasi' }}</span>
                                </td>
                                <td>
                                    <strong class="num">{{ $item['skor_akhir'] !== null ? number_format((float) $item['skor_akhir'], 2) : '-' }}</strong>
                                    <span class="sub"><span class="badge {{ $badgeClass }}">{{ $item['kategori_label'] }}</span></span>
                                    @if(!empty($item['redistribusi_bobot']))
                                        <span class="sub">Bobot disesuaikan</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty-row">Tidak ada data kinerja pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="footnote">
                    @php
                        $sbStr = \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['batas_sangat_baik'] ?? 85));
                        $bStr  = \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['batas_baik'] ?? 75));
                        $cStr  = \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['batas_cukup'] ?? 60));
                    @endphp
                    Bobot standar kinerja: Kehadiran {{ \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['bobot_kehadiran'] ?? 30)) }}%, Aktivitas {{ \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['bobot_aktivitas'] ?? 20)) }}%, Evaluasi {{ \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['bobot_evaluasi'] ?? 30)) }}%, Observasi {{ \App\Services\KinerjaCalculationService::formatAngka((float) ($ruleConfig['bobot_observasi'] ?? 20)) }}%. Kategori: Sangat Baik (&ge; {{ $sbStr }}), Baik ({{ $bStr }} s.d. &lt; {{ $sbStr }}), Cukup ({{ $cStr }} s.d. &lt; {{ $bStr }}), Kurang (&lt; {{ $cStr }}). Jika salah satu komponen belum memiliki data pada periode terpilih, bobot didistribusikan secara proporsional ke komponen yang tersedia.
                </div>
            @elseif($jenis === 'absensi')
                <table>
                    <colgroup>
                        <col style="width: 4%">
                        <col style="width: 9%">
                        <col style="width: 16%">
                        <col style="width: 8%">
                        <col style="width: 8%">
                        <col style="width: 9%">
                        <col style="width: 10%">
                        <col style="width: 10%">
                        <col style="width: 26%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Instruktur</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Terlambat</th>
                            <th>Status</th>
                            <th>Sumber</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $row)
                            @php
                                $statusBadge = match ($row->status) {
                                    'hadir'       => 'badge-emerald',
                                    'terlambat'   => 'badge-amber',
                                    'izin'        => 'badge-blue',
                                    'tidak_hadir' => 'badge-red',
                                    default       => 'badge-gray',
                                };
                            @endphp
                            <tr>
                                <td class="num">{{ $idx + 1 }}</td>
                                <td class="num">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <strong>{{ $row->instruktur?->nama_lengkap ?? '-' }}</strong>
                                    @if($row->instruktur?->kode_instruktur)
                                        <span class="sub">{{ $row->instruktur->kode_instruktur }}</span>
                                    @endif
                                </td>
                                <td class="num">{{ $row->jam_checkin ? substr((string) $row->jam_checkin, 0, 5) : '-' }}</td>
                                <td class="num">{{ $row->jam_checkout ? substr((string) $row->jam_checkout, 0, 5) : '-' }}</td>
                                <td class="num">{{ (int) ($row->menit_terlambat ?? 0) }} menit</td>
                                <td><span class="badge {{ $statusBadge }}">{{ $row->status_label }}</span></td>
                                <td>{{ $sumberLabels[$row->sumber] ?? ($row->sumber ?: '-') }}</td>
                                <td>{{ $row->keterangan ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty-row">Tidak ada data absensi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($jenis === 'jadwal')
                <table>
                    <colgroup>
                        <col style="width: 4%">
                        <col style="width: 14%">
                        <col style="width: 17%">
                        <col style="width: 21%">
                        <col style="width: 10%">
                        <col style="width: 15%">
                        <col style="width: 7%">
                        <col style="width: 12%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Hari / Tanggal</th>
                            <th>Instruktur</th>
                            <th>Kelas &amp; Materi</th>
                            <th>Waktu</th>
                            <th>Ruangan</th>
                            <th>Peserta</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $row)
                            @php
                                $jadwalBadge = match ($row->status) {
                                    'berlangsung' => 'badge-emerald',
                                    'terjadwal'   => 'badge-blue',
                                    'dibatalkan'  => 'badge-red',
                                    default       => 'badge-gray',
                                };
                            @endphp
                            <tr>
                                <td class="num">{{ $idx + 1 }}</td>
                                <td>{{ $row->tanggal_label }}</td>
                                <td>
                                    <strong>{{ $row->instruktur?->nama_lengkap ?? '-' }}</strong>
                                    @if($row->instrukturAsli)
                                        <span class="sub">Pengalihan dari {{ $row->instrukturAsli->nama_lengkap }}</span>
                                    @endif
                                </td>
                                <td>{{ $row->nama_kelas ?? '-' }} - {{ $row->materi ?: 'Umum' }}</td>
                                <td class="num">{{ substr((string) $row->jam_mulai, 0, 5) }} - {{ substr((string) $row->jam_selesai, 0, 5) }}</td>
                                <td>{{ $row->ruangan_label }}</td>
                                <td class="num">{{ (int) ($row->jumlah_peserta ?? 0) }}</td>
                                <td><span class="badge {{ $jadwalBadge }}">{{ $row->status_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty-row">Tidak ada jadwal mengajar pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($jenis === 'aktivitas')
                <table>
                    <colgroup>
                        <col style="width: 4%">
                        <col style="width: 9%">
                        <col style="width: 15%">
                        <col style="width: 16%">
                        <col style="width: 21%">
                        <col style="width: 14%">
                        <col style="width: 21%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Instruktur</th>
                            <th>Kelas &amp; Peserta</th>
                            <th>Materi Disampaikan</th>
                            <th>Status &amp; Verifikasi</th>
                            <th>Kendala / Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $row)
                            @php
                                $rincianAktivitas = [];
                                if (!empty($row->kendala_kegiatan)) {
                                    $rincianAktivitas[] = 'Kendala: ' . $row->kendala_kegiatan;
                                }
                                if (!empty($row->hasil_pembelajaran)) {
                                    $rincianAktivitas[] = 'Hasil: ' . $row->hasil_pembelajaran;
                                } elseif (!empty($row->catatan)) {
                                    $rincianAktivitas[] = $row->catatan;
                                }
                            @endphp
                            <tr>
                                <td class="num">{{ $idx + 1 }}</td>
                                <td class="num">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <strong>{{ $row->instruktur?->nama_lengkap ?? '-' }}</strong>
                                    @if($row->instruktur?->kode_instruktur)
                                        <span class="sub">{{ $row->instruktur->kode_instruktur }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $row->jadwal?->nama_kelas ?? '-' }} ({{ (int) ($row->jumlah_peserta_hadir ?? 0) }} peserta)
                                    <span class="sub">Durasi: {{ $row->durasi_aktual_label }}</span>
                                </td>
                                <td>{{ $row->materi_disampaikan ?: '-' }}</td>
                                <td>
                                    <strong>Kelas: {{ $row->status_kelas_info['label'] }}</strong>
                                    <span class="sub">{{ $row->status_label }} &middot; {{ $row->verifikasi_label }}</span>
                                </td>
                                <td>{{ !empty($rincianAktivitas) ? implode(' | ', $rincianAktivitas) : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-row">Belum ada aktivitas mengajar pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($jenis === 'evaluasi')
                <table>
                    <colgroup>
                        <col style="width: 4%">
                        <col style="width: 11%">
                        <col style="width: 15%">
                        <col style="width: 12%">
                        <col style="width: 12%">
                        <col style="width: 9%">
                        <col style="width: 9%">
                        <col style="width: 28%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Instruktur</th>
                            <th>Pengisi</th>
                            <th>Kode Kelas / Batch</th>
                            <th>Skala 1-5</th>
                            <th>Nilai (0-100)</th>
                            <th>Kategori &amp; Komentar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $row)
                            @php
                                $evalBadge = match ($row->nilai_label) {
                                    'Sangat Baik' => 'badge-emerald',
                                    'Baik'        => 'badge-blue',
                                    'Cukup'       => 'badge-amber',
                                    'Kurang'      => 'badge-red',
                                    default       => 'badge-gray',
                                };
                            @endphp
                            <tr>
                                <td class="num">{{ $idx + 1 }}</td>
                                <td class="num">{{ $row->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <strong>{{ $row->instruktur?->nama_lengkap ?? '-' }}</strong>
                                    @if($row->instruktur?->kode_instruktur)
                                        <span class="sub">{{ $row->instruktur->kode_instruktur }}</span>
                                    @endif
                                </td>
                                <td>{{ $row->nama_pengisi ?: 'Anonim' }}</td>
                                <td class="num">{{ $row->kodeEvaluasi?->kode ?? ($row->batch_import ?: '-') }}</td>
                                <td class="num">{{ $row->rata_rata_skala5 !== null ? number_format((float) $row->rata_rata_skala5, 2) . ' / 5.00' : '-' }}</td>
                                <td class="num"><strong>{{ $row->total_nilai !== null ? number_format((float) $row->total_nilai, 2) : '-' }}</strong></td>
                                <td>
                                    <span class="badge {{ $evalBadge }}">{{ $row->nilai_label }}</span>
                                    @if($row->komentar_peserta)
                                        <span class="sub">{{ $row->komentar_peserta }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty-row">Tidak ada data evaluasi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($jenis === 'observasi')
                <table>
                    <colgroup>
                        <col style="width: 4%">
                        <col style="width: 9%">
                        <col style="width: 16%">
                        <col style="width: 14%">
                        <col style="width: 9%">
                        <col style="width: 9%">
                        <col style="width: 39%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Instruktur</th>
                            <th>Supervisor</th>
                            <th>Skala 1-5</th>
                            <th>Skor (0-100)</th>
                            <th>Catatan &amp; Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $row)
                            <tr>
                                <td class="num">{{ $idx + 1 }}</td>
                                <td class="num">{{ $row->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <strong>{{ $row->instruktur?->nama_lengkap ?? '-' }}</strong>
                                    @if($row->instruktur?->kode_instruktur)
                                        <span class="sub">{{ $row->instruktur->kode_instruktur }}</span>
                                    @endif
                                </td>
                                <td>{{ $row->supervisor?->name ?? '-' }}</td>
                                <td class="num">{{ $row->rata_rata_skala5 !== null ? number_format((float) $row->rata_rata_skala5, 2) . ' / 5.00' : '-' }}</td>
                                <td class="num"><strong>{{ $row->skor_observasi !== null ? number_format((float) $row->skor_observasi, 2) : '-' }}</strong></td>
                                <td>
                                    {{ $row->catatan_observasi ?: '-' }}
                                    @if($row->rekomendasi)
                                        <span class="sub">Rekomendasi: {{ $row->rekomendasi }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-row">Belum ada data observasi supervisor pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($jenis === 'individual')
                @if(empty($individualReport))
                    <p style="text-align:center;padding:24px;color:#667085;">Pilih satu instruktur pada filter laporan untuk mencetak Rapor Individual.</p>
                @else
                    @php
                        $ind = $individualReport['instruktur'];
                        $indK = $individualReport['kinerja'];
                    @endphp
                    <div style="border:1px solid #E4E7EC;border-radius:8px;padding:12px;margin-bottom:14px;background:#F9FAFB;">
                        <p style="margin:0 0 6px;font-size:13px;font-weight:700;">{{ $ind->nama_lengkap }} ({{ $ind->kode_instruktur }})</p>
                        <p style="margin:0;color:#475467;">
                            Bidang: <strong>{{ $ind->bidang_keahlian ?: '-' }}</strong> |
                            Status: <strong>{{ $ind->status_label }}</strong> |
                            Jadwal: <strong>{{ (int) ($indK['total_jadwal'] ?? 0) }} sesi ({{ number_format((float) ($indK['jam_mengajar'] ?? 0), 1) }} jam)</strong> |
                            Kehadiran: <strong>{{ number_format((float) ($indK['persentase_kehadiran'] ?? 0), 1) }}%</strong> |
                            Skor Akhir: <strong>{{ $indK['skor_akhir'] !== null ? number_format((float) $indK['skor_akhir'], 2) : '-' }} ({{ $indK['kategori_label'] ?? '-' }})</strong>
                        </p>
                    </div>
                    <h3 style="font-size:12px;margin:10px 0 6px;">Sertifikasi Kompetensi ({{ $individualReport['sertifikasis']->count() }})</h3>
                    <table style="margin-bottom:14px;">
                        <thead><tr><th>No</th><th>Nama Sertifikasi</th><th>Nomor</th><th>Penerbit</th><th>Berlaku Sampai</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($individualReport['sertifikasis'] as $i => $s)
                                <tr>
                                    <td class="num">{{ $i + 1 }}</td>
                                    <td>{{ $s->nama_sertifikasi }}</td>
                                    <td class="num">{{ $s->nomor_sertifikat ?: '-' }}</td>
                                    <td>{{ $s->penerbit ?: '-' }}</td>
                                    <td class="num">{{ $s->berlaku_sampai?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $s->status_kedaluwarsa_label ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty-row">Belum ada data sertifikasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <h3 style="font-size:12px;margin:10px 0 6px;">Ringkasan Jadwal &amp; Presensi Periode Berjalan</h3>
                    <table>
                        <thead><tr><th>No</th><th>Tanggal / Hari</th><th>Kelas</th><th>Materi</th><th>Jam</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($individualReport['jadwals'] as $i => $j)
                                <tr>
                                    <td class="num">{{ $i + 1 }}</td>
                                    <td>{{ $j->tanggal_label }}</td>
                                    <td>{{ $j->nama_kelas }}</td>
                                    <td>{{ $j->materi ?: 'Umum' }}</td>
                                    <td class="num">{{ substr((string) $j->jam_mulai, 0, 5) }} - {{ substr((string) $j->jam_selesai, 0, 5) }}</td>
                                    <td>{{ $j->status_label }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty-row">Tidak ada jadwal pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            @elseif($jenis === 'manajerial')
                @php
                    $mKpi = $manajerialReport['kpi'] ?? [];
                    $mDist = $manajerialReport['distribusi_kategori'] ?? [];
                    $mAlerts = $manajerialReport['alerts'] ?? [];
                @endphp
                <table style="margin-bottom:14px;">
                    <thead>
                        <tr>
                            <th>Instruktur Aktif</th>
                            <th>Kelas Diampu</th>
                            <th>Total Jam Mengajar</th>
                            <th>Rata-rata Kehadiran</th>
                            <th>Rata-rata Evaluasi</th>
                            <th>Rata-rata Skor Kinerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="num"><strong>{{ $mKpi['total_instruktur_aktif'] ?? 0 }}</strong> / {{ $mKpi['total_instruktur'] ?? 0 }}</td>
                            <td class="num"><strong>{{ $mKpi['kelas_diampu'] ?? 0 }}</strong> ({{ $mKpi['total_sesi'] ?? 0 }} sesi)</td>
                            <td class="num"><strong>{{ number_format((float) ($mKpi['total_jam_mengajar'] ?? 0), 1) }}</strong> jam</td>
                            <td class="num"><strong>{{ number_format((float) ($mKpi['rata_kehadiran'] ?? 0), 1) }}%</strong></td>
                            <td class="num"><strong>{{ $mKpi['rata_evaluasi'] !== null ? number_format((float) $mKpi['rata_evaluasi'], 2) : '-' }}</strong></td>
                            <td class="num"><strong>{{ $mKpi['rata_skor_kinerja'] !== null ? number_format((float) $mKpi['rata_skor_kinerja'], 2) : '-' }}</strong></td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin:0 0 10px;color:#344054;">
                    Distribusi Kategori: Sangat Baik <strong>{{ $mDist['sangat_baik'] ?? 0 }}</strong> |
                    Baik <strong>{{ $mDist['baik'] ?? 0 }}</strong> |
                    Cukup <strong>{{ $mDist['cukup'] ?? 0 }}</strong> |
                    Kurang <strong>{{ $mDist['kurang'] ?? 0 }}</strong> |
                    Peringatan Aktif: <strong>{{ $mAlerts['total'] ?? 0 }}</strong>
                    (Tanpa Laporan: {{ $mAlerts['kelas_tanpa_laporan'] ?? 0 }}, Sertifikasi: {{ $mAlerts['sertifikasi_hampir_expired'] ?? 0 }}, Di Bawah Standar: {{ $mAlerts['instruktur_dibawah_standar'] ?? 0 }})
                </p>
                <table>
                    <thead><tr><th>No</th><th>Kode</th><th>Nama Instruktur</th><th>Sesi / Jam</th><th>Kehadiran</th><th>Skor Akhir</th><th>Kategori</th></tr></thead>
                    <tbody>
                        @forelse($manajerialReport['kinerja_rows'] ?? [] as $i => $row)
                            <tr>
                                <td class="num">{{ $i + 1 }}</td>
                                <td class="num">{{ $row['instruktur']->kode_instruktur }}</td>
                                <td>{{ $row['instruktur']->nama_lengkap }}</td>
                                <td class="num">{{ (int) $row['total_jadwal'] }} sesi ({{ number_format((float) $row['jam_mengajar'], 1) }} jam)</td>
                                <td class="num">{{ number_format((float) $row['persentase_kehadiran'], 1) }}%</td>
                                <td class="num"><strong>{{ $row['skor_akhir'] !== null ? number_format((float) $row['skor_akhir'], 2) : '-' }}</strong></td>
                                <td>{{ $row['kategori_label'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-row">Belum ada data kinerja pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</body>
</html>
