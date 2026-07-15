{{-- Reuses upload flow from frontend/upload-document/index-v1.blade.php --}}
@php
    use App\Data\DocumentNamesList;
    use App\Support\OnboardingDocumentRequirements;
    use App\Support\OnboardingMediclaimDocuments;

    $documentNames = $documentNamesList;
    $documentLabels = OnboardingMediclaimDocuments::mergeIntoLabels(DocumentNamesList::collapsedLabels(), $employee);
    $hrManagedDocKeys = array_flip(DocumentNamesList::hrManagedKeys());
    $uploadedDocs = $document_list->pluck('emp_select_document')->toArray();
    if ($employee->emergency_contact) {
        $uploadedDocs[] = 'emergency_contact';
    }
    $uploadfilestatus = 0;
    $isEdit = $isEdit ?? false;
    $assetPrefix = config('app.asset_prefix');
    $main_url = $main_url ?? config('app.main_url');
    $documentReeditActive = $documentReeditActive ?? false;
    $documentReeditKeys = $documentReeditKeys ?? [];
    $documentReeditReason = $documentReeditReason ?? '';
    $allDocumentsRequired = $allDocumentsRequired ?? true;
    $missingRequiredDocuments = $missingRequiredDocuments ?? [];
    $isFresher = $isFresher ?? $employee->isFresher();
    $requiredKeySet = $requiredDocumentKeySet ?? array_flip(OnboardingDocumentRequirements::requiredKeys($employee));
    $optionalEmploymentKeys = array_flip($optionalEmploymentDocumentKeys ?? OnboardingDocumentRequirements::employmentDocumentKeys());
    $reeditKeySet = array_flip($documentReeditKeys);
    $mediclaimDocLabels = OnboardingMediclaimDocuments::documentLabels($employee);
@endphp

