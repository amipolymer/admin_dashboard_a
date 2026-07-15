@php
    use App\Support\OnboardingLetterDeadline;
    $canRespond = OnboardingLetterDeadline::canCandidateActOnOffer($employee);
    $isAccepted = $employee->emp_offer_letter_status === 'accept';
    $isRejected = $employee->emp_offer_letter_status === 'reject';
    $returnUrl = $returnUrl ?? null;
    $iframeHeight = $iframeHeight ?? '75vh';
    $previewUrl = route('offer.preview.pdf', ['id' => $employee->id, 'token' => $employee->emp_url]);
@endphp

@if (empty($hideAlerts))
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif

<div class="offer-letter-panel text-start">
    <iframe src="{{ $previewUrl }}" style="width:100%;height:{{ $iframeHeight }};border:1px solid #dee2e6;border-radius:8px;"></iframe>

    @if ($isAccepted)
        <!-- <div class="alert alert-success mt-3 mb-0">
            <strong>Offer accepted.</strong> Thank you for signing.
            @if ($employee->emp_signature)
                <div class="mt-2">
                    <span class="d-block small text-muted">Your signature:</span>
                    <img src="{{ $employee->emp_signature }}" alt="Signature" style="max-width:220px;border:1px solid #ccc;border-radius:4px;">
                </div>
            @endif
        </div> -->
    @elseif ($isRejected)
        <div class="alert alert-warning mt-3 mb-0">
            <strong>Offer declined.</strong>
            @if ($employee->emp_offer_reject_reason)
                <p class="mb-0 mt-2"><strong>Reason:</strong> {{ $employee->emp_offer_reject_reason }}</p>
            @endif
            <p class="small text-muted mb-0 mt-2">HR will contact you if needed.</p>
        </div>
    @elseif ($canRespond)
        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center" id="offerActionButtons">
            <button type="button" id="acceptBtn" class="btn btn-success btn-sm" onclick="handleAcceptOffer()">
                <i class="bi bi-check-lg"></i> Accept Offer
            </button>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectOfferModal">
                <i class="bi bi-x-lg"></i> Reject Offer
            </button>
        </div>

        <div id="signatureBox" class="mt-4" style="display:none;">
            <form method="POST" action="{{ route('employee.documents.storeSignature') }}" enctype="multipart/form-data" onsubmit="return saveOfferSignature()">
                @csrf
                <input type="hidden" name="emp_id" value="{{ $employee->id }}">
                <input type="hidden" name="signature" id="signatureInput">
                @if ($returnUrl)
                    <input type="hidden" name="return_url" value="{{ $returnUrl }}">
                @endif

                <h6 class="fw-bold">Sign to accept offer</h6>
                <p class="small text-muted">Draw your signature or upload an image (PNG/JPG).</p>

                <div class="mb-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showDrawSignature()">Draw</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showUploadSignature()">Upload</button>
                </div>

                <div id="drawArea">
                    <canvas id="signaturePad" style="width:100%;height:180px;border:1px solid #ccc;border-radius:6px;touch-action:none;"></canvas>
                    <button type="button" class="btn btn-warning btn-sm mt-2" onclick="clearSignaturePad()">Clear</button>
                </div>

                <div id="uploadArea" style="display:none;" class="mt-2">
                    <input type="file" name="signature_file" id="signatureFileInput" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onchange="onSignatureFileSelected()">
                    <small class="text-muted">JPG or PNG only</small>
                </div>

                <button type="submit" id="submitSignatureBtn" class="btn btn-success btn-sm mt-3" disabled>
                    Submit Signature & Accept
                </button>
                <button type="button" class="btn btn-link btn-sm mt-3" onclick="cancelAcceptOffer()">Cancel</button>
            </form>
        </div>

        <div class="modal fade" id="rejectOfferModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('employee.documents.rejectOffer') }}" onsubmit="return validateLetterRejectReasonField(this.querySelector('[name=reason]'))">
                        @csrf
                        <input type="hidden" name="emp_id" value="{{ $employee->id }}">
                        @if ($returnUrl)
                            <input type="hidden" name="return_url" value="{{ $returnUrl }}">
                        @endif
                        <div class="modal-header">
                            <h5 class="modal-title">Decline Offer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label fw-bold">Reason for rejection *</label>
                            <textarea name="reason" id="rejectOfferReason" class="form-control" rows="4" required minlength="10" maxlength="1000" placeholder="Example: I have accepted another offer elsewhere">{{ old('reason') }}</textarea>
                            <small class="text-muted">At least 10 characters, at least two proper words. Letters, numbers, and spaces only.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger btn-sm">Submit Rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif ($employee->emp_offer_sent_at)
        <p class="text-muted small mt-3 mb-0 text-center">No action required at this time.</p>
    @else
        <p class="text-muted small mt-3 mb-0 text-center">Offer letter will appear here after HR sends it.</p>
    @endif
