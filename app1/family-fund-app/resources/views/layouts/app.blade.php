<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Family Fund') }}</title>
        <meta content='width=device-width,
            initial-scale=1,
            maximum-scale=1,
            user-scalable=no' name='viewport'/>

        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/css/coreui.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@icon/coreui-icons-free@1.0.1-alpha.1/coreui-icons-free.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.3.0/css/flag-icon.min.css">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" />
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" />
        <link rel="stylesheet" href="{{ asset('css/navigation.css') }}" />
        
        <script src="https://kit.fontawesome.com/d955b811ba.js" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/js/coreui.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@^3"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
        <script src="https://cdn.jsdelivr.net/npm/moment@^2"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@^1"></script>

        <script type="text/javascript">
        // Register datalabels plugin globally
        Chart.register(ChartDataLabels);

        // Color palette matching PDF reports
        let graphColors = [
            '#2563eb', '#dc2626', '#16a34a', '#d97706', '#9333ea', '#0891b2', '#db2777', '#64748b',
            '#f59e0b', '#10b981', '#6366f1', '#ec4899', '#14b8a6', '#f97316', '#4f46e5', '#059669',
            '#b91c1c', '#7c3aed', '#0369a1', '#c026d3', '#ca8a04', '#0d9488', '#e11d48', '#0284c7',
            '#15803d', '#7e22ce', '#be123c', '#1d4ed8', '#047857', '#a21caf',
        ];

        // Theme colors
        const chartTheme = {
            primary: '#2563eb',
            secondary: '#64748b',
            success: '#16a34a',
            warning: '#d97706',
            danger: '#dc2626',
            fontColor: '#1e293b',
        };

        // Format number with thousand separators
        function formatNumber(num, decimals = 0) {
            return num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        // Format as currency
        function formatCurrency(num, decimals = 0) {
            return '$' + formatNumber(num, decimals);
        }

        // Format as short currency ($10K, $1.5M, etc.)
        function formatCurrencyShort(num) {
            if (num >= 1000000) {
                return '$' + (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
            } else if (num >= 1000) {
                return '$' + (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
            }
            return '$' + Math.round(num);
        }

        // Create sparse labels array for line charts (max 24 labels)
        function createSparseLabels(labels, maxLabels = 24) {
            if (labels.length <= maxLabels) return labels;
            const step = Math.ceil(labels.length / maxLabels);
            return labels.map((label, i) => (i % step === 0) ? label : '');
        }

        // Shared doughnut chart options
        function getDoughnutOptions(percents, options = {}) {
            const legendPosition = options.legendPosition || 'right';
            const showLabels = options.showLabels !== false;
            const labelThreshold = options.labelThreshold || 5;
            const labelFormatter = options.labelFormatter || function(value, context) {
                const percent = percents[context.dataIndex];
                if (!percent || isNaN(percent) || percent < labelThreshold) return '';
                return percent.toFixed(1) + '%';
            };
            const tooltipFormatter = options.tooltipFormatter || function(context) {
                const percent = percents[context.dataIndex];
                const percentStr = (percent && !isNaN(percent)) ? percent.toFixed(1) + '%' : '-';
                return context.label + ': ' + percentStr;
            };

            return {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: legendPosition,
                        labels: {
                            color: chartTheme.fontColor,
                            font: { size: 12, weight: 'bold' },
                            padding: 8,
                        }
                    },
                    datalabels: showLabels ? {
                        color: '#ffffff',
                        font: { size: 11, weight: 'bold' },
                        textShadowColor: 'rgba(0,0,0,0.5)',
                        textShadowBlur: 3,
                        formatter: labelFormatter,
                    } : { display: false },
                    tooltip: {
                        callbacks: {
                            label: tooltipFormatter
                        }
                    }
                }
            };
        }

        // Create a doughnut chart with standard styling
        function createDoughnutChart(canvasId, labels, data, options = {}) {
            // Clean data - convert NaN/null/undefined to 0
            const cleanData = data.map(v => (v && !isNaN(v)) ? parseFloat(v) : 0);
            const total = cleanData.reduce((a, b) => a + b, 0);
            const percents = total > 0 ? cleanData.map(v => (v / total) * 100) : cleanData.map(() => 0);

            return new Chart(document.getElementById(canvasId), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: cleanData,
                        backgroundColor: graphColors.slice(0, labels.length),
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: getDoughnutOptions(percents, options)
            });
        }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js',])
    </head>
    <body class="font-sans antialiased" x-data="{ darkMode: false }">
        <div class="min-h-screen ">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                @include('layouts.flash-messages')

                {{ $slot }}
            </main>
        </div>
    </body>
    @stack('scripts')
</html>
