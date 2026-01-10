<div style="position: relative; z-index: 1;">
    <canvas id="tradePortfolioGraph{{ $tradePortfolio->id }}" style="display: block !important; visibility: visible !important;"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
    $(document).ready(function() {
        try {
            var items = {!! json_encode($tradePortfolio->tradePortfolioItems) !!};
            var labels = items.map(function(e) { return e.symbol; });
            var data = items.map(function(e) { return e.target_share * 100.0; });

            labels.push('Cash');
            data.push({{ $tradePortfolio->cash_target * 100.0 }});

            createDoughnutChart('tradePortfolioGraph{{ $tradePortfolio->id }}', labels, data, {
                legendPosition: 'top',
                tooltipFormatter: function(context) {
                    const value = context.raw;
                    return context.label + ': ' + value.toFixed(1) + '%';
                }
            });
        } catch (e) {
            console.error('Error creating trade portfolio chart:', e);
        }
    });
    </script>
@endpush
