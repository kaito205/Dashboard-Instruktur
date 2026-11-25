@php
    $isEdit = isset($jadwal) && $jadwal !== null;
    $kelasMap = ($kelasList ?? collect())->mapWithKeys(fn ($k) => [
        (string) $k->id => [
            'kode_kelas'        => (string) $k->kode_kelas,
            'nama_kelas'        => (string) $k->nama_kelas,
            'ruangan_default'   => (string) ($k->ruangan_default ?? ''),
            'kuota_peserta'     => (int) ($k->kuota_peserta ?? 20),
            'program_pelatihan' => (string) ($k->program_pelatihan ?? ''),
        ],
    ])->toArray();
@endphp

<form method="POST" action="{{ $formAction }}" class="space-y-5"
      x-data="{
          kelasMap: @js($kelasMap),
          kelasId: @js((string) old('kelas_id', $isEdit ? ($jadwal->kelas_id ?? '') : ($selectedKelasId ?? ''))),
          kodeKelas: @js((string) old('kode_kelas', $isEdit ? ($jadwal->kode_kelas ?? '') : '')),
          namaKelas: @js((string) old('nama_kelas', $isEdit ? ($jadwal->nama_kelas ?? '') : '')),
          ruangan: @js((string) old('ruangan', $isEdit ? ($jadwal->ruangan ?? '') : '')),
          jumlahPeserta: @js((string) old('jumlah_peserta', $isEdit ? ($jadwal->jumlah_peserta ?? 20) : '20')),
          isPolaMingguan: @js((bool) old('is_pola_mingguan', $isEdit ? (bool) $jadwal->is_pola_mingguan : false)),
          init() {
              if (this.kelasId && this.namaKelas === '') {
                  this.applyKelas();
              }
          },
          applyKelas() {
              const item = this.kelasMap[this.kelasId];
              if (!item) return;
              this.kodeKelas = item.kode_kelas || '';
              this.namaKelas = item.nama_kelas || '';
              if (item.ruangan_default) {
                  this.ruangan = item.ruangan_default;
              }
              if (item.kuota_peserta > 0) {
                  this.jumlahPeserta = String(item.kuota_peserta);
              }
          }
      }">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div>
        <div class="mb-1.5 flex items-center justify-between gap-2">
            <label for="kelas_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Pilih Kelas (Opsional)</label>
            <a href="{{ route('admin.kelas.index') }}" class="text-xs font-semibold text-brand-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-brand-300">Kelola Data Kelas</a>
        </div>
        <select id="kelas_id" name="kelas_id" x-model="kelasId" @change="applyKelas()"
                class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            <option value="">Ketik Kelas Manual (Tanpa Master Kelas)</option>
            @foreach($kelasList ?? collect() as $kls)
                <option value="{{ $kls->id }}">{{ $kls->display_label }}{{ $kls->program_pelatihan ? ' - ' . $kls->program_pelatihan : '' }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Memilih kelas akan mengisi otomatis Kode Kelas, Nama Kelas, Ruangan, dan Jumlah Peserta.</p>
    </div>

    <div>
        <label for="instruktur_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Instruktur Pengajar <span class="text-red-600">*</span></label>
        <select id="instruktur_id" name="instruktur_id" required
                class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            <option value="">Pilih Instruktur</option>
            @foreach($instrukturs as $ins)
                <option value="{{ $ins->id }}" {{ old('instruktur_id', $isEdit ? $jadwal->instruktur_id : '') == $ins->id ? 'selected' : '' }}>{{ $ins->nama_lengkap }} ({{ $ins->kode_instruktur }})</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label for="kode_kelas" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Kode Kelas</label>
            <input type="text" id="kode_kelas" name="kode_kelas" x-model="kodeKelas" maxlength="50"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-mono uppercase text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                   placeholder="Contoh: RPL, TKJ1, AP1">
        </div>
        <div>
            <label for="nama_kelas" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Kelas <span class="text-red-600 dark:text-red-300">*</span></label>
            <input type="text" id="nama_kelas" name="nama_kelas" x-model="namaKelas" required
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                   placeholder="Contoh: RPL">
        </div>
        <div>
            <label for="materi" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Materi / Topik</label>
            <input type="text" id="materi" name="materi" value="{{ old('materi', $isEdit ? $jadwal->materi : '') }}"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                   placeholder="Contoh: Pemrograman Web">
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/40">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Pengaturan Waktu &amp; Pengulangan</span>
            <label class="inline-flex min-h-[36px] cursor-pointer items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_pola_mingguan" value="1" x-model="isPolaMingguan" {{ old('is_pola_mingguan', $isEdit ? $jadwal->is_pola_mingguan : false) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>Jadikan jadwal mingguan berulang</span>
            </label>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div x-show="isPolaMingguan" x-cloak>
                <label for="hari" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Hari (untuk Jadwal Mingguan) <span class="text-red-600 dark:text-red-300">*</span></label>
                <select id="hari" name="hari" :disabled="!isPolaMingguan"
                        class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">Pilih Hari</option>
                    @foreach(\App\Models\Jadwal::DAFTAR_HARI as $h)
                        <option value="{{ $h }}" {{ old('hari', $isEdit ? $jadwal->hari : '') === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="!isPolaMingguan" x-cloak class="sm:col-span-2">
                <label for="tanggal" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Spesifik (untuk Sesi Sekali) <span class="text-red-600 dark:text-red-300">*</span></label>
                <input type="date" id="tanggal" name="tanggal" :disabled="isPolaMingguan" value="{{ old('tanggal', $isEdit ? $jadwal->tanggal?->format('Y-m-d') : date('Y-m-d')) }}"
                       class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hari akan ditentukan otomatis berdasarkan tanggal yang dipilih.</p>
            </div>
            <div>
                <label for="jam_mulai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Jam Mulai <span class="text-red-600 dark:text-red-300">*</span></label>
                <input type="time" id="jam_mulai" name="jam_mulai" value="{{ old('jam_mulai', $isEdit ? substr((string) $jadwal->jam_mulai, 0, 5) : '08:00') }}" required
                       class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>
            <div>
                <label for="jam_selesai" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Jam Selesai <span class="text-red-600 dark:text-red-300">*</span></label>
                <input type="time" id="jam_selesai" name="jam_selesai" value="{{ old('jam_selesai', $isEdit ? substr((string) $jadwal->jam_selesai, 0, 5) : '10:00') }}" required
                       class="min-h-[44px] w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label for="ruangan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Ruangan</label>
            <input type="text" id="ruangan" name="ruangan" list="jadwal-ruangan-list" x-model="ruangan"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                   placeholder="Pilih atau ketik ruangan (mis. L-01, R-01)">
            <datalist id="jadwal-ruangan-list">
                @foreach($ruanganList ?? collect() as $ruang)
                    @if(($ruang['kode'] ?? '') !== ($ruang['label'] ?? ''))
                        <option value="{{ $ruang['kode'] }}">{{ $ruang['label'] }}</option>
                    @else
                        <option value="{{ $ruang['kode'] }}"></option>
                    @endif
                @endforeach
            </datalist>
        </div>
        <div>
            <label for="jumlah_peserta" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Jumlah Peserta</label>
            <input type="number" id="jumlah_peserta" name="jumlah_peserta" x-model="jumlahPeserta" min="0" max="1000"
                   class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Status <span class="text-red-600 dark:text-red-300">*</span></label>
            <select id="status" name="status" required
                    class="min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="terjadwal" {{ old('status', $isEdit ? $jadwal->status : 'terjadwal') === 'terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                <option value="berlangsung" {{ old('status', $isEdit ? $jadwal->status : '') === 'berlangsung' ? 'selected' : '' }}>Sedang Berlangsung</option>
                <option value="selesai" {{ old('status', $isEdit ? $jadwal->status : '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="dibatalkan" {{ old('status', $isEdit ? $jadwal->status : '') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
    </div>

    <div>
        <label for="keterangan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="2"
                  class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                  placeholder="Catatan tambahan (opsional)">{{ old('keterangan', $isEdit ? $jadwal->keterangan : '') }}</textarea>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
        <a href="{{ route('admin.jadwal.index') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Batal</a>
        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">{{ $submitLabel ?? 'Simpan Jadwal' }}</button>
    </div>
</form>
