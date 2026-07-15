@php
    use App\Data\SrHrTeam;
    use App\Support\HrAuthorizedSignature;
    $approval = $approval ?? [];
    $compact = !empty($compact);
    $hrSigSrc = HrAuthorizedSignature::previewFromApprovalBlock($approval, !empty($signatureAsUrl));
    $hrName = $approval['approved_by_name'] ?? null;
    $hrRole = $approval['approved_by_role'] ?? SrHrTeam::roleForEmail($approval['approved_by_email'] ?? null);
    $isApproved = ($approval['status'] ?? '') === 'approved';
@endphp
<div class="sign-block{{ $compact ? ' sign-block-compact' : '' }}">
    <p class="strong">For {{ config('letterhead.company_name', config('app.name', 'Ami Polymer Pvt. Ltd.')) }}</p>
    @if ($isApproved && $hrSigSrc)
        <img src="{{ $hrSigSrc }}" alt="{{ $hrName ?? 'Signatory' }}"@if ($compact) height="32" width="110"@else width="160"@endif>
    @else
        <p class="sign-line">_________________________</p>
    @endif
    <p class="sign-name">
        <strong>{{ $hrName ?: '_________________________' }}</strong><br>
        <span class="sign-role">{{ $hrRole }}</span>
    </p>
</div>
