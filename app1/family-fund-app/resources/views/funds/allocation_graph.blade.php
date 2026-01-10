<div style="position: relative; z-index: 1;">
    <canvas id="allocationGraph" style="display: block !important; visibility: visible !important;"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        var api = {!! json_encode($api) !!};

        const allocatedPercent = api.summary.allocated_shares_percent;
        const unallocatedPercent = api.summary.unallocated_shares_percent;
        const labels = ['Allocated', 'Unallocated'];
        const data = [allocatedPercent, unallocatedPercent];

        // Custom colors for allocation chart
        new Chart(document.getElementById('allocationGraph'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [chartTheme.success, chartTheme.secondary],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: getDoughnutOptions(data.map(v => v > 1 ? v : v * 100), {
                labelFormatter: function(value, context) {
                    let percent = value > 1 ? value : value * 100;
                    if (percent < 5) return '';
                    return percent.toFixed(1) + '%';
                },
                tooltipFormatter: function(context) {
                    let value = context.raw;
                    let percent = value > 1 ? value : value * 100;
                    return context.label + ': ' + percent.toFixed(1) + '%';
                }
            })
        });
    } catch (e) {
        console.error('Error creating allocation chart:', e);
    }
});
</script>
@endpush
