@extends('layouts.app')
@php
$assetPrefix = config('app.asset_prefix').'/';
@endphp
@push('styles')
<style>
    body {
        width: 100%;
         /* background: url('assets/theme/src/images/banner/banner-2.png') no-repeat; */
         /*background: url('public/assets/theme/src/images/banner/Background.jpg') no-repeat;*/
          background: url('{{ $assetPrefix }}assets/theme/src/images/banner/bg-image-4.png') no-repeat; 
         background-size:cover;
        /* background-color: #ffffff; */
        /* padding: 0;
        margin: 0; */
    }
    .main-container{
        padding: 0 !important;
    }
    .bg-white {
    background-color: #ffffffa3 !important;
}
.box-shadow {
    -webkit-box-shadow: 0 0 28px rgba(0, 0, 0, .08);
    box-shadow: inset 0 0 0px 1px rgb(0 78 161);
}
.error_section
{
    background-color: #dc3545;
    text-align: center;
    margin-bottom: 5px;
}
/*.login-box.bg-white.box-shadow.border-radius-10 {
        float: inline-end;
    margin-right: -90px;
    margin-top: 54px;
}*/
    </style>
@endpush
@section('main_content')
<!-- <div class="login-header box-shadow"> -->
    <div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
        <div class="container">
            <div class="row align-items-center">
                <!-- <div class="col-md-6"> -->
                    <!-- <img src="https://dropways.github.io/deskapp/vendors/images/login-page-img.png" 
                         alt="Login Image" class="img-fluid"> -->
                    <!-- <img src="{{ url('assets/theme/src/images/banner/Business Banner.png') }}" 
                         alt="Login Image" class="img-fluid"> -->
                <!-- </div> -->

                <div class="col-md-12">
<div class="login-box bg-white box-shadow border-radius-10" style="float: inline-start;">
                        <div class="login-title">
                            <img src="{{ url($assetPrefix.'assets/theme/src/images/logo/main-logo-v2.png') }}" 
                         alt="Login Image" class="img-fluid px-5">
                            <!-- <h2 class="text-center text-primary">Login To Application</h2> -->
                        </div>

                        <form method="POST" action="{{ route('login') }}">
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

                            <!-- Password Input -->
                            <div class="input-group custom">
                                <input type="password" id="password" class="form-control form-control-lg" 
                                       name="password" required placeholder="**********">
                                <div class="input-group-append custom" onclick="togglePassword()">
                                    <span class="input-group-text"><i id="toggleIcon" class="fa fa-eye-slash"></i></span>
                                </div>
                            </div>

                            <!-- Error Messages -->
                        @if($errors->any())
                            <div class="p-2 error_section">
                                @error('status') 
                                    <small class="text-white">{{ $message }}</small> 
                                @enderror
                                @error('email') 
                                    <small class="text-white">{{ $message }}</small> 
                                @enderror
                                @error('password') 
                                    <small class="text-white">{{ $message }}</small> 
                                @enderror
                            </div>
                            @endif

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="input-group mb-0">
                                        <button class="btn btn-primary btn-lg btn-block" type="submit">Login</button>
                                        @if($errors->first('link'))
                                            <a href="{{ route('password.change') }}" class="btn btn-link">Change Password?</a>
                                        @else
                                            <a href="{{ route('password.request') }}" class="btn btn-link">Forgot Password?</a>
                                        @endif

                                    </div>
                                </div>                           
                             </div>
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
            const icon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    </script>
