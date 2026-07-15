@php
    use App\Support\OnboardingArchive;
    use App\Support\OnboardingProfileReedit;

    $showPanel = OnboardingProfileReedit::hasPortalProfileData($employee);
    $isFinalized = OnboardingArchive::isFinalized($employee);
    $reeditMeta = OnboardingProfileReedit::meta($employee);
    $reeditReason = OnboardingProfileReedit::reasonLabel($reeditMeta);
    $canGrant = !$isFinalized && OnboardingProfileReedit::hrCanGrant($employee);
    $canRevoke = !$isFinalized && OnboardingProfileReedit::hrCanRevoke($employee);
    $reasonOptions = OnboardingProfileReedit::reasonOptions();
@endphp

@if ($showPanel && ($canGrant || $canRevoke || $reeditMeta))
    <div class="px-3 py-3 border-bottom bg-light" id="candidateProfileReeditPanel">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h6 class="mb-1 text-primary"><i class="dw dw-edit2"></i> Allow candidate to update submitted data</h6>
                <p class="small text-muted mb-0">
                    Use this only when the candidate must correct information on the portal <strong>Info</strong> tab.
                    A reason is required; the candidate will see it in the portal and email.
                </p>
            </div>
        </div>

        @if ($reeditMeta)
            <div class="alert alert-info py-2 small mb-2 mb-md-0">
                <strong>Re-update is open for the candidate.</strong>
                @if ($reeditReason)
                    <br><span class="text-muted">Reason:</span> {{ $reeditReason }}
                @endif
                @if (!empty($reeditMeta['allowed_by']))
                    <br><span class="text-muted">Allowed by:</span> {{ $reeditMeta['allowed_by'] }}
                    @if (!empty($reeditMeta['allowed_at']))
                        <span class="text-muted"> · {{ \Carbon\Carbon::parse($reeditMeta['allowed_at'])->format('d-M-Y H:i') }}</span>
                    @endif
                @endif
            </div>
            @if ($canRevoke)
                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="mt-2"
                      onsubmit="return confirm('Cancel re-update permission? The candidate will not be able to edit until you allow again.');">
                    @csrf
                    <input type="hidden" name="step" value="revoke_profile_reedit">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel re-update permission</button>
                </form>
            @endif
        @endif

        @if ($canGrant)
            <div class="mt-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enableProfileReedit">
                    <label class="custom-control-label font-weight-bold" for="enableProfileReedit">
                        I want to allow the candidate to re-update their submitted information
                    </label>
                </div>

                <div id="profileReeditReasonBox" class="d-none mt-3 p-3 border rounded bg-white">
                    <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" id="profileReeditGrantForm">
                        @csrf
                        <input type="hidden" name="step" value="allow_profile_reedit">

                        <div class="form-group mb-2">
                            <label for="profile_reedit_reason" class="font-weight-bold small mb-1">
                                Reason <span class="text-danger">*</span>
                            </label>
                            <select name="profile_reedit_reason" id="profile_reedit_reason" class="form-control form-control-sm" required disabled>
                                <option value="">— Select reason —</option>
                                @foreach ($reasonOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('profile_reedit_reason') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('profile_reedit_reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3" id="profileReeditDetailWrap">
                            <label for="profile_reedit_detail" class="small mb-1">
                                Additional details
                                <span class="text-danger d-none" id="profileReeditDetailRequired">*</span>
                                <span class="text-muted" id="profileReeditDetailHint">(optional)</span>
                            </label>
                            <textarea name="profile_reedit_detail" id="profile_reedit_detail" rows="2"
                                      class="form-control form-control-sm" maxlength="500" disabled
                                      placeholder="Tell the candidate what to correct">{{ old('profile_reedit_detail') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary" id="profileReeditSubmitBtn" disabled>
                            Allow re-update &amp; notify candidate
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    @if ($canGrant)
        <script>
            (function () {
                const toggle = document.getElementById('enableProfileReedit');
                const box = document.getElementById('profileReeditReasonBox');
                const reason = document.getElementById('profile_reedit_reason');
                const detail = document.getElementById('profile_reedit_detail');
                const submitBtn = document.getElementById('profileReeditSubmitBtn');
                const detailRequired = document.getElementById('profileReeditDetailRequired');
                const detailHint = document.getElementById('profileReeditDetailHint');

                if (!toggle || !box) return;

                function setFieldsEnabled(on) {
                    [reason, detail, submitBtn].forEach(function (el) {
                        if (el) el.disabled = !on;
                    });
                    box.classList.toggle('d-none', !on);
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
                    syncOtherReason();
                });

                if (reason) reason.addEventListener('change', syncOtherReason);

                document.getElementById('profileReeditGrantForm')?.addEventListener('submit', function (e) {
                    if (!toggle.checked) {
                        e.preventDefault();
                        alert('Please tick the checkbox to allow re-update.');
                        return;
                    }
                    if (!reason.value) {
                        e.preventDefault();
                        alert('Please select a reason.');
                        return;
                    }
                    if (reason.value === 'other' && (!detail.value || detail.value.trim().length < 10)) {
                        e.preventDefault();
                        alert('Please enter additional details (at least 10 characters) for "Other".');
                        return;
                    }
                    if (!confirm('Allow this candidate to edit and resubmit their information on the portal?')) {
                        e.preventDefault();
                    }
                });

                @if ($errors->has('profile_reedit_reason') || old('profile_reedit_reason'))
                    toggle.checked = true;
                    setFieldsEnabled(true);
                    syncOtherReason();
                @endif
            })();
        </script>
    @endif
@endif
