<?php

use App\Http\Controllers\OnboardAssistant\EmployeeNewJoinerController;
use App\Http\Controllers\OnboardAssistant\NewJoinerUploadDocumentController;
use App\Http\Controllers\OnboardAssistant\SrHrApprovalController;

use Illuminate\Support\Facades\Route;

// Candidate onboarding portal
Route::get('/onboarding/{token}', [NewJoinerUploadDocumentController::class, 'portal'])->name('onboarding.portal');
Route::post('/onboarding/{token}', [NewJoinerUploadDocumentController::class, 'portalSave'])->name('onboarding.save');
Route::get('/onboarding/{token}/policy/{key}', [NewJoinerUploadDocumentController::class, 'streamPolicyFile'])->name('onboarding.policy.file');
Route::get('/onboarding-policy/{key}', fn () => abort(403, 'Use your onboarding portal link to view company policies.'))->name('onboarding.policy.legacy');
Route::get('/onboarding/{token}/upload-documents', [NewJoinerUploadDocumentController::class, 'uploadForm'])->name('onboarding.upload.form');

// Legacy document routes (unchanged)
Route::get('/Mph6MSf9L4NU4NWRRuxTYAvZM/{id}', [NewJoinerUploadDocumentController::class, 'index'])->name('Newjoiner.uploaddocument');
Route::post('/Mph6MSf9L4NU4NWRRuxTYAvZM', [NewJoinerUploadDocumentController::class, 'store'])->name('employee.documents.upload');
Route::delete('/Mph6MSf9L4NU4NWRRuxTYAvZM/{id}', [NewJoinerUploadDocumentController::class, 'delete'])->name('employee.documents.delete');
Route::post('/Mph6MSf9L4NU4NWRRuxTYAvZM-submited/{id}', [NewJoinerUploadDocumentController::class, 'UserUpdate'])->name('employee.documents.Submited');
Route::get('/Mph6MSf9L4NU4NWRRuxTYAvZM-edit/{url}/{id}', [NewJoinerUploadDocumentController::class, 'EditDocuments'])->name('employee.documents.edit');
Route::post('/Mph6MSf9L4NU4NWRRuxTYAvZM-update/{id}', [NewJoinerUploadDocumentController::class, 'UpdateDocuments'])->name('employee.documents.update');
Route::get('/Mph6MSf9L4NU4NWRRuxTYAvZM/{id}/{file}', [NewJoinerUploadDocumentController::class, 'ViewFile'])->name('employee.documents.ViewFile');
// Offer OR Appointment letter routes
Route::get('/offer-letter/view/{id}', [EmployeeNewJoinerController::class, 'viewOffer'])->name('EmployeeJoiner.documents.viewOffer');
Route::get('/offer-letter/preview/{id}', [EmployeeNewJoinerController::class, 'previewOffer'])->name('offer.preview.pdf');
Route::post('/offer-letter/store-signature', [EmployeeNewJoinerController::class, 'storeSignature'])->name('employee.documents.storeSignature');
Route::post('/offer-letter/reject', [EmployeeNewJoinerController::class, 'rejectOffer'])->name('employee.documents.rejectOffer');
Route::get('/appointment-letter/view/{id}', [EmployeeNewJoinerController::class, 'viewAppointment'])->name('EmployeeJoiner.documents.viewAppointment');
Route::get('/appointment-letter/preview/{id}', [EmployeeNewJoinerController::class, 'previewAppointment'])->name('appointment.preview.pdf');
Route::get('/appointment-letter/signed-pdf/{id}', [EmployeeNewJoinerController::class, 'downloadSignedAppointmentPdf'])->name('appointment.signed.pdf');
Route::post('/appointment-letter/store-signature', [EmployeeNewJoinerController::class, 'storeAppointmentSignature'])->name('employee.documents.storeAppointmentSignature');
Route::post('/appointment-letter/reject', [EmployeeNewJoinerController::class, 'rejectAppointment'])->name('employee.documents.rejectAppointment');
Route::get('/thank-you', [NewJoinerUploadDocumentController::class, 'thankyou'])->name('employee.documents.thankyou');
// SR-HR approval routes
Route::get('/sr-hr/approval/{token}', [SrHrApprovalController::class, 'show'])->where('token', '[A-Za-z0-9]{32,128}')->name('sr-hr.approval.show');
Route::get('/sr-hr/approval/{token}/document/{document}', [SrHrApprovalController::class, 'viewDocument'])
    ->where('token', '[A-Za-z0-9]{32,128}')
    ->where('document', '[0-9]+')
    ->name('sr-hr.approval.document');
