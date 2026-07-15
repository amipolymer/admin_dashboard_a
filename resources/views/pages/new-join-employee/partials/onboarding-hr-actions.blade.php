@php
    use App\Support\OnboardingDocumentReedit;
    use App\Support\OnboardingDocumentDeadline;
    use App\Support\OnboardingEarlyJoin;
    use App\Support\OnboardingLetterDeadline;
    use App\Support\OnboardingLetterResend;
    use App\Support\OnboardingStepGate;
    use App\Support\OnboardingArchive;
    use App\Support\SrHrLetterApproval;
    $canStartBgv = OnboardingDocumentReedit::canStartBgv($employee);
    $step = $employee->onboardingStep();
    $stepLabel = OnboardingStepGate::humanStepLabel($step);
    $offerSr = SrHrLetterApproval::state($employee, SrHrLetterApproval::TYPE_OFFER);
    $apptSr = SrHrLetterApproval::state($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    $apptSrApproved = SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    $canSubmitApptSr = in_array($step, ['appointment_accepted', 'appointment_sr_rejected'], true)
        && $employee->emp_appointment_letter_status === 'accept'
        && !$apptSrApproved;
    $regDoc = $employee->resignationLetterDocument();
    $offer = ($employee->emp_other ?? [])['offer_letter'] ?? [];
    $appt = ($employee->emp_other ?? [])['appointment_letter'] ?? [];
    $appointmentAccepted = $employee->emp_appointment_letter_status === 'accept'
        || in_array($step, ['appointment_accepted', 'end'], true);
    $offerAlreadySent = $employee->emp_offer_sent_at
        || in_array($step, ['offer_pending_sr_hr', 'offer_sent', 'offer_accepted', 'offer_rejected', 'registration_sent', 'registration_submitted', 'registration_verified', 'bgv_started', 'bgv_completed', 'join_forms_sent', 'join_forms_submitted', 'policy_signed', 'appointment_sent', 'appointment_accepted', 'appointment_rejected', 'end'], true);
    $canSendOffer = $employee->canProceedToOfferLetter()
        && !in_array($step, ['offer_accepted', 'end'], true)
        && !in_array($step, ['offer_pending_sr_hr', 'offer_sent'], true);
    $canSubmitOfferSr = OnboardingLetterResend::canReviseOfferForSrHr($employee);
    $canReviseAppointment = OnboardingLetterResend::canReviseAppointmentForCandidate($employee);
    $profileComplete = $employee->isProfileComplete();
    $documentsSubmitted = $employee->hasCandidateSubmittedDocuments();
    $joinToday = $employee->joinDateIsToday();
    $scheduledJoin = $employee->scheduledJoinDate();
    $joinDateMin = OnboardingEarlyJoin::joinDateWindowStart()->format('Y-m-d');
    $joinDateMax = OnboardingEarlyJoin::joinDateWindowEnd()->format('Y-m-d');
    $defaultJoinDate = old('emp_joining_date', $employee->emp_joining_date?->format('Y-m-d') ?? $scheduledJoin?->format('Y-m-d'));
    $joinDateAllowed = OnboardingEarlyJoin::isJoinDateAllowedForStartProcess($defaultJoinDate);
    $hasOfferLetter = !empty($offer) || $employee->emp_offer_sent_at;
    $hasApptLetter = !empty($appt) || $employee->emp_appointment_sent_at;
    $expandHrActions = $expandHrActions ?? (
        ($employee->emp_document_status ?? '') === 'process'
        || $canStartBgv
        || OnboardingDocumentReedit::wasRecentlyResubmitted($employee)
        || in_array($step, ['documents_submitted', 'documents_approved', 'offer_accepted', 'registration_submitted', 'registration_verified', 'bgv_started', 'bgv_completed', 'policy_signed', 'join_forms_submitted', 'appointment_sent', 'appointment_rejected'], true)
    );
@endphp

<div class="card mb-3 shadow-sm border-primary">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center hr-collapse-toggle"
         data-toggle="collapse" data-target="#collapseHrActions" aria-expanded="{{ $expandHrActions ? 'true' : 'false' }}">
        <strong><i class="dw dw-settings2 text-primary"></i> Onboarding Actions</strong>
        <small class="text-muted collapse-hint">{{ $expandHrActions ? 'Hide' : 'Show' }}</small>
    </div>
    <div id="collapseHrActions" class="collapse {{ $expandHrActions ? 'show' : '' }}">
    <div class="card-body py-3">
        @if (!$canSendOffer && !$offerAlreadySent)
            <div class="alert alert-warning py-2 small mb-2 mb-md-3">
                <strong>Offer letter not available yet.</strong>
                <ul class="mb-0 pl-3 mt-1">
                    @if (!$profileComplete)
                        <li>Candidate must submit <strong>basic information</strong> in the portal.</li>
                    @endif
                    @if (!$documentsSubmitted)
                        <li>Candidate must <strong>submit documents</strong> in the portal.</li>
                    @endif
                    @if ($employee->emp_document_status !== 'completed')
                        <li>HR must <strong>approve all documents</strong> below and save changes.</li>
                    @endif
                </ul>
            </div>
        @endif

        @if (OnboardingStepGate::isHrMutationsBlocked($employee) && !OnboardingArchive::isFinalized($employee))
            <div class="alert alert-success py-2 small mb-2">
                All approvals complete. Use <strong>Finalize &amp; Save to Folder</strong> on this page to finish onboarding.
            </div>
        @endif

        @php
            $showDocumentDeadlineForm = in_array($step, ['start', 'profile_completed', 'documents_rejected'], true)
                || OnboardingDocumentDeadline::isPortalBlocked($employee);
        @endphp
        @if ($showDocumentDeadlineForm)
            <div class="border rounded p-2 p-md-3 mb-3 {{ OnboardingDocumentDeadline::isPortalBlocked($employee) ? 'border-danger bg-light' : '' }}">
                <strong class="small d-block mb-2">Document submission deadline</strong>
                @if ($employee->emp_document_due_date)
                    <p class="small mb-2 mb-md-0 d-inline-block mr-md-3">
                        Current: <strong>{{ $employee->emp_document_due_date->format('d M Y') }}</strong>
                        @if (OnboardingDocumentDeadline::isPortalBlocked($employee))
                            <span class="badge badge-danger ml-1">Portal blocked for candidate</span>
                        @elseif (OnboardingDocumentDeadline::isExpired($employee))
                            <span class="badge badge-warning ml-1">Expired</span>
                        @endif
                    </p>
                @endif
                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline-flex flex-wrap align-items-end gap-2 mt-2 mt-md-0">
                    @csrf
                    <input type="hidden" name="step" value="update_document_deadline">
                    <div>
                        <label class="small mb-1 d-block" for="emp_document_due_date_hr">New deadline</label>
                        <input type="date" name="emp_document_due_date" id="emp_document_due_date_hr" class="form-control form-control-sm"
                               required min="{{ now()->format('Y-m-d') }}"
                               value="{{ old('emp_document_due_date', optional($employee->emp_document_due_date)->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d')) }}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Save deadline</button>
                </form>
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-center gap-2 mb-2 hr-onboarding-actions-toolbar">
            @if ($canSubmitOfferSr)
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#offerLetterModal">
                    <i class="dw dw-file"></i>&nbsp;

                    @if ($step === 'offer_sr_rejected')
                        Revise &amp; Resubmit Offer → SR-HR
                    @elseif ($step === 'offer_rejected')
                        Revise Offer After Candidate Decline → SR-HR
                    @else
                        Prepare Offer → SR-HR Approval
                    @endif
                </button>
            @endif
            @if ($hasOfferLetter)
                <!-- <a href="{{ route('EmployeeJoiner.documents.viewOffer', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-info">View Offer</a>
                <a href="{{ route('offer.preview.pdf', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print Offer</a> -->
            @endif
            @if ($step === 'offer_accepted')
                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline-flex align-items-center ">@csrf
                    <button type="submit" name="step" value="send_registration" class="btn btn-sm btn-outline-primary">Request Resignation Acceptance Letter</button>
                </form>
            @endif
            @if ($regDoc && in_array($step, ['registration_submitted', 'registration_sent'], true))
                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline-flex align-items-center ">@csrf
                    <button type="submit" name="step" value="verify_registration" class="btn btn-sm btn-success">Approve Resignation Letter (Documents)</button>
                </form>
            @elseif ($step === 'registration_submitted' && !$regDoc)
                <span class="badge badge-warning hr-action-badge">Awaiting resignation letter upload</span>
            @endif
            @if (OnboardingDocumentReedit::isReadyForBgv($employee) && $employee->emp_document_status === 'completed')
                <div class="alert alert-success py-2 small mb-2 w-100">
                    Re-uploaded documents approved. <strong>Start OnGrid BGV</strong> below.
                </div>
            @endif
            @if ($canStartBgv)
                <button type="button" class="btn btn-sm btn-info" onclick="openOfferingModal()">
                    {{ $step === 'bgv_started' || OnboardingDocumentReedit::isReadyForBgv($employee) ? 'Start / Retry OnGrid BGV' : 'Start OnGrid BGV' }}
                </button>
                @if ($step === 'registration_verified')
                    <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline-flex align-items-center">@csrf
                        <button type="submit" name="step" value="bgv_started" class="btn btn-sm btn-secondary">Mark BGV Started</button>
                    </form>
                @endif
            @endif
            @if ($step === 'bgv_started')
                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline-flex align-items-center">@csrf
                    <button type="submit" name="step" value="bgv_completed" class="btn btn-sm btn-success">BGV Done</button>
                </form>
            @endif
            @if (OnboardingEarlyJoin::hrCanStartJoin($employee))
                <div class="d-inline-flex align-items-center flex-wrap gap-2 hr-onboarding-join-row">
                    @if ($step !== 'bgv_completed' && OnboardingEarlyJoin::isAllowed($employee))
                        <span class="badge badge-warning hr-action-badge">Early join allowed</span>
                    @endif
                    <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}"
                          id="hrStartJoinForm"
                          class="d-inline-flex align-items-center m-0 flex-wrap gap-2">@csrf
                        <input type="date"
                               name="emp_joining_date"
                               id="hrStartJoinDate"
                               class="form-control form-control-sm hr-action-date"
                               required
                               min="{{ $joinDateMin }}"
                               max="{{ $joinDateMax }}"
                               value="{{ $defaultJoinDate }}">
                        <button type="submit"
                                name="step"
                                value="start_join"
                                id="hrStartJoinBtn"
                                class="btn btn-sm btn-warning"
                                @if (!$joinDateAllowed) disabled @endif>
                            Start Join Process
                        </button>
                    </form>
                </div>
                <!-- <p class="small text-muted mb-0 w-100" id="hrStartJoinHint">
                    Valid join dates: <strong>{{ OnboardingEarlyJoin::joinDateWindowStart()->format('d-m-Y') }}</strong>
                    to <strong>{{ OnboardingEarlyJoin::joinDateWindowEnd()->format('d-m-Y') }}</strong>
                    (within {{ OnboardingEarlyJoin::joinProcessPastDays() }} days before today or
                    {{ OnboardingEarlyJoin::joinProcessFutureDays() }} days after).
                    @if ($scheduledJoin)
                        Candidate date: <strong>{{ $scheduledJoin->format('d-m-Y') }}</strong>.
                    @endif
                </p> -->
                <p class="small text-danger mb-0 w-100 {{ $joinDateAllowed ? 'd-none' : '' }}" id="hrStartJoinError">
                    Selected join date is outside the allowed window. Choose a valid date to start the join process.
                </p>
            @endif
            @if ($canReviseAppointment)
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#appointmentLetterModal">
                    @if ($step === 'appointment_rejected')
                        Re-send Appointment After Candidate Decline
                    @elseif ($joinToday)
                        Send Appointment to Candidate
                    @else
                        Manual Join Today — Send Appointment
                    @endif
                </button>
            @endif
            @if ($canSubmitApptSr)
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#appointmentSrHrModal">
                    <i class="dw dw-check"></i> Submit Signed Appointment → SR-HR
                </button>
            @endif
            @if ($hasApptLetter)
                <a href="{{ route('EmployeeJoiner.documents.viewAppointment', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-info">View Appointment</a>
                <a href="{{ route('appointment.preview.pdf', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print Appointment</a>
            @endif
            @if ($step === 'join_forms_submitted' && !$employee->emp_policy_accepted_at)
                <span class="badge badge-info hr-action-badge">Awaiting candidate policy acceptance</span>
            @endif
        </div>

        <style>
            .hr-onboarding-actions-toolbar .btn.btn-sm,
            .hr-onboarding-actions-toolbar .hr-action-badge,
            .hr-onboarding-actions-toolbar .hr-action-date {
                height: 31px;
                line-height: 1.5;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .hr-onboarding-actions-toolbar .btn.btn-sm {
                padding: 0.25rem 0.75rem;
                white-space: nowrap;
            }
            .hr-onboarding-actions-toolbar .hr-action-badge {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
            }
            .hr-onboarding-actions-toolbar .hr-action-date {
                width: auto;
                min-width: 10.5rem;
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .hr-onboarding-actions-toolbar form {
                margin: 0;
            }
        </style>

        @include('pages.new-join-employee.partials.onboarding-corrections', [
            'employee' => $employee,
            'step' => $step,
            'profileComplete' => $profileComplete,
            'appointmentAccepted' => $appointmentAccepted,
            'canSubmitApptSr' => $canSubmitApptSr,
        ])

        @if ($step === 'offer_pending_sr_hr')
            <div class="alert alert-warning py-2 small mb-1">
                <strong>Awaiting SR-HR approval</strong> for offer letter.
                @if (!empty($offerSr['notify_emails']))
                    Notified: {{ implode(', ', $offerSr['notify_emails']) }}
                @endif
            </div>
        @elseif ($step === 'offer_sr_rejected')
            <div class="alert alert-danger py-2 small mb-1">
                <strong>SR-HR rejected offer.</strong>
                @if (!empty($offerSr['reject_reason']))
                    <br><strong>Reason:</strong> {{ $offerSr['reject_reason'] }}
                @endif
                @if ($canSubmitOfferSr)
                    <br class="mb-0">Update offer details and use <strong>Revise &amp; Resubmit Offer → SR-HR</strong>.
                @endif
            </div>
        @endif
        @if ($step === 'appointment_pending_sr_hr')
            <div class="alert alert-warning py-2 small mb-1">
                Candidate <strong>accepted</strong> the appointment. Awaiting <strong>SR-HR approval</strong>.
                @if (!empty($apptSr['notify_emails'])) Notified: {{ implode(', ', $apptSr['notify_emails']) }} @endif
            </div>
        @elseif ($step === 'appointment_sr_rejected')
            <div class="alert alert-danger py-2 small mb-1">SR-HR rejected appointment (candidate already signed). {{ $apptSr['reject_reason'] ?? '' }} — use <strong>Submit Signed Appointment → SR-HR</strong> to resubmit.</div>
        @elseif ($apptSrApproved && $employee->emp_appointment_letter_status === 'accept')
            <div class="alert alert-success py-2 small mb-1">SR-HR <strong>approved</strong> signed appointment. PDF on file — ready to finalize onboarding.</div>
        @elseif ($step === 'appointment_accepted' && !$apptSrApproved)
            <div class="alert alert-info py-2 small mb-1">Candidate <strong>signed</strong> appointment. Use <strong>Submit Signed Appointment → SR-HR</strong>.</div>
        @endif

        @if ($employee->emp_offer_letter_status === 'accept')
            <div class="alert alert-success py-2 small mb-1">
                Candidate <strong>accepted</strong> the offer.
                @if ($employee->emp_signature)
                    @php $offerSigPreview = \App\Support\CandidateSignature::offer($employee, true); @endphp
                    @if ($offerSigPreview)
                        <span class="d-inline-block ms-2 align-middle">
                            <img src="{{ $offerSigPreview }}" alt="Candidate signature" style="max-height:36px;border:1px solid #ddd;padding:2px;background:#fff;">
                        </span>
                    @else
                        Signature on file.
                    @endif
                @endif
            </div>
        @elseif ($employee->emp_offer_letter_status === 'reject')
            <div class="alert alert-danger py-2 small mb-1">
                Candidate <strong>rejected</strong> the offer.
                @if ($employee->emp_offer_reject_reason)
                    <br><strong>Candidate reason:</strong> {{ $employee->emp_offer_reject_reason }}
                @endif
                @if ($canSubmitOfferSr)
                    <br class="mb-0">Revise the offer and send to SR-HR again using <strong>Revise Offer After Candidate Decline → SR-HR</strong>.
                @endif
            </div>
        @endif

        @if ($employee->emp_appointment_letter_status === 'accept' && $step === 'appointment_sent')
            <div class="alert alert-success py-2 small mb-1">
                Appointment <strong>accepted</strong> by candidate — submit to SR-HR next.
                @php $apptSigPreview = \App\Support\CandidateSignature::appointment($employee, true); @endphp
                @if ($apptSigPreview)
                    <span class="d-inline-block ms-2 align-middle">
                        <img src="{{ $apptSigPreview }}" alt="Candidate signature" style="max-height:36px;border:1px solid #ddd;padding:2px;background:#fff;">
                    </span>
                @endif
            </div>
        @elseif ($employee->emp_appointment_letter_status === 'accept')
            <div class="alert alert-success py-2 small mb-1">
                Appointment <strong>accepted</strong> by candidate.@if ($apptSrApproved) SR-HR approved.@endif
                @php $apptSigPreview = \App\Support\CandidateSignature::appointment($employee, true); @endphp
                @if ($apptSigPreview)
                    <span class="d-inline-block ms-2 align-middle">
                        <img src="{{ $apptSigPreview }}" alt="Candidate signature" style="max-height:36px;border:1px solid #ddd;padding:2px;background:#fff;">
                    </span>
                @endif
            </div>
        @elseif ($employee->emp_appointment_letter_status === 'reject')
            <div class="alert alert-danger py-2 small mb-1">
                Appointment <strong>rejected</strong> by candidate.
                @if ($employee->emp_appointment_reject_reason)
                    <br><strong>Reason:</strong> {{ $employee->emp_appointment_reject_reason }}
                @endif
                @if ($canReviseAppointment)
                    <br class="mb-0">Revise the appointment letter and send again using <strong>Re-send Appointment After Candidate Decline</strong>.
                @endif
            </div>
        @endif

        @if (!empty($offer))
            <div class="small text-muted border-top pt-2 mt-2">
                <strong>Last offer details:</strong>
                {{ $offer['candidate_name'] ?? '' }} | {{ $offer['role'] ?? '' }} | {{ $offer['designation'] ?? '' }} |
                CTC: {{ $offer['ctc'] ?? '' }} | {{ $offer['location'] ?? '' }}
                @if ($employee->emp_offer_sent_at)
                    | Sent: {{ $employee->emp_offer_sent_at->format('d M Y') }}
                @endif
                @if ($employee->emp_offer_due_date)
                    | Due: {{ $employee->emp_offer_due_date->format('d M Y') }}
                    @if (OnboardingLetterDeadline::isOfferExpired($employee))
                        <span class="badge badge-danger ml-1">Expired</span>
                    @endif
                @endif
            </div>
        @endif

        @if (in_array($step, ['offer_sent', 'registration_sent', 'appointment_sent', 'appointment_rejected'], true))
            <div class="border-top pt-3 mt-3">
                <strong class="small d-block mb-2">Extend letter deadline</strong>
                <p class="small text-muted mb-2">If the candidate missed the deadline, set a new date so they can continue.</p>
                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-flex flex-wrap align-items-end gap-2">
                    @csrf
                    <input type="hidden" name="step" value="update_letter_deadlines">
                    @if ($step === 'offer_sent')
                        <div>
                            <label class="small mb-1 d-block" for="emp_offer_due_date">Offer acceptance deadline</label>
                            <input type="date" name="emp_offer_due_date" id="emp_offer_due_date" class="form-control form-control-sm"
                                   required min="{{ now()->format('Y-m-d') }}"
                                   value="{{ old('emp_offer_due_date', optional($employee->emp_offer_due_date)->format('Y-m-d') ?? now()->addDays((int) config('onboarding.offer_accept_days', 7))->format('Y-m-d')) }}">
                        </div>
                    @elseif ($step === 'registration_sent')
                        <div>
                            <label class="small mb-1 d-block" for="emp_registration_due_date">Resignation letter upload deadline</label>
                            <input type="date" name="emp_registration_due_date" id="emp_registration_due_date" class="form-control form-control-sm"
                                   required min="{{ now()->format('Y-m-d') }}"
                                   value="{{ old('emp_registration_due_date', optional($employee->emp_registration_due_date)->format('Y-m-d') ?? now()->addDays((int) config('onboarding.registration_upload_days', 7))->format('Y-m-d')) }}">
                        </div>
                    @elseif (in_array($step, ['appointment_sent', 'appointment_rejected'], true))
                        <div>
                            <label class="small mb-1 d-block" for="emp_appointment_due_date">Appointment acceptance deadline</label>
                            <input type="date" name="emp_appointment_due_date" id="emp_appointment_due_date" class="form-control form-control-sm"
                                   required min="{{ now()->format('Y-m-d') }}"
                                   value="{{ old('emp_appointment_due_date', optional($employee->emp_appointment_due_date)->format('Y-m-d') ?? now()->addDays((int) config('onboarding.appointment_accept_days', 2))->format('Y-m-d')) }}">
                            @if (OnboardingLetterDeadline::isAppointmentExpired($employee))
                                <span class="badge badge-danger mt-1">Expired for candidate</span>
                            @endif
                        </div>
                    @endif
                    <button type="submit" class="btn btn-sm btn-outline-primary">Save deadline</button>
                </form>
            </div>
        @endif
    </div>
    </div>
</div>

{{-- Offer letter modal --}}
<div class="modal fade" id="offerLetterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if ($step === 'offer_sr_rejected')
                            Revise Offer After SR-HR Rejection
                        @elseif ($step === 'offer_rejected')
                            Revise Offer After Candidate Decline
                        @else
                            Offer Letter Details (A4)
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @if (in_array($step, ['offer_sr_rejected', 'offer_rejected'], true))
                        <div class="alert alert-warning py-2 small">
                            Update offer details below, then send to <strong>SR-HR</strong> for approval again.
                            @if ($step === 'offer_rejected' && $employee->emp_offer_reject_reason)
                                <br><strong>Candidate reason:</strong> {{ $employee->emp_offer_reject_reason }}
                            @elseif ($step === 'offer_sr_rejected' && !empty($offerSr['reject_reason']))
                                <br><strong>SR-HR reason:</strong> {{ $offerSr['reject_reason'] }}
                            @endif
                        </div>
                    @else
                        <p class="small text-muted">Save offer, then send to <strong>SR-HR team</strong> for approval. Candidate is emailed only after SR-HR approves.</p>
                    @endif
                    @include('pages.new-join-employee.partials.sr-hr-email-picker', ['pickerId' => 'offerSrHrPicker'])
                    @php
                        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
                    @endphp
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Candidate Name *</label>
                            <input type="text" name="offer_candidate_name" class="form-control" required
                                value="{{ old('offer_candidate_name', $offer['candidate_name'] ?? $basic['name'] ?? $employee->emp_name) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Department *</label>
                            <input type="text" name="offer_role" class="form-control" required
                                value="{{ old('offer_role', $offer['role'] ?? $employee->emp_department) }}">
                        </div>
                       
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Level Wise Grading</label>
                            <input type="text" class="form-control" readonly
                                value="{{ $offer['grade'] ?? $employee->emp_grade ?? '—' }}@if(($offer['category'] ?? $employee->emp_category) !== '') — {{ $offer['category'] ?? $employee->emp_category }}@endif">
                            <input type="hidden" name="offer_grade" value="{{ old('offer_grade', $offer['grade'] ?? $employee->emp_grade) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Designation *</label>
                            <input type="text" name="offer_designation" class="form-control" required
                                value="{{ old('offer_designation', $offer['designation'] ?? $employee->emp_role) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Total Annual CTC (₹) *</label>
                            <input type="number" name="offer_ctc" id="offerCtcInput" class="form-control offer-comp-input" required min="1"
                                value="{{ old('offer_ctc', $offer['ctc'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Retention Bonus (₹)</label>
                            <input type="number" name="offer_retention_bonus" id="offerRetentionInput" class="form-control offer-comp-input" min="0" step="0.01"
                                value="{{ old('offer_retention_bonus', $offer['retention_bonus'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Variable Component (₹)</label>
                            <input type="number" name="offer_variable_component" id="offerVariableInput" class="form-control offer-comp-input" min="0" step="0.01"
                                value="{{ old('offer_variable_component', $offer['variable_component'] ?? '') }}">
                        </div>
                        <div class="col-12">
                            <p class="small text-muted mb-0" id="offerFixedPreview">
                                @php
                                    $offerPreview = \App\Support\OfferLetterCompensation::breakdown([
                                        'ctc' => old('offer_ctc', $offer['ctc'] ?? 0),
                                        'retention_bonus' => old('offer_retention_bonus', $offer['retention_bonus'] ?? null),
                                        'variable_component' => old('offer_variable_component', $offer['variable_component'] ?? null),
                                    ]);
                                @endphp
                                <strong>Fixed (auto):</strong> {{ \App\Support\OfferLetterCompensation::rupee($offerPreview['fixed']) }}
                                — Total CTC minus retention and variable. Retention / variable of 0 are omitted from the offer letter.
                            </p>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Location *</label>
                            <input type="text" name="offer_location" class="form-control" required
                                value="{{ old('offer_location', $offer['location'] ?? $employee->emp_location) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Joining Date</label>
                            <input type="date" name="offer_joining_date" class="form-control"
                                value="{{ old('offer_joining_date', $offer['joining_date'] ?? optional($employee->emp_date)->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <a href="{{ route('offer.preview.pdf', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-info" id="previewOfferLink">Preview letter (save first to see latest)</a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="step" value="save_offer_draft" class="btn btn-outline-primary">Save & Preview</button>
                    <button type="submit" name="step" value="submit_offer_sr_approval" class="btn btn-primary">Save & Send to SR-HR for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Offer letter modal --}}
<div class="modal fade" id="appointmentLetterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if ($step === 'appointment_rejected')
                            Revise Appointment After Candidate Decline
                        @else
                            Appointment Letter
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @if ($step === 'appointment_rejected')
                        <div class="alert alert-warning py-2 small">
                            Update appointment details below, then send to the candidate again.
                            @if ($employee->emp_appointment_reject_reason)
                                <br><strong>Candidate reason:</strong> {{ $employee->emp_appointment_reject_reason }}
                            @endif
                        </div>
                    @elseif (!$joinToday)
                        <div class="alert alert-warning py-2 small">
                            Join date is <strong>{{ $scheduledJoin ? $scheduledJoin->format('d-m-Y') : 'not set' }}</strong>;
                            today is <strong>{{ now()->format('d-m-Y') }}</strong>.
                            Confirm only if the candidate has joined today.
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="confirm_joined_today" id="confirmJoinedToday" value="1" {{ old('confirm_joined_today') ? 'checked' : '' }} required>
                            <label class="form-check-label" for="confirmJoinedToday">I confirm the candidate joined today</label>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Name *</label>
                            <input type="text" name="appt_candidate_name" class="form-control" required value="{{ old('appt_candidate_name', $appt['candidate_name'] ?? $offer['candidate_name'] ?? $employee->emp_name) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Role *</label>
                            <input type="text" name="appt_role" class="form-control" required value="{{ old('appt_role', $appt['role'] ?? $offer['role'] ?? $employee->emp_role) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Designation *</label>
                            <input type="text" name="appt_designation" class="form-control" required value="{{ old('appt_designation', $appt['designation'] ?? $offer['designation'] ?? $employee->emp_department) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Location *</label>
                            <input type="text" name="appt_location" class="form-control" required value="{{ old('appt_location', $appt['location'] ?? $offer['location'] ?? $employee->emp_location) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Joining Date *</label>
                            <input type="date" name="appt_joining_date" class="form-control" required value="{{ old('appt_joining_date', $appt['joining_date'] ?? optional($employee->emp_joining_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Annual CTC (₹) *</label>
                            <input type="number" name="appt_ctc_annual" class="form-control" required min="1" value="{{ old('appt_ctc_annual', $appt['ctc_annual'] ?? $offer['ctc'] ?? '') }}">
                        </div>
                        <div class="col-12"><hr><strong class="small">CTC breakdown (monthly)</strong></div>
                        <div class="col-md-3 form-group">
                            <label>Basic</label>
                            <input type="number" name="appt_ctc_basic" class="form-control" min="0" value="{{ old('appt_ctc_basic', $appt['ctc_breakdown']['basic'] ?? '') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>HRA</label>
                            <input type="number" name="appt_ctc_hra" class="form-control" min="0" value="{{ old('appt_ctc_hra', $appt['ctc_breakdown']['hra'] ?? '') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Special</label>
                            <input type="number" name="appt_ctc_special" class="form-control" min="0" value="{{ old('appt_ctc_special', $appt['ctc_breakdown']['special'] ?? '') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>PF</label>
                            <input type="number" name="appt_ctc_pf" class="form-control" min="0" value="{{ old('appt_ctc_pf', $appt['ctc_breakdown']['pf'] ?? '') }}">
                        </div>
                    </div>
                    <a href="{{ route('appointment.preview.pdf', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-info">Preview PDF</a>
                    <p class="small text-muted mt-2 mb-0">Candidate signs first in the portal. After acceptance, use <strong>Submit Signed Appointment → SR-HR</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="step" value="save_appointment_draft" class="btn btn-outline-primary">Save Draft</button>
                    <button type="submit" name="step" value="send_appointment_to_candidate" class="btn btn-primary">
                        {{ $step === 'appointment_rejected' ? 'Save & Re-send to Candidate' : 'Save & Send to Candidate' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SR-HR approval after candidate signed appointment --}}
<div class="modal fade" id="appointmentSrHrModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">SR-HR — Signed appointment approval</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Candidate has already signed. SR-HR reviews the signed appointment; final PDF is saved after approval.</p>
                    @include('pages.new-join-employee.partials.sr-hr-email-picker', ['pickerId' => 'apptSrHrPicker'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="step" value="submit_appointment_sr_approval" class="btn btn-primary">Send to SR-HR for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('offer_candidate_name') || $errors->has('offer_ctc'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $ !== 'undefined' && $('#offerLetterModal').length) {
                $('#offerLetterModal').modal('show');
            }
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctc = document.getElementById('offerCtcInput');
        var retention = document.getElementById('offerRetentionInput');
        var variable = document.getElementById('offerVariableInput');
        var preview = document.getElementById('offerFixedPreview');
        if (!preview || !ctc) return;

        function formatInr(n) {
            var x = Math.max(0, Math.round(n || 0)).toString();
            if (x.length <= 3) return '₹' + x;
            var last = x.slice(-3);
            var rest = x.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
            return '₹' + rest + ',' + last;
        }

        function syncOfferFixedPreview() {
            var total = parseFloat(ctc.value || '0') || 0;
            var ret = parseFloat(retention && retention.value ? retention.value : '0') || 0;
            var varPay = parseFloat(variable && variable.value ? variable.value : '0') || 0;
            var fixed = Math.max(0, total - ret - varPay);
            preview.innerHTML = '<strong>Fixed (auto):</strong> ' + formatInr(fixed)
                + ' — Total CTC minus retention and variable. Retention / variable of 0 are omitted from the offer letter.';
        }

        [ctc, retention, variable].forEach(function (el) {
            if (el) el.addEventListener('input', syncOfferFixedPreview);
        });
        syncOfferFixedPreview();
    });
</script>
@if ($errors->has('appt_candidate_name') || $errors->has('appt_ctc_annual'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $ !== 'undefined' && $('#appointmentLetterModal').length) {
                $('#appointmentLetterModal').modal('show');
            }
        });
    </script>
@endif
@if (OnboardingEarlyJoin::hrCanStartJoin($employee))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('hrStartJoinDate');
            var btn = document.getElementById('hrStartJoinBtn');
            var err = document.getElementById('hrStartJoinError');
            if (!input || !btn) return;

            var min = input.getAttribute('min') || '';
            var max = input.getAttribute('max') || '';

            function isAllowed(value) {
                if (!value) return false;
                return (!min || value >= min) && (!max || value <= max);
            }

            function syncJoinButton() {
                var ok = isAllowed(input.value);
                btn.disabled = !ok;
                if (err) {
                    err.classList.toggle('d-none', ok);
                }
            }

            input.addEventListener('change', syncJoinButton);
            input.addEventListener('input', syncJoinButton);
            syncJoinButton();
        });
    </script>
@endif
