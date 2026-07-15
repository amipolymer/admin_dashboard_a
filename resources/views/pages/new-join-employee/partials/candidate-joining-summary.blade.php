@php
    $join = $employee->emp_joining_requirements ?? [];
    $hasJoin = !empty($join);
@endphp
@if ($hasJoin || $employee->emp_joining_date)
<div class="card mb-4">
    <div class="card-header bg-light"><strong>Candidate Joining Details</strong></div>
    <div class="card-body small">
        @if ($employee->emp_joining_date)
            <p><strong>Join Date:</strong> {{ $employee->emp_joining_date->format('d M Y') }}
                @if ($employee->joinDateIsToday())<span class="badge badge-success">Today</span>@endif
            </p>
        @endif
        @if ($employee->emp_policy_accepted_at)
            <p><strong>Policy:</strong> Accepted {{ $employee->emp_policy_accepted_at->format('d M Y') }}</p>
        @endif
        @foreach ($join as $sectionKey => $sectionData)
            @if (!is_array($sectionData)) @continue @endif
            <h6 class="text-primary mt-2">{{ ucfirst(str_replace('_', ' ', $sectionKey)) }}</h6>
            @if (isset($sectionData[0]) && is_array($sectionData[0]))
                <table class="table table-sm table-bordered">
                    <thead><tr>@foreach (array_keys($sectionData[0]) as $k)<th>{{ ucfirst(str_replace('_',' ',$k)) }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($sectionData as $row)
                            <tr>@foreach ($row as $v)<td>{{ $v ?: '-' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                @foreach ($sectionData as $k => $v)
                    @if ($v && !is_array($v))
                        <p class="mb-1"><strong>{{ ucfirst(str_replace('_', ' ', $k)) }}:</strong> {{ $v }}</p>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>
</div>
@endif
