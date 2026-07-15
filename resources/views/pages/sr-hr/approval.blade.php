<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SR-HR Letter Approval{{ !empty($hrPreparerName) ? ' — ' . $hrPreparerName : '' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
<div class="container" style="max-width:820px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Sr. HR Team — {{ ($type ?? '') === 'appointment' ? 'Appointment' : 'Offer' }} letter approval</h5>
        </div>
        <div class="card-body">
            @if (!empty($invalid))
                <div class="alert alert-danger">Invalid or expired approval link.</div>
            @elseif (!empty($alreadyDecided))
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <p class="mb-0"><strong>Candidate:</strong> {{ $employee->emp_name }} ({{ $employee->emp_email }})</p>
                    <p class="mb-0 text-md-end"><strong>Requested by:</strong> {{ $hrPreparerName ?? '—' }}</p>
                </div>
                <div class="alert alert-info">
                    {{ ($type ?? '') === 'appointment' ? 'Appointment' : 'Offer' }} letter is {{ $state['status'] ?? 'processed' }}.
                    @if (!empty($state['approved_by_name']))
                        Approved by {{ $state['approved_by_name'] }} ({{ $state['approved_by_email'] ?? '' }}).
                    @endif
                    @if (!empty($state['reject_reason']))
                        <br>Reason: {{ $state['reject_reason'] }}
                    @endif
                </div>
                @php
                    $decidedSigSrc = \App\Support\HrAuthorizedSignature::previewFromApprovalBlock($state ?? [], true);
                @endphp
                @if (($state['status'] ?? '') === 'approved' && $decidedSigSrc)
                    <p class="mb-1"><strong>Your signature on file:</strong></p>
                    <img src="{{ $decidedSigSrc }}" alt="HR signature" style="max-height:64px;border:1px solid #dee2e6;padding:4px;">
                    @if (!empty($state['hr_signature_file']))
                        <!-- <p class="small text-muted mb-0 mt-1">File: {{ $state['hr_signature_file'] }}</p> -->
                    @endif
                @endif

                @include('pages.sr-hr.partials.candidate-documents')
            @else
                @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <p class="mb-0"><strong>Candidate:</strong> {{ $employee->emp_name }} ({{ $employee->emp_email }})</p>
                    <p class="mb-0 text-md-end"><strong>Requested by:</strong> {{ $hrPreparerName ?? '—' }}</p>
                </div>
                <table class="table table-sm table-bordered">
                    <tr><th>Role</th><td>{{ $letter['role'] ?? '—' }}</td></tr>
                    <tr><th>Designation</th><td>{{ $letter['designation'] ?? '—' }}</td></tr>
                    <tr><th>CTC</th><td>₹{{ number_format((float) ($letter['ctc'] ?? $letter['ctc_annual'] ?? 0)) }}</td></tr>
                    <tr><th>Location</th><td>{{ $letter['location'] ?? '—' }}</td></tr>
                    <tr><th>Joining</th><td>{{ $letter['joining_date'] ?? '—' }}</td></tr>
                </table>
                <p>
                    <a href="{{ $previewUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">Preview letter (PDF/HTML)</a>
                </p>

                @include('pages.sr-hr.partials.candidate-documents')

                <form method="POST" action="{{ route('sr-hr.approval.decide', $token) }}" class="border-top pt-3 mt-3">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Your name *</label>
                        @if (!empty($approverOptions) && count($approverOptions))
                            <select name="approver_email" class="form-select" required id="srHrApproverEmail">
                                <option value="">Select your name</option>
                                @foreach ($approverOptions as $opt)
                                    @php
                                        $m = \App\Data\SrHrTeam::findMemberByEmail($opt);
                                        $sigUrl = \App\Support\HrAuthorizedSignature::previewUrlForApproverEmail($opt);
                                    @endphp
                                    <option value="{{ $opt }}"
                                        data-sig-url="{{ $sigUrl ?? '' }}"
                                        data-sig-name="{{ $m['name'] ?? '' }}"
                                        {{ strtolower(old('approver_email', '')) === strtolower($opt) ? 'selected' : '' }}>
                                        {{ $m && ($m['name'] || $m['id']) ? ($m['name'] ?: $m['id']) : $opt }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="email" name="approver_email" class="form-control" required
                                value="{{ old('approver_email') }}" placeholder="sr-hr@company.com">
                        @endif
                    </div>
                    <div class="mb-3 border rounded p-2 bg-light" id="srHrSigPreviewBox" style="display:none;">
                        <p class="small text-muted mb-1">Signature that will appear on the letter:</p>
                        <img id="srHrSigPreviewImg" src="" alt="Signature preview" style="max-height:56px;">
                        <p class="small mb-0 mt-1" id="srHrSigPreviewName"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Decision *</label>
                        <select name="decision" class="form-select" required id="srHrDecision">
                            <option value="">Select</option>
                            <option value="approve" {{ old('decision') === 'approve' ? 'selected' : '' }}>{{ ($type ?? '') === 'appointment' ? 'Approve — save signed PDF' : 'Approve — send to candidate' }}</option>
                            <option value="reject" {{ old('decision') === 'reject' ? 'selected' : '' }}>Reject — return to HR for revision</option>
                        </select>
                    </div>
                    <div class="mb-3" id="rejectReasonBox" style="display:none;">
                        <label class="form-label">Rejection reason for HR *</label>
                        <textarea name="reject_reason" class="form-control" rows="3" minlength="10">{{ old('reject_reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Submit decision</button>
                </form>
            @endif
        </div>
    </div>
</div>
<script>
function updateSrHrSigPreview() {
    var sel = document.getElementById('srHrApproverEmail');
    var box = document.getElementById('srHrSigPreviewBox');
    var img = document.getElementById('srHrSigPreviewImg');
    var nameEl = document.getElementById('srHrSigPreviewName');
    if (!sel || !box || !img) return;
    var opt = sel.options[sel.selectedIndex];
    var url = opt ? opt.getAttribute('data-sig-url') : '';
    var name = opt ? opt.getAttribute('data-sig-name') : '';
    if (url) {
        img.src = url;
        nameEl.textContent = name ? name : '';
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
        img.src = '';
        nameEl.textContent = '';
    }
}
document.getElementById('srHrApproverEmail')?.addEventListener('change', updateSrHrSigPreview);
document.getElementById('srHrDecision')?.addEventListener('change', function () {
    document.getElementById('rejectReasonBox').style.display = this.value === 'reject' ? 'block' : 'none';
});
if (document.getElementById('srHrDecision')?.value === 'reject') {
    document.getElementById('rejectReasonBox').style.display = 'block';
}
updateSrHrSigPreview();
</script>
</body>
</html>
