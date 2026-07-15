<style>
    .letter-render-html {
        background: #e8e8e8;
        min-height: 100vh;
        padding: 12mm 16px;
        width: auto;
        max-width: none;
    }

    .letter-render-html .page-offer {
        width: 210mm;
        height: 297mm;
        max-height: 297mm;
        position: relative;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
        margin: 0 auto;
        overflow: hidden;
    }

    .letter-render-html .watermark-offer {
        position: absolute;
        top: 0%;
        left: 0;
        width: 210mm;
        height: 297mm;
        z-index: 0;
        pointer-events: none;
    }
    .letter-render-html .watermark-offer table { width: 100%; height: 297mm; border: 0; border-collapse: collapse; }
    .letter-render-html .watermark-offer td { text-align: center; vertical-align: middle; }
    .letter-render-html .watermark-offer img { width: 600px; opacity: 0.09; }

    .letter-render-html .page-offer .header,
    .letter-render-html .page-offer .office-section,
    .letter-render-html .page-offer .section-divider,
    .letter-render-html .page-offer .section-divider-2,
    .letter-render-html .page-offer .body-content-offer,
    .letter-render-html .page-offer .footer-offer {
        position: relative;
        z-index: 2;
    }

    .letter-render-html .page-offer .header { text-align: center; padding: 6px 30px 0; }
    .letter-render-html .page-offer .logo { margin-bottom: 4px; }
    .letter-render-html .page-offer .logo img { height: 52px; width: auto; }
    .letter-render-html .page-offer .tagline { font-size: 9.5px; font-weight: bold; margin-bottom: 3px; }
    .letter-render-html .page-offer .header-message {
        font-size: 10px;
        color: #034ea1;
        font-weight: bold;
        word-spacing: 2px;
        padding-bottom: 2px;
        border-bottom: 1.8px solid #034ea1;
    }
    .letter-render-html .page-offer .office-section { padding: 0 30px; overflow: hidden; }
    .letter-render-html .page-offer .office-column { width: 49%; float: left; }
    .letter-render-html .page-offer .office-column.right { float: right; }
    .letter-render-html .page-offer .office-address { font-size: 9.5px; line-height: 1.2; padding-left: 4px; }
    .letter-render-html .page-offer .office-address .office-line { margin: 0; line-height: 1.2; }
    .letter-render-html .page-offer .clear { clear: both; }
    .letter-render-html .page-offer .section-divider { border-top: 1.8px solid #034ea1; margin: 0 30px; }
    .letter-render-html .page-offer .section-divider-2 { border-top: 1.8px solid #034ea1; margin: 1px 30px 4px; }

    .letter-render-html .body-content-offer {
        padding: 0 30px 62px;
        line-height: 1.28;
        text-align: justify;
        font-size: 13px;
    }
    .letter-render-html .body-content-offer p { margin-bottom: 4px; line-height: 1.28; }
    .letter-render-html .body-content-offer .mb-2 { margin-bottom: 2px; }
    .letter-render-html .body-content-offer .mb-15 { margin-bottom: 14px; }
    .letter-render-html .body-content-offer .closing-para { margin-top: 4px; margin-bottom: 25px; }

    .letter-render-html .doc-list-wrap { margin-left: 28px; margin-bottom: 6px; }
    .letter-render-html .body-content-offer .doc-list { margin: 0; padding-left: 14px; }
    .letter-render-html .body-content-offer .doc-list li {
        margin-bottom: 0;
        line-height: 1.5;
        font-size: 13px;
    }

    .letter-render-html .body-content-offer .signatures-row { margin-top: 2px; }
    .letter-render-html .body-content-offer .sign-block { margin-top: 0; font-size: 12px; }
    .letter-render-html .body-content-offer .sign-block-compact img { height: 32px; width: auto; max-width: 110px; display: block; }
    .letter-render-html .body-content-offer .sign-block-compact .sign-line { margin-top: 8px; }
    .letter-render-html .body-content-offer .sign-block-compact .sign-name { margin: 0; line-height: 1.2; }

    .letter-render-html .signatures-row .sign-col-right {
        text-align: right;
        vertical-align: top;
    }
    .letter-render-html .sign-col-right .candidate-accept-table {
        margin-left: auto;
        margin-right: 0;
    }

    .letter-render-html .footer-offer {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 3;
    }
    .letter-render-html .page-offer .footer-message { font-size: 9px; padding: 0 30px 3px; }
    .letter-render-html .page-offer .footer-bottom-cell { height: 45px; }
    .letter-render-html .page-offer .footer-offer-table { width: 100%; border-collapse: collapse; }
    .letter-render-html .page-offer .footer-url {
        height: 45px;
        background: #034ea1;
        color: #fff;
        font-size: 11px;
        font-weight: bold;
        line-height: 45px;
        padding-left: 25px;
        vertical-align: middle;
    }
    .letter-render-html .page-offer .footer-graphic {
        height: 45px;
        padding: 0;
        vertical-align: bottom;
        text-align: right;
    }
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
