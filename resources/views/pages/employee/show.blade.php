{{-- dashboard-site-details-show.blade.php --}}
@extends('layouts.app')
@push('styles')
@endpush
@section('main_content')
   <div class="page-header">
     <div class="row pb-2">
            <div class="col-md-6 col-left">
                <div class="title">
                    <h4>Labour Role Details</h4>
                </div>
                <nav aria-label="breadcrumb" role="navigation">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard/labour-roles') }}" >Labour Role</a></li>
                        <li class="breadcrumb-item"><a href="#" class="text-primary">Show</a></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-right col-right">
              
                <a href="{{ url('/dashboard/labour-roles') }}" class="back_button text-danger border btn-sm border-danger p-2 h4">
                    <i class="dw dw-return1"></i> <span class="back_title">Back</span>
                </a>
            </div>
        </div>
    <hr class="pb-2">
        <div class="Form-Element mt-2">
            <form>
                @csrf
                @method('put')
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Role Title <sup class="text-danger text-md">*</sup></label>
                         <input type="hidden" name="parent_id" value="0">
                        <input class="form-control" readonly type="text" value="{{$labourRoles->name}}" name="name" placeholder="Role Title">
                    </div>
                </div>
            </form>
        
        </div>
    </div>
@endsection