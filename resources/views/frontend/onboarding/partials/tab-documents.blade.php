<h4 class="mb-3"><i class="bi bi-folder2-open me-2"></i>Documents</h4>

@php
    use App\Data\DocumentNamesList;
    use App\Support\OnboardingMediclaimDocuments;

    $letterKeys = DocumentNamesList::hrManagedKeys();
    $documentLabels = OnboardingMediclaimDocuments::mergeIntoLabels(DocumentNamesList::collapsedLabels(), $employee);
    $registrationVerified = in_array($currentStep ?? '', [
        'registration_verified', 'bgv_started', 'bgv_completed',
        'join_forms_sent', 'join_forms_submitted', 'policy_signed',
        'appointment_sent', 'appointment_accepted', 'appointment_pending_sr_hr', 'appointment_sr_rejected', 'appointment_rejected', 'end',
    ], true);
    $letterDocs = ($document_list ?? collect())->filter(function ($d) use ($letterKeys, $registrationVerified) {
        if (!in_array($d->emp_select_document, $letterKeys, true)) {
            return false;
        }
        if (in_array($d->emp_select_document, ['signed_registration_letter', 'resignation_acceptance_letter'], true) && !$registrationVerified) {
            return false;
        }
        return true;
    });
    $mainUrl = $main_url ?? config('app.main_url');
    $assetPrefix = config('app.asset_prefix');
@endphp

@if ($letterDocs->isNotEmpty())
    <!-- <div class="info-box mb-4">
        <h6 class="mb-2">Letters & documents submitted to HR</h6>
        <ul class="list-group list-group-flush small">
            @foreach ($letterDocs as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>{{ $documentLabels[$doc->emp_select_document] ?? $doc->emp_select_document }}</span>
                    <a href="{{ $mainUrl }}{{ $assetPrefix }}{{ $doc->emp_document_file_path }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                </li>
            @endforeach
        </ul>
    </div> -->
@endif

@if (!$profileComplete && empty($documentReeditActive))
    <div class="alert alert-warning">Complete your profile in the <strong>Info</strong> tab before uploading documents.</div>
@else
    @include('frontend.onboarding.partials.document-upload-section')
@endif
