<style>
    @page { size: A4; margin: 0; }

    html, body {
        width: 210mm;
        margin: 0;
        padding: 0;
    }

    .watermark-fixed {
        position: fixed;
        top: 20%;
        left: 0;
        width: 210mm;
        height: 297mm;
        z-index: 0;
        pointer-events: none;
    }
    .watermark-fixed table { width: 100%; height: 297mm; border: 0; border-collapse: collapse; }
    .watermark-fixed td { text-align: center; vertical-align: middle; }
    .watermark-fixed img { width: 600px; opacity: 0.08; }

    .letterhead-fixed {
        position: fixed;
        top: 0;
        left: 0;
        width: 210mm;
        z-index: 5;
        background: #fff;
    }

    .header { text-align: center; padding: 8px 30px 0; }
    .logo { margin-bottom: 6px; }
    .logo img { height: 52px; width: auto; }
    .tagline { font-size: 9.5px; color: #231f20; font-weight: bold; margin-bottom: 3px; }
    .header-message {
        font-size: 8px;
        color: #034ea1;
        font-weight: bold;
        word-spacing: 2px;
        padding-bottom: 2px;
        border-bottom: 1.8px solid #034ea1;
    }
    .office-section { padding: 0 30px; overflow: hidden; }
    .office-column { width: 49%; float: left; }
    .office-column.right { float: right; }
    .office-address { line-height: 1.3; color: #231f20; padding-left: 4px; font-size: 7.5px; }
    .office-address .office-line { margin: 0; line-height: 1.2; }
    .clear { clear: both; }
    .section-divider { border-top: 1.8px solid #034ea1; margin: 0 30px; }
    .section-divider-2 { border-top: 1.8px solid #034ea1; margin: 1px 30px 4px; }

    .page-appointment {
        width: 210mm;
        position: relative;
    }

    .body-content-appt {
        padding: 168px 30px 58px;
        line-height: 1.45;
        text-align: justify;
        font-size: 10.5px;
        position: relative;
        z-index: 2;
    }
    .body-content-appt p { margin-bottom: 10px; }
    .body-content-appt .mb-2 { margin-bottom: 6px; }
    .body-content-appt .mb-15 { margin-bottom: 12px; }

    .footer-fixed {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 210mm;
        z-index: 10;
        page-break-inside: avoid;
    }
    .footer-message {
        font-size: 9px;
        padding: 0 30px 3px;
        page-break-after: avoid;
    }
    .footer-system-notice {
        font-size: 6px;
        color: #555;
        text-align: right;
        padding: 0 30px 2px;
        line-height: 1.15;
        page-break-inside: avoid;
    }
    .footer-bottom-cell { height: 40px; page-break-inside: avoid; page-break-before: avoid; }
    .footer-offer-table { width: 100%; border-collapse: collapse; }
    .footer-url {
        height: 30px;
        background: #034ea1;
        color: #fff;
        font-size: 12px;
        font-weight: bold;
        line-height: 30px;
        padding-left: 25px;
        vertical-align: middle;
    }
    .footer-graphic {
        height: 45px;
        padding: 0;
        vertical-align: bottom;
        text-align: right;
    }
    .footer-graphic img {
        display: block;
        width: 100%;
        max-width: 100%;
        height: 45px;
    }

    .sign-block { margin-top: 12px; text-align: left; }
    .sign-block img { height: 50px; display: block; margin: 4px 0; }
    .sign-line { margin-top: 18px; }
    .sign-name { margin: 0; }
    .sign-role { margin: 2px 0 0; color: #444; font-weight: bold; }
    .signatures-row { width: 100%; margin-top: 12px; border-collapse: collapse; }
    .candidate-accept-table td { padding: 2px 0; font-size: 10.5px; }

    .appt-table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        font-size: 10px;
    }
    .appt-table th, .appt-table td {
        border: 1px solid #444;
        padding: 5px 7px;
        text-align: left;
    }
    .appt-table th { background: #f0f0f0; width: 38%; }
    .appt-table.ctc-table th { width: auto; }
    .appt-table.ctc-table td:last-child { text-align: right; font-weight: bold; }
</style>
