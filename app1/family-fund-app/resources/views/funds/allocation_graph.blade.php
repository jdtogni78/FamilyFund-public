<div>
    <canvas id="allocationGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};

const allocatedPercent = api.summary.allocated_shares_percent;
const unallocatedPercent = api.summary.unallocated_shares_percent;

const alloc_data = {
    labels: ['Allocated', 'Unallocated'],
    datasets: [{
        data: [allocatedPercent, unallocatedPercent],
        backgroundColor: [chartTheme.success, chartTheme.secondary],
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverOffset: 4
    }]
};

const alloc_config = {
    type: 'doughnut',
    data: alloc_data,
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
                    padding: 15,
                }
            },
            datalabels: {
                color: '#000000',
                font: {
                    size: 14,
                    weight: 'bold',
                },
                formatter: function(value, context) {
                    // Handle both decimal (0.256) and percentage (25.6) values
                    let percent = value > 1 ? value : value * 100;
                    if (percent < 5) return ''; // Hide small slices
                    return percent.toFixed(1) + '%';
                },
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.raw;
                        let percent = value > 1 ? value : value * 100;
                        return context.label + ': ' + percent.toFixed(2) + '%';
                    }
                }
            }
        }
    },
};

var allocationChart = new Chart(
    document.getElementById('allocationGraph'),
    alloc_config
);
</script>
@endpush
