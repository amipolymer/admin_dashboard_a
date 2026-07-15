<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserLoginLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    /** Logs list/show: only records from the last 6 months. */
    protected function logsSince(): Carbon
    {
        return now()->subMonths(6)->startOfDay();
    }

    public function activityLogsIndex(Request $request)
    {
        $since = $this->logsSince();
        $query = ActivityLog::query()
            ->with('user')
            ->where('created_at', '>=', $since)
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('model')) {
            $query->where('model', $request->input('model'));
        }

        if ($request->filled('performed_by')) {
            $query->where('performed_by', $request->input('performed_by'));
        }

        if ($request->filled('from_date')) {
            $from = Carbon::parse($request->input('from_date'))->startOfDay();
            if ($from->greaterThan($since)) {
                $query->whereDate('created_at', '>=', $from);
            }
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $logs = $query->paginate($perPage)->withQueryString();
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $modelOptions = ActivityLog::modelOptions($since);

        $logsFromDate = $since->format('Y-m-d');
        $logsToDate = now()->format('Y-m-d');

        return view('pages.logs.activity-index', compact(
            'logs', 'users', 'modelOptions', 'perPage', 'logsFromDate', 'logsToDate', 'since'
        ));
    }

    public function activityLogShow(int $id)
    {
        $log = ActivityLog::with('user')
            ->where('created_at', '>=', $this->logsSince())
            ->findOrFail($id);

        return view('pages.logs.activity-show', compact('log'));
    }

    public function userLoginLogsIndex(Request $request)
    {
        $since = $this->logsSince();
        $query = UserLoginLog::query()
            ->with('user')
            ->where('login_at', '>=', $since)
            ->latest('login_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('from_date')) {
            $from = Carbon::parse($request->input('from_date'))->startOfDay();
            if ($from->greaterThan($since)) {
                $query->whereDate('login_at', '>=', $from);
            }
        }

        if ($request->filled('to_date')) {
            $query->whereDate('login_at', '<=', $request->input('to_date'));
        }

        if ($request->boolean('active_only')) {
            $query->whereNull('logout_at');
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $logs = $query->paginate($perPage)->withQueryString();
        $users = User::query()->where('role', '!=', 'superadmin')->orderBy('name')->get(['id', 'name', 'email']);

        $logsFromDate = $since->format('Y-m-d');
        $logsToDate = now()->format('Y-m-d');

        return view('pages.logs.login-index', compact(
            'logs', 'users', 'perPage', 'logsFromDate', 'logsToDate', 'since'
        ));
    }

    public function userLoginLogShow(int $id)
    {
        $log = UserLoginLog::with('user')
            ->where('login_at', '>=', $this->logsSince())
            ->findOrFail($id);

        return view('pages.logs.login-show', compact('log'));
    }
}
