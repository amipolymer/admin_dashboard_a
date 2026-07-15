@php
    use App\Support\LetterheadAssets;
    $lh = config('letterhead');
    $img = fn (string $file) => LetterheadAssets::imageSrc($file, !empty($signatureAsUrl));
    $letterPdfMode = $letterPdfMode ?? 'offer-single';
    $footerClass = $letterPdfMode === 'appointment-multi' ? 'footer-fixed' : 'footer-offer';
    $showSystemGeneratedNotice = ($letterRenderMode ?? 'pdf') !== 'html';
@endphp
<div class="{{ $footerClass }}">
    <table class="footer-offer-table" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="footer-message">
                Regd. Office: {{ $lh['registered_office'] ?? '' }}
            </td>
        </tr>
        <!-- @if ($showSystemGeneratedNotice)
        <tr>
            <td class="footer-system-notice" align="right">
                This document is system-generated and does not require a signature, stamp, or seal.
            </td>
        </tr>
        @endif -->
        <tr>
            <td class="footer-bottom-cell" style="padding:0;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="50%" class="footer-url" valign="middle">
                            {{ $lh['website'] ?? 'www.amipolymer.com' }}
                        </td>
                        <td width="50%" class="footer-graphic" valign="bottom" align="right">
                            <img src="{{ $img('footer-shape.png') }}" alt="" height="45">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
