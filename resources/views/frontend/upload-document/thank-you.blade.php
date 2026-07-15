@extends('layouts.frontend-app')

@section('main_content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


<div class="page-header" style="border-top-left-radius:0;border-top-right-radius:0;padding:5px 5px 15px 5px">

    {{-- Header --}}
    <div class="row align-items-center mobile_view">
        <div class="col-md-12 text-center">
            <img src="https://amipolymer.in/wp-content/uploads/2020/02/ami-polymers.png" class="img-fluid p-2">
        </div>
    </div>

    <div class="text-center">
        <div class="card p-4">
            <div class="card-body">
                <i class="fa fa-check-circle text-success" style="font-size: 50px;"></i>
                <h2 class="mt-3 text-success">Thank You!</h2>
               @if(isset($message) && $message)
    <p class="mt-2">{{ $message }}</p>
@else
    <p class="mt-2">Your documents have been successfully submitted for onboarding.</p>
@endif
                <a href="https://amipolymer.com" class="btn btn-primary mt-3">
                    Go Back
                </a>
            </div>
        </div>
    </div>
</div>

@endsection