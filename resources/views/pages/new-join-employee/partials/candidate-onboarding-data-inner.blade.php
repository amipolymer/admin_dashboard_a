@php
    use App\Support\OnboardingHrDisplay;
    $info = OnboardingHrDisplay::profileInformation($employee);
    $basic = $info['basic_information'] ?? [];
    $address = $info['address_details'] ?? [];
    $education = $info['education_qualification'] ?? [];
    $family = $info['family_details'] ?? [];
    $familyMembers = OnboardingHrDisplay::familyMemberRows(is_array($family) ? $family : []);
    $employment = $info['previous_employment'] ?? [];
    $industryRelatives = OnboardingHrDisplay::industryRelativeRows(is_array($employment) ? $employment : []);
    $isFresherProfile = $employee->isFresher() || (($employment['employment_type'] ?? '') === 'fresher');
    $hasBasic = !empty($basic) || !empty($address);
    $hasEducation = is_array($education) && count($education) > 0;
    $hasEmployment = $isFresherProfile
        ? $employee->isFresher()
        : (is_array($employment) && count(\App\Support\OnGridProfilePayload::employmentRecords($employment)) > 0);
    $hasJoin = !empty($employee->emp_joining_requirements) || $employee->emp_joining_date;
@endphp

<div class="card-body py-2 p-0">
    @if (!$hasBasic && !$hasEducation && !$hasEmployment && !$hasJoin && !$employee->emp_policy_accepted_at)
        <p class="text-muted small px-3 py-3 mb-0">No portal data submitted yet.</p>
    @else
        {{-- Basic + address --}}
        @if ($hasBasic)
            <div class="border-bottom">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center hr-collapse-toggle"
                     data-toggle="collapse" data-target="#hrDataBasic" aria-expanded="false">
                    <strong class="small mb-0"><i class="dw dw-user1 text-muted"></i> Basic &amp; Address</strong>
                    <small class="text-muted collapse-hint">Show</small>
                </div>
                <div id="hrDataBasic" class="collapse">
                    <div class="px-3 pb-3 pt-2">
                        @include('pages.new-join-employee.partials.hr-data-table', [
                            'rows' => OnboardingHrDisplay::basicRows($employee, $basic),
                        ])
                        @if (!empty($address))
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <h6 class="text-primary small mb-1">Current address</h6>
                                    @include('pages.new-join-employee.partials.hr-data-table', [
                                        'rows' => OnboardingHrDisplay::addressBlock($address, 'current', ''),
                                    ])
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary small mb-1">Permanent address</h6>
                                    @include('pages.new-join-employee.partials.hr-data-table', [
                                        'rows' => OnboardingHrDisplay::addressBlock($address, 'permanent', ''),
                                    ])
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Education --}}
        @if ($hasEducation)
            <div class="border-bottom">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center hr-collapse-toggle"
                     data-toggle="collapse" data-target="#hrDataEducation" aria-expanded="false">
                    <strong class="small mb-0"><i class="dw dw-mortarboard text-muted"></i> Education ({{ count($education) }})</strong>
                    <small class="text-muted collapse-hint">Show</small>
                </div>
                <div id="hrDataEducation" class="collapse">
                    <div class="px-3 pb-3 pt-2">
                        @php $eduCols = OnboardingHrDisplay::tableColumns($education); @endphp
                        @if ($eduCols)
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light">
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
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Family --}}
        @if (!empty($family) || count($familyMembers) > 0)
            <div class="border-bottom">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center hr-collapse-toggle"
                     data-toggle="collapse" data-target="#hrDataFamily" aria-expanded="false">
                    <strong class="small mb-0"><i class="dw dw-user1 text-muted"></i> Family ({{ count($familyMembers) + (!empty($family['father_name']) ? 1 : 0) }})</strong>
                    <small class="text-muted collapse-hint">Show</small>
                </div>
                <div id="hrDataFamily" class="collapse">
                    <div class="px-3 pb-3 pt-2">
                        @php $familyHeader = OnboardingHrDisplay::familyHeaderRows(is_array($family) ? $family : []); @endphp
                        @if ($familyHeader)
                            @include('pages.new-join-employee.partials.hr-data-table', ['rows' => $familyHeader])
                        @endif
                        @if (count($familyMembers) > 0)
                            @php $famCols = OnboardingHrDisplay::tableColumns($familyMembers); @endphp
                            <table class="table table-sm table-bordered mb-0 mt-2">
                                <thead class="thead-light">
                                    <tr>@foreach ($famCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                </thead>
                                <tbody>
                                    @foreach ($familyMembers as $row)
                                        <tr>@foreach ($famCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Employment --}}
        @if ($hasEmployment)
            <div class="border-bottom">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center hr-collapse-toggle"
                     data-toggle="collapse" data-target="#hrDataEmployment" aria-expanded="false">
                    <strong class="small mb-0"><i class="dw dw-briefcase text-muted"></i>
                        @if ($isFresherProfile)
                            Employment (Fresher)
                        @else
                            Previous employment ({{ count(\App\Support\OnGridProfilePayload::employmentRecords($employment)) }})
                        @endif
                    </strong>
                    <small class="text-muted collapse-hint">Show</small>
                </div>
                <div id="hrDataEmployment" class="collapse">
                    <div class="px-3 pb-3 pt-2">
                        @if ($isFresherProfile)
                            <p class="small mb-2"><span class="badge badge-info">Fresher</span> No prior employment on file.</p>
                            @if (trim((string) ($employment['expected_ctc'] ?? '')) !== '')
                                @include('pages.new-join-employee.partials.hr-data-table', [
                                    'rows' => ['Expected CTC' => $employment['expected_ctc']],
                                ])
                            @else
                                <p class="text-muted small mb-0">Awaiting expected CTC from candidate.</p>
                            @endif
                        @else
                        @php
                            $empRecords = \App\Support\OnGridProfilePayload::employmentRecords(is_array($employment) ? $employment : []);
                            $empCols = OnboardingHrDisplay::tableColumns($empRecords);
                        @endphp
                        @if ($empCols)
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>@foreach ($empCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                </thead>
                                <tbody>
                                    @foreach ($empRecords as $row)
                                        <tr>@foreach ($empCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                        @php $empFooter = OnboardingHrDisplay::employmentFooterRows(is_array($employment) ? $employment : []); @endphp
                        @if ($empFooter)
                            <div class="mt-2">@include('pages.new-join-employee.partials.hr-data-table', ['rows' => $empFooter])</div>
                        @endif
                        @if (count($industryRelatives) > 0)
                            <h6 class="text-primary small mt-3 mb-1">Relatives in industry</h6>
                            @php $relCols = OnboardingHrDisplay::tableColumns($industryRelatives); @endphp
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>@foreach ($relCols as $col)<th>{{ OnboardingHrDisplay::label($col) }}</th>@endforeach</tr>
                                </thead>
                                <tbody>
                                    @foreach ($industryRelatives as $row)
                                        <tr>@foreach ($relCols as $col)<td>{{ $row[$col] ?? '—' }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Joining forms --}}
        @if ($hasJoin)
            <div class="border-bottom">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center hr-collapse-toggle"
                     data-toggle="collapse" data-target="#hrDataJoining" aria-expanded="false">
                    <strong class="small mb-0"><i class="dw dw-calendar1 text-muted"></i> Joining details</strong>
                    <small class="text-muted collapse-hint">Show</small>
                </div>
                <div id="hrDataJoining" class="collapse">
                    <div class="px-3 pb-3 pt-2 small">
                        @php $join = $employee->emp_joining_requirements ?? []; @endphp
                        @if ($employee->emp_joining_date)
                            <p class="mb-2"><strong>Join date:</strong> {{ $employee->emp_joining_date->format('d M Y') }}
                                @if ($employee->joinDateIsToday())<span class="badge badge-success">Today</span>@endif
                            </p>
                        @endif
                        @foreach ($join as $sectionKey => $sectionData)
                            @if (!is_array($sectionData)) @continue @endif
                            <h6 class="text-primary mt-2 mb-1">{{ ucfirst(str_replace('_', ' ', $sectionKey)) }}</h6>
                            @if (isset($sectionData[0]) && is_array($sectionData[0]))
                                <table class="table table-sm table-bordered mb-2">
                                    <thead><tr>@foreach (array_keys($sectionData[0]) as $k)<th>{{ ucfirst(str_replace('_',' ',$k)) }}</th>@endforeach</tr></thead>
                                    <tbody>
                                        @foreach ($sectionData as $row)
                                            <tr>@foreach ($row as $v)<td>{{ $v ?: '—' }}</td>@endforeach</tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                @include('pages.new-join-employee.partials.hr-data-table', [
                                    'rows' => collect($sectionData)->mapWithKeys(fn ($v, $k) => is_array($v) ? [] : [ucfirst(str_replace('_', ' ', $k)) => $v ?: null])->filter()->all(),
                                ])
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Policy --}}
        @if ($employee->emp_policy_accepted_at)
            <div class="px-3 py-2 small">
                <strong>Company policy:</strong> Accepted {{ $employee->emp_policy_accepted_at->format('d M Y H:i') }}
            </div>
        @endif
    @endif
</div>
