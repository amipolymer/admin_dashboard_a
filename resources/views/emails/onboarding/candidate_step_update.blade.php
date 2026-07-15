<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Onboarding Update</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

<p>Dear <strong>{{ $employee->emp_name }}</strong>,</p>

<p><strong>{{ $headline }}</strong></p>

<p>{{ $body }}</p>

<p>
    <a href="{{ $portalLink }}" style="display:inline-block;padding:10px 18px;background:#034ea1;color:#fff;text-decoration:none;border-radius:4px;">
        {{ $actionLabel }}
    </a>
</p>

<p>Or open this link:<br><a href="{{ $portalLink }}" style="color:#034ea1;">{{ $portalLink }}</a></p>

<p>Regards,<br><strong>HR Team</strong><br>{{ config('app.name', 'Ami Polymer') }}</p>

</body>
</html>
