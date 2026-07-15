@php
    use App\Support\LetterheadAssets;
    $lh = config('letterhead');
    $img = fn (string $file) => LetterheadAssets::imageSrc($file, !empty($signatureAsUrl));
    $letterPdfMode = $letterPdfMode ?? 'offer-single';
    $watermarkClass = $letterPdfMode === 'appointment-multi' ? 'watermark-fixed' : 'watermark-offer';
    $mfgLines = $lh['manufacturing_unit_lines'] ?? [];
    $corpLines = $lh['corporate_office_lines'] ?? [];
    if ($mfgLines === [] && !empty($lh['manufacturing_unit'])) {
        $mfgLines = [$lh['manufacturing_unit']];
    }
    if ($corpLines === [] && !empty($lh['corporate_office'])) {
        $corpLines = [$lh['corporate_office']];
    }
@endphp
<div class="{{ $watermarkClass }}">
    <table cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" valign="middle">
                <img src="{{ $img('watermark-logo.png') }}" alt="">
            </td>
        </tr>
    </table>
</div>

<div class="letterhead-block{{ $letterPdfMode === 'appointment-multi' ? ' letterhead-fixed' : '' }}">
<div class="header">
    <div class="logo">
        <img src="{{ $img('logo-v1.png') }}" alt="{{ $lh['company_name'] ?? 'AMI Polymer' }}">
    </div>
    <div class="tagline">CIN #{{ $lh['cin'] ?? '' }}</div>
    <div class="header-message">{{ $lh['certification_line'] ?? '' }}</div>
</div>

<div class="office-section">
    <div class="office-column">
        <div class="office-address">
            <p class="office-line"><b>Manufacturing Unit:</b> {{ $mfgLines[0] ?? '' }}</p>
            @if (!empty($mfgLines[1]))
                <p class="office-line">{{ $mfgLines[1] }}</p>
            @endif
            @if (!empty($mfgLines[2]))
                <p class="office-line">{{ $mfgLines[2] }}</p>
            @endif
        </div>
    </div>
    <div class="office-column right">
        <div class="office-address">
            <p class="office-line"><b>Corporate Office:</b> {{ $corpLines[0] ?? '' }}</p>
            @if (!empty($corpLines[1]))
                <p class="office-line">{{ $corpLines[1] }}</p>
            @endif
            @if (!empty($corpLines[2]))
                <p class="office-line">{{ $corpLines[2] }}</p>
            @endif
        </div>
    </div>
    <div class="clear"></div>
</div>

<div class="section-divider"></div>
<div class="section-divider-2"></div>
</div>
