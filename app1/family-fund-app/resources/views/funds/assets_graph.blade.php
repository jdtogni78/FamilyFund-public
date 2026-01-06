<div>
    <canvas id="assetsGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        var api = {!! json_encode($api) !!};

        const labels = api.portfolio.assets.map(function(e) { return e.name; });
        const data = api.portfolio.assets.map(function(e) { return parseFloat(e.value) || 0; });
        const total = data.reduce((sum, v) => sum + v, 0);

        createDoughnutChart('assetsGraph', labels, data, {
            tooltipFormatter: function(context) {
                const value = parseFloat(context.raw) || 0;
                const percent = total > 0 ? (value / total) * 100 : 0;
                return context.label + ': ' + formatCurrency(value, 2) + ' (' + percent.toFixed(1) + '%)';
            }
        });
    } catch (e) {
        console.error('Error creating assets chart:', e);
    }
});
</script>
@endpush
