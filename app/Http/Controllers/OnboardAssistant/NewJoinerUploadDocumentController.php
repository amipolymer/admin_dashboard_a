<?php

namespace App\Http\Controllers\OnboardAssistant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use App\Models\User;
use Illuminate\Support\Str;
use App\Data\DocumentNamesList;
use App\Data\CompanyPolicyDocuments;
use App\Support\Form25LeaveNomination;
use App\Support\GratuityNominationPrefill;
use App\Data\IndianCities;
use App\Data\JoiningFormSchema;
use App\Data\ProfileFormSchema;
use App\Support\OnboardingDocumentReedit;
use App\Support\OnboardingDocumentDeadline;
use App\Support\OnboardingDocumentRequirements;
use App\Support\OnboardingFileRules;
use App\Support\OnboardingLetterDocument;
use App\Support\OnboardingMail;
use App\Support\OnboardingJoiningDraft;
use App\Support\OnboardingJoiningPrefill;
use App\Support\OnboardingMediclaimDocuments;
use App\Support\OnboardingProfileDraft;
use App\Support\OnboardingSignature;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class NewJoinerUploadDocumentController extends Controller
{
    public function index($id)
    {
        return redirect()->route('onboarding.portal', ['token' => $id, 'tab' => 'document']);
    }

    /** Redirect legacy upload URL to portal document tab */
    public function uploadForm($id)
    {
        return redirect()->route('onboarding.portal', ['token' => $id, 'tab' => 'document']);
    }

    protected function candidateMayModifyDocuments(EmployeesNewJoiner $employee): bool
    {
        if (!$employee) {
            return false;
        }

        if (OnboardingDocumentReedit::isAllowed($employee)) {
            return $employee->emp_status !== 'inactive';
        }

        if ($employee->emp_status !== 'active') {
            return false;
        }

        if (!in_array($employee->emp_document_status, ['process', 'rejected'], true)) {
            return false;
        }

        return !OnboardingDocumentDeadline::isExpired($employee);
    }

    protected function documentUploadBlockMessage(EmployeesNewJoiner $employee, ?string $documentType = null): ?string
    {
        if ($documentType && OnboardingDocumentReedit::isAllowed($employee)
            && !OnboardingDocumentReedit::canUploadType($employee, $documentType)) {
            return 'HR has not allowed re-upload for this document type.';
        }

        return $this->documentEligibilityMessage($employee);
    }

    protected function documentEligibilityMessage(EmployeesNewJoiner $employee): ?string
    {
        if (!$employee) {
            return 'You are not eligible to re-submit documents. Please contact HR.';
        }

        if (OnboardingDocumentReedit::isAllowed($employee)) {
            return null;
        }

        if ($employee->emp_status !== 'active') {
            return 'You are not eligible to re-submit documents. Please contact HR.';
        }

        if (OnboardingDocumentDeadline::isPortalBlocked($employee)) {
            return OnboardingDocumentDeadline::blockedMessage() . ' Please contact HR.';
        }

        if (!in_array($employee->emp_document_status, ['process', 'rejected'])) {
            return 'Documents already submitted. No further upload is required unless HR requests re-upload.';
        }
        if (OnboardingDocumentDeadline::isExpired($employee)) {
            return 'The submission due date has expired. Please contact HR.';
        }

        return null;
    }

    protected function employeeHasEmergencyContactUploaded(EmployeesNewJoiner $employee): bool
    {
        if (trim((string) $employee->emergency_contact) !== '') {
            return true;
        }

        return NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->where('emp_select_document', 'emergency_contact')
            ->exists();
    }

    /** @return array<string, mixed> */
    protected function emergencyContactValidationRules(EmployeesNewJoiner $employee): array
    {
        return [
            'emergency_contact' => [
                'required_if:document_type,emergency_contact',
                'nullable',
                'string',
                'max:255',
                Rule::unique('employees_new_joiners', 'emergency_contact')->ignore($employee->id),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function emergencyContactValidationMessages(): array
    {
        return [
            'emergency_contact.required_if' => 'Emergency contact number is required.',
            'emergency_contact.unique' => 'This emergency contact number is already registered for another candidate. Please use a different number or contact HR.',
        ];
    }

    protected function saveEmployeeEmergencyContact(EmployeesNewJoiner $employee, string $contact): void
    {
        try {
            $employee->emergency_contact = trim($contact);
            $employee->save();
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw ValidationException::withMessages([
                    'emergency_contact' => 'This emergency contact number is already registered for another candidate. Please use a different number or contact HR.',
                ]);
            }

            throw $e;
        }
    }

    protected function rejectIfEmergencyContactAlreadySubmitted(EmployeesNewJoiner $employee): ?\Illuminate\Http\RedirectResponse
    {
        if (!$this->employeeHasEmergencyContactUploaded($employee)) {
            return null;
        }

        return back()
            ->with('error', 'Emergency contact is already submitted. Use Re-upload in the uploaded documents list to update it.')
            ->withInput();
    }

    protected function rejectIfDocumentPortalBlocked(EmployeesNewJoiner $employee)
    {
        if (OnboardingDocumentReedit::isAllowed($employee)) {
            return null;
        }

        if (OnboardingDocumentDeadline::isPortalBlocked($employee)) {
            return redirect()
                ->route('onboarding.portal', ['token' => $employee->emp_url])
                ->with('error', OnboardingDocumentDeadline::blockedMessage());
        }

        return null;
    }

    public function EditDocuments($url, $id)
    {
        $id_ = decrypt($id);
        $main_url = config('app.main_url');
        $messages = [
            "You are not eligible to re-submit your document. Please contact HR for assistance.",
            "Your document has already been submitted. No further action is required.",
            "The submission due date for your document has expired. Please contact HR for guidance.",
        ];
        $status = "";
        $message = "";
        $documentEdit = NewEmployeesDocument::findOrFail($id_);
        $employee = EmployeesNewJoiner::where("id", $documentEdit->emp_id)->first();
        $document_list = NewEmployeesDocument::where("emp_id",$employee->id)->get();
        /**
         * 1. Employee not found or inactive
         */
        if (!$employee) {
            $status = "error";
            $message = $messages[0];
        }
        elseif ($employee->emp_status === 'inactive') {
            $status = "error";
            $message = $messages[0];
        }
        /**
         * 2. Document already submitted
         */
        elseif (
            !$this->candidateMayModifyDocuments($employee)
        ) {
            $status = "error";
            $message = $messages[1];
        }
        /**
         * 3. Due date expired
         */
        elseif (
            !OnboardingDocumentReedit::isAllowed($employee)
            && OnboardingDocumentDeadline::isExpired($employee)
        ) {
            $status = "error";
            $message = $messages[2];
        }
        /**
         * 4. All good → eligible to submit
         */
        else {
            $status = "success";
            $message = "You are eligible to submit your document.";
        }

        return redirect()->route('onboarding.portal', [
            'token' => $employee->emp_url,
            'tab' => 'document',
            'edit' => encrypt($documentEdit->id),
        ]);
    }

    // Save File document in db
    public function store(Request $request)
    {
        // Fetch employee using emp_url
        $employee = EmployeesNewJoiner::findOrFail($request->emp_id);

        if ($blocked = $this->rejectIfDocumentPortalBlocked($employee)) {
            return $blocked;
        }

        $request->validate(
            array_merge([
                'document_type' => 'required|string',
                'document_file' => 'required_if:document_type,!emergency_contact|nullable|file',
            ], $this->emergencyContactValidationRules($employee)),
            $this->emergencyContactValidationMessages()
        );

        $docType = $request->document_type;

        if ($block = $this->documentUploadBlockMessage($employee, $docType)) {
            return back()->with('error', $block)->withInput();
        }

        if (DocumentNamesList::isHrManagedKey($docType)) {
            return back()->with('error', 'This document type cannot be uploaded here. Use the correct section in the portal or contact HR.')->withInput();
        }

        if ($docType === 'emergency_contact') {
            if ($redirect = $this->rejectIfEmergencyContactAlreadySubmitted($employee)) {
                return $redirect;
            }
        }

        if ($docType !== 'emergency_contact') {
            $fileRule = OnboardingFileRules::resolveDocumentFileRule($employee, $docType);
            if ($fileRule === null) {
                return back()->withErrors([
                    'document_type' => 'Invalid document type selected',
                ]);
            }

            $request->validate(
                [
                    'document_file' => $fileRule,
                ],
                [
                    'document_file.mimes' => OnboardingFileRules::mimeErrorMessage($docType),
                    'document_file.max' => 'File is too large. Max: ' . OnboardingFileRules::maxSizeLabel($docType),
                ]
            );
        }

        if ($request->document_type === 'emergency_contact') {
            $this->saveEmployeeEmergencyContact($employee, (string) $request->emergency_contact);
        } else {
            $folder = "temp/employees/EMP_{$employee->id}";
            $file = $request->file("document_file");
            $extension = $file->getClientOriginalExtension();

            $fileName = OnboardingFileRules::storedFileName($employee->id, $request->document_type, $extension);

            $filePath = Storage::disk("public")->putFileAs(
                $folder,
                $file,
                $fileName
            );
        }

        $data = [
            "emp_doc_date" => Carbon::now(),
        ];

        if ($request->document_type === "emergency_contact") {
            $data["emp_document_file"] = $request->emergency_contact;
            $data["emp_document_file_path"] = '-';
            $data["emp_hr_id"] = $employee->emp_hr_id;
            $data["approval_date"] = null;
        } else {
            $data["emp_document_file"] = $fileName;
            $data["emp_document_file_path"] = "storage/" . $filePath;
            $data["emp_document_status"] = "upload";
            $data["emp_hr_id"] = $employee->emp_hr_id;
            $data["approval_date"] = null;
            $data["rejection_reason"] = null;
        }

        NewEmployeesDocument::updateOrCreate(
            [
                "emp_id" => $employee->id,
                "emp_select_document" => $request->document_type,
            ],
            $data
        );

        if (!$employee->emp_folder) {
            $employee->emp_folder = "EMP_$employee->id";
            $employee->emp_folder_path = 'temp/employees/';
            $employee->save();
        }
        // }
        return $this->redirectToPortalDocumentTab($employee, [
            "success" => "Documents uploaded successfully",
            "bg-color" => "success",
        ]);
    }

    public function UpdateDocuments(Request $request, $id)
    {
        // Fetch existing document
        $document = NewEmployeesDocument::findOrFail($id);

        // Fetch employee
        $employee = EmployeesNewJoiner::findOrFail($document->emp_id);

        if ($blocked = $this->rejectIfDocumentPortalBlocked($employee)) {
            return $blocked;
        }

        if ($block = $this->documentUploadBlockMessage($employee, $document->emp_select_document)) {
            return back()->with('error', $block);
        }

        if (DocumentNamesList::isHrManagedKey($document->emp_select_document)) {
            return back()->with('error', 'This document cannot be re-uploaded from the Documents tab.');
        }

        $request->validate(
            array_merge([
                'document_type' => 'required|string',
                'document_file' => 'required_if:document_type,!emergency_contact|nullable|file',
            ], $this->emergencyContactValidationRules($employee)),
            $this->emergencyContactValidationMessages()
        );

        /**
         * Same validation rules as store()
         */
        $documentType = $document->emp_select_document;

        if ($documentType !== 'emergency_contact') {
            $fileRule = OnboardingFileRules::resolveDocumentFileRule($employee, $documentType);

            if ($fileRule === null) {
                return back()->withErrors([
                    'document_file' => 'Invalid document type',
                ]);
            }

            $request->validate(
                [
                    'document_file' => $fileRule,
                ],
                [
                    'document_file.mimes' => OnboardingFileRules::mimeErrorMessage($documentType),
                    'document_file.max' => 'File is too large. Max: ' . OnboardingFileRules::maxSizeLabel($documentType),
                ]
            );
        }

        if ($request->document_type === 'emergency_contact') {
            $this->saveEmployeeEmergencyContact($employee, (string) $request->emergency_contact);
        } else {
            // Upload file (same naming logic)
            $folder = "temp/employees/EMP_{$employee->id}";
            $file = $request->file("document_file");
            $extension = $file->getClientOriginalExtension();

            $fileName = OnboardingFileRules::storedFileName($employee->id, $documentType, $extension);

            $filePath = Storage::disk("public")->putFileAs(
                $folder,
                $file,
                $fileName
            );
        }

        $updateData = [
            "emp_doc_date" => Carbon::now(),
            "approval_date" => Carbon::now(),
            "rejection_reason" => null,
        ];

        if ($request->document_type === "emergency_contact") {
            $updateData["emp_document_file"] = $request->emergency_contact;
            $updateData["emp_document_file_path"] = '-';
        } else {
            $updateData["emp_document_file"] = $fileName;
            $updateData["emp_document_file_path"] = "storage/" . $filePath;
            $updateData["emp_document_status"] = "upload";
        }

        // Update document
        $document->update($updateData);
        // Ensure employee folder exists
        if (!$employee->emp_folder) {
            $employee->emp_folder = "EMP_{$employee->id}";
            $employee->emp_folder_path = 'temp/employees/';
            $employee->save();
        }

        return $this->redirectToPortalDocumentTab($employee, [
            "success" => "Document re-uploaded successfully",
            "bg-color" => "success",
        ]);
    }

    /**
     * Keep redirects on the same host/port the candidate is using
     * (avoids APP_URL host mismatch → ERR_ACCESS_DENIED after upload).
     */
    protected function redirectToPortalDocumentTab(EmployeesNewJoiner $employee, array $with = [])
    {
        $path = route('onboarding.portal', [
            'token' => $employee->emp_url,
            'tab' => 'document',
        ], absolute: false);

        return redirect()->to($path)->with($with);
    }

    // Confirm document upload (portal Documents tab)
    public function UserUpdate($id)
    {
        $employee = EmployeesNewJoiner::where('id', $id)->firstOrFail();

        if ($blocked = $this->rejectIfDocumentPortalBlocked($employee)) {
            return $blocked;
        }

        return $this->confirmPortalDocuments($employee);
    }

    // show thank you page
    public function thankyou()
    {
        return view("frontend.upload-document.thank-you");
    }
    // show thank you page
    public function ViewFile($id, $file)
    {

        $file_name = decrypt($file);
        $message = "You are not eligible to view file. Please contact HR for assistance.";

        $main_url = config('app.main_url');

        $employee = EmployeesNewJoiner::where("emp_url", $id)->first();
        if (!$employee) {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        }
        if ($employee->emp_status === 'inactive') {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        }
        /**
         * 2. Document already submitted
         */
        elseif (
            !$this->candidateMayModifyDocuments($employee)
            && !OnboardingDocumentReedit::isAllowed($employee)
        ) {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        }
        /**
         * 3. Due date expired
         */
        elseif (
            !OnboardingDocumentReedit::isAllowed($employee)
            && OnboardingDocumentDeadline::isExpired($employee)
        ) {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        } else
            $document_list = NewEmployeesDocument::where("emp_id", $employee->id)->where("emp_document_file", $file_name)->first();
        // $file_url = $main_url . $document_list->emp_document_file_path;
        if(env('APP_ENV') == 'local'){
            $file_url = $main_url. $document_list->emp_document_file_path;
        } else {
            $file_url = $main_url. 'public/' . $document_list->emp_document_file_path;
        }

        // dd($main_url.$file_url);
        return view("frontend.upload-document.view-file", [
            "file_url" => $file_url,
        ]);
    }

    public function delete($id)
    {
        $document = NewEmployeesDocument::findOrFail($id);
        $employee = EmployeesNewJoiner::findOrFail($document->emp_id);

        // Delete the file from storage
        if (Storage::disk('public')->exists('temp/employees/EMP_' . $employee->id . '/' . $document->emp_document_file)) {
            // Storage::disk('public')->delete('temp/employees/EMP_' . $employee->id . '/' . $document->emp_document_file);
        }

        // Delete the document record from the database
        // $document->delete();

        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'document'])
            ->with([
                "success" => "Document deleted successfully",
                "bg-color" => "success",
            ]);
    }

    /** Candidate onboarding portal */
    public function portal(Request $request, $token)
    {
        $employee = EmployeesNewJoiner::where('emp_url', $token)->first();
        if (!$employee) {
            return view('frontend.onboarding.portal', ['invalid' => true]);
        }
        if ($employee->emp_onboarding_status === 'completed') {
            return view('frontend.onboarding.portal', ['employee' => $employee, 'completed' => true]);
        }
        if (OnboardingDocumentDeadline::isPortalBlocked($employee) && !OnboardingDocumentReedit::isAllowed($employee)) {
            return view('frontend.onboarding.portal-document-blocked-page', ['employee' => $employee]);
        }

        $documentNamesList = OnboardingMediclaimDocuments::mergeIntoCandidateUpload(
            DocumentNamesList::forCandidateUpload(),
            $employee
        );
        $document_list = NewEmployeesDocument::where('emp_id', $employee->id)->get();
        $docMessage = $this->documentEligibilityMessage($employee);

        $isEdit = false;
        $editDocument = null;
        if ($request->filled('edit')) {
            try {
                $editDocument = NewEmployeesDocument::findOrFail(decrypt($request->query('edit')));
                if ($editDocument->emp_id === $employee->id) {
                    $isEdit = true;
                }
            } catch (\Throwable $e) {
                $editDocument = null;
            }
        }

        $canUpload = ($employee->isProfileComplete() || OnboardingDocumentReedit::isAllowed($employee))
            && $docMessage === null;

        $portalTabs = \App\Support\OnboardingPortalTabs::tabsFor($employee);
        $activeTab = (string) $request->query('tab', 'info');
        if (!isset($portalTabs[$activeTab])) {
            $activeTab = array_key_first($portalTabs) ?: 'info';
        }

        return view('frontend.onboarding.portal', [
            'employee' => $employee,
            'activeTab' => $activeTab,
            'portalTabs' => $portalTabs,
            'currentStep' => $employee->onboardingStep(),
            'currentStepLabel' => \App\Support\OnboardingStepGate::humanStepLabel($employee->onboardingStep()),
            'profileComplete' => $employee->isProfileComplete(),
            'documentNamesList' => $documentNamesList,
            'document_list' => $document_list,
            'main_url' => config('app.main_url'),
            'profileSchema' => ProfileFormSchema::get(),
            'indianCities' => IndianCities::forFrontend(),
            'profileDraftStep' => OnboardingProfileDraft::currentStep($employee),
            'profileDraftSavedAt' => OnboardingProfileDraft::savedAt($employee),
            'hasProfileDraft' => OnboardingProfileDraft::hasDraft($employee),
            'joiningSchema' => JoiningFormSchema::get(),
            'joiningDraftStep' => OnboardingJoiningDraft::currentStep($employee),
            'joiningDraftSavedAt' => OnboardingJoiningDraft::savedAt($employee),
            'hasJoiningDraft' => OnboardingJoiningDraft::hasDraft($employee),
            'policyDocuments' => CompanyPolicyDocuments::list(),
            'canEditProfile' => $employee->canEditPortalProfile(),
            'profileReeditActive' => $employee->profileReeditAllowed(),
            'profileReeditReason' => \App\Support\OnboardingProfileReedit::reasonLabel(
                \App\Support\OnboardingProfileReedit::meta($employee)
            ),
            'isFresher' => $employee->isFresher(),
            'employmentTypeLabel' => \App\Support\CandidateEmploymentType::label($employee->employmentType()),
            'canUploadDocuments' => $canUpload,
            'documentReeditActive' => OnboardingDocumentReedit::isAllowed($employee),
            'documentReeditReason' => OnboardingDocumentReedit::reasonLabel(OnboardingDocumentReedit::meta($employee)),
            'documentReeditKeys' => OnboardingDocumentReedit::allowedDocumentKeys($employee),
            'documentBlockMessage' => $docMessage,
            'allDocumentsRequired' => OnboardingDocumentRequirements::allRequired(),
            'missingRequiredDocuments' => OnboardingDocumentRequirements::missingLabels($employee),
            'requiredDocumentKeySet' => array_flip(OnboardingDocumentRequirements::requiredKeys($employee)),
            'optionalEmploymentDocumentKeys' => OnboardingDocumentRequirements::employmentDocumentKeys(),
            'isEdit' => $isEdit,
            'editDocument' => $editDocument,
            'documentNames' => collect($documentNamesList)->collapse()->toArray(),
        ]);
    }

    /** Single POST handler for portal actions */
    public function portalSave(Request $request, $token)
    {
        $employee = EmployeesNewJoiner::where('emp_url', $token)->firstOrFail();

        if ($blocked = $this->rejectIfDocumentPortalBlocked($employee)) {
            return $blocked;
        }

        $action = $request->input('action');

        return match ($action) {
            'profile', 'profile_save' => $this->savePortalProfile($request, $employee),
            'profile_draft' => $this->savePortalProfileDraft($request, $employee),
            'upload_document' => $this->storePortalDocument($request, $employee),
            'reupload_document' => $this->reuploadPortalDocument($request, $employee),
            'confirm_documents' => $this->confirmPortalDocuments($employee),
            'offer_accept' => $this->acceptPortalOffer($request, $employee),
            'offer_reject' => $this->rejectPortalOffer($request, $employee),
            'registration' => $this->uploadPortalRegistration($request, $employee),
            'joining' => $this->savePortalJoining($request, $employee),
            'joining_draft' => $this->savePortalJoiningDraft($request, $employee),
            'policy' => $this->acceptPortalPolicy($request, $employee),
            'appointment' => $this->acceptPortalAppointment($request, $employee),
            default => back()->with('error', 'Invalid action.'),
        };
    }

    protected function storePortalDocument(Request $request, EmployeesNewJoiner $employee)
    {
        $request->merge(['emp_id' => $employee->id]);

        return $this->store($request);
    }

    protected function reuploadPortalDocument(Request $request, EmployeesNewJoiner $employee)
    {
        $documentId = $request->validate(['document_id' => 'required|integer'])['document_id'];
        $document = NewEmployeesDocument::findOrFail($documentId);
        if ((int) $document->emp_id !== (int) $employee->id) {
            abort(403, 'Invalid document.');
        }

        return $this->UpdateDocuments($request, $documentId);
    }

    protected function savePortalProfileDraft(Request $request, EmployeesNewJoiner $employee)
    {
        if (!$employee->canEditPortalProfile()) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Your information is locked.'], 403);
            }

            return back()->with('error', 'Your information is locked after submission.');
        }

        $request->validate([
            'profile_information' => 'required|string',
            'draft_step' => 'required|integer|min:0',
        ]);

        $information = json_decode($request->input('profile_information'), true);
        if (!is_array($information)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Invalid profile data.'], 422);
            }

            return back()->with('error', 'Invalid profile data.');
        }

        $this->resolveProfileCityFields($information);

        OnboardingProfileDraft::saveDraft(
            $employee,
            $information,
            (int) $request->input('draft_step')
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'saved_at' => OnboardingProfileDraft::savedAt($employee),
                'step' => OnboardingProfileDraft::currentStep($employee),
            ]);
        }

        return back()->with('success', 'Draft saved.');
    }

    /**
     * @param  array<string, mixed>  $information
     */
    protected function resolveProfileCityFields(array &$information): void
    {
        $pairs = [
            ['basic_information', 'state', 'city', 'city_other'],
            ['address_details', 'current_state', 'current_city', 'current_city_other'],
            ['address_details', 'permanent_state', 'permanent_city', 'permanent_city_other'],
        ];

        foreach ($pairs as [$section, $stateKey, $cityKey, $otherKey]) {
            if (!isset($information[$section]) || !is_array($information[$section])) {
                continue;
            }
            $block = &$information[$section];
            $state = trim((string) ($block[$stateKey] ?? ''));
            $city = trim((string) ($block[$cityKey] ?? ''));
            if ($city === '__OTHER__') {
                $city = trim((string) ($block[$otherKey] ?? ''));
            }
            if ($city !== '' && $state !== '') {
                IndianCities::addCustom($state, $city);
            }
            if ($city !== '') {
                $block[$cityKey] = $city;
            }
            unset($block[$otherKey]);
        }
    }

    protected function profileSectionError(string $sectionKey, string $message): string
    {
        $title = ProfileFormSchema::section($sectionKey)['title'] ?? $sectionKey;

        return $title . ' -> ' . $message;
    }

    protected function profileFieldError(string $sectionKey, string $fieldName, string $message): string
    {
        $label = ProfileFormSchema::fieldLabel($sectionKey, $fieldName);

        return $this->profileSectionError($sectionKey, $label . ' ' . $message);
    }

    protected function familyParentsWaived(array $family): bool
    {
        $flag = $family['no_father_mother'] ?? '';

        return in_array($flag, [1, '1', true, 'true', 'on'], true);
    }

    /** @param  array<string, mixed>  $family */
    protected function validateFamilyMembers(array &$family): ?string
    {
        $members = $family['members'] ?? [];
        if (!is_array($members)) {
            $members = [];
        }

        $requiredKeys = ['name', 'relation', 'age', 'dob', 'occupation'];
        $complete = [];
        foreach ($members as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = [];
            foreach ($requiredKeys as $key) {
                $normalized[$key] = trim((string) ($row[$key] ?? ''));
            }
            if (in_array('', $normalized, true)) {
                continue;
            }
            $complete[] = $normalized;
        }

        if ($this->familyParentsWaived($family)) {
            $family['members'] = $complete;

            return null;
        }

        if (count($complete) < 2) {
            return $this->profileSectionError(
                'family_details',
                'Add at least 2 family members with Name, Relation, Age, DOB and Occupation.'
            );
        }

        $relations = array_map(
            fn (array $row) => strtolower($row['relation']),
            $complete
        );
        if (!in_array('father', $relations, true)) {
            return $this->profileSectionError(
                'family_details',
                'Father is required (name, relation, age, DOB, occupation).'
            );
        }
        if (!in_array('mother', $relations, true)) {
            return $this->profileSectionError(
                'family_details',
                'Mother is required (name, relation, age, DOB, occupation).'
            );
        }

        $family['members'] = $complete;

        return null;
    }

    /** @param  array<string, mixed>  $employment */
    protected function validateIndustryRelatives(array &$employment): ?string
    {
        $has = strtolower(trim((string) ($employment['has_industry_relatives'] ?? '')));
        if ($has === '') {
            return $this->profileFieldError(
                'previous_employment',
                'has_industry_relatives',
                'is required — select Yes or No'
            );
        }

        if ($has !== 'yes') {
            $employment['has_industry_relatives'] = 'no';
            $employment['industry_relatives'] = [];

            return null;
        }

        $employment['has_industry_relatives'] = 'yes';
        $rows = is_array($employment['industry_relatives'] ?? null) ? $employment['industry_relatives'] : [];
        $filled = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hasData = false;
            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $filled[] = $row;
            }
        }

        if (count($filled) === 0) {
            return $this->profileSectionError('previous_employment', 'Add at least one industry relative');
        }

        $requiredColumns = [
            'name' => 'Name',
            'relation' => 'Relation',
            'company_name' => 'Company Name',
            'designation' => 'Designation',
        ];
        foreach ($filled as $i => $row) {
            foreach ($requiredColumns as $key => $label) {
                if (trim((string) ($row[$key] ?? '')) === '') {
                    return $this->profileSectionError(
                        'previous_employment',
                        $label . ' is required for industry relative row ' . ($i + 1)
                    );
                }
            }
        }

        $employment['industry_relatives'] = $filled;

        return null;
    }

    protected function savePortalProfile(Request $request, EmployeesNewJoiner $employee)
    {
        if (!$employee->canEditPortalProfile()) {
            return back()->with('error', 'Your information is locked after submission. Contact HR if you need changes.');
        }

        $wasReedit = $employee->profileReeditAllowed();

        $request->validate([
            'profile_information' => 'required|string',
        ]);

        $information = json_decode($request->input('profile_information'), true);

        if (!is_array($information)) {
            return back()->with('error', 'Invalid profile data.');
        }

        $this->resolveProfileCityFields($information);

        $basic = $information['basic_information'] ?? [];
        $address = $information['address_details'] ?? [];
        $declaration = $information['declaration'] ?? [];

        if (empty(trim((string) ($basic['application_source'] ?? '')))) {
            return back()->with('error', $this->profileFieldError('basic_information', 'application_source', 'is required'));
        }

        foreach (['name', 'fathers_name', 'gender', 'religion', 'dob', 'state', 'city', 'blood_group', 'phone', 'uid', 'pan', 'email', 'has_company_contacts'] as $key) {
            if (empty($basic[$key])) {
                return back()->with('error', $this->profileFieldError('basic_information', $key, 'is required'));
            }
        }

        if (($basic['has_company_contacts'] ?? '') === 'Yes') {
            foreach (['company_contact_name', 'company_contact_relationship', 'company_contact_department'] as $key) {
                if (trim((string) ($basic[$key] ?? '')) === '') {
                    return back()->with('error', $this->profileFieldError('basic_information', $key, 'is required'));
                }
            }
        } else {
            $basic['company_contact_name'] = '';
            $basic['company_contact_relationship'] = '';
            $basic['company_contact_department'] = '';
        }

        $basic['pan'] = strtoupper(preg_replace('/\s+/', '', (string) ($basic['pan'] ?? '')) ?? '');
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $basic['pan'])) {
            return back()->with('error', $this->profileFieldError('basic_information', 'pan', 'must be 10 characters (e.g. ABCDE1234F).'));
        }

        $uidRaw = trim((string) ($basic['uid'] ?? ''));
        if (!preg_match('/^\d{12}$/', $uidRaw)) {
            return back()->with('error', $this->profileFieldError(
                'basic_information',
                'uid',
                'must be exactly 12 digits (numbers only — no commas, spaces, or other characters).'
            ));
        }
        $basic['uid'] = $uidRaw;

        foreach ([
            'voters_id_number',
            'driving_license_number',
            'driving_license_valid_till',
            'passport_number',
            'passport_validity',
        ] as $optionalKey) {
            if (trim((string) ($basic[$optionalKey] ?? '')) === '') {
                $basic[$optionalKey] = 'Not applicable';
            }
        }
        $information['basic_information'] = $basic;

        $addrRequired = [
            'current_full_address',
            'current_state',
            'current_city',
            'permanent_full_address',
            'permanent_state',
            'permanent_city',
            'permanent_line1',
            'permanent_locality',
            'permanent_landmark',
        ];
        foreach ($addrRequired as $key) {
            if (empty($address[$key])) {
                return back()->with('error', $this->profileSectionError('address_details', 'Complete current and permanent address (required for BGV)'));
            }
        }

        if (empty($declaration['consent_correct'])) {
            return back()->with('error', $this->profileSectionError('declaration', 'Please confirm the declaration and that all details are correct'));
        }

        if (($basic['marital_status'] ?? '') === 'Married' && trim((string) ($basic['spouse_name'] ?? '')) === '') {
            return back()->with('error', $this->profileFieldError('basic_information', 'spouse_name', 'is required when marital status is Married'));
        }

        $family = is_array($information['family_details'] ?? null) ? $information['family_details'] : [];
        if (($basic['marital_status'] ?? '') === 'Married' && trim((string) ($family['spouse_name'] ?? '')) === '') {
            $family['spouse_name'] = trim((string) ($basic['spouse_name'] ?? ''));
        }

        $familyError = $this->validateFamilyMembers($family);
        if ($familyError !== null) {
            return back()->with('error', $familyError);
        }

        $fatherName = '';
        if (!empty($family['members']) && is_array($family['members'])) {
            foreach ($family['members'] as $member) {
                if (!is_array($member)) {
                    continue;
                }
                if (strtolower(trim((string) ($member['relation'] ?? ''))) === 'father') {
                    $fatherName = trim((string) ($member['name'] ?? ''));
                    break;
                }
            }
        }
        if ($fatherName !== '') {
            $family['father_name'] = $fatherName;
            $basic['fathers_name'] = $fatherName;
            $information['basic_information'] = $basic;
        }

        $information['family_details'] = $family;

        $education = $information['education_qualification'] ?? [];
        if (!is_array($education) || count($education) === 0) {
            return back()->with('error', $this->profileSectionError('education_qualification', 'Add at least one education record'));
        }
        foreach ($education as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $hasData = false;
            foreach ($row as $v) {
                if (trim((string) $v) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }
            $institute = trim((string) ($row['name_of_institute'] ?? $row['institute'] ?? ''));
            foreach (['degree', 'year_of_passing'] as $col) {
                if (empty($row[$col])) {
                    return back()->with('error', $this->profileSectionError('education_qualification', 'Complete all mandatory fields for education row ' . ($i + 1)));
                }
            }
            if ($institute === '') {
                return back()->with('error', $this->profileSectionError('education_qualification', 'Institute name is required for education row ' . ($i + 1)));
            }
        }

        $employment = $information['previous_employment'] ?? [];
        $employment = \App\Support\CandidateEmploymentType::normalizeEmploymentPayload($employee, is_array($employment) ? $employment : []);
        $employment = self::preserveHrEmploymentDates($employee, $employment);
        $information['previous_employment'] = $employment;

        if ($employee->isFresher()) {
            if (trim((string) ($employment['expected_ctc'] ?? '')) === '') {
                return back()->with('error', $this->profileFieldError('previous_employment', 'expected_ctc', 'is required for fresher candidates'));
            }
        } else {
            $empRecords = \App\Support\OnGridProfilePayload::employmentRecords($employment);
            if (count($empRecords) === 0) {
                return back()->with('error', $this->profileSectionError('previous_employment', 'Add at least one previous employer'));
            }
            foreach ($empRecords as $i => $row) {
                if (empty(trim((string) ($row['employer_name'] ?? '')))) {
                    return back()->with('error', $this->profileSectionError('previous_employment', 'Company name is required for employment row ' . ($i + 1)));
                }
                if (trim((string) ($row['joining_date'] ?? '')) === '') {
                    return back()->with('error', $this->profileSectionError('previous_employment', 'Joining date is required for employment row ' . ($i + 1)));
                }
                if (\App\Support\OnGrid::formatEmploymentDate($row['joining_date'] ?? null) === null) {
                    return back()->with('error', $this->profileSectionError('previous_employment', 'Enter a valid joining date for employment row ' . ($i + 1)));
                }
            }
            if (trim((string) ($employment['current_ctc'] ?? '')) === '') {
                return back()->with('error', $this->profileFieldError('previous_employment', 'current_ctc', 'is required for experienced candidates'));
            }
            foreach (['hr_name', 'hr_email', 'hr_phone'] as $field) {
                if (trim((string) ($employment[$field] ?? '')) === '') {
                    return back()->with('error', $this->profileFieldError('previous_employment', $field, 'is required'));
                }
            }
        }

        $relativesError = $this->validateIndustryRelatives($employment);
        if ($relativesError !== null) {
            return back()->with('error', $relativesError);
        }
        $information['previous_employment'] = $employment;

        $information['declaration'] = is_array($declaration) ? $declaration : [];
        $information['declaration']['declaration_date'] = now()->toDateString();
        try {
            $information['declaration']['consent_text'] = \App\Support\OnGrid::configuredConsentText();
        } catch (\Throwable $e) {
            // ONGRID_CONSENT_TEXT not set — keep candidate value for profile display only.
        }

        OnboardingProfileDraft::markSubmitted($employee, $information);
        $profileData = $employee->emp_profile_data ?? [];
        $profileData['schema_version'] = ProfileFormSchema::get()['version'] ?? 1;
        $employee->emp_profile_data = $profileData;

        $employee->emp_name = $basic['name'];
        $employee->emp_phone = $basic['phone'];
        $employee->emp_email = $basic['email'];
        if (!empty($basic['dob'])) {
            $employee->emp_dob = $basic['dob'];
        }
        if (!empty($basic['application_source'])) {
            $employee->emp_application_source = trim((string) $basic['application_source']);
        }

        $employee->save();

        $other = $employee->emp_other ?? [];
        unset($other['profile_reedit']);
        $employee->emp_other = $other;
        $employee->save();

        if (!$wasReedit) {
            $employee->setOnboardingStep('profile_completed');
        }

        OnboardingMail::candidateActivity(
            $employee,
            $wasReedit ? 'Profile updated' : 'Profile submitted',
            $wasReedit
                ? 'Candidate updated information after HR allowed re-edit.'
                : 'Candidate completed the information form.',
            'info'
        );

        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'info'])
            ->with(
                'success',
                $wasReedit
                    ? 'Information updated successfully.'
                    : 'Information Details submitted successfully. Next step: open the Document tab to upload your documents.'
            );
    }

    protected function confirmPortalDocuments(EmployeesNewJoiner $employee)
    {
        if ($blocked = $this->rejectIfDocumentPortalBlocked($employee)) {
            return $blocked;
        }

        if (!$employee->isProfileComplete()) {
            return back()->with('error', 'Complete profile first.');
        }

        if (!OnboardingDocumentRequirements::canConfirmSubmit($employee)) {
            if (OnboardingDocumentReedit::isAllowed($employee)) {
                return back()->with('error', 'Upload at least one of the document types HR requested, then submit.');
            }

            if (OnboardingDocumentRequirements::allRequired()) {
                $missing = OnboardingDocumentRequirements::missingLabels($employee);

                return back()->with(
                    'error',
                    'Please upload all required documents before submitting.'
                    . ($missing !== [] ? ' Missing: ' . implode(' | ', $missing) . '.' : '')
                );
            }

            return back()->with('error', 'Upload at least one document before submitting.');
        }

        $wasDocumentReedit = $employee->documentReeditAllowed();

        $employee->emp_status = 'process';
        $employee->emp_document_status = 'process';

        if ($wasDocumentReedit) {
            OnboardingDocumentReedit::completeCandidateResubmit($employee);
        } elseif ($employee->onboardingStep() === 'start' || $employee->onboardingStep() === 'profile_completed') {
            $employee->setOnboardingStep('hr_review');
        }

        NewEmployeesDocument::where('emp_id', $employee->id)->where('emp_document_status', 'upload')->update(['emp_document_status' => 'process']);

        if ($wasDocumentReedit) {
            OnboardingMail::candidateActivity(
                $employee,
                'Documents re-submitted',
                'Candidate uploaded the documents HR requested. Please review in Uploaded documents.',
                'document'
            );
        } else {
            OnboardingMail::documentsSubmittedThanks($employee);
            OnboardingMail::candidateActivity(
                $employee,
                'Documents submitted',
                'Candidate submitted all uploaded documents for HR review.',
                'document'
            );
        }

        return $this->redirectToPortalDocumentTab($employee, [
            'success' => $wasDocumentReedit ? 'Documents submitted successfully. Thank you.' : 'Documents submitted successfully. Thank you.',
        ]);
    }

    protected function acceptPortalOffer(Request $request, EmployeesNewJoiner $employee)
    {
        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'letter'])
            ->with('error', 'Please use Accept Offer on the letter tab to draw or upload your signature.');
    }

    protected function rejectPortalOffer(Request $request, EmployeesNewJoiner $employee)
    {
        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'letter'])
            ->with('error', 'Please use Reject Offer on the letter tab and provide a reason.');
    }

    protected function uploadPortalRegistration(Request $request, EmployeesNewJoiner $employee)
    {
        if (!\App\Support\OnboardingLetterDeadline::canCandidateUploadRegistration($employee)) {
            $message = \App\Support\OnboardingLetterDeadline::isRegistrationExpired($employee)
                ? \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_REGISTRATION) . ' Please contact HR.'
                : 'Registration letter cannot be uploaded at this stage.';

            return back()->with('error', $message);
        }

        $request->validate(['registration_file' => OnboardingFileRules::resignationLetterRule()], [
            'registration_file.mimes' => 'Resignation acceptance letter must be a PDF file.',
        ]);
        $folder = ($employee->emp_folder_path ?? 'temp/employees/') . ($employee->emp_folder ?? 'EMP_' . $employee->id);
        $file = $request->file('registration_file');
        $fileName = 'EMP_' . $employee->id . '_resignation_acceptance.' . $file->getClientOriginalExtension();
        $path = Storage::disk('public')->putFileAs($folder, $file, $fileName);
        OnboardingLetterDocument::storeCandidateUpload(
            $employee,
            OnboardingLetterDocument::RESIGNATION_UPLOAD,
            $path,
            $fileName,
            'process'
        );
        $employee->setOnboardingStep('registration_submitted');

        OnboardingMail::candidateActivity(
            $employee,
            'Resignation letter uploaded',
            'Candidate uploaded resignation acceptance letter. Please verify in HR admin.',
            'letter'
        );

        return back()->with('success', 'Resignation acceptance letter uploaded. HR has been notified.');
    }

    public function streamPolicyFile(Request $request, string $token, string $key)
    {
        if ($request->header('X-Policy-Viewer') !== '1') {
            abort(403, 'Direct access to policy files is not permitted.');
        }

        $employee = EmployeesNewJoiner::where('emp_url', $token)->firstOrFail();

        $doc = collect(CompanyPolicyDocuments::list())->firstWhere('key', $key);
        if (!$doc) {
            abort(404);
        }

        if (!CompanyPolicyDocuments::candidateCanView($employee)) {
            abort(403, 'Company policies are not available at this stage.');
        }

        $path = CompanyPolicyDocuments::filePath($doc);
        if (!$path) {
            abort(404, 'Policy file not found.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="policy.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function savePortalJoiningDraft(Request $request, EmployeesNewJoiner $employee)
    {
        if ($employee->onboardingStep() !== 'join_forms_sent') {
            $blocked = \App\Support\OnboardingJoiningAccess::candidateBlockedMessage($employee)
                ?? 'Joining form is not open.';
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $blocked], 403);
            }

            return back()->with('error', $blocked);
        }

        $request->validate([
            'joining_information' => 'required|string',
            'draft_step' => 'required|integer|min:0',
        ]);

        $information = json_decode($request->input('joining_information'), true);
        if (!is_array($information)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Invalid joining data.'], 422);
            }

            return back()->with('error', 'Invalid joining data.');
        }

        OnboardingJoiningDraft::saveDraft($employee, $information, (int) $request->input('draft_step'));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'saved_at' => OnboardingJoiningDraft::savedAt($employee),
                'step' => OnboardingJoiningDraft::currentStep($employee),
            ]);
        }

        return back()->with('success', 'Joining draft saved.');
    }

    protected function savePortalJoining(Request $request, EmployeesNewJoiner $employee)
    {
        if ($employee->onboardingStep() !== 'join_forms_sent') {
            $blocked = \App\Support\OnboardingJoiningAccess::candidateBlockedMessage($employee)
                ?? 'Joining form is not open.';

            return back()->with('error', $blocked);
        }

        $request->validate([
            'joining_information' => 'required|string',
            'medical_fitness_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);
        $information = json_decode($request->input('joining_information'), true);

        if (!is_array($information) || empty($information['joining_details']['confirmed_join_date'])) {
            return back()->with('error', $this->joiningSectionError('joining_details', 'Confirmed Join Date is required'));
        }

        $medicalError = $this->validateJoiningMedicalFitness($request, $employee, $information['medical_fitness'] ?? null);
        if ($medicalError !== null) {
            return back()->with('error', $medicalError);
        }

        $bankPf = is_array($information['bank_pf'] ?? null) ? $information['bank_pf'] : [];
        $legacyDetails = is_array($information['joining_details'] ?? null) ? $information['joining_details'] : [];
        foreach (['bank_name', 'bank_branch', 'bank_account_number', 'ifsc_code', 'pf_uan_details'] as $bankKey) {
            if (empty($bankPf[$bankKey]) && !empty($legacyDetails[$bankKey])) {
                $bankPf[$bankKey] = $legacyDetails[$bankKey];
            }
        }
        $bankName = trim((string) ($bankPf['bank_name'] ?? ''));
        $bankBranch = trim((string) ($bankPf['bank_branch'] ?? ''));
        $bankAccount = trim((string) ($bankPf['bank_account_number'] ?? ''));
        $ifsc = strtoupper(preg_replace('/\s+/', '', (string) ($bankPf['ifsc_code'] ?? '')));
        if ($bankName === '' || $bankBranch === '' || $bankAccount === '' || $ifsc === '') {
            return back()->with('error', $this->joiningSectionError('bank_pf', 'Bank name, branch, account number, and IFSC code are required'));
        }
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            return back()->with('error', $this->joiningSectionError('bank_pf', 'IFSC code must be 11 characters (e.g. SBIN0001234)'));
        }
        $bankPf['bank_name'] = $bankName;
        $bankPf['bank_branch'] = $bankBranch;
        $bankPf['bank_account_number'] = $bankAccount;
        $bankPf['ifsc_code'] = $ifsc;
        $information['bank_pf'] = $bankPf;

        $details = is_array($information['joining_details'] ?? null) ? $information['joining_details'] : [];
        $gratuityError = $this->validateGratuityNomination($information['gratuity_nomination'] ?? null);
        if ($gratuityError !== null) {
            return back()->with('error', $gratuityError);
        }
        $submittedGratuity = is_array($information['gratuity_nomination'] ?? null) ? $information['gratuity_nomination'] : [];
        $information['gratuity_nomination'] = array_merge(
            GratuityNominationPrefill::employeeDefaults($employee, $submittedGratuity, $details),
            [
                'nominees' => GratuityNominationPrefill::normalizeNominees($submittedGratuity),
                'gratuity_declaration_confirm' => $submittedGratuity['gratuity_declaration_confirm'] ?? '',
                'nomination_submitted' => !empty($submittedGratuity['gratuity_declaration_confirm']) ? '1' : '',
            ]
        );
        $submittedForm25 = is_array($information['form_25_leave_nomination'] ?? null) ? $information['form_25_leave_nomination'] : [];
        $form25Error = Form25LeaveNomination::validate($submittedForm25);
        if ($form25Error !== null) {
            return back()->with('error', $form25Error);
        }
        $information['form_25_leave_nomination'] = array_merge(
            Form25LeaveNomination::defaults($employee, $submittedForm25, $details),
            $submittedForm25
        );

        $mediclaimError = $this->validateMediclaimDependents($information['mediclaim_dependents'] ?? null);
        if ($mediclaimError !== null) {
            return back()->with('error', $mediclaimError);
        }
        $existingMediclaim = ($employee->emp_joining_requirements ?? [])['mediclaim_dependents'] ?? [];
        $information['mediclaim_dependents'] = OnboardingMediclaimDocuments::normalizeDependents(
            $information['mediclaim_dependents'] ?? null,
            is_array($existingMediclaim) ? $existingMediclaim : []
        );
        OnboardingMediclaimDocuments::pruneOrphanDocuments($employee, $information['mediclaim_dependents']);

        $empId = trim((string) ($details['emp_id'] ?? ''));
        if ($empId !== '' && empty($employee->emp_employee_id)) {
            $employee->emp_employee_id = $empId;
        }

        if ($request->hasFile('medical_fitness_file')) {
            $this->storeJoiningMedicalDocument($request, $employee);
            $information['medical_fitness'] = array_merge(
                is_array($information['medical_fitness'] ?? null) ? $information['medical_fitness'] : [],
                ['uploaded' => '1', 'uploaded_at' => now()->toIso8601String()]
            );
        }

        $employee->emp_joining_requirements = $information;
        $employee->emp_joining_date = $information['joining_details']['confirmed_join_date'];
        OnboardingJoiningDraft::clear($employee);
        $employee->save();
        $employee->setOnboardingStep('join_forms_submitted');

        OnboardingMail::candidateActivity(
            $employee,
            'Joining form submitted',
            'Candidate submitted joining details and assets. Awaiting policy acceptance.',
            'joining'
        );

        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'policy'])
            ->with('success', 'Joining details saved. Please read and accept company policies.');
    }

    protected function joiningSectionError(string $sectionKey, string $message): string
    {
        $title = JoiningFormSchema::section($sectionKey)['title'] ?? $sectionKey;

        return $title . ' -> ' . $message;
    }

    protected function validateJoiningMedicalFitness(Request $request, EmployeesNewJoiner $employee, mixed $medical): ?string
    {
        if (!is_array($medical)) {
            return $this->joiningSectionError('medical_fitness', 'Medical fitness certificate available? is required');
        }

        $available = trim((string) ($medical['medical_fitness_available'] ?? ''));
        if (!in_array($available, ['yes', 'no'], true)) {
            return $this->joiningSectionError('medical_fitness', 'Medical fitness certificate available? is required');
        }

        if ($available === 'yes') {
            $hasExisting = NewEmployeesDocument::query()
                ->where('emp_id', $employee->id)
                ->where('emp_select_document', 'medical_fitness_certificate')
                ->whereNotNull('emp_document_file_path')
                ->exists();
            if (!$request->hasFile('medical_fitness_file') && !$hasExisting && empty($medical['uploaded'])) {
                return $this->joiningSectionError('medical_fitness', 'PDF file is required');
            }
        }

        if ($available === 'no') {
            $reason = trim((string) ($medical['medical_fitness_unavailable_reason'] ?? ''));
            if (strlen($reason) < 10) {
                return $this->joiningSectionError('medical_fitness', 'Reason must be at least 10 characters');
            }
        }

        return null;
    }

    protected function validateGratuityNomination(mixed $gratuity): ?string
    {
        if (!is_array($gratuity)) {
            return $this->joiningSectionError('gratuity_nomination', 'Gratuity Nomination Form is required');
        }

        $nomineeError = GratuityNominationPrefill::validateNominees(
            GratuityNominationPrefill::normalizeNominees($gratuity)
        );
        if ($nomineeError !== null) {
            $msg = preg_replace('/^Gratuity nomination:\s*/i', '', $nomineeError) ?? $nomineeError;

            return $this->joiningSectionError('gratuity_nomination', $msg);
        }

        if (empty($gratuity['gratuity_declaration_confirm'])) {
            return $this->joiningSectionError('gratuity_nomination', 'Please confirm the declaration before submitting');
        }

        return null;
    }

    protected function validateMediclaimDependents(mixed $rows): ?string
    {
        if ($rows === null || $rows === '') {
            return null;
        }
        if (!is_array($rows)) {
            return 'Mediclaim members data is invalid.';
        }

        $parentPattern = '/\b(father|mother|parent|parents|step\s*father|step\s*mother|in[-\s]?law)\b/i';
        $allowed = ['Spouse', 'Son', 'Daughter'];

        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $dob = trim((string) ($row['dob'] ?? ''));
            $rel = trim((string) ($row['relationship'] ?? ''));
            if ($name === '' && $dob === '' && $rel === '') {
                continue;
            }
            if ($name === '' || $dob === '' || $rel === '') {
                return 'Mediclaim member #' . ($i + 1) . ': name, date of birth, and relationship are required.';
            }
            if (preg_match($parentPattern, $rel)) {
                return 'Mediclaim covers dependents only. Parents cannot be added (member #' . ($i + 1) . ').';
            }
            if (!in_array($rel, $allowed, true)) {
                return 'Mediclaim member #' . ($i + 1) . ': choose Spouse, Son, or Daughter.';
            }
        }

        return null;
    }

    protected function storeJoiningMedicalDocument(Request $request, EmployeesNewJoiner $employee): void
    {
        $file = $request->file('medical_fitness_file');
        if (!$file) {
            return;
        }

        $documentKey = 'medical_fitness_certificate';
        $folder = "temp/employees/EMP_{$employee->id}";
        $extension = $file->getClientOriginalExtension();
        $fileName = 'EMP_' . $employee->id . '_' . $documentKey . '.' . $extension;
        $filePath = Storage::disk('public')->putFileAs($folder, $file, $fileName);

        NewEmployeesDocument::updateOrCreate(
            [
                'emp_id' => $employee->id,
                'emp_select_document' => $documentKey,
            ],
            [
                'emp_doc_date' => Carbon::now(),
                'emp_document_file' => $fileName,
                'emp_document_file_path' => 'storage/' . $filePath,
                'emp_document_status' => 'upload',
                'emp_hr_id' => $employee->emp_hr_id,
                'approval_date' => null,
                'rejection_reason' => null,
            ]
        );

        if (!$employee->emp_folder) {
            $employee->emp_folder = 'EMP_' . $employee->id;
            $employee->emp_folder_path = 'temp/employees/';
            $employee->save();
        }
    }

    protected function acceptPortalPolicy(Request $request, EmployeesNewJoiner $employee)
    {
        if ($employee->onboardingStep() !== 'join_forms_submitted') {
            return back()->with('error', 'Complete joining details before accepting policy.');
        }

        foreach (CompanyPolicyDocuments::list() as $doc) {
            if (empty($doc['require_accept'])) {
                continue;
            }
            $field = 'policy_accept_' . ($doc['key'] ?? '');
            if (!$request->boolean($field)) {
                return back()->with('error', 'Please confirm you have read and accept: ' . ($doc['title'] ?? 'policy') . '.');
            }
        }

        $request->validate([
            'signature_file' => OnboardingFileRules::signatureFileRule(),
            'signature' => 'nullable|string',
        ], [
            'signature_file.mimes' => 'Signature must be JPG or PNG image.',
        ]);

        $signature = OnboardingSignature::fromRequest($request);
        if (!$signature) {
            return back()->with('error', 'Please draw or upload your signature.');
        }

        $employee->emp_policy_signature = $signature;
        $employee->emp_policy_accepted_at = now();

        $accepted = [];
        foreach (CompanyPolicyDocuments::list() as $doc) {
            if (empty($doc['key'])) {
                continue;
            }
            if (!empty($doc['require_accept']) && !$request->boolean('policy_accept_' . $doc['key'])) {
                continue;
            }
            $accepted[] = $doc['key'];
        }
        $other = $employee->emp_other ?? [];
        $other['policy_acceptances'] = $accepted;
        $employee->emp_other = $other;

        $employee->save();

        try {
            $policyTitles = [];
            foreach (CompanyPolicyDocuments::list() as $doc) {
                $key = $doc['key'] ?? '';
                if ($key !== '' && in_array($key, $accepted, true)) {
                    $policyTitles[] = $doc['title'] ?? $key;
                }
            }

            OnboardingLetterDocument::archiveFromView(
                $employee,
                OnboardingLetterDocument::POLICY_ACCEPTANCE,
                'pdf.company_policy_acceptance_pdf',
                [
                    'employee' => $employee,
                    'acceptedAt' => $employee->emp_policy_accepted_at,
                    'policyTitles' => $policyTitles,
                    'signature' => $signature,
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Policy accepted but the compliance PDF could not be saved. Please contact HR support.');
        }

        $employee->setOnboardingStep('policy_signed');

        OnboardingMail::candidateActivity(
            $employee,
            'Policy signed',
            'Candidate accepted all company policies. You may send the appointment letter when join date is confirmed.',
            'policy'
        );

        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'policy'])
            ->with('success', 'Company policies accepted. HR notified.');
    }

    protected function acceptPortalAppointment(Request $request, EmployeesNewJoiner $employee)
    {
        return redirect()
            ->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'letter'])
            ->with('error', 'Please use Accept Appointment on the letter tab to sign.');
    }

    /**
     * Candidate must not set last_working_date; preserve HR-entered values on profile save.
     *
     * @param  array<string, mixed>  $employment
     * @return array<string, mixed>
     */
    protected function preserveHrEmploymentDates(EmployeesNewJoiner $employee, array $employment): array
    {
        $existing = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $hrDates = [];
        foreach (\App\Support\OnGrid::employmentRecords(is_array($existing) ? $existing : []) as $row) {
            $name = trim((string) ($row['employer_name'] ?? ''));
            $lastWorking = \App\Support\OnGrid::formatEmploymentDate($row['last_working_date'] ?? null);
            if ($name !== '' && $lastWorking !== null) {
                $hrDates[$name] = $lastWorking;
            }
        }

        if (!empty($employment['records']) && is_array($employment['records'])) {
            foreach ($employment['records'] as $i => $row) {
                unset($employment['records'][$i]['last_working_date']);
                $name = trim((string) ($row['employer_name'] ?? ''));
                if ($name !== '' && isset($hrDates[$name])) {
                    $employment['records'][$i]['last_working_date'] = $hrDates[$name];
                }
            }
        }

        return $employment;
    }
}
