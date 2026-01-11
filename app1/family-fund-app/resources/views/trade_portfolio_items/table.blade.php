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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($tradePortfolioItems as $tradePortfolioItem)
            <tr>
                <td>{{ $tradePortfolioItem->id }}</td>
                <td>{{ $tradePortfolioItem->trade_portfolio_id }}</td>
                <td>{{ $tradePortfolioItem->symbol }}</td>
                <td>{{ $tradePortfolioItem->type }}</td>
                <td>{{ $tradePortfolioItem->group }}</td>
                <td>{{ $tradePortfolioItem->target_share * 100 }}% <span class="text-muted" style="font-size: 0.85rem;">±{{ $tradePortfolioItem->deviation_trigger * 100 }}%</span></td>
                <td>
                    <form action="{{ route('tradePortfolioItems.destroy', $tradePortfolioItem->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('tradePortfolioItems.show', [$tradePortfolioItem->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            @if(!($editable ?? false))
                                <a href="{{ route('tradePortfolioItems.edit', [$tradePortfolioItem->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                                <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this trade portfolio item?')"><i class="fa fa-trash"></i></button>
                            @endif
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#tradePortfolioItems-table').DataTable();
        });
    </script>
@endpush
