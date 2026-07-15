<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Candidate Onboarding Portal</title>
    <link rel="icon" type="image/png" href="{{ url('assets/theme/src/images/logo/favicon-icon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; margin: 0; }
        .portal-shell {
            max-width: 1140px;
            padding-left: max(0.75rem, env(safe-area-inset-left));
            padding-right: max(0.75rem, env(safe-area-inset-right));
        }
        .main-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .portal-inner-padding {
            padding: 1rem 1.25rem 0;
        }
        .logo { max-height: 70px; }
        .info-box,
        .document-card {
            border: 1px solid #e6e6e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 12px;
        }
        .btn-brand { background: #034ea1; border-color: #034ea1; color: #fff; }
        .btn:hover {
            background: #e3e8ed;
            border-color: #034ea1;
            color: #034ea1;
        }
        .portal-tabs-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .portal-tabs {
            flex-wrap: nowrap;
            border-bottom: 1px solid #dee2e6;
            width: 100%;
        }
        .portal-tabs .nav-link {
            white-space: nowrap;
            padding: 0.65rem 1rem;
            font-size: 0.9375rem;
        }
        .portal-tab-content {
            border-top: 1px solid #dee2e6;
            background: #fff;
        }
        .portal-meta-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem 0.75rem;
        }
        .portal-meta-name {
            flex: 1 1 auto;
            min-width: 0;
        }
        .portal-meta-name h5 {
            font-size: 1rem;
            line-height: 1.25;
        }
        .portal-meta-due {
            flex: 0 0 auto;
            text-align: right;
            font-size: 0.8125rem;
            line-height: 1.35;
        }
        .portal-meta-due-label {
            display: block;
            color: #6c757d;
        }
        .portal-meta-due-date {
            display: block;
            font-weight: 700;
            color: #dc3545;
        }
        @media (max-width: 767.98px) {
            .portal-shell {
                padding-left: max(0.375rem, env(safe-area-inset-left));
                padding-right: max(0.375rem, env(safe-area-inset-right));
                padding-top: 0.375rem;
                padding-bottom: 0.5rem;
            }
            .portal-shell.container {
                --bs-gutter-x: 0.75rem;
            }
            .portal-inner-padding {
                padding: 0.625rem 0.5rem 0;
            }
            .portal-header-row h4 {
                font-size: 1.05rem;
            }
            .portal-header-row .col-md-8 {
                margin-top: 0.5rem;
            }
            .portal-meta-name h5 {
                font-size: 0.9375rem;
            }
            .portal-meta-due {
                font-size: 0.75rem;
            }
            .logo {
                max-height: 48px;
            }
            .portal-tabs .nav-link {
                padding: 0.5rem 0.65rem;
                font-size: 0.8125rem;
            }
            .portal-tab-content {
                padding: 0.5rem !important;
            }
            .info-box,
            .document-card {
                padding: 0.5rem;
                border-radius: 6px;
                margin-bottom: 0.5rem;
            }
            .portal-tab-content h4 {
                font-size: 1.05rem;
                margin-bottom: 0.75rem !important;
            }
            .portal-tab-content h5 {
                font-size: 0.95rem;
                margin-bottom: 0.5rem !important;
            }
            .offer-letter-panel iframe,
            .appointment-letter-panel iframe {
                height: min(400px, 50vh) !important;
                border-radius: 4px !important;
            }
            .portal-tab-content .alert {
                padding: 0.5rem 0.625rem;
                margin-bottom: 0.5rem;
            }
            .portal-tab-content .table {
                font-size: 0.875rem;
            }
            .portal-tab-content > .row,
            .portal-tab-content .row.g-3,
            .portal-tab-content .row.g-2 {
                --bs-gutter-x: 0.5rem;
            }
            .portal-tab-content .info-box:last-child {
                margin-bottom: 0;
            }
        }
        @media (min-width: 768px) {
            .portal-inner-padding {
                padding: 1.25rem 1.5rem 0;
            }
        }
        @media print {
            body * { visibility: hidden !important; }
            body::after {
                content: 'Printing is not permitted on the candidate onboarding portal.';
                visibility: visible !important;
                display: block;
                position: fixed;
                inset: 0;
                padding: 2rem;
                font-size: 1.25rem;
                background: #fff;
            }
        }
    </style>
</head>
<body>
@if (!empty($invalid))
    <div class="container py-5"><div class="alert alert-danger">Invalid link. Contact HR.</div></div>
@elseif (!empty($completed))
    <div class="container py-5 text-center main-card p-5">
        <h2 class="text-success">Onboarding Complete</h2>
        <p>Thank you, {{ $employee->emp_name }}.</p>
    </div>
@else
@php
    $empId = $employee->displayEmployeeId();
    $tabs = $portalTabs ?? ['info' => 'Info', 'document' => 'Document', 'letter' => 'Letter', 'joining' => 'Joining', 'policy' => 'Policy'];
@endphp
<div class="container portal-shell py-2 py-md-4">
    <div class="main-card">
        <div class="portal-inner-padding">
            <div class="row align-items-center border-bottom pb-2 pb-md-3 mb-2 mb-md-3 portal-header-row text-center text-md-start">
                <div class="col-md-4">
                    <img src="https://amipolymer.in/wp-content/uploads/2020/02/ami-polymers.png" class="logo" alt="Ami Polymer">
                </div>
                <div class="col-md-8 text-center text-md-end">
                    <h4 class="mb-0">Candidate Onboarding</h4>
                </div>
            </div>
            <div class="portal-meta-row border-bottom pb-2 pb-md-3 mb-2 mb-md-3">
                <div class="portal-meta-name">
                    <h5 class="mb-0">{{ $employee->emp_name }}</h5>
                    <small class="text-muted">{{ $currentStepLabel ?? 'In progress' }}</small>
                </div>
                <div class="portal-meta-due">
                    <span class="portal-meta-due-label">Document due:</span>
                    <span class="portal-meta-due-date">{{ $employee->emp_document_due_date?->format('d-M-Y') }}</span>
                </div>
            </div>
            @if (session('success'))<div class="alert alert-success mb-2 mb-md-3 py-2">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger mb-2 mb-md-3 py-2">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger mb-2 mb-md-3 py-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        </div>
        <div class="portal-tabs-wrap">
            <ul class="nav nav-tabs portal-tabs flex-nowrap mb-0">
                @foreach($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ ($activeTab ?? 'info') === $key ? 'active' : '' }}"
                           href="{{ route('onboarding.portal', ['token'=>$employee->emp_url,'tab'=>$key]) }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="portal-tab-content p-3 p-md-4">
            @if (($activeTab ?? 'info') === 'info') @include('frontend.onboarding.partials.tab-info')
            @elseif ($activeTab === 'document') @include('frontend.onboarding.partials.tab-documents')
            @elseif ($activeTab === 'letter') @include('frontend.onboarding.partials.tab-letter')
            @elseif ($activeTab === 'joining') @include('frontend.onboarding.partials.tab-joining')
            @elseif ($activeTab === 'policy') @include('frontend.onboarding.partials.tab-policy')
            @endif
        </div>
    <div class="footer">
    <div class="container text-center pt-2 border-top">
        <div class="row">
            <div class="col-12">
                <p> © 2026 Ami Polymer. All rights reserved.</p>
            </div>
        </div>
    </div>
</div>
    </div>
    
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
