<?php

namespace App\Http\Controllers\AnnualReport;

use App\Http\Controllers\Controller;
use App\Models\AnnualReportViewForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AnnualReportViewFormController extends Controller
{

    public function index()
    {
        $annualReport = AnnualReportViewForm::all();
        // dd($AnnualReport);
        return view('pages.AnnualReport.index', compact('annualReport'));
    }

    public function create()
    {
        return view('pages.AnnualReport.create');
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'full_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:100',
            'company_name' => 'nullable|string|max:100',
            'mobile'       => 'nullable|string|max:10',
            'gst_no'       => 'nullable|string|max:15',
            'department'   => 'nullable|string|max:50',
            'location'     => 'nullable|string|max:100',
            'report_year'  => 'required|string|max:20',
        ]);

        // Create a new record
        $annualReport = new AnnualReportViewForm();
        $annualReport->full_name    = $validated['full_name'];
        $annualReport->email        = $validated['email'];
        $annualReport->company_name = $validated['company_name'] ?? null;
        $annualReport->mobile       = $validated['mobile'] ?? null;
        $annualReport->gst_no       = $validated['gst_no'] ?? null;
        $annualReport->department   = $validated['department'] ?? null;
        $annualReport->location     = $validated['location'] ?? null;
        $annualReport->report_year  = $validated['report_year'];
        $annualReport->status       = 'process'; // default status
        $annualReport->save();

        return redirect()
            ->route('AnnualReportViewForm.Index')
            ->with([
                'success' => 'Annual Report View Request added successfully!',
                'bg-color' => 'success'
            ]);
    }

    public function show($id)
    {
        $annualReport = AnnualReportViewForm::findOrFail($id);
        // dd($AnnualReport);
        return view('pages.AnnualReport.show', compact('annualReport'));
    }
    public function edit($id)
    {
        $annualReport = AnnualReportViewForm::findOrFail($id);
        if ($annualReport->status == 'process' && $annualReport->viewed_by == null) {
            $annualReport->viewed_by = Auth::user()->id;
            $annualReport->view_date = now();
            $annualReport->save();
        }
        // dd($AnnualReport);
        return view('pages.AnnualReport.edit', compact('annualReport'));
    }


    public function update(Request $request, $id)
    {
        $annualReport = AnnualReportViewForm::findOrFail($id);

        // if($request->status == 'reject') {
        if ($request->filled('remark')) {
            $remarksHistory = $annualReport->remark ?? [];
            if (!is_array($remarksHistory)) {
                $remarksHistory = json_decode($remarksHistory, true) ?? [];
            }
            $nextSr = count($remarksHistory) + 1;
            $remarksHistory[] = [
                'sr_no'    => $nextSr,
                'datetime' => now()->format('Y-m-d H:i:s'),
                'added_by' => Auth::user()->id, // you can also use name if you want
                'remark'   => $request->remark,
                'status'   => $request->status,
            ];
            $annualReport->remark = $remarksHistory;
            $annualReport->status = $request->status;
            $annualReport->approved_by = Auth::user()->id;
            $annualReport->approve_disapprove_date = now();
        }
        // }else{
        $annualReport->approved_by = Auth::user()->id;
        $annualReport->approve_disapprove_date = now();
        $this->sendFile($annualReport->id);
        // }
        $annualReport->save();
        return redirect()->back()->with([
            'success'  => 'Annual Report updated successfully',
            'bg-color' => 'success',
        ]);
    }

    public function delete($id)
    {
        $annualReport = AnnualReportViewForm::findOrFail($id);
        $annualReport->delete();
        return redirect()->back()->with([
            'success'  => 'Annual Report deleted successfully',
            'bg-color' => 'danger',
        ]);
    }

    public function sendFile($id)
    {
        // Get the annual report
        $annualReport = AnnualReportViewForm::findOrFail($id);
        $id = encrypt($annualReport->id);
        $live_url = 'https://amipolymer.com/annual-report/view_annual_report_' . $annualReport->report_year . '.html';
        // Send the email
        Mail::send(
            "emails.annual_report_send", // Your Blade email template
            [
                "annualReport" => $annualReport,
                "viewLink" => $live_url,
                "reportYear" => $annualReport->report_year,
            ],
            function ($message) use ($annualReport) {
                $message
                    ->to($annualReport->email)
                    ->subject("AMI Polymer-Annual Report View Access - " . $annualReport->report_year);
            }
        );

        return redirect()->back()->with([
            "success" => "Annual Report email sent successfully!",
            "bg-color" => "success",
        ]);
    }

    public function APIstore(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'full_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:100',
            'company_name' => 'nullable|string|max:100',
            'mobile'       => 'nullable|string|max:10',
            'gst_no'       => 'nullable|string|max:15',
            'department'   => 'nullable|string|max:50',
            'location'     => 'nullable|string|max:100',
            'report_year'  => 'required|string|max:20',
        ]);

        // Create new record
        $annualReport = new AnnualReportViewForm();
        $annualReport->full_name    = $validated['full_name'];
        $annualReport->email        = $validated['email'];
        $annualReport->company_name = $validated['company_name'] ?? null;
        $annualReport->mobile       = $validated['mobile'] ?? null;
        $annualReport->gst_no       = $validated['gst_no'] ?? null;
        $annualReport->department   = $validated['department'] ?? null;
        $annualReport->location     = $validated['location'] ?? null;
        $annualReport->report_year  = $validated['report_year'];
        $annualReport->status       = 'process';
        $annualReport->user_ip      = $request->ip(); // store user IP
        $annualReport->save();

        // Return JSON response
        return response()->json([
            'success' => true,
            'message' => 'Annual Report View Request added successfully!',
            'data'    => $annualReport
        ], 201);
    }

    public function export()
    {
        $fileName = "annual_report_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
        ];

        $columns = [
            'ID',
            'Full Name',
            'Email',
            'Company Name',
            'Mobile',
            'Location',
            'GST No',
            'Department',
            'Report Year',
            'Status',
            'Approved By',
            'Approve/Reject Date',
            'View Date',
            'Viewed By',
            'Created At',
            'Sr No',
            'DateTime',
            'Added By',
            'Status (Remark)',
            'Remark'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $records = AnnualReportViewForm::all();

            foreach ($records as $row) {
                $remarks = $row->remark;

                if (is_string($remarks)) {
                    $remarks = json_decode($remarks, true);
                }

                if ($remarks && isset($remarks['sr_no'])) {
                    $remarks = [$remarks];
                }

                if (!is_array($remarks) || empty($remarks)) {
                    fputcsv($file, [
                        $row->id,
                        $row->full_name,
                        $row->email,
                        $row->company_name,
                        $row->mobile,
                        $row->location,
                        $row->gst_no,
                        $row->department,
                        $row->report_year,
                        $row->status,
                        $row->approved->name ?? 'N/A',
                        $row->approve_disapprove_date,
                        $row->view_date,
                        $row->viewer->name ?? 'N/A',
                        $row->created_at,
                        '',
                        '',
                        '',
                        '',
                        ''
                    ]);
                    continue;
                }

                $first = true;
                foreach ($remarks as $log) {
                    if ($first) {
                        fputcsv($file, [
                            $row->id,
                            $row->full_name,
                            $row->email,
                            $row->company_name,
                            $row->mobile,
                            $row->location,
                            $row->gst_no,
                            $row->department,
                            $row->report_year,
                            $row->status,
                            $row->approved->name ?? 'N/A',
                            $row->approve_disapprove_date,
                            $row->view_date,
                            $row->viewer->name ?? 'N/A',
                            $row->created_at,
                            $log['sr_no'] ?? '',
                            $log['datetime'] ?? '',
                            isset($log['added_by']) ? (optional(\App\Models\User::find($log['added_by']))->name ?? $log['added_by']) : 'N/A',
                            $log['status'] ?? '',
                            str_replace(["\n", "\r"], ' ', $log['remark'] ?? '')
                        ]);
                        $first = false;
                    } else {
                        // Leave main data empty for “rowspan effect”
                        fputcsv($file, [
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $log['sr_no'] ?? '',
                            $log['datetime'] ?? '',
                            isset($log['added_by']) ? (optional(\App\Models\User::find($log['added_by']))->name ?? $log['added_by']) : 'N/A',
                            $log['status'] ?? '',
                            str_replace(["\n", "\r"], ' ', $log['remark'] ?? '')
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // public function export()
    // {
    //     $fileName = "annual_report_" . date('Ymd_His') . ".csv";

    //     $headers = [
    //         "Content-Type" => "text/csv",
    //         "Content-Disposition" => "attachment; filename=$fileName",
    //     ];

    //     $columns = [
    //         'ID',
    //         'Full Name',
    //         'Email',
    //         'Company Name',
    //         'Mobile',
    //         'Location',
    //         'GST No',
    //         'Department',
    //         'Report Year',
    //         'Status',
    //         'Approved By',
    //         'Approve/Reject Date',
    //         'View Date',
    //         'Viewed By',
    //         'Created At',
    //         'Sr No',
    //         'DateTime',
    //         'Added By',
    //         'Status (Remark)',
    //         'Remark'
    //     ];

    //     $callback = function () use ($columns) {
    //         $file = fopen('php://output', 'w');
    //         fputcsv($file, $columns);

    //         $records = AnnualReportViewForm::all();

    //         foreach ($records as $row) {
    //             $remarks = $row->remark;

    //             if (is_string($remarks)) {
    //                 $remarks = json_decode($remarks, true);
    //             }

    //             if ($remarks && isset($remarks['sr_no'])) {
    //                 $remarks = [$remarks];
    //             }

    //             if (!is_array($remarks) || empty($remarks)) {
    //                 fputcsv($file, [
    //                     $row->id,
    //                     $row->full_name,
    //                     $row->email,
    //                     $row->company_name,
    //                     $row->mobile,
    //                     $row->location,
    //                     $row->gst_no,
    //                     $row->department,
    //                     $row->report_year,
    //                     $row->status,
    //                     $row->approved->name ?? 'N/A',
    //                     $row->approve_disapprove_date,
    //                     $row->view_date,
    //                     $row->viewer->name ?? 'N/A',
    //                     $row->created_at,
    //                     '',
    //                     '',
    //                     '',
    //                     '',
    //                     ''
    //                 ]);
    //                 continue;
    //             }

    //             foreach ($remarks as $log) {
    //                 fputcsv($file, [
    //                     $row->id,
    //                     $row->full_name,
    //                     $row->email,
    //                     $row->company_name,
    //                     $row->mobile,
    //                     $row->location,
    //                     $row->gst_no,
    //                     $row->department,
    //                     $row->report_year,
    //                     $row->status,
    //                     $row->approved->name ?? 'N/A',
    //                     $row->approve_disapprove_date,
    //                     $row->view_date,
    //                     $row->viewer->name ?? 'N/A',
    //                     $row->created_at,
    //                     $log['sr_no'] ?? '',
    //                     $log['datetime'] ?? '',
    //                     isset($log['added_by']) ? (optional(\App\Models\User::find($log['added_by']))->name ?? $log['added_by']) : 'N/A',
    //                     $log['status'] ?? '',
    //                     str_replace(["\n", "\r"], ' ', $log['remark'] ?? ''),
    //                 ]);
    //             }
    //         }

    //         fclose($file);
    //     };

    //     return response()->stream($callback, 200, $headers);
    // }
}
