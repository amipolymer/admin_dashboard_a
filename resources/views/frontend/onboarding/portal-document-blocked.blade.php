<div class="container portal-shell py-4 py-md-5">
    <div class="main-card p-4 p-md-5 text-center">
        <img src="https://amipolymer.in/wp-content/uploads/2020/02/ami-polymers.png" class="logo mb-4" alt="Ami Polymer" style="max-height:70px;">
        <div class="alert alert-danger text-start mb-0">
            <h5 class="alert-heading mb-2">{{ $title ?? \App\Support\OnboardingDocumentDeadline::blockedTitle() }}</h5>
            <p class="mb-2">{{ $message ?? \App\Support\OnboardingDocumentDeadline::blockedMessage() }}</p>
            @if (!empty($employee) && $employee->emp_document_due_date)
                <p class="small mb-2"><strong>Last date:</strong> {{ $employee->emp_document_due_date->format('d M Y') }}</p>
            @endif
            <p class="small mb-0">Please contact your <strong>HR team</strong> to request a new document submission deadline.</p>
        </div>
    </div>
</div>
