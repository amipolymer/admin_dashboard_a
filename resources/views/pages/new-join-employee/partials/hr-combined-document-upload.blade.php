@php
    use App\Support\OnboardingArchive;
    use App\Support\OnboardingHrCombinedDocument;

    $isFinalized = OnboardingArchive::isFinalized($employee);
    $canUpload = !$isFinalized && OnboardingHrCombinedDocument::hrCanUpload($employee);
    $existing = OnboardingHrCombinedDocument::existing($employee);
    $note = ($employee->emp_other ?? [])['hr_combined_document_note'] ?? '';
    $mainUrl = config('app.main_url');
    $assetPrefix = config('app.asset_prefix');
    $expandCombined = $existing || $errors->has('hr_combined_file') || $errors->has('hr_combined_note');
@endphp

@if ($canUpload || $existing)
<div class="card mb-3 shadow-sm border-info" id="hrCombinedDocumentPanel">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center hr-collapse-toggle"
         data-toggle="collapse" data-target="#collapseHrCombinedDoc" aria-expanded="{{ $expandCombined ? 'true' : 'false' }}">
        <strong><i class="dw dw-file-1 text-info"></i> Combined legacy / missing documents (HR)</strong>
        <small class="text-muted collapse-hint">{{ $expandCombined ? 'Hide' : 'Show' }}</small>
    </div>
    <div id="collapseHrCombinedDoc" class="collapse {{ $expandCombined ? 'show' : '' }}">
    <div class="card-body py-3">
        <p class="small text-muted mb-2">
            After all documents and processes are complete, HR may upload one combined PDF for any legacy files or missing documents.
        </p>

        @if ($existing && $existing->emp_document_file_path)
            <div class="alert alert-success py-2 small mb-2">
                <strong>Combined PDF on file.</strong>
                <a href="{{ $mainUrl }}{{ $assetPrefix }}{{ $existing->emp_document_file_path }}" target="_blank" class="alert-link">View file</a>
                @if ($note)
                    <br><span class="text-muted">Note:</span> {{ $note }}
                @endif
            </div>
        @endif

        @if ($canUpload)
            <form method="POST" action="{{ route('EmployeeJoiner.onboardingStep', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="upload_hr_combined_pdf">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5 mb-2">
                        <label class="font-weight-bold small mb-1">Combined PDF *</label>
                        <input type="file" name="hr_combined_file" class="form-control form-control-sm" accept=".pdf,application/pdf" {{ $existing ? '' : 'required' }}>
                        {{-- <small class="text-muted">PDF only. Replaces previous upload if any.</small> --}}
                    </div>
                    <div class="form-group col-md-5 mb-2">
                        <label class="font-weight-bold small mb-1">Note (optional)</label>
                        <input type="text" name="hr_combined_note" class="form-control form-control-sm" maxlength="500"
                            value="{{ old('hr_combined_note', $note) }}" placeholder="e.g. Legacy appointment + salary slips">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-info btn-block">Upload PDF</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
    </div>
</div>
@endif
