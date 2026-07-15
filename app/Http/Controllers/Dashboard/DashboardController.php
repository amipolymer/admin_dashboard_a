<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteDetail;
use App\Models\Labour;
use App\Models\Material;
use App\Models\User;
use App\Models\CheckUpdateSite;
use App\Models\DailyLabourEntry;
use App\Models\DailyMaterialEntry;
use App\Models\QuickLink;
use App\Models\RoleRoutePermission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $userRole;
    protected $userId;

    public function __construct()
    {
        // Set user role and ID from authenticated user
        $this->userRole = Auth::user()->role;
        $this->userId = Auth::user()->emp_id;
    }

    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $quicklinks =  Quicklink::where('status', 'active')->get();
        $SiteDetails = [];
        $upcomingPayments = [];
        $unupdatedSites = [];
        $activeSites = [];
       $employeeList = User::where('role', '!=', 'superadmin')->get();
           if($this->userRole == 'quick-link' || $this->userRole == 'hr' || $this->userRole == 'employee' ){ 
             $permissionslink = RoleRoutePermission::where("role_name",Auth::user()->role)->first();
        $permissionslink->quick_link_id = json_decode($permissionslink->quick_link_id, true);
            // For 'quick-link' role, fetch only quick links
            return view("frontend.quickLink.index", [
                "quickLinks" => $quicklinks,
                "permissionslink" => $permissionslink,
            ]);
        } else {
            // For other roles, fetch all dashboard data
         
        // Return dashboard view with all calculated data
        return view("pages.dashboard.dashboard", [
            "employeeList" => $employeeList,
            "quickLinks" => $quicklinks,
            
        ]);
    }
    }
}
