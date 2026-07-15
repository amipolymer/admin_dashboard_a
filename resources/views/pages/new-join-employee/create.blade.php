@extends('layouts.app')

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
                        <li class="breadcrumb-item"><a href="#" class="text-primary">Add</a></li>
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
        <hr class="pb-2">

        @if (session('success'))
            <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif


        <form action="{{ route('EmployeeJoiner.Store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('post')
            <div class="row">
                {{-- Employee Name --}}
                <div class="col-md-3 form-group">
                    <input type="hidden" name="emp_hr_id" class="form-control" value="{{ Auth::user()->id }}">
                    <label>Name <sup class="text-danger">*</sup></label>
                    <input type="text" name="emp_name" class="form-control" value="{{ old('emp_name') }}" required>
                    @error('emp_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="col-md-3 form-group">
                    <label>E-Mail<sup class="text-danger">*</sup></label>
                    <input type="email" name="emp_email" class="form-control" value="{{ old('emp_email') }}" required>
                    @error('emp_email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="col-md-3 form-group">
                    <label>Phone No <sup class="text-danger">*</sup></label>
                    <input type="text" name="emp_phone" class="form-control" value="{{ old('emp_phone') }}" required>
                    @error('emp_phone')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                {{-- Phone --}}
                <div class="col-md-3 form-group">
                    <label>DOB <sup class="text-danger">*</sup></label>
                    <input type="date" name="emp_dob" class="form-control" value="{{ old('emp_dob') }}" required>
                    @error('emp_dob')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Department --}}
                <div class="col-md-3 form-group">
                    <label>Department <sup class="text-danger">*</sup></label>
                    <input type="text" name="emp_department" class="form-control" value="{{ old('emp_department') }}" required>
                    @error('emp_department')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                @include('pages.new-join-employee.partials.level-wise-grading-fields')

                {{-- Location --}}
                <div class="col-md-3 form-group">
                    <label>Location <sup class="text-danger">*</sup></label>
                    <input type="text" name="emp_location" class="form-control" value="{{ old('emp_location') }}">
                    @error('emp_location')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                {{-- Location --}}
                <div class="col-md-3 form-group">
                    <label>MRF No <sup class="text-danger">*</sup></label>
                    <input type="text" name="emp_mrf_no" class="form-control" value="{{ old('emp_mrf_no') }}">
                    @error('emp_mrf_no')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                {{-- Location --}}
                <div class="col-md-3 form-group">
                    <label>MRF File <sup class="text-danger">*</sup></label>
                    <input type="file" name="emp_mrf_file" accept="application/pdf" class="form-control"
                        value="{{ old('emp_mrf_file') }}">
                    @error('emp_mrf_file')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                {{-- Location --}}
                <div class="col-md-3 form-group">
                    <label>Interview Evaluation File <sup class="text-danger">*</sup></label>
                    <input type="file" name="emp_interview_evaluation_file" accept="application/pdf"
                        class="form-control" value="{{ old('emp_interview_evaluation_file') }}">
                    @error('emp_interview_evaluation_file')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                {{-- Location --}}
                <div class="col-md-3 form-group">
                    <label>CV File <sup class="text-danger">*</sup></label>
                    <input type="file" name="emp_cv_file" accept="application/pdf" class="form-control"
                        value="{{ old('emp_cv_file') }}">
                    @error('emp_cv_file')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Joining Date --}}
                <div class="col-md-3 form-group">
                    <label>Joining Date <sup class="text-danger">*</sup></label>
                    <input type="date" name="emp_date" class="form-control"
                        min="{{ now()->subWeeks(2)->format('Y-m-d') }}" value="{{ old('emp_date') }}">
                    @error('emp_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                {{-- emp_document_due_date --}}
                <div class="col-md-3 form-group">
                    <label>Due Date <sup class="text-danger">*</sup></label>
                    <input type="date" name="emp_document_due_date" class="form-control"
                        min="{{ now()->format('Y-m-d') }}"
                        value="{{ old('emp_document_due_date', now()->addDays(3)->format('Y-m-d')) }}">
                    @error('emp_document_due_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="col-md-3 form-group">
                    <label>Status</label>
                    <select name="emp_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @error('emp_status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Document Status (Readonly default) --}}
                <div class="col-md-3 form-group">
                    <label>Document Status</label>
                    <input type="text" class="form-control text-capitalize" value="process" readonly>
                </div>

                <div class="col-md-3 form-group">
                    <label>Employment type <sup class="text-danger">*</sup></label>
                    <select name="employment_type" class="form-control" required>
                        <option value="experienced" {{ old('employment_type', 'experienced') === 'experienced' ? 'selected' : '' }}>Experienced (prior employment)</option>
                        <option value="fresher" {{ old('employment_type') === 'fresher' ? 'selected' : '' }}>Fresher (no prior employment)</option>
                    </select>
                    <small class="text-muted">Controls previous employment fields in candidate portal.</small>
                    @error('employment_type')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>

            </div>

        </form>
    </div>
@endsection
