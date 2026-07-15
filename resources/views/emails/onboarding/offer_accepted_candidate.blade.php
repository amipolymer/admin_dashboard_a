<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Offer Accepted</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

<p>Dear <strong>{{ $offer['candidate_name'] ?? $employee->emp_name }}</strong>,</p>

<p>Thank you for accepting our offer of employment at <strong>{{ config('app.name', 'Ami Polymer') }}</strong>.</p>

<p>Your signed offer has been received. HR will review and contact you for the next steps (documents, verification, and joining formalities).</p>

<p><strong>Role:</strong> {{ $offer['role'] ?? $employee->emp_role ?? '—' }}<br>
<strong>Designation:</strong> {{ $offer['designation'] ?? '—' }}</p>

<p>You can return to your portal anytime:<br>
<a href="{{ $portalLink }}" style="color:#034ea1;">{{ $portalLink }}</a></p>

<p>Warm regards,<br><strong>HR Team</strong><br>{{ config('app.name', 'Ami Polymer') }}</p>

</body>
</html>
