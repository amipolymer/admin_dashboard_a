@php
    use App\Support\OnboardingLetterData;
    use App\Support\SrHrLetterApproval;

    $offer = $offer ?? OnboardingLetterData::offer($employee);
    $name = $offer['candidate_name'] ?? $employee->emp_name;
    $salutation = trim((string) ($offer['salutation'] ?? ''));
    $salutationPrefix = $salutation !== '' ? $salutation . ' ' : '';
    $address = $offer['candidate_address'] ?? '—';
    $designation = $offer['designation'] ?? $employee->emp_department;
    $department = $offer['department'] ?? ($offer['role'] ?? $employee->emp_role);
    $grade = trim((string) ($offer['grade'] ?? ''));
    $location = $offer['location'] ?? $employee->emp_location;
    $joining = OnboardingLetterData::ordinalDateHtml($offer['joining_date'] ?? null);
    $offerDate = OnboardingLetterData::ordinalDateHtml($offer['offer_date'] ?? null);
    $offerSrApproval = SrHrLetterApproval::state($employee, SrHrLetterApproval::TYPE_OFFER);
    $isAccepted = ($testSimulateAccepted ?? false) || $employee->emp_offer_letter_status === 'accept';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Offer Letter — {{ $name }}</title>
    @include('pdf.partials.ami-letterhead-base')
    @if (($letterRenderMode ?? 'pdf') === 'html')
        @include('pdf.partials.offer-letter-html-styles')
    @else
        @include('pdf.partials.offer-letter-pdf-styles')
    @endif
</head>
<body class="@if(!empty($letterViewOnly)) letter-view-only @endif letter-render-{{ $letterRenderMode ?? 'pdf' }}">
    <div class="page-offer">
        @include('pdf.partials.ami-letterhead-header', ['letterPdfMode' => 'offer-single'])

        <div class="body-content-offer">
            <p class="text-right strong">Date: {!! $offerDate !!}</p>

            <p class="strong text-left spac-1 mb-2">To,</p>
            <p class="strong text-left spac-1">{{ $salutationPrefix }}{{ $name }},</p>
            <p class="text-left spac-1 mb-15" style="width: 80%;">{{ $address }}</p>

            <p class="text-left spac-1 mb-15 strong">Subject: Offer Letter for Employment</p>
            <p class="text-left spac-1 mb-2">Dear {{ $salutationPrefix }}{{ $name }},</p>
            <p class="text-left spac-1 mb-15 strong">Congratulations!</p>

            <p class="mb-15">
                With reference to your application and the subsequent interviews and discussions held with
                us, we are pleased to offer you employment for the position of
                <span class="strong">&ldquo;{{ $designation }} &ndash; {{ $department }}&rdquo;</span>@if ($grade !== '')
                    under <span class="strong">&ldquo;{{ str_contains($grade, 'Grade') ? $grade : $grade . ' Grade' }}&rdquo;</span>
                @endif.
            </p>

            <p class="mb-15">
                You will be primarily based at <span class="strong">{{ $location }}.</span> and are
                required to report for duty on or before <span class="strong">{!! $joining !!}</span>.
            </p>

            <p class="mb-15">
                This offer of employment and your subsequent appointment are subject to the satisfactory
                completion and clearance of reference checks, medical fitness, and background verification as conducted
                by the Company or its authorized agency, in accordance with Company policy.
            </p>

            <p class="mb-15">
                @include('pdf.partials.offer-letter-compensation', ['offer' => $offer])
                The detailed salary breakup, structured in accordance with Company policy, applicable income tax rules,
                and statutory regulations, will be shared with you separately. Any performance-linked variable pay, if
                applicable, shall be governed by Company policy and defined performance parameters. A formal
                <span class="strong">Letter of Appointment</span> containing detailed terms and conditions of employment
                shall be issued to you upon joining.
            </p>

            <p class="mb-15">
                For the purpose of Company records and to ensure a smooth on boarding process, you are
                required to submit the following documents at the time of joining:
            </p>

            <div class="doc-list-wrap text-left">
                <ol class="doc-list">
                    <li>Three recent passport-size colour photographs</li>
                    <li>Copies of all educational certificates (originals to be produced for verification)</li>
                    <li>Copies of experience and/or relieving letters from all previous employers</li>
                    <li>Last three months&rsquo; salary slips from your previous employer</li>
                    <li>Aadhar &amp; PAN Card</li>
                    <li>EPF UAN details</li>
                    <li>Current address proof</li>
                    <li>Bank account details</li>
                    <li>Acknowledged copy of resignation letter from previous employer</li>
                    <li>Full and Final Settlement statement from the last employer, if available</li>
                    <li>Copy of any non-compete or confidentiality agreement executed with the previous employer, if applicable</li>
                    <li>Medical test reports along with medical fitness certificate, if required</li>
                </ol>
            </div>

            <p class="closing-para">
            We are excited to welcome you to our team and look forward to your contributions. For any clarification, please contact the HR Department. Please acknowledge your acceptance of this offer within twenty-four (24) hours through the available acceptance options on the platform.
            </p>

            <table class="signatures-row" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" class="sign-col-left">
                        @include('pdf.partials.hr-authorized-signatory', ['approval' => $offerSrApproval, 'compact' => true])
                    </td>
                    <td width="50%" class="sign-col-right">
                        @if ($isAccepted || ($letterRenderMode ?? 'pdf') === 'html')
                            @include('pdf.partials.candidate-accept-signatory', [
                                'employee' => $employee,
                                'candidateName' => $name,
                                'signatureSrc' => $isAccepted && !empty($employee->emp_signature)
                                    ? $employee->emp_signature
                                    : ($testPlaceholderSignature ?? null),
                                'signatureAsUrl' => $signatureAsUrl ?? false,
                                'compact' => true,
                            ])
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @include('pdf.partials.ami-letterhead-footer', [
            'letterPdfMode' => 'offer-single',
            'letterRenderMode' => $letterRenderMode ?? 'pdf',
        ])
    </div>
    @if (($letterRenderMode ?? 'pdf') === 'html' && empty($letterAllowInspect))
        @include('pdf.partials.letter-view-protection')
    @endif
</body>
</html>
