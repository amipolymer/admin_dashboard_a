@php
    use App\Support\OnboardingLetterDeadline;
    $type = $type ?? 'offer';
    $dueDate = $dueDate ?? null;
    $title = $title ?? OnboardingLetterDeadline::expiredTitle($type);
    $message = $message ?? OnboardingLetterDeadline::expiredMessage($type);
@endphp
<div class="alert alert-danger mb-0">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>{{ $title }}</strong>
    <p class="mb-0 mt-2">{{ $message }}</p>
    @if ($dueDate)
        <p class="small mb-0 mt-2"><strong>Last date:</strong> {{ $dueDate->format('d M Y') }}</p>
    @endif
    <p class="small mb-0 mt-2">Please contact your <strong>HR team</strong> to request a new deadline.</p>
</div>
