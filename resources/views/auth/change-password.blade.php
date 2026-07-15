@extends('layouts.app')
@php
$assetPrefix = config('app.asset_prefix').'/';
@endphp
@push('styles')
<style>
   .main-container {
    padding: 90px 0px 0 0px;
}
.email_alert_success{
font-size:14px;
padding: 5px;
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
               <div class="col-md-12 text-center">
                    <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
                            <!-- <img src="{{ url($assetPrefix.'assets/theme/src/images/logo/main-logo-v2.png') }}" 
                         alt="Login Image" class="img-fluid px-5"> -->
                            <h2 class="text-center text-primary">Change Password</h2>
                        </div>
                                           
                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            @method('POST')                          
                            <!-- Email Input -->
                            <div class="input-group custom">
                                <input type="email" class="form-control form-control-lg" 
                                       name="email" value="{{ old('email') }}" required 
                                       placeholder="Username/email">
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="icon-copy dw dw-user1"></i></span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="input-group mb-0 justify-content-center">
                                        <button class="btn btn-primary btn-lg btn-block" type="submit">Email Password Reset Link</button>
                                        <a href="{{ route('login') }}" class="btn btn-link">Back to Login</a>
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
