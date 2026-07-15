{{-- dashboard-blade.php --}}
@extends('layouts.app')
@push('styles')
    <style>
        .alert-warning {
            width: fit-content;
            float: inline-end;
        }
    </style>
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
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </nav>

            </div>
            <div class="col-md-6 col-sm-12 text-right">

                @if (Auth::check())
                    @if (Auth::user()->role != 'admin' && Auth::user()->role != 'superadmin')
                        @php
                            // Get the password change date
                            if (empty($passwordChangeDate)) {
                                // If null or empty, default to now (or 60 days ago if you want alert)
                                $passwordChangeDate = now()->subDays(60)->startOfDay(); // triggers alert immediately
                            } else {
                                $passwordChangeDate = $passwordChangeDate->startOfDay();
                            }
                            // Set both dates to midnight to ignore time differences
                            $passwordChangeDateMidnight = $passwordChangeDate->startOfDay();
                            $nowMidnight = now()->startOfDay();

                            // Calculate the number of full days since the password change
                            $daysSinceChange = $passwordChangeDateMidnight->diffInDays($nowMidnight);

                            // Calculate the remaining days to change the password (60 days total)
                            $remainingDays = 60 - $daysSinceChange;

                            // Check if the password was changed more than 50 days ago and if there are 10 or fewer days left
                            $isWarningVisible = $passwordChangeDate && $daysSinceChange >= 50 && $remainingDays <= 10;
                        @endphp

                        @if ($isWarningVisible)
                            <div class="alert alert-warning alert-dismissible fade show mt-2" role="alert">
                                Your password is 60 days old. Please update it for security
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>
    @include('components.dashboard.dashboardComponent', [$employeeList])

@endsection
