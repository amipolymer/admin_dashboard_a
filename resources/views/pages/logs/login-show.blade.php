@extends('layouts.app')

@section('main_content')
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title"><h4>Login Log #{{ $log->id }}</h4></div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Login.Index') }}">User Login Logs</a></li>
                    <li class="breadcrumb-item active">Show</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ route('Log.Login.Index') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                <i class="dw dw-return1"></i> <span class="back_title">Back</span>
            </a>
        </div>
    </div>
    <hr class="pb-2">

    <div class="row">
        <div class="col-md-8">
            <table class="table table-bordered bg-white">
                <tr><th width="30%">Log ID</th><td>{{ $log->id }}</td></tr>
                <tr><th>User</th><td>{{ $log->user?->name ?? '—' }} (ID: {{ $log->user_id }})</td></tr>
                <tr><th>Email</th><td>{{ $log->user?->email ?? '—' }}</td></tr>
                <tr><th>Role</th><td>{{ $log->user?->role ?? '—' }}</td></tr>
                <tr><th>Login at</th><td>{{ $log->login_at?->format('d M Y H:i:s') }}</td></tr>
                <tr><th>Logout at</th><td>
                    @if ($log->logout_at)
                        {{ $log->logout_at->format('d M Y H:i:s') }}
                        <span class="text-muted small">({{ $log->login_at->diffForHumans($log->logout_at, true) }})</span>
                    @else
                        <span class="badge badge-success">Still active</span>
                    @endif
                </td></tr>
                <tr><th>Logout reason</th><td>{{ $log->logout_reason ?? '—' }}</td></tr>
                <tr><th>IP address</th><td>{{ $log->ip_address ?? '—' }}</td></tr>
                <tr><th>Browser</th><td style="word-break:break-all;">{{ $log->browser ?? '—' }}</td></tr>
                <tr><th>Platform</th><td>{{ $log->platform ?? '—' }}</td></tr>
                <tr><th>Device</th><td>{{ $log->device ?? '—' }}</td></tr>
                <tr><th>Location</th><td>{{ $log->location ?? '—' }}</td></tr>
                <tr><th>Created</th><td>{{ $log->created_at?->format('d M Y H:i:s') }}</td></tr>
                <tr><th>Updated</th><td>{{ $log->updated_at?->format('d M Y H:i:s') }}</td></tr>
            </table>
        </div>
    </div>
</div>
@endsection
