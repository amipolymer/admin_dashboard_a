@php
    use App\Support\OnboardingHrDisplay;
    use App\Support\OnGridProfilePayload;
    $info = OnboardingHrDisplay::profileInformation($employee);
    $basic = $info['basic_information'] ?? [];
    $address = $info['address_details'] ?? [];
    $education = $info['education_qualification'] ?? [];
    $family = $info['family_details'] ?? [];
    $familyMembers = OnboardingHrDisplay::familyMemberRows(is_array($family) ? $family : []);
    $employment = $info['previous_employment'] ?? [];
    $industryRelatives = OnboardingHrDisplay::industryRelativeRows(is_array($employment) ? $employment : []);
    $declaration = $info['declaration'] ?? [];
    $isFresherProfile = $employee->isFresher() || (($employment['employment_type'] ?? '') === 'fresher');
    $empRecords = OnGridProfilePayload::employmentRecords(is_array($employment) ? $employment : []);
    $empCols = OnboardingHrDisplay::tableColumns($empRecords);
    $savedAt = ($employee->emp_profile_data ?? [])['saved_at'] ?? null;
@endphp

<div class="profile-readonly">
    @if ($savedAt)
        <p class="small text-muted mb-3">Submitted {{ \Carbon\Carbon::parse($savedAt)->format('d M Y, h:i A') }}</p>
    @endif

    <div class="accordion" id="profileReadonlyAccordion">
        {{-- 1. Basic --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#proRoBasic" aria-expanded="true">
                    1. Basic Information
                </button>
            </h2>
            <div id="proRoBasic" class="accordion-collapse collapse show" data-bs-parent="#profileReadonlyAccordion">
                <div class="accordion-body pt-2">
                    @include('pages.new-join-employee.partials.hr-data-table', [
                        'rows' => OnboardingHrDisplay::basicRows($employee, $basic),
                    ])
                </div>
            </div>
        </div>

        {{-- 2. Address --}}
        @if (!empty($address))
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#proRoAddress">
                        2. Address Details
                    </button>
                </h2>
                <div id="proRoAddress" class="accordion-collapse collapse" data-bs-parent="#profileReadonlyAccordion">
                    <div class="accordion-body pt-2">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="text-primary small mb-2">Current address</h6>
                                @include('pages.new-join-employee.partials.hr-data-table', [
                                    'rows' => OnboardingHrDisplay::addressBlock($address, 'current', ''),
                                ])
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary small mb-2">Permanent address</h6>
                                @include('pages.new-join-employee.partials.hr-data-table', [
                                    'rows' => OnboardingHrDisplay::addressBlock($address, 'permanent', ''),
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 3. Education --}}
        @if (is_array($education) && count($education) > 0)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#proRoEducation">
                        3. Education Details
                    </button>
                </h2>
                <div id="proRoEducation" class="accordion-collapse collapse" data-bs-parent="#profileReadonlyAccordion">
                    <div class="accordion-body pt-2">
                        @php $eduCols = OnboardingHrDisplay::tableColumns($education); @endphp
                        @if ($eduCols)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>@foreach ($eduCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($education as $row)
                                            @if (is_array($row))
                                                <tr>@foreach ($eduCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- 4. Family --}}
        @if (!empty($family) || count($familyMembers) > 0)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#proRoFamily">
                        4. Family Details
                    </button>
                </h2>
                <div id="proRoFamily" class="accordion-collapse collapse" data-bs-parent="#profileReadonlyAccordion">
                    <div class="accordion-body pt-2">
                        @php $familyHeader = OnboardingHrDisplay::familyHeaderRows(is_array($family) ? $family : []); @endphp
                        @if ($familyHeader)
                            @include('pages.new-join-employee.partials.hr-data-table', ['rows' => $familyHeader])
                        @endif
                        @if (count($familyMembers) > 0)
                            @php $famCols = OnboardingHrDisplay::tableColumns($familyMembers); @endphp
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>@foreach ($famCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($familyMembers as $row)
                                            <tr>@foreach ($famCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- 5. Employment --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#proRoEmployment">
                    5. Previous Employment Details
                    @if ($isFresherProfile)
                        <span class="badge bg-info text-dark ms-2">Fresher</span>
                    @endif
                </button>
            </h2>
            <div id="proRoEmployment" class="accordion-collapse collapse" data-bs-parent="#profileReadonlyAccordion">
                <div class="accordion-body pt-2">
                    @if ($isFresherProfile)
                        <p class="small text-muted mb-2">No prior employment (fresher).</p>
                        @include('pages.new-join-employee.partials.hr-data-table', [
                            'rows' => array_filter(['Expected CTC' => $employment['expected_ctc'] ?? null]),
                        ])
                    @else
                        @if ($empCols && count($empRecords) > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>@foreach ($empCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($empRecords as $row)
                                            <tr>@foreach ($empCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small">No employment rows recorded.</p>
                        @endif
                        @php $empFooter = OnboardingHrDisplay::employmentFooterRows(is_array($employment) ? $employment : []); @endphp
                        @if ($empFooter)
                            @include('pages.new-join-employee.partials.hr-data-table', ['rows' => $empFooter])
                        @endif
                        @if (count($industryRelatives) > 0)
                            <h6 class="text-primary small mt-3 mb-2">Relatives in industry</h6>
                            @php $relCols = OnboardingHrDisplay::tableColumns($industryRelatives); @endphp
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>@foreach ($relCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($industryRelatives as $row)
                                            <tr>@foreach ($relCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- 6. Declaration --}}
        @if (!empty($declaration))
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#proRoDeclaration">
                        6. Declaration
                    </button>
                </h2>
                <div id="proRoDeclaration" class="accordion-collapse collapse" data-bs-parent="#profileReadonlyAccordion">
                    <div class="accordion-body pt-2">
                        <p class="small text-muted mb-3" style="white-space: pre-line;">I declare that the information given on all parts of this application form, and in CV which accompanies it, is to the best of my knowledge, correct. I understand that giving false information will make my application unacceptable and in future if any of the information specified in the application form and CV found to be false, the company can take the required action against me.

I acknowledge that providing false information may render my application invalid. Furthermore, if any information provided in the application form or CV is found to be false in the future, the company reserves the right to take appropriate action. The company is authorized to conduct background verification on all information provided.</p>
                        @include('pages.new-join-employee.partials.hr-data-table', [
                            'rows' => OnboardingHrDisplay::declarationRows($declaration),
                        ])
                    </div>
                </div>
            </div>
        @endif
    </div>

    <p class="small text-muted mt-3 mb-0">
        <i class="bi bi-lock"></i> Profile is locked. Contact HR if you need to change anything.
    </p>
</div>

<style>
    .profile-readonly .accordion-button { font-size: 0.95rem; font-weight: 600; color: #034ea1; }
    .profile-readonly .accordion-button:not(.collapsed) { background: #f0f6fc; }
    .profile-readonly .hr-data-kv th { font-weight: 500; }
</style>
