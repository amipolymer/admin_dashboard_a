@php
    use App\Support\OnboardingLetterDeadline;
    use App\Support\OnboardingStepGate;

    $offerAvailable = in_array($currentStep, ['offer_sent', 'offer_accepted', 'offer_rejected'], true) || $employee->emp_offer_sent_at;
    $offerAccepted = $employee->emp_offer_letter_status === 'accept';
    $offerRejected = $employee->emp_offer_letter_status === 'reject';
    $offerCanAct = OnboardingLetterDeadline::canCandidateActOnOffer($employee);
    $offerPreviewUrl = route('offer.preview.pdf', ['id' => $employee->id, 'token' => $employee->emp_url]);

    $showRegistration = in_array($currentStep, ['registration_sent', 'registration_submitted'], true)
        || (OnboardingStepGate::isRegistrationVerified($employee) && $employee->emp_offer_letter_status === 'accept');

    $appointmentAvailable = in_array($currentStep, [
        'appointment_sent', 'appointment_accepted', 'appointment_pending_sr_hr',
        'appointment_sr_rejected', 'appointment_rejected', 'end',
    ], true) || $employee->emp_appointment_sent_at;
    $appointmentAccepted = $employee->emp_appointment_letter_status === 'accept';
    $appointmentRejected = $employee->emp_appointment_letter_status === 'reject';
    $appointmentCanAct = OnboardingLetterDeadline::canCandidateActOnAppointment($employee);
    $appointmentPreviewUrl = route('appointment.preview.pdf', ['id' => $employee->id, 'token' => $employee->emp_url]);

    $letterSections = [];
    if ($offerAvailable) {
        $letterSections[] = 'offer';
    }
    if ($showRegistration) {
        $letterSections[] = 'registration';
    }
    if ($appointmentAvailable) {
        $letterSections[] = 'appointment';
    }
@endphp

