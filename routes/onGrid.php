<?php

use App\Http\Controllers\OnGridWeb\OnGridWebController;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'check.permission'])->group(function () {

    //    OnGrid Web Controller List
    Route::post('/onboard-assistant/new-employee-list/bvglink', [OnGridWebController::class, 'bvgLink'])->name('EmployeeJoiner.documents.bvgLink');
    // Route::get('/onboard-assistant/new-employee-list/bvglink/{id}', [OnGridWebController::class, 'bvgLink'])->name('EmployeeJoiner.documents.bvgLink');
    Route::get('/onboard-assistant/new-employee-list/get-status/{id}', [OnGridWebController::class, 'getStatus'])->name('EmployeeJoiner.documents.getStatus');
    Route::get('/onboard-assistant/new-employee-list/get-list', [OnGridWebController::class, 'getList'])->name('EmployeeJoiner.documents.getList');
    Route::get('/onboard-assistant/new-employee-list/ongrid-invite', [OnGridWebController::class, 'ongridInviteList'])->name('EmployeeJoiner.ongridInviteList');
    Route::get('/onboard-assistant/new-employee-list/ongrid-invite/{invited}', [OnGridWebController::class, 'ongridInviteShow'])->name('EmployeeJoiner.ongridInviteShow');
    Route::get('/onboard-assistant/new-employee-list/ongrid-invite-delete/{id}', [OnGridWebController::class, 'deleteInvite'])->name('EmployeeJoiner.deleteInvite');
    Route::get('/onboard-assistant/new-employee-list/get-offering-list', [OnGridWebController::class, 'getofferingList'])->name('EmployeeJoiner.getofferingList');
});
