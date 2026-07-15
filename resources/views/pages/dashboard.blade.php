{{-- dashboard-blade.php --}}
@extends('layouts.app')
@push('styles')
   @endpush
@section('main_content')
      <div class="page-header">
				<div class="row">
					<div class="col-md-6 col-sm-12">
						<div class="title">
							<h4>Dashboard</h4>
						</div>
						<nav aria-label="breadcrumb" role="navigation">
							<ol class="breadcrumb">
								<li class="breadcrumb-item"><a href="{{url('/dashboard')}}">Dashboard</a></li>
								<li class="breadcrumb-item active" aria-current="page">Dashboard</li>
							</ol>
						</nav>
					</div>
					<div class="col-md-6 col-sm-12 text-right">
						{{-- <div class="dropdown">
							<a class="btn btn-primary dropdown-toggle" href="#" role="button" data-toggle="dropdown">
								January 2018
							</a>
							<div class="dropdown-menu dropdown-menu-right">
								<a class="dropdown-item" href="#">Export List</a>
								<a class="dropdown-item" href="#">Policies</a>
								<a class="dropdown-item" href="#">View Assets</a>
							</div>
						</div> --}}
					</div>
				</div>
			</div>
       @include('components.dashboard.dashboardComponent',[$SiteDetails])
       {{-- @include('components.dashboard.dashboardLeadSheetComponent' ,[$tabledata]) --}}
       @include('components.dashboard.upcomingPaymentsComponent',[$upcomingPayments])
       {{-- @include('components.dashboard.unupdatedComponent' ,[$tabledata])
       @include('components.dashboard.standbyComponent' ,[$tabledata]) --}}
@endsection

