@php
    use App\Support\GratuityNominationPrefill;

    $sectionKey = $section['key'] ?? GratuityNominationPrefill::SECTION_KEY;
    $saved = is_array($sectionValues ?? null) ? $sectionValues : [];
    $joiningDetails = $savedJoiningDetails ?? [];
    if (isset($employee) && empty($joiningDetails)) {
        $joiningDetails = ($employee->emp_joining_requirements ?? [])['joining_details'] ?? [];
    }
    $vals = GratuityNominationPrefill::employeeDefaults($employee, $saved, $joiningDetails);
    $nomineeRows = GratuityNominationPrefill::normalizeNominees($saved);
    $nomineeColumns = GratuityNominationPrefill::nomineeColumns();
    $readOnly = !empty($readOnlyMode);
    $employeeGroup = $section['employee_group'] ?? [];
    $nomineeGroup = $section['nominee_group'] ?? [];
    $declaration = $section['declaration'] ?? [];
    $declarationText = $declaration['text'] ?? '';
    $confirmField = $declaration['confirm_field'] ?? 'gratuity_declaration_confirm';
    $confirmLabel = $declaration['confirm_label'] ?? 'I confirm the above nomination.';
    $gratuityDataAttr = 'data-gratuity-section="' . e($sectionKey) . '"';
@endphp

