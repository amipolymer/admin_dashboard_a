@extends('layouts.app')

@section('main_content')
<div class="page-header">
        <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                  <h4>User Role Details</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard/users-roles') }}" class="text-primary ">User Role List</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
                <a href="{{ route('UersRole.Create') }}" class="add_button text-primary btn-sm border border-primary p-2 h4">+
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
    $gridData = $roleList->map(function ($labourRole, $index) use ($csrf) {
        $editUrl = route('UersRole.Edit', $labourRole->id);
        $deleteUrl = route('UersRole.Delete', $labourRole->id);
        
           // Decode the url_ids (assuming it's a JSON string)
        $urlIds = json_decode($labourRole->url_ids);

        // Fetch titles based on the URL ids
        $titles = \App\Models\RouteURLList::whereIn('id', $urlIds)->pluck('title')->toArray();

        // Join the titles into a comma-separated string
        $titleString = implode(', ', $titles);

        $actions = <<<HTML
            <div class="btn-group">
                <a href="{$editUrl}" data-toggle="tooltip" data-placement="top" data-original-title="Edit" class="btn btn-outline-primary btn-sm"><i class="dw dw-edit-1"></i></a>
                <a href="{$deleteUrl}" data-toggle="tooltip" data-placement="top" data-original-title="Edit" class="btn btn-outline-danger btn-sm"><i class="dw dw-delete-3"></i></a>
            </div>
        HTML;
        return [
            e($index + 1),
            e($labourRole->role_name),
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
            // e($titleString), 
            { name: "ID", sort: false },
            { name: "Name", sort: true },
            // { name: "Pages", sort: true },
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
