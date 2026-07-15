<style>
    @page { size: A4; margin: 0; }

    html, body {
        width: 210mm;
        margin: 0;
        padding: 0;
    }

    .page-offer {
        width: 210mm;
        height: 297mm;
        max-height: 297mm;
        position: relative;
        page-break-after: avoid;
        page-break-inside: avoid;
        background: #fff;
        overflow: hidden;
    }

    .watermark-offer {
        position: absolute;
        top: 20%;
        left: 0;
        width: 210mm;
        height: 297mm;
        z-index: 0;
        pointer-events: none;
    }
    .watermark-offer table { width: 100%; height: 297mm; border: 0; border-collapse: collapse; }
    .watermark-offer td { text-align: center; vertical-align: middle; }
    .watermark-offer img { width: 600px; opacity: 0.09; }

    .page-offer .header,
    .page-offer .office-section,
    .page-offer .section-divider,
    .page-offer .section-divider-2,
    .page-offer .body-content-offer {
        position: relative;
        z-index: 2;
    }

    .page-offer .header { text-align: center; padding: 6px 30px 0; }
    .page-offer .logo { margin-bottom: 4px; }
    .page-offer .logo img { height: 52px; width: auto; }
    .page-offer .tagline { font-size: 9.5px; font-weight: bold; margin-bottom: 3px; }
    .page-offer .header-message {
        font-size: 8px;
        color: #034ea1;
        font-weight: bold;
        word-spacing: 2px;
        padding-bottom: 2px;
        border-bottom: 1.8px solid #034ea1;
    }
    .page-offer .office-section { padding: 0 30px; overflow: hidden; }
    .page-offer .office-column { width: 49%; float: left; }
    .page-offer .office-column.right { float: right; }
    .page-offer .office-address { font-size: 7.5px; line-height: 1.2; padding-left: 4px; }
    .page-offer .office-address .office-line { margin: 0; line-height: 1.2; }
    .page-offer .clear { clear: both; }
    .page-offer .section-divider { border-top: 1.8px solid #034ea1; margin: 0 30px; }
    .page-offer .section-divider-2 { border-top: 1.8px solid #034ea1; margin: 1px 30px 4px; }

    .body-content-offer {
        padding: 0 30px 58px;
        line-height: 1.28;
        text-align: justify;
        font-size: 12px;
    }
    .body-content-offer p { margin-bottom: 4px; line-height: 1.28; }
    .body-content-offer .mb-2 { margin-bottom: 2px; }
    .body-content-offer .mb-15 { margin-bottom: 4.7px; }
    .body-content-offer .closing-para { margin-top: 4px; margin-bottom: 18px; }

    .doc-list-wrap { margin-left: 28px; margin-bottom: 4px; }
    .body-content-offer .doc-list { margin: 0; padding-left: 14px; }
    .body-content-offer .doc-list li {
        margin-bottom: 0;
        line-height: 1.22;
        font-size: 12px;
    }

    .body-content-offer .signatures-row { margin-top: 2px; }
    .body-content-offer .sign-block { margin-top: 0; font-size: 12px; }
    .body-content-offer .sign-block-compact img { height: 32px; width: auto; max-width: 110px; display: block; }
    .body-content-offer .sign-block-compact .sign-line { margin-top: 8px; }
    .body-content-offer .sign-block-compact .sign-name { margin: 0; line-height: 1.2; }

    /* Dompdf: fixed footer keeps regd. office + banner together on page 1 */
    .footer-offer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 210mm;
        z-index: 3;
        page-break-inside: avoid;
    }
    .page-offer .footer-message {
        font-size: 9px;
        padding: 0 30px 3px;
        page-break-after: avoid;
    }
    .page-offer .footer-system-notice {
        font-size: 6px;
        color: #555;
        text-align: right;
        padding: 0 30px 2px;
        line-height: 1.15;
        page-break-inside: avoid;
    }
    .page-offer .footer-bottom-cell {
        height: 45px;
        page-break-inside: avoid;
        page-break-before: avoid;
    }
    .page-offer .footer-offer-table {
        width: 100%;
        border-collapse: collapse;
    }
    .page-offer .footer-url {
        height: 25px;
        background: #034ea1;
        color: #fff;
        font-size: 13px;
        padding-bottom: 8px;
        font-weight: bold;
        line-height: 28px;
        padding-left: 25px;
        vertical-align: middle;
    }
    .page-offer .footer-graphic {
        height: 45px;
        padding: 0;
        vertical-align: bottom;
        text-align: right;
    }
    .page-offer .footer-graphic img {
        display: block;
        width: 100%;
        max-width: 100%;
        height: 45px;
    }
</style>
