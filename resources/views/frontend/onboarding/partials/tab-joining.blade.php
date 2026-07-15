@php
    use App\Data\CompanyPolicyDocuments;
    use App\Data\JoiningFormSchema;
    use App\Support\OnboardingJoiningDraft;
    use App\Support\OnboardingJoiningPrefill;
    use App\Support\OnboardingJoiningAccess;
    use App\Support\OnboardingHrDisplay;

    $schema = $joiningSchema ?? JoiningFormSchema::get();
    $guestHousePolicy = collect(CompanyPolicyDocuments::list())->firstWhere('key', 'guesthouse_policy');
    $sections = $schema['sections'] ?? [];
    $canEditJoining = OnboardingJoiningAccess::canCandidateEdit($employee);
    $canViewJoining = OnboardingJoiningAccess::canCandidateView($employee);
    $joiningBlockedMessage = OnboardingJoiningAccess::candidateBlockedMessage($employee);
    if ($canViewJoining && OnboardingJoiningAccess::hasSubmittedData($employee)) {
        $saved = OnboardingJoiningPrefill::mergeSavedJoiningData($employee, $employee->emp_joining_requirements ?? []);
    } else {
        $draftValues = OnboardingJoiningDraft::formValues($employee);
        $saved = $draftValues !== [] ? $draftValues : ($employee->emp_joining_requirements ?? []);
        $saved = OnboardingJoiningPrefill::mergeSavedJoiningData($employee, $saved);
    }
    $medicalDoc = ($document_list ?? collect())->firstWhere('emp_select_document', 'medical_fitness_certificate');
    $mainUrl = $main_url ?? config('app.main_url');
    $assetPrefix = config('app.asset_prefix');
    $form25Section = JoiningFormSchema::section('form_25_leave_nomination');
    $gratuitySection = JoiningFormSchema::section('gratuity_nomination');
    $form25RequiredFields = JoiningFormSchema::requiredFieldNames($form25Section);
    $gratuityNomineeFields = array_column($gratuitySection['nominee_group']['columns'] ?? [], 'key');
    $gratuityNomineeColLabels = [];
    foreach ($gratuitySection['nominee_group']['columns'] ?? [] as $col) {
        $gratuityNomineeColLabels[$col['key']] = $col['label'];
    }
    $medicalHasUploaded = $medicalDoc && $medicalDoc->emp_document_file_path;
    $joiningSectionTitles = collect($sections)->pluck('title', 'key')->all();
    $totalSteps = count($sections);
    $joiningDraftStep = $joiningDraftStep ?? OnboardingJoiningDraft::currentStep($employee);
    $hasJoiningDraft = $hasJoiningDraft ?? OnboardingJoiningDraft::hasDraft($employee);
    $joiningDraftSavedAt = $joiningDraftSavedAt ?? OnboardingJoiningDraft::savedAt($employee);
@endphp

