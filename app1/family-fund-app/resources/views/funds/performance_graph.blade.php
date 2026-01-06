<div>
    <canvas id="perfGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
const perf_labels = Object.keys(api.yearly_performance);
const perf_values = Object.values(api.yearly_performance).map(function(e) { return e.value; });

// Create background colors - last bar blue (primary), others gray (secondary)
const barColors = perf_values.map((_, i) =>
    i === perf_values.length - 1 ? chartTheme.primary : chartTheme.secondary
);

const perf_data = {
    labels: perf_labels,
    datasets: [{
        label: 'Value',
        data: perf_values,
        backgroundColor: barColors,
        borderColor: barColors,
        borderWidth: 1,
    }]
};

const perf_config = {
    type: 'bar',
    data: perf_data,
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
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
                        size: 12,
                        weight: '500',
                    }
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
                color: '#ffffff',
                anchor: 'center',
                align: 'center',
                font: {
                    weight: 'bold',
                    size: 14,
                },
                formatter: function(value) {
                    return formatCurrency(value);
                },
                // Hide label if bar is too small
                display: function(context) {
                    return context.dataset.data[context.dataIndex] > 0;
                },
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return formatCurrency(context.raw, 2);
                    }
                }
            }
        }
    },
};

var myChart = new Chart(
    document.getElementById('perfGraph'),
    perf_config
);
</script>
@endpush
