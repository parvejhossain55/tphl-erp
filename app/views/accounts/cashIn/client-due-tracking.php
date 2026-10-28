<?php
$page_title = "Client-wise Due Tracking & Payment Management";
$page = 'Clients';
$show_breadcrumb = true;
?>

<style>
    /* Styling for Client-wise Due Tracking */
    .due-stat-card, .emi-stat-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }
    .emi-stat-card {
        cursor: pointer;
    }
    .due-stat-card:hover, .emi-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .due-stat-card .card-body, .emi-stat-card .card-body {
        padding: 1.15rem 1.25rem;
    }
    @media (min-width: 1200px) {
        .col-kpi-5 {
            flex: 0 0 20% !important;
            max-width: 20% !important;
        }
    }
    .emi-category-pills .nav-link {
        color: #475569;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .emi-category-pills .nav-link:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .emi-category-pills .nav-link.active {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }
    .stat-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .project-chip {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 10px;
        background: #fff;
    }
    .project-chip:hover, .project-chip.active {
        border-color: #0d6efd;
        background: #f0f7ff;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
    }
    .registered-client-chip-btn {
        transition: all 0.15s ease;
    }
    .registered-client-chip-btn:hover {
        background-color: #198754 !important;
        color: #fff !important;
        border-color: #198754 !important;
        transform: scale(1.02);
    }
    #projectChipsContainer {
        max-height: 295px;
        overflow-y: auto;
        overflow-x: hidden;
    }
    #projectChipsContainer::-webkit-scrollbar {
        width: 6px;
    }
    #projectChipsContainer::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 4px;
    }
    #projectChipsContainer::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 4px;
    }
    #projectChipsContainer::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
    }
    .due-type-pill {
        border-radius: 20px;
        font-size: 12px;
        padding: 6px 14px;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        font-weight: 500;
    }
    .due-type-pill:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    .due-type-pill.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
    }
    .table-due thead th {
        background: #f8f9fa;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #e9ecef;
        padding: 12px 10px;
        white-space: nowrap;
    }
    .table-due tbody td {
        font-size: 13px;
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f5;
    }
    .table-due tbody tr:hover {
        background-color: #fafbfd;
    }
    .fs-11 { font-size: 11px !important; }
    .fs-12 { font-size: 12px !important; }
    .fs-13 { font-size: 13px !important; }
    .fs-14 { font-size: 14px !important; }
    .fs-15 { font-size: 15px !important; }

    /* Select2 customization for due toolbar */
    .due-toolbar .select2-container {
        width: 100% !important;
    }
    .due-toolbar .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        display: flex;
        align-items: center;
        background-color: #fff;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .due-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        font-size: 13px;
        color: #212529;
        padding-left: 12px;
        padding-right: 28px;
        font-weight: 500;
    }
    .due-toolbar .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
        top: 0;
    }
    .due-toolbar .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 18px;
        font-size: 16px;
        line-height: 36px;
        color: #adb5bd;
    }
    .due-toolbar .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #dc3545;
    }
    .due-toolbar .select2-container--default.select2-container--focus .select2-selection--single,
    .due-toolbar .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .select2-dropdown {
        border-radius: 8px;
        border-color: #dee2e6;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown {
        padding: 8px 10px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 6px 12px;
        font-size: 13px;
        outline: none;
        width: 100%;
        box-sizing: border-box;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
    }
    .select2-container--default .select2-results__option {
        font-size: 13px;
        padding: 8px 14px;
    }

    /* =========================================================================
       DARK THEME COMPATIBILITY FOR CLIENT-WISE DUE TRACKING
       ========================================================================= */
    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .bg-white,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .bg-white,
    body.dark-only .client-due-tracking-wrapper .bg-white,
    body.dark-mode .client-due-tracking-wrapper .bg-white,
    .dark-mode .client-due-tracking-wrapper .bg-white,
    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .card,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .card,
    body.dark-only .client-due-tracking-wrapper .card,
    body.dark-mode .client-due-tracking-wrapper .card,
    .dark-mode .client-due-tracking-wrapper .card,
    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .card-header,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .card-header,
    body.dark-only .client-due-tracking-wrapper .card-header,
    body.dark-mode .client-due-tracking-wrapper .card-header,
    .dark-mode .client-due-tracking-wrapper .card-header,
    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .card-footer,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .card-footer,
    body.dark-only .client-due-tracking-wrapper .card-footer,
    body.dark-mode .client-due-tracking-wrapper .card-footer,
    .dark-mode .client-due-tracking-wrapper .card-footer {
        background-color: var(--clr-bg-card, #1e293b) !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }

    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .border,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .border,
    body.dark-only .client-due-tracking-wrapper .border,
    body.dark-mode .client-due-tracking-wrapper .border,
    .dark-mode .client-due-tracking-wrapper .border {
        border-color: #334155 !important;
    }

    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .text-dark,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .text-dark,
    body.dark-only .client-due-tracking-wrapper .text-dark,
    body.dark-mode .client-due-tracking-wrapper .text-dark,
    .dark-mode .client-due-tracking-wrapper .text-dark {
        color: #f1f5f9 !important;
    }

    [bd-theme="bd-theme-dark"] .client-due-tracking-wrapper .text-muted,
    [data-bs-theme="dark"] .client-due-tracking-wrapper .text-muted,
    body.dark-only .client-due-tracking-wrapper .text-muted,
    body.dark-mode .client-due-tracking-wrapper .text-muted,
    .dark-mode .client-due-tracking-wrapper .text-muted {
        color: #94a3b8 !important;
    }

    /* Top KPI & EMI Cards */
    [bd-theme="bd-theme-dark"] .due-stat-card,
    [data-bs-theme="dark"] .due-stat-card,
    body.dark-only .due-stat-card,
    body.dark-mode .due-stat-card,
    .dark-mode .due-stat-card,
    [bd-theme="bd-theme-dark"] .emi-stat-card,
    [data-bs-theme="dark"] .emi-stat-card,
    body.dark-only .emi-stat-card,
    body.dark-mode .emi-stat-card,
    .dark-mode .emi-stat-card {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3) !important;
    }
    [bd-theme="bd-theme-dark"] .due-stat-card.border-danger,
    [data-bs-theme="dark"] .due-stat-card.border-danger,
    [bd-theme="bd-theme-dark"] .emi-stat-card.border-danger,
    [data-bs-theme="dark"] .emi-stat-card.border-danger { border-left: 4px solid #ef4444 !important; }
    [bd-theme="bd-theme-dark"] .due-stat-card.border-warning,
    [data-bs-theme="dark"] .due-stat-card.border-warning,
    [bd-theme="bd-theme-dark"] .emi-stat-card.border-warning,
    [data-bs-theme="dark"] .emi-stat-card.border-warning { border-left: 4px solid #f59e0b !important; }
    [bd-theme="bd-theme-dark"] .due-stat-card.border-info,
    [data-bs-theme="dark"] .due-stat-card.border-info,
    [bd-theme="bd-theme-dark"] .emi-stat-card.border-info,
    [data-bs-theme="dark"] .emi-stat-card.border-info { border-left: 4px solid #06b6d4 !important; }
    [bd-theme="bd-theme-dark"] .due-stat-card.border-primary,
    [data-bs-theme="dark"] .due-stat-card.border-primary,
    [bd-theme="bd-theme-dark"] .emi-stat-card.border-primary,
    [data-bs-theme="dark"] .emi-stat-card.border-primary { border-left: 4px solid #3b82f6 !important; }
    [bd-theme="bd-theme-dark"] .due-stat-card.border-indigo,
    [data-bs-theme="dark"] .due-stat-card.border-indigo { border-left: 4px solid #6366f1 !important; }
    [bd-theme="bd-theme-dark"] .emi-stat-card.border-success,
    [data-bs-theme="dark"] .emi-stat-card.border-success { border-left: 4px solid #10b981 !important; }

    /* Category Pills in Dark Mode */
    [bd-theme="bd-theme-dark"] .emi-category-pills,
    [data-bs-theme="dark"] .emi-category-pills,
    body.dark-only .emi-category-pills,
    body.dark-mode .emi-category-pills,
    .dark-mode .emi-category-pills {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .emi-category-pills .nav-link,
    [data-bs-theme="dark"] .emi-category-pills .nav-link,
    body.dark-only .emi-category-pills .nav-link,
    body.dark-mode .emi-category-pills .nav-link,
    .dark-mode .emi-category-pills .nav-link {
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .emi-category-pills .nav-link.active,
    [data-bs-theme="dark"] .emi-category-pills .nav-link.active,
    body.dark-only .emi-category-pills .nav-link.active,
    body.dark-mode .emi-category-pills .nav-link.active,
    .dark-mode .emi-category-pills .nav-link.active {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
    }

    /* Project Chip Cards */
    [bd-theme="bd-theme-dark"] .project-chip,
    [data-bs-theme="dark"] .project-chip,
    body.dark-only .project-chip,
    body.dark-mode .project-chip,
    .dark-mode .project-chip {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
    }
    [bd-theme="bd-theme-dark"] .project-chip:hover,
    [data-bs-theme="dark"] .project-chip:hover,
    body.dark-only .project-chip:hover,
    body.dark-mode .project-chip:hover,
    .dark-mode .project-chip:hover {
        background: #273549 !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.35) !important;
    }
    [bd-theme="bd-theme-dark"] .project-chip.active,
    [data-bs-theme="dark"] .project-chip.active,
    body.dark-only .project-chip.active,
    body.dark-mode .project-chip.active,
    .dark-mode .project-chip.active {
        background: #1e3a5f !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 1px #3b82f6, 0 4px 12px rgba(59, 130, 246, 0.3) !important;
    }
    [bd-theme="bd-theme-dark"] .project-chip .border-top,
    [data-bs-theme="dark"] .project-chip .border-top,
    body.dark-only .project-chip .border-top,
    body.dark-mode .project-chip .border-top,
    .dark-mode .project-chip .border-top {
        border-top-color: #334155 !important;
        color: #94a3b8 !important;
    }

    /* Scrollbar for Project Chips */
    [bd-theme="bd-theme-dark"] #projectChipsContainer::-webkit-scrollbar-track,
    [data-bs-theme="dark"] #projectChipsContainer::-webkit-scrollbar-track,
    body.dark-only #projectChipsContainer::-webkit-scrollbar-track,
    body.dark-mode #projectChipsContainer::-webkit-scrollbar-track {
        background: #0f172a;
    }
    [bd-theme="bd-theme-dark"] #projectChipsContainer::-webkit-scrollbar-thumb,
    [data-bs-theme="dark"] #projectChipsContainer::-webkit-scrollbar-thumb,
    body.dark-only #projectChipsContainer::-webkit-scrollbar-thumb,
    body.dark-mode #projectChipsContainer::-webkit-scrollbar-thumb {
        background: #334155;
    }
    [bd-theme="bd-theme-dark"] #projectChipsContainer::-webkit-scrollbar-thumb:hover,
    [data-bs-theme="dark"] #projectChipsContainer::-webkit-scrollbar-thumb:hover,
    body.dark-only #projectChipsContainer::-webkit-scrollbar-thumb:hover,
    body.dark-mode #projectChipsContainer::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }

    /* Quick Filter Pills */
    [bd-theme="bd-theme-dark"] .due-type-pill,
    [data-bs-theme="dark"] .due-type-pill,
    body.dark-only .due-type-pill,
    body.dark-mode .due-type-pill,
    .dark-mode .due-type-pill {
        background: #1e293b !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .due-type-pill:hover,
    [data-bs-theme="dark"] .due-type-pill:hover,
    body.dark-only .due-type-pill:hover,
    body.dark-mode .due-type-pill:hover,
    .dark-mode .due-type-pill:hover {
        background: #273549 !important;
        border-color: #475569 !important;
        color: #ffffff !important;
    }
    [bd-theme="bd-theme-dark"] .due-type-pill.active,
    [data-bs-theme="dark"] .due-type-pill.active,
    body.dark-only .due-type-pill.active,
    body.dark-mode .due-type-pill.active,
    .dark-mode .due-type-pill.active {
        background: #0d6efd !important;
        color: #ffffff !important;
        border-color: #0d6efd !important;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.4) !important;
    }

    /* Due Toolbar & Inputs */
    [bd-theme="bd-theme-dark"] .due-toolbar,
    [data-bs-theme="dark"] .due-toolbar,
    body.dark-only .due-toolbar,
    body.dark-mode .due-toolbar,
    .dark-mode .due-toolbar {
        background-color: #141d2e !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .due-toolbar .form-control,
    [bd-theme="bd-theme-dark"] .due-toolbar .form-select,
    [bd-theme="bd-theme-dark"] #projectChipSearch,
    [data-bs-theme="dark"] .due-toolbar .form-control,
    [data-bs-theme="dark"] .due-toolbar .form-select,
    [data-bs-theme="dark"] #projectChipSearch,
    body.dark-only .due-toolbar .form-control,
    body.dark-only .due-toolbar .form-select,
    body.dark-only #projectChipSearch,
    body.dark-mode .due-toolbar .form-control,
    body.dark-mode .due-toolbar .form-select,
    body.dark-mode #projectChipSearch,
    .dark-mode .due-toolbar .form-control,
    .dark-mode .due-toolbar .form-select,
    .dark-mode #projectChipSearch {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .due-toolbar .form-control::placeholder,
    [bd-theme="bd-theme-dark"] #projectChipSearch::placeholder,
    [data-bs-theme="dark"] .due-toolbar .form-control::placeholder,
    [data-bs-theme="dark"] #projectChipSearch::placeholder {
        color: #64748b !important;
    }

    /* Select2 in Toolbar */
    [bd-theme="bd-theme-dark"] .due-toolbar .select2-container--default .select2-selection--single,
    [data-bs-theme="dark"] .due-toolbar .select2-container--default .select2-selection--single,
    body.dark-only .due-toolbar .select2-container--default .select2-selection--single,
    body.dark-mode .due-toolbar .select2-container--default .select2-selection--single,
    .dark-mode .due-toolbar .select2-container--default .select2-selection--single {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .due-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    [data-bs-theme="dark"] .due-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    body.dark-only .due-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    body.dark-mode .due-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered,
    .dark-mode .due-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown,
    [data-bs-theme="dark"] .select2-dropdown,
    body.dark-only .select2-dropdown,
    body.dark-mode .select2-dropdown,
    .dark-mode .select2-dropdown {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-search--dropdown,
    [data-bs-theme="dark"] .select2-container--default .select2-search--dropdown,
    body.dark-only .select2-container--default .select2-search--dropdown,
    body.dark-mode .select2-container--default .select2-search--dropdown,
    .dark-mode .select2-container--default .select2-search--dropdown {
        background-color: #141d2e !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-search--dropdown .select2-search__field,
    [data-bs-theme="dark"] .select2-container--default .select2-search--dropdown .select2-search__field,
    body.dark-only .select2-container--default .select2-search--dropdown .select2-search__field,
    body.dark-mode .select2-container--default .select2-search--dropdown .select2-search__field,
    .dark-mode .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-results__option,
    [data-bs-theme="dark"] .select2-container--default .select2-results__option,
    body.dark-only .select2-container--default .select2-results__option,
    body.dark-mode .select2-container--default .select2-results__option,
    .dark-mode .select2-container--default .select2-results__option {
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-results__option--highlighted,
    [data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted,
    body.dark-only .select2-container--default .select2-results__option--highlighted,
    body.dark-mode .select2-container--default .select2-results__option--highlighted,
    .dark-mode .select2-container--default .select2-results__option--highlighted {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    /* Table Due */
    [bd-theme="bd-theme-dark"] .table-due,
    [data-bs-theme="dark"] .table-due,
    body.dark-only .table-due,
    body.dark-mode .table-due,
    .dark-mode .table-due {
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .table-due thead th,
    [data-bs-theme="dark"] .table-due thead th,
    body.dark-only .table-due thead th,
    body.dark-mode .table-due thead th,
    .dark-mode .table-due thead th {
        background-color: #141d2e !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .table-due tbody td,
    [data-bs-theme="dark"] .table-due tbody td,
    body.dark-only .table-due tbody td,
    body.dark-mode .table-due tbody td,
    .dark-mode .table-due tbody td {
        border-bottom-color: #263345 !important;
        color: #e2e8f0 !important;
        background-color: transparent !important;
    }
    [bd-theme="bd-theme-dark"] .table-due tbody tr:hover,
    [data-bs-theme="dark"] .table-due tbody tr:hover,
    body.dark-only .table-due tbody tr:hover,
    body.dark-mode .table-due tbody tr:hover,
    .dark-mode .table-due tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }
    [bd-theme="bd-theme-dark"] .table-due tbody tr:hover td,
    [data-bs-theme="dark"] .table-due tbody tr:hover td,
    body.dark-only .table-due tbody tr:hover td,
    body.dark-mode .table-due tbody tr:hover td,
    .dark-mode .table-due tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }
    [bd-theme="bd-theme-dark"] .table-due .bg-light,
    [data-bs-theme="dark"] .table-due .bg-light,
    body.dark-only .table-due .bg-light,
    body.dark-mode .table-due .bg-light,
    .dark-mode .table-due .bg-light {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #38bdf8 !important;
    }

    /* Modals in Dark Mode */
    [bd-theme="bd-theme-dark"] .modal-content,
    [data-bs-theme="dark"] .modal-content,
    body.dark-only .modal-content,
    body.dark-mode .modal-content,
    .dark-mode .modal-content {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .modal-header,
    [data-bs-theme="dark"] .modal-content .modal-header,
    body.dark-only .modal-content .modal-header,
    body.dark-mode .modal-content .modal-header,
    .dark-mode .modal-content .modal-header {
        border-bottom-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .modal-footer,
    [data-bs-theme="dark"] .modal-content .modal-footer,
    body.dark-only .modal-content .modal-footer,
    body.dark-mode .modal-content .modal-footer,
    .dark-mode .modal-content .modal-footer {
        background-color: #141d2e !important;
        border-top-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .bg-light,
    [data-bs-theme="dark"] .modal-content .bg-light,
    body.dark-only .modal-content .bg-light,
    body.dark-mode .modal-content .bg-light,
    .dark-mode .modal-content .bg-light {
        background-color: #141d2e !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .table,
    [data-bs-theme="dark"] .modal-content .table,
    body.dark-only .modal-content .table,
    body.dark-mode .modal-content .table,
    .dark-mode .modal-content .table {
        color: #e2e8f0 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .table th,
    [bd-theme="bd-theme-dark"] .modal-content .table td,
    [data-bs-theme="dark"] .modal-content .table th,
    [data-bs-theme="dark"] .modal-content .table td,
    body.dark-only .modal-content .table th,
    body.dark-only .modal-content .table td,
    body.dark-mode .modal-content .table th,
    body.dark-mode .modal-content .table td,
    .dark-mode .modal-content .table th,
    .dark-mode .modal-content .table td {
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .table-light,
    [bd-theme="bd-theme-dark"] .modal-content .table-light th,
    [data-bs-theme="dark"] .modal-content .table-light,
    [data-bs-theme="dark"] .modal-content .table-light th,
    body.dark-only .modal-content .table-light,
    body.dark-only .modal-content .table-light th,
    body.dark-mode .modal-content .table-light,
    body.dark-mode .modal-content .table-light th,
    .dark-mode .modal-content .table-light,
    .dark-mode .modal-content .table-light th {
        background-color: #141d2e !important;
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .table-hover tbody tr:hover,
    [data-bs-theme="dark"] .modal-content .table-hover tbody tr:hover,
    body.dark-only .modal-content .table-hover tbody tr:hover,
    body.dark-mode .modal-content .table-hover tbody tr:hover,
    .dark-mode .modal-content .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .bg-white,
    [data-bs-theme="dark"] .modal-content .bg-white,
    body.dark-only .modal-content .bg-white,
    body.dark-mode .modal-content .bg-white,
    .dark-mode .modal-content .bg-white {
        background-color: #141d2e !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .text-dark,
    [data-bs-theme="dark"] .modal-content .text-dark,
    body.dark-only .modal-content .text-dark,
    body.dark-mode .modal-content .text-dark,
    .dark-mode .modal-content .text-dark {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .modal-content .form-control,
    [bd-theme="bd-theme-dark"] .modal-content .form-select,
    [data-bs-theme="dark"] .modal-content .form-control,
    [data-bs-theme="dark"] .modal-content .form-select,
    body.dark-only .modal-content .form-control,
    body.dark-only .modal-content .form-select,
    body.dark-mode .modal-content .form-control,
    body.dark-mode .modal-content .form-select,
    .dark-mode .modal-content .form-control,
    .dark-mode .modal-content .form-select {
        background-color: #141d2e !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }

    @media print {
        @page {
            size: landscape;
            margin: 5mm 6mm;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 1. COMPLETELY HIDE UNIVERSAL PRINT PAD (Left/Right Green Borders, Watermark, Corporate Footer, Header) */
        .universal-print-pad,
        .universal-print-pad *,
        .upp-left-border,
        .upp-right-border,
        .upp-header,
        .upp-footer,
        .upp-watermark,
        .preloader,
        .app__header__area,
        .app__header-inner,
        .app__header-left,
        .app__header-right,
        .breadcrumb-area,
        .page__title-area,
        .page-header,
        .page-header-breadcrumb,
        .breadcrumb,
        .body__overlay {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            position: absolute !important;
            visibility: hidden !important;
            overflow: hidden !important;
        }

        /* 2. HIDE NAVIGATION, SIDEBARS, SYSTEM FOOTERS, BREADCRUMBS, MODALS */
        #sidebar,
        .app-sidebar,
        .sidebar,
        .main-header,
        .app-header,
        .navbar,
        .header-wrapper,
        .footer,
        .main-footer,
        footer,
        .modal,
        .modal-backdrop,
        .avatar,
        button,
        .btn {
            display: none !important;
        }

        /* 3. HIDE PAGE CONTROLS, TOOLBARS, INPUTS, SELECTS, CARDS & PAGINATION */
        .due-header-box,
        .due-header-wrapper,
        .due-summary-section,
        .col-kpi-5,
        .due-stat-card,
        .emi-stat-card,
        .due-project-card,
        .due-main-card > .card-header,
        .due-toolbar,
        .due-toolbar *,
        #dueTrackingFilterForm,
        #dueTrackingFilterForm *,
        .card-footer,
        #duePaginationContainer,
        #dueInfoText,
        .no-print {
            display: none !important;
            height: 0 !important;
            min-height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            overflow: hidden !important;
        }

        /* 4. SHOW ONLY CLEAN REPORT HEADER AT THE VERY TOP */
        .print-only {
            display: block !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
            margin-bottom: 6px !important;
            padding-bottom: 4px !important;
        }

        /* 5. HARD RESET ALL TOP MARGINS/PADDINGS (OVERRIDING print-pad.php's 150px/120px) */
        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            font-size: 8pt !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .page__full-wrapper,
        .page__body-wrapper,
        .app__slide-wrapper,
        .main-content,
        .content-wrapper,
        .client-due-tracking-wrapper,
        .due-main-card,
        .col-12 {
            margin: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            position: static !important;
            top: 0 !important;
        }

        .card, .due-main-card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
        }

        .card-body,
        .table-responsive {
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            min-height: auto !important;
            display: block !important;
            width: 100% !important;
            border: none !important;
        }

        /* 6. CLEAN PRINT TABLE - 9 DATA COLUMNS (NO BORDERS OUTSIDE, CLEAN ROW LINES) */
        table.table-due {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            margin: 0 !important;
            font-size: 7.5pt !important;
            border: none !important;
        }

        table.table-due thead {
            display: table-header-group !important;
        }

        table.table-due tr {
            page-break-inside: avoid !important;
        }

        table.table-due th,
        table.table-due td {
            min-width: 0 !important;
            padding: 4px 4px !important;
            border-top: 1px solid #e2e8f0 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            border-left: none !important;
            border-right: none !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
            line-height: 1.25 !important;
            color: #0f172a !important;
            box-sizing: border-box !important;
        }

        table.table-due th {
            background-color: #f8fafc !important;
            color: #334155 !important;
            font-weight: 700 !important;
            font-size: 7.5pt !important;
            border-bottom: 2px solid #cbd5e1 !important;
            border-top: none !important;
        }

        table.table-due td a {
            text-decoration: none !important;
            color: inherit !important;
        }

        table.table-due td .badge {
            border: none !important;
            padding: 0 !important;
            font-size: 7.5pt !important;
            font-weight: 600 !important;
            white-space: normal !important;
            word-break: break-word !important;
            background: transparent !important;
        }

        /* 7. TOTAL FOOTER ROW IN PRINT */
        table.table-due tfoot {
            display: table-footer-group !important;
            page-break-inside: avoid !important;
        }
        table.table-due tfoot tr {
            page-break-inside: avoid !important;
        }
        table.table-due tfoot td {
            background-color: #f1f5f9 !important;
            font-weight: 700 !important;
            border-top: 2px solid #475569 !important;
            border-bottom: 2px solid #475569 !important;
            border-left: none !important;
            border-right: none !important;
            padding: 5px 4px !important;
            font-size: 8pt !important;
            color: #0f172a !important;
            line-height: 1.25 !important;
        }

        /* EXACT 9 COLUMNS PERCENTAGE WIDTHS IN PRINT (TOTAL 100%) */
        table.table-due th:nth-child(1), table.table-due td:nth-child(1) { width: 8% !important; max-width: 8% !important; }    /* ID & Date */
        table.table-due th:nth-child(2), table.table-due td:nth-child(2) { width: 16% !important; max-width: 16% !important; }  /* Client Info */
        table.table-due th:nth-child(3), table.table-due td:nth-child(3) { width: 14% !important; max-width: 14% !important; }  /* Project & Unit */
        table.table-due th:nth-child(4), table.table-due td:nth-child(4) { width: 10.5% !important; max-width: 10.5% !important; } /* Flat Value */
        table.table-due th:nth-child(5), table.table-due td:nth-child(5) { width: 10.5% !important; max-width: 10.5% !important; } /* Land Share Due */
        table.table-due th:nth-child(6), table.table-due td:nth-child(6) { width: 10% !important; max-width: 10% !important; }   /* Dev Charge Due */
        table.table-due th:nth-child(7), table.table-due td:nth-child(7) { width: 9.5% !important; max-width: 9.5% !important; } /* Shop Svc Due */
        table.table-due th:nth-child(8), table.table-due td:nth-child(8) { width: 10.5% !important; max-width: 10.5% !important; } /* Due EMI */
        table.table-due th:nth-child(9), table.table-due td:nth-child(9) { width: 11% !important; max-width: 11% !important; }   /* Overall Due */
        table.table-due th:nth-child(10), table.table-due td:nth-child(10) { display: none !important; width: 0 !important; }    /* Action Col Hidden */
    }
</style>

<div class="row client-due-tracking-wrapper">

    <!-- ══ PRINT ONLY: OFFICIAL REPORT HEADER (Placed at the very top for zero gap) ══ -->
    <div class="col-12 print-only d-none mb-2" style="margin-top: 0 !important; padding-top: 0 !important;">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= $GLOBALS['base_url'] ?>/assets/images/logo.png" alt="Company Logo" style="height: 36px;" onerror="this.style.display='none'">
                <div>
                    <h3 class="mb-0 fw-bold" style="font-size: 14pt; color: #0f172a; letter-spacing: -0.3px;">THE PREMIUM HOMES LTD</h3>
                    <div class="fw-semibold text-secondary" style="font-size: 9pt;">Client-wise Due Tracking &amp; Payment Report</div>
                </div>
            </div>
            <div class="text-end" style="font-size: 8pt; color: #475569; line-height: 1.35;">
                <div><strong>Print Date:</strong> <?= date('d M Y, h:i A') ?></div>
                <div><strong>Filter:</strong> <span id="printFilterLabel"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $dueType ?? 'all'))) ?></span></div>
                <div><strong>Showing Records:</strong> <span id="printTotalRecords"><?= number_format($totalClients ?? 0) ?></span> Clients</div>
            </div>
        </div>
    </div>

    <!-- ══ HEADER & ACTIONS ══ -->
    <div class="col-12 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white p-3 rounded-3 shadow-sm border due-header-box">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark">Client-wise Due Tracking & Payment Management</h4>
                        <small class="text-muted">
                            Project-wise and client-wise tracking of <strong>Land Share Due</strong> (Booking + Registration), <strong>Development Charge Due</strong>, <strong>EMI Due</strong>, and <strong>Overall Due</strong>
                        </small>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 no-print">
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#projectBreakdownModal">
                    <i class="fa-solid fa-layer-group me-1"></i> Project Breakdown
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/client-wise-due-tracking-export?<?= http_build_query($_GET) ?>" id="exportDueCsvBtn" class="btn btn-sm btn-success fw-semibold">
                    <i class="fa-solid fa-file-excel me-1"></i> Export to CSV
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" onclick="printDueTrackingReport()">
                    <i class="fa-solid fa-print me-1"></i> Print Report
                </button>
                <button type="button" id="refreshDueBtn" class="btn btn-sm btn-outline-info fw-semibold" onclick="refreshDueTracking(true)" title="Reset all filters and reload">
                    <i class="fa-solid fa-rotate me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- ══ TOP KPI SUMMARY CARDS ══ -->
    <?php
    if (!function_exists('formatDueCardAmount')) {
        function formatDueCardAmount($amount): string {
            $amount = (float)$amount;
            if (abs($amount) >= 10000000) {
                return number_format($amount / 10000000, 2) . ' Cr';
            } elseif (abs($amount) >= 100000) {
                return number_format($amount / 100000, 2) . ' Lac';
            } else {
                return number_format($amount);
            }
        }
    }
    $devDue = (float)($stats['total_dev_charge_due'] ?? 0);
    $devPaid = (float)($stats['total_dev_charges_paid'] ?? 0);
    $devTarget = (float)($stats['total_dev_charges_target'] ?? 0);

    $shopDue = (float)($stats['total_shop_service_charge_due'] ?? 0);
    $shopPaid = (float)($stats['total_shop_service_charge_paid'] ?? 0);
    $shopTarget = (float)($stats['total_shop_service_charge_target'] ?? 0);
    ?>
    <!-- 1. Overall Outstanding Due -->
    <div class="col-kpi-5 col-xl col-lg-4 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-danger h-100 bg-white" onclick="openOverallDueSummaryModal()" style="cursor: pointer;" title="Click to view total amount breakdown">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Overall Outstanding Due</span>
                    <span class="avatar avatar-xs bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-danger mb-1" id="statOverallDue">
                    Tk. <?= number_format($stats['overall_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                    <span>Target: <strong id="statTotalValueText">Tk. <?= formatDueCardAmount($stats['total_value'] ?? 0) ?></strong></span>
                    <span class="badge bg-danger-subtle text-danger stat-pill" id="statOverallDuePercentBadge">
                        <?= ($stats['total_value'] ?? 0) > 0 ? round((($stats['overall_due'] ?? 0) / ($stats['total_value'] ?? 1)) * 100, 1) : 0 ?>% Due
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted fs-11 pt-1 border-top">
                    <span>Paid: <strong class="text-success" id="statTotalPaidText">Tk. <?= formatDueCardAmount($stats['total_paid'] ?? 0) ?></strong></span>
                    <span class="badge bg-primary-subtle text-primary stat-pill">
                        Breakdown <i class="fa-solid fa-arrow-right ms-1"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Land Share Due (Booking + Registration) -->
    <div class="col-kpi-5 col-xl col-lg-4 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-warning h-100 bg-white" onclick="setDueTypeFilter('land_share_due')" style="cursor: pointer;" title="Click to filter Land Share Dues">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Land Share Due</span>
                    <span class="avatar avatar-xs bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-dark mb-1" id="statLandShareDue">
                    Tk. <?= number_format($stats['total_land_share_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                    <span title="Booking Money + Registration Fee">Paid: <strong id="statLandSharePaidText">Tk. <?= formatDueCardAmount($stats['total_land_share_paid'] ?? 0) ?></strong></span>
                    <span class="badge bg-warning-subtle text-dark stat-pill" id="statLandShareTargetBadge" title="Target: Tk. <?= formatDueCardAmount($stats['total_land_share_target'] ?? 0) ?>">
                        Target: Tk. <?= formatDueCardAmount($stats['total_land_share_target'] ?? 0) ?>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted fs-11 pt-1 border-top">
                    <span>Due: <strong class="text-dark" id="statLandShareDueText">Tk. <?= formatDueCardAmount($stats['total_land_share_due'] ?? 0) ?></strong></span>
                    <span class="text-muted extra-small">Filter Dues <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Development Charge Due (Residential Flats) -->
    <div class="col-kpi-5 col-xl col-lg-4 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-info h-100 bg-white" onclick="setDueTypeFilter('dev_charge_due')" style="cursor: pointer;" title="Click to filter Residential Development Charge Dues">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Development Charge Due</span>
                    <span class="avatar avatar-xs bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-info mb-1" id="statDevChargeDue">
                    Tk. <?= number_format($devDue) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                    <span>Paid: <strong id="statDevChargePaidText">Tk. <?= formatDueCardAmount($devPaid) ?></strong></span>
                    <span class="badge bg-info-subtle text-info stat-pill" id="statDevChargeTargetBadge" title="Target: Tk. <?= formatDueCardAmount($devTarget) ?>">
                        Target: Tk. <?= formatDueCardAmount($devTarget) ?>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted fs-11 pt-1 border-top">
                    <span>Due: <strong class="text-info" id="statDevChargeDueText">Tk. <?= formatDueCardAmount($devDue) ?></strong></span>
                    <span class="text-muted extra-small">Filter Dues <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Shop Service Charge Due (Commercial Units) -->
    <div class="col-kpi-5 col-xl col-lg-4 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-indigo h-100 bg-white" onclick="setDueTypeFilter('shop_service_due')" style="cursor: pointer; border-left: 4px solid #6366f1 !important;" title="Click to filter Commercial Shop Service Charge Dues">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Shop Service Due</span>
                    <span class="avatar avatar-xs rounded-circle d-flex align-items-center justify-content-center" style="background:#e0e7ff; color:#4f46e5;">
                        <i class="fa-solid fa-store"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1" style="color:#4f46e5;" id="statShopServiceDue">
                    Tk. <?= number_format($shopDue) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                    <span>Paid: <strong id="statShopServicePaidText">Tk. <?= formatDueCardAmount($shopPaid) ?></strong></span>
                    <span class="badge stat-pill" style="background:#e0e7ff; color:#4338ca;" id="statShopServiceTargetBadge" title="Target: Tk. <?= formatDueCardAmount($shopTarget) ?>">
                        Target: Tk. <?= formatDueCardAmount($shopTarget) ?>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted fs-11 pt-1 border-top">
                    <span>Due: <strong style="color:#4f46e5;" id="statShopServiceDueText">Tk. <?= formatDueCardAmount($shopDue) ?></strong></span>
                    <span class="text-muted extra-small">Filter Dues <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Total EMI / Construction Due -->
    <div class="col-kpi-5 col-xl col-lg-4 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-primary h-100 bg-white" onclick="setDueTypeFilter('emi_due')" style="cursor: pointer;" title="Click to filter EMI & Construction Dues">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Construction / EMI Due</span>
                    <span class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-building-columns"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-primary mb-1" id="statEmiDue">
                    Tk. <?= number_format($stats['total_emi_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                    <span>Paid: <strong id="statEmiPaidText">Tk. <?= formatDueCardAmount($stats['total_emi_paid'] ?? 0) ?></strong></span>
                    <span class="badge bg-primary-subtle text-primary stat-pill" id="statConstructionTargetBadge" title="Target: Tk. <?= formatDueCardAmount($stats['total_construction_target'] ?? 0) ?>">
                        Target: Tk. <?= formatDueCardAmount($stats['total_construction_target'] ?? 0) ?>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted fs-11 pt-1 border-top">
                    <?php if (($stats['total_overdue_emi'] ?? 0) > 0): ?>
                        <span class="badge bg-danger text-white stat-pill" id="statOverdueBadge" title="Matured installments overdue">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue: Tk. <?= formatDueCardAmount($stats['total_overdue_emi'] ?? 0) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success stat-pill" id="statOverdueBadge">No Overdues</span>
                    <?php endif; ?>
                    <span class="text-muted extra-small">Filter Dues <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- ══ ROW 2: EMI PAYMENT ANALYTICS & BREAKDOWN CARDS ══════ -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="col-12 mb-1 mt-1">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-1">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-pie text-success fs-6"></i>
                <span class="text-uppercase fw-bold text-dark fs-12 tracking-wider">EMI Collection & Installment Status</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-11">
                    <i class="fa-solid fa-calendar me-1"></i>Current Month: <?= date('M Y') ?>
                </span>
            </div>
            <span class="text-muted extra-small fs-11">
                <i class="fa-solid fa-circle-info me-1"></i>Click any card to view detailed client breakdown
            </span>
        </div>
    </div>

    <!-- 1. Total EMI Paid -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card emi-stat-card border-start border-4 border-success h-100 bg-white" onclick="openEmiAnalyticsModal('all')" title="Click to view all clients who paid EMIs">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Total EMI Paid</span>
                    <span class="avatar avatar-xs bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-receipt"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-success mb-1" id="statTotalEmiPaid">
                    Tk. <?= number_format($emiAnalytics['total_emi_paid'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>Clients: <strong id="statTotalEmiClients"><?= number_format($emiAnalytics['total_emi_clients'] ?? 0) ?></strong></span>
                    <span class="badge bg-success-subtle text-success stat-pill">
                        Breakdown <i class="fa-solid fa-arrow-right ms-1"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Regular EMI Paid (Up to Current Month) -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card emi-stat-card border-start border-4 border-primary h-100 bg-white" onclick="openEmiAnalyticsModal('regular')" title="Click to view regular EMI payers up to current month">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Regular EMI Paid</span>
                    <span class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-calendar-check"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-primary mb-1" id="statRegularEmiPaid">
                    Tk. <?= number_format($emiAnalytics['regular_emi_paid'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>Up to <strong><?= date('M Y') ?></strong> (<strong id="statRegularEmiClients"><?= number_format($emiAnalytics['regular_emi_clients'] ?? 0) ?></strong>)</span>
                    <span class="badge bg-primary-subtle text-primary stat-pill">
                        Current <i class="fa-solid fa-arrow-right ms-1"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Advance EMI Paid -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card emi-stat-card border-start border-4 h-100 bg-white" onclick="openEmiAnalyticsModal('advance')" style="border-left-color: #0d9488 !important;" title="Click to view clients who paid installments in advance">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Advance EMI Paid</span>
                    <span class="avatar avatar-xs rounded-circle d-flex align-items-center justify-content-center" style="background-color: #ccfbf1; color: #0d9488;">
                        <i class="fa-solid fa-forward"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1" style="color: #0d9488;" id="statAdvanceEmiPaid">
                    Tk. <?= number_format($emiAnalytics['advance_emi_paid'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>Ahead of Schedule (<strong id="statAdvanceEmiClients"><?= number_format($emiAnalytics['advance_emi_clients'] ?? 0) ?></strong>)</span>
                    <span class="badge stat-pill" style="background-color: #ccfbf1; color: #0d9488;">
                        Advance <i class="fa-solid fa-arrow-right ms-1"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Due / Overdue EMI -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card emi-stat-card border-start border-4 border-warning h-100 bg-white" onclick="openEmiAnalyticsModal('due')" title="Click to view clients with overdue EMI amounts">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Due EMI (Overdue)</span>
                    <span class="avatar avatar-xs bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-warning mb-1" id="statDueEmiAmount">
                    Tk. <?= number_format($emiAnalytics['due_emi_amount'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>Clients with Dues: (<strong id="statDueEmiClients"><?= number_format($emiAnalytics['due_emi_clients'] ?? 0) ?></strong>)</span>
                    <span class="badge bg-warning-subtle text-warning stat-pill">
                        Dues List <i class="fa-solid fa-arrow-right ms-1"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PROJECT-WISE DUES SUMMARY GRID ══ -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white due-project-card">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-diagram-project text-primary fs-5"></i>
                    <h6 class="fw-bold mb-0 text-dark">Project-wise Due Breakdown</h6>
                    <span class="badge bg-light text-secondary border fs-11"><?= count($projectBreakdown ?? []) ?> Active Projects</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="position-relative" style="width: 190px;">
                        <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 11px;"></i>
                        <input type="text" id="projectChipSearch" class="form-control form-control-sm ps-4" 
                               placeholder="Search <?= count($projectBreakdown ?? []) ?> projects..." 
                               oninput="filterProjectChips(this.value)" 
                               style="height: 30px; border-radius: 6px; font-size: 12px;">
                    </div>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fs-12" id="clearProjectFilterBtn" onclick="selectProjectFilter('')" style="<?= empty($selectedProjectId) ? 'display:none;' : '' ?>">
                        <i class="fa-solid fa-filter-circle-xmark me-1"></i> Show All Projects
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 fs-12" data-bs-toggle="modal" data-bs-target="#projectBreakdownModal">
                        <i class="fa-solid fa-table me-1"></i> View Full Table
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-2" id="projectChipsContainer">
                    <?php if (!empty($projectBreakdown)): ?>
                        <?php foreach ($projectBreakdown as $idx => $pb): 
                            $isPbActive = ((int)$pb['project_id'] === (int)($selectedProjectId ?? 0));
                        ?>
                            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 project-chip-item" data-project-name="<?= strtolower(htmlspecialchars($pb['project_name'])) ?>">
                                <div class="p-3 project-chip <?= $isPbActive ? 'active border-primary' : '' ?>" onclick="selectProjectFilter(<?= $pb['project_id'] ?>)" title="Click to filter by <?= htmlspecialchars($pb['project_name']) ?>">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div class="pe-2">
                                            <strong class="text-truncate d-inline-block text-dark fs-13" style="max-width: 140px;" title="<?= htmlspecialchars($pb['project_name']) ?>">
                                                <?= htmlspecialchars($pb['project_name']) ?>
                                            </strong>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill fs-11 d-block mb-1" title="Total Booked Clients in <?= htmlspecialchars($pb['project_name']) ?>">
                                                <i class="fa-solid fa-users me-1"></i><?= $pb['total_clients'] ?> Clients
                                            </span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-11 registered-client-chip-btn d-inline-flex align-items-center gap-1"
                                                  onclick="event.stopPropagation(); openRegisteredClientsModal(<?= $pb['project_id'] ?>, '<?= htmlspecialchars(addslashes($pb['project_name'])) ?>')"
                                                  style="cursor: pointer;"
                                                  title="Click to view <?= (int)($pb['registered_clients_count'] ?? 0) ?> verified registered clients who completed land share">
                                                <i class="fa-solid fa-file-circle-check"></i>
                                                <span><?= (int)($pb['registered_clients_count'] ?? 0) ?> Reg. Complete</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 8px;"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-baseline justify-content-between text-muted fs-11 mt-1">
                                        <span>Overall Due:</span>
                                        <strong class="text-danger fs-12">Tk. <?= number_format($pb['overall_due']) ?></strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top" style="font-size: 10px; color: #6c757d;">
                                        <span title="Land Share Due">Land: <strong>Tk. <?= number_format($pb['land_share_due']) ?></strong></span>
                                        <span title="Development & Shop Charge Due">Dev: <strong>Tk. <?= number_format((float)($pb['dev_charge_due'] ?? 0) + (float)($pb['shop_service_charge_due'] ?? 0)) ?></strong></span>
                                        <span title="EMI / Cons Due">EMI: <strong>Tk. <?= number_format($pb['emi_due']) ?></strong></span>
                                        <span title="Collection ratio" class="badge bg-success-subtle text-success"><?= $pb['collection_ratio'] ?>% Rec</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted py-3">No project data available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MAIN CLIENT DUE TRACKING SECTION ══ -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 bg-white due-main-card">
            
            <!-- Card Header: Title & Quick Counts -->
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-users-viewfinder text-primary"></i>
                        <span>Client-wise Payment & Due Tracker</span>
                        <span class="badge bg-primary rounded-pill px-2 py-1 fs-12" id="dueRecordCountBadge">
                            <span id="dueTotalCount"><?= number_format($totalClients ?? 0) ?></span> Clients
                        </span>
                        <?php if (!empty($selectedProjectName)): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle fs-12">
                                <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($selectedProjectName) ?>
                            </span>
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted">Displaying Land Share Due (Registration + Booking), Development Charge Due, EMI Due, and Overall Due per client</small>
                </div>

                <!-- Quick Due Status Filter Pills -->
                <div class="d-flex flex-wrap align-items-center gap-1 no-print">
                    <button type="button" class="due-type-pill <?= ($dueType === 'all') ? 'active' : '' ?>" onclick="setDueTypeFilter('all')">
                        All Clients
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'land_share_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('land_share_due')">
                        <i class="fa-solid fa-map-location-dot me-1"></i> Land Share Due
                    </button>
                    <button type="button" class="due-type-pill <?= in_array($dueType, ['land_share_cleared', 'registered']) ? 'active' : '' ?>" onclick="setDueTypeFilter('land_share_cleared')">
                        <i class="fa-solid fa-file-circle-check me-1 text-success"></i> Land Share Complete (Reg.)
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'dev_charge_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('dev_charge_due')">
                        <i class="fa-solid fa-screwdriver-wrench me-1"></i> Dev Charge Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'shop_service_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('shop_service_due')">
                        <i class="fa-solid fa-store me-1"></i> Shop Service Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'emi_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('emi_due')">
                        <i class="fa-solid fa-building-columns me-1"></i> EMI Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'overall_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('overall_due')">
                        <i class="fa-solid fa-circle-exclamation me-1 text-danger"></i> Has Any Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'cleared') ? 'active' : '' ?>" onclick="setDueTypeFilter('cleared')">
                        <i class="fa-solid fa-circle-check me-1 text-success"></i> Fully Cleared
                    </button>
                </div>
            </div>

            <!-- Toolbar: Search, Project Dropdown, Date Range, Sort, Limit -->
            <div class="card-body p-3 border-bottom bg-light due-toolbar no-print">
                <form id="dueTrackingFilterForm" onsubmit="event.preventDefault(); loadDueTrackingPage(1);" class="row g-2 align-items-center">
                    
                    <!-- Search Input -->
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12">
                        <div class="position-relative">
                            <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; z-index: 4;"></i>
                            <input type="text" id="dueSearchInput" class="form-control form-control-sm ps-5" 
                                   placeholder="Search by client, phone, email, unit..." 
                                   value="<?= htmlspecialchars($search ?? '') ?>" autocomplete="off"
                                   style="height: 38px; border-radius: 8px;">
                            <button type="button" id="dueClearSearch" class="btn btn-sm btn-link text-muted position-absolute p-0 <?= empty($search) ? 'd-none' : '' ?>" 
                                    style="right: 10px; top: 50%; transform: translateY(-50%); text-decoration: none; z-index: 4;" 
                                    onclick="clearDueSearch()" title="Clear Search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Project Filter Dropdown (Searchable Select2) -->
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12">
                        <select class="form-control form-control-sm select2" id="dueProjectFilter" style="width: 100%;">
                            <option value="">All Projects (All Sites)</option>
                            <?php if (!empty($projects)): foreach ($projects as $p): 
                                $prjId = $p['id'] ?? $p['project_id'] ?? '';
                                $prjName = $p['name'] ?? $p['project_name'] ?? '';
                                if ($prjId === '' || $prjName === '') continue;
                            ?>
                                <option value="<?= $prjId ?>" <?= ((int)($selectedProjectId ?? 0) === (int)$prjId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prjName) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>

                    <!-- Sort By Selector -->
                    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
                        <select class="form-select form-select-sm" id="dueSortBy" onchange="loadDueTrackingPage(1);" style="height: 38px; border-radius: 8px;">
                            <option value="overall_due" <?= (($sortBy ?? '') === 'overall_due') ? 'selected' : '' ?>>Sort: Overall Due (High-Low)</option>
                            <option value="land_share_due" <?= (($sortBy ?? '') === 'land_share_due') ? 'selected' : '' ?>>Sort: Land Share Due</option>
                            <option value="dev_charge_due" <?= (($sortBy ?? '') === 'dev_charge_due') ? 'selected' : '' ?>>Sort: Dev Charge Due</option>
                            <option value="shop_service_charge_due" <?= (($sortBy ?? '') === 'shop_service_charge_due') ? 'selected' : '' ?>>Sort: Shop Service Due</option>
                            <option value="remaining_emi_due" <?= (($sortBy ?? '') === 'remaining_emi_due') ? 'selected' : '' ?>>Sort: EMI Due</option>
                            <option value="total_paid" <?= (($sortBy ?? '') === 'total_paid') ? 'selected' : '' ?>>Sort: Total Paid</option>
                            <option value="booking_id" <?= (($sortBy ?? '') === 'booking_id') ? 'selected' : '' ?>>Sort: Recent Booking ID</option>
                            <option value="client_name" <?= (($sortBy ?? '') === 'client_name') ? 'selected' : '' ?>>Sort: Client Name</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="col-xxl-3 col-xl-3 col-lg-8 col-md-8 col-sm-12">
                        <div class="d-flex align-items-center gap-1">
                            <select id="dueDateType" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; width: 110px;">
                                <option value="booking_date" <?= (($dateType ?? '') === 'booking_date') ? 'selected' : '' ?>>Booking</option>
                                <option value="last_payment_date" <?= (($dateType ?? '') === 'last_payment_date') ? 'selected' : '' ?>>Payment</option>
                            </select>
                            <input type="date" id="dueStartDate" class="form-control form-control-sm" style="height: 38px; border-radius: 8px;" value="<?= htmlspecialchars($startDate ?? '') ?>" title="From Date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="dueEndDate" class="form-control form-control-sm" style="height: 38px; border-radius: 8px;" value="<?= htmlspecialchars($endDate ?? '') ?>" title="To Date">
                        </div>
                    </div>

                    <!-- Per Page Limit & Reset -->
                    <div class="col-xxl-1 col-xl-1 col-lg-4 col-md-4 col-sm-6 d-flex align-items-center gap-1 justify-content-end">
                        <select id="duePageLimit" class="form-select form-select-sm" onchange="loadDueTrackingPage(1);" style="height: 38px; border-radius: 8px; width: 85px;" title="Entries per page">
                            <option value="10" <?= ((string)($limit ?? 20) === '10') ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= ((string)($limit ?? 20) === '20') ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= ((string)($limit ?? 20) === '50') ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= ((string)($limit ?? 20) === '100') ? 'selected' : '' ?>>100</option>
                            <option value="all" <?= (($limit ?? '') === 'all' || (int)($limit ?? 20) >= 5000) ? 'selected' : '' ?>>All</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetAllDueFilters()" title="Reset Filters" style="height: 38px; width: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>

                </form>
            </div>

            <!-- Table Responsive Container -->
            <div class="table-responsive" style="min-height: 350px;">
                <table class="table table-hover table-due mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 85px;">ID & Date</th>
                            <th style="min-width: 180px;">Client Info</th>
                            <th style="min-width: 160px;">Project & Unit</th>
                            <th class="text-end" style="width: 130px;">Flat Value (Tk.)</th>
                            <th class="text-end" style="width: 140px;">Land Share Due (Tk.)</th>
                            <th class="text-end" style="width: 140px;">Dev Charge Due (Tk.)</th>
                            <th class="text-end" style="width: 135px;">Shop Svc Due (Tk.)</th>
                            <th class="text-end" style="width: 140px;">Due EMI (Tk.)</th>
                            <th class="text-end" style="width: 145px;">Overall Due (Tk.)</th>
                            <th class="text-center no-print" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dueTrackingTableBody">
                        <?= (new AccountClientController($GLOBALS['db'] ?? null))->renderDueTrackingRowsHtml($clients ?? []) ?>
                    </tbody>
                    <tfoot id="dueTrackingTableFoot" style="border-top: 2px solid #64748b; background-color: #f1f5f9;">
                        <?= (new AccountClientController($GLOBALS['db'] ?? null))->renderDueTrackingFooterHtml($clients ?? [], $stats ?? [], ($limit ?? 20) === 'all' || (int)($limit ?? 20) >= 5000) ?>
                    </tfoot>
                </table>
            </div>

            <!-- Table Footer: Pagination & Info -->
            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="text-muted fs-13" id="dueInfoText">
                    <?php 
                        $isAllLimit = (($limit ?? '') === 'all' || (int)($limit ?? 20) >= 5000);
                        if ($isAllLimit) {
                            $startEntry = ($totalClients ?? 0) > 0 ? 1 : 0;
                            $endEntry = $totalClients ?? 0;
                            $infoText = "Showing all " . number_format($totalClients ?? 0) . " client bookings";
                        } else {
                            $numericLimit = (int)($limit ?? 20);
                            if ($numericLimit <= 0) $numericLimit = 20;
                            $startEntry = ($totalClients ?? 0) > 0 ? ((($currentPage ?? 1) - 1) * $numericLimit) + 1 : 0;
                            $endEntry = min($totalClients ?? 0, ($currentPage ?? 1) * $numericLimit);
                            $infoText = "Showing {$startEntry} to {$endEntry} of " . number_format($totalClients ?? 0) . " client bookings";
                        }
                    ?>
                    <?= $infoText ?>
                </div>
                <div id="duePaginationContainer" class="no-print">
                    <?php if (!$isAllLimit): ?>
                        <?= (new AccountClientController($GLOBALS['db'] ?? null))->generateAjaxPagination($currentPage ?? 1, $totalPages ?? 1, $totalClients ?? 0, (int)($limit ?? 20), 'loadDueTrackingPage') ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ══ MODAL 0: OVERALL OUTSTANDING DUE & FINANCIAL BREAKDOWN ══ -->
<div class="modal fade" id="overallDueSummaryModal" tabindex="-1" aria-labelledby="overallDueModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #0f172a, #1e293b); border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:42px; height:42px;">
                        <i class="fa-solid fa-chart-pie fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="overallDueModalTitle">Overall Outstanding Due & Collections Breakdown</h5>
                        <small class="text-white-50">4-Pillar Financial Reconciliation: Land Share, Development, Shop Service, and Construction/EMI</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- 3 Top Master Cards -->
                <div class="row  mb-4">
                    <!-- Total Target (Booked Value) -->
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded-3 border border-start border-4 border-info shadow-sm h-100">
                            <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                                <span class="text-uppercase fw-bold">Total Target (Booked Value)</span>
                                <i class="fa-solid fa-bullseye text-info fs-6"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1" id="mTotTarget">Tk. <?= formatDueCardAmount($stats['total_value'] ?? 0) ?></h4>
                            <div class="extra-small text-muted" id="mTotTargetFull">Exact: Tk. <?= number_format($stats['total_value'] ?? 0) ?></div>
                            <div class="extra-small text-info mt-1"><i class="fa-solid fa-users me-1"></i><?= number_format($stats['total_clients'] ?? 0) ?> Booked Client Units</div>
                        </div>
                    </div>

                    <!-- Total Paid (Collected) -->
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded-3 border border-start border-4 border-success shadow-sm h-100">
                            <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                                <span class="text-uppercase fw-bold">Total Paid (Collected)</span>
                                <i class="fa-solid fa-circle-check text-success fs-6"></i>
                            </div>
                            <h4 class="fw-bold text-success mb-1" id="mTotPaid">Tk. <?= formatDueCardAmount($stats['total_paid'] ?? 0) ?></h4>
                            <div class="extra-small text-muted" id="mTotPaidFull">Exact: Tk. <?= number_format($stats['total_paid'] ?? 0) ?></div>
                            <div class="extra-small text-success mt-1">
                                <i class="fa-solid fa-chart-line me-1"></i><?= ($stats['total_value'] ?? 0) > 0 ? round((($stats['total_paid'] ?? 0) / ($stats['total_value'] ?? 1)) * 100, 1) : 0 ?>% Recovery Rate
                            </div>
                        </div>
                    </div>

                    <!-- Total Outstanding Due -->
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded-3 border border-start border-4 border-danger shadow-sm h-100">
                            <div class="d-flex align-items-center justify-content-between text-muted fs-12 mb-1">
                                <span class="text-uppercase fw-bold">Total Outstanding Due</span>
                                <i class="fa-solid fa-circle-exclamation text-danger fs-6"></i>
                            </div>
                            <h4 class="fw-bold text-danger mb-1" id="mTotDue">Tk. <?= formatDueCardAmount($stats['overall_due'] ?? 0) ?></h4>
                            <div class="extra-small text-muted" id="mTotDueFull">Exact: Tk. <?= number_format($stats['overall_due'] ?? 0) ?></div>
                            <div class="extra-small text-danger mt-1">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i><?= ($stats['total_value'] ?? 0) > 0 ? round((($stats['overall_due'] ?? 0) / ($stats['total_value'] ?? 1)) * 100, 1) : 0 ?>% Remaining Due
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 Pillars Breakdown Table -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-layer-group text-primary me-2"></i>Component Breakdown (How Amounts Formed)
                        </h6>
                        <span class="badge bg-light text-secondary border fs-11">Verified Accounting</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 220px;">Component / Pillar</th>
                                    <th class="text-end" style="min-width: 140px;">Target Amount</th>
                                    <th class="text-end" style="min-width: 140px;">Paid Amount</th>
                                    <th class="text-end" style="min-width: 140px;">Outstanding Due</th>
                                    <th class="text-center" style="width: 110px;">Recovery %</th>
                                    <th class="text-center" style="width: 130px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="overallDueBreakdownTableBody">
                                <!-- Row 1: Land Share -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                                                <i class="fa-solid fa-map-location-dot"></i>
                                            </span>
                                            <div>
                                                <strong class="text-dark d-block">1. Land Share</strong>
                                                <small class="text-muted extra-small">Booking Money + Land Registration Fee</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold" id="mRowLandTarget">Tk. <?= number_format($stats['total_land_share_target'] ?? 0) ?></td>
                                    <td class="text-end text-success fw-semibold" id="mRowLandPaid">Tk. <?= number_format($stats['total_land_share_paid'] ?? 0) ?></td>
                                    <td class="text-end text-danger fw-bold" id="mRowLandDue">Tk. <?= number_format($stats['total_land_share_due'] ?? 0) ?></td>
                                    <td class="text-center" id="mRowLandRec">
                                        <span class="badge bg-warning-subtle text-dark">
                                            <?= ($stats['total_land_share_target'] ?? 0) > 0 ? round((($stats['total_land_share_paid'] ?? 0) / ($stats['total_land_share_target'] ?? 1)) * 100, 1) : 0 ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-outline-warning text-dark py-1 px-2 fs-11" onclick="filterByDueTypeFromModal('land_share_due')">
                                            <i class="fa-solid fa-filter me-1"></i>Filter Due
                                        </button>
                                    </td>
                                </tr>

                                <!-- Row 2: Development Charges -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                            </span>
                                            <div>
                                                <strong class="text-dark d-block">2. Development Charges</strong>
                                                <small class="text-muted extra-small">Residential Flats Utility & Site Development</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold" id="mRowDevTarget">Tk. <?= number_format($stats['total_dev_charges_target'] ?? 0) ?></td>
                                    <td class="text-end text-success fw-semibold" id="mRowDevPaid">Tk. <?= number_format($stats['total_dev_charges_paid'] ?? 0) ?></td>
                                    <td class="text-end text-danger fw-bold" id="mRowDevDue">Tk. <?= number_format($stats['total_dev_charge_due'] ?? 0) ?></td>
                                    <td class="text-center" id="mRowDevRec">
                                        <span class="badge bg-info-subtle text-info">
                                            <?= ($stats['total_dev_charges_target'] ?? 0) > 0 ? round((($stats['total_dev_charges_paid'] ?? 0) / ($stats['total_dev_charges_target'] ?? 1)) * 100, 1) : 0 ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-outline-info py-1 px-2 fs-11" onclick="filterByDueTypeFromModal('dev_charge_due')">
                                            <i class="fa-solid fa-filter me-1"></i>Filter Due
                                        </button>
                                    </td>
                                </tr>

                                <!-- Row 3: Shop Service Charges -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs rounded-circle d-flex align-items-center justify-content-center" style="background:#e0e7ff; color:#4338ca;">
                                                <i class="fa-solid fa-store"></i>
                                            </span>
                                            <div>
                                                <strong class="text-dark d-block">3. Shop Service Charges</strong>
                                                <small class="text-muted extra-small">Commercial Units Maintenance & Service Fee</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold" id="mRowShopTarget">Tk. <?= number_format($stats['total_shop_service_charge_target'] ?? 0) ?></td>
                                    <td class="text-end text-success fw-semibold" id="mRowShopPaid">Tk. <?= number_format($stats['total_shop_service_charge_paid'] ?? 0) ?></td>
                                    <td class="text-end text-danger fw-bold" id="mRowShopDue">Tk. <?= number_format($stats['total_shop_service_charge_due'] ?? 0) ?></td>
                                    <td class="text-center" id="mRowShopRec">
                                        <span class="badge bg-light text-secondary border">
                                            <?= ($stats['total_shop_service_charge_target'] ?? 0) > 0 ? round((($stats['total_shop_service_charge_paid'] ?? 0) / ($stats['total_shop_service_charge_target'] ?? 1)) * 100, 1) : 0 ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-outline-indigo py-1 px-2 fs-11" style="color:#4f46e5; border-color:#c7d2fe;" onclick="filterByDueTypeFromModal('shop_service_due')">
                                            <i class="fa-solid fa-filter me-1"></i>Filter Due
                                        </button>
                                    </td>
                                </tr>

                                <!-- Row 4: Construction & EMI -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                                <i class="fa-solid fa-building-columns"></i>
                                            </span>
                                            <div>
                                                <strong class="text-dark d-block">4. Construction Fee & EMI</strong>
                                                <small class="text-muted extra-small">Monthly Installment Schedules</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold" id="mRowConsTarget">Tk. <?= number_format($stats['total_construction_target'] ?? 0) ?></td>
                                    <td class="text-end text-success fw-semibold" id="mRowConsPaid">Tk. <?= number_format($stats['total_emi_paid'] ?? 0) ?></td>
                                    <td class="text-end text-danger fw-bold" id="mRowConsDue">Tk. <?= number_format($stats['total_emi_due'] ?? 0) ?></td>
                                    <td class="text-center" id="mRowConsRec">
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?= ($stats['total_construction_target'] ?? 0) > 0 ? round((($stats['total_emi_paid'] ?? 0) / ($stats['total_construction_target'] ?? 1)) * 100, 1) : 0 ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 fs-11" onclick="filterByDueTypeFromModal('emi_due')">
                                            <i class="fa-solid fa-filter me-1"></i>Filter Due
                                        </button>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Summary Footer Row in Table -->
                            <tfoot class="table-light border-top border-2">
                                <tr>
                                    <th class="text-dark fw-bold">TOTAL RECONCILIATION</th>
                                    <th class="text-end text-dark fw-bold" id="mFooterTfootTarget">Tk. <?= number_format($stats['total_value'] ?? 0) ?></th>
                                    <th class="text-end text-success fw-bold" id="mFooterTfootPaid">Tk. <?= number_format($stats['total_paid'] ?? 0) ?></th>
                                    <th class="text-end text-danger fw-bold" id="mFooterTfootDue">Tk. <?= number_format($stats['overall_due'] ?? 0) ?></th>
                                    <th class="text-center" id="mFooterTfootRec">
                                        <span class="badge bg-success text-white">
                                            <?= ($stats['total_value'] ?? 0) > 0 ? round((($stats['total_paid'] ?? 0) / ($stats['total_value'] ?? 1)) * 100, 1) : 0 ?>%
                                        </span>
                                    </th>
                                    <th class="text-center">
                                        <button type="button" class="btn btn-xs btn-secondary py-1 px-2 fs-11" onclick="filterByDueTypeFromModal('all')">
                                            Reset Filter
                                        </button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Bottom Reconciliation Summary Cards -->
                <div class="row g-2">
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 border rounded-3 bg-white text-center shadow-sm">
                            <span class="text-uppercase fw-bold text-muted extra-small d-block mb-1">Total Target Amount</span>
                            <h5 class="fw-bold text-dark mb-0" id="mFooterTarget">Tk. <?= formatDueCardAmount($stats['total_value'] ?? 0) ?></h5>
                            <small class="text-muted extra-small d-block mt-1" id="mFooterTargetExact">Exact: Tk. <?= number_format($stats['total_value'] ?? 0) ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 border rounded-3 bg-white text-center shadow-sm">
                            <span class="text-uppercase fw-bold text-muted extra-small d-block mb-1">Total Paid Value</span>
                            <h5 class="fw-bold text-success mb-0" id="mFooterPaid">Tk. <?= formatDueCardAmount($stats['total_paid'] ?? 0) ?></h5>
                            <small class="text-muted extra-small d-block mt-1" id="mFooterPaidExact">Exact: Tk. <?= number_format($stats['total_paid'] ?? 0) ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 border rounded-3 bg-white text-center shadow-sm">
                            <span class="text-uppercase fw-bold text-muted extra-small d-block mb-1">Total Outstanding Due</span>
                            <h5 class="fw-bold text-danger mb-0" id="mFooterDue">Tk. <?= formatDueCardAmount($stats['overall_due'] ?? 0) ?></h5>
                            <small class="text-muted extra-small d-block mt-1" id="mFooterDueExact">Exact: Tk. <?= number_format($stats['overall_due'] ?? 0) ?></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 border rounded-3 bg-white text-center shadow-sm">
                            <span class="text-uppercase fw-bold text-muted extra-small d-block mb-1">Active Booked Units</span>
                            <h5 class="fw-bold text-primary mb-0" id="mFooterUnits"><?= number_format($stats['total_clients'] ?? 0) ?> Bookings</h5>
                            <small class="text-muted extra-small d-block mt-1">Across All Projects</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <span class="text-muted extra-small">
                    <i class="fa-solid fa-circle-check text-success me-1"></i>All figures derived from verified transaction records.
                </span>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL 1: QUICK CLIENT DUE BREAKDOWN ══ -->
<div class="modal fade" id="clientDueBreakdownModal" tabindex="-1" aria-labelledby="clientDueModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 bg-primary text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar fs-5"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="clientDueModalTitle">Client Due Breakdown</h5>
                        <small class="text-white-50" id="clientDueModalSubtitle">Booking Financial Breakdown & Dues</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="clientDueModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted mb-0">Loading client financial breakdown...</p>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <a href="#" id="clientDueModalLedgerBtn" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Full Ledger
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL 2: FULL PROJECT BREAKDOWN TABLE ══ -->
<div class="modal fade" id="projectBreakdownModal" tabindex="-1" aria-labelledby="projModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-building-circle-check text-warning fs-5"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="projModalTitle">All Projects Due Breakdown</h5>
                        <small class="text-white-50">Comparative Land Share, Dev Charge, EMI, and Overall Dues across all projects</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Project Name</th>
                                <th class="text-center">Total Clients</th>
                                <th class="text-center text-success">Reg. Complete</th>
                                <th class="text-end">Total Value</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end text-danger">Land Share Due</th>
                                <th class="text-end text-danger">Dev Charge Due</th>
                                <th class="text-end text-danger">EMI Due</th>
                                <th class="text-end text-danger fw-bold">Overall Due</th>
                                <th class="text-center">Recovery</th>
                                <th class="text-center">Filter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totPbProjects = count($projectBreakdown ?? []);
                            $totPbClients = 0;
                            $totPbRegClients = 0;
                            $totPbValue = 0;
                            $totPbPaid = 0;
                            $totPbLandDue = 0;
                            $totPbDevDue = 0;
                            $totPbEmiDue = 0;
                            $totPbOverallDue = 0;

                            if (!empty($projectBreakdown)): 
                                $pIdx = 1;
                                foreach ($projectBreakdown as $pRow): 
                                    $totPbClients += (int)($pRow['total_clients'] ?? 0);
                                    $totPbRegClients += (int)($pRow['registered_clients_count'] ?? 0);
                                    $totPbValue += (float)($pRow['total_project_value'] ?? 0);
                                    $totPbPaid += (float)($pRow['total_paid'] ?? 0);
                                    $totPbLandDue += (float)($pRow['land_share_due'] ?? 0);
                                    $totPbDevDue += (float)($pRow['dev_charge_due'] ?? 0) + (float)($pRow['shop_service_charge_due'] ?? 0);
                                    $totPbEmiDue += (float)($pRow['emi_due'] ?? 0);
                                    $totPbOverallDue += (float)($pRow['overall_due'] ?? 0);
                                ?>
                                    <tr>
                                        <td><?= $pIdx++ ?></td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($pRow['project_name']) ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill"><?= $pRow['total_clients'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 fw-semibold" 
                                                    onclick="openRegisteredClientsModal(<?= $pRow['project_id'] ?>, '<?= htmlspecialchars(addslashes($pRow['project_name'])) ?>'); bootstrap.Modal.getInstance(document.getElementById('projectBreakdownModal')).hide();"
                                                    title="View <?= (int)($pRow['registered_clients_count'] ?? 0) ?> Registered Clients (Land Share Complete)">
                                                <i class="fa-solid fa-file-circle-check me-1"></i><?= (int)($pRow['registered_clients_count'] ?? 0) ?>
                                            </button>
                                        </td>
                                        <td class="text-end">Tk. <?= number_format($pRow['total_project_value']) ?></td>
                                        <td class="text-end text-success">Tk. <?= number_format($pRow['total_paid']) ?></td>
                                        <td class="text-end text-danger">Tk. <?= number_format($pRow['land_share_due']) ?></td>
                                        <td class="text-end text-danger" title="Dev: Tk. <?= number_format($pRow['dev_charge_due']) ?><?= ((float)($pRow['shop_service_charge_due'] ?? 0) > 0 ? ' | Shop: Tk. ' . number_format($pRow['shop_service_charge_due']) : '') ?>">Tk. <?= number_format((float)($pRow['dev_charge_due'] ?? 0) + (float)($pRow['shop_service_charge_due'] ?? 0)) ?></td>
                                        <td class="text-end text-danger">Tk. <?= number_format($pRow['emi_due']) ?></td>
                                        <td class="text-end text-danger fw-bold">Tk. <?= number_format($pRow['overall_due']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success"><?= $pRow['collection_ratio'] ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" 
                                                    onclick="selectProjectFilter(<?= $pRow['project_id'] ?>); bootstrap.Modal.getInstance(document.getElementById('projectBreakdownModal')).hide();">
                                                Select
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="12" class="text-center py-4 text-muted">No project records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php 
                        $totPbRecovery = $totPbValue > 0 ? round(($totPbPaid / $totPbValue) * 100, 1) : 0;
                        ?>
                        <tfoot class="table-light fw-bold" style="border-top: 2px solid #64748b; background-color: #f1f5f9; position: sticky; bottom: 0; z-index: 2; box-shadow: 0 -2px 6px rgba(0,0,0,0.06);">
                            <tr class="align-middle fw-bold">
                                <td class="text-center fw-bold fs-12 text-uppercase" style="color: #0f172a;">TOTAL</td>
                                <td>
                                    <strong class="text-dark fs-13"><?= number_format($totPbProjects) ?> Projects</strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill fs-12"><?= number_format($totPbClients) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success rounded-pill fs-12"><?= number_format($totPbRegClients) ?></span>
                                </td>
                                <td class="text-end fw-bold fs-13" style="font-variant-numeric: tabular-nums;">Tk. <?= number_format($totPbValue) ?></td>
                                <td class="text-end text-success fw-bold fs-13" style="font-variant-numeric: tabular-nums;">Tk. <?= number_format($totPbPaid) ?></td>
                                <td class="text-end text-danger fw-bold fs-13" style="font-variant-numeric: tabular-nums;">Tk. <?= number_format($totPbLandDue) ?></td>
                                <td class="text-end text-danger fw-bold fs-13" style="font-variant-numeric: tabular-nums;">Tk. <?= number_format($totPbDevDue) ?></td>
                                <td class="text-end text-danger fw-bold fs-13" style="font-variant-numeric: tabular-nums;">Tk. <?= number_format($totPbEmiDue) ?></td>
                                <td class="text-end text-danger fw-bold fs-14" style="font-variant-numeric: tabular-nums;">Tk. <?= number_format($totPbOverallDue) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success text-white fs-12"><?= $totPbRecovery ?>%</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fw-semibold" 
                                            onclick="selectProjectFilter(''); bootstrap.Modal.getInstance(document.getElementById('projectBreakdownModal')).hide();" title="Clear project filter">
                                        All
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: REGISTERED CLIENTS (LAND SHARE COMPLETED) ══ -->
<div class="modal fade" id="registeredClientsModal" tabindex="-1" aria-labelledby="regModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 bg-success text-white">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm bg-white text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:34px; height:34px;">
                        <i class="fa-solid fa-file-circle-check fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="regModalTitle">Registered Clients (Land Share Complete)</h5>
                        <small class="text-white-50" id="regModalSubtitle">Clients who have completed 100% verified Land Share payment (Booking + Registration)</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="regModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-success mb-2" role="status"></div>
                    <p class="text-muted mb-0">Loading registered clients data...</p>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <button type="button" id="regModalFilterBtn" class="btn btn-sm btn-outline-success fw-semibold">
                    <i class="fa-solid fa-filter me-1"></i> Filter These Clients in Main Table
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ═════════════════════════════════════════════════════════════ -->
<!-- ══ EMI ANALYTICS CLIENTS BREAKDOWN MODAL ═════════════════════ -->
<!-- ═════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="emiAnalyticsModal" tabindex="-1" aria-labelledby="emiAnalyticsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-chart-line"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="emiAnalyticsModalTitle">
                            EMI Collection & Status Breakdown
                        </h5>
                        <small class="text-white-50" id="emiAnalyticsModalSubtitle">
                            Detailed installment analysis for all active clients
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light py-1 px-3 fs-12" onclick="refreshEmiAnalyticsModal()">
                        <i class="fa-solid fa-arrows-rotate me-1"></i>Refresh
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Subheader: Tabs & Search Controls -->
            <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Category Tabs -->
                <div class="nav nav-pills emi-category-pills d-inline-flex bg-white p-1 rounded-3 border" role="tablist">
                    <button type="button" class="btn btn-sm nav-link active py-1 px-3 rounded-2 fw-semibold fs-12" id="tabEmiAll" onclick="switchEmiAnalyticsTab('all')">
                        <i class="fa-solid fa-receipt me-1 text-success"></i> All EMI Paid
                        <span class="badge bg-success-subtle text-success rounded-pill ms-1" id="badgeEmiAllCount">0</span>
                    </button>
                    <button type="button" class="btn btn-sm nav-link py-1 px-3 rounded-2 fw-semibold fs-12" id="tabEmiRegular" onclick="switchEmiAnalyticsTab('regular')">
                        <i class="fa-solid fa-calendar-check me-1 text-primary"></i> Regular (Current)
                        <span class="badge bg-primary-subtle text-primary rounded-pill ms-1" id="badgeEmiRegularCount">0</span>
                    </button>
                    <button type="button" class="btn btn-sm nav-link py-1 px-3 rounded-2 fw-semibold fs-12" id="tabEmiAdvance" onclick="switchEmiAnalyticsTab('advance')">
                        <i class="fa-solid fa-forward me-1" style="color: #0d9488;"></i> Advance EMI
                        <span class="badge rounded-pill ms-1" style="background-color: #ccfbf1; color: #0d9488;" id="badgeEmiAdvanceCount">0</span>
                    </button>
                    <button type="button" class="btn btn-sm nav-link py-1 px-3 rounded-2 fw-semibold fs-12" id="tabEmiDue" onclick="switchEmiAnalyticsTab('due')">
                        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Due / Overdue
                        <span class="badge bg-warning-subtle text-warning rounded-pill ms-1" id="badgeEmiDueCount">0</span>
                    </button>
                </div>

                <!-- Search and Project Dropdown in Modal -->
                <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end" style="max-width: 500px;">
                    <select id="emiModalProjectSelect" class="form-select form-select-sm" style="max-width: 200px; font-size: 12px;" onchange="handleEmiModalProjectChange(this.value)">
                        <option value="">All Projects</option>
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $prj): ?>
                                <option value="<?= $prj['id'] ?>" <?= ((int)($selectedProjectId ?? 0) === (int)$prj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <div class="position-relative flex-grow-1">
                        <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 11px;"></i>
                        <input type="text" id="emiModalSearchInput" class="form-control form-control-sm ps-4" 
                               placeholder="Search client, phone, flat..." 
                               oninput="filterEmiModalRows(this.value)" 
                               style="height: 32px; border-radius: 6px; font-size: 12px;">
                    </div>
                </div>
            </div>

            <!-- Modal Info Banner -->
            <div class="px-4 py-2 bg-body-tertiary border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted fs-12">
                <div>
                    <span id="emiModalStatusText" class="fw-semibold text-dark">Showing all clients who made EMI payments</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span>Total In View: <strong class="text-dark" id="emiModalTotalCount">0</strong> Clients</span>
                    <span class="border-start ps-3">Total Amount: <strong class="text-success" id="emiModalTotalAmount">Tk. 0</strong></span>
                </div>
            </div>

            <!-- Modal Body Table -->
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 520px;">
                    <table class="table table-hover align-middle mb-0 fs-12" id="emiModalTable">
                        <thead class="table-light sticky-top" style="z-index: 2;">
                            <tr class="text-uppercase fs-11 tracking-wider text-muted">
                                <th class="text-center" style="width: 80px;">#ID & Date</th>
                                <th style="min-width: 180px;">Client Info</th>
                                <th style="min-width: 150px;">Project & Unit</th>
                                <th style="min-width: 140px;">Paid Up To (Month)</th>
                                <th class="text-end" style="min-width: 120px;">Last EMI Paid</th>
                                <th class="text-end" style="min-width: 110px;">Regular Paid</th>
                                <th class="text-end" style="min-width: 110px;">Advance Paid</th>
                                <th class="text-end" style="min-width: 100px;">Due EMI</th>
                                <th class="text-center" style="min-width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="emiModalTableBody">
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="spinner-border text-primary mb-2" role="status"></div>
                                    <p class="text-muted mb-0">Loading EMI breakdown...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <span class="text-muted extra-small">
                    <i class="fa-solid fa-shield-halved me-1 text-success"></i>Only verified payments are counted. Current month: <strong><?= date('F Y') ?></strong>
                </span>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== CLIENT EMI FOLLOW UP MODAL ===== -->
<div class="modal fade" id="clientEmiFollowupModal" tabindex="-1" aria-labelledby="clientEmiFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); color:#fff; border:0; padding:16px 24px;">
                <h5 class="modal-title fw-bold" id="clientEmiFollowupModalLabel">
                    <i class="fa-light fa-comment-dots me-2"></i>EMI Follow Up Notes & Schedule — <span id="followup_modal_client_name" class="badge bg-white text-primary fs-14">Client</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Left Column: Add Follow Up Form -->
                    <div class="col-lg-5 border-end pe-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h6 class="text-primary mb-0 fw-bold"><i class="fa-light fa-plus-circle me-1"></i>Add New Follow Up Note</h6>
                            <span class="badge bg-primary-subtle text-primary extra-small">Follow-up Log</span>
                        </div>
                        <input type="hidden" id="modalFollowupBookingId">
                        
                        <div class="mb-3" id="modalFollowupProjectContainer">
                            <label class="form-label fs-12 fw-semibold text-muted mb-1">Project & Flat</label>
                            <select id="modalFollowupBookingSelect" class="form-select form-select-sm" onchange="onFollowupBookingChange()">
                                <!-- dynamically loaded -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-12 fw-semibold text-muted mb-1">Installment / EMI</label>
                            <select id="modalFollowupEmiSelect" class="form-select form-select-sm" onchange="loadClientEmiFollowups($('#modalFollowupBookingId').val())">
                                <option value="0">General / Overall Follow Up</option>
                                <option value="999">Development Charge</option>
                            </select>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Date of Activity <span class="text-danger">*</span></label>
                                <input type="date" id="modalFollowupActivityDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Mode of Activity <span class="text-danger">*</span></label>
                                <select id="modalFollowupActivityMode" class="form-select form-select-sm">
                                    <option value="">Select Mode</option>
                                    <option value="Phone Call" selected>Phone Call</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="SMS">SMS</option>
                                    <option value="Email">Email</option>
                                    <option value="Meeting">Meeting</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6" id="modalFollowupCallStatusGroup">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Call Status</label>
                                <select id="modalFollowupCallStatus" class="form-select form-select-sm">
                                    <option value="">Select Call Status</option>
                                    <option value="Outgoing" selected>Outgoing</option>
                                    <option value="Incoming">Incoming</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Reason for Activity <span class="text-danger">*</span></label>
                                <select id="modalFollowupActivityReason" class="form-select form-select-sm">
                                    <option value="">Select Reason</option>
                                    <option value="EMI Payment Follow-up" selected>EMI Payment Follow-up</option>
                                    <option value="Payment Reminder (General)">Payment Reminder (General)</option>
                                    <option value="Development Charge Follow-up">Development Charge Follow-up</option>
                                    <option value="Overdue Payment Follow-up">Overdue Payment Follow-up</option>
                                    <option value="Commitment Date Extension">Commitment Date Extension</option>
                                    <option value="Handover / Registration Discussion">Handover / Registration Discussion</option>
                                    <option value="Document Collection">Document Collection</option>
                                    <option value="Client Query / Issue Resolution">Client Query / Issue Resolution</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-12 fw-semibold text-muted mb-1">Discussion Note / Remarks <span class="text-danger">*</span></label>
                            <textarea id="modalFollowupNoteInput" class="form-control form-control-sm" rows="3" placeholder="Enter follow up discussion details, client feedback, or notes..."></textarea>
                        </div>

                        <!-- 2 New Optional Fields: Next Follow Up Date & Next Follow Up Note -->
                        <div class="p-3 mb-3 rounded-3" style="background:#f0f7ff; border:1px dashed #93c5fd;">
                            <div class="d-flex align-items-center gap-1 mb-2 text-primary fw-semibold fs-12">
                                <i class="fa-solid fa-calendar-clock"></i> Next Follow Up Planning (Optional)
                            </div>
                            <div class="mb-2">
                                <label class="form-label fs-12 fw-medium text-dark mb-1">Next Follow Up Date</label>
                                <input type="date" id="modalFollowupNextDate" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>">
                                <small class="text-muted extra-small">If scheduled, client appears in Today / Upcoming follow-up lists</small>
                            </div>
                            <div>
                                <label class="form-label fs-12 fw-medium text-dark mb-1">Next Follow Up Note / Promise</label>
                                <textarea id="modalFollowupNextNote" class="form-control form-control-sm" rows="2" placeholder="Next plan, commitment amount, or reminder note..."></textarea>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 shadow-sm py-2" id="saveFollowupBtn" onclick="saveClientEmiFollowup()">
                            <i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note
                        </button>
                    </div>
                    
                    <!-- Right Column: Follow Up History -->
                    <div class="col-lg-7 ps-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h6 class="text-dark mb-0 fw-bold"><i class="fa-light fa-clock-rotate-left me-1"></i>Previous Follow Up History</h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="modalFollowupHistoryCountBadge">0 notes</span>
                        </div>
                        <div id="modalFollowupHistoryContainer" style="max-height: 480px; overflow-y: auto; padding-right: 4px;">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <span class="text-muted extra-small"><i class="fa-solid fa-circle-info me-1"></i>Follow-ups update client engagement metrics and automated queues.</span>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ CLIENT DUE TRACKING JAVASCRIPT ══ -->
<script>
    window.base_url = '<?= $GLOBALS['base_url'] ?? '' ?>';
    let dueCurrentPage = <?= (int)($currentPage ?? 1) ?>;
    let dueActiveType = '<?= htmlspecialchars($dueType ?? 'all') ?>';
    let dueSearchTimer = null;

    // Open Overall Outstanding Due Breakdown Modal
    function openOverallDueSummaryModal() {
        const modalEl = document.getElementById('overallDueSummaryModal');
        if (!modalEl) return;
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Filter by Due Type directly from Modal and close modal
    function filterByDueTypeFromModal(type) {
        const modalEl = document.getElementById('overallDueSummaryModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
        setDueTypeFilter(type);
    }

    // Set Due Type filter tab
    function setDueTypeFilter(type) {
        dueActiveType = type;
        document.querySelectorAll('.due-type-pill').forEach(btn => btn.classList.remove('active'));
        const activeBtn = Array.from(document.querySelectorAll('.due-type-pill')).find(b => b.getAttribute('onclick') === `setDueTypeFilter('${type}')`);
        if (activeBtn) activeBtn.classList.add('active');
        const pfl = document.getElementById('printFilterLabel');
        if (pfl) pfl.innerText = type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        loadDueTrackingPage(1);
    }

    // Instant live search filter for project breakdown cards
    function filterProjectChips(query) {
        const q = (query || '').toLowerCase().trim();
        const items = document.querySelectorAll('.project-chip-item');

        items.forEach(item => {
            const name = item.getAttribute('data-project-name') || '';
            if (!q || name.includes(q)) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    }

    // Handle project filter changes (from select2 dropdown or chip clicks)
    function handleProjectFilterChange(projectId) {
        const pid = projectId ? String(projectId) : '';
        // Update chip active states
        document.querySelectorAll('.project-chip').forEach(chip => chip.classList.remove('active'));
        if (pid) {
            const activeChip = Array.from(document.querySelectorAll('.project-chip')).find(c => c.getAttribute('onclick') === `selectProjectFilter(${pid})`);
            if (activeChip) activeChip.classList.add('active');
            document.getElementById('clearProjectFilterBtn')?.style.removeProperty('display');
        } else {
            const clearBtn = document.getElementById('clearProjectFilterBtn');
            if (clearBtn) clearBtn.style.display = 'none';
        }
        loadDueTrackingPage(1);
    }

    // Select Project filter from breakdown chips or dropdown
    function selectProjectFilter(projectId) {
        const val = projectId ? String(projectId) : '';
        const select = document.getElementById('dueProjectFilter');
        if (select) {
            select.value = val;
            if (window.jQuery && $(select).data('select2')) {
                $(select).val(val).trigger('change.select2');
            }
        }
        handleProjectFilterChange(val);
    }

    // Clear search box
    function clearDueSearch() {
        const input = document.getElementById('dueSearchInput');
        if (input) input.value = '';
        document.getElementById('dueClearSearch')?.classList.add('d-none');
        loadDueTrackingPage(1);
    }

    // Reset all filters to default
    function resetAllDueFilters() {
        document.getElementById('dueSearchInput').value = '';
        const projSelect = document.getElementById('dueProjectFilter');
        if (projSelect) {
            projSelect.value = '';
            if (window.jQuery && $(projSelect).data('select2')) {
                $(projSelect).val('').trigger('change.select2');
            }
        }
        document.getElementById('dueDateType').value = 'booking_date';
        document.getElementById('dueStartDate').value = '';
        document.getElementById('dueEndDate').value = '';
        document.getElementById('dueSortBy').value = 'overall_due';
        document.getElementById('duePageLimit').value = '20';
        dueActiveType = 'all';
        document.querySelectorAll('.due-type-pill').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.due-type-pill')?.classList.add('active');
        document.querySelectorAll('.project-chip').forEach(chip => chip.classList.remove('active'));
        const clearBtn = document.getElementById('clearProjectFilterBtn');
        if (clearBtn) clearBtn.style.display = 'none';
        const chipSearch = document.getElementById('projectChipSearch');
        if (chipSearch) {
            chipSearch.value = '';
            filterProjectChips('');
        }
        loadDueTrackingPage(1);
    }

    // Listen on project select change (fires on Select2 or native select change)
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery) {
            window.jQuery(document).on('change', '#dueProjectFilter', function() {
                handleProjectFilterChange(this.value);
            });
        } else {
            const pf = document.getElementById('dueProjectFilter');
            if (pf) {
                pf.addEventListener('change', function() {
                    handleProjectFilterChange(this.value);
                });
            }
        }
    });

    // Live search debouncing
    document.getElementById('dueSearchInput')?.addEventListener('input', function() {
        const clearBtn = document.getElementById('dueClearSearch');
        if (this.value.trim().length > 0) {
            clearBtn?.classList.remove('d-none');
        } else {
            clearBtn?.classList.add('d-none');
        }
        clearTimeout(dueSearchTimer);
        dueSearchTimer = setTimeout(() => {
            loadDueTrackingPage(1);
        }, 350);
    });

    // Date filters auto-reload
    document.getElementById('dueStartDate')?.addEventListener('change', () => loadDueTrackingPage(1));
    document.getElementById('dueEndDate')?.addEventListener('change', () => loadDueTrackingPage(1));

    // Core AJAX Page Loader
    function loadDueTrackingPage(page = 1, callback = null) {
        dueCurrentPage = page;
        const search = document.getElementById('dueSearchInput')?.value.trim() || '';
        const projectId = document.getElementById('dueProjectFilter')?.value || '';
        const sortBy = document.getElementById('dueSortBy')?.value || 'overall_due';
        const dateType = document.getElementById('dueDateType')?.value || 'booking_date';
        const startDate = document.getElementById('dueStartDate')?.value || '';
        const endDate = document.getElementById('dueEndDate')?.value || '';
        const limit = document.getElementById('duePageLimit')?.value || '20';

        const tbody = document.getElementById('dueTrackingTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>Loading due records...</td></tr>';
        }

        const params = new URLSearchParams({
            ajax: '1',
            page: page,
            limit: limit,
            due_type: dueActiveType,
            search: search,
            project_id: projectId,
            sort_by: sortBy,
            date_type: dateType,
            start_date: startDate,
            end_date: endDate
        });

        // Update CSV export link with active filters
        const exportBtn = document.getElementById('exportDueCsvBtn');
        if (exportBtn) {
            exportBtn.href = `${window.base_url || ''}/client-wise-due-tracking-export?${params.toString()}`;
        }

        // Update URL query string without reloading
        const newUrl = `${window.location.pathname}?${params.toString().replace('ajax=1&', '')}`;
        history.pushState(null, '', newUrl);

        fetch(`${window.base_url || ''}/client-wise-due-tracking?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (tbody) tbody.innerHTML = data.table_html;
                const tfoot = document.getElementById('dueTrackingTableFoot');
                if (data.footer_html && tfoot) tfoot.innerHTML = data.footer_html;
                const pagination = document.getElementById('duePaginationContainer');
                if (pagination) pagination.innerHTML = data.pagination_html;
                const infoText = document.getElementById('dueInfoText');
                if (infoText) infoText.innerText = data.info_text;
                const totalCountSpan = document.getElementById('dueTotalCount');
                if (totalCountSpan) totalCountSpan.innerText = Number(data.totalRecords).toLocaleString();
                const printTotalRecords = document.getElementById('printTotalRecords');
                if (printTotalRecords) printTotalRecords.innerText = Number(data.totalRecords).toLocaleString();
                if (typeof callback === 'function') callback();

                // Update Top KPI stats if returned
                if (data.stats) {
                    const s = data.stats;
                    const fmt = (num) => 'Tk. ' + Number(num || 0).toLocaleString();
                    const fmtCrLac = (amount) => {
                        const val = Number(amount || 0);
                        if (Math.abs(val) >= 10000000) return (val / 10000000).toFixed(2) + ' Cr';
                        if (Math.abs(val) >= 100000) return (val / 100000).toFixed(2) + ' Lac';
                        return val.toLocaleString();
                    };

                    const devDue = Number(s.total_dev_charge_due || 0);
                    const devPaid = Number(s.total_dev_charges_paid || 0);
                    const devTarget = Number(s.total_dev_charges_target || 0);

                    const shopDue = Number(s.total_shop_service_charge_due || 0);
                    const shopPaid = Number(s.total_shop_service_charge_paid || 0);
                    const shopTarget = Number(s.total_shop_service_charge_target || 0);

                    if (document.getElementById('statOverallDue')) document.getElementById('statOverallDue').innerText = fmt(s.overall_due);
                    if (document.getElementById('statTotalValueText')) document.getElementById('statTotalValueText').innerText = 'Tk. ' + fmtCrLac(s.total_value);
                    if (document.getElementById('statOverallDuePercentBadge')) {
                        const pct = Number(s.total_value || 0) > 0 ? Math.round((Number(s.overall_due || 0) / Number(s.total_value)) * 1000) / 10 : 0;
                        document.getElementById('statOverallDuePercentBadge').innerText = pct + '% Due';
                    }

                    if (document.getElementById('statLandShareDue')) document.getElementById('statLandShareDue').innerText = fmt(s.total_land_share_due);
                    if (document.getElementById('statLandSharePaidText')) document.getElementById('statLandSharePaidText').innerText = 'Tk. ' + fmtCrLac(s.total_land_share_paid);
                    if (document.getElementById('statLandShareTargetBadge')) {
                        document.getElementById('statLandShareTargetBadge').innerText = 'Target: Tk. ' + fmtCrLac(s.total_land_share_target);
                        document.getElementById('statLandShareTargetBadge').title = 'Target: Tk. ' + fmtCrLac(s.total_land_share_target);
                    }
                    if (document.getElementById('statLandShareDueText')) document.getElementById('statLandShareDueText').innerText = 'Tk. ' + fmtCrLac(s.total_land_share_due);

                    if (document.getElementById('statDevChargeDue')) document.getElementById('statDevChargeDue').innerText = fmt(devDue);
                    if (document.getElementById('statDevChargePaidText')) document.getElementById('statDevChargePaidText').innerText = 'Tk. ' + fmtCrLac(devPaid);
                    if (document.getElementById('statDevChargeTargetBadge')) {
                        document.getElementById('statDevChargeTargetBadge').innerText = 'Target: Tk. ' + fmtCrLac(devTarget);
                        document.getElementById('statDevChargeTargetBadge').title = 'Target: Tk. ' + fmtCrLac(devTarget);
                    }
                    if (document.getElementById('statDevChargeDueText')) document.getElementById('statDevChargeDueText').innerText = 'Tk. ' + fmtCrLac(devDue);

                    if (document.getElementById('statShopServiceDue')) document.getElementById('statShopServiceDue').innerText = fmt(shopDue);
                    if (document.getElementById('statShopServicePaidText')) document.getElementById('statShopServicePaidText').innerText = 'Tk. ' + fmtCrLac(shopPaid);
                    if (document.getElementById('statShopServiceTargetBadge')) {
                        document.getElementById('statShopServiceTargetBadge').innerText = 'Target: Tk. ' + fmtCrLac(shopTarget);
                        document.getElementById('statShopServiceTargetBadge').title = 'Target: Tk. ' + fmtCrLac(shopTarget);
                    }
                    if (document.getElementById('statShopServiceDueText')) document.getElementById('statShopServiceDueText').innerText = 'Tk. ' + fmtCrLac(shopDue);

                    if (document.getElementById('statEmiDue')) document.getElementById('statEmiDue').innerText = fmt(s.total_emi_due);
                    if (document.getElementById('statEmiPaidText')) document.getElementById('statEmiPaidText').innerText = 'Tk. ' + fmtCrLac(s.total_emi_paid);
                    if (document.getElementById('statConstructionTargetBadge')) {
                        document.getElementById('statConstructionTargetBadge').innerText = 'Target: Tk. ' + fmtCrLac(s.total_construction_target);
                        document.getElementById('statConstructionTargetBadge').title = 'Target: Tk. ' + fmtCrLac(s.total_construction_target);
                    }
                    if (document.getElementById('statOverdueBadge')) {
                        if (Number(s.total_overdue_emi || 0) > 0) {
                            document.getElementById('statOverdueBadge').className = 'badge bg-danger text-white stat-pill';
                            document.getElementById('statOverdueBadge').innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue: Tk. ' + fmtCrLac(s.total_overdue_emi);
                        } else {
                            document.getElementById('statOverdueBadge').className = 'badge bg-success-subtle text-success stat-pill';
                            document.getElementById('statOverdueBadge').innerText = 'No Overdues';
                        }
                    }

                    // Dynamically update Breakdown Modal stats
                    const setTxt = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.innerText = val;
                    };
                    const getPct = (p, t) => {
                        const paid = Number(p || 0);
                        const tgt = Number(t || 0);
                        return tgt > 0 ? (Math.round((paid / tgt) * 1000) / 10) + '%' : '0%';
                    };

                    setTxt('mTotTarget', 'Tk. ' + fmtCrLac(s.total_value));
                    setTxt('mTotTargetFull', 'Exact: Tk. ' + Number(s.total_value || 0).toLocaleString());
                    setTxt('mTotPaid', 'Tk. ' + fmtCrLac(s.total_paid));
                    setTxt('mTotPaidFull', 'Exact: Tk. ' + Number(s.total_paid || 0).toLocaleString());
                    setTxt('mTotDue', 'Tk. ' + fmtCrLac(s.overall_due));
                    setTxt('mTotDueFull', 'Exact: Tk. ' + Number(s.overall_due || 0).toLocaleString());

                    setTxt('mRowLandTarget', fmt(s.total_land_share_target));
                    setTxt('mRowLandPaid', fmt(s.total_land_share_paid));
                    setTxt('mRowLandDue', fmt(s.total_land_share_due));
                    const mRowLandRec = document.getElementById('mRowLandRec');
                    if (mRowLandRec) mRowLandRec.innerHTML = `<span class="badge bg-warning-subtle text-dark">${getPct(s.total_land_share_paid, s.total_land_share_target)}</span>`;

                    setTxt('mRowDevTarget', fmt(s.total_dev_charges_target));
                    setTxt('mRowDevPaid', fmt(s.total_dev_charges_paid));
                    setTxt('mRowDevDue', fmt(s.total_dev_charge_due));
                    const mRowDevRec = document.getElementById('mRowDevRec');
                    if (mRowDevRec) mRowDevRec.innerHTML = `<span class="badge bg-info-subtle text-info">${getPct(s.total_dev_charges_paid, s.total_dev_charges_target)}</span>`;

                    setTxt('mRowShopTarget', fmt(s.total_shop_service_charge_target));
                    setTxt('mRowShopPaid', fmt(s.total_shop_service_charge_paid));
                    setTxt('mRowShopDue', fmt(s.total_shop_service_charge_due));
                    const mRowShopRec = document.getElementById('mRowShopRec');
                    if (mRowShopRec) mRowShopRec.innerHTML = `<span class="badge bg-light text-secondary border">${getPct(s.total_shop_service_charge_paid, s.total_shop_service_charge_target)}</span>`;

                    setTxt('mRowConsTarget', fmt(s.total_construction_target));
                    setTxt('mRowConsPaid', fmt(s.total_emi_paid));
                    setTxt('mRowConsDue', fmt(s.total_emi_due));
                    const mRowConsRec = document.getElementById('mRowConsRec');
                    if (mRowConsRec) mRowConsRec.innerHTML = `<span class="badge bg-primary-subtle text-primary">${getPct(s.total_emi_paid, s.total_construction_target)}</span>`;

                    setTxt('mFooterTfootTarget', fmt(s.total_value));
                    setTxt('mFooterTfootPaid', fmt(s.total_paid));
                    setTxt('mFooterTfootDue', fmt(s.overall_due));
                    const mFooterTfootRec = document.getElementById('mFooterTfootRec');
                    if (mFooterTfootRec) mFooterTfootRec.innerHTML = `<span class="badge bg-success text-white">${getPct(s.total_paid, s.total_value)}</span>`;

                    setTxt('mFooterTarget', 'Tk. ' + fmtCrLac(s.total_value));
                    setTxt('mFooterTargetExact', 'Exact: Tk. ' + Number(s.total_value || 0).toLocaleString());
                    setTxt('mFooterPaid', 'Tk. ' + fmtCrLac(s.total_paid));
                    setTxt('mFooterPaidExact', 'Exact: Tk. ' + Number(s.total_paid || 0).toLocaleString());
                    setTxt('mFooterDue', 'Tk. ' + fmtCrLac(s.overall_due));
                    setTxt('mFooterDueExact', 'Exact: Tk. ' + Number(s.overall_due || 0).toLocaleString());
                    setTxt('mFooterUnits', Number(s.total_clients || 0).toLocaleString() + ' Bookings');
                }

                // Update EMI Analytics KPI cards if returned
                if (data.emi_analytics) {
                    const ea = data.emi_analytics;
                    const fmt = (num) => 'Tk. ' + Number(num || 0).toLocaleString();
                    if (document.getElementById('statTotalEmiPaid')) document.getElementById('statTotalEmiPaid').innerText = fmt(ea.total_emi_paid);
                    if (document.getElementById('statTotalEmiClients')) document.getElementById('statTotalEmiClients').innerText = Number(ea.total_emi_clients || 0).toLocaleString();
                    if (document.getElementById('statRegularEmiPaid')) document.getElementById('statRegularEmiPaid').innerText = fmt(ea.regular_emi_paid);
                    if (document.getElementById('statRegularEmiClients')) document.getElementById('statRegularEmiClients').innerText = Number(ea.regular_emi_clients || 0).toLocaleString();
                    if (document.getElementById('statAdvanceEmiPaid')) document.getElementById('statAdvanceEmiPaid').innerText = fmt(ea.advance_emi_paid);
                    if (document.getElementById('statAdvanceEmiClients')) document.getElementById('statAdvanceEmiClients').innerText = Number(ea.advance_emi_clients || 0).toLocaleString();
                    if (document.getElementById('statDueEmiAmount')) document.getElementById('statDueEmiAmount').innerText = fmt(ea.due_emi_amount);
                    if (document.getElementById('statDueEmiClients')) document.getElementById('statDueEmiClients').innerText = Number(ea.due_emi_clients || 0).toLocaleString();
                }
            } else {
                if (tbody) tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-danger">Failed to load data.</td></tr>';
            }
        })
        .catch(err => {
            console.error('Error loading due tracking data:', err);
            if (tbody) tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-danger">Error loading data. Please try again.</td></tr>';
        });
    }

    // Print Due Tracking Report Handler (Handles loading all rows before printing)
    function printDueTrackingReport() {
        const limitSelect = document.getElementById('duePageLimit');
        if (limitSelect && limitSelect.value !== 'all') {
            if (confirm('Currently only ' + limitSelect.value + ' rows are loaded. Would you like to load ALL records before printing?')) {
                limitSelect.value = 'all';
                loadDueTrackingPage(1, function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                });
                return;
            }
        }
        window.print();
    }

    // Refresh data handler (Full clean reload of the route)
    function refreshDueTracking(force = false) {
        window.location.href = `${window.base_url || ''}/client-wise-due-tracking`;
    }

    // Open Quick Client Due Breakdown Modal
    function openClientDueBreakdownModal(bookingId) {
        const modal = new bootstrap.Modal(document.getElementById('clientDueBreakdownModal'));
        const body = document.getElementById('clientDueModalBody');
        const title = document.getElementById('clientDueModalTitle');
        const ledgerBtn = document.getElementById('clientDueModalLedgerBtn');

        title.innerText = `Client Due Breakdown #${bookingId}`;
        ledgerBtn.href = `${window.base_url || ''}/clients-cash-in-history-view/${bookingId}`;
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <p class="text-muted mb-0">Loading booking financial details...</p>
            </div>
        `;
        modal.show();

        fetch(`${window.base_url || ''}/client-wise-due-tracking?ajax=client_detail&booking_id=${bookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.summary && data.details) {
                    const s = data.summary;
                    const d = data.details;
                    const fmt = (num) => 'Tk. ' + Number(num || 0).toLocaleString();

                    body.innerHTML = `
                        <!-- Client Header -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h5 class="fw-bold mb-1 text-dark">${d.client_name || 'N/A'}</h5>
                                    <div class="text-muted extra-small">
                                        <i class="fa-solid fa-phone me-1"></i>${d.phone || 'N/A'} | 
                                        <i class="fa-solid fa-envelope me-1"></i>${d.email || 'N/A'}
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-end mt-2 mt-md-0">
                                    <span class="badge bg-primary fs-12">${d.project_name || 'Project'}</span>
                                    <div class="fw-semibold text-dark extra-small mt-1">
                                        Unit: ${d.unit_name || 'Unit'} - Floor ${d.flat_no || ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Pillars Breakdown -->
                        <div class="row g-2 mb-3">
                            <!-- 1. Land Share -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-warning">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-map-location-dot text-warning me-1"></i> Land Share</strong>
                                        <span class="badge ${s.remaining_land_share <= 0 ? 'bg-success' : 'bg-danger'}">${s.remaining_land_share <= 0 ? 'Cleared' : 'Due: ' + fmt(s.remaining_land_share)}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Target: <strong>${fmt(s.total_land_share)}</strong><br>
                                        Booking Money Paid: <strong>${fmt(s.booking_money)}</strong><br>
                                        Registration Fee Paid: <strong>${fmt(s.registration_paid)}</strong><br>
                                        Total Land Share Paid: <strong>${fmt(s.total_land_share_paid)}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Development Charges -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-info">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-screwdriver-wrench text-info me-1"></i> Dev. Charges</strong>
                                        <span class="badge ${s.is_dev_charge_completed ? 'bg-success' : 'bg-danger'}">${s.is_dev_charge_completed ? 'Cleared' : 'Due: ' + fmt((s.flat_development_charges || 0) - (s.development_charge_paid || 0))}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Dev. Target: <strong>${fmt(s.flat_development_charges)}</strong><br>
                                        Dev. Charges Paid: <strong>${fmt(s.development_charge_paid)}</strong><br>
                                        Status: <strong>${s.is_dev_charge_completed ? 'Fully Completed' : 'Pending Payment'}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Construction & EMI -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-primary">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-building-columns text-primary me-1"></i> Construction & EMI</strong>
                                        <span class="badge ${s.remaining_construction_fee <= 0 ? 'bg-success' : 'bg-danger'}">${s.remaining_construction_fee <= 0 ? 'Cleared' : 'Due: ' + fmt(s.remaining_construction_fee)}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Total Construction Fee: <strong>${fmt(s.construction_fee)}</strong><br>
                                        EMI Paid: <strong>${fmt(s.construction_fee_paid)}</strong> (${s.completed_emi || 0} / ${s.total_emi || 0} EMIs)<br>
                                        Per EMI Rate: <strong>${fmt(s.per_emi_amount)}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Overall Position -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-danger">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-vault text-danger me-1"></i> Overall Account Due</strong>
                                        <span class="badge ${s.total_due <= 0 ? 'bg-success' : 'bg-danger'}">${s.total_due <= 0 ? 'All Clear ✓' : 'Due: ' + fmt(s.total_due)}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Total Flat Value: <strong>${fmt(s.total_price)}</strong><br>
                                        Discount Approved: <strong>${fmt(s.discount_amount)}</strong><br>
                                        Total Confirmed Paid: <strong>${fmt(s.total_paid)}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    body.innerHTML = '<div class="alert alert-danger mb-0">Failed to load client details.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                body.innerHTML = '<div class="alert alert-danger mb-0">An error occurred while fetching details.</div>';
            });
    }

    // Open Registered Clients Modal (Clients who completed Land Share in a Project)
    function openRegisteredClientsModal(projectId, projectName) {
        const modalEl = document.getElementById('registeredClientsModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        const title = document.getElementById('regModalTitle');
        const subtitle = document.getElementById('regModalSubtitle');
        const body = document.getElementById('regModalBody');
        const filterBtn = document.getElementById('regModalFilterBtn');

        title.innerHTML = `<i class="fa-solid fa-file-circle-check me-2"></i>Registered Clients — <span class="text-warning">${projectName || 'Project'}</span>`;
        subtitle.textContent = `Clients who completed 100% verified Land Share payment (Booking + Registration) in ${projectName}`;

        if (filterBtn) {
            filterBtn.onclick = function() {
                modal.hide();
                selectProjectFilter(projectId);
                setDueTypeFilter('land_share_cleared');
            };
        }

        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-success mb-2" role="status"></div>
                <p class="text-muted mb-0">Loading registered clients for ${projectName}...</p>
            </div>
        `;
        modal.show();

        fetch(`${window.base_url || ''}/client-wise-due-tracking?ajax=registered_clients&project_id=${projectId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const clients = data.clients || [];
                    if (clients.length === 0) {
                        body.innerHTML = `
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                <h5>No Registered Clients Yet</h5>
                                <p class="small text-muted mb-0">No client in <strong>${projectName}</strong> has 100% completed their verified Land Share payment yet.</p>
                            </div>
                        `;
                        return;
                    }

                    const fmt = (num) => 'Tk. ' + Number(num || 0).toLocaleString();
                    let html = `
                        <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="badge bg-success rounded-pill px-3 py-2 fs-12 fw-bold">
                                    <i class="fa-solid fa-user-check me-1"></i>${clients.length} Registered Client(s)
                                </span>
                                <small class="text-muted ms-2"><i class="fa-solid fa-shield-check text-success me-1"></i>All payments verified by accounts</small>
                            </div>
                            <div class="fs-12 text-muted">
                                Click <strong>"History / Ledger"</strong> to view full cash-in ledger & receipts
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top" style="z-index: 2;">
                                    <tr class="fs-12 text-muted text-uppercase">
                                        <th class="ps-3" style="width: 85px;">#ID</th>
                                        <th>Client Information</th>
                                        <th>Unit / Flat</th>
                                        <th class="text-end">Land Share Target</th>
                                        <th class="text-end text-success">Verified Paid</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end">Overall Due</th>
                                        <th class="text-center pe-3" style="width: 175px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    clients.forEach(c => {
                        const ledgerUrl = `${window.base_url || ''}/clients-cash-in-history-view/${c.booking_id}`;
                        const phone = c.phone ? `<a href="tel:${c.phone}" class="text-muted extra-small text-decoration-none d-block"><i class="fa-solid fa-phone me-1"></i>${c.phone}</a>` : '';
                        const agent = c.sales_agent_name ? `<span class="extra-small text-muted d-block" title="Assigned Sales Agent"><i class="fa-solid fa-user-tie me-1 text-info"></i>${c.sales_agent_name}</span>` : '';
                        const floorText = c.floor_no ? ` (Floor ${c.floor_no})` : '';
                        const lastPay = c.last_payment_date ? `<div class="extra-small text-muted" style="font-size:10px;"><i class="fa-solid fa-clock-rotate-left me-1"></i>Last: ${c.last_payment_date}</div>` : '';
                        const overallBadge = Number(c.overall_due) <= 0 
                            ? `<span class="badge bg-success-subtle text-success fw-bold"><i class="fa-solid fa-check-double me-1"></i>All Paid</span>`
                            : `<span class="fw-bold text-danger fs-12">${fmt(c.overall_due)}</span>`;

                        html += `
                            <tr>
                                <td class="ps-3">
                                    <a href="${ledgerUrl}" target="_blank" class="fw-bold text-primary text-decoration-none">#${c.booking_id}</a>
                                    <div class="extra-small text-muted">${c.booking_date || ''}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width:34px; height:34px; font-size:12px;">
                                            ${(c.client_name || 'C').charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <a href="${ledgerUrl}" target="_blank" class="fw-bold text-dark text-decoration-none d-block">${c.client_name || 'N/A'}</a>
                                            ${phone}
                                            ${agent}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-dark fs-12">${c.unit_name || 'N/A'}</strong>${floorText}
                                    ${c.is_shop == 1 ? '<span class="badge bg-warning text-dark extra-small ms-1">Shop</span>' : ''}
                                </td>
                                <td class="text-end fw-semibold text-dark fs-12">${fmt(c.total_land_share)}</td>
                                <td class="text-end fw-bold text-success fs-12">
                                    ${fmt(c.land_share_paid)}
                                    ${lastPay}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success text-white px-2 py-1 fs-11" title="100% of Land Share Target has been verified paid">
                                        <i class="fa-solid fa-circle-check me-1"></i>100% Cleared
                                    </span>
                                </td>
                                <td class="text-end">${overallBadge}</td>
                                <td class="text-center pe-3">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <a href="${ledgerUrl}" target="_blank" class="btn btn-sm btn-primary py-1 px-2 text-nowrap" style="font-size:11px;" title="View Full Payment Ledger & History">
                                            <i class="fa-solid fa-receipt me-1"></i>Ledger / History
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:11px;" onclick="openClientDueBreakdownModal(${c.booking_id})" title="Quick Due Breakdown">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    body.innerHTML = html;
                } else {
                    body.innerHTML = '<div class="alert alert-danger m-3">Failed to load registered clients.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                body.innerHTML = '<div class="alert alert-danger m-3">An error occurred while fetching data.</div>';
            });
    }

    // ── EMI Follow Up Modal Logic ──────────────────────────────────────────
    let clientEmiFollowupModalInstance = null;

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function openClientEmiFollowupModal(bookingId, clientName, totalEmi = 36, leadId = null) {
        document.getElementById('followup_modal_client_name').textContent = clientName || 'Client';
        document.getElementById('modalFollowupBookingId').value = bookingId;
        document.getElementById('modalFollowupNoteInput').value = '';
        if (document.getElementById('modalFollowupNextDate')) {
            document.getElementById('modalFollowupNextDate').value = '';
        }
        if (document.getElementById('modalFollowupNextNote')) {
            document.getElementById('modalFollowupNextNote').value = '';
        }
        
        const bookingSelect = document.getElementById('modalFollowupBookingSelect');
        if (bookingSelect) {
            bookingSelect.innerHTML = '<option value="' + bookingId + '">Loading project details...</option>';
            bookingSelect.disabled = true;
        }

        populateEmiSelect(totalEmi);

        document.getElementById('modalFollowupHistoryContainer').innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...</div>';
        
        const modalEl = document.getElementById('clientEmiFollowupModal');
        if (modalEl) {
            clientEmiFollowupModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            clientEmiFollowupModalInstance.show();
        }
        
        if (leadId) {
            fetch('<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.lands && data.lands.length > 0) {
                        let opts = '';
                        data.lands.forEach(land => {
                            const selected = (land.booking_id == bookingId) ? 'selected' : '';
                            opts += `<option value="${land.booking_id}" data-emi="${land.total_emi_count || 36}" ${selected}>${escapeHtml(land.project_name)} - ${escapeHtml(land.unit_name)} (Floor ${escapeHtml(land.flat_no || '')})</option>`;
                        });
                        if (bookingSelect) {
                            bookingSelect.innerHTML = opts;
                            bookingSelect.disabled = false;
                        }
                    } else {
                        if (bookingSelect) {
                            bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Booking #' + bookingId + '</option>';
                            bookingSelect.disabled = false;
                        }
                    }
                })
                .catch(err => {
                    if (bookingSelect) {
                        bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Booking #' + bookingId + '</option>';
                        bookingSelect.disabled = false;
                    }
                });
        } else {
            if (bookingSelect) {
                bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Booking #' + bookingId + '</option>';
                bookingSelect.disabled = false;
            }
        }

        loadClientEmiFollowups(bookingId);
    }

    function onFollowupBookingChange() {
        const bookingSelect = document.getElementById('modalFollowupBookingSelect');
        if (!bookingSelect) return;
        
        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        const bookingId = selectedOption.value;
        const totalEmi = selectedOption.getAttribute('data-emi') || 36;
        
        document.getElementById('modalFollowupBookingId').value = bookingId;
        populateEmiSelect(totalEmi);
        loadClientEmiFollowups(bookingId);
    }

    function populateEmiSelect(totalEmi) {
        const emiSelect = document.getElementById('modalFollowupEmiSelect');
        if (emiSelect) {
            let optionsHtml = '<option value="0">General / Overall Follow Up</option>';
            optionsHtml += '<option value="999">Development Charge</option>';
            const maxEmi = parseInt(totalEmi) || 36;
            for (let i = 1; i <= maxEmi; i++) {
                optionsHtml += `<option value="${i}">EMI #${i}</option>`;
            }
            emiSelect.innerHTML = optionsHtml;
        }
    }

    function loadClientEmiFollowups(bookingId) {
        if (!bookingId) return;
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/getEmiFollowups',
            type: 'GET',
            data: { booking_id: bookingId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    const countBadge = document.getElementById('modalFollowupHistoryCountBadge');
                    if (countBadge) {
                        countBadge.textContent = (response.data ? response.data.length : 0) + ' note(s)';
                    }
                    if (!response.data || response.data.length === 0) {
                        html = '<div class="text-center text-muted py-5"><i class="fa-light fa-comment-slash d-block fs-2 mb-2 opacity-50"></i>No follow up notes added yet. Use the form on the left to add the first follow up note.</div>';
                    } else {
                        response.data.forEach(function(note) {
                            const label = note.purpose_label || (note.emi_no == 999 ? 'Development Charge' : (note.emi_no == 0 ? 'General Follow Up' : 'EMI #' + note.emi_no));
                            const badgeClass = note.purpose_badge_class || (note.emi_no == 999 ? 'bg-warning text-dark' : (note.emi_no == 0 ? 'bg-secondary text-white' : 'bg-primary text-white'));
                            const borderClr = note.emi_no == 999 ? '#f59e0b' : (note.emi_no == 0 ? '#64748b' : '#2563eb');

                            let nextFollowupBlock = '';
                            if (note.next_follow_up_date) {
                                const nextDateDisplay = note.next_follow_up_date_formatted || note.next_follow_up_date;
                                const nextNoteDisplay = note.next_follow_up_note ? `<div class="text-dark fst-italic mt-1">${escapeHtml(note.next_follow_up_note)}</div>` : '';
                                nextFollowupBlock = `
                                    <div class="mt-2 p-2 rounded bg-info-subtle border-start border-3 border-info fs-12 text-dark">
                                        <div class="fw-semibold text-primary"><i class="fa-solid fa-calendar-check me-1"></i>Next Scheduled Follow Up: <strong>${escapeHtml(nextDateDisplay)}</strong></div>
                                        ${nextNoteDisplay}
                                    </div>
                                `;
                            }

                            html += `
                            <div class="card mb-2 shadow-sm border-0" style="background:#f8fafc; border-left: 4px solid ${borderClr} !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge ${badgeClass} fs-11 px-2 py-1">
                                            <i class="fa-light fa-tag me-1"></i>Follow Up for: <strong>${escapeHtml(label)}</strong>
                                        </span>
                                        <span class="text-muted fs-11"><i class="fa-light fa-clock me-1"></i>${note.created_at_formatted || note.created_at || ''}</span>
                                    </div>
                                    <div class="mb-2" style="font-size:12px; color:#475569;">
                                        ${note.activity_date ? `<span><i class="fa-light fa-calendar me-1 text-primary"></i>${escapeHtml(note.activity_date)}</span>` : ''}
                                        ${note.activity_mode ? `<span class="ms-3"><i class="fa-light fa-phone-volume me-1 text-info"></i>${escapeHtml(note.activity_mode)}</span>` : ''}
                                        ${note.call_status ? `<span class="ms-2 badge ${note.call_status.toLowerCase() === 'incoming' ? 'bg-success text-white' : 'bg-primary text-white'}" style="font-size:10px;"><i class="fa-light ${note.call_status.toLowerCase() === 'incoming' ? 'fa-arrow-down-left' : 'fa-arrow-up-right'} me-1"></i>${escapeHtml(note.call_status)}</span>` : ''}
                                        ${note.activity_reason ? `<span class="ms-3"><i class="fa-light fa-bullseye me-1 text-warning"></i>${escapeHtml(note.activity_reason)}</span>` : ''}
                                    </div>
                                    <p class="mb-2 text-dark fs-13 fw-medium" style="white-space:pre-line;">${escapeHtml(note.note || '')}</p>
                                    ${nextFollowupBlock}
                                    <div class="text-muted fs-11 text-end mt-2 pt-1 border-top">
                                        <span><i class="fa-light fa-user me-1"></i>By ${escapeHtml(note.author_name || 'Staff')}</span>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    $('#modalFollowupHistoryContainer').html(html);
                } else {
                    $('#modalFollowupHistoryContainer').html('<div class="text-danger p-3 text-center">Failed to load history.</div>');
                }
            },
            error: function() {
                $('#modalFollowupHistoryContainer').html('<div class="text-danger p-3 text-center">Error connecting to server.</div>');
            }
        });
    }

    function saveClientEmiFollowup() {
        const bookingId = $('#modalFollowupBookingId').val();
        const emiNo = $('#modalFollowupEmiSelect').val() ?? 0;
        const note = $('#modalFollowupNoteInput').val().trim();
        const activityDate = $('#modalFollowupActivityDate').val();
        const activityMode = $('#modalFollowupActivityMode').val();
        const callStatus = $('#modalFollowupCallStatus').val() || '';
        const activityReason = $('#modalFollowupActivityReason').val();
        const nextDate = $('#modalFollowupNextDate').val() || '';
        const nextNote = $('#modalFollowupNextNote').val().trim();
        
        if (!note || !activityDate || !activityMode || !activityReason) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Fields Missing',
                    text: 'Please fill in Note, Activity Date, Mode, and Reason.',
                });
            } else {
                alert('Please fill in Note, Activity Date, Mode, and Reason.');
            }
            return;
        }

        const saveBtn = document.getElementById('saveFollowupBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        }
        
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/saveEmiFollowup',
            type: 'POST',
            data: { 
                booking_id: bookingId, 
                emi_no: emiNo, 
                note: note,
                activity_date: activityDate,
                activity_mode: activityMode,
                call_status: callStatus,
                activity_reason: activityReason,
                next_follow_up_date: nextDate,
                next_follow_up_note: nextNote
            },
            dataType: 'json',
            success: function(response) {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note';
                }
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: 'Follow up note recorded successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    $('#modalFollowupNoteInput').val('');
                    $('#modalFollowupNextDate').val('');
                    $('#modalFollowupNextNote').val('');
                    loadClientEmiFollowups(bookingId);
                    
                    // Update count badge in real-time
                    const badges = document.querySelectorAll(`.followup-count-badge[data-booking-id="${bookingId}"]`);
                    badges.forEach(badge => {
                        let currentCount = parseInt(badge.textContent) || 0;
                        badge.textContent = currentCount + 1;
                        badge.className = 'badge bg-primary rounded-pill ms-1 followup-count-badge';
                    });
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: response.message || "Failed to save note."
                        });
                    } else {
                        alert(response.message || "Failed to save note.");
                    }
                }
            },
            error: function() {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while saving the note.'
                    });
                } else {
                    alert('An error occurred while saving the note.');
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════════
    // ══ EMI ANALYTICS & BREAKDOWN MODAL HANDLERS ══════════════════
    // ══════════════════════════════════════════════════════════════
    let currentEmiCategory = 'all';
    let emiAnalyticsModalInstance = null;

    function openEmiAnalyticsModal(category = 'all') {
        currentEmiCategory = category;
        const modalEl = document.getElementById('emiAnalyticsModal');
        if (!modalEl) return;
        emiAnalyticsModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

        // Sync project dropdown in modal with currently selected project filter
        const mainPrjSelect = document.getElementById('dueProjectFilter');
        const modalPrjSelect = document.getElementById('emiModalProjectSelect');
        if (mainPrjSelect && modalPrjSelect) {
            modalPrjSelect.value = mainPrjSelect.value || '';
        }

        // Reset search input
        const searchInput = document.getElementById('emiModalSearchInput');
        if (searchInput) searchInput.value = '';

        updateEmiModalTabUI(category);
        emiAnalyticsModalInstance.show();
        loadEmiAnalyticsModalData(category);
    }

    function switchEmiAnalyticsTab(category) {
        currentEmiCategory = category;
        updateEmiModalTabUI(category);
        loadEmiAnalyticsModalData(category);
    }

    function updateEmiModalTabUI(category) {
        document.querySelectorAll('.emi-category-pills .nav-link').forEach(btn => btn.classList.remove('active'));
        if (category === 'all') document.getElementById('tabEmiAll')?.classList.add('active');
        else if (category === 'regular') document.getElementById('tabEmiRegular')?.classList.add('active');
        else if (category === 'advance') document.getElementById('tabEmiAdvance')?.classList.add('active');
        else if (category === 'due') document.getElementById('tabEmiDue')?.classList.add('active');

        const statusText = document.getElementById('emiModalStatusText');
        if (statusText) {
            if (category === 'all') statusText.innerText = 'Showing all clients who made EMI installment payments';
            else if (category === 'regular') statusText.innerText = 'Showing clients who are up-to-date with regular installments through current month';
            else if (category === 'advance') statusText.innerText = 'Showing clients who paid advance installments beyond current month';
            else if (category === 'due') statusText.innerText = 'Showing clients with overdue / pending installment amounts';
        }
    }

    function handleEmiModalProjectChange(projectId) {
        loadEmiAnalyticsModalData(currentEmiCategory);
    }

    function refreshEmiAnalyticsModal() {
        loadEmiAnalyticsModalData(currentEmiCategory);
    }

    function loadEmiAnalyticsModalData(category) {
        const tbody = document.getElementById('emiModalTableBody');
        const modalPrjSelect = document.getElementById('emiModalProjectSelect');
        const projectId = modalPrjSelect ? modalPrjSelect.value : '';
        const searchInput = document.getElementById('emiModalSearchInput');
        const searchVal = searchInput ? searchInput.value.trim() : '';

        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="text-muted mb-0">Loading EMI breakdown data...</p>
                    </td>
                </tr>
            `;
        }

        const params = new URLSearchParams({
            ajax: 'emi_analytics_clients',
            category: category,
            project_id: projectId,
            search: searchVal
        });

        fetch(`${window.base_url || ''}/client-wise-due-tracking?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (tbody) tbody.innerHTML = data.html;

                    // Update Tab Badges
                    if (data.summary) {
                        const s = data.summary;
                        const fmt = (num) => 'Tk. ' + Number(num || 0).toLocaleString();

                        if (document.getElementById('badgeEmiAllCount')) 
                            document.getElementById('badgeEmiAllCount').innerText = Number(s.total_emi_clients || 0).toLocaleString();
                        if (document.getElementById('badgeEmiRegularCount')) 
                            document.getElementById('badgeEmiRegularCount').innerText = Number(s.regular_emi_clients || 0).toLocaleString();
                        if (document.getElementById('badgeEmiAdvanceCount')) 
                            document.getElementById('badgeEmiAdvanceCount').innerText = Number(s.advance_emi_clients || 0).toLocaleString();
                        if (document.getElementById('badgeEmiDueCount')) 
                            document.getElementById('badgeEmiDueCount').innerText = Number(s.due_emi_clients || 0).toLocaleString();

                        // Update in-view count & amount
                        const totalCountEl = document.getElementById('emiModalTotalCount');
                        const totalAmountEl = document.getElementById('emiModalTotalAmount');

                        if (totalCountEl) totalCountEl.innerText = Number(data.count || 0).toLocaleString();

                        if (totalAmountEl) {
                            if (category === 'advance') totalAmountEl.innerText = fmt(s.advance_emi_paid);
                            else if (category === 'regular') totalAmountEl.innerText = fmt(s.regular_emi_paid);
                            else if (category === 'due') totalAmountEl.innerText = fmt(s.due_emi_amount);
                            else totalAmountEl.innerText = fmt(s.total_emi_paid);
                        }
                    }
                } else {
                    if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load EMI analytics records.</td></tr>';
                }
            })
            .catch(err => {
                console.error('Error loading EMI analytics clients:', err);
                if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Error loading data. Please try again.</td></tr>';
            });
    }

    // Client-side quick filter for instantaneous typing response
    function filterEmiModalRows(query) {
        const q = (query || '').toLowerCase().trim();
        const tbody = document.getElementById('emiModalTableBody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;

        rows.forEach(tr => {
            if (tr.children.length < 9) return; // skip no-records row
            const text = tr.innerText.toLowerCase();
            if (!q || text.includes(q)) {
                tr.style.display = '';
                visibleCount++;
            } else {
                tr.style.display = 'none';
            }
        });

        const totalCountEl = document.getElementById('emiModalTotalCount');
        if (totalCountEl) totalCountEl.innerText = Number(visibleCount).toLocaleString();
    }
</script>
