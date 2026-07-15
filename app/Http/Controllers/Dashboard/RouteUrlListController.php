<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RoleRoutePermission;
use App\Models\RouteURLList;
use App\Support\RouteRegistrySync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RouteUrlListController extends Controller
{
    public function index()
    {
        $routeList = RouteURLList::query()->orderBy('url_name')->get();

        return view('pages.logs.routes.index', compact('routeList'));
    }

    public function create()
    {
        return view('pages.logs.routes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'url_name' => 'required|string|max:500|unique:route_u_r_l_lists,url_name',
            'title' => 'required|string|max:255',
        ]);

        RouteURLList::create([
            'url_name' => trim($request->url_name),
            'title' => trim($request->title),
        ]);

        return redirect()->route('Log.Routes.Index')
            ->with('bg-color', 'success')
            ->with('success', 'Route registered successfully.');
    }

    public function show(int $id)
    {
        $route = RouteURLList::findOrFail($id);
        $rolesUsing = $this->rolesUsingRoute($id);

        return view('pages.logs.routes.show', compact('route', 'rolesUsing'));
    }

    public function edit(int $id)
    {
        $route = RouteURLList::findOrFail($id);

        return view('pages.logs.routes.edit', compact('route'));
    }

    public function update(Request $request, int $id)
    {
        $route = RouteURLList::findOrFail($id);

        $request->validate([
            'url_name' => [
                'required',
                'string',
                'max:500',
                Rule::unique('route_u_r_l_lists', 'url_name')->ignore($route->id),
            ],
            'title' => 'required|string|max:255',
        ]);

        $route->update([
            'url_name' => trim($request->url_name),
            'title' => trim($request->title),
        ]);

        return redirect()->route('Log.Routes.Index')
            ->with('bg-color', 'success')
            ->with('success', 'Route updated successfully.');
    }

    public function destroy(int $id)
    {
        if ($this->rolesUsingRoute($id)->isNotEmpty()) {
            return redirect()->route('Log.Routes.Index')
                ->with('bg-color', 'danger')
                ->with('success', 'Cannot delete: route is assigned to one or more user roles. Remove it from role permissions first.');
        }

        RouteURLList::findOrFail($id)->delete();

        return redirect()->route('Log.Routes.Index')
            ->with('bg-color', 'success')
            ->with('success', 'Route deleted successfully.');
    }

    public function syncFromApp()
    {
        $result = RouteRegistrySync::syncFromApplication();

        $message = sprintf(
            '%d new route(s) saved to the database. %d already existed (unchanged). The table below shows all %d route(s) in the database.',
            $result['created'],
            $result['skipped'],
            $result['total_in_db']
        );

        return redirect()->route('Log.Routes.Index')
            ->with('bg-color', 'success')
            ->with('success', $message);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function rolesUsingRoute(int $routeId)
    {
        return RoleRoutePermission::query()
            ->get()
            ->filter(fn ($role) => in_array($routeId, $role->url_ids ?? [], true))
            ->pluck('role_name')
            ->values();
    }
}
