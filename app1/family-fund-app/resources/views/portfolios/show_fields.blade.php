<!-- Fund Id Field -->
<div class="form-group">
    {!! Form::label('fund_id', 'Fund Id:') !!}
    <p>{{ $portfolio->fund_id }}</p>
</div>

<!-- Source Field -->
<div class="form-group">
    {!! Form::label('source', 'Source:') !!}
    <p>{{ $portfolio->source }}</p>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <strong>Trade Portfolios</strong>
            </div>
            <div class="card-body">
                @php($tradePortfolios = $portfolio->tradePortfolios()->get())
                @include('trade_portfolios.table')
            </div>
        </div>
    </div>
</div>
