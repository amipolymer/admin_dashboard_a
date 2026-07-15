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
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard/annual-report') }}" class="text-primary ">View Request</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
                <a href="{{ route('AnnualReportViewForm.export') }}" class="add_button text-primary btn-sm border border-primary p-2 h4">
                    Export</span></a>
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

$gridData = $annualReport->map(function ($emp, $index) {

    $editUrl   = route('AnnualReportViewForm.Edit', $emp->id);
    $deleteUrl = route('AnnualReportViewForm.Delete', $emp->id);
    $approved_by = $emp->approved->name ?? 'N/A';
    $actions = <<<HTML
        <div class="btn-group">
            <a href="{$editUrl}" class="btn btn-outline-primary btn-sm" title="Edit">
                <i class="dw dw-edit-1"></i>
            </a>
            <a href="{$deleteUrl}" class="btn btn-outline-danger btn-sm" title="Delete">
                <i class="dw dw-delete-3"></i>
            </a>
        </div>
    HTML;

    return [
        $index + 1,
        e($emp->full_name),
        e($emp->email),
        e($emp->mobile),
        e($emp->company_name),
        e($emp->department),
        e($emp->gst_no),
        e($emp->report_year),
        e($emp->created_at->format('Y-m-d')),
        e($emp->status),
        e($approved_by),
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
        { name: "#", sort: false },
        { name: "Full Name", sort: true },
        { 
            name: "Email", 
            sort: true,
            formatter: cell => gridjs.html(`<span style="text-transform:none">${cell}</span>`)
        },
        { name: "Phone", sort: true },
        { name: "Company Name", sort: true },
        { name: "Designation", sort: true },
        { name: "GST", sort: true },
        { name: "Report Year", sort: true },
        { name: "DOS", sort: true },
        { name: "Status", sort: true },
        { name: "Updated By", sort: true },
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
