@php
    use App\Data\CompanyPolicyDocuments;

    $viewerId = 'policyViewer_' . preg_replace('/[^a-z0-9_-]/i', '_', (string) ($doc['key'] ?? uniqid()));
    $streamUrl = CompanyPolicyDocuments::streamUrl($employee, $doc);
    $title = $doc['title'] ?? 'Policy';
@endphp

<div class="policy-pdf-viewer mb-2" data-policy-viewer>
    <button type="button"
            class="btn btn-outline-primary btn-sm policy-view-toggle"
            data-target="{{ $viewerId }}"
            data-src="{{ $streamUrl }}"
            data-title="{{ $title }}"
            aria-expanded="false">
        View {{ $title }}
    </button>
    <div id="{{ $viewerId }}" class="policy-pdf-frame-wrap d-none mt-2" oncontextmenu="return false;">
        <p class="small text-muted mb-1">
            Read only. Saving, printing, or downloading this policy is not permitted.
        </p>
        <div class="policy-pdf-scroll">
            <div class="policy-pdf-pages" aria-label="{{ $title }}"></div>
            <div class="policy-pdf-loading small text-muted py-3 d-none">Loading policy…</div>
            <div class="policy-pdf-error small text-danger py-2 d-none"></div>
        </div>
    </div>
</div>

@once('policy-pdf-viewer-assets')
    <style>
        .policy-pdf-scroll {
            max-height: min(520px, 70vh);
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 8px;
        }
        .policy-pdf-frame-wrap {
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
        .policy-pdf-pages canvas {
            display: block;
            width: 100%;
            height: auto;
            margin: 0 auto 10px;
            border-radius: 4px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            pointer-events: none;
        }
        @media print {
            body.policy-viewer-open * {
                visibility: hidden !important;
            }
            body.policy-viewer-open::before {
                content: 'Printing company policies is not permitted.';
                visibility: visible !important;
                display: block;
                position: fixed;
                inset: 0;
                padding: 2rem;
                font-size: 1.25rem;
                background: #fff;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof pdfjsLib === 'undefined') return;
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            var loaded = {};

            function blockPolicyShortcuts(e) {
                if (!document.querySelector('.policy-pdf-frame-wrap:not(.d-none)')) return;
                var key = (e.key || '').toLowerCase();
                if ((e.ctrlKey || e.metaKey) && (key === 's' || key === 'p' || key === 'o')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                if (key === 'f12') {
                    e.preventDefault();
                }
            }

            document.addEventListener('keydown', blockPolicyShortcuts, true);
            window.addEventListener('beforeprint', function (e) {
                if (document.querySelector('.policy-pdf-frame-wrap:not(.d-none)')) {
                    e.preventDefault();
                }
            });

            function closePolicyViewer(btn) {
                var wrap = document.getElementById(btn.getAttribute('data-target'));
                if (!wrap) return;
                wrap.classList.add('d-none');
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = 'View ' + (btn.getAttribute('data-title') || 'Policy');
            }

            function closeAllPolicyViewers(exceptTargetId) {
                document.querySelectorAll('.policy-view-toggle').forEach(function (otherBtn) {
                    if (otherBtn.getAttribute('data-target') === exceptTargetId) return;
                    closePolicyViewer(otherBtn);
                });
                if (!exceptTargetId) {
                    document.body.classList.remove('policy-viewer-open');
                }
            }

            function renderPolicyPages(wrap, url) {
                var pagesEl = wrap.querySelector('.policy-pdf-pages');
                var loadingEl = wrap.querySelector('.policy-pdf-loading');
                var errorEl = wrap.querySelector('.policy-pdf-error');
                if (!pagesEl || loaded[url]) return;

                pagesEl.innerHTML = '';
                if (loadingEl) loadingEl.classList.remove('d-none');
                if (errorEl) errorEl.classList.add('d-none');

                fetch(url, {
                    headers: { 'X-Policy-Viewer': '1', 'Accept': 'application/pdf' },
                    credentials: 'same-origin'
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Could not load policy.');
                        return res.arrayBuffer();
                    })
                    .then(function (buffer) {
                        return pdfjsLib.getDocument({ data: buffer, disableAutoFetch: true, disableStream: true }).promise;
                    })
                    .then(function (pdf) {
                        var chain = Promise.resolve();
                        for (var i = 1; i <= pdf.numPages; i++) {
                            (function (pageNum) {
                                chain = chain.then(function () {
                                    return pdf.getPage(pageNum).then(function (page) {
                                        var viewport = page.getViewport({ scale: 1.35 });
                                        var canvas = document.createElement('canvas');
                                        canvas.width = viewport.width;
                                        canvas.height = viewport.height;
                                        canvas.setAttribute('draggable', 'false');
                                        canvas.oncontextmenu = function () { return false; };
                                        return page.render({
                                            canvasContext: canvas.getContext('2d'),
                                            viewport: viewport
                                        }).promise.then(function () {
                                            pagesEl.appendChild(canvas);
                                        });
                                    });
                                });
                            })(i);
                        }
                        return chain;
                    })
                    .then(function () {
                        loaded[url] = true;
                        if (loadingEl) loadingEl.classList.add('d-none');
                    })
                    .catch(function (err) {
                        if (loadingEl) loadingEl.classList.add('d-none');
                        if (errorEl) {
                            errorEl.textContent = err.message || 'Unable to display this policy.';
                            errorEl.classList.remove('d-none');
                        }
                    });
            }

            document.querySelectorAll('.policy-view-toggle').forEach(function (btn) {
                if (btn.dataset.bound === '1') return;
                btn.dataset.bound = '1';

                btn.addEventListener('click', function () {
                    var targetId = btn.getAttribute('data-target');
                    var wrap = document.getElementById(targetId);
                    if (!wrap) return;
                    var titleText = btn.getAttribute('data-title') || 'Policy';
                    var isClosed = wrap.classList.contains('d-none');

                    if (isClosed) {
                        closeAllPolicyViewers(targetId);
                        renderPolicyPages(wrap, btn.getAttribute('data-src'));
                        wrap.classList.remove('d-none');
                        btn.setAttribute('aria-expanded', 'true');
                        btn.textContent = 'Hide ' + titleText;
                        document.body.classList.add('policy-viewer-open');
                    } else {
                        closePolicyViewer(btn);
                        if (!document.querySelector('.policy-pdf-frame-wrap:not(.d-none)')) {
                            document.body.classList.remove('policy-viewer-open');
                        }
                    }
                });
            });

            document.querySelectorAll('[data-policy-viewer]').forEach(function (el) {
                el.addEventListener('contextmenu', function (e) {
                    e.preventDefault();
                });
            });
        });
    </script>
@endonce
