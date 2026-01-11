<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        /* ============================================
           PDF-Compatible CSS (no CSS variables)
           wkhtmltopdf uses old WebKit without var() support
           ============================================ */

        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1e293b;
            background-color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }

        /* ============================================
           Print Styles
           ============================================ */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }

        @page {
            margin: 15mm 10mm;
            size: A4;
        }

        .page-break {
            page-break-before: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        /* Keep header with following content */
        .keep-together {
            page-break-inside: avoid;
        }

        .keep-with-next {
            page-break-after: avoid;
        }

        /* ============================================
           Layout
           ============================================ */
        .container {
            max-width: 100%;
            padding: 0 16px;
        }

        .header {
            display: table;
            width: 100%;
            padding: 16px 20px;
            background: #134e4a;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .header-logo {
            display: table-cell;
            vertical-align: middle;
        }

        .header-logo-icon {
            display: inline-block;
            width: 44px;
            height: 44px;
            background: #14b8a6;
            border-radius: 6px;
            text-align: center;
            line-height: 44px;
            color: #ffffff;
            font-weight: bold;
            font-size: 18px;
            vertical-align: middle;
            margin-right: 12px;
        }

        .header-text {
            display: inline-block;
            vertical-align: middle;
        }

        .header-title {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
        }

        .header-subtitle {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 2px;
        }

        .header-meta {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .header-date {
            font-size: 12px;
            color: #ffffff;
        }

        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }

        /* ============================================
           Typography
           ============================================ */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            line-height: 1.25;
            color: #0f172a;
        }

        h1 { font-size: 30px; margin-bottom: 24px; }
        h2 { font-size: 24px; margin-bottom: 20px; }
        h3 { font-size: 18px; margin-bottom: 16px; }
        h4 { font-size: 16px; margin-bottom: 12px; }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #0d9488;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #14b8a6;
            page-break-after: avoid;
        }

        .text-muted { color: #64748b; }
        .text-success { color: #16a34a; }
        .text-warning { color: #d97706; }
        .text-danger { color: #dc2626; }
        .text-primary { color: #0d9488; }

        .text-sm { font-size: 12px; }
        .text-xs { font-size: 10px; }
        .text-lg { font-size: 16px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }

        /* ============================================
           Cards
           ============================================ */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .card-header {
            padding: 12px 16px;
            background-color: #f0fdfa !important;
            border-bottom: 1px solid #99f6e4;
            border-left: 4px solid #14b8a6;
            border-radius: 6px 6px 0 0;
            page-break-after: avoid;
        }

        .card-header.admin-header {
            background-color: #fffbeb !important;
            border-bottom: 2px solid #d97706;
            border-left: 4px solid #d97706;
        }

        .card-header-title {
            font-size: 14px;
            font-weight: 600;
            color: #0f766e;
            margin: 0;
            display: inline-block;
        }

        .card-header-icon,
        .header-icon {
            width: 18px;
            height: auto;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* Generic icon classes for all SVG icons */
        .icon {
            width: 18px;
            height: auto;
            vertical-align: middle;
        }

        .icon-sm {
            width: 12px;
            height: auto;
            vertical-align: middle;
            margin-right: 3px;
        }

        .icon-md {
            width: 14px;
            height: auto;
            vertical-align: middle;
            margin-right: 6px;
        }

        /* Section header for table-based layouts */
        .section-header-cell {
            padding: 10px 16px;
            background-color: #f0fdfa !important;
            border-bottom: 1px solid #99f6e4;
            border-left: 4px solid #14b8a6;
            page-break-after: avoid;
        }

        .section-header-cell.admin {
            background-color: #fffbeb !important;
            border-bottom: 2px solid #d97706;
            border-left: 4px solid #d97706;
        }

        .section-header-text {
            color: #0f766e;
            font-weight: 700;
            font-size: 14px;
        }

        .section-header-text.admin {
            color: #92400e;
        }

        /* Hero header for main account/fund info */
        .hero-header-cell {
            padding: 28px 24px;
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
            border-radius: 8px;
            border: 1px solid #99f6e4;
            border-left: 4px solid #14b8a6;
        }

        /* Icon in headers - duplicate removed, see above */

        .card-body {
            padding: 16px;
            page-break-before: avoid;
        }

        .card-footer {
            padding: 12px 16px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 6px 6px;
        }

        /* ============================================
           Tables
           ============================================ */
        .table-container {
            overflow-x: auto;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table th,
        table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        table thead th {
            background-color: #f0fdfa !important;
            font-weight: 600;
            color: #0f766e;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #99f6e4;
        }

        table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-compact th,
        .table-compact td {
            padding: 4px 8px;
        }

        /* Numeric columns */
        .col-number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ============================================
           Grid Layout (using table for PDF compat)
           ============================================ */
        .row {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 16px;
        }

        .col-6 {
            display: table-cell;
            width: 50%;
            padding: 0 8px;
            vertical-align: top;
        }

        .col-4 {
            display: table-cell;
            width: 33.333%;
            padding: 0 8px;
            vertical-align: top;
        }

        .col-3 {
            display: table-cell;
            width: 25%;
            padding: 0 8px;
            vertical-align: top;
        }

        .col-12 {
            display: table-cell;
            width: 100%;
            padding: 0 8px;
            vertical-align: top;
        }

        /* ============================================
           Stats & Metrics
           ============================================ */
        .stat-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 20px;
        }

        .stat-card {
            display: table-cell;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 8px;
            text-align: center;
            vertical-align: top;
        }

        .stat-card + .stat-card {
            margin-left: 12px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #0d9488;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-top: 4px;
        }

        /* ============================================
           Charts
           ============================================ */
        .chart-container {
            text-align: center;
            margin: 8px 0;
            page-break-inside: avoid;
        }

        .chart-container img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .chart-container-small img {
            width: auto;
            max-width: 100%;
        }

        .chart-title {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        /* ============================================
           Progress Bars
           ============================================ */
        .progress {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 5px;
        }

        .progress-bar-success { background: #16a34a; }
        .progress-bar-warning { background: #d97706; }
        .progress-bar-danger { background: #dc2626; }
        .progress-bar-primary { background: #0d9488; }

        /* ============================================
           Badges & Labels
           ============================================ */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .badge-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-primary {
            background: #ccfbf1;
            color: #0d9488;
        }

        .badge-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        /* ============================================
           Detail Lists
           ============================================ */
        .detail-list {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .detail-row {
            display: table-row;
        }

        .detail-item {
            display: table-cell;
            padding: 8px 12px;
            vertical-align: top;
            width: 50%;
        }

        .detail-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 2px;
            display: block;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            display: block;
        }

        /* ============================================
           Utility Classes
           ============================================ */
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 20px; }
        .mb-6 { margin-bottom: 24px; }

        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .mt-5 { margin-top: 20px; }
        .mt-6 { margin-top: 24px; }

        .ml-3 { margin-left: 12px; }

        .p-3 { padding: 12px; }
        .p-4 { padding: 16px; }

        .bg-gray-50 { background: #f8fafc; }
        .bg-white { background: #ffffff; }

        .border { border: 1px solid #e2e8f0; }
        .border-t { border-top: 1px solid #e2e8f0; }
        .border-b { border-bottom: 1px solid #e2e8f0; }

        .rounded { border-radius: 6px; }

        /* ============================================
           Specific Components for Reports
           ============================================ */
        .summary-box {
            background: linear-gradient(135deg, #0d9488 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .summary-box h2 {
            color: #ffffff;
            margin-bottom: 16px;
            font-size: 22px;
        }

        .summary-box .stat-grid {
            background: transparent;
        }

        .summary-box .stat-card {
            background: rgba(255,255,255,0.15);
            border: none;
        }

        .summary-box .stat-value {
            color: #ffffff;
            font-size: 20px;
        }

        .summary-box .stat-label {
            color: rgba(255, 255, 255, 0.85);
        }

        .goal-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .goal-header {
            margin-bottom: 8px;
        }

        .goal-header:after {
            content: "";
            display: table;
            clear: both;
        }

        .goal-name {
            font-weight: 600;
            color: #1e293b;
            float: left;
        }

        .goal-status {
            float: right;
        }

        .goal-progress {
            font-size: 12px;
            color: #475569;
        }

        .goal-details {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
        }

        .transaction-positive {
            color: #16a34a;
        }

        .transaction-negative {
            color: #dc2626;
        }

        /* Two-column detail layout */
        .detail-grid {
            width: 100%;
        }

        .detail-grid tr td {
            padding: 6px 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-grid tr td:first-child {
            width: 40%;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 500;
        }

        .detail-grid tr td:last-child {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
        }

        .detail-grid tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-logo">
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height: 44px; width: 44px; border-radius: 50%; vertical-align: middle; margin-right: 12px; object-fit: cover;">
                <span class="header-text">
                    <span class="header-title">{{ config('app.name') }}</span>
                    <br>
                    <span class="header-subtitle">@yield('report-type', 'Quarterly Report')</span>
                </span>
            </div>
            <div class="header-meta">
                <div class="header-date"><strong>Report Date:</strong> {{ $api['as_of'] ?? ($asOf ?? now()->format('Y-m-d')) }}</div>
                @hasSection('report-period')
                    <div class="header-date text-muted">@yield('report-period')</div>
                @endif
            </div>
        </header>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>{{ config('app.name') }} &bull; Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
            <p style="margin-top: 4px;">This report is confidential and intended for the named recipient only.</p>
        </footer>
    </div>
</body>
</html>
