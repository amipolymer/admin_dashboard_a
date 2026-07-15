@php
    use App\Support\OnboardingLetterResend;
    use App\Support\OnboardingStepGate;

    $hrLocked = OnboardingStepGate::isHrMutationsBlocked($employee);
    $canResendOfferSr = !$hrLocked && OnboardingLetterResend::canResendOfferSrApproval($employee);
    $canResendOfferCandidate = !$hrLocked && OnboardingLetterResend::canResendOfferToCandidate($employee);
    $canResendApptCandidate = !$hrLocked && OnboardingLetterResend::canResendAppointmentToCandidate($employee);
    $canResendApptSr = !$hrLocked && OnboardingLetterResend::canResendAppointmentSrApproval($employee);
    $canResendRegistration = !$hrLocked && OnboardingLetterResend::canResendRegistrationRequest($employee);
    $canResendPortalLink = OnboardingLetterResend::canResendPortalLink($employee);
    $hasAnyReminder = $canResendOfferSr || $canResendOfferCandidate
        || $canResendApptCandidate || $canResendApptSr
        || $canResendRegistration || $canResendPortalLink;
@endphp

@if ($hasAnyReminder)
<div class="border-top pt-3 mt-2">
    <strong class="small d-block mb-2"><i class="dw dw-mail"></i> Email reminders</strong>
    <div class="d-flex flex-wrap gap-2">

        @if ($canResendPortalLink)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                <button type="submit" name="step" value="resend_portal_upload_link" class="btn btn-sm btn-outline-secondary">Resend Portal Link</button>
            </form>
        @endif

        @if ($canResendOfferSr)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                <button type="submit" name="step" value="resend_offer_sr_approval" class="btn btn-sm btn-outline-secondary">Resend SR-HR Offer Email</button>
            </form>
        @endif
        @if ($canResendOfferCandidate)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                <button type="submit" name="step" value="resend_offer_to_candidate" class="btn btn-sm btn-outline-secondary">Resend Offer to Candidate</button>
            </form>
        @endif

        @if ($canResendApptCandidate)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                <button type="submit" name="step" value="resend_appointment_to_candidate" class="btn btn-sm btn-outline-secondary">Resend Appointment to Candidate</button>
            </form>
        @endif
        @if ($canResendApptSr)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                <button type="submit" name="step" value="resend_appointment_sr_approval" class="btn btn-sm btn-outline-secondary">Resend SR-HR Appointment Email</button>
            </form>
        @endif

        @if ($canResendRegistration)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                <button type="submit" name="step" value="resend_registration_request" class="btn btn-sm btn-outline-secondary">Resend Registration Request</button>
            </form>
        @endif
    </div>
</div>
@endif
