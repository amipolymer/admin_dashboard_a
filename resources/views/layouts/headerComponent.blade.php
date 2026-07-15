{{-- headerComponent.blade.php --}}

{{-- Header --}}
<div class="header">
    {{-- Header Left Side --}}
    <div class="header-left">
        <div class="menu-icon dw dw-menu"></div>
        <div class="search-toggle-icon dw dw-search2" data-toggle="header_search"></div>
        <div class="header-search">
            @if(isset($daily_search_form) && $daily_search_form == 'No')
         
            @else
             @if(Auth::user()->role == 'superadmin')
            <form method="GET" class="form-inline flex-wrap align-items-center">
                {{-- IF daily_search_from avl show this else show filte by day --}}
                @if (isset($daily_search_form) && $daily_search_form == 'yes')
                    <label for="date_range" class="mr-2 font-weight-bold">Filter by Date:</label>
              <input type="date" name="daily_entry" id="daily_entry" value="{{ request('daily_entry', \Carbon\Carbon::today()->format('Y-m-d')) }}" class="form-control">
                      <button type="submit" class="btn btn-primary py-2">Apply</button>
                @else
                    {{-- <label for="date_range" class="mr-2 mb-2 font-weight-bold">Filter by Day:</label> --}}
                    <!-- <label for="date_range" class="mr-2 mb-2 font-weight-bold">Entry for:</label>
                    <select name="date_range" id="date_range" class="form-control mr-2 mb-2"
                        onchange="toggleCustomDateInputs(this.value)">
                        <option value="">All</option>
                        <option value="last_30" {{ request('date_range') == 'last_30' ? 'selected' : '' }}>Last 30 Days
                        </option>
                        <option value="last_90" {{ request('date_range') == 'last_90' ? 'selected' : '' }}>Last 90 Days
                        </option>
                        <option value="last_180" {{ request('date_range') == 'last_180' ? 'selected' : '' }}>Last 180
                            Days</option>
                    </select>
                    <button type="submit" class="btn btn-primary mb-2">Apply</button> -->
                @endif
            </form>
            @endif
            @endif
        </div>
    </div>

    {{-- Header Right Side --}}
    <div class="header-right">

        <div class="user-info-dropdown">
            <div class="dropdown">
                <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <span class="user-icon" style="width: 40px; height:40px">
                        <img class="img-fluid" src="{{ asset($assetPrefix.'assets/theme/vendors/images/user-icon-default.jpg') }}"
                            alt="">
                        {{-- <img class="img-fluid" src="{{ asset($assetPrefix.'assets/theme/vendors/images/user-icon-default.png') }}" alt=""> --}}
                    </span>
                        <span class="user-name text-capitalize">{{ substr(Auth::user()->name, 0, 10) }}
                             <span class="btn-block font-weight-400 font-12">
                                Role: 
                                 @if(Auth::user()->role == 'superadmin')
                                  S-ADM
                                 @elseif (Auth::user()->role == 'quick-link')
                                      QLK
                                 @elseif (Auth::user()->role == 'user')
                                    USR
                                 @else
                                    {{Auth::user()->role}}
                                 @endif
                                {{-- {{ Auth::user()->role }} --}}
                            </span>
                        </span>
                        {{-- <h3 class="font-16 text-blue text-capitalize">
                            {{ Auth::user()->name }}
                            <span class="btn-block font-weight-400 font-12">{{ Auth::user()->role }}</span>
                        </h3> --}}
                
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                    <div class="text-center">
                        {{-- <span class="user-name text-capitalize">{{ Auth::user()->name }}
                             <span class="btn-block font-weight-400 font-12">{{ Auth::user()->role }}</span>
                        </span> --}}
                        </div>
                              <a class="dropdown-item" href="{{ route('profile.edit', Auth::user()->id) }}"><i class="dw dw-edit"></i> Edit Profile</a>
                    <a class="dropdown-item" href="{{ url('/logout') }}"><i class="dw dw-logout"></i> Log Out</a>
                </div>
            </div>
        </div>
        <div class="dashboard-setting user-notification">
            <div class="dropdown">
                <a class="dropdown-toggle no-arrow" href="javascript:;" data-toggle="right-sidebar">
                    <i class="dw dw-settings2"></i>
                </a>
            </div>
        </div>
        {{-- <div class="github-link">
                    <a href="#" target="_blank">
                        <img src="{{ asset($assetPrefix.'assets/theme/vendors/images/github.svg') }}" alt="">
                    </a>
                </div> --}}
    </div>
