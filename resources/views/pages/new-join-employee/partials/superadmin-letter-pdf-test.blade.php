@if (Auth::check() && Auth::user()->role === 'superadmin')
<div class="card mb-3 shadow-sm border-warning">
    <div class="card-header bg-white py-2">
        <strong class="text-warning"><i class="dw dw-file-1"></i> Letter PDF test</strong>
        <span class="badge badge-warning ml-1">Superadmin only</span>
    </div>
    <div class="card-body py-3">
        <p class="small text-muted mb-3">
            HTML preview uses the same page as HR and candidates. Superadmin can use right-click and inspect on that page.
            PDF download tests Dompdf output directly.
        </p>

        <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
                <h6 class="font-weight-bold mb-2">Offer letter</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('offer.preview.pdf', $employee->id) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mr-1 mb-1">
                        View HTML
                    </a>
                    <a href="{{ route('offer.preview.pdf', ['id' => $employee->id, 'simulate_accepted' => 1]) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-info mr-1 mb-1">
                        View HTML (simulate accepted)
                    </a>
                    <a href="{{ route('EmployeeJoiner.letterPdfTest', ['id' => $employee->id, 'type' => 'offer', 'format' => 'pdf']) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mr-1 mb-1">
                        Download PDF
                    </a>
                    <a href="{{ route('EmployeeJoiner.letterPdfTest', ['id' => $employee->id, 'type' => 'offer', 'format' => 'pdf', 'simulate_accepted' => 1]) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-success mb-1">
                        PDF (simulate accepted)
                    </a>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="font-weight-bold mb-2">Appointment letter</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('appointment.preview.pdf', $employee->id) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mr-1 mb-1">
                        View HTML
                    </a>
                    <a href="{{ route('appointment.preview.pdf', ['id' => $employee->id, 'simulate_accepted' => 1]) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-info mr-1 mb-1">
                        View HTML (simulate accepted)
                    </a>
                    <a href="{{ route('EmployeeJoiner.letterPdfTest', ['id' => $employee->id, 'type' => 'appointment', 'format' => 'pdf']) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mr-1 mb-1">
                        Download PDF
                    </a>
                    <a href="{{ route('EmployeeJoiner.letterPdfTest', ['id' => $employee->id, 'type' => 'appointment', 'format' => 'pdf', 'simulate_accepted' => 1]) }}"
                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-success mb-1">
                        PDF (simulate accepted)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
