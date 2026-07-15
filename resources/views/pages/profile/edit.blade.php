@extends('layouts.app')

@section('main_content')
<div class="page-header">
   <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                    <h4>Profile Details</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#" >Profile</a></li>
                        <li class="breadcrumb-item"><a href="#" class="text-primary">Edit</a></li>
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


    @if (session('success'))
        <div class="alert alert-{{ session('bg-color') }} alert-dismissible fade show">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif


<form action="{{ route('profile.UserUpdate',$userData->id) }}" class="w-50 m-auto" method="POST">
    @csrf
    @method('put')

    <div class="row">

     <div class="col-md-12 form-group">
            <label>Name</label>
            <input readonly type="text" name="name" class="form-control"
                   value="{{ old('name', $userData->name) }}"
                   placeholder="Enter name" required>
            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
     <div class="col-md-12 form-group">
            <label>Email-ID</label>
            <input readonly type="text" name="email" class="form-control"
                   value="{{ old('email', $userData->email) }}"
                   placeholder="Enter email" required>
            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        {{-- EMP-ID --}}
        <div class="col-md-12 form-group">
            <label>Employee ID <sup class="text-danger">*</sup></label>
            <input type="text" name="emp_id" class="form-control"
                   value="{{ old('emp_id', $userData->emp_id) }}"
                   placeholder="Enter emp_id" required>
            @error('emp_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

            {{-- Phone No --}}
        <div class="col-md-12 form-group">
            <label>Phone No <sup class="text-danger">*</sup></label>
            <input type="text" name="phoneno" class="form-control"
                   value="{{ old('phoneno', $userData->phoneno) }}"
                   placeholder="Enter phone number" required>
            @error('phoneno') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        {{-- Password --}}
        <div class="col-md-12 form-group"><br>
            <label>Password (Leave blank to keep current)</label>
             <div class="input-group">
            <input type="password" name="password" id="password" class="form-control"
                   placeholder="Enter new password">
                     <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)"><i class="dw dw-hide"></i></button>
                    </div>
                    @error('password') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
    
        {{-- Submit --}}
      
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>

    </div>
</form>


</div>
@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('changePasswordCheckbox');
        const passwordField = document.querySelector('.password-field');

        checkbox.addEventListener('change', function () {
            if (checkbox.checked) {
                passwordField.style.display = 'block';
            } else {
                passwordField.style.display = 'none';
                passwordField.querySelector('input').value = ''; // Clear value when hidden
            }
        });
    });
</script>

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
