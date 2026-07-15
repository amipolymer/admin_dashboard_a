@php
    $section = $familySection ?? [];
    $sectionKey = $section['key'] ?? 'family_details';
    $sectionValues = $savedFamily ?? [];
    $headerFields = $section['header_fields'] ?? [];
    $columns = $section['columns'] ?? [];
    $members = $sectionValues['members'] ?? [];
    if (empty($members) && is_array($sectionValues) && isset($sectionValues[0]) && is_array($sectionValues[0])) {
        $members = $sectionValues;
    }
    $fatherName = trim((string) ($sectionValues['father_name'] ?? ''));
    if (empty($members) || (count($members) === 1 && !array_filter($members[0] ?? []))) {
        $members = [
            array_filter(['name' => $fatherName, 'relation' => 'Father']),
            ['relation' => 'Mother'],
        ];
    } elseif (count($members) < 2) {
        $members[] = ['relation' => count($members) === 0 || empty($members[0]['relation'] ?? '') ? 'Father' : 'Mother'];
    }
    foreach ($members as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($fatherName !== '' && $i === 0 && trim((string) ($row['name'] ?? '')) === '' && strtolower(trim((string) ($row['relation'] ?? ''))) === 'father') {
            $members[$i]['name'] = $fatherName;
        }
        if (trim((string) ($row['relation'] ?? '')) === '' && $i === 0) {
            $members[$i]['relation'] = 'Father';
        }
        if (trim((string) ($row['relation'] ?? '')) === '' && $i === 1) {
            $members[$i]['relation'] = 'Mother';
        }
    }
@endphp

<div data-family-section>
    <div class="address-box mb-4" data-family-header>
        <div class="row">
            @foreach ($headerFields as $fKey => $field)
                @php
                    $isWaiver = ($fKey === 'no_father_mother');
                    $wrapperClass = trim(($field['col'] ?? 'col-md-6 mb-3') . ' '
                        . (!empty($field['show_when_married']) ? 'family-married-only' : '')
                        . ($isWaiver ? ' family-waiver-field' : ''));
                    $fieldCopy = $field;
                    $fieldCopy['col'] = $wrapperClass;
                @endphp
                @include('frontend.onboarding.partials.profile-field', [
                    'field' => $fieldCopy,
                    'fieldKey' => $fKey,
                    'values' => $sectionValues,
                ])
            @endforeach
        </div>
        <p class="small text-muted mb-0" id="familyMemberCount">Complete family members: <strong>0</strong> (minimum 2 — Father and Mother required)</p>
    </div>

    <div class="family-members-block" data-family-members-block>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="text-primary mb-0">{{ $section['table_title'] ?? 'Family members' }}</h5>
        <button type="button" class="btn btn-success btn-sm btn-add-row" data-table="{{ $sectionKey }}">+ Add member</button>
    </div>
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
            <tbody id="table-{{ $sectionKey }}" data-family-table>
                @foreach ($members as $ri => $row)
                    <tr>
                        <td class="row-num">{{ $ri + 1 }}</td>
                        @foreach ($columns as $col)
                            <td>
                                <input type="{{ $col['type'] ?? 'text' }}"
                                    data-col="{{ $col['key'] }}"
                                    class="form-control form-control-sm {{ !empty($col['required']) ? 'required-field' : '' }}"
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
    </div>
    <template id="tpl-row-{{ $sectionKey }}">
        <tr>
            <td class="row-num">1</td>
            @foreach ($columns as $col)
                <td>
                    <input type="{{ $col['type'] ?? 'text' }}"
                        data-col="{{ $col['key'] }}"
                        class="form-control form-control-sm {{ !empty($col['required']) ? 'required-field' : '' }}"
                        {{ !empty($col['required']) ? 'data-required=1' : '' }}>
                </td>
            @endforeach
            <td><button type="button" class="btn btn-danger btn-sm btn-remove-row">Remove</button></td>
        </tr>
    </template>
</div>
