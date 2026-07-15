@php
    use App\Support\OnboardingLetterResend;
    use App\Support\OnboardingMail;
    use App\Support\SrHrLetterApproval;

    $offerSrState = SrHrLetterApproval::state($employee, SrHrLetterApproval::TYPE_OFFER);
    $apptSrState = SrHrLetterApproval::state($employee, SrHrLetterApproval::TYPE_APPOINTMENT);

    $links = [[
        'label' => 'Candidate portal',
        'note' => 'Sent to candidate when HR creates the record',
        'url' => OnboardingMail::portalUrl($employee),
        'send_step' => 'resend_portal_upload_link',
        'send_label' => 'Email candidate',
        'can_send' => OnboardingLetterResend::canResendPortalLink($employee),
    ]];

    if (($offerSrState['status'] ?? '') === 'pending' && !empty($offerSrState['token'])) {
        $links[] = [
            'label' => 'SR-HR — Offer approval',
            'note' => 'Sent when HR submits offer for SR-HR approval'
                . (!empty($offerSrState['notify_emails']) ? ' · ' . implode(', ', $offerSrState['notify_emails']) : ''),
            'url' => SrHrLetterApproval::approvalUrl($offerSrState['token']),
            'send_step' => 'resend_offer_sr_approval',
            'send_label' => 'Email SR-HR',
            'can_send' => OnboardingLetterResend::canResendOfferSrApproval($employee),
        ];
    }

    if (($apptSrState['status'] ?? '') === 'pending' && !empty($apptSrState['token'])) {
        $links[] = [
            'label' => 'SR-HR — Appointment approval',
            'note' => 'Sent when HR submits signed appointment for SR-HR approval'
                . (!empty($apptSrState['notify_emails']) ? ' · ' . implode(', ', $apptSrState['notify_emails']) : ''),
            'url' => SrHrLetterApproval::approvalUrl($apptSrState['token']),
            'send_step' => 'resend_appointment_sr_approval',
            'send_label' => 'Email SR-HR',
            'can_send' => OnboardingLetterResend::canResendAppointmentSrApproval($employee),
        ];
    }
@endphp

<div class="card mb-3 shadow-sm border-info">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center hr-collapse-toggle"
         data-toggle="collapse" data-target="#collapseHrLinks" aria-expanded="true">
        <strong><i class="dw dw-link text-info"></i> Onboarding links</strong>
        <small class="text-muted collapse-hint">Hide</small>
    </div>
    <div id="collapseHrLinks" class="collapse hide">
        <div class="card-body py-3">
            @foreach ($links as $link)
                <div class="onboarding-link-row {{ !$loop->last ? 'mb-3' : '' }}">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-1 mb-1">
                        <div class="small">
                            <strong>{{ $link['label'] }}</strong>
                            @if (!empty($link['note']))
                                <span class="text-muted d-block">{{ $link['note'] }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2 onboarding-copy-link mr-2"
                                    data-url="{{ $link['url'] }}" title="Copy link">
                                <i class="dw dw-copy"></i> Copy
                            </button>
            
                            @if (!empty($link['can_send']) && !empty($link['send_step']))
                                <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" class="d-inline">@csrf
                                    <button type="submit" name="step" value="{{ $link['send_step'] }}" class="btn btn-xs btn-outline-success btn-sm py-0 px-2">
                                        <i class="dw dw-mail"></i> {{ $link['send_label'] ?? 'Email' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <input type="text" class="form-control form-control-sm onboarding-link-input font-monospace small"
                           value="{{ $link['url'] }}" readonly onclick="this.select()">
                </div>
            @endforeach

            @if (count($links) === 1)
                <p class="small text-muted mb-0 mt-2">
                    SR-HR offer / appointment approval links appear here when pending.
                </p>
            @endif
        </div>
    </div>
</div>
