@extends('layouts.app')

@section('main_content')
<div class="page-header">
    <div class="row pb-2">
        <div class="col-md-6 col-left">
            <div class="title">
                <h4>Employee Details</h4>
            </div>
            <nav aria-label="breadcrumb" role="navigation">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/master-entry/employee-list') }}">Employee List</a></li>
                    <li class="breadcrumb-item"><a href="#" class="text-primary">Add</a></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-right col-right">
            <a href="{{ url('/master-entry/employee-list') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
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

    <form action="{{ route('Users.Store') }}" method="POST">
        @csrf
        <div class="row">
            {{-- Employee ID --}}
            <div class="col-md-6 form-group">
                <label>Employee-ID <sup class="text-danger">*</sup></label>
                <input type="text" name="emp_id" class="form-control" value="{{ old('emp_id') }}" placeholder="Enter emp id" required>
                @error('emp_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Name --}}
            <div class="col-md-6 form-group">
                <label>Name <sup class="text-danger">*</sup></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Enter name" required>
                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Phone Number --}}
            <div class="col-md-6 form-group">
                <label>Phone No <sup class="text-danger">*</sup></label>
                <input type="text" name="phoneno" class="form-control" value="{{ old('phoneno') }}" placeholder="Enter phone number" required>
                @error('phoneno') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Role --}}
            <div class="col-md-6 form-group">
                <label>Role <sup class="text-danger">*</sup></label>
                <select name="role" class="form-control text-capitalize" required>
                    <option value="">-- Select Role --</option>
                    @foreach ($roleList as $role)
                        <option value="{{ $role->role_name }}" {{ old('role') == $role->role_name ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
                @error('role') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Email --}}
            <div class="col-md-6 form-group">
                <label>Email-Id <sup class="text-danger">*</sup></label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Enter email" required>
                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Password --}}
            <div class="col-md-6 form-group"><br>
                <label>Password <sup class="text-danger">*</sup></label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)"><i class="dw dw-hide"></i></button>
                </div>
                @error('password') <small class="text-danger">{{ $message }}</small> @enderror
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
