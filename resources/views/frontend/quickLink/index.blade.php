{{-- dashboard-blade.php --}}
@extends('layouts.app')
@push('styles')
    <style>
        .contact-directory-box {
            min-height: 0px;
        }

        @media (max-width: 767px) {
            .contact-directory-box .view-contact a {
                padding: 10px 0px;
            }

            .contact-directory-box .contact-name h4 {
                font-size: 13px;
            }

            .contact-dire-info {
                padding: 5px 0px 0px;
            }

            .contact-directory-list {
                padding: 0px 20px !important;

            }

            .contact-directory-list>ul>li {
                margin-bottom: 1px;
            }

            .contact-directory-list .col-6 {
                padding: 4px;
            }

            .contact-directory-box .contact-name,
            .contact-directory-box .contact-skill {
                height: 40px;

            }

        }

        .contact-directory-box .view-contact a:before {
            background-color: unset;
        }

        .contact-directory-box .contact-name,
        .contact-directory-box .contact-skill {
            /* height: 40px; */
            padding-bottom: 2px;
            text-transform: capitalize;
        }

        .contact-directory-box .view-contact a {
            padding: 10px 0px;
        }

        .btn-outline-primary {
            color: #0b4b9d;
            border-color: #0b4b9d;
        }

        .contact-directory-box:hover .btn-outline-primary {
            background-color: #0b4b9d !important;
            border-color: #0b4b9d !important;
            color: #fff !important;

        }

        .btn-outline-primary:hover {
            background-color: #0b4b9d !important;
            border-color: #0b4b9d !important;
            color: #fff !important;
        }

        .contact-directory-box .contact-avatar span {
            width: 60px !important;
            height: 60px !important;
        }

        .contact-dire-info {
            padding: 10px 5px 5px;
        }

        .contact-directory-box .contact-name h4 {
            font-size: 15px;
        }
    .alert-warning{
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
                    @php
                                           
                        if(is_null(Auth::user()->password_changed_at)) {
                            $passwordChangeDate = \Carbon\Carbon::now()->subDays(61);
                    }else{
                            $passwordChangeDate = Auth::user()->password_changed_at;
                    }
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
            </div>
        </div>
    </div>
    <div class="contact-directory-list">
        <ul class="row">
            @foreach ($quickLinks as $link)
                @if (Auth::user()->role == 'superadmin')
                    <li class="col-6 col-sm-6 col-md-2">
                        <div class="contact-directory-box">
                            <div class="contact-dire-info text-center">
                                <div class="contact-avatar">
                                    <span>
                                        <img src="{{ $link->logo }}" class="img-fluid" alt="">
                                    </span>
                                </div>
                                <div class="contact-name">
                                    <h4>{{ $link->name }}</h4>
                                </div>
                            </div>
                            <div class="view-contact">
                                <a target="{{ $link->openurl == 'new' ? '_blank' : '_self' }}" href="{{ $link->url }}"
                                    class="btn btn-outline-primary">Open Link</a>
                            </div>
                        </div>
                    </li>
                @else
                    @if (in_array($link->id, $permissionslink->quick_link_id))
                        <li class="col-6 col-sm-6 col-md-2">
                            <div class="contact-directory-box">
                                <div class="contact-dire-info text-center">
                                    <div class="contact-avatar">
                                        <span>
                                            <img src="{{ $link->logo }}" class="img-fluid" alt="">
                                        </span>
                                    </div>
                                    <div class="contact-name">
                                        <h4>{{ $link->name }}</h4>
                                    </div>
                                </div>
                                <div class="view-contact">
                                    <a target="{{ $link->openurl == 'new' ? '_blank' : '_self' }}"
                                        href="{{ $link->url }}" class="btn btn-outline-primary">Open Link</a>
                                </div>
                            </div>
                        </li>
                    @else
                    @endif
                @endif
            @endforeach
        </ul>
    </div>

@endsection