<div class="gratuity-nomination-block" data-gratuity-section="{{ $sectionKey }}">
    <h6 class="text-secondary border-bottom pb-2 mb-3">{{ $employeeGroup['title'] ?? 'Employee Information' }}</h6>
    @if (!empty($employeeGroup['intro']))
        <p class="small text-muted mb-3">{{ $employeeGroup['intro'] }}</p>
    @endif
    <div class="row g-2 mb-4">
        @foreach ($employeeGroup['fields'] ?? [] as $field)
            @include('frontend.onboarding.partials.joining-custom-field', [
                'field' => $field,
                'values' => $vals,
                'readOnly' => $readOnly,
                'inputClass' => 'form-control gratuity-field',
                'dataSectionAttr' => $gratuityDataAttr,
                'readonlyBg' => 'bg-light',
            ])
        @endforeach
    </div>

    <h6 class="text-secondary border-bottom pb-2 mb-3">{{ $nomineeGroup['title'] ?? 'Nominee Information' }}</h6>
    @if (!empty($nomineeGroup['intro']))
        <p class="small text-muted mb-3">{{ $nomineeGroup['intro'] }}</p>
    @endif

    @if ($readOnly)
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        @foreach ($nomineeColumns as $col)
                            <th>{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nomineeRows as $ri => $row)
                        @if (!array_filter($row, fn ($v) => trim((string) $v) !== '')) @continue @endif
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            @foreach ($nomineeColumns as $col)
                                @php $cell = $row[$col['key']] ?? ''; @endphp
                                <td>
                                    @if ($col['key'] === 'gratuity_share_percentage' && $cell !== '')
                                        {{ $cell }}%
                                    @else
                                        {{ $cell ?: '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-success btn-sm btn-add-gratuity-nominee">+ Add Nominee</button>
        </div>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        @foreach ($nomineeColumns as $col)
                            <th>{{ $col['label'] }}@if (!empty($col['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody id="table-gratuity-nominees">
                    @foreach ($nomineeRows as $ri => $row)
                        <tr>
                            <td class="row-num">{{ $ri + 1 }}</td>
                            @foreach ($nomineeColumns as $col)
                                @php
                                    $colKey = $col['key'];
                                    $inputType = $col['type'] ?? 'text';
                                    $cellVal = $row[$colKey] ?? '';
                                    $isTextarea = $inputType === 'textarea';
                                @endphp
                                <td>
                                    @if ($isTextarea)
                                        <textarea class="form-control form-control-sm gratuity-nominee-field{{ !empty($col['required']) ? ' required-field' : '' }}" data-key="{{ $colKey }}" rows="2"{{ !empty($col['required']) ? ' data-required=1' : '' }}>{{ $cellVal }}</textarea>
                                    @else
                                        <input type="{{ $inputType === 'number' ? 'number' : 'text' }}"
                                            class="form-control form-control-sm gratuity-nominee-field{{ !empty($col['required']) ? ' required-field' : '' }}"
                                            data-key="{{ $colKey }}"
                                            value="{{ $cellVal }}"
                                            {{ !empty($col['required']) ? 'data-required=1' : '' }}
                                            @if ($colKey === 'nominee_age') min="1" max="120" @endif
                                            @if ($colKey === 'gratuity_share_percentage') min="1" max="100" step="0.01" @endif>
                                    @endif
                                </td>
                            @endforeach
                            <td><button type="button" class="btn btn-danger btn-sm btn-remove-gratuity-nominee">Remove</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h6 class="text-secondary border-bottom pb-2 mb-3">Declaration</h6>
    <div class="alert alert-light border small mb-3">{{ $declarationText }}</div>

    @if ($readOnly)
        @if (!empty($saved[$confirmField]) || !empty($saved['nomination_submitted']))
            <span class="badge bg-success">Nomination submitted</span>
        @endif
    @else
        <div class="form-check mb-2">
            <input class="form-check-input gratuity-field" type="checkbox" name="{{ $confirmField }}" value="1"
                id="gratuityDeclarationConfirm" {!! $gratuityDataAttr !!}
                {{ !empty($saved[$confirmField]) ? 'checked' : '' }}>
            <label class="form-check-label" for="gratuityDeclarationConfirm">{{ $confirmLabel }}</label>
        </div>
        <input type="hidden" name="nomination_submitted" value="" class="gratuity-field gratuity-nomination-flag" {!! $gratuityDataAttr !!}>
    @endif
</div>

@if (!$readOnly)
<script>
(function () {
    const columns = @json($nomineeColumns);

    function bindGratuityNomineeRemove() {
        document.querySelectorAll('.btn-remove-gratuity-nominee').forEach(btn => {
            btn.onclick = function () {
                const tr = this.closest('tr');
                const tbody = document.getElementById('table-gratuity-nominees');
                if (!tbody || tbody.querySelectorAll('tr').length <= 1) return;
                tr.remove();
                tbody.querySelectorAll('tr').forEach((r, i) => {
                    const num = r.querySelector('.row-num');
                    if (num) num.textContent = i + 1;
                });
            };
        });
    }

    document.querySelector('.btn-add-gratuity-nominee')?.addEventListener('click', () => {
        const tbody = document.getElementById('table-gratuity-nominees');
        if (!tbody) return;
        const ri = tbody.querySelectorAll('tr').length;
        let html = '<tr><td class="row-num">' + (ri + 1) + '</td>';
        columns.forEach(col => {
            const reqCls = col.required ? ' required-field' : '';
            const reqAttr = col.required ? ' data-required=1' : '';
            const isTextarea = col.type === 'textarea';
            if (isTextarea) {
                html += '<td><textarea class="form-control form-control-sm gratuity-nominee-field' + reqCls + '" data-key="' + col.key + '" rows="2"' + reqAttr + '></textarea></td>';
            } else {
                let attrs = reqAttr;
                if (col.key === 'nominee_age') attrs += ' min="1" max="120"';
                if (col.key === 'gratuity_share_percentage') attrs += ' min="1" max="100" step="0.01"';
                html += '<td><input type="' + (col.type || 'text') + '" class="form-control form-control-sm gratuity-nominee-field' + reqCls + '" data-key="' + col.key + '"' + attrs + '></td>';
            }
        });
        html += '<td><button type="button" class="btn btn-danger btn-sm btn-remove-gratuity-nominee">Remove</button></td></tr>';
        tbody.insertAdjacentHTML('beforeend', html);
        bindGratuityNomineeRemove();
    });

    bindGratuityNomineeRemove();
})();
</script>
@endif
