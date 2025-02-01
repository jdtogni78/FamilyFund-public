<div class="table-responsive-sm">
    <table class="table table-striped" id="transactionMatchings-table">
        <thead>
            <tr>
                <th>Matching Rule Id</th>
        <th>Transaction Id</th>
        <th>Reference Transaction Id</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transactionMatchings as $transactionMatching)
            <tr>
                <td>{{ $transactionMatching->matching_rule_id }}</td>
            <td>{{ $transactionMatching->transaction_id }}</td>
            <td>{{ $transactionMatching->reference_transaction_id }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('transactionMatchings.show', [$transactionMatching->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('transactionMatchings.edit', [$transactionMatching->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('transactionMatchings.destroy', $transactionMatching->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this transaction matching?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>