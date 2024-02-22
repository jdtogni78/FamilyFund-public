<div class="table-responsive-sm">
    <table class="table table-striped" id="tradePortfolios-table">
        <thead>
        <tr>
            <th class="no_mobile">Id</th>
            <th class="no_mobile">Account Name</th>
            <th class="no_mobile">Portfolio Id</th>
            <th class="no_mobile">Portfolio Source</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th class="no_mobile">Cash Target</th>
            <th class="no_mobile">Cash Reserve Target</th>
            <th class="no_mobile">Max Single Order</th>
            <th class="no_mobile">Minimum Order</th>
            <th class="no_mobile">Rebalance Period</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tradePortfolios as $tradePortfolio)
            <tr>
                <td class="no_mobile">{{ $tradePortfolio->id }}</td>
                <td class="no_mobile">{{ $tradePortfolio->account_name }}</td>
                <td class="no_mobile">{{ $tradePortfolio->portfolio_id }}</td>
                <td class="no_mobile">{{ $tradePortfolio->portfolio_id? $tradePortfolio->portfolio()->source : "N/A" }}</td>
                <td>{{ $tradePortfolio->start_dt->format('Y-m-d') }}</td>
                <td>{{ $tradePortfolio->end_dt->format('Y-m-d') }}</td>
                <td class="no_mobile">{{ $tradePortfolio->cash_target * 100 }}%</td>
                <td class="no_mobile">{{ $tradePortfolio->cash_reserve_target * 100 }}%</td>
                <td class="no_mobile">{{ $tradePortfolio->max_single_order * 100 }}%</td>
                <td class="no_mobile">${{ $tradePortfolio->minimum_order }}</td>
                <td class="no_mobile">{{ $tradePortfolio->rebalance_period }} days</td>
                <td>
                    {!! Form::open(['route' => ['tradePortfolios.destroy', $tradePortfolio->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('tradePortfolios.show', [$tradePortfolio->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('tradePortfolios.edit', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('tradePortfolios.split', [$tradePortfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-code-fork"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger no_mobile', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
