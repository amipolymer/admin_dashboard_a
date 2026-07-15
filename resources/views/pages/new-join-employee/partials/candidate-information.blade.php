@php
    use App\Support\OnboardingProfileDraft;
    $info = ($employee->emp_profile_data ?? [])['information'] ?? [];
    $basic = $info['basic_information'] ?? ($employee->emp_profile_data['step1'] ?? []);
    $address = $info['address_details'] ?? ($employee->emp_profile_data['step2'] ?? []);
    $education = $info['education_qualification'] ?? ($employee->emp_profile_data['education_list'] ?? []);
    $employment = $info['previous_employment'] ?? ($employee->emp_profile_data['employment_list'] ?? []);
    $join = $employee->emp_joining_requirements ?? [];
    $profileSubmitted = OnboardingProfileDraft::isSubmitted($employee);
    $hasDraft = OnboardingProfileDraft::hasDraft($employee);
    $draftLabel = OnboardingProfileDraft::progressLabel($employee);
    $draftSavedAt = OnboardingProfileDraft::savedAt($employee);
    $hasInfo = $profileSubmitted && (!empty($basic) || !empty($address));
    $hasPartial = $hasDraft && !$profileSubmitted;
    $eduCount = is_array($education) ? count($education) : 0;
    $empCount = count(\App\Support\OnGridProfilePayload::employmentRecords(is_array($employment) ? $employment : []));
    $joinDate = optional($employee->emp_joining_date)->format('d M Y');
@endphp

<div class="card mb-3">
    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <button class="btn btn-link btn-sm text-dark text-decoration-none p-0 font-weight-bold" type="button"
            data-toggle="collapse" data-target="#candidateInfoCollapse" aria-expanded="false">
            Candidate summary
            @if ($profileSubmitted)
                <span class="badge badge-success ml-1">Profile submitted</span>
            @elseif ($hasPartial)
                <span class="badge badge-info ml-1">Draft in progress</span>
            @else
                <span class="badge badge-warning ml-1">No profile</span>
            @endif
        </button>
        <span class="small text-muted">
            {{ $basic['name'] ?? $employee->emp_name }}
            · {{ $basic['email'] ?? $employee->emp_email }}
            · {{ $basic['phone'] ?? $employee->emp_phone }}
            @if ($joinDate) · Join {{ $joinDate }} @endif
        </span>
    </div>
    <div id="candidateInfoCollapse" class="collapse">
        <div class="card-body py-2 small">
            @if ($hasPartial)
                <div class="alert alert-info py-2 mb-2">
                    <strong>{{ $draftLabel ?? 'Draft in progress' }}</strong>
                    @if ($draftSavedAt)
                        — last saved {{ \Carbon\Carbon::parse($draftSavedAt)->format('d M Y, h:i A') }}
                    @endif
                    <br>Full profile details are available only after the candidate submits the form.
                </div>
                @if (!empty($basic['name']) || !empty($basic['email']))
                    <p class="mb-0 text-muted">
                        Partial data on file:
                        @if (!empty($basic['name'])) {{ $basic['name'] }} @endif
                        @if (!empty($basic['state']) || !empty($basic['city']))
                            · {{ trim(($basic['city'] ?? '') . ', ' . ($basic['state'] ?? ''), ', ') }}
                        @endif
                    </p>
                @endif
            @elseif (!$hasInfo)
                <p class="text-muted mb-0">No profile submitted in portal yet.</p>
            @else
                <div class="row">
                    <div class="col-md-6">
                        <strong>Basic:</strong>
                        {{ $employee->emp_role ?? '—' }} |
                        {{ $basic['gender'] ?? '—' }} |
                        DOB {{ $basic['dob'] ?? '—' }} |
                        {{ $basic['city'] ?? '—' }}{{ !empty($basic['state']) ? ', ' . $basic['state'] : '' }}
                        @if (!empty($basic['blood_group']))
                            | {{ $basic['blood_group'] }}
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Address:</strong>
                        {{ \Illuminate\Support\Str::limit($address['current_full_address'] ?? $address['permanent_full_address'] ?? '—', 80) }}
                    </div>
                </div>
                <p class="mb-1 mt-1">
                    <strong>Education:</strong> {{ $eduCount }} record(s) |
                    <strong>Employment:</strong> {{ $empCount }} record(s)
                    @if ($employee->emp_policy_accepted_at)
                        | <strong>Policy:</strong> {{ $employee->emp_policy_accepted_at->format('d M Y') }}
                    @endif
                </p>
                @if (!empty($join['joining_details']['confirmed_join_date']))
                    <p class="mb-0"><strong>Join form:</strong> {{ $join['joining_details']['confirmed_join_date'] }}
                        @if (!empty($join['assets_requested'][0]))
                            | Assets: {{ count($join['assets_requested']) }}
                        @endif
                    </p>
                @endif
                <details class="mt-2">
                    <summary class="text-primary" style="cursor:pointer;">Full profile detail</summary>
                    <div class="mt-2">
                        @include('pages.new-join-employee.partials.candidate-information-detail', [
                            'basic' => $basic,
                            'address' => $address,
                            'education' => $education,
                            'employment' => $employment,
                            'employee' => $employee,
                        ])
                    </div>
                </details>
            @endif
        </div>
    </div>
</div>
