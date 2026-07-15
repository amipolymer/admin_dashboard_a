<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>HR Notification</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

<p>Dear <strong>{{ $hr->name ?? 'HR' }}</strong>,</p>

<p><strong>{{ $activityTitle }}</strong> — candidate <strong>{{ $employee->emp_name }}</strong>.</p>

<p>{{ $detail }}</p>

<p>
    <strong>Current step:</strong> {{ $step }}<br>
    <strong>Email:</strong> {{ $employee->emp_email }}<br>
    <strong>Phone:</strong> {{ $employee->emp_phone }}
</p>

<p>Review full profile, documents, and onboarding actions:<br>
<a href="{{ $adminLink }}" style="color:#034ea1;">{{ $adminLink }}</a></p>

<p>Regards,<br>
 Onboarding System</p>

</body>
</html>
