<div>
    <canvas id="perfGraph{{$group}}"></canvas>
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
    const rawLabelsAssets = Object.keys(perf.SP500 || perf[Object.keys(perf)[0]]);
    const sparseLabelsAssets = createSparseLabels(rawLabelsAssets);
    const datasets = [];

    let colorIndex = 0;
    for (let symbol in perf) {
        const firstDate = Object.keys(perf[symbol])[0];
        const firstValue = perf[symbol][firstDate].price;
        const data = Object.values(perf[symbol]).map(function(e) {
            return e.price / firstValue;
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
        });
        colorIndex++;
    }

    new Chart(
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
    );
})();
</script>
@endpush
