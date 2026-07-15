@php
    use App\Support\Form25LeaveNomination;

    $sectionKey = $section['key'] ?? Form25LeaveNomination::SECTION_KEY;
    $saved = is_array($sectionValues ?? null) ? $sectionValues : [];
    $joiningDetails = $savedJoiningDetails ?? [];
    if (isset($employee) && empty($joiningDetails)) {
        $joiningDetails = ($employee->emp_joining_requirements ?? [])['joining_details'] ?? [];
    }
    $vals = Form25LeaveNomination::defaults($employee, $saved, $joiningDetails);
    $readOnly = !empty($readOnlyMode);
    $groups = $section['groups'] ?? [];
    $dataSectionAttr = 'data-form25-section="' . e($sectionKey) . '"';
@endphp

<div class="form25-leave-nomination-block" data-form25-section="{{ $sectionKey }}">
    @foreach ($groups as $group)
        @php
            $wrap = ($group['wrap'] ?? '') === 'card';
            $hasTitle = !empty($group['title']);
        @endphp
        @if ($wrap)
            <div class="border rounded p-3 mb-3 bg-light">
                @if ($hasTitle)
                    <h6 class="text-secondary mb-3">{{ $group['title'] }}</h6>
                @endif
        @elseif ($hasTitle)
            <h6 class="text-secondary border-bottom pb-2 mb-3">{{ $group['title'] }}</h6>
        @endif

        @if (!empty($group['fields']))
            <div class="row g-2 {{ $wrap ? '' : 'mb-2' }}">
                @foreach ($group['fields'] as $field)
                    @include('frontend.onboarding.partials.joining-custom-field', [
                        'field' => $field,
                        'values' => $vals,
                        'readOnly' => $readOnly,
                        'inputClass' => 'form-control form-control-sm form25-field',
                        'fieldClassSuffix' => '',
                        'dataSectionAttr' => $dataSectionAttr,
                        'readonlyBg' => $wrap ? 'bg-white' : '',
                    ])
                @endforeach
            </div>
        @endif

        @foreach ($group['rows'] ?? [] as $row)
            <div class="row g-2 {{ !$loop->last ? 'mb-2' : '' }}">
                @foreach ($row['fields'] ?? [] as $field)
                    @include('frontend.onboarding.partials.joining-custom-field', [
                        'field' => $field,
                        'values' => $vals,
                        'readOnly' => $readOnly,
                        'inputClass' => 'form-control form-control-sm form25-field',
                        'fieldClassSuffix' => '',
                        'dataSectionAttr' => $dataSectionAttr,
                        'readonlyBg' => 'bg-white',
                    ])
                @endforeach
            </div>
        @endforeach

        @if ($wrap)
            </div>
        @endif
    @endforeach
</div>