<style>
    .doc-upload-wrap .btn-primary { background-color: #034ea1; border-color: #034ea1; }
    .doc-upload-wrap .btn-primary:hover { background-color: #4672a3; border-color: #034ea1; color: #fff; }
    .doc-upload-wrap .btn-outline-primary { border-color: #034ea1; color: #034ea1; }
    .doc-upload-wrap .tablehead { background-color: #4672a3 !important; color: #fff !important; }
</style>

<div class="doc-upload-wrap">
    @if (!empty($documentBlockMessage) && !$documentReeditActive)
        <div class="alert alert-warning">{{ $documentBlockMessage }}</div>
    @elseif (!$canUploadDocuments && !in_array($currentStep ?? '', ['documents_submitted', 'hr_review', 'documents_approved', 'documents_rejected'], true) && !$documentReeditActive)
        <div class="alert alert-warning mb-0">Document upload is not available at this stage. Complete your profile first or contact HR.</div>
    @else
        @if ($documentReeditActive)
            <div class="alert alert-info py-2 small mb-3">
                <strong>HR has asked you to upload or replace documents.</strong>
                @if ($documentReeditReason)
                    <br><span class="text-muted">Reason:</span> {{ $documentReeditReason }}
                @endif
                <br>Upload only the document types listed below, then click <strong>Confirm &amp; Submit</strong>.
                <br><span class="text-muted">To replace an uploaded file, use the <strong>Re-upload</strong> button in the list below — already uploaded types are disabled in the dropdown.</span>
            </div>
        @elseif ($allDocumentsRequired)
            @if ($isFresher)
                <div class="alert alert-warning py-2 small mb-3">
                    <strong>Fresher profile — required documents.</strong>
                    Upload all mandatory items below, then click <strong>Confirm &amp; Submit</strong>.
                    <br><span class="text-muted">Employment documents</span> (Appointment Letter, Increment Letter, Salary Slips, Bank Statement) are <strong>optional</strong> for freshers — upload only if you have them.
                    @if (!empty($missingRequiredDocuments))
                        <br><span class="text-muted">Still missing:</span> {{ implode(', ', $missingRequiredDocuments) }}
                    @endif
                </div>
            @else
                <div class="alert alert-warning py-2 small mb-3">
                    <strong>All document types are required.</strong>
                    Upload every item below, including <strong>Employment</strong> documents (Appointment Letter, Increment Letter, Salary Slips, Bank Statement), then click <strong>Confirm &amp; Submit</strong>.
                    @if (!empty($missingRequiredDocuments))
                        <br><span class="text-muted">Still missing:</span> {{ implode(', ', $missingRequiredDocuments) }}
                    @endif
                </div>
            @endif
        @else
            <div class="alert alert-secondary py-2 small mb-3">
                <strong>Partial upload is allowed.</strong> Upload the documents you have available, then click <strong>Confirm &amp; Submit</strong>. HR may request additional files later.
            </div>
        @endif
        @if (!empty($mediclaimDocLabels))
            <div class="alert alert-secondary py-2 small mb-3">
                <strong>Mediclaim Aadhaar (optional):</strong> Upload dependent Aadhaar cards from the <strong>Mediclaim</strong> group below. These are separate from the mandatory document checklist and are only needed after joining details are submitted.
            </div>
        @endif
        <div class="alert alert-info py-2 small mb-3">
            <strong>File formats:</strong> Passport photo — <strong>JPG/PNG</strong> only (max <strong>1 MB</strong>).
            All other documents — <strong>PDF</strong> only (max <strong>3 MB</strong>; bank statement max <strong>5 MB</strong>).
            Signatures (offer / policy / appointment upload) — <strong>JPG/PNG</strong> only.
        </div>
        {{-- UPLOAD FORM — post to portal route (same host/port); avoid legacy absolute APP_URL links --}}
        <form action="{{ route('onboarding.save', $employee->emp_url, false) }}"
              method="POST" enctype="multipart/form-data" class="mb-3" id="documentUploadForm">
            @csrf
            <input type="hidden" name="action" value="{{ $isEdit ? 'reupload_document' : 'upload_document' }}">
            @if ($isEdit)
                <input type="hidden" name="document_id" value="{{ $editDocument->id }}">
            @endif
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>{{ $isEdit ? 'Re-upload Document' : 'Upload Document' }}</strong>
                    @if ($isEdit)
                        <a href="{{ route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'document'], false) }}"
                           class="btn btn-sm btn-primary float-end">Back</a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <input type="hidden" name="emp_id" value="{{ $employee->id }}">

                        <div class="col-md-4">
                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                            @if ($isEdit)
                                <input type="hidden" id="documentTypeEdit" value="{{ $editDocument->emp_select_document }}">
                                <input type="hidden" name="document_type" value="{{ $editDocument->emp_select_document }}">
                                <input type="text" class="form-control" disabled
                                    value="{{ $documentLabels[$editDocument->emp_select_document] ?? $editDocument->emp_select_document }}">
                            @else
                                <select name="document_type" id="documentType" class="form-control" required>
                                    <option value="">-- Select Document --</option>
                                    @foreach ($documentNames as $group => $docs)
                                        <optgroup label="{{ $group }}{{ ($group === 'Employment' && $isFresher) ? ' (optional for freshers)' : (($group === 'Employment' && !$isFresher && $allDocumentsRequired) ? ' (required)' : '') }}">
                                            @foreach ($docs as $key => $label)
                                                @php
                                                    $inReeditList = !$documentReeditActive || isset($reeditKeySet[$key]);
                                                    $alreadyUploaded = in_array($key, $uploadedDocs);
                                                    $disableOption = !$inReeditList || $alreadyUploaded;
                                                    $optionLabel = $label;
                                                    if ($isFresher && isset($optionalEmploymentKeys[$key])) {
                                                        $optionLabel .= ' (optional)';
                                                    } elseif (!$isFresher && $allDocumentsRequired && isset($requiredKeySet[$key])) {
                                                        $optionLabel .= ' *';
                                                    }
                                                    if ($alreadyUploaded && $documentReeditActive && $inReeditList) {
                                                        $optionLabel = $label;
                                                    }
                                                @endphp
                                                @if ($inReeditList)
                                                    <option value="{{ $key }}"
                                                        {{ old('document_type') === $key ? 'selected' : '' }}
                                                        {{ $disableOption ? 'disabled' : '' }}>
                                                        {{ $optionLabel }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <div id="emergency_contact" style="display:none">
                                <label class="form-label">Emergency Contact <span class="text-danger" id="emergencyRequiredMark">*</span></label>
                                <input type="text" name="emergency_contact" id="emergencyContactInput"
                                    class="form-control @error('emergency_contact') is-invalid @enderror"
                                    value="{{ old('emergency_contact', $isEdit ? ($editDocument->emp_document_file ?? '') : '') }}">
                               
                            </div>
                            <div id="document_file">
                                <label class="form-label">Select File <span class="text-danger" id="documentFileRequiredMark">* <small class="text-muted" id="documentFileHint">PDF only (max 3 MB)</small></span></label>
                                <input type="file" name="document_file" id="documentFileInput" class="form-control" accept=".pdf,application/pdf">
                                
                                <div class="invalid-feedback d-block" id="documentFileSizeError"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-upload"></i> {{ $isEdit ? 'Re-upload' : 'Upload' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- UPLOADED TABLE --}}
        <div class="card">
            <div class="card-header bg-light">
                <strong>Uploaded Documents</strong>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0 text-center">
                    <thead class="tablehead">
                        <tr>
                            <th>Sr.</th>
                            <th class="text-start">Document</th>
                            <th class="text-start">File Name</th>
                            <th>Status</th>
                            <th>Uploaded On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = 0; @endphp
                        @forelse($document_list as $document)
                            @if (isset($hrManagedDocKeys[$document->emp_select_document]))
                                @continue
                            @endif
                            @php
                                $showInReedit = $documentReeditActive && isset($reeditKeySet[$document->emp_select_document]);
                                $canReuploadRow = in_array($document->emp_document_status, ['rejected', 'upload', 'approved', 'process'], true) || $showInReedit;
                            @endphp
                            @if ($document->emp_document_status != 'approved' || $showInReedit)
                                @php $sr++; @endphp
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td class="text-start">
                                        {{ $documentLabels[$document->emp_select_document] ?? $document->emp_select_document }}
                                    </td>
                                    <td class="text-start">{{ $document->emp_document_file }}</td>
                                    <td>
                                        @if ($document->emp_document_status == 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                            @if ($document->rejection_reason)
                                                <br><small class="text-danger">{{ $document->rejection_reason }}</small>
                                            @endif
                                            @php $uploadfilestatus = 1 @endphp
                                        @elseif ($document->emp_document_status == 'process')
                                            <span class="badge bg-warning text-dark">Process</span>
                                            @php $uploadfilestatus = 1 @endphp
                                        @else
                                            <span class="badge bg-info">Upload</span>
                                            @php $uploadfilestatus = 1 @endphp
                                        @endif
                                    </td>
                                    <td>{{ $document->emp_doc_date ? \Carbon\Carbon::parse($document->emp_doc_date)->format('Y-m-d') : '-' }}</td>
                                    <td class="text-nowrap">
                                        @php $file_name = encrypt($document->emp_document_file); @endphp
                                        <a href="{{ route('employee.documents.ViewFile', ['id' => $employee->emp_url, 'file' => $file_name], false) }}"
                                           target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                        @if ($canReuploadRow && ($documentReeditActive ? $showInReedit : in_array($document->emp_document_status, ['rejected', 'upload'])))
                                            <a href="{{ route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'document', 'edit' => encrypt($document->id)], false) }}"
                                               class="btn btn-sm btn-outline-danger">Re-upload</a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled>Re-upload</button>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6">No documents uploaded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (($uploadfilestatus == 1 && $canUploadDocuments) || $documentReeditActive)
                <div class="text-end p-3 border-top">
                    <form action="{{ route('onboarding.save', $employee->emp_url, false) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="confirm_documents">
                        <button type="submit" class="btn btn-primary"
                            onclick="return confirm('{{ $documentReeditActive ? 'Submit the uploaded documents to HR?' : ($allDocumentsRequired ? 'Are you sure all required documents are uploaded?' : 'Submit the documents you have uploaded to HR?') }}')">
                            Confirm & Submit
                        </button>
                    </form>
                </div>
            @elseif ($canUploadDocuments && !$allDocumentsRequired && count($uploadedDocs) > 0)
                <div class="text-end p-3 border-top">
                    <form action="{{ route('onboarding.save', $employee->emp_url, false) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="confirm_documents">
                        <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Submit the documents you have uploaded to HR?')">
                            Confirm & Submit
                        </button>
                    </form>
                </div>
            @elseif (in_array($currentStep ?? '', ['documents_submitted', 'hr_review'], true))
                <div class="alert alert-info m-3 mb-0">Waiting for HR review.</div>
            @elseif (($currentStep ?? '') === 'documents_rejected')
                <div class="alert alert-danger m-3 mb-0">Some documents were rejected. Please re-upload and confirm again.</div>
            @endif
        </div>
    @endif
</div>

<script>
    function documentFileMaxBytes(docType) {
        if (docType === 'photo') return 1024 * 1024;
        if (docType === 'bank_statement') return 5 * 1024 * 1024;
        return 3 * 1024 * 1024;
    }

    function documentFileSizeHint(docType) {
        if (docType === 'photo') return 'JPG or PNG only (max 1 MB)';
        if (docType === 'bank_statement') return 'PDF only (max 5 MB)';
        return 'PDF only (max 3 MB)';
    }

    function currentDocumentType() {
        const select = document.getElementById('documentType');
        const editValue = document.getElementById('documentTypeEdit');
        if (select) return select.value || '';
        if (editValue) return editValue.value || '';
        return '';
    }

    function clearDocumentFileSizeError() {
        const fileInput = document.getElementById('documentFileInput');
        const errorEl = document.getElementById('documentFileSizeError');
        if (fileInput) fileInput.classList.remove('is-invalid');
        if (errorEl) errorEl.textContent = '';
    }

    function validateDocumentFileSize(showMessage) {
        const docType = currentDocumentType();
        const fileInput = document.getElementById('documentFileInput');
        const errorEl = document.getElementById('documentFileSizeError');
        if (!fileInput || docType === '' || docType === 'emergency_contact') {
            clearDocumentFileSizeError();
            return true;
        }

        const file = fileInput.files && fileInput.files[0];
        if (!file) {
            clearDocumentFileSizeError();
            return true;
        }

        const maxBytes = documentFileMaxBytes(docType);
        if (file.size <= maxBytes) {
            clearDocumentFileSizeError();
            return true;
        }

        const label = docType === 'bank_statement' ? '5 MB' : (docType === 'photo' ? '1 MB' : '3 MB');
        const message = 'File is too large. Maximum allowed size is ' + label + '.';
        fileInput.classList.add('is-invalid');
        if (errorEl) errorEl.textContent = message;
        if (showMessage) alert(message);
        return false;
    }

    function toggleDocFields(value) {
        const emergency = document.getElementById('emergency_contact');
        const file = document.getElementById('document_file');
        const emergencyInput = document.getElementById('emergencyContactInput');
        const fileInput = document.getElementById('documentFileInput');
        const emergencyMark = document.getElementById('emergencyRequiredMark');
        const fileMark = document.getElementById('documentFileRequiredMark');
        const hint = document.getElementById('documentFileHint');
        if (!emergency || !file) return;

        const isEmergency = value === 'emergency_contact';
        const hasType = value !== '';

        if (isEmergency) {
            emergency.style.display = 'block';
            file.style.display = 'none';
            if (emergencyInput) emergencyInput.required = true;
            if (emergencyMark) emergencyMark.style.display = '';
            if (fileInput) {
                fileInput.required = false;
                fileInput.value = '';
                clearDocumentFileSizeError();
            }
            if (fileMark) fileMark.style.display = 'none';
        } else {
            emergency.style.display = 'none';
            file.style.display = 'block';
            if (emergencyInput) {
                emergencyInput.required = false;
                if (!document.getElementById('documentTypeEdit')) {
                    emergencyInput.value = '';
                }
            }
            if (emergencyMark) emergencyMark.style.display = 'none';
            if (fileInput) {
                fileInput.required = hasType;
                if (value === 'photo') {
                    fileInput.accept = '.jpg,.jpeg,.png,image/jpeg,image/png';
                } else if (hasType) {
                    fileInput.accept = '.pdf,application/pdf';
                }
                if (hint) hint.textContent = hasType ? documentFileSizeHint(value) : 'PDF only (max 3 MB)';
                clearDocumentFileSizeError();
            }
            if (fileMark) fileMark.style.display = hasType ? '' : 'none';
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('documentType');
        const editValue = document.getElementById('documentTypeEdit');
        if (select) {
            const initialType = select.value || @json(old('document_type', ''));
            if (initialType && !select.value) {
                select.value = initialType;
            }
            toggleDocFields(select.value || initialType);
            select.addEventListener('change', () => toggleDocFields(select.value));
        }
        if (editValue) toggleDocFields(editValue.value);

        const fileInput = document.getElementById('documentFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                validateDocumentFileSize(false);
            });
        }

        const uploadForm = document.getElementById('documentUploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function (e) {
                if (!validateDocumentFileSize(true)) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
