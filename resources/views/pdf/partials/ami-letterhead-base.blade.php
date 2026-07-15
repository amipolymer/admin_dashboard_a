<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #231f20;
    }

    .letter-view-only {
        -webkit-user-select: none;
        user-select: none;
    }
    .letter-view-only .body-content-offer,
    .letter-view-only .body-content-appt {
        pointer-events: none;
    }

    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .spac-1 { line-height: normal; }
    .strong { font-weight: bold; }

    .signatures-row { width: 100%; border-collapse: collapse; }
    .sign-col-left { vertical-align: top; text-align: left; }
    .sign-col-right { vertical-align: top; text-align: right; }

    .candidate-accept-table { border-collapse: collapse; margin-left: auto; }
    .candidate-accept-table td { padding: 1px 0; font-size: 12px; vertical-align: top; }
    .candidate-accept-table .candidate-accept-label { padding-bottom: 2px; }
    .candidate-accept-table .candidate-accept-sig { padding: 2px 0; }
    .candidate-accept-table .candidate-accept-sig img { display: block; margin-left: auto; }
    .candidate-accept-table .candidate-accept-name { padding-top: 2px; }

    .letter-ordinal {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 0.72em;
        font-weight: inherit;
        font-style: normal;
        line-height: 0;
        vertical-align: super;
        position: relative;
        top: -0.05em;
    }
</style>
