{{-- sideBarComponent.blade.php --}}

{{-- Left Sidebar --}}
<div class="left-side-bar">
    {{-- Brand Logo --}}
    <div class="brand-logo">
        <a href="{{ url('/') }}">
            <img src="{{ asset($assetPrefix.'assets/theme/src/images/logo/main-logo-v2.png') }}" alt="" class="dark-logo">
            <img src="{{ asset($assetPrefix.'assets/theme/src/images/logo/main-logo-v2.png') }}" alt="" class="light-logo">
        </a>
        <div class="close-sidebar" data-toggle="left-sidebar-close">
            <i class="ion-close-round"></i>
        </div>
    </div>

    {{-- Menu Block --}}
    <div class="menu-block customscroll">
        <div class="sidebar-menu">
            <ul id="accordion-menu">
                @php
                    $isMasterEntry = ['master-entry*','dashboard*','quick-link*'];
                    $isMasterEntry = collect($isMasterEntry)->contains(fn($route) => Request::is($route));
                    $isAnnualReport = ['annual-report*'];
                    $isAnnualReport = collect($isAnnualReport)->contains(fn($route) => Request::is($route));

                    $isOnBoardAssistant = ['onboard-assistant*'];
                    $isOnBoardAssistant = collect($isOnBoardAssistant)->contains(fn($route) => Request::is($route));

                    $userRole = Auth::user()->role;
                @endphp
            
                 @if ($userRole == 'superadmin' || $userRole == 'admin')
                    <li class="dropdown {{ $isMasterEntry ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-home"></span>
                            <span class="mtext">Dashboard</span>
                        </a>
                        <ul class="submenu {{ $isMasterEntry ? 'show' : '' }}"
                            style="{{ $isMasterEntry ? 'display:block;' : '' }}">
                            <li><a href="{{ url('/dashboard/') }}">Dashboard</a></li>
                        
                                <li><a href="{{ route('UersRole.Index') }}">Users Role</a></li>
                          
                            <li><a href="{{ route('Users.Index') }}">Employee List</a></li>
                            {{-- <li><a href="{{ route('Users.Index') }}">New Joiner List</a></li> --}}
                            <li><a href="{{ route('Quicklink.Index') }}">Quicklink List</a></li>
                        </ul>
                    </li>

                    <li class="dropdown {{ $isOnBoardAssistant ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-home"></span>
                            <span class="mtext">OnBoard Assistant</span>
                        </a>
                        <ul class="submenu {{ $isOnBoardAssistant ? 'show' : '' }}"
                            style="{{ $isOnBoardAssistant ? 'display:block;' : '' }}">
                            <li><a href="{{ route('EmployeeJoiner.Index') }}">New Joiner List</a></li>
                            <li><a href="{{ route('EmployeeJoiner.ongridInviteList') }}">OnGrid Invite List</a></li> 
                        </ul>
                    </li>
                    <li class="dropdown {{ $isAnnualReport ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-home"></span>
                            <span class="mtext">Annual R. View</span>
                        </a>
                        <ul class="submenu {{ $isAnnualReport ? 'show' : '' }}"
                            style="{{ $isAnnualReport ? 'display:block;' : '' }}">
                            <li><a href="{{ route('AnnualReportViewForm.Index') }}">Annual Report View Form List</a></li>
                        </ul>
                    </li>
                @else
                    <li class="dropdown {{ $isMasterEntry ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-home"></span>
                            <span class="mtext">Dashboard</span>
                        </a>
                        <ul class="submenu {{ $isMasterEntry ? 'show' : '' }}"
                            style="{{ $isMasterEntry ? 'display:block;' : '' }}">
                               @if ($userRole == 'hr')
                               <li><a href="{{ route('EmployeeJoiner.Index') }}">New Joiner List</a></li> 
                               <li><a href="{{ route('EmployeeJoiner.ongridInviteList') }}">OnGrid Invited List</a></li> 
                               @endif
                               @if ($userRole == 'account')
                               <li><a href="{{ route('AnnualReportViewForm.Index') }}">Annual Report View Form List</a></li>
                               @endif
                            <li><a href="{{ route('quickLink') }}">Quicklink List</a></li>
                        </ul>
                    </li>   
                @endif
       
            </ul>
        </div>
    </div>
</div>

<style>
    @media only screen and (max-width: 768px) {
        .submenu[style*="display:block"] {
            display: none !important; /* Overrides inline display:block on mobile */
        }
    }
</style>
