@extends('layouts.app')

@section('main_content')
<div class="page-header">
   <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                    <h4>User Role Details</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/master-entry/user-role') }}" >User Role</a></li>
                        <li class="breadcrumb-item"><a href="#" class="text-primary">Add</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
              
                <a href="{{ url('/master-entry/user-role') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                    <i class="dw dw-return1"></i> <span class="back_title">Back</span>
                </a>
            </div>
        </div>
    <hr class="pb-2">


    @if (session('success'))
        <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif


    <form action="{{ route('UersRole.Store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('post')
        <div class="row">
            <div class="col-md-3 form-group pt-4">
                <label>Role Title <sup class="text-danger">*</sup></label>
                <input class="form-control" type="text" name="role_name" value="{{ old('role_name') }}" required placeholder="Role Title">
                @error('role_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label>Page URL <sup class="text-danger">*</sup></label>
               {{-- <select class="form-control" name="url_ids"> --}}
             <select class="form-control selectpicker" name="url_ids[]" data-style="btn-outline-success" data-selected-text-format="count" multiple="" tabindex="-98">
                 @foreach ($routeList as $route)
         <option value="{{ $route->id }}"
            {{ in_array($route->id, old('url_ids', json_decode($userData->url_ids ?? '[]'))) ? 'selected' : '' }}>
            {{ $route->url_name }}
        </option>
          @endforeach
               </select>

                @error('url_ids')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-3">
                <label>Quick-link URL <sup class="text-danger">*</sup></label>
             <select class="form-control selectpicker" name="quick_link_id[]" data-style="btn-outline-success" data-selected-text-format="count" multiple="" tabindex="-98">
                 @foreach ($quicklinks as $quicklink)
         <option value="{{ $quicklink->id }}"
            {{ in_array($quicklink->id, old('quick_link_id', json_decode($userData->quick_link_id ?? '[]'))) ? 'selected' : '' }}>
            {{ $quicklink->name }}
        </option>
          @endforeach
               </select>

                @error('quick_link_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        </div>
    </form>
</div>
@endsection
