@extends('layouts.app')

@push('styles')
@endpush

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-8 text-center">
       <h3 class="text-danger display-3 pb-3">404</h3>
           <h4 class="text-danger">Oops! The page not be found.</h4>
           <p class="text-muted mt-2">It might have been moved, deleted, or you may have entered the wrong URL.</p>
        <a href="{{ url('/') }}" class="btn btn-primary mt-3">Go to Back</a>
        </div>
    </div>
</div>
@endsection
