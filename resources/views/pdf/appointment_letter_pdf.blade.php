@php
    use App\Support\CandidateSignature;
    use App\Support\IndianAmountFormat;
    use App\Support\OnboardingLetterData;
    use App\Support\SrHrLetterApproval;

    $appt = $appointment ?? OnboardingLetterData::appointment($employee);
    $name = $appt['candidate_name'] ?? $employee->emp_name;
    $salutation = trim((string) ($appt['salutation'] ?? ''));
    $salutationPrefix = $salutation !== '' ? $salutation . ' ' : '';
    $department = $appt['role'] ?? $employee->emp_department;
    $designation = $appt['designation'] ?? $employee->emp_role;
    $grade = trim((string) ($appt['grade'] ?? ''));
    $category = trim((string) ($appt['category'] ?? ''));
    $location = $appt['location'] ?? $employee->emp_location;
    $joining = OnboardingLetterData::ordinalDateHtml($appt['joining_date'] ?? null);
    $ctcAnnual = (float) ($appt['ctc_annual'] ?? 0);
    $ctcFormatted = IndianAmountFormat::annualCtc($ctcAnnual);
    $retentionBonus = OnboardingLetterData::positiveAmount($appt['retention_bonus'] ?? null);
    $variableComponent = OnboardingLetterData::positiveAmount($appt['variable_component'] ?? null);
    $breakdown = $appt['ctc_breakdown'] ?? [];
    $letterDate = OnboardingLetterData::ordinalDateHtml($appt['letter_date'] ?? null);
    $apptAccepted = ($testSimulateAccepted ?? false) || $employee->emp_appointment_letter_status === 'accept';
    $apptSignatureRaw = ($employee->emp_other ?? [])['appointment_signature'] ?? $employee->emp_signature ?? null;
    $apptSignature = $apptAccepted
        ? (CandidateSignature::preview($apptSignatureRaw, !empty($signatureAsUrl), $employee->id) ?? $apptSignatureRaw)
        : ($testPlaceholderSignature ?? null);
    $apptSrApproval = SrHrLetterApproval::state($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    $isHtml = ($letterRenderMode ?? 'pdf') === 'html';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Appointment Letter — {{ $name }}</title>
    @include('pdf.partials.ami-letterhead-base')
    @if ($isHtml)
        @include('pdf.partials.appointment-letter-html-styles')
    @else
        @include('pdf.partials.appointment-letter-pdf-styles')
    @endif
</head>
<body class="@if(!empty($letterViewOnly)) letter-view-only @endif letter-render-{{ $letterRenderMode ?? 'pdf' }}">
@if ($isHtml)
    <div class="page-appointment">
        @include('pdf.partials.ami-letterhead-header', ['letterPdfMode' => 'appointment-multi'])
        @include('pdf.partials.appointment-letter-body', [
            'employee' => $employee,
            'name' => $name,
            'salutationPrefix' => $salutationPrefix,
            'designation' => $designation,
            'department' => $department,
            'grade' => $grade,
            'category' => $category,
            'location' => $location,
            'joining' => $joining,
            'letterDate' => $letterDate,
            'ctcFormatted' => $ctcFormatted,
            'ctcAnnual' => $ctcAnnual,
            'retentionBonus' => $retentionBonus,
            'variableComponent' => $variableComponent,
            'breakdown' => $breakdown,
            'apptAccepted' => $apptAccepted,
            'apptSignature' => $apptSignature,
            'apptSrApproval' => $apptSrApproval,
            'testPlaceholderSignature' => $testPlaceholderSignature ?? null,
            'signatureAsUrl' => $signatureAsUrl ?? false,
            'letterRenderMode' => $letterRenderMode ?? 'pdf',
        ])
        @include('pdf.partials.ami-letterhead-footer', [
            'letterPdfMode' => 'appointment-multi',
            'letterRenderMode' => $letterRenderMode ?? 'pdf',
        ])
    </div>
@else
    @include('pdf.partials.ami-letterhead-header', ['letterPdfMode' => 'appointment-multi'])
    <div class="page-appointment">
        @include('pdf.partials.appointment-letter-body', [
            'employee' => $employee,
            'name' => $name,
            'salutationPrefix' => $salutationPrefix,
            'designation' => $designation,
            'department' => $department,
            'grade' => $grade,
            'category' => $category,
            'location' => $location,
            'joining' => $joining,
            'letterDate' => $letterDate,
            'ctcFormatted' => $ctcFormatted,
            'ctcAnnual' => $ctcAnnual,
            'retentionBonus' => $retentionBonus,
            'variableComponent' => $variableComponent,
            'breakdown' => $breakdown,
            'apptAccepted' => $apptAccepted,
            'apptSignature' => $apptSignature,
            'apptSrApproval' => $apptSrApproval,
            'testPlaceholderSignature' => $testPlaceholderSignature ?? null,
            'signatureAsUrl' => $signatureAsUrl ?? false,
            'letterRenderMode' => $letterRenderMode ?? 'pdf',
        ])
    </div>
    @include('pdf.partials.ami-letterhead-footer', [
        'letterPdfMode' => 'appointment-multi',
        'letterRenderMode' => $letterRenderMode ?? 'pdf',
    ])
@endif
    @if ($isHtml && empty($letterAllowInspect))
        @include('pdf.partials.letter-view-protection')
    @endif
</body>
</html>