<style>
    .section-title { color: #034ea1; font-weight: 700; }
    .joining-form-step { animation: fadeIn .3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .joining-readonly .accordion-button { font-size: 0.95rem; font-weight: 600; color: #034ea1; }
    .joining-readonly .accordion-button:not(.collapsed) { background: #f0f6fc; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-briefcase me-2"></i>Joining Details</h4>
    @if ($canEditJoining && $totalSteps > 0)
        <span class="badge bg-warning text-dark" id="joiningStepBadge">Step 1 of {{ $totalSteps }}</span>
        @if ($hasJoiningDraft)
            <span class="badge bg-secondary" id="joiningDraftBadge">Draft saved</span>
        @endif
    @endif
</div>

@if ($canEditJoining)
    <div class="alert alert-info py-2 small mb-3">
        Complete each section and click <strong>Next</strong> — your progress is saved automatically.
        @if (!empty($joiningDraftSavedAt))
            <br><span class="text-muted">Last saved: {{ \Carbon\Carbon::parse($joiningDraftSavedAt)->format('d M Y, h:i A') }}</span>
        @endif
    </div>

    @if ($totalSteps > 1)
        <div class="progress mb-4" style="height:10px;">
            <div class="progress-bar bg-primary" id="joiningProgressBar" style="width:{{ $totalSteps ? (($joiningDraftStep + 1) / $totalSteps * 100) : 0 }}%"></div>
        </div>
    @endif

    <form method="POST" action="{{ route('onboarding.save', $employee->emp_url) }}" id="joiningForm" class="info-box" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="action" value="joining">
        <input type="hidden" name="joining_information" id="joiningInformationInput">

        @foreach ($sections as $stepIndex => $section)
            @php
                $sectionKey = $section['key'];
                $sectionValues = $saved[$sectionKey] ?? [];
            @endphp
            <div class="joining-form-step joining-section-block mb-4"
                 data-step="{{ $stepIndex }}"
                 data-section="{{ $sectionKey }}"
                 data-section-type="{{ $section['type'] ?? 'fields' }}"
                 @if ($stepIndex !== $joiningDraftStep) style="display:none" @endif>
                <h4 class="section-title mb-4">{{ $section['title'] }}</h4>
                @if (!empty($section['intro']))
                    <p class="small text-danger mb-3">{{ $section['intro'] }}</p>
                @endif

                @if (($section['type'] ?? '') === 'medical_fitness')
                    @php
                        $medSaved = is_array($sectionValues) ? $sectionValues : [];
                    @endphp
                    @if ($medicalHasUploaded)
                        <div class="alert alert-success py-2 small mb-3">
                            Medical fitness certificate already uploaded.
                            <a href="{{ $mainUrl }}{{ $assetPrefix }}{{ $medicalDoc->emp_document_file_path }}" target="_blank" class="alert-link">View file</a>
                            <span class="text-muted">— HR will verify under Documents.</span>
                        </div>
                    @endif
                    <div class="row g-2">
                        @foreach ($section['fields'] ?? [] as $field)
                            @include('frontend.onboarding.partials.profile-field', [
                                'field' => $field,
                                'fieldKey' => $field['name'],
                                'values' => $medSaved,
                            ])
                        @endforeach
                    </div>
                    <div class="mb-3 medical-fitness-upload-wrap d-none" id="medicalFitnessUploadWrap">
                        <label class="form-label">Upload Medical Fitness Certificate (PDF) @include('frontend.onboarding.partials.required-asterisk')</label>
                        <input type="file" name="medical_fitness_file" id="medicalFitnessFile" class="form-control"
                            accept="{{ $section['accept'] ?? '.pdf,application/pdf' }}">
                    </div>
                    <input type="hidden" id="medicalFitnessAlreadyUploaded" value="{{ $medicalHasUploaded ? '1' : '0' }}">

                @elseif (($section['type'] ?? '') === 'file_upload')
                    @php $docKey = $section['document_key'] ?? 'medical_fitness_certificate'; @endphp
                    @if ($medicalDoc && $medicalDoc->emp_document_file_path)
                        <div class="alert alert-success py-2 small mb-3">
                            Medical fitness certificate already uploaded.
                            <a href="{{ $mainUrl }}{{ $assetPrefix }}{{ $medicalDoc->emp_document_file_path }}" target="_blank" class="alert-link">View file</a>
                            <span class="text-muted">— HR will verify under Documents.</span>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Upload Medical Fitness Certificate (PDF)</label>
                        <input type="file" name="medical_fitness_file" id="medicalFitnessFile" class="form-control"
                            accept="{{ $section['accept'] ?? '.pdf,application/pdf' }}">
                        <small class="text-muted">Optional now — you may upload later from Documents if needed.</small>
                    </div>

                @elseif (($section['type'] ?? '') === 'gratuity_nomination')
                    @include('frontend.onboarding.partials.tab-joining-gratuity', [
                        'section' => $section,
                        'sectionValues' => $sectionValues,
                        'savedJoiningDetails' => $saved['joining_details'] ?? [],
                        'employee' => $employee,
                    ])

                @elseif (($section['type'] ?? '') === 'form_25_leave_nomination')
                    @include('frontend.onboarding.partials.tab-joining-form25', [
                        'section' => $section,
                        'sectionValues' => $sectionValues,
                        'savedJoiningDetails' => $saved['joining_details'] ?? [],
                        'employee' => $employee,
                    ])

                @elseif (($section['type'] ?? '') === 'repeatable_table')
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-success btn-sm btn-add-asset" data-table="{{ $sectionKey }}">+ Add</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    @foreach ($section['columns'] as $col)
                                        <th>{{ $col['label'] }}@if (!empty($col['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</th>
                                    @endforeach
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="table-{{ $sectionKey }}">
                                @php $rows = is_array($sectionValues) && isset($sectionValues[0]) ? $sectionValues : [[]]; @endphp
                                @foreach ($rows as $ri => $row)
                                    <tr>
                                        <td class="row-num">{{ $ri + 1 }}</td>
                                        @foreach ($section['columns'] as $col)
                                            @php
                                                $colKey = $col['key'];
                                                $legacyKey = $colKey === 'particulars' ? ($row['particulars'] ?? $row['asset_type'] ?? '') : ($row[$colKey] ?? '');
                                                $cellVal = $row[$colKey] ?? ($colKey === 'remarks' ? ($row['notes'] ?? '') : $legacyKey);
                                            @endphp
                                            <td>
                                                @if (($col['type'] ?? '') === 'select')
                                                    <select class="form-control form-control-sm asset-field{{ !empty($col['required']) ? ' required-field' : '' }}"
                                                        data-section="{{ $sectionKey }}"
                                                        data-key="{{ $colKey }}"
                                                        {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                                                        <option value="">Select</option>
                                                        @foreach ($col['options'] ?? [] as $opt)
                                                            <option value="{{ $opt }}" {{ (string) $cellVal === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                <input type="{{ $col['type'] ?? 'text' }}"
                                                    class="form-control form-control-sm asset-field{{ !empty($col['required']) ? ' required-field' : '' }}"
                                                    data-section="{{ $sectionKey }}"
                                                    data-key="{{ $colKey }}"
                                                    value="{{ $cellVal }}"
                                                    {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td><button type="button" class="btn btn-danger btn-sm btn-remove-asset">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @else
                    <div class="row g-2">
                        @foreach ($section['fields'] ?? [] as $field)
                            @php
                                $fname = $field['name'];
                                $fvals = $sectionValues;
                                if ($sectionKey === 'joining_details') {
                                    if ($fname === 'confirmed_join_date' && empty($fvals['confirmed_join_date'])) {
                                        $fvals['confirmed_join_date'] = optional($employee->emp_joining_date)->format('Y-m-d');
                                    }
                                    if ($fname === 'emp_id') {
                                        if (empty($fvals['emp_id']) && !empty($employee->emp_employee_id)) {
                                            $fvals['emp_id'] = $employee->emp_employee_id;
                                        }
                                        if (!empty($employee->emp_employee_id)) {
                                            $field = array_merge($field, ['readonly' => true]);
                                        }
                                    }
                                }
                            @endphp
                            @include('frontend.onboarding.partials.profile-field', [
                                'field' => $field,
                                'fieldKey' => $fname,
                                'values' => $fvals,
                            ])
                            @if ($fname === 'guest_house_required' && $guestHousePolicy)
                                <div id="guestHousePolicyPanel" class="col-12 mb-3 guest-house-policy-wrap d-none">
                                    <div class="alert alert-light border py-2 mb-0">
                                        <strong class="small d-block mb-1">{{ $guestHousePolicy['title'] ?? 'Guest House Policy' }}</strong>
                                        @if (!empty($guestHousePolicy['description']))
                                            <p class="small text-muted mb-2">{{ $guestHousePolicy['description'] }}</p>
                                        @endif
                                        @include('shared.policy-pdf-viewer', ['employee' => $employee, 'doc' => $guestHousePolicy])
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="alert alert-info py-2 small mb-3">
            Please read company policies on the
            <a href="{{ route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'policy']) }}">Policy tab</a>
            before submitting joining details.
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4" id="joiningNavBtns">
            <button type="button" class="btn btn-secondary" id="joiningBtnBack" style="display:none;">Back</button>
            <div class="ms-auto d-flex gap-2" id="joiningNavRight">
                <button type="button" class="btn btn-brand" id="joiningBtnNext">Next</button>
                <button type="submit" class="btn btn-success d-none" id="joiningBtnSubmit">Submit Joining Details</button>
            </div>
        </div>
    </form>

    <script>
    (function () {
        const schema = @json($schema);
        const form = document.getElementById('joiningForm');
        const hidden = document.getElementById('joiningInformationInput');
        const steps = Array.from(document.querySelectorAll('.joining-form-step'));
        const total = steps.length || 1;
        let currentStep = Math.min(Math.max(0, {{ (int) $joiningDraftStep }}), total - 1);
        const progressBar = document.getElementById('joiningProgressBar');
        const stepBadge = document.getElementById('joiningStepBadge');
        const draftBadge = document.getElementById('joiningDraftBadge');
        const btnBack = document.getElementById('joiningBtnBack');
        const btnNext = document.getElementById('joiningBtnNext');
        const btnSubmit = document.getElementById('joiningBtnSubmit');
        const saveUrl = @json(route('onboarding.save', $employee->emp_url));
        const csrf = form.querySelector('input[name="_token"]')?.value || '';

        function collectJoiningPayload() {
            const payload = {};
            (schema.sections || []).forEach(section => {
                const key = section.key;
                if (section.type === 'repeatable_table') {
                    payload[key] = [];
                    document.querySelectorAll('#table-' + key + ' tr').forEach(tr => {
                        const row = {};
                        tr.querySelectorAll('.asset-field').forEach(inp => {
                            row[inp.dataset.key] = inp.value;
                        });
                        if (Object.values(row).some(v => String(v).trim() !== '')) payload[key].push(row);
                    });
                } else if (section.type === 'medical_fitness') {
                    payload[key] = {};
                    (section.fields || []).forEach(f => {
                        const inp = form.querySelector('[name="' + f.name + '"]');
                        if (!inp) return;
                        payload[key][f.name] = inp.value;
                    });
                    if (document.getElementById('medicalFitnessAlreadyUploaded')?.value === '1') {
                        payload[key].uploaded = '1';
                    }
                } else if (section.type === 'file_upload') {
                    return;
                } else if (section.type === 'gratuity_nomination') {
                    syncGratuityEmployeeFields();
                    payload[key] = {};
                    form.querySelectorAll('.gratuity-field[data-gratuity-section="' + key + '"]').forEach(inp => {
                        if (!inp.name) return;
                        if (inp.type === 'checkbox') {
                            payload[key][inp.name] = inp.checked ? '1' : '';
                        } else {
                            payload[key][inp.name] = inp.value;
                        }
                    });
                    payload[key].nominees = [];
                    document.querySelectorAll('#table-gratuity-nominees tr').forEach(tr => {
                        const row = {};
                        tr.querySelectorAll('.gratuity-nominee-field').forEach(inp => {
                            row[inp.dataset.key] = inp.value;
                        });
                        if (Object.values(row).some(v => String(v).trim() !== '')) {
                            payload[key].nominees.push(row);
                        }
                    });
                    if (payload[key].gratuity_declaration_confirm === '1') {
                        payload[key].nomination_submitted = '1';
                    }
                } else if (section.type === 'form_25_leave_nomination') {
                    payload[key] = {};
                    form.querySelectorAll('.form25-field[data-form25-section="' + key + '"]').forEach(inp => {
                        if (!inp.name) return;
                        payload[key][inp.name] = inp.value;
                    });
                } else {
                    payload[key] = {};
                    form.querySelectorAll('[name]').forEach(inp => {
                        if (!inp.name || inp.name === 'action' || inp.name === 'joining_information' || inp.name === '_token' || inp.name === 'medical_fitness_file') return;
                        const inSection = (section.fields || []).some(f => f.name === inp.name);
                        if (inSection) {
                            payload[key][inp.name] = inp.type === 'checkbox' ? (inp.checked ? '1' : '') : inp.value;
                        }
                    });
                }
            });
            return payload;
        }

        function getVisibleJoiningStepEl() {
            return steps[currentStep];
        }

        const joiningSectionTitles = @json($joiningSectionTitles);

        function joiningSectionTitleByKey(key) {
            return joiningSectionTitles[key] || key;
        }

        const gratuityNomineeColLabels = @json($gratuityNomineeColLabels);

        function joiningSectionTitle(stepEl) {
            return stepEl?.querySelector('.section-title')?.textContent?.trim() || 'This section';
        }

        function joiningFieldLabel(field) {
            const wrap = field.closest('[class*="col-"]') || field.closest('td');
            const lab = wrap?.querySelector('label');
            if (lab) return lab.textContent.replace(/\s*\*\s*$/, '').trim();
            const key = field.dataset?.key;
            if (key && gratuityNomineeColLabels[key]) return gratuityNomineeColLabels[key];
            return field.name || key || 'Field';
        }

        function joiningStepErrors(stepEl) {
            const errors = [];
            if (!stepEl) return errors;

            const sectionTitle = joiningSectionTitle(stepEl);
            const sectionKey = stepEl.dataset.section;
            stepEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            const shoeReq = form.querySelector('.shoe-required-toggle');
            const shoeRequired = shoeReq && shoeReq.value === 'yes';

            if (sectionKey === 'joining_details' && shoeRequired) {
                const shoeSize = stepEl.querySelector('.shoe-size-field');
                if (!shoeSize || !String(shoeSize.value || '').trim()) {
                    if (shoeSize) shoeSize.classList.add('is-invalid');
                    errors.push(sectionTitle + ' -> Shoe Size is required');
                }
            }

            stepEl.querySelectorAll('.required-field, [data-required="1"]').forEach(field => {
                if (field.id === 'gratuityDeclarationConfirm') return;
                if (field.classList.contains('shoe-size-field') && !shoeRequired) return;
                if (field.classList.contains('medical-fitness-reason-field')) return;

                const isOptionalTable = stepEl.dataset.section === 'mediclaim_dependents' || stepEl.dataset.section === 'assets_requested';
                if (isOptionalTable) {
                    const tr = field.closest('tr');
                    if (tr) {
                        const rowEmpty = !Array.from(tr.querySelectorAll('.asset-field')).some(inp => String(inp.value || '').trim() !== '');
                        if (rowEmpty) return;
                    }
                }

                const empty = field.type === 'checkbox' ? !field.checked : !String(field.value || '').trim();
                if (empty) {
                    field.classList.add('is-invalid');
                    errors.push(sectionTitle + ' -> ' + joiningFieldLabel(field) + ' is required');
                }
            });

            if (sectionKey === 'gratuity_nomination') {
                const nomineeFields = @json($gratuityNomineeFields);
                const rows = [];
                stepEl.querySelectorAll('#table-gratuity-nominees tr').forEach(tr => {
                    const row = {};
                    tr.querySelectorAll('.gratuity-nominee-field').forEach(inp => {
                        row[inp.dataset.key] = inp.value;
                    });
                    if (Object.values(row).some(v => String(v).trim() !== '')) {
                        rows.push({ row, tr });
                    }
                });
                if (rows.length === 0) {
                    const firstRow = stepEl.querySelector('#table-gratuity-nominees tr');
                    if (firstRow) {
                        firstRow.querySelectorAll('.gratuity-nominee-field').forEach(inp => inp.classList.add('is-invalid'));
                    }
                    errors.push(sectionTitle + ' -> Add at least one nominee');
                } else {
                    let totalShare = 0;
                    rows.forEach(({ row, tr }, idx) => {
                        nomineeFields.forEach(f => {
                            if (!String(row[f] || '').trim()) {
                                tr.querySelectorAll('.gratuity-nominee-field[data-key="' + f + '"]').forEach(inp => inp.classList.add('is-invalid'));
                                errors.push(sectionTitle + ' -> ' + (gratuityNomineeColLabels[f] || f) + ' is required (nominee #' + (idx + 1) + ')');
                            }
                        });
                        const share = parseFloat(row.gratuity_share_percentage);
                        if (!isNaN(share)) totalShare += share;
                    });
                    if (rows.length > 0 && Math.abs(totalShare - 100) > 0.01) {
                        errors.push(sectionTitle + ' -> Total gratuity share must equal 100% (currently ' + totalShare.toFixed(2) + '%)');
                        stepEl.querySelectorAll('.gratuity-nominee-field[data-key="gratuity_share_percentage"]').forEach(inp => inp.classList.add('is-invalid'));
                    }
                }
            }

            if (sectionKey === 'mediclaim_dependents') {
                const parentRel = /\b(father|mother|parent|parents|step\s*father|step\s*mother|in[-\s]?law)\b/i;
                stepEl.querySelectorAll('#table-' + sectionKey + ' tr').forEach((tr, i) => {
                    const row = {};
                    tr.querySelectorAll('.asset-field').forEach(inp => { row[inp.dataset.key] = inp.value; });
                    const name = String(row.name || '').trim();
                    const dob = String(row.dob || '').trim();
                    const rel = String(row.relationship || '').trim();
                    if (!name && !dob && !rel) return;
                    if (!name || !dob || !rel) {
                        tr.querySelectorAll('.asset-field').forEach(inp => {
                            if (!String(inp.value || '').trim()) inp.classList.add('is-invalid');
                        });
                        errors.push(sectionTitle + ' -> Complete all fields for mediclaim member #' + (i + 1));
                        return;
                    }
                    if (parentRel.test(rel)) {
                        tr.querySelectorAll('.asset-field[data-key="relationship"]').forEach(inp => inp.classList.add('is-invalid'));
                        errors.push(sectionTitle + ' -> Parents cannot be added (member #' + (i + 1) + ')');
                    }
                });
            }

            if (sectionKey === 'assets_requested') {
                stepEl.querySelectorAll('#table-assets_requested tr').forEach((tr, i) => {
                    const row = {};
                    tr.querySelectorAll('.asset-field').forEach(inp => { row[inp.dataset.key] = inp.value; });
                    const hasData = Object.values(row).some(v => String(v).trim() !== '');
                    const particulars = String(row.particulars || '').trim();
                    if (hasData && !particulars) {
                        tr.querySelectorAll('.asset-field[data-key="particulars"]').forEach(inp => inp.classList.add('is-invalid'));
                        errors.push(sectionTitle + ' -> Particulars is required (asset row #' + (i + 1) + ')');
                    }
                });
            }

            if (sectionKey === 'medical_fitness') {
                const avail = stepEl.querySelector('.medical-fitness-available-toggle');
                const availVal = avail ? String(avail.value || '').trim() : '';
                if (!availVal) {
                    if (avail) avail.classList.add('is-invalid');
                    errors.push(sectionTitle + ' -> Medical fitness certificate available? is required');
                } else if (availVal === 'yes') {
                    const already = document.getElementById('medicalFitnessAlreadyUploaded')?.value === '1';
                    const fileInp = document.getElementById('medicalFitnessFile');
                    const hasFile = fileInp && fileInp.files && fileInp.files.length > 0;
                    if (!already && !hasFile) {
                        if (fileInp) fileInp.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> PDF file is required');
                    }
                } else if (availVal === 'no') {
                    const reason = stepEl.querySelector('.medical-fitness-reason-field');
                    const reasonVal = reason ? String(reason.value || '').trim() : '';
                    if (reasonVal.length < 10) {
                        if (reason) reason.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> Reason must be at least 10 characters');
                    }
                }
            }

            return errors;
        }

        function validateJoiningStep() {
            return joiningStepErrors(getVisibleJoiningStepEl());
        }

        function showJoiningStep(n) {
            currentStep = Math.min(Math.max(0, n), total - 1);
            steps.forEach((el, i) => { el.style.display = i === currentStep ? '' : 'none'; });
            if (progressBar) progressBar.style.width = ((currentStep + 1) / total * 100) + '%';
            if (stepBadge) stepBadge.textContent = 'Step ' + (currentStep + 1) + ' of ' + total;
            if (btnBack) btnBack.style.display = currentStep === 0 ? 'none' : 'inline-block';
            if (btnNext) btnNext.classList.toggle('d-none', currentStep >= total - 1);
            if (btnSubmit) btnSubmit.classList.toggle('d-none', currentStep < total - 1);
        }

        function saveJoiningDraft(step) {
            const payload = collectJoiningPayload();
            const body = new FormData();
            body.append('_token', csrf);
            body.append('action', 'joining_draft');
            body.append('joining_information', JSON.stringify(payload));
            body.append('draft_step', String(step));
            return fetch(saveUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body,
            }).then(r => r.json().then(data => {
                if (!r.ok) throw new Error(data.message || 'Save failed');
                if (data.ok && draftBadge) {
                    draftBadge.classList.remove('d-none');
                    draftBadge.textContent = 'Draft saved';
                }
                return data;
            }));
        }

        document.querySelectorAll('.btn-add-asset').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.table;
                const tbody = document.getElementById('table-' + key);
                const section = (schema.sections || []).find(s => s.key === key);
                if (!tbody || !section) return;
                const ri = tbody.querySelectorAll('tr').length;
                let html = '<tr><td class="row-num">' + (ri + 1) + '</td>';
                section.columns.forEach(col => {
                    const reqCls = col.required ? ' required-field' : '';
                    const reqAttr = col.required ? ' data-required=1' : '';
                    if (col.type === 'select') {
                        const opts = (col.options || []).map(o => '<option value="' + o + '">' + o + '</option>').join('');
                        html += '<td><select class="form-control form-control-sm asset-field' + reqCls + '" data-section="' + key + '" data-key="' + col.key + '"' + reqAttr + '><option value="">Select</option>' + opts + '</select></td>';
                    } else {
                        html += '<td><input type="' + (col.type || 'text') + '" class="form-control form-control-sm asset-field' + reqCls + '" data-section="' + key + '" data-key="' + col.key + '"' + reqAttr + '></td>';
                    }
                });
                html += '<td><button type="button" class="btn btn-danger btn-sm btn-remove-asset">Remove</button></td></tr>';
                tbody.insertAdjacentHTML('beforeend', html);
                bindRemove();
            });
        });

        function bindRemove() {
            document.querySelectorAll('.btn-remove-asset').forEach(b => {
                b.onclick = function () {
                    const tr = this.closest('tr');
                    const tbody = tr.parentElement;
                    if (tbody.querySelectorAll('tr').length > 1) tr.remove();
                    tbody.querySelectorAll('tr').forEach((r, i) => r.querySelector('.row-num').textContent = i + 1);
                };
            });
        }
        bindRemove();

        function updateShoeVisibility() {
            const sel = form.querySelector('.shoe-required-toggle');
            const show = sel && sel.value === 'yes';
            form.querySelectorAll('.shoe-size-wrap').forEach(el => {
                el.classList.toggle('d-none', !show);
            });
            const sizeInp = form.querySelector('.shoe-size-field');
            if (sizeInp) {
                sizeInp.required = show;
                if (!show) sizeInp.value = '';
            }
        }

        function updateGuestHousePolicyVisibility() {
            const sel = form.querySelector('.guest-house-required-toggle');
            const show = sel && sel.value === 'yes';
            form.querySelectorAll('.guest-house-policy-wrap').forEach(el => {
                el.classList.toggle('d-none', !show);
            });
        }

        const shoeToggle = form.querySelector('.shoe-required-toggle');
        const guestHouseToggle = form.querySelector('.guest-house-required-toggle');
        if (shoeToggle) {
            shoeToggle.addEventListener('change', updateShoeVisibility);
            updateShoeVisibility();
        }
        if (guestHouseToggle) {
            guestHouseToggle.addEventListener('change', updateGuestHousePolicyVisibility);
            updateGuestHousePolicyVisibility();
        }

        function updateMedicalFitnessVisibility() {
            const sel = form.querySelector('.medical-fitness-available-toggle');
            const val = sel ? sel.value : '';
            const uploadWrap = document.getElementById('medicalFitnessUploadWrap');
            const reasonWrap = form.querySelector('.medical-fitness-reason-wrap');
            const reasonField = form.querySelector('.medical-fitness-reason-field');
            if (uploadWrap) uploadWrap.classList.toggle('d-none', val !== 'yes');
            if (reasonWrap) reasonWrap.classList.toggle('d-none', val !== 'no');
            if (reasonField) {
                reasonField.classList.toggle('required-field', val === 'no');
                if (val !== 'no') {
                    reasonField.classList.remove('is-invalid');
                }
            }
            const fileInp = document.getElementById('medicalFitnessFile');
            if (fileInp && val !== 'yes') {
                fileInp.value = '';
                fileInp.classList.remove('is-invalid');
            }
        }

        const medFitnessToggle = form.querySelector('.medical-fitness-available-toggle');
        if (medFitnessToggle) {
            medFitnessToggle.addEventListener('change', updateMedicalFitnessVisibility);
            updateMedicalFitnessVisibility();
        }

        function syncGratuityEmployeeFields() {
            const joinDate = form.querySelector('[name="confirmed_join_date"]');
            const empId = form.querySelector('[name="emp_id"]');
            const gJoin = form.querySelector('[name="date_of_joining"]');
            const gEmp = form.querySelector('[name="employee_id"]');
            if (joinDate && gJoin) gJoin.value = joinDate.value;
            if (empId && gEmp) gEmp.value = empId.value;
        }

        form.querySelector('[name="confirmed_join_date"]')?.addEventListener('change', syncGratuityEmployeeFields);
        form.querySelector('[name="emp_id"]')?.addEventListener('input', syncGratuityEmployeeFields);
        syncGratuityEmployeeFields();

        if (btnBack) {
            btnBack.addEventListener('click', function () {
                if (currentStep > 0) showJoiningStep(currentStep - 1);
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                const stepErrors = validateJoiningStep();
                if (stepErrors.length) {
                    alert([...new Set(stepErrors)].join('\n'));
                    return;
                }
                if (currentStep >= total - 1) return;
                const next = currentStep + 1;
                btnNext.disabled = true;
                saveJoiningDraft(next).then(function (res) {
                    btnNext.disabled = false;
                    if (res && res.ok) showJoiningStep(next);
                }).catch(function (err) {
                    btnNext.disabled = false;
                    alert(err.message || 'Could not save draft. Check your connection and try again.');
                });
            });
        }

        form.addEventListener('submit', function (e) {
            let allErrors = [];
            steps.forEach(stepEl => {
                allErrors = allErrors.concat(joiningStepErrors(stepEl));
            });
            const gratuityStep = Array.from(steps).find(el => el.dataset.section === 'gratuity_nomination');
            if (gratuityStep) {
                const confirm = gratuityStep.querySelector('#gratuityDeclarationConfirm');
                if (confirm && !confirm.checked) {
                    confirm.classList.add('is-invalid');
                    allErrors.push(joiningSectionTitle(gratuityStep) + ' -> Declaration confirmation is required');
                }
            }
            if (allErrors.length) {
                e.preventDefault();
                alert([...new Set(allErrors)].join('\n'));
                return;
            }

            const payload = collectJoiningPayload();
            const bankTitle = joiningSectionTitleByKey('bank_pf');

            const bankPf = payload.bank_pf || {};
            const ifsc = String(bankPf.ifsc_code || '').trim().toUpperCase();
            if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifsc)) {
                e.preventDefault();
                alert(bankTitle + ' -> IFSC code must be 11 characters (e.g. SBIN0001234)');
                return;
            }
            bankPf.ifsc_code = ifsc;
            payload.bank_pf = bankPf;

            hidden.value = JSON.stringify(payload);
        });

        showJoiningStep(currentStep);
    })();
    </script>
@elseif ($canViewJoining && !empty($saved))
    <div class="joining-readonly">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-success">Submitted</span>
        </div>
        <div class="accordion" id="joiningReadonlyAccordion">
            @foreach ($sections as $stepIndex => $section)
                @php
                    $data = $saved[$section['key']] ?? [];
                    $collapseId = 'joinRo' . preg_replace('/[^a-z0-9]/i', '', $section['key']);
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $stepIndex > 0 ? 'collapsed' : '' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                            aria-expanded="{{ $stepIndex === 0 ? 'true' : 'false' }}">
                            {{ $section['title'] }}
                        </button>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $stepIndex === 0 ? 'show' : '' }}"
                        data-bs-parent="#joiningReadonlyAccordion">
                        <div class="accordion-body pt-2">
                                @if (($section['type'] ?? '') === 'medical_fitness')
                                    @php
                                        $medRows = OnboardingHrDisplay::joiningMedicalFitnessRows(is_array($data) ? $data : [], (bool) $medicalHasUploaded);
                                    @endphp
                                    @include('pages.new-join-employee.partials.hr-data-table', ['rows' => $medRows])
                                    @if ($medicalHasUploaded && ($data['medical_fitness_available'] ?? '') === 'yes')
                                        <p class="mb-0 mt-2 small">
                                            <a href="{{ $mainUrl }}{{ $assetPrefix }}{{ $medicalDoc->emp_document_file_path }}" target="_blank">View uploaded certificate</a>
                                        </p>
                                    @endif
                                @elseif (($section['type'] ?? '') === 'file_upload')
                                    @if ($medicalDoc)
                                        <p class="mb-0 small">
                                            <strong>Medical certificate:</strong>
                                            <a href="{{ $mainUrl }}{{ $assetPrefix }}{{ $medicalDoc->emp_document_file_path }}" target="_blank">View uploaded file</a>
                                        </p>
                                    @else
                                        <p class="text-muted small mb-0">No medical fitness file uploaded.</p>
                                    @endif
                                @elseif (($section['type'] ?? '') === 'gratuity_nomination')
                                    @include('frontend.onboarding.partials.tab-joining-gratuity', [
                                        'section' => $section,
                                        'sectionValues' => $data,
                                        'savedJoiningDetails' => $saved['joining_details'] ?? [],
                                        'employee' => $employee,
                                        'readOnlyMode' => true,
                                    ])
                                @elseif (($section['type'] ?? '') === 'form_25_leave_nomination')
                                    @include('frontend.onboarding.partials.tab-joining-form25', [
                                        'section' => $section,
                                        'sectionValues' => $data,
                                        'savedJoiningDetails' => $saved['joining_details'] ?? [],
                                        'employee' => $employee,
                                        'readOnlyMode' => true,
                                    ])
                                @elseif (($section['type'] ?? '') === 'repeatable_table' && is_array($data) && count($data))
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead><tr>@foreach ($section['columns'] as $c)<th>{{ $c['label'] }}</th>@endforeach</tr></thead>
                                        <tbody>
                                            @foreach ($data as $row)
                                                <tr>
                                                    @foreach ($section['columns'] as $c)
                                                        <td>{{ $row[$c['key']] ?? ($c['key'] === 'particulars' ? ($row['asset_type'] ?? '-') : ($c['key'] === 'remarks' ? ($row['notes'] ?? '-') : '-')) }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @elseif (is_array($data) && in_array($section['type'] ?? 'fields', ['fields', ''], true) && !empty($section['fields']))
                                    @include('pages.new-join-employee.partials.hr-data-table', [
                                        'rows' => OnboardingHrDisplay::joiningFieldRows($section, $data),
                                    ])
                                    @if ($section['key'] === 'joining_details' && ($data['guest_house_required'] ?? '') === 'yes' && $guestHousePolicy)
                                        <p class="mb-0 mt-2">
                                        @include('shared.policy-pdf-viewer', ['employee' => $employee, 'doc' => $guestHousePolicy])
                                        </p>
                                    @endif
                                @elseif (is_array($data) && ($section['type'] ?? '') !== 'repeatable_table')
                                @else
                                    <p class="text-muted small mb-0">No details provided.</p>
                                @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-warning mb-0">
        <i class="bi bi-lock me-1"></i>
        {{ $joiningBlockedMessage ?? 'Joining forms will be available when HR starts the join process.' }}
    </div>
    @php $scheduledJoin = $employee->emp_joining_date ?? $employee->scheduledJoinDate(); @endphp
    @if ($scheduledJoin && !$canEditJoining)
        <p class="small text-muted mt-2 mb-0">
            <strong>Scheduled join date:</strong> {{ \Carbon\Carbon::parse($scheduledJoin)->format('d M Y') }}
        </p>
    @endif
@endif
