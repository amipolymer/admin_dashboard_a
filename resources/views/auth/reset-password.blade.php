@extends('layouts.app')
@php
$assetPrefix = config('app.asset_prefix').'/';
@endphp
@push('styles')
<style>

.email_alert_success{
font-size:14px;
padding: 5px;
}.main-container {
    padding: 80px 0px 0 0px;
}
.btn-link{
    text-decoration: underline;
    color: #007bff;
}
    </style>
@endpush
@section('main_content')
<!-- <div class="login-header box-shadow"> -->
    <div class="login-wrap d-flex flex-wrap justify-content-center">
        <div class="container">
            <div class="row">
              <div class="col-md-12">
                 <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
                            <!-- <img src="{{ url($assetPrefix.'assets/theme/src/images/logo/main-logo-v2.png') }}" 
                         alt="Login Image" class="img-fluid px-5"> -->
                            <h2 class="text-center text-primary">Change Password</h2>
                        </div>
                                           
                           <form method="POST" action="{{ route('password.store') }}">
                            @csrf
                            @method('POST')
                                          
                            <!-- Email Input -->
                            <div class="input-group custom">
                                <input readonly type="email" class="form-control form-control-lg" 
                                name="email" value="{{ $request->email }}" required 
                                placeholder="Username/email">
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="icon-copy dw dw-user1"></i></span>
                                </div>
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                            </div>
      <!-- Password Input -->
                            <div class="input-group custom">
                                <input type="password" id="password" class="form-control form-control-lg" 
                                       name="password" required placeholder="**********">
                                <div class="input-group-append custom" onclick="togglePassword()">
                                    <span class="input-group-text"><i id="toggleIcon" class="fa fa-eye-slash"></i></span>
                                </div>
                            </div>

                                  <!-- Password Input -->
                            <div class="input-group custom">
                                <input type="password" id="password_confirmation" class="form-control form-control-lg" 
                                       name="password_confirmation" required placeholder="**********">
                                <div class="input-group-append custom" onclick="togglePassword_confirmation()">
                                    <span class="input-group-text"><i id="toggleIcon1" class="fa fa-eye-slash"></i></span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-sm-12 pb-2">
                                    @error('password') <small class="text-danger pb-5">{{ $message }}</small> @enderror
                                </div>
                                    <div class="col-sm-12">
                                    <div class="input-group mb-0 justify-content-center">
                                        <button class="btn btn-primary btn-lg btn-block" type="submit">Reset Password</button>
                                        <a href="{{ route('login') }}" class="btn btn-link text-center">Back to Login</a>
                                    </div>
                                </div>
                            </div>
                                    @if (session('status'))
                                <div class="text-center mt-3">
                                <span class="alert alert-{{ session('status_bg') }} email_alert_success" role="alert">
                                    {{ session('status') }}
                                </span>
                            </div>
                                @endif
                               
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const password_confirmation = document.getElementById('password_confirmation');
            const icon = document.getElementById('toggleIcon');
            const icon1 = document.getElementById('toggleIcon1');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                password_confirmation.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon1.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                icon1.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                password_confirmation.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                icon1.classList.add('fa-eye-slash');
                icon1.classList.remove('fa-eye');
            }
        }
    </script>

    <script>
        function togglePassword_confirmation() {
            const password_confirmation = document.getElementById('password_confirmation');
             const passwordInput = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
             const icon1 = document.getElementById('toggleIcon1')
            
            if (password_confirmation.type === 'password') {
                password_confirmation.type = 'text';
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon1.classList.remove('fa-eye-slash');
                icon1.classList.add('fa-eye');
                icon.classList.add('fa-eye');
            } else {
                password_confirmation.type = 'password';
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon1.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                icon1.classList.add('fa-eye-slash');
            }
        }
    </script>
