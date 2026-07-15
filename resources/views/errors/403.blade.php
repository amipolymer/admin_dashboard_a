@extends('layouts.app')

@push('styles')
@endpush

@section('main_content')
<div class="container">
    <div class="row justify-content-center align-items-center" style="height: 80vh;">
        <div class="col-md-8 text-center">
            <h3 class="text-danger display-3 pb-3">Oops!</h3>
            <h4 class="text-danger">
                 {{ $exception->getMessage() ?: ' 
                It looks like you’re not authorized to view this page.'
                }}
            </h4>
        </div>
    </div>
</div>
@endsection
