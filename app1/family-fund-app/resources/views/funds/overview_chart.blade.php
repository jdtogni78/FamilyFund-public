{{-- Net Worth Line Chart --}}
<div style="position: relative; z-index: 1; height: 400px;">
    <canvas id="overviewChart" style="display: block !important; visibility: visible !important;"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
(function() {
    var chartData = overviewApi.chartData;
    var rawLabels = chartData.labels;
    var sparseLabels = createSparseLabels(rawLabels);
    var values = chartData.values;

    // Determine fill color based on overall trend
    var startValue = values[0] || 0;
    var endValue = values[values.length - 1] || 0;
    var isPositive = endValue >= startValue;
    var lineColor = isPositive ? '#0d9488' : '#dc2626';
    var fillColor = isPositive ? 'rgba(13, 148, 136, 0.1)' : 'rgba(220, 38, 38, 0.1)';

    window.overviewChart = registerChart(new Chart(
        document.getElementById('overviewChart'),
        {
            type: 'line',
            data: {
                labels: sparseLabels,
                datasets: [{
                    label: 'Net Worth',
                    data: values,
                    backgroundColor: fillColor,
                    borderColor: lineColor,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: lineColor,
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    tension: 0.1,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return formatCurrencyShort(value);
                            },
                            color: chartTheme.fontColor,
                            font: {
                                size: 12,
                                weight: '500',
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        }
                    },
                    x: {
                        ticks: {
                            color: chartTheme.fontColor,
                            font: {
                                size: 11,
                            },
                            maxRotation: 45,
                            minRotation: 0,
                        },
                        grid: {
                            display: false,
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    datalabels: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: '600' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            title: function(context) {
                                return rawLabels[context[0].dataIndex];
                            },
                            label: function(context) {
                                return formatCurrency(context.raw, 0);
                            }
                        }
                    }
                }
            },
        }
    ));

    // Store raw labels for updates
    window.overviewChart.rawLabels = rawLabels;
})();
</script>
@endpush
