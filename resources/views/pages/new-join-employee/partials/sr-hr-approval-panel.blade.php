@php
    use App\Support\SrHrLetterApproval;
    $offerSr = SrHrLetterApproval::summaryForHr($employee, SrHrLetterApproval::TYPE_OFFER);
    $apptSr = SrHrLetterApproval::summaryForHr($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
@endphp

<div class="card mb-3 shadow-sm border-warning">
    <div class="card-header py-2 d-flex justify-content-between align-items-center hr-collapse-toggle bg-white"
         data-toggle="collapse" data-target="#collapseSrHrStatus" aria-expanded="true">
        <strong class="mb-0"><i class="dw dw-checked text-muted"></i> SR-HR — action needed</strong>
        <small class="text-muted collapse-hint">Hide</small>
    </div>
    <div id="collapseSrHrStatus" class="collapse show">
        <div class="card-body py-3">
            <div class="row">
                @if (in_array($offerSr['status'] ?? '', ['pending', 'rejected'], true))
                    <div class="col-md-6 mb-2 mb-md-0">
                        <h6 class="font-weight-bold text-primary small mb-2">Offer letter</h6>
                        @include('pages.new-join-employee.partials.sr-hr-status-block', [
                            'summary' => $offerSr,
                            'type' => SrHrLetterApproval::TYPE_OFFER,
                            'employee' => $employee,
                        ])
                    </div>
                @endif
                @if (in_array($apptSr['status'] ?? '', ['pending', 'rejected'], true))
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-primary small mb-2">Appointment letter</h6>
                        @include('pages.new-join-employee.partials.sr-hr-status-block', [
                            'summary' => $apptSr,
                            'type' => SrHrLetterApproval::TYPE_APPOINTMENT,
                            'employee' => $employee,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
