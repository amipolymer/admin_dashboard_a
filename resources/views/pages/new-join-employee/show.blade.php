@extends('layouts.app')

@php
    use App\Support\OnboardingArchive;
    use App\Support\OnboardingDocumentReedit;
    use App\Support\OnboardingHrDocumentReupload;
    use App\Support\OnboardingStepGate;
    use App\Support\OnboardingFileRules;
    use App\Support\SrHrLetterApproval;
    $assetPrefix = config('app.asset_prefix');
    $step = $employee->onboardingStep();
    $stepLabel = OnboardingStepGate::humanStepLabel($step);
    $canFinalize = OnboardingArchive::canFinalize($employee);
    $isFinalized = OnboardingArchive::isFinalized($employee);
    $onboardingLocked = OnboardingStepGate::isHrMutationsBlocked($employee);
    $showHrActions = !$isFinalized && !$onboardingLocked;
    $hrCanReuploadDocs = OnboardingHrDocumentReupload::hrCanReupload($employee);
    $showSrHrPanel = !$isFinalized && !$onboardingLocked && (
        ($employee->emp_offer_sr_hr_status ?? '') === 'pending'
        || ($employee->emp_appointment_sr_hr_status ?? '') === 'pending'
        || ($employee->emp_offer_sr_hr_status ?? '') === 'rejected'
        || ($employee->emp_appointment_sr_hr_status ?? '') === 'rejected'
    );
    $hrRegDocVisible = in_array($step, [
        'registration_verified', 'bgv_started', 'bgv_completed',
        'join_forms_sent', 'join_forms_submitted', 'policy_signed',
        'appointment_sent', 'appointment_accepted', 'appointment_rejected', 'end',
    ], true);
    $hrApptLetterVisible = SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_APPOINTMENT)
        || in_array($step, ['appointment_accepted', 'end'], true);
    $processDocCount = 0;
    foreach ($document_list as $doc) {
        if ($doc->emp_select_document === 'signed_registration_letter' && !$hrRegDocVisible) {
            continue;
        }
        if (in_array($doc->emp_select_document, ['sr_hr_approved_appointment_letter', 'signed_appointment_letter'], true) && !$hrApptLetterVisible) {
            continue;
        }
        if ($doc->emp_document_status !== 'upload' && $doc->emp_document_status === 'process') {
            $processDocCount++;
        }
    }
    $expandHrActions = $processDocCount > 0
        || OnboardingDocumentReedit::wasRecentlyResubmitted($employee)
        || OnboardingDocumentReedit::isReadyForBgv($employee)
        || OnboardingDocumentReedit::canStartBgv($employee)
        || in_array($step, ['documents_submitted', 'documents_approved', 'offer_accepted', 'registration_submitted', 'registration_verified', 'bgv_started', 'bgv_completed', 'policy_signed', 'join_forms_submitted', 'appointment_sent', 'appointment_accepted'], true)
        || SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    $expandDocuments = !$isFinalized && ($processDocCount > 0 || in_array($step, ['hr_review', 'documents_rejected', 'start', 'profile_completed'], true) || SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_APPOINTMENT) || $errors->has('document_file') || $errors->has('reupload_reason'));
    $visibleDocCount = 0;
    foreach ($document_list as $doc) {
        if ($doc->emp_select_document === 'signed_registration_letter' && !$hrRegDocVisible) {
            continue;
        }
        if (in_array($doc->emp_select_document, ['sr_hr_approved_appointment_letter', 'signed_appointment_letter'], true) && !$hrApptLetterVisible) {
            continue;
        }
        if ($doc->emp_document_status !== 'upload') {
            $visibleDocCount++;
        }
    }
@endphp

