@php
    use App\Support\OnboardingArchive;
    use App\Support\OnboardingDocumentReedit;

    $isFinalized = OnboardingArchive::isFinalized($employee);
    $reeditMeta = OnboardingDocumentReedit::meta($employee);
    $reeditOpen = OnboardingDocumentReedit::isOpenForCandidate($employee);
    $reeditReason = OnboardingDocumentReedit::reasonLabel($reeditMeta);
    $canGrant = OnboardingDocumentReedit::hrCanGrant($employee);
    $canRevoke = OnboardingDocumentReedit::hrCanRevoke($employee);
    $reasonOptions = OnboardingDocumentReedit::reasonOptions();
    $documentOptions = OnboardingDocumentReedit::documentOptions($employee);
    $missingDocKeys = OnboardingDocumentReedit::missingDocumentKeys($employee);
    $uploadedDocKeys = \App\Support\OnboardingDocumentRequirements::uploadedKeys($employee);
    $bgvGaps = OnboardingDocumentReedit::bgvGaps($employee);
    $showPanel = OnboardingDocumentReedit::hrShouldShowPanel($employee);
    $postOffer = OnboardingDocumentReedit::isPostOfferStage($employee);
    $canStartBgv = OnboardingDocumentReedit::canStartBgv($employee);
    $recentResubmit = OnboardingDocumentReedit::wasRecentlyResubmitted($employee);
    $expandDocumentReedit = $reeditOpen
        || $recentResubmit
        || ($bgvGaps !== [] && $postOffer)
        || OnboardingDocumentReedit::isReadyForBgv($employee)
        || $errors->has('document_reedit_reason')
        || $errors->has('document_reedit_keys');
@endphp

