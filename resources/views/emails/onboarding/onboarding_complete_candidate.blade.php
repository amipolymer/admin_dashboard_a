<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Onboarding Complete</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

<p>Dear <strong>{{ $employee->emp_name }}</strong>,</p>

<p>Congratulations! You have completed all onboarding steps including appointment letter acceptance.</p>

<p>Welcome to <strong>{{ config('app.name', 'Ami Polymer') }}</strong>. HR will share any final instructions before your joining day.</p>

<p>Regards,<br><strong>HR Team</strong><br>{{ config('app.name', 'Ami Polymer') }}</p>

</body>
</html>
