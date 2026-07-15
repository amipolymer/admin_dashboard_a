@extends('layouts.app')

@section('main_content')
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title"><h4>Add Route</h4></div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('Log.Routes.Index') }}">Route List</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ route('Log.Routes.Index') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                <i class="dw dw-return1"></i> <span class="back_title">Back</span>
            </a>
        </div>
    </div>
    <hr class="pb-2">

    <form action="{{ route('Log.Routes.Store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">URL name (path) *</label>
                <input type="text" name="url_name" class="form-control" value="{{ old('url_name') }}"
                    placeholder="master-entry/logs/route-url-list" required>
                <small class="text-muted">Must match request path (use {id} for numeric segments).</small>
                @error('url_name') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Title *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                    placeholder="Log-Routes-Index" required>
                <small class="text-muted">Shown in role permission UI.</small>
                @error('title') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>
            <div class="col-12 text-right">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('Log.Routes.Index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
