<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">

<p>Dear Sir,</p>

<p>Approval is requested for the following <strong>{{ strtolower($type) }} letter </strong> before it can proceed to the next stage of the onboarding process.</p>

@if ($type === 'appointment')
<p><em>The candidate has already accepted and signed the appointment letter. Upon your approval, the final signed document will be saved to the employee's records.</em></p>
@else
<p><em>After your approval, the offer letter will be released and sent to the candidate.</em></p>
@endif
<h4>Candidate Details</h4>
<p>Name: <strong>{{ $employee->emp_name }}</strong><br>
{{ $employee->emp_email }} · {{ $employee->emp_phone }}</p>
<h4>Letter Details</h4>
<ul>
    <li>Role: {{ $letter['role'] ?? '—' }}</li>
    <li>Designation: {{ $letter['designation'] ?? '—' }}</li>
    <li>CTC: ₹{{ number_format((float) ($letter['ctc'] ?? $letter['ctc_annual'] ?? 0)) }}</li>
    <li>Location: {{ $letter['location'] ?? '—' }}</li>
</ul>

<h4>Preview and Approval</h4>
    <a href="{{ $previewLink }}" style="color:#034ea1;">Preview letter</a><br>
    <a href="{{ $approvalLink }}" style="display:inline-block;margin-top:8px;padding:10px 16px;background:#034ea1;color:#fff;text-decoration:none;border-radius:4px;">Open approval page</a>
</p>
<p>Please review the details and provide your approval at your earliest convenience.</p>

<p>Regards,<br>{{ config('app.name', 'Ami Polymer') }} Onboarding</p>

</body>
</html>
