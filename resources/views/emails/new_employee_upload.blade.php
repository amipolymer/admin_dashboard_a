<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Upload Joining Documents</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6;">

    <p>Dear <strong> {{ $employee->emp_name }},</strong></p>

    <p>
        Welcome to <strong>Ami Polymer</strong>!
        We are pleased to have you join our team.
    </p>

    <p>
        To complete your onboarding process, please upload your personal
        information and required documents using the secure link below:  <a id="uploadLink" style="color:blue" href="{{ $uploadLink }}">  {{$uploadLink}}</a>
    </p>
        <br>
   


    <!-- <p><strong>Required Documents:</strong></p>
<ul>
    @foreach ($documentNamesList as $category => $documents)
        <li><strong>{{ $category }}</strong>
            <ol>
                @foreach ($documents as $documentKey => $documentName)
                    <li>{{ $documentName }}</li>
                @endforeach
            </ol>
        </li>
    @endforeach
</ul> -->


    <p>
        Kindly complete this process on or before
        <strong>{{ $employee->emp_document_due_date->format('d-M-Y') }}</strong>.
    </p>
    <p> You can also visit our website: <a href="https://amipolymer.com">www.amipolymer.com</a></p>
    <p>
        If you face any issues, please contact us at
        <a href="mailto:{{ $employee->user->email }}">{{ $employee->user->email  }}</a> or call <a
            href="tel:{{$employee->user->phoneno}}">{{$employee->user->phoneno }}</a>
    </p>

    <p>
        We look forward to working with you and wish you a great start at
        <strong>Ami Polymer</strong>.
    </p>

    <br>

    <p>
        Warm regards,<br>
        <strong>HR Team</strong><br>
        {{-- {{$employee->user->name}}<br> --}}
        {{-- check if user name show test user then replace with "Test User" to HR Name --}}
            @if(strtolower($employee->user->name) === 'test user')
                <strong>HR Name</strong>
            @else
                <strong>{{ $employee->user->name }}</strong>
            @endif
            <br>
         {{ config('app.name', 'Ami Polymer') }}
    </p>
<script>
function copyLink() {
    // Get the link text
    const link = document.getElementById('uploadLink').innerText;

    // Use the Clipboard API
    navigator.clipboard.writeText(link).then(() => {
        alert('Link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}
</script>
</body>

</html>