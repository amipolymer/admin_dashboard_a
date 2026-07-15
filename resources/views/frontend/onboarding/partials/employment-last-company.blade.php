@php
    $section = $employmentSection ?? [];
    $sectionKey = $section['key'] ?? 'previous_employment';
    $sectionValues = $savedEmployment ?? [];
    $isFresher = $isFresher ?? false;
    $records = $sectionValues['records'] ?? [];
    if (empty($records) && !empty($sectionValues['last_company']) && is_array($sectionValues['last_company'])) {
        $records = [$sectionValues['last_company']];
    }
    if (empty($records) && is_array($sectionValues) && isset($sectionValues[0]) && is_array($sectionValues[0])) {
        $records = $sectionValues;
    }
    if (empty($records) && !$isFresher) {
        $records = [[]];
    }
    $columns = $section['columns'] ?? [];
    $footer = $section['footer_group'] ?? [];
    $footerFields = $footer['fields'] ?? [];
@endphp

<div data-employment-section data-fresher="{{ $isFresher ? '1' : '0' }}">
    @if ($isFresher)
        <div class="alert alert-info py-2 small mb-3">
            <strong>Fresher profile.</strong> You do not need to add previous employers. Enter your <strong>expected CTC</strong> only.
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="text-primary mb-0">{{ $section['table_title'] ?? 'Previous employment' }}</h5>
            <button type="button" class="btn btn-success btn-sm btn-add-row" data-table="{{ $sectionKey }}">+ Add</button>
        </div>
        <p class="small text-muted mb-2">
            Enter your <strong>joining date</strong> at each previous employer. HR will add the <strong>last working date</strong> for background verification (most recent employer only).
        </p>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sr.</th>
                        @foreach ($columns as $col)
                            <th>{{ $col['label'] }}@if (!empty($col['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody id="table-{{ $sectionKey }}" data-employment-table>
                    @foreach ($records as $ri => $row)
                        <tr>
                            <td class="row-num">{{ $ri + 1 }}</td>
                            @foreach ($columns as $col)
                                <td>
                                    <input type="{{ $col['type'] ?? 'text' }}"
                                        data-col="{{ $col['key'] }}"
                                        class="form-control form-control-sm {{ !empty($col['required']) ? 'required-field' : '' }}"
                                        value="{{ $row[$col['key']] ?? '' }}"
                                        placeholder="{{ $col['placeholder'] ?? '' }}"
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
                @foreach ($columns as $col)
                    <td>
                        <input type="{{ $col['type'] ?? 'text' }}"
                            data-col="{{ $col['key'] }}"
                            class="form-control form-control-sm {{ !empty($col['required']) ? 'required-field' : '' }}"
                            placeholder="{{ $col['placeholder'] ?? '' }}"
                            {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                    </td>
                @endforeach
                <td><button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button></td>
            </tr>
        </template>
    @endif

    @if (!empty($footerFields))
        <div class="{{ $footer['css_class'] ?? 'address-box mt-4' }}" data-employment-footer>
            @if ($isFresher)
                <h5 class="text-primary mb-3">Expected salary</h5>
            @else
                <h5 class="text-primary mb-3">{{ $footer['title'] ?? 'CTC & previous company HR contact' }}</h5>
                <p class="small text-muted mb-3">Current company salary and HR contact at your last employer (the record used for employment verification).</p>
            @endif
            <div class="row">
                @foreach ($footerFields as $fKey => $field)
                    @php
                        if ($isFresher && !in_array($fKey, ['expected_ctc'], true)) {
                            continue;
                        }
                        $fieldCopy = $field;
                        if ($isFresher && $fKey === 'expected_ctc') {
                            $fieldCopy['required'] = true;
                            $fieldCopy['class'] = trim(($fieldCopy['class'] ?? 'form-control') . ' required-field');
                        }
                        if (!$isFresher && !empty($field['required'])) {
                            $fieldCopy['required'] = true;
                            $fieldCopy['class'] = trim(($fieldCopy['class'] ?? 'form-control') . ' required-field');
                        }
                    @endphp
                    @include('frontend.onboarding.partials.profile-field', [
                        'field' => $fieldCopy,
                        'fieldKey' => $fKey,
                        'values' => $sectionValues,
                    ])
                @endforeach
            </div>
        </div>
    @endif

    @php
        $relativesGroup = $section['relatives_group'] ?? [];
        $relColumns = $relativesGroup['columns'] ?? [];
        $toggleField = $relativesGroup['toggle_field'] ?? [];
        $hasRelatives = ($sectionValues['has_industry_relatives'] ?? '') === 'yes';
        $relRecords = $sectionValues['industry_relatives'] ?? [];
        if (empty($relRecords)) {
            $relRecords = [[]];
        }
    @endphp
    @if (!empty($relColumns))
        <div class="address-box mt-4" data-employment-relatives>
            <h5 class="text-primary mb-2">{{ $relativesGroup['title'] ?? 'Industry relatives' }}</h5>
            @if (!empty($toggleField))
                @include('frontend.onboarding.partials.profile-field', [
                    'field' => $toggleField,
                    'fieldKey' => $toggleField['name'] ?? 'has_industry_relatives',
                    'values' => $sectionValues,
                ])
            @endif
            <div data-industry-relatives-body class="{{ $hasRelatives ? '' : 'd-none' }}">
            @if (!empty($relativesGroup['intro']))
                <p class="small text-muted mb-3">{{ $relativesGroup['intro'] }}</p>
            @endif
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-success btn-sm btn-add-row" data-table="{{ $sectionKey }}_relatives">+ Add</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sr.</th>
                            @foreach ($relColumns as $col)
                                <th>{{ $col['label'] }}@if (!empty($col['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</th>
                            @endforeach
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="table-{{ $sectionKey }}_relatives" data-employment-relatives-table>
                        @foreach ($relRecords as $ri => $row)
                            <tr>
                                <td class="row-num">{{ $ri + 1 }}</td>
                                @foreach ($relColumns as $col)
                                    <td>
                                        <input type="{{ $col['type'] ?? 'text' }}"
                                            data-col="{{ $col['key'] }}"
                                            class="form-control form-control-sm {{ ($hasRelatives && !empty($col['required'])) ? 'required-field' : '' }}"
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
            <template id="tpl-row-{{ $sectionKey }}_relatives">
                <tr>
                    <td class="row-num">1</td>
                    @foreach ($relColumns as $col)
                        <td>
                            <input type="{{ $col['type'] ?? 'text' }}"
                                data-col="{{ $col['key'] }}"
                                class="form-control form-control-sm"
                                {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                        </td>
                    @endforeach
                    <td><button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button></td>
                </tr>
            </template>
            </div>
        </div>
    @endif
</div>
