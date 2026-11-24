@props([
    'instrukturs',
    'ruanganList' => collect(),
])

<div x-show="alihkanOpen"
     x-cloak
     class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="alihkan-modal-title">
    <div @click.outside="alihkanOpen = false"
         class="my-auto w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-800">
            <div>
                <h3 id="alihkan-modal-title" class="text-base font-bold text-gray-900 dark:text-white">
                    Pengalihan &amp; Penyesuaian Jadwal
                </h3>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300" x-text="alihkanJudul"></p>
            </div>
            <button type="button"
                    @click="alihkanOpen = false"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    aria-label="Tutup dialog">
                &times;
            </button>
        </div>

        <form method="POST" :action="alihkanUrl" class="mt-4 space-y-4">
            @csrf

            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-xs text-gray-700 dark:border-gray-800 dark:bg-gray-800/70 dark:text-gray-200">
                <p class="font-semibold text-gray-900 dark:text-white">Jadwal Saat Ini</p>
                <p class="mt-1 leading-relaxed">
                    Instruktur: <span class="font-medium" x-text="alihkanAwal.instruktur_nama"></span>
                    &middot; Kelas: <span class="font-medium" x-text="alihkanAwal.nama_kelas"></span>
                    &middot; Ruang: <span class="font-medium" x-text="alihkanAwal.ruangan"></span>
                    <br>
                    Waktu: <span class="font-medium" x-text="alihkanAwal.jadwal_label"></span>,
                    <span class="font-mono font-medium" x-text="alihkanAwal.jam_label"></span>
                </p>
                <p class="mt-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                    Ubah minimal satu bagian jadwal (Instruktur, Ruangan, Hari/Tanggal, atau Jam). Sistem otomatis memeriksa bentrok instruktur dan ruangan.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="alihkan-instruktur" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Instruktur Mengajar
                    </label>
                    <select id="alihkan-instruktur"
                            name="instruktur_pengganti_id"
                            x-model="alihkanForm.instruktur_id"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @foreach($instrukturs as $ins)
                            <option value="{{ $ins->id }}">
                                {{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})
                            </option>
                        @endforeach
                        <option x-show="alihkanForm.instruktur_id && !@js($instrukturs->pluck('id')->map(fn ($id) => (string) $id)->values()).includes(String(alihkanForm.instruktur_id))"
                                :value="alihkanForm.instruktur_id"
                                x-text="alihkanAwal.instruktur_nama"></option>
                    </select>
                </div>

                <div>
                    <label for="alihkan-ruangan" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Ruangan
                    </label>
                    <input type="text"
                           id="alihkan-ruangan"
                           name="ruangan"
                           list="alihkan-ruangan-list"
                           x-model="alihkanForm.ruangan"
                           maxlength="100"
                           placeholder="Pilih atau ketik ruangan (mis. L-01, R-01, L-02)"
                           class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Pilih dari saran atau ketik nama ruangan baru secara langsung.
                    </p>
                    <datalist id="alihkan-ruangan-list">
                        @foreach($ruanganList as $ruang)
                            @if(($ruang['kode'] ?? '') !== ($ruang['label'] ?? ''))
                                <option value="{{ $ruang['kode'] }}">{{ $ruang['label'] }}</option>
                            @else
                                <option value="{{ $ruang['kode'] }}"></option>
                            @endif
                        @endforeach
                    </datalist>
                </div>

                <div x-show="alihkanForm.is_pola_mingguan">
                    <label for="alihkan-hari" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Hari (Jadwal Mingguan)
                    </label>
                    <select id="alihkan-hari"
                            name="hari"
                            x-model="alihkanForm.hari"
                            :disabled="!alihkanForm.is_pola_mingguan"
                            class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @foreach(\App\Models\Jadwal::DAFTAR_HARI as $hariOption)
                            <option value="{{ $hariOption }}">{{ $hariOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="!alihkanForm.is_pola_mingguan">
                    <label for="alihkan-tanggal" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Tanggal Sesi
                    </label>
                    <input type="date"
                           id="alihkan-tanggal"
                           name="tanggal"
                           x-model="alihkanForm.tanggal"
                           :disabled="alihkanForm.is_pola_mingguan"
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div>
                    <label for="alihkan-jam-mulai" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Jam Mulai
                    </label>
                    <input type="time"
                           id="alihkan-jam-mulai"
                           name="jam_mulai"
                           x-model="alihkanForm.jam_mulai"
                           required
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div>
                    <label for="alihkan-jam-selesai" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Jam Selesai
                    </label>
                    <input type="time"
                           id="alihkan-jam-selesai"
                           name="jam_selesai"
                           x-model="alihkanForm.jam_selesai"
                           required
                           class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            <div>
                <label for="alihkan-alasan" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Alasan Pengalihan / Penyesuaian <span class="text-error-600 dark:text-error-400">*</span>
                </label>
                <textarea id="alihkan-alasan"
                          name="alasan_pengalihan"
                          x-model="alihkanForm.alasan_pengalihan"
                          rows="2"
                          maxlength="255"
                          required
                          placeholder="Contoh: Tukar jam karena lab dipakai ujian, atau instruktur berhalangan hadir"
                          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400"></textarea>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-800">
                <button type="button"
                        @click="alihkanOpen = false"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    Simpan Pengalihan
                </button>
            </div>
        </form>
    </div>
</div>