Route::post('/sr-hr/approval/{token}', [SrHrApprovalController::class, 'decide'])->where('token', '[A-Za-z0-9]{32,128}')->name('sr-hr.approval.decide');
// HR onboarding step routes
Route::middleware(['auth', 'check.permission'])->group(function () {
    Route::get('/onboard-assistant/new-employee-list', [EmployeeNewJoinerController::class, 'index'])->name('EmployeeJoiner.Index');
    Route::get('/onboard-assistant/new-employee-list/create', [EmployeeNewJoinerController::class, 'create'])->name('EmployeeJoiner.Create');
    Route::post('/onboard-assistant/new-employee-list/store', [EmployeeNewJoinerController::class, 'store'])->name('EmployeeJoiner.Store');
    Route::get('/onboard-assistant/new-employee-list/edit/{id}', [EmployeeNewJoinerController::class, 'edit'])->name('EmployeeJoiner.Edit');
    Route::get('/onboard-assistant/new-employee-list/show/{id}', [EmployeeNewJoinerController::class, 'show'])->name('EmployeeJoiner.Show');
    Route::put('/onboard-assistant/new-employee-list/update/{id}', [EmployeeNewJoinerController::class, 'update'])->name('EmployeeJoiner.Update');
    Route::put('/onboard-assistant/new-employee-list/doc-update/{id}', [EmployeeNewJoinerController::class, 'updateDocument'])->name('EmployeeJoiner.updateDocument');
    Route::post('/onboard-assistant/new-employee-list/{id}/hr-reupload-document', [EmployeeNewJoinerController::class, 'hrReuploadDocument'])->name('EmployeeJoiner.hrReuploadDocument');
    Route::get('/onboard-assistant/new-employee-list/status/{id}', [EmployeeNewJoinerController::class, 'statusUpdate'])->name('EmployeeJoiner.StatusUpdate');
    Route::get('/onboard-assistant/new-employee-list/delete/{id}', [EmployeeNewJoinerController::class, 'delete'])->name('EmployeeJoiner.Delete');
    Route::get('/onboard-assistant/new-employee-list-verify/{id}', [EmployeeNewJoinerController::class, 'DocVerify'])->name('employee.documents.verify');
    Route::get('/onboard-assistant/new-employee-list-resendemail/{id}/{type}/{remarkId}', [EmployeeNewJoinerController::class, 'reSendEmail'])->name('employee.documents.reSendEmail');
    Route::get('/onboard-assistant/new-employee-list/download_all/{id}', [EmployeeNewJoinerController::class, 'downloadAll'])->name('EmployeeJoiner.documents.downloadAll');
    Route::post('/onboard-assistant/new-employee-list/{id}/onboarding-step', [EmployeeNewJoinerController::class, 'onboardingStep'])->name('EmployeeJoiner.onboardingStep');
    Route::post('/onboard-assistant/new-employee-list/{id}/revoke-document-reedit', [EmployeeNewJoinerController::class, 'revokeDocumentReedit'])->name('EmployeeJoiner.revokeDocumentReedit');
    Route::post('/onboard-assistant/new-employee-list/{id}/finalize', [EmployeeNewJoinerController::class, 'finalizeOnboarding'])->name('EmployeeJoiner.finalize');
    Route::get('/onboard-assistant/new-employee-list/{id}/letter-test/{type}', [EmployeeNewJoinerController::class, 'letterPdfTest'])
        ->where('type', 'offer|appointment')
        ->name('EmployeeJoiner.letterPdfTest');
});
