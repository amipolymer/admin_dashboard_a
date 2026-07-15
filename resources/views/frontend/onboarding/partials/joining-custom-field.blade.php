@php
    $fname = $field['name'] ?? '';
    $flabel = $field['label'] ?? $fname;
    $ftype = $field['type'] ?? 'text';
    $fcol = $field['col'] ?? 'col-md-4 mb-3';
    $freq = !empty($field['required']);
    $fval = $values[$fname] ?? '';
    $inputClass = $inputClass ?? 'form-control form-control-sm';
    $fieldClass = trim($inputClass . ' ' . ($fieldClassSuffix ?? '') . ($freq ? ' required-field' : ''));
    $dataAttr = $dataSectionAttr ?? '';
@endphp
<div class="{{ $fcol }}">
    <label class="form-label small mb-1">{{ $flabel }}@if ($freq) @include('frontend.onboarding.partials.required-asterisk') @endif</label>
    @if (!empty($readOnly))
        <p class="form-control-plaintext border rounded px-2 py-1 mb-0 small {{ $readonlyBg ?? '' }}">{{ $fval ?: '—' }}</p>
    @elseif ($ftype === 'textarea')
        <textarea name="{{ $fname }}" class="{{ $fieldClass }}" rows="{{ $field['rows'] ?? 2 }}"
            {!! $dataAttr !!} @if($freq) required data-required="1" @endif @if(!empty($field['readonly'])) readonly @endif>{{ $fval }}</textarea>
    @elseif ($ftype === 'date')
        <input type="date" name="{{ $fname }}" value="{{ $fval }}" class="{{ $fieldClass }}"
            {!! $dataAttr !!} @if($freq) required data-required="1" @endif @if(!empty($field['readonly'])) readonly @endif>
    @else
        <input type="{{ $ftype === 'number' ? 'number' : 'text' }}" name="{{ $fname }}" value="{{ $fval }}"
            class="{{ $fieldClass }}" {!! $dataAttr !!}
            @if($freq) required data-required="1" @endif
            @if(!empty($field['readonly'])) readonly @endif
            @if(!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif>
    @endif
</div>
