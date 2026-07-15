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

    $documentNames = $documentNamesList;

    $uploadedDocs = $document_list->pluck('emp_select_document')->toArray();

    if ($employee->emergency_contact) {
        $uploadedDocs[] = 'emergency_contact';
    }

    $uploadfilestatus = 0;

@endphp


<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


<div class="page-header p-3">


    {{-- HEADER --}}
    <div class="row align-items-center mobile_view border-bottom pb-3">

        <div class="col-md-12 text-center">
       <img src="https://amipolymer.in/wp-content/uploads/2020/02/ami-polymers.png" class="img-fluid p-2 w-50 pb-5">

        </div>

        <div class="col-md-6">

            <h4 class="fs-5">
                Employee Onboarding – Document Submission
            </h4>

        </div>

        <div class="col-md-6 text-md-right emp_info">

            <div>

                <span class="text-primary">
                    {{ $employee->emp_name }}
                </span>

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



    {{-- OPTION BUTTONS --}}
    <div class="row justify-content-center mt-5 mb-4">


        {{-- UPDATE INFORMATION --}}
        <div class="col-md-3 col-6 mb-3">

            <div class="card text-center shadow-sm border"
                 id="infoBtn"
                 style="cursor:pointer;"
                 onclick="showSection('infoSection','infoBtn')">

                <div class="card-body py-4">

                    <i class="fa fa-user text-primary mb-2"
                       style="font-size:28px;"></i>

                    <h5 class="mb-0 font-weight-bold">
                        Update Information
                    </h5>

                </div>

            </div>

        </div>



        {{-- UPLOAD DOCUMENT --}}
        <div class="col-md-3 col-6 mb-3">

            <div class="card text-center shadow-sm border"
                 id="documentBtn"
                 style="cursor:pointer;"
                 onclick="showSection('documentSection','documentBtn')">

                <div class="card-body py-4">

                    <i class="fa fa-upload text-primary mb-2"
                       style="font-size:28px;"></i>

                    <h5 class="mb-0 font-weight-bold">
                        Upload Document
                    </h5>

                </div>

            </div>

        </div>

    </div>



    {{-- ALERTS --}}
    @if (session('success'))

        <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">

            <strong>Success!</strong>

            {{ session('success') }}

            <button type="button"
                    class="close"
                    data-dismiss="alert">

                &times;

            </button>

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




    {{-- UPDATE INFORMATION SECTION --}}
    <div id="infoSection" style="display:none;">

        <div class="card mb-4">

            <div class="card-header">

                <strong>
                    Employee Information
                </strong>

            </div>

            <div class="card-body">

                <form action=""
                      method="POST">

                    @csrf

                    <input type="hidden"
                           name="emp_id"
                           value="{{ $employee->id }}">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label>
                                Employee Name
                            </label>

                            <input type="text"
                                   name="emp_name"
                                   class="form-control"
                                   value="{{ $employee->emp_name }}">

                        </div>


                        <div class="col-md-6 mb-3">

                            <label>
                                Mobile Number
                            </label>

                            <input type="text"
                                   name="emp_mobile"
                                   class="form-control"
                                   value="{{ $employee->emp_mobile }}">

                        </div>



                        <div class="col-md-6 mb-3">

                            <label>
                                Email Address
                            </label>

                            <input type="email"
                                   name="emp_email"
                                   class="form-control"
                                   value="{{ $employee->emp_email }}">

                        </div>



                        <div class="col-md-6 mb-3">

                            <label>
                                City
                            </label>

                            <input type="text"
                                   name="emp_city"
                                   class="form-control"
                                   value="{{ $employee->emp_city }}">

                        </div>



                        <div class="col-md-12 mb-3">

                            <label>
                                Address
                            </label>

                            <textarea name="emp_address"
                                      rows="3"
                                      class="form-control">{{ $employee->emp_address }}</textarea>

                        </div>

                    </div>


                    <div class="text-right">

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="fa fa-save"></i>

                            Update Information

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>





    {{-- DOCUMENT SECTION --}}
    <div id="documentSection" style="display:none;">


        {{-- UPLOAD FORM --}}
        <form
            action="{{ $isEdit ? route('employee.documents.update', $editDocument->id) : route('employee.documents.upload') }}"
            method="POST"
            enctype="multipart/form-data">

            @csrf

            <div class="card mb-3">

                <div class="card-header">

                    <strong>

                        {{ $isEdit ? 'Re-upload Document' : 'Upload Document' }}

                    </strong>

                </div>

                <div class="card-body">

                    <div class="row align-items-end">

                        <input type="hidden"
                               name="emp_id"
                               value="{{ $employee->id }}">


                        {{-- DOCUMENT TYPE --}}
                        <div class="col-md-4">

                            <label>
                                Document Type
                            </label>

                            <select name="document_type"
                                    id="documentType"
                                    class="form-control">

                                <option value="">
                                    -- Select Document --
                                </option>

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

                        </div>



                        {{-- FILE --}}
                        <div class="col-md-4">

                            <label>
                                Select File
                            </label>

                            <input type="file"
                                   name="document_file"
                                   class="form-control">

                        </div>



                        {{-- BUTTON --}}
                        <div class="col-md-4">

                            <button type="submit"
                                    class="btn btn-primary btn-block">

                                <i class="fa fa-upload"></i>

                                Upload

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </form>





        {{-- DOCUMENT TABLE --}}
        <div class="card">

            <div class="card-header">

                <strong>
                    Uploaded Documents
                </strong>

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

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($document_list as $document)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td class="text-left">

                                    {{ collect($documentNames)->collapse()[$document->emp_select_document] ?? '' }}

                                </td>

                                <td class="text-left">

                                    {{ $document->emp_document_file }}

                                </td>

                                <td>

                                    <span class="badge badge-warning">

                                        {{ ucfirst($document->emp_document_status) }}

                                    </span>

                                </td>

                                <td>

                                    {{ $document->emp_doc_date }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5">
                                    No documents uploaded
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>



<script>

    function showSection(sectionId, buttonId)
    {
        document.getElementById('infoSection').style.display = 'none';

        document.getElementById('documentSection').style.display = 'none';

        document.getElementById(sectionId).style.display = 'block';

        document.getElementById('infoBtn')
            .classList.remove('border-primary');

        document.getElementById('documentBtn')
            .classList.remove('border-primary');

        document.getElementById(buttonId)
            .classList.add('border-primary');
    }

</script>

@endsection
