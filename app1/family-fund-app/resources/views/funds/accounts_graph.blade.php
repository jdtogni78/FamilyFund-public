<div>
    <canvas id="accountGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        var api = {!! json_encode($api) !!};

        // Calculate total shares for percentage calculation
        const totalShares = parseFloat(api.summary.total_shares) || 0;
        const unallocatedShares = parseFloat(api.summary.unallocated_shares) || 0;

        // Prepare data with percentages
        let accountData = api.balances.map(function(e) {
            const shares = parseFloat(e.shares) || 0;
            return {
                label: e.nickname,
                shares: shares,
                percent: totalShares > 0 ? (shares / totalShares) * 100 : 0
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
                percent: totalShares > 0 ? (othersShares / totalShares) * 100 : 0
            });
        }

        // Add unallocated shares at the very bottom
        if (unallocatedShares > 0) {
            filteredData.push({
                label: 'Unallocated',
                shares: unallocatedShares,
                percent: totalShares > 0 ? (unallocatedShares / totalShares) * 100 : 0
            });
        }

        const labels = filteredData.map(function(e) { return e.label; });
        const data = filteredData.map(function(e) { return e.shares; });
        const percents = filteredData.map(function(e) { return e.percent; });

        console.log('Accounts chart data:', { totalShares, labels, data, percents, filteredData });

        createDoughnutChart('accountGraph', labels, data, {
            tooltipFormatter: function(context) {
                const percent = percents[context.dataIndex];
                console.log('Tooltip:', { dataIndex: context.dataIndex, percent, raw: context.raw, label: context.label });
                const percentStr = (percent && !isNaN(percent)) ? percent.toFixed(1) + '%' : '0%';
                return context.label + ': ' + formatNumber(context.raw, 2) + ' shares (' + percentStr + ')';
            }
        });
    } catch (e) {
        console.error('Error creating accounts chart:', e);
    }
});
</script>
@endpush
