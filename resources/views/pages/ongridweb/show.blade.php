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
                        <li class="breadcrumb-item"><a href="{{ route('EmployeeJoiner.ongridInviteList') }}">On Grid Invited list</a>
                        </li>
                        <li class="breadcrumb-item text-primary">Show</li>
                    </ol>
                </nav>
            </div>

            <div class="col-md-6 text-right col-right">
                <a href="{{ route('EmployeeJoiner.ongridInviteList') }}"
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


        @if (!empty($data))
            <div class="container mt-4">

                <div class="container mt-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0 text-white">Employee Details</h4>
                        </div>

                        <div class="card-body">
                            @include('components.dynamic-data', ['data' => $data])
                        </div>
                    </div>
                </div>

            </div>
        @else
            <p>No Data Found</p>
        @endif
    </div>
@endsection
