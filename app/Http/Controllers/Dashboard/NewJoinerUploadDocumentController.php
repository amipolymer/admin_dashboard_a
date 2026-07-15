<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use App\Models\User;
use Illuminate\Support\Str;
use App\Data\DocumentNamesList;
use App\Support\OnboardingFileRules;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class NewJoinerUploadDocumentController extends Controller
{
    // Show File document upload page
    public function index($id)
    {
        $main_url = config('app.main_url');
        $messages = [
            "You are not eligible to submit your document. Please contact HR for assistance.",
            "Your document has already been submitted. No further action is required.",
            "The submission due date for your document has expired. Please contact HR for guidance.",
        ];
        $status = "";
        $message = "";
        $employee = EmployeesNewJoiner::where("emp_url", $id)->first();
        $document_list = NewEmployeesDocument::where(
            "emp_id",
            $employee->id
        )->get();
        /**
         * 1. Employee not found or inactive
         */
        if (!$employee || $employee->emp_status !== "active") {
            $status = "error";
            $message = $messages[0];
        }
        /**
         * 2. Document already submitted
         */
        elseif (
            !in_array($employee->emp_document_status, ["process", "rejected"])
        ) {
            $status = "error";
            $message = $messages[1];
        }
        /**
         * 3. Due date expired
         */
        elseif (
            Carbon::parse($employee->emp_document_due_date)->lt(Carbon::today())
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
        $documentNamesList = DocumentNamesList::all(); // Get the array
        return view("frontend.upload-document.index", [
            "employee" => $employee,
            "status" => $status,
            "message" => $message,
            "main_url" => $main_url,
            "documentNamesList" => $documentNamesList,
            "document_list" => $document_list,
        ]);
    }

    public function EditDocuments($url, $id)
    {
        $id_ = decrypt($id);
        $main_url = config('app.main_url');
        $messages = [
            "You are not eligible to submit your document. Please contact HR for assistance.",
            "Your document has already been submitted. No further action is required.",
            "The submission due date for your document has expired. Please contact HR for guidance.",
        ];
        $status = "";
        $message = "";
        $documentEdit = NewEmployeesDocument::findOrFail($id_);
        $employee = EmployeesNewJoiner::where("id", $documentEdit->emp_id)->first();
        $document_list = NewEmployeesDocument::where(
            "emp_id",
            $employee->id
        )->get();
        /**
         * 1. Employee not found or inactive
         */
        if (!$employee || $employee->emp_status !== "active") {
            $status = "error";
            $message = $messages[0];
        }
        /**
         * 2. Document already submitted
         */
        elseif (
            !in_array($employee->emp_document_status, ["process", "rejected"])
        ) {
            $status = "error";
            $message = $messages[1];
        }
        /**
         * 3. Due date expired
         */
        elseif (
            Carbon::parse($employee->emp_document_due_date)->lt(Carbon::today())
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

        $documentNamesList = DocumentNamesList::all(); // Get the array
        return view("frontend.upload-document.index", [
            "employee" => $employee,
            "status" => $status,
            "message" => $message,
            "editDocument" => $documentEdit,
            "documentNamesList" => $documentNamesList,
            "main_url" => $main_url,
            "document_list" => $document_list,
        ]);
    }

    // Save File document in db
    public function store(Request $request)
    {
        // Fetch employee using emp_url
        $employee = EmployeesNewJoiner::findOrFail($request->emp_id);
        $request->validate([
            "document_type" => "required|string",
            // "document_file" => "required|file",
            "document_file" => "required_if:document_type,!emergency_contact|file",
        ]);

        /**
         * Validation rules based on document type
         */
        $documentRules = [
            "aadhaar_card" => "mimes:pdf|max:4048",
            "pan_card" => "mimes:pdf|max:4048",
            "passport" => "mimes:pdf|max:4048",
            "address_proof" => "mimes:pdf|max:4048",
            "photo" => "mimes:jpg,jpeg,png|max:1024",

            "highest_certificate" => "mimes:pdf|max:4048",
            "additional_certification" => "mimes:pdf|max:4048",

            "appointment_letter" => "mimes:pdf|max:4048",
            "increment_letter" => "mimes:pdf|max:4048",
            "salary_slips" => "mimes:pdf|max:3072",
            "bank_statement" => "mimes:pdf|max:3072",

            "pf_uan" => "mimes:pdf|max:1024",
            "cancelled_cheque" => "mimes:pdf,jpg,jpeg|max:1024",
            "emergency_contact" => "string|max:255",
        ];

        $maxSizes = [
            "aadhaar_card" => "2 MB",
            "pan_card" => "2 MB",
            "passport" => "2 MB",
            "address_proof" => "2 MB",
            "photo" => "1 MB",
            "highest_certificate" => "2 MB",
            "additional_certification" => "2 MB",
            "appointment_letter" => "2 MB",
            "increment_letter" => "2 MB",
            "salary_slips" => "3 MB",
            "bank_statement" => "3 MB",
            "pf_uan" => "1 MB",
            "cancelled_cheque" => "1 MB",
            "emergency_contact" => "phone number format ",
        ];

        if (!array_key_exists($request->document_type, $documentRules)) {
            return back()->withErrors([
                "document_type" => "Invalid document type selected",
            ]);
        }

        $request->validate(
            [
                "document_file" => $documentRules[$request->document_type],
            ],
            [
                "document_file.max" => "The '{$request->document_type}' file must not be greater than {$maxSizes[$request->document_type]}.",
            ]
        );

        if ($request->document_type === "emergency_contact") {
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
        return redirect()
            ->route("Newjoiner.uploaddocument", $employee->emp_url)
            ->with([
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

        // File is mandatory in re-upload
        $request->validate([
            // 'document_file' => 'required|file'
            "document_file" => "required_if:document_type,!emergency_contact|file",
        ]);

        /**
         * Same validation rules as store()
         */
        $documentRules = [
            "aadhaar_card" => "mimes:pdf|max:4048",
            "pan_card" => "mimes:pdf|max:4048",
            "passport" => "mimes:pdf|max:4048",
            "address_proof" => "mimes:pdf|max:4048",
            "photo" => "mimes:jpg,jpeg,png|max:1024",

            "highest_certificate" => "mimes:pdf|max:4048",
            "additional_certification" => "mimes:pdf|max:4048",

            "appointment_letter" => "mimes:pdf|max:4048",
            "increment_letter" => "mimes:pdf|max:4048",
            "salary_slips" => "mimes:pdf|max:3072",
            "bank_statement" => "mimes:pdf|max:3072",

            "pf_uan" => "mimes:pdf|max:1024",
            "cancelled_cheque" => "mimes:pdf,jpg,jpeg|max:1024",
            "emergency_contact" => "string|max:255",
        ];

        $maxSizes = [
            "aadhaar_card" => "2 MB",
            "pan_card" => "2 MB",
            "passport" => "2 MB",
            "address_proof" => "2 MB",
            "photo" => "1 MB",
            "highest_certificate" => "2 MB",
            "additional_certification" => "2 MB",
            "appointment_letter" => "2 MB",
            "increment_letter" => "2 MB",
            "salary_slips" => "3 MB",
            "bank_statement" => "3 MB",
            "pf_uan" => "1 MB",
            "cancelled_cheque" => "1 MB",
            "emergency_contact" => "phone number format ",
        ];

        $documentType = $document->emp_select_document;

        if (!array_key_exists($documentType, $documentRules)) {
            return back()->withErrors([
                "document_file" => "Invalid document type",
            ]);
        }

        // Validate file based on existing document type
        $request->validate(
            [
                "document_file" => $documentRules[$documentType],
            ],
            [
                "document_file.max" =>
                "The '{$documentType}' file must not be greater than {$maxSizes[$documentType]}.",
            ]
        );

        if ($request->document_type === "emergency_contact") {
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

        return redirect()
            ->route("Newjoiner.uploaddocument", $employee->emp_url)
            ->with([
                "success" => "Document re-uploaded successfully",
                "bg-color" => "success",
            ]);
    }

    // user update email send
    public function UserUpdate($id)
    {

        $employee = EmployeesNewJoiner::where("id", $id)->first();
        $employee->emp_status = "process";
        $employee->emp_document_status = "process";
        $employee->save();

        // Get the HR user
        $user_list = User::find($employee->emp_hr_id);

        $NewEmployeesDocument = NewEmployeesDocument::where("emp_id", $employee->id)->where("emp_document_status", "upload")->get();
        foreach ($NewEmployeesDocument as $document) {
            $document->emp_document_status = "process";
            $document->save();
        }
        // Send email
        Mail::send(
            "emails.new_employee_document_submited",
            [
                "employee" => $employee,
            ],
            function ($message) use ($employee, $user_list) {
                // <-- Pass $user_list here
                $message
                    ->to($user_list->email)
                    ->subject($employee->emp_name . " Documents Submitted");
            }
        );
        return redirect(route("employee.documents.thankyou"));
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
        if (!$employee || $employee->emp_status !== "active") {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        }
        /**
         * 2. Document already submitted
         */
        elseif (
            !in_array($employee->emp_document_status, ["process", "rejected"])
        ) {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        }
        /**
         * 3. Due date expired
         */
        elseif (
            Carbon::parse($employee->emp_document_due_date)->lt(Carbon::today())
        ) {
            return view("frontend.upload-document.thank-you", ['message' => $message]);
        } else
            $document_list = NewEmployeesDocument::where("emp_id", $employee->id)->where("emp_document_file", $file_name)->first();
        // $file_url = $main_url . $document_list->emp_document_file_path;
        $file_url = $main_url. 'public/' . $document_list->emp_document_file_path;

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
            ->route("Newjoiner.uploaddocument", $employee->emp_url)
            ->with([
                "success" => "Document deleted successfully",
                "bg-color" => "success",
            ]);
    }
}
