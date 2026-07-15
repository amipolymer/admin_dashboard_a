<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Annual Report | Email</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <p>Dear {{ $annualReport->full_name ?? 'Sir/Ma\'am' }},</p>

    <p>I hope this message finds you well.</p>

    <p><strong>The annual report for the year {{ $reportYear }} is ready for your review.</strong></p>

    <p>You can view the report by clicking the link below:</p>
    <p><a href="{{ $viewLink }}" target="_blank">{{ $viewLink }}</a></p>

    <p>Thank you for your attention.</p>

    <p>Best regards,<br>
       APPL Team
    </p>

</body>

</html>