<div>
    <canvas id="assetsGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};

const assets_labels = api.portfolio.assets.map(function(e) { return e.name; });
const assets_values = api.portfolio.assets.map(function(e) { return e.value; });

// Calculate total for percentages
const total = assets_values.reduce((sum, v) => sum + v, 0);

const assets_config = {
    type: 'doughnut',
    data: {
        labels: assets_labels,
        datasets: [{
            data: assets_values,
            backgroundColor: graphColors.slice(0, assets_labels.length),
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    color: '#000000',
                    font: {
                        size: 14,
                        weight: 'bold',
                    },
                    padding: 10,
                }
            },
            datalabels: {
                color: '#000000',
                font: {
                    size: 12,
                    weight: 'bold',
                },
                formatter: function(value, context) {
                    const percent = (value / total) * 100;
                    if (percent < 5) return ''; // Hide labels on small slices
                    return percent.toFixed(1) + '%';
                },
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const percent = (value / total) * 100;
                        return context.label + ': ' + formatCurrency(value, 2) + ' (' + percent.toFixed(2) + '%)';
                    }
                }
            }
        }
    },
};

var assetsChart = new Chart(
    document.getElementById('assetsGraph'),
    assets_config
);
</script>
@endpush
