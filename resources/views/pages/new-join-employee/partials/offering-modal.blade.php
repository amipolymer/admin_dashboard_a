{{-- OnGrid BGV offerings selection --}}
<div class="modal fade" id="offeringModal" tabindex="-1" role="dialog" aria-labelledby="offeringModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <form action="{{ route('EmployeeJoiner.documents.bvgLink') }}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="offeringModalLabel">Start OnGrid BGV (all verifications)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">
                        Onboards the candidate on OnGrid and starts background checks.
                        On retry, checks already running or completed are not re-requested — documents are updated in place.
                        Standard checks (address, criminal, employment, education) are selected by default on first start.
                        CV validation uploads the HR CV to OnGrid after onboarding. Checks without portal data are skipped automatically.
                    </p>
                    <div id="offering_container"></div>
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Start BGV on OnGrid</button>
                </div>
            </form>
        </div>
    </div>
</div>