@if ($showPanel)
<div class="card mb-3 shadow-sm border-secondary" id="candidateDocumentReeditPanel">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center hr-collapse-toggle"
         data-toggle="collapse" data-target="#collapseDocumentReedit" aria-expanded="{{ $expandDocumentReedit ? 'true' : 'false' }}">
        <strong><i class="dw dw-upload text-secondary"></i> Document re-upload</strong>
        <small class="text-muted collapse-hint">{{ $expandDocumentReedit ? 'Hide' : 'Show' }}</small>
    </div>
    <div id="collapseDocumentReedit" class="collapse {{ $expandDocumentReedit ? 'show' : '' }}">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h6 class="mb-1 text-primary"><i class="dw dw-upload"></i> Allow candidate to re-upload documents</h6>
                <p class="small text-muted mb-0">
                    Request missing or unclear files at any stage before onboarding is finalized (e.g. salary slip, photo, ID proof).
                    Works whether documents are approved or not, and while the candidate is in BGV or later steps.
                    For wrong profile details, use <strong>Allow candidate to update submitted data</strong> (Info tab) above.
                </p>
            </div>
        </div>

        @if (!$reeditMeta && $bgvGaps !== [] && OnboardingDocumentReedit::isPostOfferStage($employee))
            <div class="alert alert-warning py-2 small mb-2">
                <strong>BGV may fail until resolved:</strong>
                <ul class="mb-0 pl-3 mt-1">
                    @foreach ($bgvGaps as $gap)
                        <li>{{ $gap }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($recentResubmit && $employee->emp_document_status === 'process')
            <div class="alert alert-warning py-2 small mb-2">
                <strong>Candidate re-uploaded documents.</strong> Approve them in <strong>Uploaded documents</strong> below — then <strong>Start OnGrid BGV</strong> will appear in Onboarding Actions.
            </div>
        @elseif (OnboardingDocumentReedit::isReadyForBgv($employee) && $employee->emp_document_status === 'completed')
            <div class="alert alert-success py-2 small mb-2">
                <strong>Re-uploaded documents approved.</strong> Start BGV from Onboarding Actions.
                @if ($canStartBgv)
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-info" onclick="typeof openOfferingModal === 'function' ? openOfferingModal() : window.location='{{ route('EmployeeJoiner.Show', $employee->id) }}#collapseHrActions'">
                            Start / Retry OnGrid BGV
                        </button>
                    </div>
                @endif
            </div>
        @endif

        @if ($reeditOpen)
            <div class="alert alert-success py-2 small mb-2 d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <strong>Re-upload permission is active.</strong> The candidate can upload the document types listed below on the portal.
                    @if ($reeditReason)
                        <br><span class="text-muted">Reason:</span> {{ $reeditReason }}
                    @endif
                    @if (!empty($reeditMeta['document_keys']))
                        <br><span class="text-muted">Allowed:</span>
                        {{ collect($reeditMeta['document_keys'])->map(fn ($k) => $documentOptions[$k] ?? $k)->implode(', ') }}
                    @endif
                    @if (!empty($reeditMeta['allowed_by']))
                        <br><span class="text-muted">Allowed by:</span> {{ $reeditMeta['allowed_by'] }}
                        @if (!empty($reeditMeta['allowed_at']))
                            <span class="text-muted"> · {{ \Carbon\Carbon::parse($reeditMeta['allowed_at'])->format('d-M-Y H:i') }}</span>
                        @endif
                    @endif
                </div>
                @if ($canRevoke)
                    <form method="POST" action="{{ route('EmployeeJoiner.revokeDocumentReedit', $employee->id) }}" class="mb-0 flex-shrink-0"
                          onsubmit="return confirm('Cancel re-upload permission? The candidate will not be able to upload documents until you allow again.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="dw dw-cancel"></i> Cancel re-upload request
                        </button>
                    </form>
                @endif
            </div>
        @else
            <div class="alert alert-light border py-2 small mb-2">
                <strong>Re-upload is blocked.</strong> The candidate cannot upload or replace documents unless you allow it below.
            </div>
        @endif

        @if ($canGrant)
            <div class="mt-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enableDocumentReedit">
                    <label class="custom-control-label font-weight-bold" for="enableDocumentReedit">
                        Allow candidate to upload or replace selected documents
                    </label>
                </div>

                <div id="documentReeditReasonBox" class="d-none mt-3 p-3 border rounded bg-white">
                    <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" id="documentReeditGrantForm">
                        @csrf
                        <input type="hidden" name="step" value="allow_document_reedit">

                        <div class="form-group mb-2">
                            <label for="document_reedit_reason" class="font-weight-bold small mb-1">
                                Reason <span class="text-danger">*</span>
                            </label>
                            <select name="document_reedit_reason" id="document_reedit_reason" class="form-control form-control-sm" required disabled>
                                <option value="">— Select reason —</option>
                                @foreach ($reasonOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('document_reedit_reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('document_reedit_reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-2" id="documentReeditKeysWrap">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <span class="font-weight-bold small">Documents candidate may upload <span class="text-danger">*</span></span>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="documentReeditSelectMissing" disabled>
                                        <label class="custom-control-label small" for="documentReeditSelectMissing">Missing only</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="documentReeditSelectAll" disabled>
                                        <label class="custom-control-label small" for="documentReeditSelectAll">Select all</label>
                                    </div>
                                </div>
                            </div>
                            @if ($missingDocKeys !== [])
                                <p class="small text-muted mb-2">
                                    Not uploaded yet:
                                    <strong>{{ collect($missingDocKeys)->map(fn ($k) => $documentOptions[$k] ?? $k)->implode(', ') }}</strong>
                                </p>
                            @endif
                            <div class="row">
                                @foreach ($documentOptions as $key => $label)
                                    @php
                                        $isMissing = in_array($key, $missingDocKeys, true);
                                        $isUploaded = in_array($key, $uploadedDocKeys, true);
                                    @endphp
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input document-reedit-key"
                                                   name="document_reedit_keys[]" value="{{ $key }}"
                                                   id="docReedit{{ $key }}" disabled
                                                   data-missing="{{ $isMissing ? '1' : '0' }}"
                                                   @checked(in_array($key, old('document_reedit_keys', $missingDocKeys)))>
                                            <label class="custom-control-label small" for="docReedit{{ $key }}">
                                                {{ $label }}
                                                @if ($isMissing)
                                                    <span class="badge badge-warning text-dark">Not uploaded</span>
                                                @elseif ($isUploaded)
                                                    <span class="badge badge-light border">Uploaded</span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('document_reedit_keys')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3" id="documentReeditDetailWrap">
                            <label for="document_reedit_detail" class="small mb-1">
                                Additional details
                                <span class="text-danger d-none" id="documentReeditDetailRequired">*</span>
                                <span class="text-muted" id="documentReeditDetailHint">(optional)</span>
                            </label>
                            <textarea name="document_reedit_detail" id="document_reedit_detail" rows="2"
                                      class="form-control form-control-sm" maxlength="500" disabled
                                      placeholder="Tell the candidate what to upload">{{ old('document_reedit_detail') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary" id="documentReeditSubmitBtn" disabled>
                            Allow re-upload &amp; notify candidate
                        </button>
                    </form>
                </div>
            </div>
        @elseif (!$reeditOpen && !$canGrant)
            <p class="small text-muted mb-0">
                Allow re-upload will be available after the candidate saves their profile or uploads documents on the portal.
            </p>
        @endif
    </div>
    </div>
</div>

    @if ($canGrant)
        <script>
            (function () {
                const toggle = document.getElementById('enableDocumentReedit');
                const box = document.getElementById('documentReeditReasonBox');
                const reason = document.getElementById('document_reedit_reason');
                const detail = document.getElementById('document_reedit_detail');
                const submitBtn = document.getElementById('documentReeditSubmitBtn');
                const detailRequired = document.getElementById('documentReeditDetailRequired');
                const detailHint = document.getElementById('documentReeditDetailHint');
                const keysWrap = document.getElementById('documentReeditKeysWrap');
                const presetMap = @json(OnboardingDocumentReedit::reasonDocumentMap());
                const missingKeys = @json($missingDocKeys);
                const selectAll = document.getElementById('documentReeditSelectAll');
                const selectMissing = document.getElementById('documentReeditSelectMissing');

                if (!toggle || !box) return;

                function keyCheckboxes() {
                    return Array.from(document.querySelectorAll('.document-reedit-key'));
                }

                function checkKeys(keys) {
                    const set = new Set(keys || []);
                    keyCheckboxes().forEach(function (el) {
                        el.checked = set.has(el.value);
                    });
                    syncBulkToggles();
                }

                function missingOnlyKeys() {
                    return keyCheckboxes()
                        .filter(function (el) { return el.dataset.missing === '1'; })
                        .map(function (el) { return el.value; });
                }

                function syncBulkToggles() {
                    const boxes = keyCheckboxes().filter(function (el) { return !el.disabled; });
                    if (!selectAll || !selectMissing || boxes.length === 0) return;
                    const checked = boxes.filter(function (el) { return el.checked; });
                    const missing = boxes.filter(function (el) { return el.dataset.missing === '1'; });
                    const allChecked = checked.length === boxes.length;
                    const missingChecked = missing.length > 0 && missing.every(function (el) { return el.checked; })
                        && checked.length === missing.length;
                    selectAll.checked = allChecked;
                    selectMissing.checked = missingChecked;
                    selectAll.indeterminate = checked.length > 0 && !allChecked;
                    selectMissing.indeterminate = checked.length > 0 && !missingChecked && !allChecked;
                }

                function setFieldsEnabled(on) {
                    [reason, detail, submitBtn, selectAll, selectMissing].forEach(function (el) {
                        if (el) el.disabled = !on;
                    });
                    keyCheckboxes().forEach(function (el) { el.disabled = !on; });
                    box.classList.toggle('d-none', !on);
                    if (on) {
                        checkKeys(missingOnlyKeys());
                    } else {
                        checkKeys([]);
                    }
                }

                function applyPreset() {
                    const preset = presetMap[reason?.value] || [];
                    const manualReasons = ['hr_review_missing', 'documents_mismatch', 'other'];
                    const showKeys = preset.length === 0;
                    if (keysWrap) keysWrap.classList.toggle('d-none', !showKeys);
                    if (!showKeys) {
                        checkKeys(preset);
                        keyCheckboxes().forEach(function (el) { el.disabled = true; });
                        if (selectAll) selectAll.disabled = true;
                        if (selectMissing) selectMissing.disabled = true;
                        return;
                    }
                    if (toggle.checked) {
                        keyCheckboxes().forEach(function (el) { el.disabled = false; });
                        if (selectAll) selectAll.disabled = false;
                        if (selectMissing) selectMissing.disabled = false;
                    }
                    if (reason && manualReasons.indexOf(reason.value) !== -1) {
                        if (reason.value === 'hr_review_missing') {
                            checkKeys(missingOnlyKeys().length ? missingOnlyKeys() : missingKeys);
                        } else if (reason.value === 'documents_mismatch') {
                            checkKeys(missingOnlyKeys());
                        }
                    }
                    syncBulkToggles();
                }

                function syncOtherReason() {
                    const isOther = reason && reason.value === 'other';
                    if (detailRequired) detailRequired.classList.toggle('d-none', !isOther);
                    if (detailHint) detailHint.classList.toggle('d-none', isOther);
                    if (detail) detail.required = isOther;
                }

                toggle.addEventListener('change', function () {
                    setFieldsEnabled(toggle.checked);
                    if (!toggle.checked && reason) reason.value = '';
                    applyPreset();
                    syncOtherReason();
                });

                if (reason) reason.addEventListener('change', function () {
                    applyPreset();
                    syncOtherReason();
                });

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        if (selectAll.checked) {
                            checkKeys(keyCheckboxes().map(function (el) { return el.value; }));
                        } else {
                            checkKeys([]);
                        }
                    });
                }

                if (selectMissing) {
                    selectMissing.addEventListener('change', function () {
                        if (selectMissing.checked) {
                            checkKeys(missingOnlyKeys().length ? missingOnlyKeys() : missingKeys);
                        } else {
                            checkKeys([]);
                        }
                    });
                }

                keyCheckboxes().forEach(function (el) {
                    el.addEventListener('change', syncBulkToggles);
                });

                document.getElementById('documentReeditGrantForm')?.addEventListener('submit', function (e) {
                    if (!toggle.checked) {
                        e.preventDefault();
                        return;
                    }
                    if (!reason.value) {
                        e.preventDefault();
                        alert('Please select a reason.');
                        return;
                    }
                    const preset = presetMap[reason.value] || [];
                    if (preset.length === 0 && keyCheckboxes().filter(function (el) { return el.checked; }).length === 0) {
                        e.preventDefault();
                        alert('Select at least one document type.');
                        return;
                    }
                    if (reason.value === 'other' && (!detail.value || detail.value.trim().length < 10)) {
                        e.preventDefault();
                        alert('Please enter additional details (at least 10 characters) for "Other".');
                        return;
                    }
                    if (preset.length) {
                        keyCheckboxes().forEach(function (el) { el.disabled = false; });
                    }
                    if (!confirm('Allow this candidate to upload documents on the portal?')) {
                        e.preventDefault();
                    }
                });

                @if ($errors->has('document_reedit_reason') || old('document_reedit_reason'))
                    toggle.checked = true;
                    setFieldsEnabled(true);
                    applyPreset();
                    syncOtherReason();
                @endif
            })();
        </script>
    @endif
@endif
