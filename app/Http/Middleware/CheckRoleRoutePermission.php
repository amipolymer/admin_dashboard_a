<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RouteURLList;
use App\Models\RoleRoutePermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CheckRoleRoutePermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, "Unauthorized");
        }

        // ✅ Superadmin gets all access
        if ($user->role === "superadmin") {
            return $next($request);
        }
 
        // Get current request path
        $currentPath = $request->path(); // e.g. dashboard-leads/leads-sheets/1
        // dd($currentPath);

        $paremert = [
            "active",
            "close",
            "standby",
            "unupdated",
            "completed",
            "visits",
            "estimates",
            "converted",
        ];
        // $paremert = 'active';
        $segments = explode("/", $currentPath);
        if (isset($segments[2])) {
            if (in_array($segments[2], $paremert)) {
                $segments[2] = "{id}";
            }
        }
        foreach ($segments as $index => $segment) {
            if (is_numeric($segment)) {
                $segments[$index] = "{id}";
            }
        }
        $normalizedPath = implode("/", $segments);

        $route = Cache::remember(
            'route_perm:url:' . $normalizedPath,
            now()->addHour(),
            fn () => RouteURLList::where('url_name', $normalizedPath)->first()
        );

        if (!$route) {
            abort(403, "URL not registered.");
        }

        $urlIds = Cache::remember(
            'route_perm:role:' . $user->role,
            now()->addHour(),
            function () use ($user) {
                $permissions = RoleRoutePermission::where('role_name', $user->role)->first();
                if (!$permissions) {
                    return [];
                }

                return json_decode($permissions->url_ids, true) ?? [];
            }
        );

        if (!in_array($route->id, $urlIds, true)) {
            abort(403, "You do not have permission to access this URL.");
        }

        return $next($request);
    }
}
