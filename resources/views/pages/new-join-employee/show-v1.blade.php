@extends('layouts.app')

@php
$assetPrefix = config('app.asset_prefix');
@endphp

@section('main_content')
    <div class="container-fluid">
        <div class="page-header">
            <div class="row pb-3 align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">New Join Employee</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ url('/master-entry/new-employee-list') }}">New Employee
                                    List</a></li>
                            <li class="breadcrumb-item active text-primary">Show</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-md-right mt-3 mt-md-0">
                    <a href="{{ url('/master-entry/new-employee-list') }}" class="btn btn-outline-danger btn-sm">
                        <i class="dw dw-return1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> Please fix the following issues:
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form id="employeeForm" action="{{ route('EmployeeJoiner.updateDocument', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Employee Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Employee Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Name</label>
                            <input type="text" readonly class="form-control"
                                value="{{ old('emp_name', $employee->emp_name) }}">
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" readonly class="form-control"
                                value="{{ old('emp_email', $employee->emp_email) }}">
                        </div>

                        {{-- <div class="col-md-2 form-group">
                            <label class="font-weight-bold">Emergency Contact</label>
                            <input type="text" readonly class="form-control"
                                value="{{ old('emergency_contact', $employee->emergency_contact) }}">
                        </div> --}}

                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Employee Status</label>
                            <select readonly class="form-control">
                                <option {{ $employee->emp_status == 'active' ? 'selected' : '' }}>Active</option>
                                <option {{ $employee->emp_status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option {{ $employee->emp_status == 'process' ? 'selected' : '' }} disabled>Process
                                <option {{ $employee->emp_status == 'completed' ? 'selected' : '' }} disabled>Completed
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Overall Document Status</label>
                            <select readonly name="emp_document_status" id="overall_doc_status" class="form-control">
                                <option value="process"
                                    {{ $employee->emp_document_status == 'process' ? 'selected' : '' }}>Process</option>
                                <option value="completed"
                                    {{ $employee->emp_document_status == 'completed' ? 'selected' : '' }}>Completed
                                </option>
                                <option value="rejected"
                                    {{ $employee->emp_document_status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uploaded Documents Table -->
            <div class="card shadow-sm mb-4 mb-md-5">
                <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                    <span>Uploaded Documents</span>
                    @if( $employee->emp_status == 'completed')
                    <a href="{{ route('EmployeeJoiner.documents.downloadAll', $employee->id) }}" class="d-md-end btn btn-outline-secondary btn-sm">Download All</a>
                    @endif
                </div>
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

                                        <td class="p-1 text-center">
                                            @if($doc->emp_select_document =='emergency_contact')

                                            <a href="tel:{{ $doc->emp_document_file }}" class="btn btn-sm btn-outline-primary disabled">
                                                View
                                            </a>

                                            @else
                                            <a href="{{ $main_url ?? '' }}{{ $assetPrefix }}{{ $doc->emp_document_file_path }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
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

                   
            <!-- Submit Button -->
            <div class="text-center mt-4 mt-md-5 mb-15">
                @if($employee->emp_status != 'completed')
                <button type="button" id="submitBtn" class="btn btn-primary btn-lg px-2 py-1">
                    <i class="dw dw-check me-2"></i> Save All Changes
                </button>
                @endif
            </div>

        </form>
    </div>

    <!-- Modal for Employee ID (shown only when all approved) -->
    <div class="modal fade" id="empIdModal" tabindex="-1" aria-labelledby="empIdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="empIdModalLabel">Enter Employee ID</h5>
                </div>
                <div class="modal-body">
                    <p>All documents are approved. Please enter the Employee ID to complete the process.</p>
                    <div class="mb-3">
                        <label for="manual_emp_id" class="form-label fw-bold">Employee ID</label>
                        <input type="text" class="form-control" id="manual_emp_id" name="manual_emp_id" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn_close" id="btn_close" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmSubmit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>

   @push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('employeeForm');
        const submitBtn = document.getElementById('submitBtn');
        const modal = new bootstrap.Modal(document.getElementById('empIdModal'));
        const statusSelects = document.querySelectorAll('.doc-status-select');

        // Hide button by default to prevent flash
        submitBtn.style.display = 'none';

        // Check if ALL documents are "Approved"
        function areAllApproved() {
            return Array.from(statusSelects).every(select => select.value === 'approved');
        }

        // Toggle Save button visibility
        function toggleSubmitButton() {
            if (areAllApproved()) {
                submitBtn.style.display = 'none';           // All approved → hide "Save" button
            } else {
                submitBtn.style.display = 'inline-block';   // Any Process or Rejected → show button
            }
        }

        // Handle each status dropdown change
        statusSelects.forEach(select => {
            const row = select.closest('tr');
            const remarkBox = row.querySelector('.remark-box');
            const remarkTextarea = remarkBox ? remarkBox.querySelector('textarea') : null;

            function handleStatusChange() {
                // Show remark box only when Rejected
                if (select.value === 'rejected') {
                    if (remarkBox) remarkBox.style.display = 'block';
                    if (remarkTextarea) remarkTextarea.required = true;
                } else {
                    if (remarkBox) remarkBox.style.display = 'none';
                    if (remarkTextarea) remarkTextarea.required = false;
                }

                // Update Save button visibility
                toggleSubmitButton();
            }

            select.addEventListener('change', handleStatusChange);
            handleStatusChange(); // Initial call for each row
        });

        // Initial button state
        toggleSubmitButton();

        // Submit Button Click
        submitBtn.addEventListener('click', function () {
            if (areAllApproved()) {
                modal.show();
            } else {
                form.submit();
            }
        });

        // Modal Confirm (Enter Employee ID)
        document.getElementById('confirmSubmit').addEventListener('click', function () {
            const empId = document.getElementById('manual_emp_id').value.trim();

            if (empId === '') {
                alert('Please enter Employee ID');
                return;
            }

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'manual_emp_id';
            hiddenInput.value = empId;
            form.appendChild(hiddenInput);

            modal.hide();
            form.submit();
        });
    });
</script>
@endpush
@endsection
