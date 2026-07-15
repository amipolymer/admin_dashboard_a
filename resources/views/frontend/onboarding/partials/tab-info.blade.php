@php
    use App\Data\ProfileFormSchema;
    $schema = $profileSchema ?? ProfileFormSchema::get();
    $sections = $schema['sections'] ?? [];
    $saved = ($employee->emp_profile_data ?? [])['information'] ?? [];
    $basicSaved = $saved['basic_information'] ?? [];
    if (empty($basicSaved['application_source']) && !empty($employee->emp_application_source)) {
        $basicSaved['application_source'] = $employee->emp_application_source;
    }
    if (!empty($basicSaved)) {
        $saved['basic_information'] = $basicSaved;
    }
    $totalSteps = count($sections);
    $isFresher = $isFresher ?? $employee->isFresher();
@endphp

<style>
    .section-title { color: #034ea1; font-weight: 700; }
    .phone-field-group {
        display: flex;
        align-items: stretch;
        width: 100%;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        background: #fff;
        overflow: hidden;
    }
    .phone-field-group:focus-within {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .phone-field-group.is-invalid {
        border-color: #dc3545;
    }
    .phone-field-group.is-invalid:focus-within {
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
    .phone-field-group .phone-code-input {
        width: 5rem;
        min-width: 5rem;
        max-width: 5rem;
        flex: 0 0 5rem;
        border: 0;
        border-right: 1px solid #dee2e6;
        border-radius: 0;
        padding: 0.375rem 0.35rem;
        text-align: center;
        font-weight: 600;
        background: #f8f9fa;
        color: #212529;
    }
    .phone-field-group .phone-number-input {
        flex: 1 1 auto;
        min-width: 0;
        width: 1%;
        border: 0;
        border-radius: 0;
        padding: 0.375rem 0.75rem;
    }
    .phone-field-group .phone-code-input:focus,
    .phone-field-group .phone-number-input:focus {
        outline: 0;
        box-shadow: none;
    }
    .phone-field-group .phone-code-input.is-invalid,
    .phone-field-group .phone-number-input.is-invalid {
        background-image: none;
        padding-right: inherit;
        box-shadow: none;
    }
    .form-step { animation: fadeIn .3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .address-box { background: #f8f9fa; border-radius: 10px; padding: 20px; border: 1px solid #dee2e6; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Information Details</h4>
    @if ($profileComplete)
        <span class="badge bg-success">Completed</span>
    @else
        @if ($isFresher)
            <span class="badge bg-info text-dark me-1">Fresher</span>
        @endif
        <span class="badge bg-warning text-dark" id="stepBadge">Step 1 of {{ $totalSteps }}</span>
        @if (($hasProfileDraft ?? false) && !($profileComplete ?? false))
            <span class="badge bg-secondary" id="draftSavedBadge">Draft saved</span>
        @endif
    @endif
</div>

@if (!$canEditProfile && $profileComplete)
    <div class="alert alert-secondary py-2 small mb-3">
        Your information has been submitted and is locked. Contact HR if you need to make changes.
    </div>
    @include('frontend.onboarding.partials.profile-readonly', ['employee' => $employee])
@else
    @if ($profileComplete && ($canEditProfile ?? false))
        <div class="alert alert-warning py-2 small mb-3">
            <strong>HR has allowed you to update your information.</strong>
            @if (!empty($profileReeditReason))
                <br><span class="text-muted">Reason:</span> {{ $profileReeditReason }}
            @endif
            <br>Review all steps, then submit again.
        </div>
    @endif
    <div class="alert alert-info py-2 small mb-3">
        Your progress is saved automatically when you click <strong>Next</strong>. Sign in again anytime to continue from your last step.
        @if (!empty($profileDraftSavedAt))
            <br><span class="text-muted">Last saved: {{ \Carbon\Carbon::parse($profileDraftSavedAt)->format('d M Y, h:i A') }}</span>
        @endif
    </div>

    <div class="progress mb-4" style="height:10px;">
        <div class="progress-bar bg-primary" id="progressBar" style="width:{{ $totalSteps ? (100 / $totalSteps) : 0 }}%"></div>
    </div>

    <form id="profileForm" novalidate>
        @foreach ($sections as $stepIndex => $section)
            @php
                $sectionKey = $section['key'];
                $sectionValues = $saved[$sectionKey] ?? [];
            @endphp
            <div class="form-step {{ $stepIndex > 0 ? 'd-none' : '' }}" data-step="{{ $stepIndex }}" data-section="{{ $sectionKey }}" data-section-type="{{ $section['type'] ?? 'fields' }}">

                <h4 class="section-title mb-4">{{ $section['title'] }}</h4>
                @if (!empty($section['intro']))
                    <p class="small text-danger mb-3">{{ $section['intro'] }}</p>
                @endif
                @if (!empty($section['information']))
                    <p class="small text-black mb-3">{{ $section['information'] }}</p>
                @endif

                @if (in_array($section['type'] ?? '', ['last_employment', 'employment_with_footer'], true))
                    @include('frontend.onboarding.partials.employment-last-company', [
                        'employmentSection' => $section,
                        'savedEmployment' => $sectionValues,
                        'isFresher' => $isFresher,
                    ])

                @elseif (($section['type'] ?? '') === 'family_section')
                    @include('frontend.onboarding.partials.family-details', [
                        'familySection' => $section,
                        'savedFamily' => $sectionValues,
                    ])

                @elseif (($section['type'] ?? '') === 'declaration_step')
                    <div class="address-box mb-4">
                        <p class="small mb-0" style="white-space: pre-line;">{{ $section['declaration_text'] ?? '' }}</p>
                    </div>
                    <div class="row">
                        @foreach ($section['fields'] ?? [] as $fKey => $field)
                            @php
                                if ($fKey === 'declaration_date') {
                                    $field = array_merge($field, ['readonly' => true]);
                                    if (empty($sectionValues['declaration_date'])) {
                                        $sectionValues['declaration_date'] = now()->toDateString();
                                    }
                                }
                            @endphp
                            @include('frontend.onboarding.partials.profile-field', [
                                'field' => $field,
                                'fieldKey' => $fKey,
                                'values' => $sectionValues,
                            ])
                        @endforeach
                    </div>

                @elseif (($section['type'] ?? '') === 'repeatable_table')
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-success btn-sm btn-add-row" data-table="{{ $sectionKey }}">+ Add</button>
                    </div>
                    @if (!empty($section['optional']))
                        <p class="small text-muted">Optional — leave blank if fresher.</p>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr.</th>
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
                                            <td>
                                                <input type="{{ $col['type'] ?? 'text' }}"
                                                    name="{{ $sectionKey }}[{{ $ri }}][{{ $col['key'] }}]"
                                                    data-col="{{ $col['key'] }}"
                                                    class="form-control {{ !empty($col['required']) ? 'required-field' : '' }}"
                                                    value="{{ $row[$col['key']] ?? '' }}"
                                                    {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                                            </td>
                                        @endforeach
                                        <td><button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <template id="tpl-row-{{ $sectionKey }}">
                        <tr>
                            <td class="row-num">1</td>
                            @foreach ($section['columns'] as $col)
                                <td>
                                    <input type="{{ $col['type'] ?? 'text' }}"
                                        class="form-control {{ !empty($col['required']) ? 'required-field' : '' }}"
                                        data-col="{{ $col['key'] }}"
                                        {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                                </td>
                            @endforeach
                            <td><button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button></td>
                        </tr>
                    </template>

                @elseif (!empty($section['groups']))
                    @foreach ($section['groups'] as $group)
                        <div class="{{ $group['css_class'] ?? 'mb-4' }}">
                            @if (!empty($group['same_as_current']))
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-primary mb-0">{{ $group['title'] }}</h5>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="sameAddress">
                                        <label class="form-check-label" for="sameAddress">Same as Current Address</label>
                                    </div>
                                </div>
                            @else
                                <h5 class="text-primary mb-3">{{ $group['title'] }}</h5>
                            @endif
                            <div class="row">
                                @foreach ($group['fields'] as $fKey => $field)
                                    @include('frontend.onboarding.partials.profile-field', [
                                        'field' => $field,
                                        'fieldKey' => $fKey,
                                        'values' => $sectionValues,
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                @else
                    <div class="row">
                        @foreach ($section['fields'] ?? [] as $fKey => $field)
                            @php
                                if ($fKey === 'declaration_date') {
                                    $field = array_merge($field, ['readonly' => true]);
                                    if (empty($sectionValues['declaration_date'])) {
                                        $sectionValues['declaration_date'] = now()->toDateString();
                                    }
                                }
                            @endphp
                            @include('frontend.onboarding.partials.profile-field', [
                                'field' => $field,
                                'fieldKey' => $fKey,
                                'values' => $sectionValues,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <input type="hidden" name="profile_information" id="profileInformationJson">

        <div class="d-flex justify-content-between align-items-center mt-4" id="profileNavBtns">
            <button type="button" class="btn btn-secondary" id="prevBtn" style="display:none;">Back</button>
            <div class="ms-auto d-flex gap-2" id="profileNavRight">
                <button type="button" class="btn btn-brand" id="nextBtn">Next</button>
                <button type="button" class="btn btn-success d-none" id="submitBtn">Submit & Save</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('onboarding.save', $employee->emp_url) }}" id="profileSaveForm" class="d-none">
        @csrf
        <input type="hidden" name="action" value="profile_save">
        <input type="hidden" name="profile_information" id="profileInformationPost">
    </form>

    <script>
        window.profileFormSchema = @json($schema);
        window.profileSavedData = @json($saved);
        window.indianCities = @json($indianCities ?? ['states' => [], 'cities' => []]);
        window.profileStorageKey = 'onboarding_profile_{{ $employee->emp_url }}';
        window.profileIsFresher = @json($isFresher);
        window.profileReeditActive = @json($profileReeditActive ?? $employee->profileReeditAllowed());
        window.profileHasSubmitted = @json($profileComplete ?? false);
        window.profileDraftStep = @json($profileDraftStep ?? 0);
        window.profileDraftUrl = @json(route('onboarding.save', $employee->emp_url));
        window.csrfToken = @json(csrf_token());

        (function () {
            const steps = document.querySelectorAll('.form-step');
            const total = steps.length;
            let current = 0;
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            const progressBar = document.getElementById('progressBar');
            const stepBadge = document.getElementById('stepBadge');
            const form = document.getElementById('profileForm');
            const pendingCityValues = {};

            function showStep(n) {
                current = n;
                steps.forEach((el, i) => el.classList.toggle('d-none', i !== n));
                prevBtn.style.display = n === 0 ? 'none' : 'inline-block';
                nextBtn.classList.toggle('d-none', n === total - 1);
                submitBtn.classList.toggle('d-none', n !== total - 1);
                progressBar.style.width = (((n + 1) / total) * 100) + '%';
                if (stepBadge) stepBadge.textContent = 'Step ' + (n + 1) + ' of ' + total;
                if (steps[n]?.dataset.section === 'family_details') {
                    syncFamilyFromBasic();
                    syncSpouseToFamilyTable();
                    updateFamilyMemberCount();
                    updateFamilyWaiverUI();
                }
                saveToLocal();
            }

            function isMarried() {
                return form.querySelector('[name="marital_status"]')?.value === 'Married';
            }

            function updateMaritalVisibility() {
                const married = isMarried();
                form.querySelectorAll('.spouse-field, .family-married-only').forEach(function (el) {
                    const box = el.closest('[class*="col-"]');
                    if (box) box.classList.toggle('d-none', !married);
                });
                const spouseBasic = form.querySelector('[data-section="basic_information"] [name="spouse_name"]');
                if (spouseBasic) {
                    spouseBasic.classList.toggle('required-field', married);
                }
            }

            function hasCompanyContacts() {
                return form.querySelector('[name="has_company_contacts"]')?.value === 'Yes';
            }

            function updateCompanyContactVisibility() {
                const show = hasCompanyContacts();
                form.querySelectorAll('.company-contact-field-wrap').forEach(function (box) {
                    box.classList.toggle('d-none', !show);
                });
                form.querySelectorAll('.company-contact-field').forEach(function (inp) {
                    inp.classList.toggle('required-field', show);
                    if (!show) {
                        inp.classList.remove('is-invalid');
                        inp.value = '';
                    }
                });
            }

            function getVisibleStepEl() {
                return steps[current];
            }

            function profileSectionTitle(stepEl) {
                return stepEl?.querySelector('.section-title')?.textContent?.trim() || 'This section';
            }

            function profileFieldLabel(field) {
                const wrap = field.closest('[class*="col-"]') || field.closest('td');
                const lab = wrap?.querySelector('label');
                if (lab) return lab.textContent.replace(/\s*\*\s*$/, '').trim();
                const col = field.dataset?.col;
                if (col) return col.replace(/_/g, ' ');
                return field.name || 'Field';
            }

            function profileStepErrors(stepEl) {
                const errors = [];
                if (!stepEl) return errors;

                const sectionTitle = profileSectionTitle(stepEl);
                const sectionKey = stepEl.dataset.section;

                stepEl.querySelectorAll('[data-phone-group]').forEach(group => {
                    group.classList.remove('is-invalid');
                });
                stepEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                const empSection = stepEl.querySelector('[data-employment-section]');
                if (empSection) {
                    const relToggle = empSection.querySelector('[name="has_industry_relatives"]');
                    const relRequiredCols = [
                        { key: 'name', label: 'Name' },
                        { key: 'relation', label: 'Relation' },
                        { key: 'company_name', label: 'Company Name' },
                        { key: 'designation', label: 'Designation' },
                    ];
                    if (relToggle && relToggle.value === 'yes') {
                        const relRows = collectTableRows(empSection.querySelector('[data-employment-relatives-table]'));
                        if (!relRows.length) {
                            relToggle.classList.add('is-invalid');
                            errors.push(sectionTitle + ' -> Add at least one industry relative');
                        } else {
                            relRows.forEach(function (row, idx) {
                                relRequiredCols.forEach(function (col) {
                                    if (!String(row[col.key] || '').trim()) {
                                        errors.push(sectionTitle + ' -> ' + col.label + ' is required for relative #' + (idx + 1));
                                    }
                                });
                            });
                            empSection.querySelectorAll('[data-employment-relatives-table] input').forEach(function (inp) {
                                const col = inputColumnKey(inp);
                                if (relRequiredCols.some(function (c) { return c.key === col; }) && !String(inp.value || '').trim()) {
                                    inp.classList.add('is-invalid');
                                }
                            });
                        }
                    }
                    if (window.profileIsFresher) {
                        const expected = empSection.querySelector('[name="expected_ctc"]');
                        if (!expected || !String(expected.value || '').trim()) {
                            if (expected) expected.classList.add('is-invalid');
                            errors.push(sectionTitle + ' -> Expected CTC is required');
                        }
                    } else {
                        let hasEmployer = false;
                        empSection.querySelectorAll('[data-employment-table] tr').forEach(tr => {
                            const nameInp = tr.querySelector('[data-col="employer_name"]');
                            if (nameInp && String(nameInp.value || '').trim()) hasEmployer = true;
                        });
                        if (!hasEmployer) {
                            errors.push(sectionTitle + ' -> Add at least one previous employer');
                        }
                        ['current_ctc', 'hr_name', 'hr_email', 'hr_phone'].forEach(name => {
                            const inp = empSection.querySelector('[name="' + name + '"]');
                            if (!inp || !String(inp.value || '').trim()) {
                                if (inp) inp.classList.add('is-invalid');
                                errors.push(sectionTitle + ' -> ' + profileFieldLabel(inp || { name: name }) + ' is required');
                            }
                        });
                    }
                }

                stepEl.querySelectorAll('.city-select-field').forEach(function (sel) {
                    if (sel.value === '__OTHER__') {
                        const otherInp = form.querySelector('[data-other-for="' + sel.dataset.cityField + '"]');
                        if (!otherInp || !String(otherInp.value || '').trim()) {
                            if (otherInp) otherInp.classList.add('is-invalid');
                            errors.push(sectionTitle + ' -> ' + profileFieldLabel(otherInp || sel) + ' is required');
                        }
                    }
                });

                stepEl.querySelectorAll('.required-field, [data-required="1"]').forEach(field => {
                    if (field.closest('[data-family-table]')) {
                        return;
                    }
                    if (field.closest('[data-industry-relatives-body]')?.classList.contains('d-none')) {
                        return;
                    }
                    if (empSection && empSection.contains(field) && window.profileIsFresher && field.name !== 'expected_ctc') {
                        return;
                    }
                    const empty = field.type === 'checkbox' ? !field.checked : !String(field.value || '').trim();
                    if (empty) {
                        field.classList.add('is-invalid');
                        const phoneGroup = field.closest('[data-phone-group]');
                        if (phoneGroup) phoneGroup.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> ' + profileFieldLabel(field) + ' is required');
                    }
                });

                stepEl.querySelectorAll('.required-checkbox').forEach(box => {
                    if (!box.checked) {
                        box.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> ' + profileFieldLabel(box) + ' is required');
                    } else {
                        box.classList.remove('is-invalid');
                    }
                });

                if (sectionKey === 'basic_information' && isMarried()) {
                    const spouse = stepEl.querySelector('[name="spouse_name"]');
                    if (!spouse || !String(spouse.value || '').trim()) {
                        if (spouse) spouse.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> Spouse name is required');
                    }
                }

                if (sectionKey === 'basic_information' && hasCompanyContacts()) {
                    ['company_contact_name', 'company_contact_relationship', 'company_contact_department'].forEach(function (name) {
                        const inp = stepEl.querySelector('[name="' + name + '"]');
                        if (!inp || !String(inp.value || '').trim()) {
                            if (inp) inp.classList.add('is-invalid');
                            errors.push(sectionTitle + ' -> ' + profileFieldLabel(inp || { name: name }) + ' is required');
                        }
                    });
                }

                if (sectionKey === 'basic_information') {
                    const pan = stepEl.querySelector('[name="pan"]');
                    const panVal = pan ? String(pan.value || '').trim().toUpperCase().replace(/\s+/g, '') : '';
                    if (panVal && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(panVal)) {
                        if (pan) pan.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> PAN Number must be 10 characters (e.g. ABCDE1234F).');
                    }
                    const uid = stepEl.querySelector('[name="uid"]');
                    const uidVal = uid ? String(uid.value || '').replace(/\D/g, '') : '';
                    if (uid && uid.value !== uidVal) {
                        uid.value = uidVal;
                    }
                    if (uidVal && !/^\d{12}$/.test(uidVal)) {
                        if (uid) uid.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> Aadhaar Number must be exactly 12 digits (numbers only, no commas or spaces).');
                    }
                }

                const familySection = stepEl.querySelector('[data-family-section]');
                if (familySection) {
                    validateFamilySection(familySection, sectionTitle).forEach(function (msg) {
                        errors.push(msg);
                    });
                }

                return errors;
            }

            const familyMemberFields = ['name', 'relation', 'age', 'dob', 'occupation'];

            function familyRelationKey(value) {
                return String(value || '').trim().toLowerCase();
            }

            function isCompleteFamilyMember(row) {
                return familyMemberFields.every(function (key) {
                    return String(row[key] || '').trim() !== '';
                });
            }

            function isFamilyWaiver() {
                const cb = form.querySelector('[name="no_father_mother"]');
                return !!(cb && cb.checked);
            }

            function validateFamilySection(familySection, sectionTitle) {
                const errors = [];
                const waiver = isFamilyWaiver();

                if (isMarried()) {
                    const spouse = familySection.querySelector('[name="spouse_name"]');
                    const basicSpouse = form.querySelector('[data-section="basic_information"] [name="spouse_name"]');
                    let spouseVal = spouse ? String(spouse.value || '').trim() : '';
                    if (!spouseVal && basicSpouse) {
                        spouseVal = String(basicSpouse.value || '').trim();
                        if (spouse && spouseVal) spouse.value = spouseVal;
                    }
                    if (!spouseVal) {
                        if (spouse) spouse.classList.add('is-invalid');
                        errors.push(sectionTitle + ' -> Spouse name is required when marital status is Married');
                    }
                }

                const tbody = familySection.querySelector('[data-family-table]');
                const rows = collectTableRows(tbody);
                const complete = rows.filter(isCompleteFamilyMember);

                if (!waiver) {
                    const relations = complete.map(function (row) {
                        return familyRelationKey(row.relation);
                    });

                    if (complete.length < 2) {
                        errors.push(sectionTitle + ' -> Add at least 2 family members with Name, Relation, Age, DOB and Occupation filled.');
                    }
                    if (!relations.includes('father')) {
                        errors.push(sectionTitle + ' -> Father is required (name, relation, age, DOB, occupation).');
                    }
                    if (!relations.includes('mother')) {
                        errors.push(sectionTitle + ' -> Mother is required (name, relation, age, DOB, occupation).');
                    }

                    if (tbody) {
                        tbody.querySelectorAll('tr').forEach(function (tr, index) {
                            const row = {};
                            tr.querySelectorAll('input[data-col]').forEach(function (inp) {
                                row[inp.dataset.col] = inp.value;
                            });
                            const hasAny = familyMemberFields.some(function (key) {
                                return String(row[key] || '').trim() !== '';
                            });
                            const completeRow = isCompleteFamilyMember(row);
                            if (hasAny && !completeRow) {
                                tr.querySelectorAll('input[data-col]').forEach(function (inp) {
                                    if (!String(inp.value || '').trim()) {
                                        inp.classList.add('is-invalid');
                                    }
                                });
                                errors.push(sectionTitle + ' -> Family member row #' + (index + 1) + ' — fill Name, Relation, Age, DOB and Occupation.');
                            }
                        });
                    }
                }

                return errors;
            }

            function updateFamilyWaiverUI() {
                const familyStep = form.querySelector('[data-section="family_details"]');
                if (!familyStep) return;
                const waiver = isFamilyWaiver();
                const block = familyStep.querySelector('[data-family-members-block]');
                const counter = document.getElementById('familyMemberCount');
                if (block) {
                    block.classList.toggle('opacity-50', waiver);
                    block.querySelectorAll('input, button.btn-add-row, button.btn-remove-row').forEach(function (el) {
                        el.disabled = waiver;
                    });
                }
                if (counter) {
                    counter.innerHTML = waiver
                        ? 'Parent details waived — you may continue with <strong>Next</strong>.'
                        : 'Complete family members: <strong>' + collectTableRows(familyStep.querySelector('[data-family-table]')).filter(isCompleteFamilyMember).length + '</strong> (minimum 2 — Father and Mother required)';
                }
            }

            function validateStep() {
                return profileStepErrors(getVisibleStepEl());
            }

            function validateAllProfileSteps() {
                let all = [];
                steps.forEach(function (stepEl) {
                    all = all.concat(profileStepErrors(stepEl));
                });
                return [...new Set(all)];
            }

            function collectTableRows(tbody) {
                const rows = [];
                if (!tbody) return rows;
                tbody.querySelectorAll('tr').forEach(function (tr) {
                    const row = {};
                    tr.querySelectorAll('input[data-col], input[name]').forEach(function (inp) {
                        const col = inputColumnKey(inp);
                        if (col) row[col] = inp.value;
                    });
                    if (Object.values(row).some(function (v) { return String(v).trim() !== ''; })) {
                        rows.push(row);
                    }
                });
                return rows;
            }

            function inputColumnKey(inp) {
                return inp.dataset.col || (inp.name && inp.name.match(/\[([^\]]+)\]$/) || [])[1] || '';
            }

            function applyTableRows(tbody, tpl, sectionKey, rows) {
                if (!tbody || !rows.length) return;
                while (tbody.rows.length < rows.length && tpl) {
                    tbody.appendChild(tpl.content.cloneNode(true).querySelector('tr'));
                }
                while (tbody.rows.length > rows.length && tbody.rows.length > 1) {
                    tbody.deleteRow(tbody.rows.length - 1);
                }
                rows.forEach(function (row, ri) {
                    const tr = tbody.rows[ri];
                    if (!tr) return;
                    tr.querySelectorAll('input').forEach(function (inp) {
                        const col = inputColumnKey(inp);
                        if (!col) return;
                        if (!inp.dataset.col) inp.dataset.col = col;
                        if (row[col] !== undefined) inp.value = row[col];
                        if (sectionKey) inp.name = sectionKey + '[' + ri + '][' + col + ']';
                    });
                });
                tbody.querySelectorAll('.row-num').forEach(function (td, i) { td.textContent = i + 1; });
            }

            function collectSection(stepEl) {
                const key = stepEl.dataset.section;
                const familySection = stepEl.querySelector('[data-family-section]');
                if (familySection) {
                    const data = {};
                    familySection.querySelectorAll('[data-family-header] input, [data-family-header] select, [data-family-header] textarea').forEach(function (el) {
                        if (!el.name || el.type === 'button') return;
                        if (el.type === 'checkbox') data[el.name] = el.checked ? '1' : '';
                        else data[el.name] = el.value;
                    });
                    const members = collectTableRows(familySection.querySelector('[data-family-table]'));
                    if (members.length) data.members = members;
                    return data;
                }
                const empSection = stepEl.querySelector('[data-employment-section]');
                if (empSection) {
                    const data = {};
                    const rows = collectTableRows(empSection.querySelector('[data-employment-table]'));
                    if (rows.length) data.records = rows;
                    empSection.querySelectorAll('[data-employment-footer] input, [data-employment-footer] select, [data-employment-footer] textarea').forEach(el => {
                        if (!el.name || el.type === 'button') return;
                        if (el.type === 'checkbox') data[el.name] = el.checked ? '1' : '';
                        else data[el.name] = el.value;
                    });
                    const relToggle = empSection.querySelector('[name="has_industry_relatives"]');
                    if (relToggle) data.has_industry_relatives = relToggle.value;
                    if (relToggle && relToggle.value === 'yes') {
                        const relRows = collectTableRows(empSection.querySelector('[data-employment-relatives-table]'));
                        if (relRows.length) data.industry_relatives = relRows;
                    }
                    return data;
                }
                const table = stepEl.querySelector('tbody');
                if (table && stepEl.dataset.sectionType === 'repeatable_table') {
                    return collectTableRows(table);
                }
                const data = {};
                stepEl.querySelectorAll('input, select, textarea').forEach(el => {
                    if (!el.name || el.type === 'button') return;
                    if (el.type === 'checkbox') data[el.name] = el.checked ? '1' : '';
                    else data[el.name] = el.value;
                });
                return data;
            }

            function normalizeBasicInformation(information) {
                const basic = information.basic_information;
                if (!basic || typeof basic !== 'object') return;
                [
                    'voters_id_number',
                    'driving_license_number',
                    'driving_license_valid_till',
                    'passport_number',
                    'passport_validity',
                ].forEach(function (key) {
                    if (!String(basic[key] || '').trim()) {
                        basic[key] = 'Not applicable';
                    }
                });
            }

            function syncCrossSections(information) {
                const basic = information.basic_information || {};
                const family = information.family_details || {};
                if (isMarried() && family.spouse_name) {
                    basic.spouse_name = family.spouse_name;
                }
                const members = Array.isArray(family.members) ? family.members : [];
                const fatherRow = members.find(function (row) {
                    return familyRelationKey(row.relation) === 'father' && String(row.name || '').trim() !== '';
                });
                if (fatherRow) {
                    family.father_name = String(fatherRow.name).trim();
                    basic.fathers_name = family.father_name;
                }
                information.basic_information = basic;
                information.family_details = family;
            }

            function resolveCityFields(information) {
                [
                    ['basic_information', 'city', 'city_other'],
                    ['address_details', 'current_city', 'current_city_other'],
                    ['address_details', 'permanent_city', 'permanent_city_other'],
                ].forEach(function (pair) {
                    const section = information[pair[0]];
                    if (!section || typeof section !== 'object') return;
                    if (section[pair[1]] === '__OTHER__') {
                        section[pair[1]] = String(section[pair[2]] || '').trim();
                    }
                    delete section[pair[2]];
                });
                return information;
            }

            function collectAll() {
                const information = {};
                steps.forEach(stepEl => {
                    information[stepEl.dataset.section] = collectSection(stepEl);
                });
                normalizeBasicInformation(information);
                syncCrossSections(information);
                return resolveCityFields(information);
            }

            function saveToLocal() {
                try {
                    localStorage.setItem(window.profileStorageKey, JSON.stringify(collectAll()));
                } catch (e) {}
            }

            function applyProfileData(data) {
                if (!data || typeof data !== 'object') return;
                Object.keys(data).forEach(function (sectionKey) {
                    const stepEl = form.querySelector('[data-section="' + sectionKey + '"]');
                    if (!stepEl) return;
                    const val = data[sectionKey];
                    const sectionType = stepEl.dataset.sectionType || 'fields';

                    if (sectionType === 'repeatable_table' && Array.isArray(val)) {
                        const tbody = document.getElementById('table-' + sectionKey);
                        const tpl = document.getElementById('tpl-row-' + sectionKey);
                        applyTableRows(tbody, tpl, sectionKey, val);
                        return;
                    }

                    if (sectionType === 'family_section' && val && typeof val === 'object' && !Array.isArray(val)) {
                        const familySection = stepEl.querySelector('[data-family-section]');
                        if (familySection) {
                            Object.keys(val).forEach(function (name) {
                                if (name === 'members') return;
                                const el = familySection.querySelector('[name="' + name + '"]');
                                if (!el) return;
                                if (el.type === 'checkbox') el.checked = val[name] === '1' || val[name] === true;
                                else el.value = val[name];
                            });
                            const members = val.members || [];
                            const tbody = familySection.querySelector('[data-family-table]');
                            const tpl = document.getElementById('tpl-row-' + sectionKey);
                            applyTableRows(tbody, tpl, null, members);
                        }
                        return;
                    }

                    if (Array.isArray(val)) return;

                    const empSection = stepEl.querySelector('[data-employment-section]');
                    const empTbody = empSection && empSection.querySelector('[data-employment-table]');
                    const empRows = val.records || (val.last_company ? [val.last_company] : null);
                    if (empSection && empTbody && Array.isArray(empRows) && empRows.length) {
                        const tpl = document.getElementById('tpl-row-' + sectionKey);
                        applyTableRows(empTbody, tpl, null, empRows);
                    }
                    if (empSection && val.has_industry_relatives !== undefined) {
                        const relToggle = empSection.querySelector('[name="has_industry_relatives"]');
                        if (relToggle) relToggle.value = val.has_industry_relatives;
                    }
                    if (empSection && val.has_industry_relatives === 'yes' && Array.isArray(val.industry_relatives) && val.industry_relatives.length) {
                        const relTbody = empSection.querySelector('[data-employment-relatives-table]');
                        const relTpl = document.getElementById('tpl-row-' + sectionKey + '_relatives');
                        applyTableRows(relTbody, relTpl, sectionKey + '_relatives', val.industry_relatives);
                    }
                    Object.keys(val).forEach(function (name) {
                        if (name === 'records' || name === 'last_company' || name === 'industry_relatives' || name === 'has_industry_relatives') return;
                        const scope = empSection ? empSection.querySelector('[data-employment-footer]') : stepEl;
                        const el = (scope || stepEl).querySelector('[name="' + name + '"]');
                        if (!el) return;
                        if (el.type === 'checkbox') el.checked = val[name] === '1' || val[name] === true;
                        else el.value = val[name];
                        if (el.classList.contains('city-select-field') && val[name]) {
                            pendingCityValues[el.dataset.cityField || name] = val[name];
                        }
                        if (el.classList.contains('city-other-input') && val[name]) {
                            const cityField = el.dataset.otherFor;
                            if (cityField && pendingCityValues[cityField] === undefined) {
                                pendingCityValues[cityField] = '__OTHER__';
                            }
                        }
                    });
                });
                updateMaritalVisibility();
                updateCompanyContactVisibility();
                updateFamilyMemberCount();
                updateIndustryRelativesVisibility();
            }

            function updateIndustryRelativesVisibility() {
                form.querySelectorAll('[data-employment-relatives]').forEach(function (block) {
                    const toggle = block.querySelector('[name="has_industry_relatives"]');
                    const body = block.querySelector('[data-industry-relatives-body]');
                    if (!toggle || !body) return;
                    const show = toggle.value === 'yes';
                    body.classList.toggle('d-none', !show);
                    body.querySelectorAll('input[data-col]').forEach(function (inp) {
                        const required = show && inp.dataset.required === '1';
                        inp.classList.toggle('required-field', required);
                        if (!show) {
                            inp.classList.remove('is-invalid');
                            inp.value = '';
                        }
                    });
                });
            }

            function syncFamilyFromBasic() {
                const basicStep = form.querySelector('[data-section="basic_information"]');
                const familyStep = form.querySelector('[data-section="family_details"]');
                if (!basicStep || !familyStep) return;
                const father = basicStep.querySelector('[name="fathers_name"]');
                const spouse = basicStep.querySelector('[name="spouse_name"]');
                const famSpouse = familyStep.querySelector('[name="spouse_name"]');
                if (spouse && famSpouse && !famSpouse.dataset.touched) famSpouse.value = spouse.value;

                const tbody = familyStep.querySelector('[data-family-table]');
                if (!father || !tbody || !father.value.trim()) return;
                let fatherRow = null;
                tbody.querySelectorAll('tr').forEach(function (tr) {
                    const rel = tr.querySelector('[data-col="relation"]');
                    if (rel && familyRelationKey(rel.value) === 'father') fatherRow = tr;
                });
                if (!fatherRow && tbody.rows[0]) {
                    fatherRow = tbody.rows[0];
                    const relInp = fatherRow.querySelector('[data-col="relation"]');
                    if (relInp && !String(relInp.value || '').trim()) relInp.value = 'Father';
                }
                const nameInp = fatherRow ? fatherRow.querySelector('[data-col="name"]') : null;
                if (nameInp && !nameInp.dataset.touched) nameInp.value = father.value;
            }

            function syncSpouseToFamilyTable() {
                const familyStep = form.querySelector('[data-section="family_details"]');
                if (!familyStep || !isMarried()) return;
                const include = familyStep.querySelector('[name="include_spouse"]');
                const spouseName = familyStep.querySelector('[name="spouse_name"]')?.value?.trim();
                const tbody = familyStep.querySelector('[data-family-table]');
                if (!tbody || !include || !include.checked || !spouseName) return;
                let spouseRow = null;
                tbody.querySelectorAll('tr').forEach(function (tr) {
                    const rel = tr.querySelector('[data-col="relation"]');
                    if (rel && String(rel.value).trim().toLowerCase() === 'spouse') spouseRow = tr;
                });
                if (!spouseRow) {
                    const tpl = document.getElementById('tpl-row-family_details');
                    if (!tpl) return;
                    spouseRow = tpl.content.cloneNode(true).querySelector('tr');
                    tbody.appendChild(spouseRow);
                }
                const nameInp = spouseRow.querySelector('[data-col="name"]');
                const relInp = spouseRow.querySelector('[data-col="relation"]');
                if (nameInp) nameInp.value = spouseName;
                if (relInp) relInp.value = 'Spouse';
                tbody.querySelectorAll('.row-num').forEach(function (td, i) { td.textContent = i + 1; });
                updateFamilyMemberCount();
            }

            function updateFamilyMemberCount() {
                const familyStep = form.querySelector('[data-section="family_details"]');
                const counter = document.getElementById('familyMemberCount');
                if (!familyStep || !counter) return;
                if (isFamilyWaiver()) {
                    counter.innerHTML = 'Parent details waived — you may continue with <strong>Next</strong>.';
                    return;
                }
                const members = collectTableRows(familyStep.querySelector('[data-family-table]'));
                const complete = members.filter(isCompleteFamilyMember).length;
                counter.innerHTML = 'Complete family members: <strong>' + complete + '</strong> (minimum 2 — Father and Mother required)';
            }

            function loadFromLocal() {
                try {
                    const raw = localStorage.getItem(window.profileStorageKey) || '{}';
                    applyProfileData(JSON.parse(raw));
                } catch (e) {}
            }

            function mergeDraftData(server, local) {
                const merged = Object.assign({}, server || {});
                if (!local || typeof local !== 'object') return merged;
                Object.keys(local).forEach(function (key) {
                    const localVal = local[key];
                    const serverVal = merged[key];
                    if (Array.isArray(localVal) && localVal.length > 0) {
                        merged[key] = localVal;
                        return;
                    }
                    if (localVal && typeof localVal === 'object' && !Array.isArray(localVal)) {
                        merged[key] = Object.assign({}, serverVal && typeof serverVal === 'object' ? serverVal : {}, localVal);
                    }
                });
                return merged;
            }

            function saveDraftToServer(nextStep) {
                const fd = new FormData();
                fd.append('_token', window.csrfToken);
                fd.append('action', 'profile_draft');
                fd.append('draft_step', String(nextStep));
                fd.append('profile_information', JSON.stringify(collectAll()));
                return fetch(window.profileDraftUrl, {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                }).then(function (r) {
                    const contentType = r.headers.get('content-type') || '';
                    if (contentType.indexOf('application/json') === -1) {
                        throw new Error('Could not save draft (server error). Please refresh and try again.');
                    }
                    return r.json().then(function (data) {
                        if (!r.ok) throw new Error(data.message || 'Save failed');
                        return data;
                    });
                });
            }

            function updateDraftBadge(savedAt) {
                const badge = document.getElementById('draftSavedBadge');
                if (!badge) return;
                badge.classList.remove('d-none');
                badge.textContent = savedAt ? 'Draft saved' : 'Draft saved';
            }

            function initIndianCitySelects() {
                const cities = (window.indianCities && window.indianCities.cities) || {};
                function populateCitySelect(citySel, stateValue, savedCity) {
                    citySel.querySelectorAll('option:not([value=""]):not([value="__OTHER__"])').forEach(function (o) { o.remove(); });
                    (cities[stateValue] || []).forEach(function (city) {
                        const opt = document.createElement('option');
                        opt.value = city;
                        opt.textContent = city;
                        citySel.insertBefore(opt, citySel.querySelector('option[value="__OTHER__"]'));
                    });
                    const pick = pendingCityValues[citySel.dataset.cityField] || savedCity || citySel.dataset.savedCity || '';
                    if (pick && pick !== '__OTHER__') {
                        citySel.value = pick;
                        if (citySel.value !== pick) {
                            citySel.value = '__OTHER__';
                            const otherInp = form.querySelector('[data-other-for="' + citySel.dataset.cityField + '"]');
                            if (otherInp) {
                                otherInp.value = pick;
                                otherInp.classList.remove('d-none');
                            }
                        }
                    }
                    const otherInp = form.querySelector('[data-other-for="' + citySel.dataset.cityField + '"]');
                    if (otherInp) {
                        otherInp.classList.toggle('d-none', citySel.value !== '__OTHER__');
                    }
                }
                form.querySelectorAll('.state-select-field').forEach(function (stateSel) {
                    stateSel.addEventListener('change', function () {
                        form.querySelectorAll('.city-select-field[data-state-field="' + stateSel.dataset.stateField + '"]').forEach(function (citySel) {
                            citySel.value = '';
                            populateCitySelect(citySel, stateSel.value, '');
                            citySel.dataset.savedCity = '';
                        });
                        rebuildFullAddress('current');
                        rebuildFullAddress('permanent');
                        saveToLocal();
                    });
                });
                form.querySelectorAll('.city-select-field').forEach(function (citySel) {
                    const stateSel = form.querySelector('[data-state-field="' + citySel.dataset.stateField + '"]');
                    if (stateSel) populateCitySelect(citySel, stateSel.value, citySel.dataset.savedCity);
                    citySel.addEventListener('change', function () {
                        const otherInp = form.querySelector('[data-other-for="' + citySel.dataset.cityField + '"]');
                        if (!otherInp) return;
                        const isOther = citySel.value === '__OTHER__';
                        otherInp.classList.toggle('d-none', !isOther);
                        if (!isOther) otherInp.value = '';
                        if (citySel.name.indexOf('current_') === 0) rebuildFullAddress('current');
                        if (citySel.name.indexOf('permanent_') === 0) rebuildFullAddress('permanent');
                        saveToLocal();
                    });
                });
                form.querySelectorAll('.city-other-input').forEach(function (inp) {
                    inp.addEventListener('input', function () {
                        if (inp.name.indexOf('current_') === 0) rebuildFullAddress('current');
                        if (inp.name.indexOf('permanent_') === 0) rebuildFullAddress('permanent');
                    });
                });
            }

            function rebuildFullAddress(prefix) {
                const parts = ['line1', 'line2', 'locality', 'city', 'state', 'country', 'pincode', 'landmark']
                    .map(function (s) {
                        const el = form.querySelector('[name="' + prefix + '_' + s + '"]');
                        if (!el) return '';
                        if (el.classList && el.classList.contains('city-select-field') && el.value === '__OTHER__') {
                            const other = form.querySelector('[data-other-for="' + el.dataset.cityField + '"]');
                            return other ? other.value : '';
                        }
                        return el.value || '';
                    }).filter(Boolean);
                const ta = form.querySelector('[name="' + prefix + '_full_address"]');
                if (ta) ta.value = parts.join(', ');
            }

            function initProfileDraft() {
                if (window.profileReeditActive) {
                    try { localStorage.removeItem(window.profileStorageKey); } catch (e) {}
                    applyProfileData(window.profileSavedData || {});
                    initIndianCitySelects();
                    return;
                }
                if (window.profileHasSubmitted) {
                    return;
                }
                let local = {};
                try { local = JSON.parse(localStorage.getItem(window.profileStorageKey) || '{}'); } catch (e) {}
                applyProfileData(mergeDraftData(window.profileSavedData || {}, local));
                initIndianCitySelects();
            }

            nextBtn.addEventListener('click', function () {
                const stepErrors = validateStep();
                if (stepErrors.length) {
                    alert(stepErrors.join('\n'));
                    return;
                }
                if (current >= total - 1) return;
                const next = current + 1;
                nextBtn.disabled = true;
                saveDraftToServer(next).then(function (res) {
                    nextBtn.disabled = false;
                    if (res && res.ok) {
                        showStep(next);
                        updateDraftBadge(res.saved_at);
                    } else {
                        alert((res && res.message) ? res.message : 'Could not save draft. Please try again.');
                    }
                }).catch(function (err) {
                    nextBtn.disabled = false;
                    alert(err.message || 'Could not save draft. Check your connection and try again.');
                });
            });

            prevBtn.addEventListener('click', function () {
                if (current > 0) showStep(current - 1);
            });

            submitBtn.addEventListener('click', function () {
                const allErrors = validateAllProfileSteps();
                if (allErrors.length) {
                    alert(allErrors.join('\n'));
                    return;
                }
                const json = JSON.stringify(collectAll());
                document.getElementById('profileInformationPost').value = json;
                document.getElementById('profileSaveForm').submit();
            });

            document.querySelectorAll('.btn-add-row').forEach(btn => {
                btn.addEventListener('click', function () {
                    const key = btn.dataset.table;
                    const tbody = document.getElementById('table-' + key);
                    const tpl = document.getElementById('tpl-row-' + key);
                    if (!tbody || !tpl) return;
                    const clone = tpl.content.cloneNode(true);
                    const tr = clone.querySelector('tr');
                    const idx = tbody.rows.length;
                    tr.querySelectorAll('input[data-col]').forEach(inp => {
                        inp.name = key + '[' + idx + '][' + inp.dataset.col + ']';
                    });
                    tbody.appendChild(tr);
                    renumber(tbody);
                    updateIndustryRelativesVisibility();
                });
            });

            form.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-row')) {
                    const tbody = e.target.closest('tbody');
                    const minRows = tbody && tbody.hasAttribute('data-family-table')
                        ? (isFamilyWaiver() ? 0 : 2)
                        : 1;
                    if (tbody && tbody.rows.length > minRows) {
                        e.target.closest('tr').remove();
                        renumber(tbody);
                        tbody.querySelectorAll('tr').forEach((tr, i) => {
                            tr.querySelectorAll('input').forEach(inp => {
                                const m = inp.name && inp.name.match(/^([^[]+)\[\d+\]\[([^\]]+)\]$/);
                                if (m) inp.name = m[1] + '[' + i + '][' + m[2] + ']';
                            });
                        });
                        if (tbody.hasAttribute('data-family-table')) {
                            updateFamilyMemberCount();
                        }
                    }
                }
            });

            function renumber(tbody) {
                tbody.querySelectorAll('.row-num').forEach((td, i) => td.textContent = i + 1);
            }

            document.getElementById('sameAddress')?.addEventListener('change', function () {
                if (!this.checked) return;
                ['co','line1','line2','locality','country','pincode','landmark'].forEach(s => {
                    const c = form.querySelector('[name="current_' + s + '"]');
                    const p = form.querySelector('[name="permanent_' + s + '"]');
                    if (c && p) p.value = c.value;
                });
                const cState = form.querySelector('[name="current_state"]');
                const pState = form.querySelector('[name="permanent_state"]');
                if (cState && pState) {
                    pState.value = cState.value;
                    pState.dispatchEvent(new Event('change', { bubbles: true }));
                }
                const cCity = form.querySelector('[name="current_city"]');
                const pCity = form.querySelector('[name="permanent_city"]');
                if (cCity && pCity) {
                    pCity.value = cCity.value;
                    if (cCity.value === '__OTHER__') {
                        const cOther = form.querySelector('[name="current_city_other"]');
                        const pOther = form.querySelector('[name="permanent_city_other"]');
                        if (cOther && pOther) {
                            pOther.value = cOther.value;
                            pOther.classList.remove('d-none');
                        }
                    }
                    pCity.dispatchEvent(new Event('change', { bubbles: true }));
                }
                rebuildFullAddress('permanent');
            });

            document.querySelectorAll('.current-address').forEach(f => {
                f.addEventListener('input', function () { rebuildFullAddress('current'); });
                f.addEventListener('change', function () { rebuildFullAddress('current'); });
            });
            document.querySelectorAll('.permanent-address').forEach(f => {
                f.addEventListener('input', function () { rebuildFullAddress('permanent'); });
                f.addEventListener('change', function () { rebuildFullAddress('permanent'); });
            });

            form.querySelector('[name="marital_status"]')?.addEventListener('change', function () {
                updateMaritalVisibility();
                updateFamilyMemberCount();
                saveToLocal();
            });

            form.querySelector('[name="has_company_contacts"]')?.addEventListener('change', function () {
                updateCompanyContactVisibility();
                saveToLocal();
            });

            function bindDigitsOnlyFields() {
                form.querySelectorAll('[data-digits-only]').forEach(function (inp) {
                    function cleanDigitsOnly() {
                        const cleaned = String(inp.value || '').replace(/\D/g, '');
                        if (inp.value !== cleaned) {
                            inp.value = cleaned;
                        }
                    }
                    inp.addEventListener('input', cleanDigitsOnly);
                    inp.addEventListener('paste', function (e) {
                        e.preventDefault();
                        const pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
                        const start = inp.selectionStart ?? inp.value.length;
                        const end = inp.selectionEnd ?? inp.value.length;
                        const merged = (inp.value.slice(0, start) + pasted + inp.value.slice(end)).replace(/\D/g, '');
                        const max = inp.maxLength > 0 ? inp.maxLength : merged.length;
                        inp.value = merged.slice(0, max);
                        inp.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    cleanDigitsOnly();
                });
            }

            bindDigitsOnlyFields();

            form.addEventListener('input', function (e) {
                if (e.target.matches('[name="no_father_mother"]')) {
                    updateFamilyWaiverUI();
                    updateFamilyMemberCount();
                }
                if (e.target.matches('[name="spouse_name"], [name="include_spouse"]')) {
                    if (e.target.name === 'spouse_name') {
                        e.target.dataset.touched = '1';
                    }
                    syncSpouseToFamilyTable();
                    updateFamilyMemberCount();
                }
                if (e.target.matches('[data-col]') && e.target.closest('[data-family-table]')) {
                    e.target.dataset.touched = '1';
                    updateFamilyMemberCount();
                }
                if (e.target.matches('[name="fathers_name"], [name="spouse_name"]') && e.target.closest('[data-section="basic_information"]')) {
                    syncFamilyFromBasic();
                }
                saveToLocal();
            });

            form.addEventListener('change', function (e) {
                if (e.target.matches('[name="no_father_mother"]')) {
                    updateFamilyWaiverUI();
                    updateFamilyMemberCount();
                    saveToLocal();
                }
                if (e.target.matches('[name="include_spouse"]')) {
                    syncSpouseToFamilyTable();
                    updateFamilyMemberCount();
                }
                if (e.target.matches('[name="has_industry_relatives"]')) {
                    updateIndustryRelativesVisibility();
                }
                saveToLocal();
            });

            form.querySelectorAll('[data-family-header] input, [data-family-table]').forEach(function (el) {
                el.addEventListener('input', updateFamilyMemberCount);
            });

            initProfileDraft();
            updateMaritalVisibility();
            updateCompanyContactVisibility();
            updateIndustryRelativesVisibility();
            updateFamilyWaiverUI();
            const resumeStep = Math.min(Math.max(0, window.profileDraftStep || 0), total - 1);
            showStep(resumeStep);
        })();
    </script>
@endif

@php
    $showDocumentNextStep = ($profileComplete ?? false)
        && !($canEditProfile ?? false)
        && !$employee->hasCandidateSubmittedDocuments();
@endphp

@if ($showDocumentNextStep)
    <div class="alert alert-success mt-3 mb-0">
        <i class="bi bi-check-circle me-1"></i>
        <strong>Information Details submitted successfully.</strong>
        <br class="d-none d-sm-inline">
        <span class="d-block d-sm-inline mt-1 mt-sm-0">
            Next step: upload your documents — select the
            <a href="{{ route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'document']) }}" class="alert-link fw-semibold">Document</a>
            tab above.
        </span>
    </div>
@elseif (!($profileComplete ?? false) || ($canEditProfile ?? false))
    <div class="alert alert-info mt-3 mb-0 small">
        Complete all steps and click <strong>Submit & Save</strong>.
    </div>
@endif
