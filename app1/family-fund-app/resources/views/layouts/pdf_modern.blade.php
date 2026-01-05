<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        /* ============================================
           CSS Variables & Base Styles
           ============================================ */
        :root {
            --color-primary: #1e40af;
            --color-primary-light: #3b82f6;
            --color-secondary: #64748b;
            --color-success: #16a34a;
            --color-warning: #d97706;
            --color-danger: #dc2626;
            --color-gray-50: #f8fafc;
            --color-gray-100: #f1f5f9;
            --color-gray-200: #e2e8f0;
            --color-gray-300: #cbd5e1;
            --color-gray-400: #94a3b8;
            --color-gray-500: #64748b;
            --color-gray-600: #475569;
            --color-gray-700: #334155;
            --color-gray-800: #1e293b;
            --color-gray-900: #0f172a;
            --color-white: #ffffff;
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --font-size-xs: 10px;
            --font-size-sm: 12px;
            --font-size-base: 14px;
            --font-size-lg: 16px;
            --font-size-xl: 18px;
            --font-size-2xl: 24px;
            --font-size-3xl: 30px;
            --spacing-1: 4px;
            --spacing-2: 8px;
            --spacing-3: 12px;
            --spacing-4: 16px;
            --spacing-5: 20px;
            --spacing-6: 24px;
            --spacing-8: 32px;
            --border-radius: 6px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
        }

        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            line-height: 1.5;
            color: var(--color-gray-800);
            background-color: var(--color-white);
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

        /* ============================================
           Layout
           ============================================ */
        .container {
            max-width: 100%;
            padding: 0 var(--spacing-4);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-4) 0;
            border-bottom: 2px solid var(--color-primary);
            margin-bottom: var(--spacing-6);
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }

        .header-logo-icon {
            width: 40px;
            height: 40px;
            background: var(--color-primary);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-white);
            font-weight: bold;
            font-size: var(--font-size-lg);
        }

        .header-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--color-gray-900);
        }

        .header-subtitle {
            font-size: var(--font-size-sm);
            color: var(--color-gray-500);
        }

        .header-meta {
            text-align: right;
        }

        .header-date {
            font-size: var(--font-size-sm);
            color: var(--color-gray-600);
        }

        .footer {
            margin-top: var(--spacing-8);
            padding-top: var(--spacing-4);
            border-top: 1px solid var(--color-gray-200);
            text-align: center;
            font-size: var(--font-size-xs);
            color: var(--color-gray-400);
        }

        /* ============================================
           Typography
           ============================================ */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            line-height: 1.25;
            color: var(--color-gray-900);
        }

        h1 { font-size: var(--font-size-3xl); margin-bottom: var(--spacing-6); }
        h2 { font-size: var(--font-size-2xl); margin-bottom: var(--spacing-5); }
        h3 { font-size: var(--font-size-xl); margin-bottom: var(--spacing-4); }
        h4 { font-size: var(--font-size-lg); margin-bottom: var(--spacing-3); }

        .section-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: var(--spacing-4);
            padding-bottom: var(--spacing-2);
            border-bottom: 2px solid var(--color-primary-light);
        }

        .text-muted { color: var(--color-gray-500); }
        .text-success { color: var(--color-success); }
        .text-warning { color: var(--color-warning); }
        .text-danger { color: var(--color-danger); }
        .text-primary { color: var(--color-primary); }

        .text-sm { font-size: var(--font-size-sm); }
        .text-xs { font-size: var(--font-size-xs); }
        .text-lg { font-size: var(--font-size-lg); }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }

        /* ============================================
           Cards
           ============================================ */
        .card {
            background: var(--color-white);
            border: 1px solid var(--color-gray-200);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-5);
            page-break-inside: avoid;
        }

        .card-header {
            padding: var(--spacing-4);
            background: var(--color-gray-50);
            border-bottom: 1px solid var(--color-gray-200);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .card-header-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--color-gray-800);
            margin: 0;
        }

        .card-body {
            padding: var(--spacing-4);
        }

        .card-footer {
            padding: var(--spacing-3) var(--spacing-4);
            background: var(--color-gray-50);
            border-top: 1px solid var(--color-gray-200);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        /* ============================================
           Tables
           ============================================ */
        .table-container {
            overflow-x: auto;
            margin-bottom: var(--spacing-4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--font-size-sm);
        }

        table th,
        table td {
            padding: var(--spacing-2) var(--spacing-3);
            text-align: left;
            border-bottom: 1px solid var(--color-gray-200);
        }

        table thead th {
            background: var(--color-gray-50);
            font-weight: 600;
            color: var(--color-gray-700);
            text-transform: uppercase;
            font-size: var(--font-size-xs);
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--color-gray-300);
        }

        table tbody tr:hover {
            background: var(--color-gray-50);
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-striped tbody tr:nth-child(even) {
            background: var(--color-gray-50);
        }

        .table-compact th,
        .table-compact td {
            padding: var(--spacing-1) var(--spacing-2);
        }

        /* Numeric columns */
        .col-number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ============================================
           Grid Layout
           ============================================ */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 calc(var(--spacing-3) * -1);
        }

        .col { flex: 1; padding: 0 var(--spacing-3); }
        .col-6 { flex: 0 0 50%; max-width: 50%; padding: 0 var(--spacing-3); }
        .col-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 var(--spacing-3); }
        .col-3 { flex: 0 0 25%; max-width: 25%; padding: 0 var(--spacing-3); }
        .col-12 { flex: 0 0 100%; max-width: 100%; padding: 0 var(--spacing-3); }

        /* ============================================
           Stats & Metrics
           ============================================ */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-5);
        }

        .stat-card {
            background: var(--color-gray-50);
            border: 1px solid var(--color-gray-200);
            border-radius: var(--border-radius);
            padding: var(--spacing-4);
            text-align: center;
        }

        .stat-value {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1.2;
        }

        .stat-label {
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-gray-500);
            margin-top: var(--spacing-1);
        }

        /* ============================================
           Charts
           ============================================ */
        .chart-container {
            text-align: center;
            margin: var(--spacing-4) 0;
            page-break-inside: avoid;
        }

        .chart-container img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .chart-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--color-gray-700);
            margin-bottom: var(--spacing-3);
        }

        /* ============================================
           Progress Bars
           ============================================ */
        .progress {
            height: 8px;
            background: var(--color-gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-bar-success { background: var(--color-success); }
        .progress-bar-warning { background: var(--color-warning); }
        .progress-bar-danger { background: var(--color-danger); }
        .progress-bar-primary { background: var(--color-primary); }

        /* ============================================
           Badges & Labels
           ============================================ */
        .badge {
            display: inline-block;
            padding: var(--spacing-1) var(--spacing-2);
            font-size: var(--font-size-xs);
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #dcfce7;
            color: var(--color-success);
        }

        .badge-warning {
            background: #fef3c7;
            color: var(--color-warning);
        }

        .badge-danger {
            background: #fee2e2;
            color: var(--color-danger);
        }

        .badge-primary {
            background: #dbeafe;
            color: var(--color-primary);
        }

        .badge-secondary {
            background: var(--color-gray-200);
            color: var(--color-gray-600);
        }

        /* ============================================
           Detail Lists
           ============================================ */
        .detail-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-3);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-gray-500);
            margin-bottom: var(--spacing-1);
        }

        .detail-value {
            font-size: var(--font-size-base);
            font-weight: 500;
            color: var(--color-gray-800);
        }

        /* ============================================
           Utility Classes
           ============================================ */
        .mb-1 { margin-bottom: var(--spacing-1); }
        .mb-2 { margin-bottom: var(--spacing-2); }
        .mb-3 { margin-bottom: var(--spacing-3); }
        .mb-4 { margin-bottom: var(--spacing-4); }
        .mb-5 { margin-bottom: var(--spacing-5); }
        .mb-6 { margin-bottom: var(--spacing-6); }

        .mt-1 { margin-top: var(--spacing-1); }
        .mt-2 { margin-top: var(--spacing-2); }
        .mt-3 { margin-top: var(--spacing-3); }
        .mt-4 { margin-top: var(--spacing-4); }
        .mt-5 { margin-top: var(--spacing-5); }
        .mt-6 { margin-top: var(--spacing-6); }

        .p-3 { padding: var(--spacing-3); }
        .p-4 { padding: var(--spacing-4); }

        .bg-gray-50 { background: var(--color-gray-50); }
        .bg-white { background: var(--color-white); }

        .border { border: 1px solid var(--color-gray-200); }
        .border-t { border-top: 1px solid var(--color-gray-200); }
        .border-b { border-bottom: 1px solid var(--color-gray-200); }

        .rounded { border-radius: var(--border-radius); }

        /* ============================================
           Specific Components for Reports
           ============================================ */
        .summary-box {
            background: linear-gradient(135deg, var(--color-primary) 0%, #1e3a8a 100%);
            color: var(--color-white);
            padding: var(--spacing-5);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-5);
        }

        .summary-box h2 {
            color: var(--color-white);
            margin-bottom: var(--spacing-4);
        }

        .summary-box .stat-value {
            color: var(--color-white);
        }

        .summary-box .stat-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .goal-item {
            background: var(--color-gray-50);
            border: 1px solid var(--color-gray-200);
            border-radius: var(--border-radius);
            padding: var(--spacing-4);
            margin-bottom: var(--spacing-3);
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-2);
        }

        .goal-name {
            font-weight: 600;
            color: var(--color-gray-800);
        }

        .goal-progress {
            font-size: var(--font-size-sm);
            color: var(--color-gray-600);
        }

        .transaction-positive {
            color: var(--color-success);
        }

        .transaction-negative {
            color: var(--color-danger);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-logo">
                <div class="header-logo-icon">FF</div>
                <div>
                    <div class="header-title">{{ config('app.name') }}</div>
                    <div class="header-subtitle">@yield('report-type', 'Quarterly Report')</div>
                </div>
            </div>
            <div class="header-meta">
                <div class="header-date">Report Date: {{ $asOf ?? now()->format('Y-m-d') }}</div>
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
            <p class="text-xs mt-1">This report is confidential and intended for the named recipient only.</p>
        </footer>
    </div>
</body>
</html>
