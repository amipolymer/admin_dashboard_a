@extends('layouts.app')

@section('main_content')
<!-- <style>
    td.gridjs-td{
        text-align: left !important;
    }
</style> -->
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title">
                <h4>Route URL List</h4>
            </div>
            <nav aria-label="breadcrumb" role="navigation">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Routes.Index') }}" class="text-primary">Route List</a></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <form method="POST" action="{{ route('Log.Routes.Sync') }}" class="d-inline" onsubmit="return confirm('Import Laravel routes into the database? Only new paths are added; existing rows are kept.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-info">Sync from app</button>
            </form>
            <a href="{{ route('Log.Routes.Create') }}" class="add_button text-primary btn-sm border border-primary p-2 h4">+
                <span>New</span></a>
            <a href="{{ url('/dashboard') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                <i class="dw dw-return1"></i> <span class="back_title">Back</span>
            </a>
        </div>
    </div>
    <hr class="pb-2">

    @if (session('success'))
        <div class="alert alert-{{ session('bg-color', 'success') }} alert-dismissible fade show">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="alert alert-light border py-2 small mb-3">
        <strong>Sync from app</strong> saves <em>new</em> Laravel route paths into the database (existing rows are not overwritten).
        The grid below always shows <strong>every route stored in the database</strong> ({{ $routeList->count() }} total).
        Assign route IDs under <strong>Users → Users Role</strong> for admin access.
    </div>

    <div id="site-grid" class="text-capitalize text-left"></div>
</div>

@php
    $paginationLimit = env('TABLE_PAGINATION_LIMIT', 5);
    $csrf = csrf_token();

    $gridData = $routeList->map(function ($route, $index) use ($csrf) {
        $showUrl = route('Log.Routes.Show', $route->id);
        $editUrl = route('Log.Routes.Edit', $route->id);
        $deleteUrl = route('Log.Routes.Delete', $route->id);

        $actions = <<<HTML
            <div class="btn-group">
                <a href="{$editUrl}" data-toggle="tooltip" data-placement="top" data-original-title="Edit" class="btn btn-outline-primary btn-sm"><i class="dw dw-edit-1"></i></a>
                <!-- <form method="POST" action="{$deleteUrl}" class="d-inline" onsubmit="return confirm('Delete this route from the database?');">
                    <input type="hidden" name="_token" value="{$csrf}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" data-toggle="tooltip" data-placement="top" data-original-title="Delete" class="btn btn-outline-danger btn-sm"><i class="dw dw-delete-3"></i></button>
                </form> -->
            </div>
        HTML;

        return [
            e($route->id),
            e($route->url_name),
            e($route->title),
            e($route->updated_at?->format('d-m-Y H:i') ?? ''),
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
            { name: "ID", sort: true },
            {
                name: "URL name (path)",
                sort: true,
                formatter: cell => gridjs.html(`<code class="small" style="text-transform:none">${cell}</code>`)
            },
            {
                name: "Title",
                sort: true,
                formatter: cell => gridjs.html(`<span style="text-transform:none">${cell}</span>`)
            },
            { name: "Updated", sort: true },
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
