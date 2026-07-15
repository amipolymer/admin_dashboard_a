<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RoleRoutePermission;
use App\Models\RouteURLList;
use App\Models\QuickLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UsersRoleListController extends Controller
{
    /**
     * Display a listing of the Users-roles.
     */
    public function index()
    {
        $roleList = RoleRoutePermission::where('status', '!=', 'deactivate')->get();
        return view('pages.users-roles.index', compact('roleList'));
    }

    /**
     * Show the form for creating a new User.
     */
    public function create()
    {
        $routeList = RouteURLList::all();
        $quicklinks = QuickLink::all();
        return view('pages.users-roles.create', compact('routeList', 'quicklinks'));
    }

    /**
     * Store a newly created User in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|max:255',
            'url_ids' => 'required|array',
            'url_ids.*' => 'integer',
            'quick_link_id' => 'array',
            'quick_link_id.*' => 'integer',
        ]);

        $userRoleStore = new RoleRoutePermission();
        $userRoleStore->role_name = $request->role_name;
        $userRoleStore->url_ids = json_encode(array_map('intval', $request->url_ids));
        $userRoleStore->quick_link_id = json_encode(array_map('intval', $request->quick_link_id));
        $userRoleStore->status = 'active';
        $userRoleStore->save();

        return redirect()->route('UersRole.Index')
            ->with('bg-color', 'success')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified User.
     */
    public function edit($id)
    {
        $roledata = RoleRoutePermission::findOrFail($id);
        $routeIds = array_map('strval', json_decode($roledata->url_ids ?? '[]', true));
        $routeList = RouteURLList::all();
        $quicklinks = QuickLink::all();
        $quicklinkid = array_map('strval', json_decode($roledata->quick_link_id ?? '[]', true));
        return view('pages.users-roles.edit', compact('roledata', 'routeList', 'routeIds', 'quicklinks', 'quicklinkid'));
    }

    /**
     * Display the specified User.
     */
    public function show($id)
    {
        $roledata = RoleRoutePermission::findOrFail($id);
        $routeList = RouteURLList::all();
        $quicklinks = QuickLink::all();
        return view('pages.users-roles.show', compact('roledata', 'routeList', 'quicklinks'));
    }

    /**
     * Update the specified User in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'role_name' => 'required|string|max:255',
            'url_ids' => 'required|array',
        ]);

        $userRoleUpdate = RoleRoutePermission::find($id);
        $previousRoleName = $userRoleUpdate->role_name;
        $userRoleUpdate->role_name = $request->role_name;
        $userRoleUpdate->url_ids = json_encode(array_map('intval', $request->url_ids));
        $userRoleUpdate->quick_link_id = json_encode(array_map('intval', $request->quick_link_id));
        $userRoleUpdate->save();

        Cache::forget('route_perm:role:' . $previousRoleName);
        Cache::forget('route_perm:role:' . $request->role_name);

        return redirect()->route('UersRole.Index')
            ->with('bg-color', 'success')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle the status of the specified User.
     */
    public function statusUpdate($id)
    {
        $user = RoleRoutePermission::findOrFail($id);
        $user->status = $user->status === 'deactivate' ? 'active' : 'deactivate';
        $user->save();

        return redirect()->route('UersRole.Index')
            ->with('bg-color', 'success')
            ->with('success', 'User status updated successfully.');
    }

    /**
     * Remove the specified User from storage.
     */
    public function delete($id)
    {
        $user = RoleRoutePermission::findOrFail($id);
        $user->status = 'deactivate';
        $user->save();

        return redirect()->route('UersRole.Index')
            ->with('bg-color', 'success')
            ->with('success', 'User deleted successfully.');
    }
}
