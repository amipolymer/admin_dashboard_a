@php
    use App\Data\SrHrTeam;
    use App\Support\HrAuthorizedSignature;
    $badgeClass = match ($summary['status'] ?? '') {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning',
        default => 'secondary',
    };
    $approvalBlock = \App\Support\SrHrLetterApproval::state($employee, $type);
    $sigUrl = HrAuthorizedSignature::previewFromApprovalBlock($approvalBlock, true);
    $sigSrc = HrAuthorizedSignature::srcFromApprovalBlock($approvalBlock);
    $isApproved = ($summary['status'] ?? '') === 'approved';
    $reassignStep = $type === \App\Support\SrHrLetterApproval::TYPE_OFFER
        ? 'reassign_offer_sr_approval'
        : 'reassign_appointment_sr_approval';
    $resendStep = $type === \App\Support\SrHrLetterApproval::TYPE_OFFER
        ? 'resend_offer_sr_approval'
        : 'resend_appointment_sr_approval';
    $notifyNames = collect($summary['notify_emails'] ?? [])
        ->map(fn ($email) => SrHrTeam::displayNameForEmail($email))
        ->filter()
        ->values()
        ->all();
@endphp

<div class="sr-hr-compact mb-2">
    @if ($isApproved)
        <div class="d-flex align-items-center flex-wrap gap-2 small">
            <span class="badge badge-success">Approved</span>
            @if ($summary['approved_by_name'])
                <span>{{ $summary['approved_by_name'] }}</span>
            @endif
            @if ($summary['approved_at'])
                <span class="text-muted">· {{ \Carbon\Carbon::parse($summary['approved_at'])->format('d M Y') }}</span>
            @endif
            @if ($sigUrl || $sigSrc)
                <img src="{{ $sigUrl ?: $sigSrc }}" alt="Signature" style="max-height:32px;border:1px solid #ddd;padding:1px;">
            @endif
        </div>
    @elseif (($summary['status'] ?? '') === 'pending')
        <p class="mb-1 small">
            <span class="badge badge-warning">Pending</span>
            @if ($summary['requested_at'])
                <span class="text-muted">since {{ \Carbon\Carbon::parse($summary['requested_at'])->format('d M Y') }}</span>
            @endif
        </p>
        @if ($notifyNames !== [])
            <p class="mb-2 small"><strong>Assigned to:</strong> {{ implode(', ', $notifyNames) }}</p>
        @endif
        @if (!empty($approvalBlock['token']))
            @php $approvalUrl = \App\Support\SrHrLetterApproval::approvalUrl($approvalBlock['token']); @endphp
            <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control font-monospace small" value="{{ $approvalUrl }}" readonly onclick="this.select()">
                <div class="input-group-append">
                    <a href="{{ $approvalUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">Open</a>
                </div>
            </div>
        @endif
        <div class="border rounded p-2 bg-white mb-2">
            <p class="small font-weight-bold mb-2 mb-md-1">Change SR-HR approver</p>
            <p class="small text-muted mb-2">Use if the assigned person is on leave or unavailable. The same approval link stays valid.</p>
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="mb-0">
                @csrf
                <input type="hidden" name="step" value="{{ $reassignStep }}">
                @include('pages.new-join-employee.partials.sr-hr-email-picker', [
                    'pickerId' => 'reassign_' . $type,
                    'showConfirm' => false,
                    'compact' => true,
                    'selectedEmail' => $summary['notify_emails'][0] ?? '',
                ])
                <button type="submit" class="btn btn-sm btn-outline-primary">Send to selected SR-HR</button>
            </form>
        </div>
        <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">
            @csrf
            <button type="submit" name="step" value="{{ $resendStep }}" class="btn btn-sm btn-outline-secondary">
                Resend email to current SR-HR
            </button>
        </form>
    @elseif (($summary['status'] ?? '') === 'rejected')
        <p class="mb-1 small">
            <span class="badge badge-danger">Rejected</span>
            @if ($summary['rejected_at'])
                <span class="text-muted">{{ \Carbon\Carbon::parse($summary['rejected_at'])->format('d M Y') }}</span>
            @endif
        </p>
        @if ($summary['reject_reason'])
            <p class="mb-0 small text-danger">{{ $summary['reject_reason'] }}</p>
        @endif
    @else
        <span class="badge badge-secondary">{{ $summary['label'] ?? '—' }}</span>
    @endif
</div>
