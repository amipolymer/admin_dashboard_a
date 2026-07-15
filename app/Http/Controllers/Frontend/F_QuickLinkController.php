<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuickLink;
use App\Models\RoleRoutePermission;
use Illuminate\Support\Facades\Auth;

class F_QuickLinkController extends Controller
{
     public function quickLink()
    {
        if(Auth::user()->role == 'superadmin'){
            $quickLinks =  Quicklink::where('status', 'active')->get();
            $permissionslink = null;
        }
        else{
        $permissionslink = RoleRoutePermission::where("role_name",Auth::user()->role)->first();
        $permissionslink->quick_link_id = json_decode($permissionslink->quick_link_id, true);
        $quickLinks =  Quicklink::where('status', 'active')->get();
        }
        
        return view('frontend.quickLink.index', compact('quickLinks','permissionslink'));
    }
}
