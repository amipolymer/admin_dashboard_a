@php
    use App\Support\OnGrid;

    $step = $employee->onboardingStep();
    $snapshot = OnGrid::hrBgvSnapshot($employee);
    $hasBgvData = !empty($employee->ongrid_id) || !empty($snapshot['verifications']);
    $showPanel = !$isFinalized && (
        $hasBgvData
        || in_array($step, ['bgv_started', 'bgv_completed'], true)
    );
    $expandBgvStatus = $hasBgvData || $step === 'bgv_started';
    $initiatedAt = !empty($snapshot['initiated_at'])
        ? \Carbon\Carbon::parse($snapshot['initiated_at'])->format('d-M-Y H:i')
        : null;
@endphp

@if ($showPanel)
<div class="card mb-3 shadow-sm border-info" id="bvgStatusPanel">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center hr-collapse-toggle"
         data-toggle="collapse" data-target="#collapseBgvStatus" aria-expanded="{{ $expandBgvStatus ? 'true' : 'false' }}">
        <strong><i class="dw dw-shield1 text-info"></i> BGV status (OnGrid)</strong>
        <small class="text-muted collapse-hint">{{ $expandBgvStatus ? 'Hide' : 'Show' }}</small>
    </div>
    <div id="collapseBgvStatus" class="collapse {{ $expandBgvStatus ? 'show' : '' }}">
        <div class="card-body py-3">
            @if (!$employee->ongrid_id)
                <div class="alert alert-secondary py-2 small mb-0">
                    BGV has not been started on OnGrid yet. Use <strong>Start OnGrid BGV</strong> in Onboarding Actions when documents are ready.
                </div>
            @else
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div class="small">
                        <div><span class="text-muted">OnGrid individual ID:</span> <strong>{{ $employee->ongrid_id }}</strong></div>
                        @if ($initiatedAt)
                            <div><span class="text-muted">Last initiated:</span> {{ $initiatedAt }}</div>
                        @endif
                        @if (!empty($snapshot['verification_codes']))
                            <div class="mt-1">
                                <span class="text-muted">Requested checks:</span>
                                {{ collect($snapshot['verification_codes'])->map(fn ($c) => OnGrid::offeringLabel($c))->implode(', ') }}
                            </div>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-outline-info mr-2" id="bgvStatusRefreshBtn" data-url="{{ route('EmployeeJoiner.documents.getStatus', $employee->id) }}">
                            <i class="dw dw-reload"></i> Refresh from OnGrid
                        </button>
                        @if (OnGrid::isConfigured())
                            <a href="{{ route('EmployeeJoiner.ongridInviteShow', $employee->ongrid_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                Full OnGrid record
                            </a>
                        @endif
                    </div>
                </div>

                @if (!empty($snapshot['skipped_offerings']))
                    <div class="alert alert-warning py-2 small mb-3">
                        <strong>Skipped at last initiate:</strong>
                        <ul class="mb-0 pl-3 mt-1">
                            @foreach ($snapshot['skipped_offerings'] as $code => $reason)
                                <li><strong>{{ $code }}</strong> — {{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div id="bgvStatusError" class="alert alert-danger py-2 small d-none mb-2"></div>
                <div id="bgvStatusLoading" class="small text-muted d-none mb-2">Loading status from OnGrid…</div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 small table-striped" id="bgvStatusTable">
                        <thead class="thead-light">
                            <tr>
                                <th class="py-1 px-2">Check</th>
                                <th class="py-1 px-2">Code</th>
                                <th class="py-1 px-2">Request ID</th>
                                <th class="py-1 px-2">Status</th>
                            </tr>
                        </thead>
                        <tbody id="bgvStatusTableBody">
                            @forelse ($snapshot['verifications'] as $row)
                                <tr class="p-1" data-code="{{ $row['code'] }}" @if(!empty($row['request_id'])) data-request-id="{{ $row['request_id'] }}" @endif>
                                    <td class="py-1 px-2">{{ $row['label'] }}</td>
                                    <td class="py-1 px-2"><code>{{ $row['code'] }}</code></td>
                                    <td class="py-1 px-2">{{ $row['request_id'] ?? '—' }}</td>
                                    <td class="py-1 px-2" class="bgv-live-state">
                                        @if (!empty($row['is_success']))
                                            <span class="badge badge-success p-1">Success</span>
                                        @elseif (!empty($row['reason']) || !empty($row['status']) || !empty($row['state']))
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 border-0 bgv-status-detail"
                                                    data-status="{{ $row['display'] ?? ($row['status'] ?? $row['state'] ?? '—') }}"
                                                    data-reason="{{ $row['reason'] ?? '' }}"
                                                    data-code="{{ $row['code'] }}"
                                                    title="Click for details">
                                                <span class="badge {{ !empty($row['reason']) || stripos((string) ($row['status'] ?? $row['state'] ?? ''), 'fail') !== false ? 'badge-danger p-1' : 'badge-warning p-1' }}">
                                                    {{ $row['display'] ?? ($row['status'] ?? $row['state'] ?? '—') }}
                                                </span>
                                            </button>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr id="bgvStatusEmptyRow">
                                    <td colspan="4" class="text-muted p-2">No verification rows in last initiate response. Click Refresh after BGV starts.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="modal fade" id="bgvStatusDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header py-2">
                                <h6 class="modal-title mb-0">BGV check details</h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body small">
                                <div class="mb-2"><span class="text-muted">Check:</span> <strong id="bgvDetailCode">—</strong></div>
                                <div class="mb-2"><span class="text-muted">Status:</span> <strong id="bgvDetailStatus">—</strong></div>
                                <div id="bgvDetailReasonWrap" class="d-none">
                                    <span class="text-muted">Reason:</span>
                                    <div id="bgvDetailReason" class="mt-1 p-2 bg-light border rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mb-0 mt-2">
                    Status is loaded from OnGrid verification status. Click a non-Success status for reason details. Step: {{ \App\Support\OnboardingStepGate::humanStepLabel($step) }}.
                </p>
            @endif
        </div>
    </div>
</div>

@if ($employee->ongrid_id)
<script>
(function () {
    var btn = document.getElementById('bgvStatusRefreshBtn');
    if (!btn) return;

    var errBox = document.getElementById('bgvStatusError');
    var loading = document.getElementById('bgvStatusLoading');
    var tbody = document.getElementById('bgvStatusTableBody');
    var detailModal = document.getElementById('bgvStatusDetailModal');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function badgeClass(row) {
        if (row && row.is_success) return 'badge-success';
        var s = String((row && (row.status || row.state || row.display)) || '').toLowerCase();
        if (s.indexOf('fail') !== -1 || s.indexOf('reject') !== -1) return 'badge-danger p-1';
        if (s.indexOf('request') !== -1 || s.indexOf('progress') !== -1 || s.indexOf('pending') !== -1) return 'badge-warning';
        return 'badge-secondary';
    }

    function renderStatusCell(row) {
        if (!row) {
            return '<span class="text-muted">—</span>';
        }
        if (row.is_success) {
            return '<span class="badge badge-success p-1">Success</span>';
        }

        var display = row.display || row.status || row.state || '—';
        var reason = row.reason || '';
        var code = row.code || '';
        var badge = badgeClass(row);

        if (reason || display !== '—') {
            return '<button type="button" class="btn btn-link btn-sm p-0 border-0 bgv-status-detail"'
                + ' data-status="' + escapeHtml(display) + '"'
                + ' data-reason="' + escapeHtml(reason) + '"'
                + ' data-code="' + escapeHtml(code) + '"'
                + ' title="Click for details">'
                + '<span class="badge ' + badge + '">' + escapeHtml(display) + '</span>'
                + '</button>';
        }

        return '<span class="text-muted">—</span>';
    }

    function bindDetailButtons(scope) {
        (scope || document).querySelectorAll('.bgv-status-detail').forEach(function (el) {
            if (el.dataset.bound === '1') return;
            el.dataset.bound = '1';
            el.addEventListener('click', function () {
                var codeEl = document.getElementById('bgvDetailCode');
                var statusEl = document.getElementById('bgvDetailStatus');
                var reasonWrap = document.getElementById('bgvDetailReasonWrap');
                var reasonEl = document.getElementById('bgvDetailReason');
                if (codeEl) codeEl.textContent = el.getAttribute('data-code') || '—';
                if (statusEl) statusEl.textContent = el.getAttribute('data-status') || '—';
                var reason = el.getAttribute('data-reason') || '';
                if (reasonWrap && reasonEl) {
                    if (reason) {
                        reasonEl.textContent = reason;
                        reasonWrap.classList.remove('d-none');
                    } else {
                        reasonEl.textContent = '';
                        reasonWrap.classList.add('d-none');
                    }
                }
                if (detailModal && window.jQuery) {
                    window.jQuery(detailModal).modal('show');
                }
            });
        });
    }

    function findLiveRow(rows, code, requestId) {
        if (requestId) {
            var match = rows.find(function (r) {
                return r.request_id != null && String(r.request_id) === String(requestId);
            });
            if (match) return match;
        }
        return rows.find(function (r) { return r.code === code; }) || null;
    }

    function applyLiveRows(rows) {
        if (!tbody || !Array.isArray(rows)) return;

        tbody.querySelectorAll('tr[data-code]').forEach(function (tr) {
            var code = tr.getAttribute('data-code');
            var requestId = tr.getAttribute('data-request-id');
            var cell = tr.querySelector('.bgv-live-state');
            if (!cell) return;
            var live = findLiveRow(rows, code, requestId);
            cell.innerHTML = renderStatusCell(live);
        });

        if (rows.length && !tbody.querySelector('tr[data-code]')) {
            tbody.innerHTML = '';
            rows.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.setAttribute('data-code', r.code || '');
                if (r.request_id != null) {
                    tr.setAttribute('data-request-id', String(r.request_id));
                }
                tr.innerHTML =
                    '<td>' + escapeHtml(r.label || r.code || '—') + '</td>' +
                    '<td><code>' + escapeHtml(r.code || '—') + '</code></td>' +
                    '<td>' + (r.request_id != null ? escapeHtml(r.request_id) : '—') + '</td>' +
                    '<td class="bgv-live-state">' + renderStatusCell(r) + '</td>';
                tbody.appendChild(tr);
            });
        }

        bindDetailButtons(tbody);
    }

    function refreshStatus() {
        if (errBox) errBox.classList.add('d-none');
        if (loading) loading.classList.remove('d-none');
        btn.disabled = true;

        return fetch(btn.getAttribute('data-url'), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
            .then(function (result) {
                if (!result.ok || result.body.error) {
                    throw new Error(result.body.error || 'Could not load BGV status');
                }
                applyLiveRows(result.body.live_status || []);
            })
            .catch(function (e) {
                if (errBox) {
                    errBox.textContent = e.message || 'Refresh failed';
                    errBox.classList.remove('d-none');
                }
            })
            .finally(function () {
                if (loading) loading.classList.add('d-none');
                btn.disabled = false;
            });
    }

    bindDetailButtons(document);
    btn.addEventListener('click', refreshStatus);
    refreshStatus();
})();
</script>
@endif
@endif
