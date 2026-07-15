<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Letter — {{ $employee->emp_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; }
        .page { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="page">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 class="mb-0">Appointment Letter (HR View)</h4>
                <div>
                    <a href="{{ route('appointment.preview.pdf', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Open print view</a>
                    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>
            </div>
            <iframe src="{{ route('appointment.preview.pdf', $employee->id) }}" style="width:100%;height:75vh;border:1px solid #dee2e6;border-radius:8px;"></iframe>
        </div>
    </div>
</body>
</html>