</div>

@if ($canRespond || $errors->has('reason'))
@include('shared.letter-reject-reason-validation-script')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    let offerSignaturePad = null;
    let offerSignatureChecker = null;

    function handleAcceptOffer() {
        document.getElementById('signatureBox').style.display = 'block';
        document.getElementById('offerActionButtons').style.display = 'none';
        const canvas = document.getElementById('signaturePad');
        resizeOfferCanvas(canvas);
        offerSignaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255,255,255,0)',
            penColor: 'rgb(0,0,0)'
        });
        startOfferSignatureWatcher();
    }

    function cancelAcceptOffer() {
        document.getElementById('signatureBox').style.display = 'none';
        document.getElementById('offerActionButtons').style.display = 'flex';
        if (offerSignatureChecker) clearInterval(offerSignatureChecker);
        offerSignaturePad = null;
    }

    function startOfferSignatureWatcher() {
        if (offerSignatureChecker) clearInterval(offerSignatureChecker);
        offerSignatureChecker = setInterval(() => {
            const hasDraw = offerSignaturePad && !offerSignaturePad.isEmpty();
            const fileInput = document.getElementById('signatureFileInput');
            const hasFile = fileInput && fileInput.files.length > 0;
            document.getElementById('submitSignatureBtn').disabled = !(hasDraw || hasFile);
        }, 300);
    }

    function resizeOfferCanvas(canvas) {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
    }

    function showDrawSignature() {
        document.getElementById('drawArea').style.display = 'block';
        document.getElementById('uploadArea').style.display = 'none';
        document.getElementById('signatureFileInput').value = '';
        document.getElementById('submitSignatureBtn').disabled = true;
    }

    function showUploadSignature() {
        document.getElementById('drawArea').style.display = 'none';
        document.getElementById('uploadArea').style.display = 'block';
        if (offerSignaturePad) offerSignaturePad.clear();
        document.getElementById('submitSignatureBtn').disabled = true;
    }

    function clearSignaturePad() {
        if (offerSignaturePad) offerSignaturePad.clear();
        document.getElementById('submitSignatureBtn').disabled = true;
    }

    function onSignatureFileSelected() {
        const fileInput = document.getElementById('signatureFileInput');
        document.getElementById('submitSignatureBtn').disabled = !(fileInput && fileInput.files.length > 0);
    }

    function getCroppedCanvas(canvas) {
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const imageData = ctx.getImageData(0, 0, width, height);
        let top = null, left = null, right = null, bottom = null;
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const alpha = imageData.data[(y * width + x) * 4 + 3];
                if (alpha > 0) {
                    if (top === null) top = y;
                    if (left === null || x < left) left = x;
                    if (right === null || x > right) right = x;
                    bottom = y;
                }
            }
        }
        if (top === null) return canvas;
        const croppedWidth = right - left;
        const croppedHeight = bottom - top;
        const newCanvas = document.createElement('canvas');
        newCanvas.width = croppedWidth;
        newCanvas.height = croppedHeight;
        newCanvas.getContext('2d').drawImage(canvas, left, top, croppedWidth, croppedHeight, 0, 0, croppedWidth, croppedHeight);
        return newCanvas;
    }

    function saveOfferSignature() {
        if (document.getElementById('drawArea').style.display !== 'none') {
            if (!offerSignaturePad || offerSignaturePad.isEmpty()) {
                alert('Please draw your signature.');
                return false;
            }
            const canvas = document.getElementById('signaturePad');
            const cropped = getCroppedCanvas(canvas);
            document.getElementById('signatureInput').value = cropped.toDataURL('image/png');
            return true;
        }
        const file = document.getElementById('signatureFileInput');
        if (!file.files.length) {
            alert('Please upload your signature image.');
            return false;
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->has('reason'))
            var modal = document.getElementById('rejectOfferModal');
            if (modal && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modal).show();
            }
        @endif
    });

    window.addEventListener('resize', function () {
        const canvas = document.getElementById('signaturePad');
        if (canvas && offerSignaturePad) {
            resizeOfferCanvas(canvas);
            offerSignaturePad.clear();
            document.getElementById('submitSignatureBtn').disabled = true;
        }
    });
</script>
@endif
