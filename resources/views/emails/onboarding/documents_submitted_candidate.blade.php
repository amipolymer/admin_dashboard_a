<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Documents Submitted</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

<p>Dear <strong>{{ $employee->emp_name }}</strong>,</p>

<p>Thank you — we have received your uploaded documents for onboarding.</p>

<p>HR will review them and update you when the offer letter or next step is ready.</p>

<p><a href="{{ $portalLink }}" style="color:#034ea1;">View your portal</a></p>

<p>Regards,<br><strong>HR Team</strong><br>{{ config('app.name', 'Ami Polymer') }}</p>

</body>
</html>
