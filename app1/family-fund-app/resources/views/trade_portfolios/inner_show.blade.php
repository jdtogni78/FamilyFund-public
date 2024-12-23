@php($editable = \Carbon\Carbon::parse($tradePortfolio->end_dt)->isBefore($asOf))
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <a class="btn btn-primary" data-toggle="collapse" href="#collapseTP" 
                role="button" aria-expanded="false" aria-controls="collapseTP">
                Trade Portfolio {{ $extraTitle ?? '' }}
                </a>
                <a href="{{ route('tradePortfolios.index') }}" class="btn btn-light">Back</a>
                @if($editable)
                    {{-- If end_dt is before today, don't display the buttons --}}
                @else
                    <div class='btn-group'>
                        <a href="{{ route('tradePortfolios.edit', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('tradePortfolios.split', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-code-fork"></i></a>
                        <a href="{{ route('fund.show_trade_bands', [$tradePortfolio->portfolio()->fund()->first()->id, $tradePortfolio->id, $asOf]) }}" class='btn btn-ghost-info'><i class="fa fa-wave-pulse"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger no_mobile', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                @endif
            </div>
            <div class="collapse" id="collapseTP">
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
                <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPIT" 
                role="button" aria-expanded="false" aria-controls="collapseTPIT">
                Trade Portfolio Items {{ $extraTitle ?? '' }}
                </a>
            </div>
            <div class="collapse" id="collapseTPIT">
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
                <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPGT" 
                role="button" aria-expanded="false" aria-controls="collapseTPGT">
                Trade Portfolio Group Targets {{ $extraTitle ?? '' }}
                </a>
            </div>
            <div class="collapse" id="collapseTPGT">
                <div class="card-body">
                    @include('trade_portfolios.group_table')
                </div>
            </div>
        </div>
    </div>
</div>
