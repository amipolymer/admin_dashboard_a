{{-- Legacy include: delegate to full HR portal data panel sections --}}
@php
    use App\Support\OnboardingHrDisplay;
    $info = OnboardingHrDisplay::profileInformation($employee ?? null);
    $basic = $basic ?? ($info['basic_information'] ?? []);
    $address = $address ?? ($info['address_details'] ?? []);
    $education = $education ?? ($info['education_qualification'] ?? []);
    $employment = $employment ?? ($info['previous_employment'] ?? []);
@endphp
@include('pages.new-join-employee.partials.hr-data-table', ['rows' => OnboardingHrDisplay::basicRows($employee, $basic)])
@if (!empty($address))
    <div class="row mt-2">
        <div class="col-md-6">
            <h6 class="text-primary small">Current</h6>
            @include('pages.new-join-employee.partials.hr-data-table', ['rows' => OnboardingHrDisplay::addressBlock($address, 'current', '')])
        </div>
        <div class="col-md-6">
            <h6 class="text-primary small">Permanent</h6>
            @include('pages.new-join-employee.partials.hr-data-table', ['rows' => OnboardingHrDisplay::addressBlock($address, 'permanent', '')])
        </div>
    </div>
@endif
