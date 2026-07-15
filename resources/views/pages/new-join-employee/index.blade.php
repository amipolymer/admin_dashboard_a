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
                        <li class="breadcrumb-item"><a href="{{ url('/onboard-assistant/new-employee-list/') }}" class="text-primary ">New Employee List</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
                <a href="{{ route('EmployeeJoiner.Create') }}" class="add_button text-primary btn-sm border border-primary p-2 h4">+
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
    use App\Support\OnboardingStepGate;

    $paginationLimit = env('TABLE_PAGINATION_LIMIT', 5); // Default fallback
    $csrf = csrf_token();
    $gridData = $employeesJoinerList->map(function ($emp, $index) {
    $editUrl   = route('EmployeeJoiner.Edit', $emp->id);
    $deleteUrl = route('EmployeeJoiner.Delete', $emp->id);
    $showUrl = route('EmployeeJoiner.Show', $emp->id);
    $bvgLink = route('EmployeeJoiner.documents.bvgLink', $emp->id);

    $bvgButton = '';

    if ($emp->emp_status === 'completed') {
    $bvgButton = <<<HTML
    <a href="{$bvgLink}" class="btn btn-outline-info btn-sm" title="Invite OnGrid">
        <i class="dw dw-link"></i>
    </a>
    HTML;
} else {
    $bvgButton = <<<HTML
    <a href="#" 
       class="btn btn-outline-info btn-sm disabled" 
       title="Available after completion"
       onclick="return false;" 
       style="pointer-events: none; opacity: 0.6;">
        <i class="dw dw-link"></i>
    </a>
    HTML;
}

    $actions = <<<HTML
        <div class="btn-group">
            
            <a href="{$showUrl}" class="btn btn-outline-success btn-sm" title="Show">
                <i class="dw dw-eye"></i>
            </a>
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
        e($emp->emp_name),
        e($emp->emp_email),
        e($emp->emp_phone),
        e($emp->emp_role),
        e($emp->emp_location),
        e($emp->user?->name ?? 'N/A'), // HR Name
        e(optional($emp->emp_date)->format('d-m-Y')),
        e(OnboardingStepGate::listOnboardingStage($emp->onboardingStep())),
        e(OnboardingStepGate::documentStatusLabel($emp->emp_document_status)),
        e(optional($emp->updated_at)->format('d-m-Y')),
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
        { name: "EMP Name", sort: true },
        { 
            name: "Email", 
            sort: true,
            formatter: cell => gridjs.html(`<span style="text-transform:none">${cell}</span>`)
        },
        { name: "Phone", sort: true },
        { name: "Designation", sort: true },
        { name: "Location", sort: true },
        { name: "HR", sort: true },
        { name: "JOD", sort: true },
        { name: "Onboarding Stage", sort: true },
        { name: "Doc. Status", sort: true },
        { name: "Last Updated", sort: true },
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
