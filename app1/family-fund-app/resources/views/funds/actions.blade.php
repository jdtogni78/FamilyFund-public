<form action="{{ route('funds.destroy', $fund->id) }}" method="DELETE">
    @csrf
    <div class='btn-group'>
        <a href="{{ route('funds.show', [$fund->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
        <a href="{{ route('funds.show_trade_bands', [$fund->id]) }}" class='btn btn-ghost-success'><i class="fa fa-wave-square"></i></a>
        @if($fund->portfolios()->first())
            <a href="{{ route('portfolios.showRebalance', [$fund->portfolios()->first()->id, now()->subMonths(3)->format('Y-m-d'), now()->format('Y-m-d')]) }}"
               class='btn btn-ghost-primary' title="Rebalance Analysis (last 3 months)"><i class="fa fa-chart-line"></i></a>
        @endif
        <a href="{{ route('funds.edit', [$fund->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
        <a href="{{ route('portfolios.show', [$fund->portfolios()->first()]) }}" class='btn btn-ghost-info'><i class="fa fa-eye"></i></a>
        <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this fund?')"><i class="fa fa-trash"></i></button>
    </div>
</form>
