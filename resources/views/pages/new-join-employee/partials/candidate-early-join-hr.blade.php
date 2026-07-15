@php
    use App\Support\OnboardingArchive;
    use App\Support\OnboardingEarlyJoin;

    $isFinalized = OnboardingArchive::isFinalized($employee);
    $earlyMeta = OnboardingEarlyJoin::meta($employee);
    $earlyReason = OnboardingEarlyJoin::reasonLabel($earlyMeta);
    $canGrant = !$isFinalized && OnboardingEarlyJoin::hrCanGrant($employee);
    $canRevoke = !$isFinalized && OnboardingEarlyJoin::hrCanRevoke($employee);
    $canStartJoin = OnboardingEarlyJoin::hrCanStartJoin($employee);
    $reasonOptions = OnboardingEarlyJoin::reasonOptions();
    $step = $employee->onboardingStep();
    $showPanel = $canGrant || $canRevoke || $earlyMeta || ($canStartJoin && $step !== 'bgv_completed');
    $expandEarlyJoin = $earlyMeta
        || $errors->has('early_join_reason')
        || $errors->has('early_join_detail');
@endphp

@if ($showPanel)
<div class="card mb-3 shadow-sm border-warning" id="candidateEarlyJoinPanel">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center hr-collapse-toggle"
         data-toggle="collapse" data-target="#collapseEarlyJoin" aria-expanded="{{ $expandEarlyJoin ? 'true' : 'false' }}">
        <strong><i class="dw dw-calendar-1 text-warning"></i> Early join (before BGV complete)</strong>
        <small class="text-muted collapse-hint">{{ $expandEarlyJoin ? 'Hide' : 'Show' }}</small>
    </div>
    <div id="collapseEarlyJoin" class="collapse {{ $expandEarlyJoin ? 'show' : '' }}">
    <div class="card-body py-3">
        <p class="small text-muted mb-2">
            If BGV is still in progress but the candidate must join on schedule, HR can allow the join process to start early with a documented reason.
        </p>

        @if ($earlyMeta)
            <div class="alert alert-warning py-2 small mb-2">
                <strong>Early join is allowed.</strong>
                @if ($earlyReason)
                    <br><span class="text-muted">Reason:</span> {{ $earlyReason }}
                @endif
                @if (!empty($earlyMeta['granted_at']))
                    <br><span class="text-muted">Granted:</span> {{ \Carbon\Carbon::parse($earlyMeta['granted_at'])->format('d M Y, h:i A') }}
                @endif
            </div>
        @endif

        @if ($canGrant)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="mb-2">
                @csrf
                <input type="hidden" name="step" value="allow_early_join">
                <div class="form-row">
                    <div class="form-group col-md-5 mb-2">
                        <label class="font-weight-bold small mb-1">Reason *</label>
                        <select name="early_join_reason" class="form-control form-control-sm" required>
                            <option value="">— Select —</option>
                            @foreach ($reasonOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('early_join_reason') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-5 mb-2">
                        <label class="font-weight-bold small mb-1">Additional detail</label>
                        <input type="text" name="early_join_detail" class="form-control form-control-sm" maxlength="500"
                            value="{{ old('early_join_detail') }}" placeholder="Required if reason is Other">
                    </div>
                    <div class="form-group col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-warning btn-block">Allow early join</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($canRevoke)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline"
                onsubmit="return confirm('Cancel early join permission?');">
                @csrf
                <input type="hidden" name="step" value="revoke_early_join">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel early join permission</button>
            </form>
        @endif
    </div>
    </div>
</div>
@endif
