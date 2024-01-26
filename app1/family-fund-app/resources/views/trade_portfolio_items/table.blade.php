<div class="table-responsive-sm">
    <table class="table table-striped" id="tradePortfolioItems-table">
        <thead>
            <tr>
                <th>Trade Portfolio Id</th>
                <th>Symbol</th>
                <th>Type</th>
                <th>Target Share</th>
                <th>Deviation trigger</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($tradePortfolioItems as $tradePortfolioItem)
            <tr>
                <td>{{ $tradePortfolioItem->trade_portfolio_id }}</td>
                <td>{{ $tradePortfolioItem->symbol }}</td>
                <td>{{ $tradePortfolioItem->type }}</td>
                <td>{{ $tradePortfolioItem->target_share * 100 }}%</td>
                <td>{{ $tradePortfolioItem->deviation_trigger * 100}}%</td>
                <td>
                    {!! Form::open(['route' => ['tradePortfolioItems.destroy', $tradePortfolioItem->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('tradePortfolioItems.show', [$tradePortfolioItem->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('tradePortfolioItems.edit', [$tradePortfolioItem->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
