@extends('layouts.frontend-app')

@section('main_content')
    <style>
        .btn-primary {
            background-color: #034ea1;
            border-color: #034ea1;
        }

        .btn-outline-primary {
            border-color: #034ea1;
            color: #034ea1
        }

        .text-primary {
            color: #034ea1 !important;
            font-weight: bold;
        }

        .tablehead {
            background-color: #4672a3 !important;
            color: #fff !important;
        }
    </style>

    @php
        $isEdit = isset($editDocument);
$assetPrefix = config('app.asset_prefix');

    @endphp

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <div class="page-header p-3">

        @php
            $documentNames = $documentNamesList;
            $uploadedDocs = $document_list->pluck('emp_select_document')->toArray();

            if ($employee->emergency_contact) {
                $uploadedDocs[] = 'emergency_contact';
            }

            $uploadfilestatus = 0;
        @endphp

        {{-- HEADER --}}
        <div class="row align-items-center mobile_view">
            <div class="col-md-12 text-center border-bottom">
                <img src="https://amipolymer.in/wp-content/uploads/2020/02/ami-polymers.png" class="img-fluid p-2 w-50 pb-5">
            </div>

            <div class="col-md-6">
                <h4 class="fs-5">Employee Onboarding – Document Submission</h4>
            </div>

            <div class="col-md-6 text-md-right emp_info">
                <div>
                    <span class="text-primary">{{ $employee->emp_name }}</span>
                    <strong>: Name</strong>
                </div>
                <div>
                    <span class="text-danger font-weight-bold">
                        {{ $employee->emp_document_due_date->format('d-M-Y') }}
                    </span>
                    <strong>: Last Due Date</strong>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- UPLOAD FORM --}}
        <form
            action="{{ $isEdit ? route('employee.documents.update', $editDocument->id) : route('employee.documents.upload') }}"
            method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card mb-3">
                <div class="card-header">
                    <strong>{{ $isEdit ? 'Re-upload Document' : 'Upload Document' }}</strong>

                    @if ($isEdit)
                        <a href="{{ $main_url . 'Mph6MSf9L4NU4NWRRuxTYAvZM/' . $employee->emp_url }}"
                            class="btn btn-sm btn-primary float-right">
                            Back
                        </a>
                    @endif
                </div>

                <div class="card-body">
                    <div class="row align-items-end">

                        <input type="hidden" name="emp_id" value="{{ $employee->id }}">

                        {{-- DOCUMENT TYPE --}}
                        <div class="col-md-4">
                            <label>Document Type</label>

                            @if ($isEdit)
                                <input type="hidden" id="documentTypeEdit"
                                    value="{{ $editDocument->emp_select_document }}">

                                <input type="hidden" name="document_type"
                                    value="{{ $editDocument->emp_select_document }}">

                                <input type="text" class="form-control" disabled
                                    value="{{ collect($documentNames)->collapse()->get($editDocument->emp_select_document) }}">
                            @else
                                <select name="document_type" id="documentType" class="form-control">
                                    <option value="">-- Select Document --</option>
                                    @foreach ($documentNames as $group => $docs)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($docs as $key => $label)
                                                <option value="{{ $key }}"
                                                    {{ in_array($key, $uploadedDocs) ? 'disabled' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        {{-- FILE / EMERGENCY CONTACT --}}
                        <div class="col-md-4">
                            <div id="emergency_contact" style="display:none">
                                <label>Emergency Contact <span class="text-danger">*</span></label>
                                <input type="text" name="emergency_contact" class="form-control"
                                    value="{{ $editDocument->emp_document_file ?? '' }}">
                            </div>

                            <div id="document_file">
                                <label>Select File <span class="text-danger">*</span></label>
                                <input type="file" name="document_file" class="form-control">
                            </div>
                        </div>

                        {{-- BUTTON --}}
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-block py-2">
                                <i class="fa fa-upload"></i>
                                {{ $isEdit ? 'Re-upload' : 'Upload' }}
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </form>

        {{-- DOCUMENT TABLE --}}
        <div class="card">
            <div class="card-header">
                <strong>Uploaded Documents</strong>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0 text-center">
                    <thead class="tablehead">
                        <tr>
                            <th>Sr.</th>
                            <th class="text-left">Document</th>
                            <th class="text-left">File Name</th>
                            <th>Status</th>
                            <th>Uploaded On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($document_list as $document)
                            @if ($document->emp_document_status != 'approved')
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-left">
                                        {{ collect($documentNames)->collapse()[$document->emp_select_document] ?? '' }}
                                    </td>
                                    <td class="text-left">{{ $document->emp_document_file }}</td>
                                    <td>
                                        @if ($document->emp_document_status == 'rejected')
                                            <span class="badge badge-danger">Rejected</span>
                                            @php $uploadfilestatus = 1 @endphp
                                        @elseif ($document->emp_document_status == 'process')
                                            <span class="badge badge-warning">Process</span>
                                            @php $uploadfilestatus = 1 @endphp
                                        @else
                                            <span class="badge badge-info">Upload</span>
                                            @php $uploadfilestatus = 1 @endphp
                                        @endif
                                    </td>
                                    <td>{{ $document->emp_doc_date }}</td>
                                    <td>
                                        @php $file_name = encrypt($document->emp_document_file); @endphp

                                        <a href="{{ $main_url . $assetPrefix . 'Mph6MSf9L4NU4NWRRuxTYAvZM/' . $employee->emp_url . '/' . $file_name }}"
                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>

                                        @if (in_array($document->emp_document_status, ['rejected', 'upload']))
                                            <a href="{{ route('employee.documents.edit', [
                                                'url' => $employee->emp_url,
                                                'id' => encrypt($document->id),
                                            ]) }}"
                                                class="btn btn-sm btn-outline-danger">
                                                Re-upload
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-outline-danger" disabled>
                                                Re-upload
                                            </button>
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

            @if ($uploadfilestatus == 1)
                <div class="text-right p-2">
                    <form action="{{ route('employee.documents.Submited', $employee->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-primary"
                            onclick="return confirm('Are you sure all required documents are uploaded?')">
                            Confirm & Submit
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- JS --}}
    <script>
        function toggleFields(value) {
            const emergency = document.getElementById('emergency_contact');
            const file = document.getElementById('document_file');

            if (value === 'emergency_contact') {
                emergency.style.display = 'block';
                file.style.display = 'none';
            } else {
                emergency.style.display = 'none';
                file.style.display = 'block';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('documentType');
            const editValue = document.getElementById('documentTypeEdit');

            if (select) {
                toggleFields(select.value);
                select.addEventListener('change', () => toggleFields(select.value));
            }

            if (editValue) {
                toggleFields(editValue.value);
            }
        });
    </script>
@endsection
