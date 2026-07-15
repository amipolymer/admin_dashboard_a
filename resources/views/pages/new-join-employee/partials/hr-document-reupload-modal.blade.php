@php
    use App\Support\OnboardingHrDocumentReupload;
    $oldReuploadDoc = null;
    if (old('document_id') && isset($document_list)) {
        $oldReuploadDoc = $document_list->firstWhere('id', (int) old('document_id'));
    }
    $initialReuploadLabel = $oldReuploadDoc
        ? (collect($documentNamesList ?? [])->collapse()[$oldReuploadDoc->emp_select_document] ?? $oldReuploadDoc->emp_select_document)
        : '—';
    $initialReuploadFile = $oldReuploadDoc->emp_document_file ?? '—';
@endphp

<div class="modal fade" id="hrDocumentReuploadModal" tabindex="-1" role="dialog" aria-labelledby="hrDocumentReuploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST"
                  action="{{ route('EmployeeJoiner.hrReuploadDocument', $employee->id) }}"
                  enctype="multipart/form-data"
                  id="hrDocumentReuploadForm">
                @csrf
                <input type="hidden" name="document_id" id="hrReuploadDocumentId" value="{{ old('document_id') }}">

                <div class="modal-header py-2">
                    <h5 class="modal-title" id="hrDocumentReuploadModalLabel">Re-upload Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold mb-1">Document</label>
                        <div class="form-control-plaintext border rounded px-2 py-1 bg-light" id="hrReuploadDocumentLabel">
                            {{ $initialReuploadLabel }}
                        </div>
                        <small class="text-muted d-block mt-1">
                            Current file: <span id="hrReuploadCurrentFile">{{ $initialReuploadFile }}</span>
                        </small>
                    </div>

                    <div class="form-group mb-2">
                        <label for="hrReuploadFile" class="font-weight-bold">Choose file <span class="text-danger">*</span></label>
                        <input type="file"
                               name="document_file"
                               id="hrReuploadFile"
                               class="form-control-file form-control"
                               required>
                        <small class="text-muted d-block mt-1" id="hrReuploadFileHint">PDF only unless noted otherwise.</small>
                        @error('document_file')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="hrReuploadReason" class="font-weight-bold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reupload_reason"
                                  id="hrReuploadReason"
                                  class="form-control"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Why is this file being replaced?"
                                  required>{{ old('reupload_reason') }}</textarea>
                        @error('reupload_reason')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('hrDocumentReuploadModal');
    if (!modal) {
        return;
    }

    var form = document.getElementById('hrDocumentReuploadForm');
    var docIdInput = document.getElementById('hrReuploadDocumentId');
    var labelEl = document.getElementById('hrReuploadDocumentLabel');
    var fileEl = document.getElementById('hrReuploadCurrentFile');
    var fileInput = document.getElementById('hrReuploadFile');
    var hintEl = document.getElementById('hrReuploadFileHint');
    var reasonEl = document.getElementById('hrReuploadReason');

    document.querySelectorAll('.hr-doc-reupload-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            docIdInput.value = btn.getAttribute('data-document-id') || '';
            labelEl.textContent = btn.getAttribute('data-document-label') || 'Document';
            fileEl.textContent = btn.getAttribute('data-current-file') || '—';
            hintEl.textContent = btn.getAttribute('data-file-hint') || 'PDF only unless noted otherwise.';
            fileInput.value = '';
            fileInput.accept = btn.getAttribute('data-file-accept') || '.pdf,application/pdf';

            if (!reasonEl.value) {
                reasonEl.value = '';
            }

            $('#hrDocumentReuploadModal').modal('show');
        });
    });

    @if ($errors->has('document_file') || $errors->has('reupload_reason') || $errors->has('document_id'))
        if (form && docIdInput.value) {
            $('#hrDocumentReuploadModal').modal('show');
        }
    @endif
});
</script>
@endpush
