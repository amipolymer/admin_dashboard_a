@php
    $ftype = $field['type'] ?? 'text';
    $fname = $field['name'] ?? $fieldKey;
    $value = $values[$fname] ?? old($fname, $field['default'] ?? '');
    $otherField = $field['other_field'] ?? ($fname . '_other');
    $otherValue = $values[$otherField] ?? old($otherField, '');
    $req = !empty($field['required']) ? 'required' : '';
    $reqClass = !empty($field['required']) ? 'required-field' : '';
    $extraClass = trim(($field['class'] ?? 'form-control') . ' ' . $reqClass);
    $isOtherCity = $value === '__OTHER__' || ($value === '' && $otherValue !== '');
    $fieldLabel = str_replace(
        '{company_name}',
        config('letterhead.company_name', config('app.name')),
        $field['label'] ?? $fname
    );
    $digitsOnly = !empty($field['digits_only']);
    $wrapperClass = trim(($field['col'] ?? 'col-md-6 mb-3') . ' ' . (!empty($field['show_when_yes']) ? 'company-contact-field-wrap' : ''));
    if (!empty($field['show_when_yes']) && ($values['has_company_contacts'] ?? '') !== 'Yes') {
        $wrapperClass .= ' d-none';
    }
@endphp
@if ($ftype === 'phone')
@php
    $codeName = $field['code_name'] ?? 'phone_country_code';
    $numberName = $field['number_name'] ?? 'phone';
    $codeVal = $values[$codeName] ?? old($codeName, $field['code_default'] ?? '+91');
    $numberVal = $values[$numberName] ?? old($numberName, '');
@endphp
<div class="{{ $wrapperClass }}">
    <label class="form-label">{{ $fieldLabel ?? 'Mobile Number' }}@if (!empty($field['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</label>
    <div class="phone-field-group" data-phone-group>
        <input type="text" name="{{ $codeName }}" class="phone-code-input {{ $reqClass }}"
            value="{{ $codeVal }}" placeholder="+91" maxlength="8"
            autocomplete="tel-country-code" inputmode="tel"
            title="Country code — type manually, e.g. +91"
            aria-label="Country code">
        <input type="tel" name="{{ $numberName }}" class="phone-number-input {{ $reqClass }}"
            value="{{ $numberVal }}" placeholder="{{ $field['number_placeholder'] ?? 'Mobile number' }}"
            maxlength="{{ $field['maxlength'] ?? 15 }}"
            autocomplete="tel-national" inputmode="numeric"
            aria-label="Mobile number">
    </div>
</div>
@elseif ($ftype === 'state_select')
<div class="{{ $wrapperClass }}">
    <label class="form-label">{{ $fieldLabel }}@if (!empty($field['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</label>
    <select name="{{ $fname }}" class="{{ $extraClass }} state-select-field" data-state-field="{{ $fname }}" {{ $req }}>
        <option value="">Select state</option>
        @foreach (\App\Data\IndianCities::states() as $stateName)
            <option value="{{ $stateName }}" {{ (string) $value === (string) $stateName ? 'selected' : '' }}>{{ $stateName }}</option>
        @endforeach
    </select>
</div>
@elseif ($ftype === 'city_select')
@php
    $stateField = $field['state_field'] ?? 'state';
    $savedCity = $value;
    if ($savedCity !== '' && $savedCity !== '__OTHER__') {
        $cityInList = false;
        $stateForCity = $values[$stateField] ?? '';
        foreach (\App\Data\IndianCities::citiesByState()[$stateForCity] ?? [] as $c) {
            if ((string) $c === (string) $savedCity) {
                $cityInList = true;
                break;
            }
        }
        if (!$cityInList && $savedCity !== '') {
            $otherValue = $savedCity;
            $savedCity = '__OTHER__';
        }
    }
@endphp
<div class="{{ $wrapperClass }}">
    <label class="form-label">{{ $fieldLabel }}@if (!empty($field['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</label>
    <select name="{{ $fname }}" class="{{ $extraClass }} city-select-field"
        data-city-field="{{ $fname }}"
        data-state-field="{{ $stateField }}"
        data-other-field="{{ $otherField }}"
        data-saved-city="{{ $savedCity === '__OTHER__' ? '' : $savedCity }}"
        {{ $req }}>
        <option value="">Select city</option>
        <option value="__OTHER__" {{ $savedCity === '__OTHER__' ? 'selected' : '' }}>Other (enter manually)</option>
    </select>
    <input type="text" name="{{ $otherField }}" class="form-control mt-2 city-other-input {{ $isOtherCity ? '' : 'd-none' }}"
        value="{{ $otherValue }}" placeholder="Enter city name" data-other-for="{{ $fname }}">
</div>
@else
<div class="{{ $wrapperClass }}">
    @if ($ftype === 'checkbox')
        <div class="form-check mb-3">
            <input type="checkbox" name="{{ $fname }}" value="1" id="fld_{{ $fname }}"
                class="{{ $extraClass }}" {{ $value ? 'checked' : '' }} {{ $req }}>
            <label class="form-check-label" for="fld_{{ $fname }}">{{ $fieldLabel }}@if (!empty($field['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</label>
        </div>
    @else
        <label class="form-label">{{ $fieldLabel }}@if (!empty($field['required'])) @include('frontend.onboarding.partials.required-asterisk') @endif</label>
        @if (!empty($field['show_when_yes']) && ($field['name'] ?? '') === 'company_contact_name')
        @endif
        @if (($field['type'] ?? '') === 'select')
            <select name="{{ $fname }}" class="{{ $extraClass }}" {{ $req }}>
                @foreach ($field['options'] ?? [] as $opt)
                    <option value="{{ $opt }}" {{ (string) $value === (string) $opt ? 'selected' : '' }}>{{ $opt === '' ? 'Select' : $opt }}</option>
                @endforeach
            </select>
        @elseif (($field['type'] ?? '') === 'textarea')
            <textarea name="{{ $fname }}" class="{{ $extraClass }}" rows="{{ $field['rows'] ?? 3 }}"
                placeholder="{{ $field['placeholder'] ?? '' }}" {{ !empty($field['readonly']) ? 'readonly' : '' }} {{ $req }}>{{ $value }}</textarea>
        @else
            <input type="{{ $field['type'] ?? 'text' }}" name="{{ $fname }}" class="{{ $extraClass }}"
                value="{{ $value }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                maxlength="{{ $field['maxlength'] ?? '' }}" {{ $req }}
                @if ($digitsOnly) inputmode="numeric" pattern="[0-9]*" data-digits-only="1" @endif
                @if (!empty($field['readonly'])) readonly @endif>
        @endif
    @endif
</div>
@endif
