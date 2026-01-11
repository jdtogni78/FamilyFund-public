<div style="position: relative; z-index: 1;">
    <canvas id="perfGraph{{$group}}" style="display: block !important; visibility: visible !important;"></canvas>
</div>
<div class="col-xs-12 mt-2">
    <ul class="small text-muted">
        <li><b>S&P 500</b>: relative performance if invested 100% in S&P 500</li>
        <li><b>Others</b>: relative performance of other assets (normalized to starting value)</li>
    </ul>
</div>

@push('scripts')
<script type="text/javascript">
(function() {
    const group = '{{$group}}';
    const perf = {!! json_encode($perf) !!};

    // Find earliest date among non-SP500 assets (the fund's actual assets)
    let earliestDate = null;
    for (let symbol in perf) {
        if (symbol === 'SP500') continue;
        const dates = Object.keys(perf[symbol]);
        if (dates.length > 0 && (!earliestDate || dates[0] < earliestDate)) {
            earliestDate = dates[0];
        }
    }

    // Use SP500 dates but trim to start from earliestDate
    const allDates = Object.keys(perf.SP500 || perf[Object.keys(perf)[0]]);
    const rawLabelsAssets = earliestDate
        ? allDates.filter(date => date >= earliestDate)
        : allDates;
    const sparseLabelsAssets = createSparseLabels(rawLabelsAssets);
    const datasets = [];

    let colorIndex = 0;
    for (let symbol in perf) {
        const symbolData = perf[symbol];

        // Find first valid date for this symbol within our trimmed range
        const firstDate = rawLabelsAssets.find(date => symbolData[date] && symbolData[date].price);
        if (!firstDate) continue; // Skip if no valid data
        const firstValue = symbolData[firstDate].price;

        // Align data to rawLabelsAssets dates (fill null for missing dates)
        const data = rawLabelsAssets.map(function(date) {
            if (symbolData[date] && symbolData[date].price) {
                return symbolData[date].price / firstValue;
            }
            return null; // Chart.js skips null values
        });

        datasets.push({
            label: symbol,
            data: data,
            backgroundColor: graphColors[colorIndex % graphColors.length],
            borderColor: graphColors[colorIndex % graphColors.length],
            borderWidth: 2,
            pointRadius: 2,
            pointHoverRadius: 4,
            tension: 0.1,
            fill: false,
            spanGaps: false, // Don't connect across null values
        });
        colorIndex++;
    }

    registerChart(new Chart(
        document.getElementById('perfGraph' + group),
        {
            type: 'line',
            data: {
                labels: sparseLabelsAssets,
                datasets: datasets
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return (value * 100).toFixed(0) + '%';
                            },
                            color: chartTheme.fontColor,
                            font: {
                                size: 12,
                                weight: '500',
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
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
                        position: 'top',
                        labels: {
                            color: chartTheme.fontColor,
                            font: {
                                size: 13,
                                weight: '600',
                            },
                            usePointStyle: true,
                            padding: 15,
                        }
                    },
                    datalabels: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return rawLabelsAssets[context[0].dataIndex];
                            },
                            label: function(context) {
                                const percent = (context.raw * 100).toFixed(2);
                                const sign = context.raw >= 1 ? '+' : '';
                                return context.dataset.label + ': ' + sign + (percent - 100).toFixed(2) + '% from start';
                            }
                        }
                    }
                }
            },
        }
    ));
})();
</script>
@endpush
