@extends('layouts.app')

@section('main_content')
    <div class="page-header">

        <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                    <h4>Annual Report View Request</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/master-entry/new-employee-list') }}">View Request</a>
                        </li>
                        <li class="breadcrumb-item"><a href="#" class="text-primary">Edit</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
                <a href="{{ url('/annual-report/view-form') }}"
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



        <form action="{{ route('AnnualReportViewForm.Update', $annualReport->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">

                {{-- Full Name --}}
                <div class="col-md-4 form-group">
                    <label>Full Name</label>
                    <input readonly type="text" name="full_name" class="form-control"
                        value="{{ old('full_name', $annualReport->full_name) }}" maxlength="100">
                    @error('full_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="col-md-4 form-group">
                    <label>Email</label>
                    <input readonly type="email" name="email" class="form-control"
                        value="{{ old('email', $annualReport->email) }}" maxlength="100">
                    @error('email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Company Name --}}
                <div class="col-md-4 form-group">
                    <label>Company Name</label>
                    <input readonly type="text" name="company_name" class="form-control"
                        value="{{ old('company_name', $annualReport->company_name) }}" maxlength="100">
                    @error('company_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Mobile --}}
                <div class="col-md-2 form-group">
                    <label>Mobile</label>
                    <input readonly type="text" name="mobile" class="form-control"
                        value="{{ old('mobile', $annualReport->mobile) }}" maxlength="15">
                    @error('mobile')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                {{-- GST No --}}
                <div class="col-md-2 form-group">
                    <label>GST No</label>
                    <input readonly type="text" name="gst_no" class="form-control"
                        value="{{ old('gst_no', $annualReport->gst_no) }}" maxlength="15">
                    @error('gst_no')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Department --}}
                <div class="col-md-4 form-group">
                    <label>Department</label>
                    <input readonly type="text" name="department" class="form-control"
                        value="{{ old('department', $annualReport->department) }}" maxlength="50">
                    @error('department')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Location --}}
                <div class="col-md-4 form-group">
                    <label>Location</label>
                    <input readonly type="text" name="location" class="form-control"
                        value="{{ old('location', $annualReport->location) }}" maxlength="100">
                    @error('location')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Report Year --}}
                <div class="col-md-2 form-group">
                    <label>Report Year</label>
                    <input readonly type="text" name="report_year" class="form-control"
                        value="{{ old('report_year', $annualReport->report_year ?? '2025-2026') }}">
                    @error('report_year')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- View Date --}}
                <div class="col-md-2 form-group">
                    <label>View Date</label>
                    <input readonly type="datetime-local" name="view_date" class="form-control"
                        value="{{ old('view_date', $annualReport->view_date ? \Carbon\Carbon::parse($annualReport->view_date)->format('Y-m-d\TH:i:s') : '') }}">
                    @error('view_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Viewed By --}}
                <div class="col-md-2 form-group">
                    <label>Viewed By</label>
                    <input readonly type="text" name="viewed_by" class="form-control"
                        value="{{ old('viewed_by', $annualReport->viewer->name ?? '') }}">
                    @error('viewed_by')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="col-md-2 form-group">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control">
                        <option disabled value="process" {{ $annualReport->status == 'process' ? 'selected' : '' }}>Process
                        </option>
                        <option value="approved" {{ $annualReport->status == 'approved' ? 'selected' : '' }}>Approved
                        </option>
                        <option value="reject" {{ $annualReport->status == 'reject' ? 'selected' : '' }}>Reject</option>
                    </select>
                    @error('status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Approved By --}}
                {{-- <div class="col-md-2 form-group">
                    <label>Approved / Rejected By</label>
                    <input readonly type="text" name="approved_by" class="form-control"
                        value="{{ old('approved_by', $annualReport->approved->name ?? '') }}">
                    @error('approved_by')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="col-md-2 form-group">
                    <label>Approve / Reject Date</label>
                    <input readonly type="datetime-local" name="approve_disapprove_date" class="form-control"
                        value="{{ old('approve_disapprove_date', $annualReport->approve_disapprove_date ? \Carbon\Carbon::parse($annualReport->approve_disapprove_date)->format('Y-m-d\TH:i:s') : '') }}">
                    @error('approve_disapprove_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div> --}}

                {{-- Remark --}}
                <div class="col-md-4 form-group" id="remarkRequired">
                    <label>Remark
                        <span class="text-danger">*</span> <sup class="text-danger"> ( required if status is reject/approval )</sup>
                    </label>
                    <textarea required name="remark" id="remark" class="form-control"></textarea>
                    @error('remark')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="col-md-12 text-right mt-3">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                @if (!empty($annualReport->remark))
                <div class="col-md-12 pb-5">
          Remark List:

@if (!empty($annualReport->remark))
    <div class="table-responsive mt-2">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr class="bg-dark text-white text-center">
                    <th class="p-1" style="width: 5%;">#</th>
                    <th class="p-1 text-left" style="width: 40%;">Remark</th>
                    <th class="p-1" style="width: 20%;">Date & Time</th>
                    <th class="p-1" style="width: 20%;">User</th>
                    <th class="p-1" style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($annualReport->remark as $index => $remark)
                    <tr class="text-center">
                        <td class="p-1">{{ $index + 1 }}</td>
                        <td class="p-1 text-left">{{ $remark['remark'] }}</td>
                        <td class="p-1">
                            {{ \Carbon\Carbon::parse($remark['datetime'])->format('d M y H:i:s') }}
                        </td>
                       <td class="p-1" >
                            {{ $remark['added_by'] 
                                ? \App\Models\User::find($remark['added_by'])->name 
                                : 'Unknown' }}
                        </td>
                        <td class="p-1">
                            @if($remark['status'] == 'approved')
                                <span style="font-size:13px" class="badge text-success">
                                    {{ ucfirst($remark['status']) }}
                                </span>
                            @else
                                <span style="font-size:13px" class="badge text-danger">
                                    {{ ucfirst($remark['status']) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
                </div>
                @endif
        </form>
    </div>

    @php
        $assetPrefix = config('app.asset_prefix');
    @endphp

    @push('js')
       
        

        {{-- <script>
            document.addEventListener('DOMContentLoaded', function() {

                const statusField = document.getElementById('status');
                const remarkField = document.getElementById('remark');
                const remarkStar = document.getElementById('remarkRequired');

                function toggleRemarkRequirement() {
                    if (statusField.value === 'reject') {
                        remarkField.setAttribute('required', 'required');
                        remarkStar.style.display = 'inline';
                    } else {
                        remarkField.removeAttribute('required');
                        remarkStar.style.display = 'none';
                        remarkField.value = '';
                    }
                }

                // Run on page load
                toggleRemarkRequirement();

                // Run on change
                statusField.addEventListener('change', toggleRemarkRequirement);

            });
        </script> --}}
    @endpush
@endsection
