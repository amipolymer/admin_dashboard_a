@php
    $letterPdfMode = $letterPdfMode ?? 'offer-single';
    $letterRenderMode = $letterRenderMode ?? 'pdf';
@endphp
@include('pdf.partials.ami-letterhead-base')
@if ($letterPdfMode === 'offer-single')
    @if ($letterRenderMode === 'html')
        @include('pdf.partials.offer-letter-html-styles')
    @else
        @include('pdf.partials.offer-letter-pdf-styles')
    @endif
@endif
@if ($letterPdfMode === 'appointment-multi')
    @if ($letterRenderMode === 'html')
        @include('pdf.partials.appointment-letter-html-styles')
    @else
        @include('pdf.partials.appointment-letter-pdf-styles')
    @endif
@endif
