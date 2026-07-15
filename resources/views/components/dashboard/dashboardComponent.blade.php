<div class="row p-2">
    <a href="/" class="col-6 col-md-3 mb-3 pr-0">
        <div class="card-box card_1 widget-style2 text-center">
            <div class="widget-data">
                <div class="h3 mb-0">{{ $employeeList->count() }}</div>
                <div class="weight-600 dashboard_tite font-15">Employee List</div>
            </div>
        </div>
    </a>

    <a href="/" class="col-6 col-md-3 mb-3">
        <div class="card-box card_2 widget-style2 text-center">
            <div class="widget-data">
                <div class="h3 mb-0">{{ $employeeList->where('status', 'active')->count() }}</div>
                <div class="weight-600 dashboard_tite font-15">Active</div>
            </div>
        </div>
    </a>

    <a href="/" class="col-6 col-md-3 mb-3 pr-0">
        <div class="card-box card_3 widget-style2 text-center">
            <div class="widget-data">
                <div class="h3 mb-0">{{ $employeeList->where('status', 'deactivate')->count() }}</div>
                <div class="weight-600 dashboard_tite font-15">Deactivate</div>
            </div>
        </div>
    </a>

</div>

<style>
    .card_1 { background-color: #8bf8ae75; }
    .card_2 { background-color: #7dccf18f; }
    .card_3 { background-color: #97f893; }
    .card_4 { background-color: #ffc1078a; }
    .card_5 { background-color: #fbc0bc; }
    .card_6 { background-color: #a7eee7; }
</style>
