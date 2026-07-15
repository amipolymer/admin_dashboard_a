<?php

namespace App\Http\Controllers\Dashboard;

use App\Data\DocumentNamesList;
use App\Http\Controllers\Controller;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class EmployeeNewJoinerController extends Controller
{
    public $Url = 'Mph6MSf9L4NU4NWRRuxTYAvZM';

    public function index()
    {
        $userRole = Auth::user()->role;
        if ($userRole == 'hr') {
            $employeesJoinerList = EmployeesNewJoiner::where('emp_hr_id', Auth::user()->id)->latest()->get();
        } else {
            $employeesJoinerList = EmployeesNewJoiner::latest()->get();
        }

        return view(
            "pages.new-join-employee.index",
            compact("employeesJoinerList")
        );
    }

    public function create()
    {
        $roleList = [];
        return view("pages.new-join-employee.create", compact("roleList"));
    }

    public function store(Request $request)
    {
        $request->validate([
            "emp_hr_id" => "required",
            "emp_name" => "required|string",
            "emp_location" => "required|string",
            "emp_phone" => "required|unique:employees_new_joiners,emp_phone",
            "emp_email" => "required|email|unique:employees_new_joiners,emp_email",
            "emp_role" => "required",
            "emp_date" => "required|date",
            "emp_document_due_date" => "required|date",
        ]);
        $emp_url = Str::random(80);
        $employee = EmployeesNewJoiner::create([
            "emp_hr_id" => $request->emp_hr_id,
            "emp_name" => $request->emp_name,
            "emp_phone" => $request->emp_phone,
            "emp_location" => $request->emp_location,
            "emp_role" => $request->emp_role,
            "emp_date" => $request->emp_date,
            "emp_document_due_date" => $request->emp_document_due_date,
            "emp_email" => $request->emp_email,
            "emp_url" => $emp_url,
            "emp_status" => "active",
        ]);
        $uploadLink = config('app.main_url') . $this->Url . '/' . $emp_url;
        $documentNamesList = DocumentNamesList::all();
        Mail::send(
            "emails.new_employee_upload",
            [
                "employee" => $employee,
                "uploadLink" => $uploadLink,
                "documentNamesList" => $documentNamesList,
                "dueDate" => Carbon::parse(
                    $employee->emp_document_due_date
                )->format("d M Y"),
            ],
            function ($message) use ($employee) {
                $message
                    ->to($employee->emp_email)
                    ->subject("Upload Your Joining Documents");
            }
        );
        return redirect()
            ->route("EmployeeJoiner.Create")
            ->with([
                "success" => "Employee joined successfully",
                "bg-color" => "success",
            ]);
    }

    public function edit($id)
    {
        $main_url = config('app.main_url');
        $employee = EmployeesNewJoiner::findOrFail($id);
        $documentNamesList = DocumentNamesList::all();
        $document_list = NewEmployeesDocument::where("emp_id", $employee->id)->get();
        return view("pages.new-join-employee.edit", compact("employee", "document_list", "main_url", "documentNamesList"));
    }

    public function show($id)
    {
        $main_url = config('app.main_url');
        $employee = EmployeesNewJoiner::findOrFail($id);
        $documentNamesList = DocumentNamesList::all();
        $document_list = NewEmployeesDocument::where("emp_id", $employee->id)->get();
        return view("pages.new-join-employee.show", compact("employee", "document_list", "main_url", "documentNamesList"));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $employee = EmployeesNewJoiner::findOrFail($id);
        if (!empty($request->has('emergency_contact'))) {
            }else{
            $employee->emergency_contact = $request->emergency_contact;

        }
        $employee->emp_date = $request->emp_date;
        $employee->emp_document_due_date = $request->emp_document_due_date;
        $employee->emp_location = $request->emp_location;
        $employee->emp_phone = $request->emp_phone;
        $employee->emp_role = $request->emp_role;
        $employee->emp_email = $request->emp_email;
        $employee->emp_name = $request->emp_name;
        $employee->emp_status = $request->emp_status;
        if ($request->emp_status == 'active') {
            $employee->emp_document_status = 'process';
        }
        $employee->save();
        return redirect()->back()->with([
            'success'  => 'Employee updated successfully',
            'bg-color' => 'success',
        ]);
    }

    public function updateDocument(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        $live_url   = config('app.main_url') . $this->Url . '/';
        $uploadLink = $live_url . $employee->emp_url;

        $data = $request->only([
            'emp_name',
            'emp_email',
            'emp_phone',
            'emp_role',
            'emp_location',
            'emp_date',
            'emp_document_due_date',
        ]);

        $rejectedFiles = [];
        $hasRejection  = false;
        $allApproved   = true;
        $allRemarks    = [];


        //   Process document statuses

        if ($request->filled('doc_status')) {
            foreach ($request->input('doc_status') as $docId => $status) {

                $document = NewEmployeesDocument::find($docId);
                if (!$document) {
                    continue;
                }

                $document->emp_document_status = $status;

                if ($status === 'rejected') {

                    $remark = trim($request->input("doc_remark.$docId", ''));

                    if (empty($remark)) {
                        return back()
                            ->withErrors(["doc_remark.$docId" => 'Rejection reason is required'])
                            ->withInput();
                    }

                    $document->rejection_reason = $remark;

                    $rejectedFiles[] = $document->emp_document_file . ' : ' . $remark;

                    $hasRejection = true;
                    $allApproved  = false;
                    $allRemarks[] = $remark;
                } else {
                    $document->rejection_reason = null;
                }

                $document->save();
            }
        }


        //   Final statuses
        $finalDocStatus = 'process';
        $finalEmpStatus = $employee->emp_status;

        if ($hasRejection) {

            $finalDocStatus = 'rejected';
            $finalEmpStatus = 'active';
        } elseif ($allApproved && $request->filled('doc_status')) {

            $finalDocStatus = 'completed';
            $finalEmpStatus = 'completed';


            //   Folder rename & file move (only if manual_emp_id changes)

            if ($request->filled('manual_emp_id') && $request->manual_emp_id != $employee->emp_folder) {

                $newFolderName   = 'EMP_' . $request->manual_emp_id;
                $emp_folder_path = 'uploads/employees/';
                $oldFolder       = $employee->emp_folder_path . $employee->emp_folder;
                $newFolder       = $emp_folder_path . $newFolderName;

                // Create new folder if it does not exist
                if (!Storage::disk('public')->exists($newFolder)) {
                    Storage::disk('public')->makeDirectory($newFolder);
                }

                $documents = NewEmployeesDocument::where('emp_id', $employee->id)->get();

                foreach ($documents as $doc) {

                    $oldFile = $oldFolder . '/' . $doc->emp_document_file;

                    if (!Storage::disk('public')->exists($oldFile)) {
                        continue;
                    }

                    $newFileName = preg_replace('/EMP_\d+/', $newFolderName, $doc->emp_document_file);
                    $newFilePath = $newFolder . '/' . $newFileName;

                    // Move file only if the name changes
                    if ($newFileName !== $doc->emp_document_file) {
                        Storage::disk('public')->move($oldFile, $newFilePath);
                    }

                    // Update document DB
                    $doc->emp_document_file      = $newFileName;
                    $doc->emp_document_file_path = 'storage/' . $newFilePath;
                    $doc->save();
                }

                // Delete old folder if empty
                if (empty(Storage::disk('public')->files($oldFolder))) {
                    Storage::disk('public')->deleteDirectory($oldFolder);
                }

                // Update employee DB directly
                $employee->emp_folder      = $newFolderName;
                $employee->emp_folder_path = $emp_folder_path;
                $employee->save();
            }
        }

        $data['emp_document_status'] = $finalDocStatus;
        $data['emp_status']          = $finalEmpStatus;

        //  | Store rejection remarks
        if (!empty($allRemarks)) {

            $other      = $employee->emp_other ?? [];
            $rejections = $other['rejections'] ?? [];

            $rejections[] = [
                'sr'          => count($rejections) + 1,
                'date'        => now()->toDateString(),
                'remark'      => implode(", \n", $allRemarks),
                'document_id' => $request->input('document_id', []),
            ];

            $data['emp_other'] = array_merge($other, [
                'rejections' => $rejections
            ]);
        }


        //   Send rejection email

        if ($hasRejection && !empty($rejectedFiles)) {

            Mail::send(
                'emails.new_employee_re_upload',
                [
                    'employee'   => $employee,
                    'uploadLink' => $uploadLink,
                    'file_name'  => $rejectedFiles,
                    'remark'     => 'Please check individual remarks on documents',
                    'dueDate'    => Carbon::parse(
                        $employee->emp_document_due_date ?? now()->addDays(7)
                    )->format('d M Y'),
                ],
                function ($message) use ($employee) {
                    $message->to($employee->emp_email)
                        ->subject('Re-Upload Joining Documents');
                }
            );
        }

        //  | Update remaining employee fields
        $employee->update($data);

        return redirect()->back()->with([
            'success'  => 'Employee documents updated successfully',
            'bg-color' => 'success',
        ]);
    }

    // public function delete($id)
    // {
    //     // EmployeesNewJoiner::findOrFail($id)->delete();
    //     return back()->with("success", "Employee deleted");
    // }

    public function delete($id)
{
    DB::transaction(function () use ($id) {

        $employee = EmployeesNewJoiner::withTrashed()->findOrFail($id);

        // Permanently delete related documents
        NewEmployeesDocument::where('emp_id', $employee->id)
            ->forceDelete();

        // Paths relative to storage/app/public
        $folder1 = 'uploads/employees/' . $employee->emp_folder;
        $folder2 = 'team/employees/' . $employee->emp_folder;

        // Delete folders
        if (Storage::disk('public')->exists($folder1)) {
            Storage::disk('public')->deleteDirectory($folder1);
        }

        if (Storage::disk('public')->exists($folder2)) {
            Storage::disk('public')->deleteDirectory($folder2);
        }

        // Permanently delete employee
        $employee->forceDelete();
    });

    return back()->with([
        'success'  => 'Employee deleted successfully!',
        'bg-color' => 'success',
    ]);
}

    public function updateStatus(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);
        $employee->update([
            "emp_document_status" => $request->emp_document_status,
            "emp_last_updated_at" => now(),
        ]);

        return back()->with("success", "Status updated");
    }

    public function DocVerify($id)
    {
        $document_list = NewEmployeesDocument::findOrFail($id);
        if ($document_list->emp_document_status == 'process') {
            $document_list->emp_document_status = 'approved';
        } else {
            $document_list->emp_document_status = 'process';
        }
        $document_list->save();
        return redirect()->back()->with('success', 'Document approved successfully!');
    }

    public function reSendEmail($id, $type, $remarkId)
    {
        $type = $type;
        $remarkId = $remarkId;
        $live_url = config('app.main_url') . $this->Url . '/';
        $documentNamesList = DocumentNamesList::all();
        $employee = EmployeesNewJoiner::findOrFail($id);
        $uploadLink = $live_url . $employee->emp_url;

        if ($type == 'reupload') {
            $file_name = [];
            $remarkList = $employee->emp_other['rejections'];
            $document_ids = $remarkList[$remarkId]['document_id'];
            foreach ($document_ids as $document) {
                $document_list = NewEmployeesDocument::findOrFail($document);
                if ($document_list->emp_document_status == 'rejected') {
                    $file_name[] = $document_list->emp_document_file;
                }
            }
            $remark = $remarkList[$remarkId]['remark'];
            Mail::send(
                "emails.new_employee_re_upload",
                [
                    "employee" => $employee,
                    "uploadLink" => $uploadLink,
                    "file_name" => $file_name,
                    "remark" => $remark,
                    "dueDate" => Carbon::parse(
                        $employee->emp_document_due_date
                    )->format("d M Y"),
                ],
                function ($message) use ($employee) {
                    $message
                        ->to($employee->emp_email)
                        ->subject("Re-Upload Your Joining Documents Resend");
                }
            );
        } else {
            Mail::send(
                "emails.new_employee_upload",
                [
                    "employee" => $employee,
                    "uploadLink" => $uploadLink,
                    "documentNamesList" => $documentNamesList,
                    "dueDate" => Carbon::parse(
                        $employee->emp_document_due_date
                    )->format("d M Y"),
                ],
                function ($message) use ($employee) {
                    $message
                        ->to($employee->emp_email)
                        ->subject("Upload Your Joining Documents-resend");
                }
            );
        }
        return redirect()->back()
            ->with([
                "success" => "Email resent successfully!",
                "bg-color" => "success",
            ]);
    }


public function downloadAll($id)
{
    $employee = EmployeesNewJoiner::findOrFail($id);
    $documents = NewEmployeesDocument::where('emp_id', $employee->id)->get();

    $zipFileName = $employee->emp_folder.'_Documents.zip';
    $zipFilePath = storage_path('app/public/'.$zipFileName);

    $zip = new \ZipArchive();
    if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
        foreach ($documents as $doc) {
            $filePath = storage_path('app/public/uploads/employees/'.$employee->emp_folder.'/'.$doc->emp_document_file);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $doc->emp_document_file);
            }
        }
        $zip->close();

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    } else {
        return redirect()->back()->with('error', 'Could not create zip file.');
    }
}


}
