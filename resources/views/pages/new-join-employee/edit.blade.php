@extends('layouts.app')

@php
    use App\Support\OnboardingStepGate;
    $canResendPortal = OnboardingStepGate::canResendPortalLink($employee);
@endphp

@section('main_content')
    <div class="page-header">

        <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                    <h4>New Join Employee</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/onboard-assistant/new-employee-list/') }}">New Employee
                                List</a></li>
                        <li class="breadcrumb-item"><a href="#" class="text-primary">Edit</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
                <a href="{{ url('/onboard-assistant/new-employee-list/') }}"
                    class="back_button text-danger border btn-sm border-danger p-2 h4">
                    <i class="dw dw-return1"></i> <span class="back_title">Back</span>
                </a>
            </div>
        </div>
        <hr>

        @if (session('success'))
            <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif



        <form action="{{ route('EmployeeJoiner.Update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">

                {{-- Name --}}
                <div class="col-md-3 form-group">
                    <label>Name</label>
                    <input type="text" name="emp_name" class="form-control"
                        value="{{ old('emp_name', $employee->emp_name) }}">
                    @error('emp_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="col-md-3 form-group">
                    <label>Email</label>
                    <input type="email" name="emp_email" class="form-control"
                        value="{{ old('emp_email', $employee->emp_email) }}">
                    @error('emp_email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="col-md-3 form-group">
                    <label>Phone</label>
                    <input type="text" name="emp_phone" class="form-control"
                        value="{{ old('emp_phone', $employee->emp_phone) }}">
                    @error('emp_phone')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Designation & grading --}}
                @include('pages.new-join-employee.partials.level-wise-grading-fields', ['employee' => $employee])

                {{-- Location --}}
                <div class="col-md-3 form-group">
                    <label>Location</label>
                    <input type="text" name="emp_location" class="form-control"
                        value="{{ old('emp_location', $employee->emp_location) }}">
                    @error('emp_location')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Joining Date --}}
                <div class="col-md-3 form-group">
                    <label>Joining Date</label>
                    <input type="date" name="emp_date" class="form-control"
                        min="{{ now()->subWeeks(3)->format('Y-m-d') }}"
                        value="{{ old('emp_date', optional($employee->emp_date)->format('Y-m-d')) }}">
                    @error('emp_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- emp_document_due_date --}}
                <div class="col-md-3 form-group">
                    <label>Due Date</label>
                    <input type="date" name="emp_document_due_date" class="form-control"
                        value="{{ old('emp_document_due_date', optional($employee->emp_document_due_date)->format('Y-m-d')) }}">
                    @error('emp_document_due_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- emp_document_due_date --}}
                {{-- <div class="col-md-2 form-group">
                    <label>Emergency Contact</label>
                    <input readonly type="text" name="emergency_contact" class="form-control"
                        value="{{ old('emergency_contact', $employee->emergency_contact) }}">
                    @error('emergency_contact')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div> --}}

                {{-- Status --}}
                <div class="col-md-3 form-group">
                    <label>Status</label>
                    <select name="emp_status" class="form-control">
                        <option value="active" {{ $employee->emp_status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $employee->emp_status == 'inactive' ? 'selected' : '' }}>Inactive
                        </option>
                        <option value="process" disabled {{ $employee->emp_status == 'process' ? 'selected' : '' }}>Process
                        </option>
                    </select>
                    @error('emp_status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Document Status --}}
                {{-- <div class="col-md-2 form-group">
                    <label>Document Status</label>
                    <select name="emp_document_status" id="doc_status" class="form-control">
                        <option value="process" {{ $employee->emp_document_status == 'process' ? 'selected' : '' }}>Process
                        </option>
                        <option value="completed" {{ $employee->emp_document_status == 'completed' ? 'selected' : '' }}>
                            Completed
                        </option>
                        <option value="rejected" {{ $employee->emp_document_status == 'rejected' ? 'selected' : '' }}>
                            Rejected
                        </option>
                    </select>
                </div> --}}

                <div class="col-md-3 form-group">
                    <label>Employment type</label>
                    @php $empType = ($employee->emp_other ?? [])['candidate_profile']['employment_type'] ?? 'experienced'; @endphp
                    <select name="employment_type" class="form-control">
                        <option value="experienced" {{ old('employment_type', $empType) === 'experienced' ? 'selected' : '' }}>Experienced</option>
                        <option value="fresher" {{ old('employment_type', $empType) === 'fresher' ? 'selected' : '' }}>Fresher</option>
                    </select>
                    <small class="text-muted">Portal employment section follows this setting.</small>
                </div>

                {{-- Remark box (only if rejected) --}}
                <div class="col-md-12 form-group" id="remarkBox" style="display:none">
                    {{-- style="display: {{ $employee->emp_document_status == 'rejected' ? 'block' : 'none' }}"> --}}
                    <div class="row">
                        <div class="col-md-8">
                            <label>Rejection Remark</label>
                            <textarea name="remark" class="form-control" placeholder="Enter rejection remark"></textarea>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Select Document</label>
                            <select class="form-control selectpicker" name="document_id[]" data-style="btn-outline-dark"
                                data-selected-text-format="count" multiple="" tabindex="-98">
                                @foreach ($document_list as $index => $quicklink)
                                    @if ($quicklink->emp_document_status == 'approved')
                                    @else
                                        <option value="{{ $quicklink->id }}">
                                            {{ $index + 1 }}-{{ $quicklink->emp_document_file }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>


                    </div>
                </div>

                <div class="col-md-12 text-center text-md-right mt-3">
                    @if ($canResendPortal)
                    <a href="{{ route('employee.documents.reSendEmail', [$employee->id, 'resend', 0]) }}"
                        class="btn btn-warning">
                        Resend Portal Link
                    </a>
                    @endif
                    <button type="submit" class="btn btn-primary" {{ $employee->emp_status == 'completed' ? 'disabled' : '' }}>Update</button>
                </div>

            </div>
        </form>

        {{-- Show old remarks --}}
        @if (!empty($employee->emp_other['rejections']))
            <hr>
            <div class="col-12 p-0">
                <h6 class="pb-2">Rejection History</h6>
                <ul class="list-group">
                    @foreach (array_reverse($employee->emp_other['rejections'], true) as $index => $rej)
                        @if ($loop->first)
                            <!-- Latest (first after reverse) -->
                            <li class="list-group-item">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-bold">
                                        #{{ $index + 1 }} | {{ $rej['date'] }}
                                    </div>

                                    <a href="{{ route('employee.documents.reSendEmail', [$employee->id, 'reupload', $index]) }}"
                                        class="btn btn-sm btn-warning rounded p-1">
                                        Resend Email
                                    </a>
                                </div>

                                <div class="small text-black">
                                    {{ $rej['remark'] }}
                                </div>
                            </li>
                        @else
                            <!-- Older (disabled resend) -->
                            <li class="list-group-item">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-bold">
                                        #{{ $index + 1 }} | {{ $rej['date'] }}
                                    </div>

                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning rounded p-1 disabled">
                                        Resend Email
                                    </a>
                                </div>

                                <div class="small text-black">
                                    {{ $rej['remark'] }}
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>


            </div>
        @endif


    </div>

@php
        $documentNames = $documentNamesList;
        $assetPrefix = config('app.asset_prefix');
    @endphp

    @push('js')
        <script>
            document.getElementById('doc_status').addEventListener('change', function() {
                const remarkBox = document.getElementById('remarkBox');
                const selectDocument = remarkBox.querySelector('select[name="quick_link_id[]"]');

                if (this.value === 'rejected') {
                    remarkBox.style.display = 'block';
                    selectDocument.setAttribute('required', 'required');
                } else {
                    remarkBox.style.display = 'none';
                    selectDocument.removeAttribute('required');
                }
            });
        </script>
        <script>
            $(document).ready(function() {
                setTimeout(function() {
                    $('.selectpicker').selectpicker('refresh');
                }, 100);
            });
        </script>
    @endpush
@endsection