<style>
    .letter-readonly .accordion-button { font-size: 0.95rem; font-weight: 600; color: #034ea1; }
    .letter-readonly .accordion-button:not(.collapsed) { background: #f0f6fc; }
</style>

<h4 class="mb-3 mb-md-4"><i class="bi bi-file-earmark-text me-2"></i>Letters</h4>

@if ($letterSections === [])
    <div class="alert alert-info mb-0">Letters will appear here after HR sends your offer.</div>
@else
    <div class="accordion letter-readonly" id="letterPortalAccordion">
        @if ($offerAvailable)
            @php
                $offerExpanded = $offerCanAct && !$offerAccepted && !$offerRejected;
                $offerCollapseId = 'letterOfferCollapse';
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $offerExpanded ? '' : 'collapsed' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#{{ $offerCollapseId }}"
                        aria-expanded="{{ $offerExpanded ? 'true' : 'false' }}">
                        Offer Letter
                        @if ($offerAccepted)
                            <span class="badge bg-success ms-2">Accepted</span>
                        @elseif ($offerRejected)
                            <span class="badge bg-warning text-dark ms-2">Declined</span>
                        @elseif ($offerCanAct)
                            <span class="badge bg-danger ms-2">Action required</span>
                        @endif
                    </button>
                </h2>
                <div id="{{ $offerCollapseId }}" class="accordion-collapse collapse {{ $offerExpanded ? 'show' : '' }}"
                    data-bs-parent="#letterPortalAccordion">
                    <div class="accordion-body pt-2">
                        @if ($offerAccepted || $offerRejected)
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-0">
                                @if ($offerAccepted)
                                    <span class="text-success small">You have accepted this offer.</span>
                                @else
                                    <span class="text-muted small">You declined this offer.</span>
                                @endif
                                <a href="{{ $offerPreviewUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-pdf me-1"></i>View PDF
                                </a>
                            </div>
                        @elseif (OnboardingLetterDeadline::isOfferExpired($employee))
                            @include('shared.letter-deadline-expired', [
                                'type' => OnboardingLetterDeadline::TYPE_OFFER,
                                'dueDate' => $employee->emp_offer_due_date,
                            ])
                        @else
                            @if (OnboardingLetterDeadline::isOfferPending($employee) && $employee->emp_offer_due_date)
                                <p class="text-danger small mb-2">Accept before due date: <strong>{{ $employee->emp_offer_due_date->format('d M Y') }}</strong></p>
                            @endif
                            @include('shared.offer-letter-panel', [
                                'employee' => $employee,
                                'returnUrl' => route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'letter']),
                                'iframeHeight' => '75vh',
                                'hideAlerts' => true,
                            ])
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($showRegistration)
            @php
                $regExpanded = $currentStep === 'registration_sent' && !OnboardingLetterDeadline::isRegistrationExpired($employee);
                $regCollapseId = 'letterRegCollapse';
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $regExpanded ? '' : 'collapsed' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#{{ $regCollapseId }}"
                        aria-expanded="{{ $regExpanded ? 'true' : 'false' }}">
                        Resignation Acceptance Letter
                        @if ($currentStep === 'registration_submitted')
                            <span class="badge bg-warning text-dark ms-2">Submitted</span>
                        @elseif (OnboardingStepGate::isRegistrationVerified($employee))
                            <span class="badge bg-success ms-2">Verified</span>
                        @elseif ($regExpanded)
                            <span class="badge bg-danger ms-2">Upload required</span>
                        @endif
                    </button>
                </h2>
                <div id="{{ $regCollapseId }}" class="accordion-collapse collapse {{ $regExpanded ? 'show' : '' }}"
                    data-bs-parent="#letterPortalAccordion">
                    <div class="accordion-body pt-2">
                        @if ($currentStep === 'registration_sent')
                            @if (OnboardingLetterDeadline::isRegistrationExpired($employee))
                                @include('shared.letter-deadline-expired', [
                                    'type' => OnboardingLetterDeadline::TYPE_REGISTRATION,
                                    'dueDate' => $employee->emp_registration_due_date,
                                ])
                            @else
                                <p class="small text-muted mb-2">Upload the letter from your <strong>previous employer</strong> confirming resignation acceptance.</p>
                                @if ($employee->emp_registration_due_date)
                                    <p class="text-danger small mb-2">Upload before due date: <strong>{{ $employee->emp_registration_due_date->format('d M Y') }}</strong></p>
                                @endif
                                <form method="POST" action="{{ route('onboarding.save', $employee->emp_url) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="action" value="registration">
                                    <label class="form-label">Upload letter (PDF) @include('frontend.onboarding.partials.required-asterisk')</label>
                                    <input type="file" name="registration_file" class="form-control mb-2" required accept=".pdf,application/pdf">
                                    <button type="submit" class="btn btn-brand btn-sm">Upload</button>
                                </form>
                            @endif
                        @else
                            <p class="mb-0 small text-muted">
                                @if (OnboardingStepGate::isRegistrationVerified($employee))
                                    Verified by HR. View under the <strong>Documents</strong> tab.
                                @else
                                    Submitted — awaiting HR verification.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($appointmentAvailable)
            @php
                $apptExpanded = $appointmentCanAct && !$appointmentAccepted && !$appointmentRejected;
                $apptCollapseId = 'letterApptCollapse';
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $apptExpanded ? '' : 'collapsed' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#{{ $apptCollapseId }}"
                        aria-expanded="{{ $apptExpanded ? 'true' : 'false' }}">
                        Appointment Letter
                        @if ($appointmentAccepted)
                            <span class="badge bg-success ms-2">Accepted</span>
                        @elseif ($appointmentRejected)
                            <span class="badge bg-warning text-dark ms-2">Declined</span>
                        @elseif ($appointmentCanAct)
                            <span class="badge bg-danger ms-2">Action required</span>
                        @endif
                    </button>
                </h2>
                <div id="{{ $apptCollapseId }}" class="accordion-collapse collapse {{ $apptExpanded ? 'show' : '' }}"
                    data-bs-parent="#letterPortalAccordion">
                    <div class="accordion-body pt-2">
                        @if ($appointmentAccepted || $appointmentRejected)
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-0">
                                @if ($appointmentAccepted)
                                    <span class="text-success small">You have accepted your appointment letter.</span>
                                @else
                                    <span class="text-muted small">You declined the appointment letter.</span>
                                @endif
                                <a href="{{ $appointmentPreviewUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-pdf me-1"></i>View PDF
                                </a>
                            </div>
                        @elseif (OnboardingLetterDeadline::isAppointmentExpired($employee))
                            @include('shared.letter-deadline-expired', [
                                'type' => OnboardingLetterDeadline::TYPE_APPOINTMENT,
                                'dueDate' => $employee->emp_appointment_due_date,
                            ])
                        @else
                            @if (OnboardingLetterDeadline::isAppointmentPending($employee) && $employee->emp_appointment_due_date)
                                <p class="text-danger small mb-2">Accept before due date: <strong>{{ $employee->emp_appointment_due_date->format('d M Y') }}</strong></p>
                            @endif
                            @include('shared.appointment-letter-panel', [
                                'employee' => $employee,
                                'returnUrl' => route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'letter']),
                                'iframeHeight' => '50vh',
                            ])
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
