<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RouteURLList;
use App\Models\RoleRoutePermission;
use Illuminate\Support\Facades\Auth;

class CheckRoleRoutePermission
{
    public function handle(Request $request, Closure $next)
    {
       $user = Auth::user();

    if (!$user) {
        abort(403, 'Unauthorized');
    }

    // ✅ Superadmin gets all access
    if ($user->role === 'superadmin') {
        return $next($request);
    }

    // Get current request path
    $currentPath = $request->path(); // e.g. dashboard-leads/leads-sheets/1

    // Normalize the path by removing numeric segments
    $normalizedPath = preg_replace('/\/\d+$/', '/{id}', $currentPath);

    // Try finding route
    $route = RouteURLList::where('url_name', $normalizedPath)->first();

    if (!$route) {
        abort(403, 'Route not registered.');
    }

    // Get role permissions
    $permissions = RoleRoutePermission::where('role_name', $user->role)->first();
    $permissions->url_ids = json_decode($permissions->url_ids, true);

    if (!$permissions || !in_array($route->id, $permissions->url_ids ?? [])) {
        abort(403, 'You do not have permission to access this route.');
    }

        return $next($request);
    }
}
