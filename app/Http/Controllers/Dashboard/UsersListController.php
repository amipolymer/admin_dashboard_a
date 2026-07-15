<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoleRoutePermission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Labour;
use Carbon\Carbon;

class UsersListController extends Controller
{
    /**
     * Display a listing of the Users.
     */
    public function index(Request $request)
    {
        $userListQuery = User::query();
        $dateRange = $request->input('date_range');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $today = Carbon::today();

        // Apply date range filters
        if ($dateRange) {
            match ($dateRange) {
                'last_30' => $userListQuery->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                                           ->whereDate('created_at', '<=', $today),
                'last_90' => $userListQuery->whereDate('created_at', '>=', Carbon::now()->subDays(90))
                                           ->whereDate('created_at', '<=', $today),
                'last_180' => $userListQuery->whereDate('created_at', '>=', Carbon::now()->subDays(180))
                                            ->whereDate('created_at', '<=', $today),
                'one_year' => $userListQuery->whereDate('created_at', '>=', Carbon::now()->subYear())
                                            ->whereDate('created_at', '<=', $today),
                'last_year' => $userListQuery->whereYear('created_at', Carbon::now()->subYear()->year)
                                             ->whereDate('created_at', '<=', $today),
                default => $this->applyCustomDateRange($userListQuery, $startDate, $endDate, $today),
            };
        }

        // Exclude closed and superadmin users
        $userList = $userListQuery->where('status', '!=', 'close')
                                   ->where('role', '!=', 'superadmin')
                                   ->get();

        return view('pages.employee.index', compact('userList'));
    }

    /**
     * Apply custom date range filter.
     */
    private function applyCustomDateRange($query, $startDate, $endDate, $today)
    {
        if ($startDate && $endDate) {
            $endDate = Carbon::parse($endDate)->gt($today) ? $today : $endDate;
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        return $query;
    }

    /**
     * Show the form for creating a new User.
     */
    public function create()
    {
        // Get active roles for dropdown
        $roleList = RoleRoutePermission::where('status', 'active')->get();
        return view('pages.employee.create', compact('roleList'));
    }

    /**
     * Store a newly created User in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        // Validate user input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phoneno' => 'required|unique:users,phoneno',
            'role' => 'required|string',
            'emp_id' => 'required|string|unique:users,emp_id',
        ]);

        // Create new user
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phoneno' => $validated['phoneno'],
            'emp_id' => $validated['emp_id'],
            'password_changed_at' => now(),
            'status' => 'active',
            'email_verified_at' => now(),
            'profile_image' => 'default',
            'remember_token' => Str::random(60),
        ]);

        return redirect()->route('Users.Index')
                         ->with('bg-color', 'success')
                         ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified User.
     */
    public function edit($id)
    {
        $userData = User::findOrFail($id);
        $roleList = RoleRoutePermission::where('status', 'active')->get();
        return view('pages.employee.edit', compact('userData', 'roleList'));
    }

    /**
     * Display the specified User.
     */
    public function show($id)
    {
        $userData = User::findOrFail($id);
        $roleList = RoleRoutePermission::where('status', 'active')->get();
        return view('pages.employee.show', compact('userData', 'roleList'));
    }

    /**
     * Update the specified User in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate user input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'phoneno' => 'required|string|unique:users,phoneno,' . $id,
            'role' => 'required|string',
            'is_locked' => 'required|boolean',
            'status' => 'required|in:active,deactivate,close',
        ]);

        // Update user data
        $userUpdate = User::findOrFail($id);
        $userUpdate->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phoneno' => $validated['phoneno'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'is_locked' => $validated['is_locked'],
        ]);

        // Update password if provided
        if ($request->filled('password')) {
            $userUpdate->update(['password' => Hash::make($validated['password'])]);
            $userUpdate->update(['password_changed_at' => now()]);
        }

        return redirect()->route('Users.Index')
                         ->with('bg-color', 'success')
                         ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle the status of the specified User.
     */
    public function statusUpdate($id)
    {
        $user = User::findOrFail($id);
        // Toggle between active and deactivate
        $user->status = $user->status === 'deactivate' ? 'active' : 'deactivate';
        $user->save();

        return redirect()->route('Users.Index')
                         ->with('bg-color', 'success')
                         ->with('success', 'User status updated successfully.');
    }

    /**
     * Soft delete the specified User.
     */
    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'close';
        $user->save();

        return redirect()->route('Users.Index')
                         ->with('bg-color', 'success')
                         ->with('success', 'User deleted successfully.');
    }
    
    // user update option 
    public function profileEdit($id)
    {
        $userData = User::findOrFail($id);
        $roleList = RoleRoutePermission::all();
        return view('pages.profile.edit', compact('userData', 'roleList'));
    }

    public function UserUpdate(Request $request, $id)
    {

           // Validate user input
         $validated = $request->validate(
              [
                  'password' => [
                      'nullable',
                      'string',
                      'min:8',
                      'max:15',
                      'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,15}$/'
                  ],
                  'phoneno' => 'required|string|unique:users,phoneno,' . $id,
                  'emp_id'  => 'required|string|unique:users,emp_id,' . $id,
              ],
              [
                  'password.regex' => 'Password must be 8–15 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.',
                  'password.min'   => 'Password must be at least 8 characters.',
                  'password.max'   => 'Password may not be greater than 15 characters.',
              ]
          );

        $userProfuleUpdate = User::findOrFail($id);
               // Update password if provided
        if ($request->filled('password')) {
            $userProfuleUpdate->update(['password' => Hash::make($validated['password'])]);
            $userProfuleUpdate->update(['password_changed_at' => now()]);
        }
        $userProfuleUpdate->emp_id = $validated['emp_id'];
        $userProfuleUpdate->phoneno = $validated['phoneno'];

        // $userProfuleUpdate->save();

        return redirect()->route('profile.edit', $id)
                         ->with('bg-color', 'success')
                         ->with('success', 'User Data updated successfully.');
    }
    
}
