@extends('layouts.app')

@section('main_content')
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title"><h4>Route #{{ $route->id }}</h4></div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Routes.Index') }}">Route List</a></li>
                    <li class="breadcrumb-item active">Show</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ route('Log.Routes.Edit', $route->id) }}" class="btn btn-sm btn-primary">Edit</a>
            <a href="{{ route('Log.Routes.Index') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                <i class="dw dw-return1"></i> <span class="back_title">Back</span>
            </a>
        </div>
    </div>
    <hr class="pb-2">

    <table class="table table-bordered bg-white">
        <tr><th width="25%">ID</th><td>{{ $route->id }}</td></tr>
        <tr><th>URL name</th><td><code>{{ $route->url_name }}</code></td></tr>
        <tr><th>Title</th><td>{{ $route->title }}</td></tr>
        <tr><th>Created</th><td>{{ $route->created_at?->format('d M Y H:i:s') }}</td></tr>
        <tr><th>Updated</th><td>{{ $route->updated_at?->format('d M Y H:i:s') }}</td></tr>
        <tr>
            <th>Used by roles</th>
            <td>
                @if ($rolesUsing->isNotEmpty())
                    @foreach ($rolesUsing as $roleName)
                        <span class="badge badge-primary mr-1">{{ $roleName }}</span>
                    @endforeach
                @else
                    <span class="text-muted">Not assigned to any role yet.</span>
                @endif
            </td>
        </tr>
    </table>
</div>
@endsection
