@php($editable = \Carbon\Carbon::parse($tradePortfolio->end_dt)->isBefore($asOf))
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <a class="btn btn-primary" data-toggle="collapse" href="#collapseTP{{ $tradePortfolio->id }}" 
                role="button" aria-expanded="false" aria-controls="collapseTP{{ $tradePortfolio->id }}">
                Trade Portfolio {{ $extraTitle ?? '' }}
                </a>
                <a href="{{ route('tradePortfolios.index') }}" class="btn btn-light">Back</a>
                @if($editable)
                    {{-- If end_dt is before today, don't display the buttons --}}
                @else
                    <div class='btn-group'>
                        <a href="{{ route('tradePortfolios.edit', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('tradePortfolios.split', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-code-fork"></i></a>
                        <a href="{{ route('funds.show_trade_bands', [$tradePortfolio->portfolio->fund()->first()->id, $tradePortfolio->id, $asOf]) }}" class='btn btn-ghost-info'><i class="fa fa-wave-square"></i></a>
                        <a href="{{ route('tradePortfolios.show_diff', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-random"></i></a>
                        <a href="{{ route('tradePortfolios.preview_deposits', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-download"></i></a>
                        <button type="submit" class="btn btn-ghost-danger no_mobile" onclick="return confirm('Are you sure?')"><i class="fa fa-trash"></i></button>
                    </div>
                @endif
            </div>
            <div class="collapse" id="collapseTP{{ $tradePortfolio->id }}">
                <div class="card-body">
                    @include('trade_portfolios.show_fields')
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPIT{{ $tradePortfolio->id }}" 
                role="button" aria-expanded="false" aria-controls="collapseTPIT{{ $tradePortfolio->id }}">
                Trade Portfolio Items {{ $extraTitle ?? '' }}
                </a>
                <a href="{{ route('tradePortfoliosItems.createWithParams', ['tradePortfolioId' => $tradePortfolio->id]) }}" class="btn btn-ghost-info"><i class="fa fa-plus"></i></a>
            </div>
            <div class="collapse" id="collapseTPIT{{ $tradePortfolio->id }}">
                <div class="card-body">
                    @php($tradePortfolioItems = $tradePortfolio['items'])
                    @include('trade_portfolio_items.table')
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPGT{{ $tradePortfolio->id }}" 
                role="button" aria-expanded="false" aria-controls="collapseTPGT{{ $tradePortfolio->id }}">
                Trade Portfolio Group Targets {{ $extraTitle ?? '' }}
                </a>
            </div>
            <div class="collapse" id="collapseTPGT{{ $tradePortfolio->id }}">
                <div class="card-body">
                    @include('trade_portfolios.group_table')
                </div>
            </div>
        </div>
    </div>
</div>
