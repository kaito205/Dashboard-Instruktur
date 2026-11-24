@extends('layouts.fullscreen-layout')

@section('full_title', 'Evaluasi Instruktur | LPK CTI')

@section('content')
@php $appIdentity = \App\Models\Setting::getAppIdentity(); @endphp
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-8 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Header Portal Evaluasi Peserta --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/logo/logocti.png') }}"
                    alt="Logo {{ $appIdentity['lpk_name'] }}"
                    class="h-11 w-auto rounded-lg object-contain shrink-0" />
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Kuesioner Evaluasi Pengajaran Instruktur
                    </h1>
                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                        {{ $appIdentity['lpk_name'] }}@if($appIdentity['lpk_alamat'] !== '') &middot; {{ $appIdentity['lpk_alamat'] }}@endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Pesan Kesalahan Validasi --}}
        @if ($kodeError)
            <div role="alert" class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                <p class="font-semibold">Kode Evaluasi Tidak Valid</p>
                <p class="mt-1 text-xs">{{ $kodeError }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/60 dark:text-red-200">
                <p class="font-semibold">Periksa kembali isian formulir Anda:</p>
                <ul class="mt-1.5 list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Langkah 1: Masukkan Kode Kelas (disembunyikan jika kode sudah terverifikasi) --}}
        @if (! $kodeEvaluasi)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Langkah 1: Verifikasi Kode Kelas Evaluasi
                </h2>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    Masukkan kode akses evaluasi kelas yang dibagikan oleh Instruktur atau Admin di ruang pelatihan Anda.
                </p>

                <form method="GET" action="{{ route('evaluasi.publik.index') }}" class="mt-4 flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label for="kode-input" class="sr-only">Kode Kelas Evaluasi</label>
                        <input type="text" id="kode-input" name="kode"
                            value="{{ old('kode', $inputKode) }}"
                            placeholder="Ketik kode evaluasi kelas Anda..."
                            autocomplete="off"
                            required
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm font-mono uppercase text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                    <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Verifikasi Kode Kelas
                    </button>
                </form>
            </div>
        @endif

        {{-- Langkah 2: Formulir Penilaian Skala 1-5 --}}
        @if ($kodeEvaluasi)
            <form method="POST" action="{{ route('evaluasi.publik.store') }}" class="rounded-2xl border border-gray-200 bg-white p-6 space-y-6 dark:border-gray-800 dark:bg-gray-900">
                @csrf
                <input type="hidden" name="kode" value="{{ $kodeEvaluasi->kode }}" />

                <div class="pb-4 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                            Kode Kelas Aktif: {{ $kodeEvaluasi->kode }}
                        </span>
                        <a href="{{ route('evaluasi.publik.index') }}"
                            class="inline-flex min-h-[36px] shrink-0 items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Ganti Kode
                        </a>
                    </div>
                    <h2 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                        {{ $kodeEvaluasi->nama_kelas ?? ('Kelas ' . $kodeEvaluasi->kode) }}
                    </h2>
                    @if ($kodeEvaluasi->keterangan)
                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">{{ $kodeEvaluasi->keterangan }}</p>
                    @endif
                </div>

                {{-- Pemilihan Sesi / Instruktur & Materi --}}
                <div>
                    <label class="mb-2 block text-sm font-bold text-gray-900 dark:text-white">
                        Pilih Sesi, Instruktur, dan Materi yang Dievaluasi <span class="text-red-600">*</span>
                    </label>
                    <p class="mb-3 text-xs text-gray-600 dark:text-gray-300">
                        Satu kelas dapat diajar oleh beberapa instruktur pada sesi atau materi yang berbeda. Pilih sesi pengajaran yang ingin Anda nilai:
                    </p>

                    @if ($sesiList->isNotEmpty())
                        <div class="grid grid-cols-1 gap-3">
                            @foreach ($sesiList as $idx => $sesi)
                                @php
                                    $isChecked = old('jadwal_id')
                                        ? ((int) old('jadwal_id') === (int) $sesi->id)
                                        : ($sesiList->count() === 1 || $idx === 0);
                                @endphp
                                <label class="flex items-start gap-3.5 rounded-xl border border-gray-200 p-4 cursor-pointer transition hover:border-brand-400 hover:bg-brand-50/30 dark:border-gray-700 dark:hover:border-brand-600 dark:hover:bg-brand-950/20">
                                    <input type="radio" name="jadwal_id" value="{{ $sesi->id }}"
                                        {{ $isChecked ? 'checked' : '' }}
                                        required
                                        class="mt-1 h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500" />
                                    <div class="flex-1 text-xs">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $sesi->instruktur?->nama_lengkap ?? 'Instruktur' }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-900/50 dark:text-brand-300">
                                                Materi: {{ $sesi->materi }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-gray-600 dark:text-gray-300">
                                            Jadwal: {{ $sesi->jadwal_ringkas ?? ($sesi->tanggal_label . ', Pukul ' . substr((string) $sesi->jam_mulai, 0, 5) . ' - ' . substr((string) $sesi->jam_selesai, 0, 5) . ' WIB') }} | Ruangan: {{ $sesi->ruangan_ringkas ?? ($sesi->ruangan ?? '-') }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @elseif ($kodeEvaluasi->instruktur)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                Instruktur: {{ $kodeEvaluasi->instruktur->nama_lengkap }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                Bidang Keahlian: {{ $kodeEvaluasi->instruktur->bidang_keahlian ?? '-' }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Identitas Peserta (Opsional) --}}
                <div>
                    <label for="nama_pengisi" class="mb-1.5 block text-sm font-bold text-gray-900 dark:text-white">
                        Nama Peserta <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Opsional - Kosongkan untuk mengirim sebagai Anonim)</span>
                    </label>
                    <input type="text" id="nama_pengisi" name="nama_pengisi"
                        value="{{ old('nama_pengisi') }}"
                        placeholder="Kosongkan apabila ingin mengisi secara Anonim"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>

                {{-- 5 Indikator Penilaian Skala 1 - 5 --}}
                <div class="space-y-4">
                    <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900/60 dark:bg-blue-950/30">
                        <p class="text-xs font-semibold text-blue-900 dark:text-blue-200">
                            Panduan Skala Penilaian (1 sampai 5):
                        </p>
                        <p class="mt-1 text-xs text-blue-800 dark:text-blue-300">
                            <strong>1</strong> = Sangat Kurang, <strong>2</strong> = Kurang, <strong>3</strong> = Cukup, <strong>4</strong> = Baik, <strong>5</strong> = Sangat Baik.
                        </p>
                    </div>

                    @foreach ($indikatorLabels as $key => $label)
                        @php
                            $nomor = $loop->iteration;
                            $oldVal = old($key);
                            $labelPublik = trim((string) preg_replace(['/\s*\(Konfigurasi LPK\)\s*/iu', '/^Indikator\s+\d+\s*:\s*/iu'], '', (string) $label));
                        @endphp
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $nomor }}. {{ $labelPublik }} <span class="text-red-600">*</span>
                            </p>
                            <div class="mt-3 grid grid-cols-5 gap-2 sm:gap-3">
                                @for ($nilai = 1; $nilai <= 5; $nilai++)
                                    @php
                                        $ketSkala = match($nilai) {
                                            1 => 'Sangat Kurang',
                                            2 => 'Kurang',
                                            3 => 'Cukup',
                                            4 => 'Baik',
                                            5 => 'Sangat Baik',
                                        };
                                    @endphp
                                    <label class="flex flex-col items-center justify-center min-h-[52px] rounded-lg border border-gray-200 p-2 text-center cursor-pointer transition hover:border-brand-500 hover:bg-brand-50/40 dark:border-gray-700 dark:hover:border-brand-500 dark:hover:bg-brand-950/30">
                                        <input type="radio" name="{{ $key }}" value="{{ $nilai }}"
                                            {{ (int) $oldVal === $nilai ? 'checked' : '' }}
                                            required
                                            class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500" />
                                        <span class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $nilai }}</span>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 hidden sm:inline">{{ $ketSkala }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Komentar / Saran Opsional --}}
                <div>
                    <label for="komentar" class="mb-1.5 block text-sm font-bold text-gray-900 dark:text-white">
                        Komentar / Masukan untuk Instruktur <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(Opsional)</span>
                    </label>
                    <textarea id="komentar" name="komentar" rows="3"
                        placeholder="Tuliskan kesan, pesan, atau saran perbaikan penyampaian materi..."
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('komentar') }}</textarea>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                        class="inline-flex min-h-[44px] w-full sm:w-auto items-center justify-center rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Kirim Evaluasi Sekarang
                    </button>
                </div>
            </form>
        @endif

    </div>
</div>
@endsection
