@php
    use App\Data\CompanyPolicyDocuments;
    use App\Support\CandidateSignature;
    use App\Support\OnboardingJoiningAccess;

    $policies = $policyDocuments ?? [];
    $policyAccepted = !empty($employee->emp_policy_accepted_at);
    $onboardingStep = $employee->onboardingStep();
    $joiningProcessStarted = OnboardingJoiningAccess::hasJoiningProcessStarted($employee);
    $canReadPolicy = $joiningProcessStarted && $onboardingStep === 'join_forms_sent';
    $canAcceptPolicy = $onboardingStep === 'join_forms_submitted' && !$policyAccepted;
    $acceptedKeys = $employee->emp_other['policy_acceptances'] ?? [];
@endphp

@if ($policyAccepted)
    <div class="alert alert-success">
        Company policies accepted on {{ $employee->emp_policy_accepted_at->format('d M Y, h:i A') }}.
        @if (!empty($acceptedKeys))
            <div class="small mt-1">Accepted: {{ implode(', ', array_map(fn ($k) => str_replace('_', ' ', $k), $acceptedKeys)) }}</div>
        @endif
        @if ($employee->emp_policy_signature)
            <div class="mt-2"><img src="{{ CandidateSignature::policy($employee, true) }}" alt="Policy signature" style="max-width:200px;border:1px solid #ccc;"></div>
        @endif
    </div>
    @forelse ($policies as $doc)
        <div class="info-box mb-2">
            <h6 class="mb-1">{{ $doc['title'] ?? 'Policy' }}</h6>
            @include('shared.policy-pdf-viewer', ['employee' => $employee, 'doc' => $doc])
        </div>
    @empty
    @endforelse
@elseif ($canAcceptPolicy)
    <p class="small text-muted mb-3">Read each policy document, tick acceptance where required, then sign below.</p>

    @forelse ($policies as $doc)
        @php
            $docKey = $doc['key'] ?? '';
            $needsAccept = !empty($doc['require_accept']);
        @endphp
        <div class="info-box mb-3">
            <h6 class="mb-1">{{ $doc['title'] ?? 'Policy' }}</h6>
            @if (!empty($doc['description']))
                <p class="small text-muted mb-2">{{ $doc['description'] }}</p>
            @endif
            @include('shared.policy-pdf-viewer', ['employee' => $employee, 'doc' => $doc])
            @if ($needsAccept)
                <div class="form-check mt-2">
                    <input class="form-check-input policy-accept-check" type="checkbox"
                        name="policy_accept_{{ $docKey }}" value="1" id="policyAccept_{{ $docKey }}" required
                        form="policyAcceptForm">
                    <label class="form-check-label" for="policyAccept_{{ $docKey }}">
                        Yes, I have read and I accept <strong>{{ $doc['title'] ?? 'this policy' }}</strong>.
                    </label>
                </div>
            @endif
        </div>
    @empty
        <div class="alert alert-info">Policy PDFs are not configured yet. Add entries in <code>form_join/company-policies.json</code>.</div>
    @endforelse

    <div id="policySignatureBox">
        <form method="POST" action="{{ route('onboarding.save', $employee->emp_url) }}" enctype="multipart/form-data"
            id="policyAcceptForm" onsubmit="return savePolicySignature()">
            @csrf
            <input type="hidden" name="action" value="policy">
            <input type="hidden" name="signature" id="policySignatureInput">
            <h6 class="fw-bold mt-2">Your signature</h6>
            <div class="mb-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="showPolicyDraw()">Draw</button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="showPolicyUpload()">Upload</button>
            </div>
            <div id="policyDrawArea">
                <canvas id="policyPad" style="width:100%;height:160px;border:1px solid #ccc;border-radius:6px;"></canvas>
                <button type="button" class="btn btn-warning btn-sm mt-2" onclick="clearPolicyPad()">Clear</button>
            </div>
            <div id="policyUploadArea" style="display:none;" class="mt-2">
                <input type="file" name="signature_file" id="policyFileInput" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onchange="onPolicyFile()">
                <small class="text-muted">JPG or PNG only</small>
            </div>
            <button type="submit" id="policySubmitBtn" class="btn btn-brand mt-3" disabled>Accept Policies & Submit Signature</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
    let policyPad, policyChecker;
    function policyFormReady() {
        const checks = document.querySelectorAll('.policy-accept-check');
        const allChecked = Array.from(checks).every(c => c.checked);
        const d = policyPad && !policyPad.isEmpty();
        const f = document.getElementById('policyFileInput')?.files.length;
        document.getElementById('policySubmitBtn').disabled = !(allChecked && (d || f));
    }
    document.addEventListener('DOMContentLoaded', function(){
        const c=document.getElementById('policyPad');
        if(!c)return;
        resizePolicyCanvas(c);
        policyPad=new SignaturePad(c,{penColor:'#000'});
        policyChecker=setInterval(policyFormReady,300);
        document.querySelectorAll('.policy-accept-check').forEach(el => el.addEventListener('change', policyFormReady));
    });
    function resizePolicyCanvas(c){const r=Math.max(window.devicePixelRatio||1,1);c.width=c.offsetWidth*r;c.height=c.offsetHeight*r;c.getContext('2d').scale(r,r);}
    function showPolicyDraw(){document.getElementById('policyDrawArea').style.display='block';document.getElementById('policyUploadArea').style.display='none';document.getElementById('policyFileInput').value='';policyFormReady();}
    function showPolicyUpload(){document.getElementById('policyDrawArea').style.display='none';document.getElementById('policyUploadArea').style.display='block';if(policyPad)policyPad.clear();policyFormReady();}
    function clearPolicyPad(){if(policyPad)policyPad.clear();policyFormReady();}
    function onPolicyFile(){policyFormReady();}
    function savePolicySignature(){
        const checks = document.querySelectorAll('.policy-accept-check');
        for (const c of checks) {
            if (!c.checked) { alert('Please confirm you have read and accept all required policies.'); return false; }
        }
        if(document.getElementById('policyDrawArea').style.display!=='none'){
            if(!policyPad||policyPad.isEmpty()){alert('Please draw your signature.');return false;}
            document.getElementById('policySignatureInput').value=document.getElementById('policyPad').toDataURL('image/png');
            return true;
        }
        if(!document.getElementById('policyFileInput').files.length){alert('Please upload signature.');return false;}
        return true;
    }
    </script>
@elseif ($canReadPolicy)
    <div class="alert alert-info py-2 small mb-3">
        Please read each policy below before submitting your joining details on the <strong>Joining</strong> tab.
        Signature and formal acceptance will be required here after you submit joining details.
    </div>

    @forelse ($policies as $doc)
        <div class="info-box mb-3">
            <h6 class="mb-1">{{ $doc['title'] ?? 'Policy' }}</h6>
            @if (!empty($doc['description']))
                <p class="small text-muted mb-2">{{ $doc['description'] }}</p>
            @endif
            @include('shared.policy-pdf-viewer', ['employee' => $employee, 'doc' => $doc])
        </div>
    @empty
        <div class="alert alert-info mb-0">Policy PDFs are not configured yet. Add entries in <code>form_join/company-policies.json</code>.</div>
    @endforelse
@else
    <div class="alert alert-info mb-0">
        @if ($joiningProcessStarted)
            Company policy acceptance will be available after you submit your joining details on the <strong>Joining</strong> tab.
        @else
            Company policy acceptance will be available when HR starts the joining process.
        @endif
    </div>
@endif
