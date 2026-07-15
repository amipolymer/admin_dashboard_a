<style>
    .letter-render-html {
        background: #e8e8e8;
        min-height: 100vh;
        padding: 12mm 16px;
        width: auto;
        max-width: none;
    }

    .letter-render-html .page-appointment {
        width: 210mm;
        min-height: 297mm;
        position: relative;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
        margin: 0 auto;
        overflow: hidden;
    }

    .letter-render-html .watermark-fixed {
        position: absolute;
        top: 15%;
        left: 0;
        width: 210mm;
        height: 297mm;
        z-index: 0;
        pointer-events: none;
    }
    .letter-render-html .watermark-fixed table { width: 100%; height: 297mm; border: 0; border-collapse: collapse; }
    .letter-render-html .watermark-fixed td { text-align: center; vertical-align: middle; }
    .letter-render-html .watermark-fixed img { width: 420px; opacity: 0.05; }

    .letter-render-html .letterhead-fixed,
    .letter-render-html .letterhead-block {
        position: relative;
        top: auto;
        left: auto;
        width: 100%;
        z-index: 2;
        background: #fff;
    }

    .letter-render-html .header { text-align: center; padding: 8px 30px 0; }
    .letter-render-html .logo { margin-bottom: 6px; }
    .letter-render-html .logo img { height: 52px; width: auto; }
    .letter-render-html .tagline { font-size: 9.5px; font-weight: bold; margin-bottom: 3px; }
    .letter-render-html .header-message {
        font-size: 8px;
        color: #034ea1;
        font-weight: bold;
        word-spacing: 2px;
        padding-bottom: 2px;
        border-bottom: 1.8px solid #034ea1;
    }
    .letter-render-html .office-section { padding: 0 30px; overflow: hidden; }
    .letter-render-html .office-column { width: 49%; float: left; }
    .letter-render-html .office-column.right { float: right; }
    .letter-render-html .office-address { font-size: 7.5px; line-height: 1.2; padding-left: 4px; }
    .letter-render-html .office-address .office-line { margin: 0; line-height: 1.2; }
    .letter-render-html .clear { clear: both; }
    .letter-render-html .section-divider { border-top: 1.8px solid #034ea1; margin: 0 30px; }
    .letter-render-html .section-divider-2 { border-top: 1.8px solid #034ea1; margin: 1px 30px 6px; }

    .letter-render-html .body-content-appt {
        padding: 8px 30px 58px;
        line-height: 1.45;
        text-align: justify;
        font-size: 10.5px;
        position: relative;
        z-index: 2;
    }
    .letter-render-html .body-content-appt p { margin-bottom: 10px; }
    .letter-render-html .body-content-appt .mb-2 { margin-bottom: 6px; }
    .letter-render-html .body-content-appt .mb-15 { margin-bottom: 12px; }

    .letter-render-html .footer-fixed {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 3;
    }
    .letter-render-html .footer-message { font-size: 9px; padding: 0 30px 3px; }
    .letter-render-html .footer-bottom-cell { height: 45px; }
    .letter-render-html .footer-offer-table { width: 100%; border-collapse: collapse; }
    .letter-render-html .footer-url {
        height: 45px;
        background: #034ea1;
        color: #fff;
        font-size: 11px;
        font-weight: bold;
        line-height: 45px;
        padding-left: 25px;
        vertical-align: middle;
    }
    .letter-render-html .footer-graphic {
        height: 45px;
        padding: 0;
        vertical-align: bottom;
        text-align: right;
    }
    .letter-render-html .footer-graphic img {
        display: block;
        width: 100%;
        max-width: 100%;
        height: 45px;
    }

    .letter-render-html .signatures-row .sign-col-right {
        text-align: right;
        vertical-align: top;
    }
    .letter-render-html .sign-col-right .candidate-accept-table {
        margin-left: auto;
        margin-right: 0;
    }

    .letter-render-html .sign-block { margin-top: 12px; text-align: left; font-size: 10.5px; }
    .letter-render-html .sign-block img { height: 50px; display: block; margin: 4px 0; }
    .letter-render-html .signatures-row { width: 100%; margin-top: 12px; border-collapse: collapse; }
    .letter-render-html .candidate-accept-table td { padding: 2px 0; font-size: 10.5px; }

    .letter-render-html .appt-table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        font-size: 10px;
    }
    .letter-render-html .appt-table th,
    .letter-render-html .appt-table td {
        border: 1px solid #444;
        padding: 5px 7px;
        text-align: left;
    }
    .letter-render-html .appt-table th { background: #f0f0f0; width: 38%; }
    .letter-render-html .appt-table.ctc-table th { width: auto; }
    .letter-render-html .appt-table.ctc-table td:last-child { text-align: right; font-weight: bold; }

    .letter-render-html .letter-ordinal {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 0.72em;
        font-weight: inherit;
        font-style: normal;
        line-height: 0;
        vertical-align: super;
        position: relative;
        top: -0.05em;
    }
</style>
