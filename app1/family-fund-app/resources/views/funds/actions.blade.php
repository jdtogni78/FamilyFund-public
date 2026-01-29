@php
    $portfolios = $fund->portfolios()->get();
    $primaryPortfolio = $portfolios->first();
    // Find active trade portfolio from any portfolio
    $activeTradePortfolio = null;
    foreach ($portfolios as $port) {
        $atp = $port->tradePortfolios()
            ->where('end_dt', '>', now())
            ->orderBy('start_dt', 'desc')
            ->first();
        if ($atp) {
            $activeTradePortfolio = $atp;
            break;
        }
    }
@endphp
<form action="{{ route('funds.destroy', $fund->id) }}" method="POST" class="d-inline-block">
    @csrf
    @method('DELETE')
    <div class='btn-group'>
        <a href="{{ route('funds.show', [$fund->id]) }}" class='btn btn-ghost-success' title="View Fund"><i class="fa fa-eye"></i></a>
        <a href="{{ route('funds.show_trade_bands', [$fund->id]) }}" class='btn btn-ghost-success' title="Trade Bands"><i class="fa fa-wave-square"></i></a>
        @if($primaryPortfolio)
            <a href="{{ route('portfolios.showRebalance', [$primaryPortfolio->id, now()->subMonths(3)->format('Y-m-d'), now()->format('Y-m-d')]) }}"
               class='btn btn-ghost-primary' title="Rebalance Analysis (last 3 months)"><i class="fa fa-chart-line"></i></a>
        @endif
        @if($activeTradePortfolio)
            <a href="{{ route('tradePortfolios.rebalance', [$activeTradePortfolio->id]) }}"
               class='btn btn-ghost-warning' title="Edit Allocations"><i class="fa fa-balance-scale"></i></a>
        @endif
        <a href="{{ route('funds.edit', [$fund->id]) }}" class='btn btn-ghost-info' title="Edit Fund"><i class="fa fa-edit"></i></a>
        <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this fund?')" title="Delete Fund"><i class="fa fa-trash"></i></button>
    </div>
</form>
