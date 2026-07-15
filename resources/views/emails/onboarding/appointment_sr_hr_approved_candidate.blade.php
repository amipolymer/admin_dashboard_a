<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Appointment letter approved</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <p>Dear <strong>{{ $appointment['candidate_name'] ?? $employee->emp_name }}</strong>,</p>

    <p>
        Your signed appointment letter has received <strong>final approval</strong> from our Senior HR team.
        Your onboarding steps on the portal are now <strong>complete</strong>.
    </p>

    <p><strong>Appointment summary:</strong></p>
    <ul>
        <li><strong>Role:</strong> {{ $appointment['role'] ?? '-' }}</li>
        <li><strong>Designation:</strong> {{ $appointment['designation'] ?? '-' }}</li>
        @if (!empty($appointment['joining_date']))
            <li><strong>Joining Date:</strong> {{ \Carbon\Carbon::parse($appointment['joining_date'])->format('d M Y') }}</li>
        @endif
        @if (!empty($appointment['location']))
            <li><strong>Location:</strong> {{ $appointment['location'] }}</li>
        @endif
    </ul>

    <p>
        <strong>Your approved appointment letter is attached to this email as a PDF.</strong>
        Please save it for your records.
    </p>

    @if (!empty($pdfLink))
        <p>
            You can also open or download the PDF here:
            <br>
            <a href="{{ $pdfLink }}" style="display:inline-block;margin-top:8px;padding:10px 18px;background:#034ea1;color:#fff;text-decoration:none;border-radius:4px;">
                Download appointment letter (PDF)
            </a>
        </p>
        <p class="small" style="color:#666;">Link: <a href="{{ $pdfLink }}" style="color:#034ea1;">{{ $pdfLink }}</a></p>
    @endif

    <p>
        Welcome to <strong>{{ config('app.name', 'Ami Polymer') }}</strong>.
        HR will share any final instructions before your joining day.
    </p>

    <p>
        Onboarding portal:
        <br>
        <a href="{{ $portalLink }}" style="color:#034ea1;">{{ $portalLink }}</a>
    </p>

    <p>Regards,<br><strong>HR Team</strong><br>{{ config('app.name', 'Ami Polymer') }}</p>

</body>
</html>
