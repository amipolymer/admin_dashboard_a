@php
    use App\Support\OnboardingLetterDeadline;
    $canRespond = OnboardingLetterDeadline::canCandidateActOnAppointment($employee);
    $isAccepted = $employee->emp_appointment_letter_status === 'accept';
    $isRejected = $employee->emp_appointment_letter_status === 'reject';
    $previewUrl = route('appointment.preview.pdf', ['id' => $employee->id, 'token' => $employee->emp_url]);
@endphp

<div class="appointment-letter-panel text-start">
    <iframe src="{{ $previewUrl }}" style="width:100%;height:{{ $iframeHeight ?? '55vh' }};border:1px solid #dee2e6;border-radius:8px;"></iframe>

    @if ($isAccepted)
        @php
            $apptSrOk = \App\Support\SrHrLetterApproval::isApproved($employee, \App\Support\SrHrLetterApproval::TYPE_APPOINTMENT);
            $apptSrPending = $employee->onboardingStep() === 'appointment_pending_sr_hr';
        @endphp
        <!-- <div class="alert alert-success mt-3 mb-0">
            Appointment letter <strong>accepted</strong>.
            @if ($apptSrPending)
                <p class="small mb-0 mt-2">Awaiting <strong>SR-HR approval</strong>. HR will confirm when onboarding is complete.</p>
            @elseif ($apptSrOk)
                <p class="small mb-0 mt-2"><strong>SR-HR approved.</strong> Onboarding is complete pending HR finalization.</p>
            @else
                <p class="small mb-0 mt-2">HR will submit your signed letter to SR-HR for final approval.</p>
            @endif
            @php $apptSig = ($employee->emp_other ?? [])['appointment_signature'] ?? null; @endphp
            @if ($apptSig)
                <div class="mt-2">
                    <span class="d-block small text-muted">Your signature:</span>
                    <img src="{{ $apptSig }}" alt="Appointment signature" style="max-width:220px;border:1px solid #ccc;border-radius:4px;">
                </div>
            @endif
        </div> -->
    @elseif ($isRejected)
        <div class="alert alert-warning mt-3 mb-0">
            Appointment <strong>declined</strong>.
            @if ($employee->emp_appointment_reject_reason)
                <br><strong>Reason:</strong> {{ $employee->emp_appointment_reject_reason }}
            @endif
        </div>
    @elseif ($canRespond)
        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center" id="apptActionButtons">
            <button type="button" class="btn btn-success btn-sm" onclick="handleAcceptAppt()">Accept Appointment</button>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectApptModal">Reject</button>
        </div>
        <div id="apptSignatureBox" class="mt-4" style="display:none;">
            <form method="POST" action="{{ route('employee.documents.storeAppointmentSignature') }}" enctype="multipart/form-data" onsubmit="return saveApptSignature()">
                @csrf
                <input type="hidden" name="emp_id" value="{{ $employee->id }}">
                <input type="hidden" name="signature" id="apptSignatureInput">
                @if (!empty($returnUrl))<input type="hidden" name="return_url" value="{{ $returnUrl }}">@endif
                <h6 class="fw-bold">Sign to accept appointment</h6>
                <div class="mb-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showApptDraw()">Draw</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showApptUpload()">Upload</button>
                </div>
                <div id="apptDrawArea"><canvas id="apptPad" style="width:100%;height:180px;border:1px solid #ccc;border-radius:6px;"></canvas>
                    <button type="button" class="btn btn-warning btn-sm mt-2" onclick="clearApptPad()">Clear</button></div>
                <div id="apptUploadArea" style="display:none;" class="mt-2">
                    <input type="file" name="signature_file" id="apptFileInput" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onchange="onApptFile()">
                    <small class="text-muted d-block">JPG or PNG only</small>
                </div>
                <button type="submit" id="apptSubmitBtn" class="btn btn-success btn-sm mt-3" disabled>Submit & Accept</button>
                <button type="button" class="btn btn-link btn-sm" onclick="cancelApptAccept()">Cancel</button>
            </form>
        </div>
        <div class="modal fade" id="rejectApptModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('employee.documents.rejectAppointment') }}" onsubmit="return validateLetterRejectReasonField(this.querySelector('[name=reason]'))">
                        @csrf
                        <input type="hidden" name="emp_id" value="{{ $employee->id }}">
                        @if (!empty($returnUrl))<input type="hidden" name="return_url" value="{{ $returnUrl }}">@endif
                        <div class="modal-header"><h5 class="modal-title">Decline Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label class="form-label fw-bold">Reason *</label>
                            <textarea name="reason" id="rejectApptReason" class="form-control" rows="4" required minlength="10" maxlength="1000" placeholder="Example: I am joining another company">{{ old('reason') }}</textarea>
                            <small class="text-muted">At least 10 characters, at least two proper words. Letters, numbers, and spaces only.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@if ($canRespond)
@include('shared.letter-reject-reason-validation-script')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
let apptPad, apptChecker;
function handleAcceptAppt(){document.getElementById('apptSignatureBox').style.display='block';document.getElementById('apptActionButtons').style.display='none';const c=document.getElementById('apptPad');resizeApptCanvas(c);apptPad=new SignaturePad(c,{penColor:'#000'});apptChecker=setInterval(()=>{const d=apptPad&&!apptPad.isEmpty();const f=document.getElementById('apptFileInput').files.length;document.getElementById('apptSubmitBtn').disabled=!(d||f);},300);}
function cancelApptAccept(){document.getElementById('apptSignatureBox').style.display='none';document.getElementById('apptActionButtons').style.display='flex';clearInterval(apptChecker);}
function resizeApptCanvas(c){const r=Math.max(window.devicePixelRatio||1,1);c.width=c.offsetWidth*r;c.height=c.offsetHeight*r;c.getContext('2d').scale(r,r);}
function showApptDraw(){document.getElementById('apptDrawArea').style.display='block';document.getElementById('apptUploadArea').style.display='none';document.getElementById('apptFileInput').value='';}
function showApptUpload(){document.getElementById('apptDrawArea').style.display='none';document.getElementById('apptUploadArea').style.display='block';if(apptPad)apptPad.clear();}
function clearApptPad(){if(apptPad)apptPad.clear();}
function onApptFile(){document.getElementById('apptSubmitBtn').disabled=!document.getElementById('apptFileInput').files.length;}
function saveApptSignature(){if(document.getElementById('apptDrawArea').style.display!=='none'){if(!apptPad||apptPad.isEmpty()){alert('Draw signature');return false;}document.getElementById('apptSignatureInput').value=document.getElementById('apptPad').toDataURL('image/png');return true;}if(!document.getElementById('apptFileInput').files.length){alert('Upload signature');return false;}return true;}
</script>
@endif
