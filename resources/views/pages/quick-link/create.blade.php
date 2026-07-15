@extends('layouts.app')

@section('main_content')
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title">
                <h4>QuickLink Details</h4>
            </div>
            <nav aria-label="breadcrumb" role="navigation">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/master-entry/quick-link') }}">Link List</a></li>
                    <li class="breadcrumb-item"><a href="#" class="text-primary">Add</a></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ url('/master-entry/quick-link') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
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

    <form action="{{ route('Quicklink.Store') }}" method="POST">
        @csrf
        <div class="row">
            {{-- Name --}}
            <div class="col-md-3 form-group">
                <label>Name <sup class="text-danger">*</sup></label>
                <input type="text"
                       name="name"
                       class="form-control"
                       value="{{ old('name') }}"
                       placeholder="Enter name"
                       required>
                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Open URL --}}
            <div class="col-md-3 form-group">
                <label>Open URL</label>
                <select name="openurl" class="form-control mb-2">
                    <option value="same">Same Tab</option>
                    <option value="new">New Tab</option>
                </select>
                @error('openurl') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- URL --}}
            <div class="col-md-6 form-group">
                <label>URL <sup class="text-danger">*</sup></label>
                <input type="text"
                       name="url"
                       class="form-control"
                       value="{{ old('url') }}"
                       placeholder="Enter URL"
                       required>
                @error('url') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Logo --}}
            <div class="col-md-3 form-group">
                <label>Logo</label>
                <input type="text"
                       name="logo"
                       class="form-control"
                       value="{{ old('logo') }}"
                       placeholder="Enter logo path or URL">
                @error('logo') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Submit --}}
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        </div>
    </form>
</div>

@push('js')
<script>
    function togglePassword(button) {
        const passwordField = document.getElementById("password");

        if (passwordField.type === "password") {
            passwordField.type = "text";
            button.innerText = "Hide";
            button.innerHTML = '<i class="dw dw-eye"></i>';
        } else {
            passwordField.type = "password";
            button.innerText = "Show";
            button.innerHTML = '<i class="dw dw-hide"></i>';
        }
    }
</script>
@endpush

@endsection
