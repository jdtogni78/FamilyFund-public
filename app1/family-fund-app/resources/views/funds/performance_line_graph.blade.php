<div style="position: relative; z-index: 1;">
    <canvas id="perfGraphMonthly" style="display: block !important; visibility: visible !important;"></canvas>
</div>
<div class="col-xs-12 mt-2">
    <ul class="small text-muted">
        <li><b>Monthly Value</b>: the performance of this fund</li>
        <li><b>S&P 500</b>: the performance of a fund that would invest the same amount of funds 100% on S&P 500</li>
        <li><b>Cash</b>: the performance of a fund that would invest the same amount of funds 100% on Cash</li>
    </ul>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};

const rawLabels = Object.keys(api.monthly_performance);
const sparseLabels = createSparseLabels(rawLabels);
const data = Object.values(api.monthly_performance).map(function(e) { return e.value; });

let datasets = [{
    label: 'Monthly Value',
    data: data,
    backgroundColor: graphColors[0],
    borderColor: graphColors[0],
    borderWidth: 2,
    pointRadius: 2,
    pointHoverRadius: 4,
    tension: 0.1,
    fill: false,
}];

const addSP500 = {!! $addSP500 !!};
if (addSP500) {
    const sp500 = Object.values(api.sp500_monthly_performance).map(function(e) { return e.value; });
    datasets.push({
        label: 'S&P 500',
        data: sp500,
        backgroundColor: graphColors[1],
        borderColor: graphColors[1],
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 4,
        tension: 0.1,
        fill: false,
    });
}

datasets.push({
    label: 'Cash',
    data: Object.values(api.cash).map(function(e) { return e.value; }),
    backgroundColor: graphColors[2],
    borderColor: graphColors[2],
    borderWidth: 2,
    pointRadius: 2,
    pointHoverRadius: 4,
    tension: 0.1,
    fill: false,
});

var perfMonthlyChart = registerChart(new Chart(
    document.getElementById('perfGraphMonthly'),
    {
        type: 'line',
        data: {
            labels: sparseLabels,
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
                            return formatCurrency(value);
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
                    display: false, // No data labels on line charts
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            // Show the actual date from rawLabels
                            return rawLabels[context[0].dataIndex];
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.raw, 2);
                        }
                    }
                }
            }
        },
    }
));
</script>
@endpush