@section('main_content')
<div class="container-fluid hr-onboarding-show">

    {{-- Page header --}}
    <div class="page-header mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-1">Candidate Onboarding</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('EmployeeJoiner.Index') }}">New Employee List</a></li>
                        <li class="breadcrumb-item active">{{ $employee->emp_name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-md-right mt-2 mt-md-0">
                <a href="{{ route('EmployeeJoiner.Index') }}" class="btn btn-outline-danger btn-sm">
                    <i class="dw dw-return1"></i> Back
                </a>
                <!-- <a href="{{ route('onboarding.portal', $employee->emp_url) }}" target="_blank" class="btn btn-outline-primary btn-sm ml-1">
                    Open Portal
                </a> -->
            </div>
        </div>
    </div>

    @foreach (['error' => 'danger', 'success' => 'success', 'warning' => session('bg-color') ?? 'warning'] as $key => $class)
        @if (session($key))
            <div class="alert alert-{{ $class }} alert-dismissible fade show py-2">
                {{ session($key) }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <ul class="mb-0 pl-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Summary strip (always visible) --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-2 mb-lg-0">
                    <h5 class="mb-0 font-weight-bold">{{ $employee->emp_name }}</h5>
                    <small class="text-muted">{{ $employee->emp_email }} · {{ $employee->emp_phone }} · {{ \App\Support\CandidateEmploymentType::label($employee->employmentType()) }}</small>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        @if ($isFinalized)
                            <span class="badge badge-success px-3 py-2"><i class="dw dw-checked"></i> Onboarding complete</span>
                            @if ($employee->emp_employee_id)
                                <span class="badge badge-dark px-3 py-2">ID: {{ $employee->emp_employee_id }}</span>
                            @endif
                        @elseif ($onboardingLocked)
                            <span class="badge badge-success px-3 py-2">Ready to finalize</span>
                            @if ($employee->emp_employee_id)
                                <span class="badge badge-dark px-3 py-2">ID: {{ $employee->emp_employee_id }}</span>
                            @endif
                        @else
                            <span class="badge badge-primary px-3 py-2">{{ $stepLabel }}</span>
                            @if ($processDocCount > 0)
                                <span class="badge badge-warning px-3 py-2">{{ $processDocCount }} doc(s) to review</span>
                            @elseif ($employee->emp_document_status === 'completed')
                                <span class="badge badge-success px-3 py-2">Documents approved</span>
                            @elseif ($employee->emp_document_status === 'rejected')
                                <span class="badge badge-danger px-3 py-2">Documents rejected</span>
                            @endif
                            @if (($employee->emp_offer_sr_hr_status ?? '') === 'pending')
                                <span class="badge badge-warning px-2 py-2">Offer awaiting SR-HR</span>
                            @elseif (($employee->emp_offer_sr_hr_status ?? '') === 'rejected')
                                <span class="badge badge-danger px-2 py-2">Offer SR-HR rejected</span>
                            @endif
                            @if (($employee->emp_appointment_sr_hr_status ?? '') === 'pending')
                                <span class="badge badge-warning px-2 py-2">Appointment awaiting SR-HR</span>
                            @elseif (($employee->emp_appointment_sr_hr_status ?? '') === 'rejected')
                                <span class="badge badge-danger px-2 py-2">Appointment SR-HR rejected</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Finalize banner --}}
    @if ($canFinalize)
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between mb-3">
            <span><strong>Ready to finalize.</strong> Candidate accepted appointment. Save all files to the official employee folder.</span>
            <button type="button" class="btn btn-success btn-sm mt-2 mt-md-0" data-toggle="modal" data-target="#finalizeModal">
                <i class="dw dw-folder"></i> Finalize &amp; Save to Folder
            </button>
        </div>
    @elseif ($isFinalized)
        <div class="alert alert-info py-2 mb-3 small">
            All documents stored in <strong>{{ $employee->emp_folder_path }}{{ $employee->emp_folder }}</strong>
            @if ($employee->emp_archived_at) · {{ $employee->emp_archived_at->format('d M Y H:i') }} @endif
            · <a href="{{ route('EmployeeJoiner.documents.downloadAll', $employee->id) }}">Download all</a>
        </div>
    @endif

    @include('pages.new-join-employee.partials.onboarding-hr-links', ['employee' => $employee])

    @include('pages.new-join-employee.partials.superadmin-letter-pdf-test', ['employee' => $employee])

 

    @if ($showHrActions)
        @include('pages.new-join-employee.partials.onboarding-hr-actions', ['expandHrActions' => $expandHrActions])
    @endif

    @include('pages.new-join-employee.partials.candidate-document-reedit-hr', ['employee' => $employee])

    @include('pages.new-join-employee.partials.candidate-early-join-hr', ['employee' => $employee])

    @include('pages.new-join-employee.partials.hr-combined-document-upload', ['employee' => $employee])

    @include('pages.new-join-employee.partials.bvg-status-hr', ['employee' => $employee, 'isFinalized' => $isFinalized])

    @if ($showSrHrPanel)
        @include('pages.new-join-employee.partials.sr-hr-approval-panel', ['employee' => $employee])
    @endif

    {{-- Collapsible: Basic employee info --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white cursor-pointer"
             data-toggle="collapse" data-target="#collapseBasicInfo" aria-expanded="false">
            <strong class="mb-0"><i class="dw dw-user1 text-muted"></i> Basic Information</strong>
            <small class="text-muted collapse-hint">Show</small>
        </div>
        <div id="collapseBasicInfo" class="collapse">
            <div class="card-body pt-2">
                <div class="row small">
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Department</span>{{ $employee->emp_department ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Designation</span>{{ $employee->emp_role ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Level Wise Grading</span>{{ $employee->emp_grade ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Category</span>{{ $employee->emp_category ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Location</span>{{ $employee->emp_location ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">DOB</span>{{ $employee->emp_dob ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">MRF No</span>{{ $employee->emp_mrf_no ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Doc due date</span>{{ optional($employee->emp_document_due_date)->format('d-M-Y') ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Folder</span>{{ $employee->emp_folder ?? '—' }}</div>
                    <div class="col-md-3 mb-2"><span class="text-muted d-block">Status</span>{{ $employee->emp_status }} / {{ $employee->emp_onboarding_status ?? 'active' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Collapsible: Candidate portal submitted data --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white cursor-pointer"
             data-toggle="collapse" data-target="#collapsePortalData" aria-expanded="false">
            <strong class="mb-0"><i class="dw dw-file text-muted"></i> Candidate Submitted Data</strong>
            <small class="text-muted collapse-hint">Show</small>
        </div>
        <div id="collapsePortalData" class="collapse {{ $errors->has('profile_reedit_reason') || $errors->has('document_reedit_reason') ? 'show' : '' }}">
            @include('pages.new-join-employee.partials.candidate-profile-reedit-hr')
            @include('pages.new-join-employee.partials.candidate-onboarding-data-inner')
        </div>
    </div>

    {{-- Uploaded documents (collapsible) --}}
    <form id="employeeForm" action="{{ route('EmployeeJoiner.updateDocument', $employee->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3 shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white hr-collapse-toggle"
                 data-toggle="collapse" data-target="#collapseDocuments" aria-expanded="{{ $expandDocuments ? 'true' : 'false' }}">
                <strong class="mb-0">
                    <i class="dw dw-file-1 text-muted"></i> Uploaded Documents
                    @if ($processDocCount > 0)
                        <span class="badge badge-warning ml-1">{{ $processDocCount }} pending</span>
                    @elseif ($visibleDocCount > 0)
                        <span class="badge badge-success ml-1">{{ $visibleDocCount }} on file</span>
                    @endif
                </strong>
                <span class="d-flex align-items-center gap-2">
                    @if ($processDocCount > 0 && !$isFinalized)
                        <button type="button" id="approveAllBtn" class="btn btn-outline-success btn-sm mr-2"
                                title="Set all Process documents to Approved"
                                onclick="event.stopPropagation();">
                            <i class="dw dw-checked"></i> Approve All
                        </button>
                    @endif
                    @if ($isFinalized)

                    @endif
                    <small class="text-muted collapse-hint">{{ $expandDocuments ? 'Hide' : 'Show' }}</small>
                </span>
            </div>
            <div id="collapseDocuments" class="collapse {{ $expandDocuments ? 'show' : '' }}">
            <div class="table-responsive card-body pt-2 px-2">
                <table class="table table-bordered table-striped table-hover table-sm mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center">Sr.</th>
                            <th class="text-left">Document</th>
                            <th class="text-left">File</th>
                            <th class="text-center">Status</th>
                            <th class="text-left">Remark</th>
                            <th class="text-center">Uploaded_at</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-capitalize">
                        @forelse($document_list as $index => $doc)
                            @if ($doc->emp_select_document === 'signed_registration_letter' && !$hrRegDocVisible)
                                @continue
                            @endif
                            @if (in_array($doc->emp_select_document, ['sr_hr_approved_appointment_letter', 'signed_appointment_letter'], true) && !$hrApptLetterVisible)
                                @continue
                            @endif
                            @if ($doc->emp_document_status !== 'upload')
                                <tr>
                                    <td class="text-center p-1">{{ $loop->iteration }}</td>

                                    <td class="p-1 text-left">
                                        {{ $documentNames[$doc->emp_select_document] ?? ($doc->emp_select_document ?? 'Unknown') }}
                                    </td>

                                    <td class="p-1 text-left text-nowrap">
                                        {{ $doc->emp_document_file ?? '-' }}
                                    </td>

                                    <td class="p-1 text-center">
                                        <select name="doc_status[{{ $doc->id }}]"
                                            class="form-control form-select-sm doc-status-select">
                                            <option value="process"
                                                {{ $doc->emp_document_status == 'process' ? 'selected' : '' }}
                                                disabled>Process</option>
                                            <option value="approved"
                                                {{ $doc->emp_document_status == 'approved' ? 'selected' : '' }}>
                                                Approved</option>
                                            <option value="rejected"
                                                {{ $doc->emp_document_status == 'rejected' ? 'selected' : '' }}>
                                                Rejected</option>
                                        </select>
                                    </td>

                                    <td class="p-1">
                                        <div class="remark-box mt-1"
                                            style="display: {{ $doc->emp_document_status == 'rejected' ? 'block' : 'none' }};">
                                            <textarea name="doc_remark[{{ $doc->id }}]" class="form-control form-control-sm" rows="2"
                                                placeholder="Mandatory rejection reason"
                                                required="{{ $doc->emp_document_status == 'rejected' ? 'required' : '' }}">{{ old("doc_remark.{$doc->id}", $doc->rejection_reason ?? '') }}</textarea>
                                        </div>
                                    </td>

                                    <td class="p-1 text-center text-nowrap">
                                        {{ $doc->emp_doc_date ?? '—' }}
                                    </td>

                                    <td class="p-1 text-center text-nowrap">
                                        @if ($doc->emp_select_document == 'emergency_contact')
                                            <a href="tel:{{ $doc->emp_document_file }}"
                                                class="btn btn-sm btn-outline-primary disabled">
                                                View
                                            </a>
                                            <a type="button"
                                                    class="btn disabled btn-sm btn-warning hr-doc-reupload-btn ml-1">
                                                   Edit
</a>
                                        @else
                                            <a href="{{ $main_url ?? '' }}{{ $assetPrefix }}{{ $doc->emp_document_file_path }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                            @if ($hrCanReuploadDocs && OnboardingHrDocumentReupload::canReuploadDocument($doc))
                                                <button type="button"
                                                    class="btn btn-sm btn-warning hr-doc-reupload-btn ml-1"
                                                    data-document-id="{{ $doc->id }}"
                                                    data-document-label="{{ $documentNames[$doc->emp_select_document] ?? ($doc->emp_select_document ?? 'Document') }}"
                                                    data-current-file="{{ $doc->emp_document_file ?? '—' }}"
                                                    data-file-accept="{{ OnboardingFileRules::acceptAttribute($doc->emp_select_document === 'photo' ? 'photo' : 'default') }}"
                                                    data-file-hint="{{ OnboardingFileRules::maxSizeLabel($doc->emp_select_document) ?: '3 MB (PDF only)' }}">
                                                    Edit
                                                </button>
                                                @else
                                                
                                                <a type="button"
                                                    class="btn disabled btn-sm btn-warning hr-doc-reupload-btn ml-1">
                                                   Edit
</a>
                                                @endif

                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 py-md-5 text-muted">
                                    No documents uploaded yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        @if (!$isFinalized && $processDocCount > 0)
        <div class="text-center mt-3 mb-4">
            <button type="button" id="submitBtn" class="btn btn-primary btn-lg px-3 py-1">
                <i class="dw dw-check"></i> Save All Changes
            </button>
            <p class="small text-muted mt-2 mb-0">Saves document status only. Use <strong>Finalize</strong> after appointment is approved.</p>
        </div>
        @endif
    </form>
</div>

{{-- Finalize modal (end of process only) --}}
<div class="modal fade" id="finalizeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('EmployeeJoiner.finalize', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Finalize Onboarding</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small">All onboarding steps are complete. Enter the official <strong>Employee ID</strong> to move every document and file into the permanent folder:</p>
                    <p class="small text-muted mb-3"><code>uploads/employees/EMP_{id}/</code></p>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="manual_emp_id" class="form-control" required
                               placeholder="e.g. EMP0123" value="{{ old('manual_emp_id', $employee->emp_employee_id) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save All to Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('pages.new-join-employee.partials.offering-modal', ['employee' => $employee])

@include('pages.new-join-employee.partials.hr-document-reupload-modal', [
    'employee' => $employee,
    'document_list' => $document_list,
    'documentNamesList' => $documentNamesList,
])

@php
    $empvBgvDateContext = \App\Support\OnGrid::empvStartDateFormContext($employee);
    if ($empvBgvDateContext && old('empv_start_date')) {
        $oldDate = \App\Support\OnGrid::formatEmploymentDate(old('empv_start_date'));
        if ($oldDate) {
            $empvBgvDateContext['default_date'] = $oldDate;
            $empvBgvDateContext['saved_date'] = $oldDate;
            $empvBgvDateContext['saved_display'] = \Carbon\Carbon::parse($oldDate)->format('d M Y');
            $empvBgvDateContext['has_saved_date'] = true;
            $empvBgvDateContext['is_future_scheduled'] = \Carbon\Carbon::parse($oldDate)->startOfDay()
                ->isAfter(\Carbon\Carbon::today()->startOfDay());
        }
    }
    $bgvOldOfferings = old('offerings', []);
    if (!is_array($bgvOldOfferings)) {
        $bgvOldOfferings = [];
    }
    $bgvPreviousOfferings = [];
    $bgvAlreadyActiveOfferings = [];
    if ($bgvOldOfferings === []) {
        $savedCodes = is_array($employee->ongrid_response)
            ? ($employee->ongrid_response['verification_codes'] ?? [])
            : [];
        $bgvPreviousOfferings = is_array($savedCodes) ? array_values($savedCodes) : [];
        if ($employee->ongrid_id) {
            $bgvAlreadyActiveOfferings = \App\Support\OnGrid::blockingOfferingCodesForEmployee($employee);
        }
    }
@endphp

<style>
    .hr-onboarding-show .card-header[data-toggle="collapse"],
    .hr-onboarding-show .hr-collapse-toggle { cursor: pointer; }
    .hr-onboarding-show .card-header[data-toggle="collapse"]:hover,
    .hr-onboarding-show .hr-collapse-toggle:hover { background: #f8f9fa !important; }
    .hr-onboarding-show .gap-2 > * { margin-right: 0.35rem; margin-bottom: 0.35rem; }
    .hr-onboarding-show .collapse-hint { font-size: 0.8rem; color: #6c757d; }
</style>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.doc-status-select').forEach(function (select) {
        const remarkBox = select.closest('tr').querySelector('.remark-box');
        if (!remarkBox) return;

        function toggleRemark() {
            if (select.value === 'rejected') {
                remarkBox.style.display = 'block';
                remarkBox.querySelector('textarea').required = true;
            } else {
                remarkBox.style.display = 'none';
                remarkBox.querySelector('textarea').required = false;
            }
        }

        select.addEventListener('change', toggleRemark);
        toggleRemark();
    });

    const form = document.getElementById('employeeForm');
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn && form) {
        submitBtn.addEventListener('click', function () {
            form.submit();
        });
    }

    const approveAllBtn = document.getElementById('approveAllBtn');
    if (approveAllBtn) {
        approveAllBtn.addEventListener('click', function () {
            let changed = 0;
            document.querySelectorAll('.doc-status-select').forEach(function (select) {
                if (select.value === 'process') {
                    select.value = 'approved';
                    select.dispatchEvent(new Event('change'));
                    changed++;
                }
            });
            if (changed === 0) {
                alert('No documents in Process status.');
            }
        });
    }

    document.querySelectorAll('[data-toggle="collapse"]').forEach(function (el) {
        var target = el.getAttribute('data-target');
        if (!target) return;
        var panel = document.querySelector(target);
        if (!panel) return;
        var hint = el.querySelector('.collapse-hint');
        function syncHint() {
            if (!hint) return;
            hint.textContent = panel.classList.contains('show') ? 'Hide' : 'Show';
        }
        panel.addEventListener('shown.bs.collapse', syncHint);
        panel.addEventListener('hidden.bs.collapse', syncHint);
        syncHint();
    });

    @if ($errors->has('manual_emp_id'))
        $('#finalizeModal').modal('show');
    @endif

    @if (session('open_bgv_modal') || $errors->has('empv_start_date'))
        openOfferingModal();
    @endif

    document.querySelectorAll('.onboarding-copy-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url') || '';
            var row = btn.closest('.onboarding-link-row');
            var input = row ? row.querySelector('.onboarding-link-input') : null;
            var done = function () {
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="dw dw-checked"></i> Copied';
                setTimeout(function () { btn.innerHTML = original; }, 1500);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done).catch(function () {
                    if (input) { input.select(); document.execCommand('copy'); done(); }
                });
            } else if (input) {
                input.select();
                document.execCommand('copy');
                done();
            }
        });
    });
});

var empvBgvDateContext = @json($empvBgvDateContext);
var bgvOldOfferings = @json($bgvOldOfferings);
var bgvPreviousOfferings = @json($bgvPreviousOfferings);
var bgvAlreadyActiveOfferings = @json($bgvAlreadyActiveOfferings);

function syncEmpvStartDateField() {
    var wrap = document.getElementById('empv_start_date_wrap');
    if (!wrap) return;
    wrap.style.display = empvBgvDateContext ? 'block' : 'none';
}

function openOfferingModal() {
    fetch("{{ route('EmployeeJoiner.getofferingList') }}")
        .then(res => res.json())
        .then(({ data, defaults }) => {
            var container = document.getElementById('offering_container');
            var defaultSet = new Set(defaults || []);
            var oldOfferings = new Set(bgvOldOfferings || []);
            var previousOfferings = new Set(bgvPreviousOfferings || []);
            var alreadyActive = new Set(bgvAlreadyActiveOfferings || []);
            var note = alreadyActive.size > 0
                ? '<p class="small text-info mb-2"><i class="dw dw-info"></i> Checks already running or completed on OnGrid stay unchecked (update existing — will not create duplicates). Failed checks can be selected again.</p>'
                : '';
            container.innerHTML = note + Object.entries(data).map(([group, items]) =>
                '<div class="mt-1 offering-group" data-group="' + group + '"><strong class="small">' + group + '</strong>' +
                Object.entries(items).map(([code, label]) => {
                    var checked = '';
                    var suffix = '';
                    if (oldOfferings.size > 0) {
                        checked = oldOfferings.has(code) ? ' checked' : '';
                    } else if (previousOfferings.size > 0) {
                        // Retry: pre-check previous only when not already active on OnGrid
                        if (alreadyActive.has(code)) {
                            suffix = ' <span class="text-muted small">(already on OnGrid)</span>';
                        } else {
                            checked = previousOfferings.has(code) ? ' checked' : '';
                        }
                    } else {
                        checked = (defaultSet.size === 0 || defaultSet.has(code)) ? ' checked' : '';
                    }
                    return '<div class="ms-2 mb-1"><label><input type="checkbox" class="offering-check" name="offerings[]" value="' + code + '"' + checked + '> ' + label + suffix + '</label></div>';
                }).join('') + '</div>'
            ).join('');

            if (empvBgvDateContext) {
                var employer = empvBgvDateContext.employer_name || 'previous employer';
                var minAttr = empvBgvDateContext.allowed_min ? ' min="' + empvBgvDateContext.allowed_min + '"' : '';
                var maxAttr = empvBgvDateContext.allowed_max ? ' max="' + empvBgvDateContext.allowed_max + '"' : '';
                var valueAttr = empvBgvDateContext.default_date ? ' value="' + empvBgvDateContext.default_date + '"' : '';
                var savedNotice = '';
                if (empvBgvDateContext.has_saved_date) {
                    var schedNote = empvBgvDateContext.is_future_scheduled
                        ? ' <span class="badge badge-warning">Scheduled — other BGV checks start now; Employment Verification waits until this date</span>'
                        : '';
                    savedNotice =
                        '<div class="alert alert-info py-2 px-2 small mb-2" id="empv_saved_notice">' +
                        '<i class="dw dw-calendar-1"></i> Employment verification start date already set: ' +
                        '<strong>' + empvBgvDateContext.saved_display + '</strong>' + schedNote + '</div>';
                }
                container.insertAdjacentHTML('beforeend',
                    savedNotice +
                    '<div id="empv_start_date_wrap" class="mt-2 p-2 border rounded bg-light">' +
                    '<label for="empv_start_date" class="small font-weight-bold mb-1 d-block">' +
                    'Employment verification start date <span class="text-danger">*</span></label>' +
                    '<input type="date" name="empv_start_date" id="empv_start_date" class="form-control form-control-sm"' +
                    minAttr + maxAttr + valueAttr + '>' +
                    '<small class="text-muted d-block mt-1">For <strong>' + employer + '</strong>. ' +
                    'Defaults to candidate <strong>joining date</strong> at that employer when empty. ' +
                    'Allowed: up to <strong>' + (empvBgvDateContext.back_days || 45) + ' days</strong> in the past ' +
                    '(from ' + (empvBgvDateContext.min_display || '') + ') or up to <strong>' +
                    (empvBgvDateContext.forward_months || 3) + ' months</strong> ahead (until ' +
                    (empvBgvDateContext.max_display || '') + '). ' +
                    'OnGrid will not start employment verification before the date you select.</small></div>'
                );
            }

            container.querySelectorAll('.offering-check').forEach(function (el) {
                el.addEventListener('change', syncEmpvStartDateField);
            });
            syncEmpvStartDateField();

            var form = document.querySelector('#offeringModal form');
            if (form && !form.dataset.empvDateBound) {
                form.dataset.empvDateBound = '1';
                form.addEventListener('submit', function (e) {
                    var empvOn = !!document.querySelector('input[name="offerings[]"][value="EMPV"]:checked');
                    var dateInput = document.getElementById('empv_start_date');
                    if (empvOn && empvBgvDateContext && dateInput) {
                        if (!dateInput.value && empvBgvDateContext.joining_date) {
                            dateInput.value = empvBgvDateContext.joining_date;
                        }
                        if (dateInput.value) {
                            var v = dateInput.value;
                            var min = empvBgvDateContext.allowed_min;
                            var max = empvBgvDateContext.allowed_max;
                            if ((min && v < min) || (max && v > max)) {
                                e.preventDefault();
                                alert('Employment verification start date must be between ' +
                                    (empvBgvDateContext.min_display || min) + ' and ' +
                                    (empvBgvDateContext.max_display || max) + ' (45 days back or 3 months ahead).');
                                dateInput.focus();
                            }
                        }
                    }
                });
            }

            $('#offeringModal').modal('show');
        });
}
</script>
@endpush
@endsection
