<div>
    <canvas id="accountGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        var api = {!! json_encode($api) !!};

        // Calculate total shares for percentage calculation
        const totalShares = api.summary.total_shares;
        const unallocatedShares = api.summary.unallocated_shares;

        // Prepare data with percentages
        let accountData = api.balances.map(function(e) {
            return {
                label: e.nickname,
                shares: e.shares,
                percent: (e.shares / totalShares) * 100
            };
        });

        // Group small accounts (<3%) into "Others"
        const threshold = 3;
        let othersShares = 0;
        let othersCount = 0;
        let filteredData = [];

        accountData.forEach(function(item) {
            if (item.percent < threshold) {
                othersShares += item.shares;
                othersCount++;
            } else {
                filteredData.push(item);
            }
        });

        // Sort by percentage descending (largest first)
        filteredData.sort((a, b) => b.percent - a.percent);

        // Add "Others" at the end if there are small accounts
        if (othersCount > 0) {
            filteredData.push({
                label: 'Others (' + othersCount + ' accounts)',
                shares: othersShares,
                percent: (othersShares / totalShares) * 100
            });
        }

        // Add unallocated shares at the very bottom
        if (unallocatedShares > 0) {
            filteredData.push({
                label: 'Unallocated',
                shares: unallocatedShares,
                percent: (unallocatedShares / totalShares) * 100
            });
        }

        const labels = filteredData.map(function(e) { return e.label; });
        const data = filteredData.map(function(e) { return e.shares; });
        const percents = filteredData.map(function(e) { return e.percent; });

        createDoughnutChart('accountGraph', labels, data, {
            tooltipFormatter: function(context) {
                const percent = percents[context.dataIndex];
                return context.label + ': ' + formatNumber(context.raw, 2) + ' shares (' + percent.toFixed(1) + '%)';
            }
        });
    } catch (e) {
        console.error('Error creating accounts chart:', e);
    }
});
</script>
@endpush
