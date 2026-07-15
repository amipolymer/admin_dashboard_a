<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Policy Acceptance</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        .meta { margin-bottom: 14px; }
        .meta p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; }
        .signature img { max-height: 100px; border: 1px solid #ccc; margin-top: 8px; }
        .footer { margin-top: 24px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>Company Policy Acceptance Record</h1>
    <div class="meta">
        <p><strong>Candidate:</strong> {{ $employee->emp_name }}</p>
        <p><strong>Employee ID (joiner):</strong> {{ $employee->displayEmployeeId() }}</p>
        <p><strong>Accepted on:</strong> {{ $acceptedAt->format('d M Y, h:i A') }}</p>
    </div>

    <h2>Policies accepted</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th>Policy</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($policyTitles as $index => $title)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $title }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No policy list recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Candidate signature</h2>
    <div class="signature">
        @if (!empty($signature))
            <img src="{{ $signature }}" alt="Policy signature">
        @else
            <p>Signature on file.</p>
        @endif
    </div>

    <div class="footer">
        This document was generated automatically when the candidate accepted company policies in the onboarding portal.
    </div>
</body>
</html>
