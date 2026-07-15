@php
    use App\Data\SrHrTeam;
    use App\Support\HrAuthorizedSignature;
    use App\Support\SrHrLetterApproval;
    $pickerId = $pickerId ?? 'srHrPicker';
    $showConfirm = $showConfirm ?? true;
    $showSignaturePreview = $showSignaturePreview ?? true;
    $compact = $compact ?? false;
    $selectedEmail = old('sr_hr_email', $selectedEmail ?? '');
    $members = SrHrTeam::memberOptions();
    if ($members === []) {
        foreach (SrHrLetterApproval::configuredEmails() as $email) {
            $members[] = [
                'id' => '',
                'name' => SrHrTeam::displayNameForEmail($email),
                'email' => $email,
                'signature_file' => '',
            ];
        }
    }
@endphp
<div class="{{ $compact ? 'mb-2' : 'border rounded p-3 mb-3 bg-light' }}" id="{{ $pickerId }}">
    <label class="font-weight-bold small d-block mb-2" for="{{ $pickerId }}_select">Select SR-HR approver</label>
    @if (count($members))
        <select name="sr_hr_email" class="form-control" id="{{ $pickerId }}_select">
            <option value="">— Choose SR-HR —</option>
            @foreach ($members as $member)
                @php
                    $sigUrl = HrAuthorizedSignature::previewUrlForApproverEmail($member['email']);
                @endphp
                <option value="{{ $member['email'] }}"
                    data-sig-url="{{ $sigUrl ?? '' }}"
                    data-sig-name="{{ $member['name'] ?: $member['id'] }}"
                    {{ strtolower($selectedEmail) === strtolower($member['email']) ? 'selected' : '' }}>
                    {{ $member['name'] ?: $member['id'] }}
                </option>
            @endforeach
        </select>
        @if ($showSignaturePreview)
            <div class="border rounded p-2 bg-white mt-2" id="{{ $pickerId }}_sigPreviewBox" style="display:none;">
                <p class="small text-muted mb-1">Signature preview on letter:</p>
                <img id="{{ $pickerId }}_sigPreviewImg" src="" alt="Signature preview" style="max-height:56px;border:1px solid #dee2e6;padding:4px;">
                <p class="small mb-0 mt-1" id="{{ $pickerId }}_sigPreviewName"></p>
            </div>
        @endif
        <p class="small text-muted mb-2 mt-2">
            One SR-HR receives the approval request. If they are on leave, you can assign another SR-HR from the SR-HR panel while approval is still pending.
        </p>
        @if ($showConfirm)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sr_hr_confirm" value="1" id="{{ $pickerId }}_confirm">
                <label class="form-check-label small" for="{{ $pickerId }}_confirm">
                    I confirm this offer/appointment will go to the selected SR-HR approver before the candidate receives it.
                </label>
            </div>
        @endif
    @else
        <p class="small text-danger mb-0">
            Add SR-HR members in <code>form_join/sr-hr-team.json</code> (name + email), or set <code>ONBOARDING_SR_HR_EMAILS</code> in <code>.env</code>, then run <code>php artisan config:clear</code>.
        </p>
    @endif
</div>
@if ($showSignaturePreview && count($members))
<script>
(function () {
    var pickerId = @json($pickerId);
    var sel = document.getElementById(pickerId + '_select');
    var box = document.getElementById(pickerId + '_sigPreviewBox');
    var img = document.getElementById(pickerId + '_sigPreviewImg');
    var nameEl = document.getElementById(pickerId + '_sigPreviewName');
    if (!sel || !box || !img) return;

    function updateSigPreview() {
        var opt = sel.options[sel.selectedIndex];
        var url = opt ? opt.getAttribute('data-sig-url') : '';
        var name = opt ? opt.getAttribute('data-sig-name') : '';
        if (url) {
            img.src = url;
            if (nameEl) nameEl.textContent = name || '';
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
            img.src = '';
            if (nameEl) nameEl.textContent = '';
        }
    }

    sel.addEventListener('change', updateSigPreview);
    updateSigPreview();
})();
</script>
@endif