</div>
{{-- Right Sidebar --}}
<div class="right-sidebar">
    {{-- Sidebar Title --}}
    <div class="sidebar-title">
        <h3 class="weight-600 font-16 text-blue">
            Layout Settings
            <span class="btn-block font-weight-400 font-12">User Interface Settings</span>
        </h3>
        <div class="close-sidebar" data-toggle="right-sidebar-close">
            <i class="icon-copy ion-close-round"></i>
        </div>
    </div>
    {{-- Sidebar Right Side Customize Option --}}
    <div class="right-sidebar-body customscroll">
        <div class="right-sidebar-body-content">
            <h4 class="weight-600 font-18 pb-10">Header Background</h4>
            <div class="sidebar-btn-group pb-30 mb-10">
                <a href="javascript:void(0);" class="btn btn-outline-primary header-white active">White</a>
                <a href="javascript:void(0);" class="btn btn-outline-primary header-dark">Dark</a>
            </div>
            <h4 class="weight-600 font-18 pb-10">Sidebar Background</h4>
            <div class="sidebar-btn-group pb-30 mb-10">
                <a href="javascript:void(0);" class="btn btn-outline-primary sidebar-light ">White</a>
                <a href="javascript:void(0);" class="btn btn-outline-primary sidebar-dark active">Dark</a>
            </div>
            <h4 class="weight-600 font-18 pb-10">Menu Dropdown Icon</h4>
            <div class="sidebar-radio-group pb-10 mb-10">
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebaricon-1" name="menu-dropdown-icon" class="custom-control-input"
                        value="icon-style-1" checked="">
                    <label class="custom-control-label" for="sidebaricon-1"><i class="fa fa-angle-down"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebaricon-2" name="menu-dropdown-icon" class="custom-control-input"
                        value="icon-style-2">
                    <label class="custom-control-label" for="sidebaricon-2"><i class="ion-plus-round"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebaricon-3" name="menu-dropdown-icon" class="custom-control-input"
                        value="icon-style-3">
                    <label class="custom-control-label" for="sidebaricon-3"><i
                            class="fa fa-angle-double-right"></i></label>
                </div>
            </div>
            <h4 class="weight-600 font-18 pb-10">Menu List Icon</h4>
            <div class="sidebar-radio-group pb-30 mb-10">
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebariconlist-1" name="menu-list-icon" class="custom-control-input"
                        value="icon-list-style-1" checked="">
                    <label class="custom-control-label" for="sidebariconlist-1"><i
                            class="ion-minus-round"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebariconlist-2" name="menu-list-icon" class="custom-control-input"
                        value="icon-list-style-2">
                    <label class="custom-control-label" for="sidebariconlist-2"><i class="fa fa-circle-o"
                            aria-hidden="true"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebariconlist-3" name="menu-list-icon" class="custom-control-input"
                        value="icon-list-style-3">
                    <label class="custom-control-label" for="sidebariconlist-3"><i class="dw dw-check"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebariconlist-4" name="menu-list-icon" class="custom-control-input"
                        value="icon-list-style-4" checked="">
                    <label class="custom-control-label" for="sidebariconlist-4"><i
                            class="icon-copy dw dw-next-2"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebariconlist-5" name="menu-list-icon" class="custom-control-input"
                        value="icon-list-style-5">
                    <label class="custom-control-label" for="sidebariconlist-5"><i
                            class="dw dw-fast-forward-1"></i></label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="sidebariconlist-6" name="menu-list-icon" class="custom-control-input"
                        value="icon-list-style-6">
                    <label class="custom-control-label" for="sidebariconlist-6"><i class="dw dw-next"></i></label>
                </div>
            </div>
            <div class="reset-options pt-30 text-center">
                <form action="" method="post">
                <button class="btn btn-danger" id="reset-settings">Reset Settings</button><br><br>
                  <a class="btn btn-primary" href="{{ url('/logout') }}"><i class="dw dw-logout"></i> Log Out</a>
                    @csrf
                    @method('post')
                    <!-- @if (Auth::user()->role =='superadmin' || Auth::user()->role =='admin' )
                    <button class="btn btn-info" type="submit" >Re-Fresh</button>
                    @endif -->
               </form>
            </div>
        </div>
    </div>
</div>
