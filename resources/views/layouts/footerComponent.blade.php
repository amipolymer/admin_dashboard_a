{{-- footerComponent.blade.php --}}

<style>
td.gridjs-td {
    white-space: nowrap;
    overflow: clip;
    width: auto;
    text-overflow: ellipsis;
    padding: 0px 2px !important;
}

.no_action td.gridjs-td {
    padding: 5px 2px !important;
}

table.gridjs-table {
    width: auto;
    min-width: 100%;
}

@media(max-width:400px) {
    .dashboard_tite {
        font-size: 12px;
    }
}

body.sidebar-dark.header-dark .header-search form label {
    color: #fff !important;
}

/* Global Grid.js alternating row colors */

.gridjs-table .gridjs-tbody .gridjs-tr:nth-child(odd) .gridjs-td {
    background-color: #eaeef22e !important;
}
</style>

<div class="footer-wrap pd-20 mb-20 card-box">

    © 2025 - <?php echo date('Y'); ?> APPL E-Digital Application.
</div>