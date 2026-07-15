<?php

use App\Http\Controllers\AnnualReport\AnnualReportViewFormController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\QuickLinkController;
use App\Http\Controllers\Dashboard\UsersListController;
use App\Http\Controllers\Dashboard\RouteUrlListController;
use App\Http\Controllers\Dashboard\SystemLogController;
use App\Http\Controllers\Dashboard\UsersRoleListController;
use App\Http\Controllers\Frontend\F_QuickLinkController;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
      if (Auth::check()) {
        // Example: role-based redirect
        if (in_array(Auth::user()->role, ['representative'])) {
            
        }
 
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::middleware(['auth', 'check.permission'])->group(function () {
    Route::get('/quick-link', [F_QuickLinkController::class, 'quickLink'])->name('quickLink');
    
    });
    
    Route::middleware(['auth', 'check.permission'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // quick-link 
        Route::get('/master-entry/quick-link', [QuickLinkController::class, 'index'])->name('Quicklink.Index');
        Route::get('/master-entry/quick-link/create', [QuickLinkController::class, 'create'])->name('Quicklink.Create');
        Route::post('/master-entry/quick-link/store', [QuickLinkController::class, 'store'])->name('Quicklink.Store');
        Route::get('/master-entry/quick-link/edit/{id}', [QuickLinkController::class, 'edit'])->name('Quicklink.Edit');
        Route::get('/master-entry/quick-link/show/{id}', [QuickLinkController::class, 'show'])->name('Quicklink.Show');
        Route::put('/master-entry/quick-link/update/{id}', [QuickLinkController::class, 'update'])->name('Quicklink.Update');
        Route::get('/master-entry/quick-link/status/{id}', [QuickLinkController::class, 'statusUpdate'])->name('Quicklink.StatusUpdate');
        Route::get('/master-entry/quick-link/delete/{id}', [QuickLinkController::class, 'delete'])->name('Quicklink.Delete');
        // employee-sheet 
        Route::get('/master-entry/employee-list', [UsersListController::class, 'index'])->name('Users.Index');
        Route::get('/master-entry/employee-list/create', [UsersListController::class, 'create'])->name('Users.Create');
        Route::post('/master-entry/employee-list/store', [UsersListController::class, 'store'])->name('Users.Store');
        Route::get('/master-entry/employee-list/edit/{id}', [UsersListController::class, 'edit'])->name('Users.Edit');
        Route::get('/master-entry/employee-list/show/{id}', [UsersListController::class, 'show'])->name('Users.Show');
        Route::put('/master-entry/employee-list/update/{id}', [UsersListController::class, 'update'])->name('Users.Update');
        Route::get('/master-entry/employee-list/status/{id}', [UsersListController::class, 'statusUpdate'])->name('Users.StatusUpdate');
        Route::get('/master-entry/employee-list/delete/{id}', [UsersListController::class, 'delete'])->name('Users.Delete');
        Route::get('/dashboard/profile/edit/{id}', [UsersListController::class, 'profileEdit'])->name('profile.edit');
        Route::put('/dashboard/profile/update/{id}', [UsersListController::class, 'UserUpdate'])->name('profile.UserUpdate');
        // user-sheet 
        Route::get('/master-entry/user-role', [UsersRoleListController::class, 'index'])->name('UersRole.Index');
        Route::get('/master-entry/user-role/create', [UsersRoleListController::class, 'create'])->name('UersRole.Create');
        Route::post('/master-entry/user-role/store', [UsersRoleListController::class, 'store'])->name('UersRole.Store');
        Route::get('/master-entry/user-role/edit/{id}', [UsersRoleListController::class, 'edit'])->name('UersRole.Edit');
        Route::get('/master-entry/user-role/show/{id}', [UsersRoleListController::class, 'show'])->name('UersRole.Show');
        Route::put('/master-entry/user-role/update/{id}', [UsersRoleListController::class, 'update'])->name('UersRole.Update');
        Route::get('/master-entry/user-role/status/{id}', [UsersRoleListController::class, 'statusUpdate'])->name('UersRole.StatusUpdate');
        Route::get('/master-entry/user-role/delete/{id}', [UsersRoleListController::class, 'delete'])->name('UersRole.Delete');

        // AnnualReportViewForm
        Route::get('/annual-report/view-form', [AnnualReportViewFormController::class, 'index'])->name('AnnualReportViewForm.Index');
        Route::get('/annual-report/view-form/create', [AnnualReportViewFormController::class, 'create'])->name('AnnualReportViewForm.Create');
        Route::post('/annual-report/view-form/store', [AnnualReportViewFormController::class, 'store'])->name('AnnualReportViewForm.Store');
        Route::get('/annual-report/view-form/edit/{id}', [AnnualReportViewFormController::class, 'edit'])->name('AnnualReportViewForm.Edit');
        Route::get('/annual-report/view-form/show/{id}', [AnnualReportViewFormController::class, 'show'])->name('AnnualReportViewForm.Show');
        Route::put('/annual-report/view-form/update/{id}', [AnnualReportViewFormController::class, 'update'])->name('AnnualReportViewForm.Update');
        Route::get('/annual-report/view-form/status/{id}', [AnnualReportViewFormController::class, 'statusUpdate'])->name('AnnualReportViewForm.StatusUpdate');
        Route::get('/annual-report/view-form/export', [AnnualReportViewFormController::class, 'export'])->name('AnnualReportViewForm.export');
        Route::get('/annual-report/view-form/delete/{id}', [AnnualReportViewFormController::class, 'delete'])->name('AnnualReportViewForm.Delete');

        // System logs (activity + user login)
        Route::get('/master-entry/logs/active-logs', [SystemLogController::class, 'activityLogsIndex'])->name('Log.Activity.Index');
        Route::get('/master-entry/logs/active-logs/{id}', [SystemLogController::class, 'activityLogShow'])->name('Log.Activity.Show');
        Route::get('/master-entry/logs/user-login-logs', [SystemLogController::class, 'userLoginLogsIndex'])->name('Log.Login.Index');
        Route::get('/master-entry/logs/user-login-logs/{id}', [SystemLogController::class, 'userLoginLogShow'])->name('Log.Login.Show');

        // Route registry (permission URLs in DB)
        Route::get('/master-entry/logs/route-list', [RouteUrlListController::class, 'index'])->name('Log.Routes.Index');
        Route::get('/master-entry/logs/route-list/create', [RouteUrlListController::class, 'create'])->name('Log.Routes.Create');
        Route::post('/master-entry/logs/route-list/store', [RouteUrlListController::class, 'store'])->name('Log.Routes.Store');
        Route::get('/master-entry/logs/route-list/show/{id}', [RouteUrlListController::class, 'show'])->name('Log.Routes.Show');
        Route::get('/master-entry/logs/route-list/edit/{id}', [RouteUrlListController::class, 'edit'])->name('Log.Routes.Edit');
        Route::put('/master-entry/logs/route-list/update/{id}', [RouteUrlListController::class, 'update'])->name('Log.Routes.Update');
        Route::delete('/master-entry/logs/route-list/delete/{id}', [RouteUrlListController::class, 'destroy'])->name('Log.Routes.Delete');
        Route::post('/master-entry/logs/route-list/sync', [RouteUrlListController::class, 'syncFromApp'])->name('Log.Routes.Sync');
    });
        
require __DIR__.'/auth.php';
require __DIR__.'/onGrid.php';
require __DIR__.'/onboardAssistant.php';