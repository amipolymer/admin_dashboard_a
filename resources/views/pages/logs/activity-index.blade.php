@extends('layouts.app')

@section('main_content')
@include('pages.logs.partials.log-list-styles')

<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title"><h4>Activity Logs</h4></div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Activity.Index') }}">Logs</a></li>
                    <li class="breadcrumb-item active">Active Logs</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ url('/dashboard') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                <i class="dw dw-return1"></i> <span class="back_title">Back</span>
            </a>
        </div>
    </div>
    <hr class="pb-2">

    <div class="alert alert-info py-2 small mb-3">
        Showing logs from the <strong>last 6 months</strong> only
        ({{ $since->format('d M Y') }} – {{ now()->format('d M Y') }}).
    </div>

    <form method="GET" class="mb-3 small bg-light border rounded p-3">
        <div class="row">
            <div class="col-md-2 form-group mb-2">
                <label class="font-weight-bold">Action</label>
                <select name="action" class="form-control form-control-sm">
                    <option value="">All</option>
                    @foreach (['create', 'update', 'delete'] as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group mb-2">
                <label class="font-weight-bold">Model</label>
                <select name="model" class="form-control form-control-sm">
                    <option value="">All models</option>
                    @foreach ($modelOptions as $modelClass)
                        <option value="{{ $modelClass }}" {{ request('model') === $modelClass ? 'selected' : '' }}>
                            {{ class_basename($modelClass) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group mb-2">
                <label class="font-weight-bold">Performed by</label>
                <select name="performed_by" class="form-control form-control-sm">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ (string) request('performed_by') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group mb-2">
                <label class="font-weight-bold">From</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}"
                    min="{{ $logsFromDate }}" max="{{ $logsToDate }}">
            </div>
            <div class="col-md-2 form-group mb-2">
                <label class="font-weight-bold">To</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}"
                    min="{{ $logsFromDate }}" max="{{ $logsToDate }}">
            </div>
            <div class="col-md-2 form-group mb-2">
                <label class="font-weight-bold">Per page</label>
                <select name="per_page" class="form-control form-control-sm">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ (int) ($perPage ?? 25) === $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-10 form-group mb-0 d-flex align-items-end flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply filter</button>
                <a href="{{ route('Log.Activity.Index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm bg-white">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Date / Time</th>
                    <th>Model</th>
                    <th>Record ID</th>
                    <th>Action</th>
                    <th>Performed by</th>
                    <th>IP</th>
                    <th>Status</th>
                    <th>JSON preview</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $modelShort = class_basename($log->model);
                        $jsonPreview = $log->new_data ?? $log->old_data ?? [];
                        $previewText = is_array($jsonPreview)
                            ? \Illuminate\Support\Str::limit(json_encode($jsonPreview, JSON_UNESCAPED_UNICODE), 80)
                            : '—';
                    @endphp
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td class="text-nowrap">{{ $log->created_at?->format('d-m-Y H:i') }}</td>
                        <td title="{{ $log->model }}">{{ $modelShort }}</td>
                        <td>{{ $log->model_id }}</td>
                        <td><span class="badge badge-{{ $log->action === 'delete' ? 'danger' : ($log->action === 'create' ? 'success' : 'info') }}">{{ $log->action }}</span></td>
                        <td>{{ $log->user?->name ?? ($log->performed_by ? 'User #' . $log->performed_by : '—') }}</td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td>{{ $log->status }}</td>
                        <td><code class="small">{{ $previewText }}</code></td>
                        <td class="text-nowrap">
                            <a href="{{ route('Log.Activity.Show', $log->id) }}" class="btn btn-outline-primary btn-sm">Show</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No activity logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('pages.logs.partials.pagination-bar', ['paginator' => $logs])
</div>
@endsection
