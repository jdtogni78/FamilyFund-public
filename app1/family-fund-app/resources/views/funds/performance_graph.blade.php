<div style="position: relative; z-index: 1;">
    <canvas id="perfGraph" style="display: block !important; visibility: visible !important;"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
const rawPerfLabels = Object.keys(api.yearly_performance);
// Extract end date from range labels like "2024-01-01 to 2025-01-01"
const perf_labels = rawPerfLabels.map(label => {
    const parts = label.split(' to ');
    const endDate = parts.length > 1 ? parts[1] : label;
    // Format as "Jan 2025"
    const date = new Date(endDate);
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
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
                anchor: 'end',
                align: 'start',
                offset: 4,
                font: {
                    weight: 'bold',
                    size: 11,
                },
                formatter: function(value) {
                    return formatCurrencyShort(value);
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
