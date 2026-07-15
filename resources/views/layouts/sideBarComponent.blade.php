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
                    // master entry
                    $isMasterEntry = ['master-entry*','dashboard*','quick-link*'];
                    $isMasterEntry = collect($isMasterEntry)->contains(fn($route) => Request::is($route));
                    // annual report view
                    $isAnnualReport = ['annual-report*'];
                    $isAnnualReport = collect($isAnnualReport)->contains(fn($route) => Request::is($route));
                    // quick link
                    $isQuickLink = ['master-entry/quick-link*'];
                    $isQuickLink = collect($isQuickLink)->contains(fn($route) => Request::is($route));
                    // employee list
                    $isEmployeeList = ['master-entry/employee-list*','master-entry/user-role*'];
                    $isEmployeeList = collect($isEmployeeList)->contains(fn($route) => Request::is($route));
                    // onboard assistant
                    $isOnBoardAssistant = ['onboard-assistant*'];
                    $isOnBoardAssistant = collect($isOnBoardAssistant)->contains(fn($route) => Request::is($route));
                    // system logs
                    $isSystemLogs = ['master-entry/logs*'];
                    $isSystemLogs = collect($isSystemLogs)->contains(fn ($route) => Request::is($route));
                    // Get the authenticated user's role
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
                        
                            {{-- <li><a href="{{ route('UersRole.Index') }}">Users Role</a></li> --}}  
                            {{-- <li><a href="{{ route('Users.Index') }}">Employee List</a></li> --}}
                            {{-- <li><a href="{{ route('Users.Index') }}">New Joiner List</a></li> --}}
                            {{-- <li><a href="{{ route('Quicklink.Index') }}">Quicklink List</a></li> --}}
                        </ul>
                    </li>
                    <li class="dropdown {{ $isEmployeeList ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-user1"></span>
                            <span class="mtext">Users</span>
                        </a>
                        <ul class="submenu {{ $isEmployeeList ? 'show' : '' }}"
                            style="{{ $isEmployeeList ? 'display:block;' : '' }}">
                               <li><a href="{{ route('UersRole.Index') }}">Users Role</a></li>
                               <li><a href="{{ route('Users.Index') }}">Employee List</a></li>
                        </ul>
                    </li>
                    <li class="dropdown {{ $isQuickLink ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-link"></span>
                            <span class="mtext">Quicklink</span>
                        </a>
                        <ul class="submenu {{ $isQuickLink ? 'show' : '' }}"
                            style="{{ $isQuickLink ? 'display:block;' : '' }}">
                            {{-- <li><a href="{{ route('Users.Index') }}">New Joiner List</a></li> --}}
                            <li><a href="{{ route('Quicklink.Index') }}">Quicklink List</a></li>
                        </ul>
                    </li>

                    <li class="dropdown {{ $isOnBoardAssistant ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-add-user"></span>
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
                            <span class="micon dw dw-invoice"></span>
                            <span class="mtext">Annual R. View</span>
                        </a>
                        <ul class="submenu {{ $isAnnualReport ? 'show' : '' }}"
                            style="{{ $isAnnualReport ? 'display:block;' : '' }}">
                            <li><a href="{{ route('AnnualReportViewForm.Index') }}">Annual Report View Form List</a></li>
                        </ul>
                    </li>
                    @if ($userRole == 'superadmin' || $userRole == 'admin')
                    <li class="dropdown {{ $isSystemLogs ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-notebook"></span>
                            <span class="mtext">Log</span>
                        </a>
                        <ul class="submenu {{ $isSystemLogs ? 'show' : '' }}"
                            style="{{ $isSystemLogs ? 'display:block;' : '' }}">
                              @if ($userRole == 'superadmin')
                            <li><a href="{{ route('Log.Routes.Index') }}">All Route List </a></li>
                            <li><a href="{{ route('Log.Activity.Index') }}">Active Logs</a></li>
                             @endif
                            <li><a href="{{ route('Log.Login.Index') }}">User Login Logs</a></li>
                        </ul>
                    </li>
                    @endif
                @else
                @php
    $showSubmenu = $isMasterEntry || $isOnBoardAssistant ||  $isAnnualReport || $isQuickLink;
@endphp
                    <li class="dropdown {{ $showSubmenu ? 'show' : '' }}">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-home"></span>
                            <span class="mtext">Dashboard</span>
                        </a>
                        <ul class="submenu {{ $showSubmenu ? 'show' : '' }}"
                            style="{{ $showSubmenu ? 'display:block;' : '' }}">
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
