@php($editable = \Carbon\Carbon::parse($tradePortfolio->end_dt)->isBefore($asOf))
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <strong>Trade Portfolio {{ $extraTitle ?? '' }}</strong>
                <a href="{{ route('tradePortfolios.index') }}" class="btn btn-light">Back</a>
                @if($editable)
                    {{-- If end_dt is before today, don't display the buttons --}}
                @else
                    <div class='btn-group'>
                        <a href="{{ route('tradePortfolios.edit', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('tradePortfolios.split', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-code-fork"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger no_mobile', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                @endif
            </div>
            <div class="card-body">
                @include('trade_portfolios.show_fields')
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <strong>Trade Portfolio Items {{ $extraTitle ?? '' }}</strong>
            </div>
            <div class="card-body">
                @php($tradePortfolioItems = $tradePortfolio['items'])
                @include('trade_portfolio_items.table')
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <strong>Trade Portfolio Group Targets {{ $extraTitle ?? '' }}</strong>
            </div>
            <div class="card-body">
                @include('trade_portfolios.group_table')
            </div>
        </div>
    </div>
</div>
