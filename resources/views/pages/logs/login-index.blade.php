@extends('layouts.app')

@section('main_content')
@include('pages.logs.partials.log-list-styles')

<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title"><h4>User Login Logs</h4></div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Login.Index') }}">Logs</a></li>
                    <li class="breadcrumb-item active">User Login Logs</li>
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
            <div class="col-md-4 form-group mb-2">
                <label class="font-weight-bold">User</label>
                <select name="user_id" class="form-control form-control-sm">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
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
            <div class="col-md-4 form-group mb-0 d-flex align-items-end flex-wrap gap-2">
                <div class="form-check mr-3 mb-2">
                    <input type="checkbox" class="form-check-input" name="active_only" value="1" id="activeOnly" {{ request('active_only') ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeOnly">Active sessions only</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply filter</button>
                <a href="{{ route('Log.Login.Index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm bg-white">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Login at</th>
                    <th>Logout at</th>
                    <th>IP</th>
                    <th>Browser</th>
                    <th>Platform</th>
                    <th>Device</th>
                    <th>Logout reason</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->user?->name ?? 'User #' . $log->user_id }}<br><small class="text-muted">{{ $log->user?->email }}</small></td>
                        <td class="text-nowrap">{{ $log->login_at?->format('d-m-Y H:i:s') }}</td>
                        <td class="text-nowrap">
                            @if ($log->logout_at)
                                {{ $log->logout_at->format('d-m-Y H:i:s') }}
                            @else
                                <span class="badge badge-success">Active</span>
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($log->browser ?? '—', 30) }}</td>
                        <td>{{ $log->platform ?? '—' }}</td>
                        <td>{{ $log->device ?? '—' }}</td>
                        <td>{{ $log->logout_reason ?? '—' }}</td>
                        <td>
                            <a href="{{ route('Log.Login.Show', $log->id) }}" class="btn btn-outline-primary btn-sm">Show</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No login logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('pages.logs.partials.pagination-bar', ['paginator' => $logs])
</div>
@endsection
