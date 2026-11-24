<script>
(function () {
    let debounceTimer = null;
    let requestSeq = 0;
    let activeController = null;

    function resetBodyPageLoading() {
        try {
            if (window.Alpine && typeof window.Alpine.$data === 'function') {
                const bodyData = window.Alpine.$data(document.body);
                if (bodyData && typeof bodyData.pageLoading !== 'undefined') {
                    bodyData.pageLoading = false;
                }
            } else if (document.body && document.body._x_dataStack && document.body._x_dataStack[0]) {
                document.body._x_dataStack[0].pageLoading = false;
            }
        } catch (e) {
            // Abaikan jika state tidak ditemukan
        }
    }

    function isGetFilterForm(form) {
        if (!form || form.tagName !== 'FORM') return false;
        const main = document.getElementById('main-content');
        if (!main || !main.contains(form)) return false;
        const methodAttr = form.getAttribute('method');
        return Boolean(methodAttr && methodAttr.toUpperCase() === 'GET');
    }

    function buildFormUrl(form) {
        const action = form.getAttribute('action') || window.location.pathname;
        const url = new URL(action, window.location.origin);
        const formData = new FormData(form);
        const params = new URLSearchParams();

        const periodeSelect = form.querySelector('select[name="periode"]');
        const omitRangeDates = Boolean(periodeSelect && periodeSelect.value !== 'kustom');

        for (const [key, value] of formData.entries()) {
            const strVal = String(value).trim();
            if (strVal === '') {
                continue;
            }
            if (omitRangeDates && (key === 'dari' || key === 'sampai')) {
                continue;
            }
            params.append(key, strVal);
        }

        url.search = params.toString();
        return url.toString();
    }

    function filterTableInstant(form) {
        const mainEl = document.getElementById('main-content');
        if (!mainEl) return false;

        const allTables = Array.from(mainEl.querySelectorAll('table'));
        if (allTables.length !== 1) return false;

        const table = allTables[0];
        const tbody = table.querySelector('tbody');
        if (!tbody) return false;

        const searchInput = form.querySelector('input[type="text"], input[type="search"]');
        const searchRaw = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const searchTokens = searchRaw ? searchRaw.split(/\s+/).filter(Boolean) : [];

        const rows = Array.from(tbody.querySelectorAll('tr'));
        const dataRows = rows.filter((tr) => {
            if (tr.getAttribute('data-instant-empty') === '1') return false;
            const onlyTd = tr.children.length === 1 && tr.children[0].hasAttribute('colspan');
            return !onlyTd;
        });

        if (dataRows.length === 0 && searchTokens.length === 0) {
            return false;
        }

        let visibleCount = 0;

        dataRows.forEach((tr) => {
            const text = (tr.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
            let match = true;

            for (const token of searchTokens) {
                if (!text.includes(token)) {
                    match = false;
                    break;
                }
            }

            tr.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        const serverEmptyRows = rows.filter((tr) => {
            if (tr.getAttribute('data-instant-empty') === '1') return false;
            return tr.children.length === 1 && tr.children[0].hasAttribute('colspan');
        });
        serverEmptyRows.forEach((tr) => {
            tr.style.display = (dataRows.length === 0 && visibleCount === 0) ? '' : 'none';
        });

        let instantEmptyRow = tbody.querySelector('tr[data-instant-empty="1"]');
        if (dataRows.length > 0 && visibleCount === 0) {
            if (!instantEmptyRow) {
                const colCount = table.querySelectorAll('thead th').length || 6;
                instantEmptyRow = document.createElement('tr');
                instantEmptyRow.setAttribute('data-instant-empty', '1');
                instantEmptyRow.innerHTML = '<td colspan="' + colCount + '" class="px-4 py-8 text-center text-sm text-gray-600 dark:text-gray-300">Tidak ada data yang sesuai dengan filter pencarian.</td>';
                tbody.appendChild(instantEmptyRow);
            }
            instantEmptyRow.style.display = '';
        } else if (instantEmptyRow) {
            instantEmptyRow.style.display = 'none';
        }

        const targetUrl = buildFormUrl(form);
        window.history.replaceState({}, '', targetUrl);
        return true;
    }

    function findFilterBlockAndContainer(form, rootMain) {
        let curr = form;
        while (curr && curr.parentElement && curr.parentElement !== rootMain) {
            const parent = curr.parentElement;
            if (parent.children.length > 1 && parent.querySelector('table, [data-live-filter-content]') && !curr.querySelector('table, [data-live-filter-content]')) {
                return { container: parent, block: curr };
            }
            curr = parent;
        }
        return { container: rootMain, block: curr };
    }

    function syncResetLink(liveForm, fetchedForm) {
        if (!liveForm || !fetchedForm) return;
        const liveReset = liveForm.querySelector('a');
        const fetchedReset = fetchedForm.querySelector('a');
        if (fetchedReset && !liveReset) {
            liveForm.appendChild(fetchedReset.cloneNode(true));
        } else if (!fetchedReset && liveReset) {
            liveReset.remove();
        } else if (fetchedReset && liveReset) {
            liveReset.setAttribute('href', fetchedReset.getAttribute('href') || '');
        }
    }

    function reexecutePageScripts(doc) {
        const newScriptsContainer = doc.getElementById('page-scripts');
        const currentScriptsContainer = document.getElementById('page-scripts');
        if (!newScriptsContainer || !currentScriptsContainer) return;

        currentScriptsContainer.innerHTML = '';
        const scripts = Array.from(newScriptsContainer.querySelectorAll('script'));
        const inlineCodes = [];
        const externalSrcs = [];

        scripts.forEach((oldScript) => {
            if (oldScript.src) {
                externalSrcs.push(oldScript.src);
            } else {
                let code = oldScript.textContent || '';
                code = code.replace(
                    /document\.addEventListener\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\(\s*\)\s*\{([\s\S]*)\}\s*\)\s*;?/m,
                    '(function(){ $1 })();'
                );
                if (code.trim() !== '') {
                    inlineCodes.push(code);
                }
            }
        });

        const runInline = function () {
            inlineCodes.forEach((code) => {
                const newScript = document.createElement('script');
                newScript.textContent = code;
                currentScriptsContainer.appendChild(newScript);
            });
        };

        const missingExternal = externalSrcs.filter((src) => {
            return !document.querySelector('script[src="' + src + '"]');
        });

        if (missingExternal.length === 0) {
            runInline();
            return;
        }

        let loadedCount = 0;
        missingExternal.forEach((src) => {
            const extScript = document.createElement('script');
            extScript.src = src;
            extScript.onload = extScript.onerror = function () {
                loadedCount++;
                if (loadedCount === missingExternal.length) {
                    runInline();
                }
            };
            document.head.appendChild(extScript);
        });
    }

    async function fetchAndUpdateMain(targetUrl, form, keepFormFocused) {
        const mainEl = document.getElementById('main-content');
        if (!mainEl) {
            resetBodyPageLoading();
            return;
        }

        const mySeq = ++requestSeq;
        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();
        const signal = activeController.signal;

        const allGetForms = Array.from(mainEl.querySelectorAll('form')).filter(isGetFilterForm);
        const formIndex = form ? allGetForms.indexOf(form) : -1;

        try {
            const response = await fetch(targetUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Live-Filter': '1',
                    'Accept': 'text/html'
                },
                signal
            });

            if (mySeq !== requestSeq || !response.ok) {
                return;
            }

            const html = await response.text();
            if (mySeq !== requestSeq) {
                return;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMain = doc.getElementById('main-content');

            if (!newMain) {
                return;
            }

            window.history.replaceState({}, '', targetUrl);

            if (keepFormFocused && form && formIndex !== -1 && mainEl.contains(form)) {
                const newGetForms = Array.from(newMain.querySelectorAll('form')).filter((f) => {
                    const m = f.getAttribute('method');
                    return Boolean(m && m.toUpperCase() === 'GET');
                });
                const newForm = newGetForms[formIndex];

                if (newForm) {
                    const liveInfo = findFilterBlockAndContainer(form, mainEl);
                    const newInfo = findFilterBlockAndContainer(newForm, newMain);

                    if (liveInfo.container && newInfo.container && liveInfo.block && newInfo.block) {
                        const liveChildren = Array.from(liveInfo.container.children);
                        const liveBlockIdx = liveChildren.indexOf(liveInfo.block);

                        const newChildren = Array.from(newInfo.container.children);
                        const newBlockIdx = newChildren.indexOf(newInfo.block);

                        if (liveBlockIdx !== -1 && newBlockIdx !== -1) {
                            liveChildren.forEach((child, idx) => {
                                if (idx !== liveBlockIdx) {
                                    child.remove();
                                }
                            });

                            for (let i = 0; i < newBlockIdx; i++) {
                                liveInfo.container.insertBefore(newChildren[i].cloneNode(true), liveInfo.block);
                            }

                            for (let i = newBlockIdx + 1; i < newChildren.length; i++) {
                                liveInfo.container.appendChild(newChildren[i].cloneNode(true));
                            }

                            syncResetLink(form, newForm);
                            reexecutePageScripts(doc);
                            return;
                        }
                    }
                }
            }

            mainEl.innerHTML = newMain.innerHTML;
            reexecutePageScripts(doc);
        } catch (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
        } finally {
            if (mySeq === requestSeq) {
                resetBodyPageLoading();
            }
        }
    }

    function scheduleServerSync(form, keepFormFocused, delayMs) {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(function () {
            if (!isGetFilterForm(form)) return;
            const targetUrl = buildFormUrl(form);
            fetchAndUpdateMain(targetUrl, form, keepFormFocused);
        }, delayMs);
    }

    document.addEventListener('input', function (event) {
        const target = event.target;
        if (!target || target.tagName !== 'INPUT') return;

        const type = (target.getAttribute('type') || 'text').toLowerCase();
        if (!['text', 'search'].includes(type)) return;

        const form = target.closest('form');
        if (!isGetFilterForm(form)) return;

        filterTableInstant(form);
        scheduleServerSync(form, true, 300);
    });

    document.addEventListener('change', function (event) {
        const target = event.target;
        if (!target) return;

        const form = target.closest('form');
        if (!isGetFilterForm(form)) return;

        const tag = target.tagName;
        const type = (target.getAttribute('type') || '').toLowerCase();

        if (tag === 'SELECT') {
            if (target.getAttribute('name') === 'periode' && target.value === 'kustom') {
                return;
            }
            scheduleServerSync(form, false, 30);
            return;
        }

        if (tag === 'INPUT' && type === 'date') {
            const val = (target.value || '').trim();
            if (!/^\d{4}-\d{2}-\d{2}$/.test(val)) {
                return;
            }
            scheduleServerSync(form, false, 250);
            return;
        }

        if (tag === 'INPUT' && ['month', 'radio', 'checkbox'].includes(type)) {
            scheduleServerSync(form, false, 50);
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!isGetFilterForm(form)) return;

        event.preventDefault();
        resetBodyPageLoading();
        scheduleServerSync(form, false, 0);
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link) return;

        const form = link.closest('form');
        if (!isGetFilterForm(form)) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || link.getAttribute('target') === '_blank') return;

        event.preventDefault();
        resetBodyPageLoading();
        if (debounceTimer) clearTimeout(debounceTimer);
        fetchAndUpdateMain(href, null, false);
    });
})();
</script>
