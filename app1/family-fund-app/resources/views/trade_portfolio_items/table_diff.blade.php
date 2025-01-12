<div class="table-responsive-sm">
    <table class="table table-striped" id="tradePortfolioItems-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Trade Portfolio Id</th>
                <th>Symbol</th>
                <th>Type</th>
                <th>Group</th>
                <th>Target Share</th>
                <th>Deviation trigger</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['old']['items'] as $tradePortfolioItem)
            @php
                // find the new item with the same symbol
                $newItem = collect($api['new']['items'])->firstWhere('symbol', $tradePortfolioItem['symbol']);
            @endphp
            <tr>
                <td>{{ $tradePortfolioItem['id'] }}
                    @if($newItem)
                        -> <span class="text-success">{{ $newItem['id'] }}</span>
                    @endif
                </td>
                <td>{{ $tradePortfolioItem['trade_portfolio_id'] }} 
                    @if($newItem)
                        -> <span class="text-success">{{ $newItem['trade_portfolio_id'] }}</span>
                    @endif
                </td>
                <td>{{ $tradePortfolioItem['symbol'] }}
                    @if(!isset($newItem))
                        -> <span class="text-danger">Removed</span>
                    @endif
                </td>
                <td>{{ $tradePortfolioItem['type'] }}
                    @if($newItem && $newItem['type'] != $tradePortfolioItem['type'])
                        -> <span class="text-success">{{ $newItem['type'] }}</span>
                    @endif
                </td>
                <td>{{ $tradePortfolioItem['group'] }}
                    @if($newItem && $newItem['group'] != $tradePortfolioItem['group'])
                        -> <span class="text-success">{{ $newItem['group'] }}</span>
                    @endif
                </td>
                <td>{{ $tradePortfolioItem['target_share'] * 100 }}%
                    @if($newItem && $newItem['target_share'] != $tradePortfolioItem['target_share'])
                        -> <span class="text-success">{{ $newItem['target_share'] * 100 }}%</span>
                    @endif
                </td>
                <td>{{ $tradePortfolioItem['deviation_trigger'] * 100}}%
                    @if($newItem && $newItem['deviation_trigger'] != $tradePortfolioItem['deviation_trigger'])
                        -> <span class="text-success">{{ $newItem['deviation_trigger'] * 100 }}%</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @foreach($api['new']['items'] as $tradePortfolioItem)
            @php
                // find the old item with the same symbol
                $oldItem = collect($api['old']['items'])->firstWhere('symbol', $tradePortfolioItem['symbol']);
            @endphp
            @if(!isset($oldItem))
                <tr>
                    <td>{{ $tradePortfolioItem['id'] }}</td>
                    <td>{{ $tradePortfolioItem['trade_portfolio_id'] }}</td>
                    <td>{{ $tradePortfolioItem['symbol'] }} -> <span class="text-success">Added</span></td>
                    <td>{{ $tradePortfolioItem['type'] }}</td>
                    <td>{{ $tradePortfolioItem['group'] }}</td>
                    <td>{{ $tradePortfolioItem['target_share'] * 100 }}%</td>
                    <td>{{ $tradePortfolioItem['deviation_trigger'] * 100}}%</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
