@php
    use App\Support\CandidateSignature;
    $compact = !empty($compact);
    $sigHeight = $compact ? 32 : 50;
    $sigWidth = $compact ? 110 : 150;
    $candidateName = $candidateName ?? '';
    $signatureSrc = isset($employee)
        ? CandidateSignature::preview($signatureSrc ?? null, !empty($signatureAsUrl), $employee->id ?? null)
        : CandidateSignature::preview($signatureSrc ?? null, !empty($signatureAsUrl));
@endphp
<table class="candidate-accept-table" cellpadding="0" cellspacing="0" align="right">
    <tr>
        <td align="right" class="candidate-accept-label"><strong>Candidate Acceptance</strong></td>
    </tr>
    <tr>
        <td align="right" class="candidate-accept-sig">
            @if (!empty($signatureSrc))
                <img src="{{ $signatureSrc }}" alt="Candidate Signature" height="{{ $sigHeight }}" width="{{ $sigWidth }}">
            @else
                <span class="sign-line">_________________________</span>
            @endif
        </td>
    </tr>
    <tr>
        <td align="right" class="candidate-accept-name"><strong>{{ $candidateName }}</strong></td>
    </tr>
</table>
