@extends('layouts.app')

@section('main_content')
<div class="page-header">

    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title">
                <h4>New Annual Report View Request</h4>
            </div>
            <nav aria-label="breadcrumb" role="navigation">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/annual-report-view') }}">View Requests</a></li>
                    <li class="breadcrumb-item active text-primary" aria-current="page">Add</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ url('/annual-report-view') }}"
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

    <form action="{{ route('AnnualReportViewForm.Store') }}" method="POST">
        @csrf

        <div class="row">

            {{-- Full Name --}}
            <div class="col-md-4 form-group">
                <label>Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" maxlength="100" required>
                @error('full_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Email --}}
            <div class="col-md-4 form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="100" required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Company Name --}}
            <div class="col-md-4 form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" maxlength="100">
                @error('company_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Mobile --}}
            <div class="col-md-2 form-group">
                <label>Mobile</label>
                <input type="text" name="mobile" class="form-control" value="{{ old('mobile') }}" maxlength="15">
                @error('mobile')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- GST No --}}
            <div class="col-md-2 form-group">
                <label>GST No</label>
                <input type="text" name="gst_no" class="form-control" value="{{ old('gst_no') }}" maxlength="15">
                @error('gst_no')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Department --}}
            <div class="col-md-4 form-group">
                <label>Department</label>
                <input type="text" name="department" class="form-control" value="{{ old('department') }}" maxlength="50">
                @error('department')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Location --}}
            <div class="col-md-4 form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}" maxlength="100">
                @error('location')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Report Year --}}
            <div class="col-md-2 form-group">
                <label>Report Year</label>
                <input type="text" name="report_year" class="form-control" 
                       value="{{ old('report_year', '2025-2026') }}">
                @error('report_year')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

        


            {{-- Submit --}}
            <div class="col-md-12 text-right mt-3">
                <button type="submit" class="btn btn-primary">Add Request</button>
            </div>

        </div>
    </form>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusField = document.getElementById('status');
        const remarkField = document.getElementById('remark');
        const remarkWrapper = document.getElementById('remarkRequired');

        function toggleRemark() {
            if (statusField.value === 'reject') {
                remarkField.setAttribute('required', 'required');
                remarkWrapper.style.display = 'block';
            } else {
                remarkField.removeAttribute('required');
                remarkWrapper.style.display = 'block'; // still visible but optional
            }
        }

        toggleRemark();
        statusField.addEventListener('change', toggleRemark);
    });
</script>
@endpush

@endsection