<script>
    (function () {
        if (window.submitWithOptionalGps) {
            return;
        }

        function showGpsNotice(message) {
            const indicator = document.getElementById('gps-live-indicator');
            if (indicator) {
                indicator.textContent = message;
            }

            let toast = document.getElementById('gps-status-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'gps-status-toast';
                toast.setAttribute('role', 'alert');
                toast.className = 'fixed bottom-5 right-5 z-50 max-w-sm rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-900 shadow-lg dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100';
                document.body.appendChild(toast);
            }
            toast.textContent = message;
            toast.style.display = 'block';
        }

        function setHiddenValue(form, name, value) {
            let input = form.querySelector('input[name="' + name + '"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.appendChild(input);
            }
            input.value = value;
        }

        function getGpsErrorMessage(err) {
            if (!err) {
                return 'Lokasi perangkat tidak tersedia. Presensi tetap dilanjutkan tanpa koordinat GPS.';
            }
            if (err.code === 1) {
                return 'Izin lokasi ditolak oleh browser/perangkat. Presensi tetap dilanjutkan tanpa koordinat GPS.';
            }
            if (err.code === 3) {
                return 'Waktu pengambilan lokasi habis (timeout). Presensi tetap dilanjutkan tanpa koordinat GPS.';
            }
            return 'Lokasi perangkat tidak tersedia. Presensi tetap dilanjutkan tanpa koordinat GPS.';
        }

        window.submitWithOptionalGps = function (event, form) {
            if (form.dataset.gpsResolved === 'true') {
                return true;
            }

            event.preventDefault();

            if (form.dataset.gpsPending === 'true') {
                return false;
            }
            form.dataset.gpsPending = 'true';

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.dataset.originalText = submitBtn.textContent.trim();
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-70', 'cursor-wait');
                submitBtn.textContent = 'Mengambil lokasi...';
            }

            const indicator = document.getElementById('gps-live-indicator');
            if (indicator) {
                indicator.textContent = 'Mengambil koordinat lokasi...';
            }

            const finalizeSubmit = function () {
                form.dataset.gpsResolved = 'true';
                form.dataset.gpsPending = 'false';
                form.submit();
            };

            const onSuccess = function (pos) {
                setHiddenValue(form, 'latitude', pos.coords.latitude.toFixed(7));
                setHiddenValue(form, 'longitude', pos.coords.longitude.toFixed(7));
                setHiddenValue(form, 'akurasi_gps', pos.coords.accuracy ? pos.coords.accuracy.toFixed(2) : '');
                setHiddenValue(form, 'status_gps', 'terdeteksi');
                finalizeSubmit();
            };

            const onFailure = function (err) {
                const statusVal = (err && err.code === 1) ? 'ditolak_perangkat' : 'tidak_tersedia';
                setHiddenValue(form, 'latitude', '');
                setHiddenValue(form, 'longitude', '');
                setHiddenValue(form, 'akurasi_gps', '');
                setHiddenValue(form, 'status_gps', statusVal);
                showGpsNotice(getGpsErrorMessage(err));
                window.setTimeout(finalizeSubmit, 900);
            };

            if (!navigator.geolocation) {
                showGpsNotice('Browser tidak mendukung fitur lokasi GPS. Presensi tetap dilanjutkan tanpa koordinat.');
                setHiddenValue(form, 'status_gps', 'tidak_tersedia');
                window.setTimeout(finalizeSubmit, 900);
                return false;
            }

            // Tahap 1: enableHighAccuracy: false agar cepat dan stabil di laptop / Wi-Fi
            navigator.geolocation.getCurrentPosition(
                onSuccess,
                function (firstErr) {
                    if (firstErr && firstErr.code === 1) {
                        onFailure(firstErr);
                        return;
                    }
                    if (indicator) {
                        indicator.textContent = 'Mencoba ulang pembacaan GPS akurasi tinggi...';
                    }
                    // Tahap 2: fallback otomatis ke akurasi tinggi jika tahap 1 gagal/timeout
                    navigator.geolocation.getCurrentPosition(
                        onSuccess,
                        onFailure,
                        { enableHighAccuracy: true, timeout: 6000, maximumAge: 60000 }
                    );
                },
                { enableHighAccuracy: false, timeout: 8000, maximumAge: 60000 }
            );

            return false;
        };
    })();
</script>
