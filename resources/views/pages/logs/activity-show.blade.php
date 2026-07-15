@extends('layouts.app')

@section('main_content')
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title"><h4>Activity Log #{{ $log->id }}</h4></div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Activity.Index') }}">Active Logs</a></li>
                    <li class="breadcrumb-item active">Show</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ route('Log.Activity.Index') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                <i class="dw dw-return1"></i> <span class="back_title">Back</span>
            </a>
        </div>
    </div>
    <hr class="pb-2">

    <div class="row mb-3">
        <div class="col-md-6">
            <table class="table table-sm table-bordered bg-white">
                <tr><th width="35%">Log ID</th><td>{{ $log->id }}</td></tr>
                <tr><th>Created</th><td>{{ $log->created_at?->format('d M Y H:i:s') }}</td></tr>
                <tr><th>Action</th><td><strong>{{ $log->action }}</strong></td></tr>
                <tr><th>Status</th><td>{{ $log->status }}</td></tr>
                <tr><th>Model</th><td><code>{{ $log->model }}</code></td></tr>
                <tr><th>Model ID</th><td>{{ $log->model_id }}</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-sm table-bordered bg-white">
                <tr><th width="35%">Performed by</th><td>{{ $log->user?->name ?? '—' }} @if($log->user) ({{ $log->user->email }}) @endif</td></tr>
                <tr><th>User ID</th><td>{{ $log->performed_by ?? '—' }}</td></tr>
                <tr><th>IP</th><td>{{ $log->ip_address ?? '—' }}</td></tr>
                <tr><th>Browser</th><td>{{ $log->browser ?? '—' }}</td></tr>
                <tr><th>Platform</th><td>{{ $log->platform ?? '—' }}</td></tr>
                <tr><th>Device</th><td>{{ $log->device ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    @include('pages.logs.partials.json-block', ['title' => 'Old data (JSON)', 'data' => $log->old_data, 'id' => 'old'])
    @include('pages.logs.partials.json-block', ['title' => 'New data (JSON)', 'data' => $log->new_data, 'id' => 'new'])

    <button type="button" class="btn btn-secondary btn-sm" id="showAllJson">Show all JSON</button>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('.toggle-json').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-target'));
        if (target) {
            target.style.display = target.style.display === 'none' ? 'block' : 'none';
        }
    });
});
document.getElementById('showAllJson')?.addEventListener('click', function () {
    document.querySelectorAll('.json-preview').forEach(function (el) {
        el.style.display = 'block';
    });
});
</script>
@endpush
