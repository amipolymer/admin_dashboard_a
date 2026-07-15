<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Appointment accepted</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $employee->emp_name }}</strong>,</p>
    <p>Thank you for accepting and signing your appointment letter.</p>
    <p>Your appointment is pending final <strong>Approval</strong>. HR will notify you when onboarding is fully complete.</p>
    <p><a href="{{ $portalLink }}">Open onboarding portal</a></p>
    <p>Regards,<br>HR Team<br>{{ config('app.name', 'Ami Polymer') }}</p>
</body>
</html>
