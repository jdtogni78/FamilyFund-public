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

        // Add "Others" if there are small accounts
        if (othersCount > 0) {
            filteredData.push({
                label: 'Others (' + othersCount + ' accounts)',
                shares: othersShares,
                percent: (othersShares / totalShares) * 100
            });
        }

        // Add unallocated shares
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

        // Debug logging
        console.log('=== ACCOUNTS CHART DEBUG ===');
        console.log('totalShares:', totalShares, typeof totalShares);
        console.log('api.balances:', api.balances);
        console.log('filteredData:', filteredData);
        console.log('labels:', labels);
        console.log('data:', data);
        console.log('percents:', percents);

        // Check for any NaN in percents
        percents.forEach((p, i) => {
            if (isNaN(p) || p === null || p === undefined) {
                console.error('NaN/null at index', i, '- label:', labels[i], 'shares:', data[i]);
            }
        });

        createDoughnutChart('accountGraph', labels, data, {
            tooltipFormatter: function(context) {
                const idx = context.dataIndex;
                const percent = percents[idx];
                console.log('Tooltip hover - idx:', idx, 'label:', context.label, 'raw:', context.raw, 'percent:', percent, 'percents.length:', percents.length);

                if (percent === undefined || percent === null || isNaN(percent)) {
                    console.error('BAD PERCENT at idx', idx, '- percents array:', percents);
                    return context.label + ': ' + formatNumber(context.raw, 2) + ' shares';
                }
                return context.label + ': ' + formatNumber(context.raw, 2) + ' shares (' + percent.toFixed(1) + '%)';
            }
        });
    } catch (e) {
        console.error('Error creating accounts chart:', e);
    }
});
</script>
@endpush
