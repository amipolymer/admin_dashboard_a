@extends('layouts.app')

@section('main_content')
<div class="page-header">
        <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                    <h4>Employee Details</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard/employee-list') }}" class="text-primary ">Employee List</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
                <a href="{{ route('Users.Create') }}" class="add_button text-primary btn-sm border border-primary p-2 h4">+
                    New</span></a>
                <a href="{{ url('/dashboard') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                    <i class="dw dw-return1"></i> <span class="back_title">Back</span>
                </a>
            </div>
        </div>
    <hr class="pb-2">

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div id="site-grid" class="text-capitalize"></div>
</div>

@php
    $paginationLimit = env('TABLE_PAGINATION_LIMIT', 5); // Default fallback
    $csrf = csrf_token();

    // <a href="{$deleteUrl}" class="btn btn-danger btn-sm"><i class="dw dw-delete-3"></i></a>
    $gridData = $userList->map(function ($user, $index) use ($csrf) {
        $editUrl = route('Users.Edit', $user->id);
        $deleteUrl = route('Users.Delete', $user->id);

        $actions = <<<HTML
            <div class="btn-group">
                <a href="{$editUrl}" data-toggle="tooltip" data-placement="top" data-original-title="Edit" class="btn btn-outline-primary btn-sm"><i class="dw dw-edit-1"></i></a>
                <a href="{$deleteUrl}" data-toggle="tooltip" data-placement="top" data-original-title="Delete" class="btn btn-outline-danger  btn-sm"><i class="dw dw-delete-3"></i></a>
            </div>
        HTML;

        return [
            e($index + 1),
            e($user->emp_id),
            e($user->name),
            e($user->email),
            e($user->role),
            e($user->is_locked ?? '1' ? 'Yes' : 'No'),
            e($user->status),
            $actions,
        ];
    });
@endphp
@endsection
@php
$assetPrefix = config('app.asset_prefix').'/';
@endphp
@push('js')
<script src="{{ asset($assetPrefix.'assets/table/js/tablenew.js') }}"></script>
<script>
    new gridjs.Grid({
        columns: [
            { name: "ID", sort: false },
            { name: "EMP-ID", sort: true },
            { name: "Name", sort: true },
           { 
                name: "Email", 
                sort: true,
                formatter: cell => gridjs.html(`<span style="text-transform: none;">${cell}</span>`) 
            },
            { name: "Role", sort: true },
            { name: "Is Locked", sort: true },
            { name: "Status", sort: true },
            // { name: "Status", sort: true },
            {
                name: "Actions",
                sort: false,
                formatter: cell => gridjs.html(cell)
            }
        ],
        data: {!! json_encode($gridData) !!},
        search: true,
        pagination: {
            enabled: true,
            limit: {{ $paginationLimit }}
        },
        resizable: true
    }).render(document.getElementById("site-grid"));
</script>
@endpush
