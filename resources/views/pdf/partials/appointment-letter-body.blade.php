@php
    use App\Support\IndianAmountFormat;
@endphp
<div class="body-content-appt">
    <p class="text-right strong">Date: {!! $letterDate !!}</p>

    <p class="strong mb-2">To,</p>
    <p class="strong">{{ $salutationPrefix }}{{ $name }},</p>
    <p class="mb-15">{{ $location }}</p>

    <p class="strong mb-15">Subject: Appointment to the position of {{ $designation }}</p>

    <p>Dear {{ $salutationPrefix }}{{ $name }},</p>

    <p>
        Further to your offer acceptance, we are pleased to confirm your appointment as
        <span class="strong">&ldquo;{{ $designation }} &ndash; {{ $department }}&rdquo;</span>@if ($grade !== '')
            under <span class="strong">&ldquo;{{ str_contains($grade, 'Grade') ? $grade : $grade . ' Grade' }}&rdquo;</span>
        @endif with effect from
        <span class="strong">{!! $joining !!}</span> at <span class="strong">{{ $location }}</span>.
    </p>

    <table class="appt-table">
        <tr><th>Employee Name</th><td>{{ $name }}</td></tr>
        <tr><th>Designation</th><td>{{ $designation }}</td></tr>
        <tr><th>Department</th><td>{{ $department }}</td></tr>
        @if ($grade !== '')
            <tr><th>Level Wise Grading</th><td>{{ $grade }}@if ($category !== '') — {{ $category }}@endif</td></tr>
        @endif
        <tr><th>Date of Joining</th><td>{!! $joining !!}</td></tr>
        <tr><th>Work Location</th><td>{{ $location }}</td></tr>
        <tr><th>Annual CTC</th><td>INR {{ $ctcFormatted['amount'] }} ({{ $ctcFormatted['words'] }})</td></tr>
        @if ($retentionBonus)
            <tr><th>Retention Bonus</th><td>INR {{ IndianAmountFormat::annualCtc($retentionBonus)['amount'] }}</td></tr>
        @endif
        @if ($variableComponent)
            <tr><th>Variable Component</th><td>INR {{ IndianAmountFormat::annualCtc($variableComponent)['amount'] }}</td></tr>
        @endif
    </table>

    <p class="strong">CTC Breakdown (Monthly / Annual)</p>
    <table class="appt-table ctc-table">
        <thead>
            <tr><th>Component</th><th>Monthly (₹)</th><th>Annual (₹)</th></tr>
        </thead>
        <tbody>
            @foreach ([
                'Basic' => $breakdown['basic'] ?? 0,
                'HRA' => $breakdown['hra'] ?? 0,
                'Special Allowance' => $breakdown['special'] ?? 0,
                'PF (Employer)' => $breakdown['pf'] ?? 0,
            ] as $label => $monthly)
                @php $m = (float) $monthly; $a = $m * 12; @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $m ? number_format($m) : '—' }}</td>
                    <td>{{ $m ? number_format($a) : '—' }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total (approx.)</strong></td>
                <td><strong>{{ number_format((float)($breakdown['basic'] ?? 0) + (float)($breakdown['hra'] ?? 0) + (float)($breakdown['special'] ?? 0) + (float)($breakdown['pf'] ?? 0)) }}</strong></td>
                <td><strong>{{ $ctcAnnual ? number_format($ctcAnnual) : '—' }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p>
        Other terms and conditions of your employment remain as per company policy and your offer letter.
        You are required to comply with all applicable policies, procedures, and statutory requirements of the Company.
    </p>

    <p>
        Please sign and return this appointment letter through the onboarding portal as a token of your acceptance.
    </p>

    <table class="signatures-row" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%" class="sign-col-left">
                @include('pdf.partials.hr-authorized-signatory', ['approval' => $apptSrApproval])
            </td>
            <td width="50%" class="sign-col-right">
                @if ($apptAccepted || ($letterRenderMode ?? 'pdf') === 'html')
                    @include('pdf.partials.candidate-accept-signatory', [
                        'employee' => $employee ?? null,
                        'candidateName' => $name,
                        'signatureSrc' => $apptAccepted ? ($apptSignature ?? null) : ($testPlaceholderSignature ?? null),
                        'signatureAsUrl' => $signatureAsUrl ?? (($letterRenderMode ?? 'pdf') === 'html'),
                        'compact' => false,
                    ])
                @endif
            </td>
        </tr>
    </table>
</div>
