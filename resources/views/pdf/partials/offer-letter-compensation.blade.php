@php
    use App\Support\OfferLetterCompensation;
@endphp
{!! OfferLetterCompensation::letterHtml($offer ?? []) !!}
